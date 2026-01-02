<?php
// =================================================================
//  Parceiro de Programação - AJAX Diário de Bordo (v4.0 - Espelho do Portal)
//  - Lógica de Recheio: Cópia fiel do buscar_horario.php
//  - Lógica de WorkID: Filtro estrito (<) + Linha Final (End Time)
//  - Visual: Linha preenchida em todas as colunas, Limpeza nas pontas.
// =================================================================

ob_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'config_km.php';

function enviar_erro_json($msg) {
    ob_clean();
    echo json_encode(['error' => $msg]);
    exit;
}

try {
    $conexao = new mysqli("localhost", "root", "", "relatorio");
    if ($conexao->connect_error) { 
        throw new Exception("Erro de Conexão DB: " . $conexao->connect_error); 
    }
    $conexao->set_charset("utf8mb4");

    $acao = $_GET['acao'] ?? '';
    $data_selecionada = $_GET['data'] ?? '';

    // --- FUNÇÃO AUXILIAR: Formatar Hora GTFS ---
    function formatar_hora_gtfs($hora_db) {
        if (empty($hora_db)) return '-';
        $hora_curta = substr($hora_db, 0, 5);
        $parts = explode(':', $hora_curta);
        if(count($parts) < 2) return $hora_curta;
        $h = (int)$parts[0];
        if ($h >= 24) {
            $h_real = $h - 24;
            return sprintf('%02d:%s', $h_real, $parts[1]);
        }
        return $hora_curta;
    }

    // --- AÇÕES BÁSICAS ---
    if ($acao === 'get_ultima_data') {
        $res = $conexao->query("SELECT MAX(data_viagem) as ultima_data FROM relatorios_viagens");
        $row = $res ? $res->fetch_assoc() : null;
        ob_clean(); echo json_encode(['data' => $row['ultima_data'] ?? date('Y-m-d')]); exit;
    }

    if ($acao === 'listar_linhas') {
        $res = $conexao->query("SELECT DISTINCT ROUTE_ID as linha FROM relatorios_viagens WHERE data_viagem = '$data_selecionada' AND ROUTE_ID IS NOT NULL ORDER BY ROUTE_ID");
        $linhas = []; if ($res) while($r = $res->fetch_assoc()) $linhas[] = $r['linha'];
        ob_clean(); echo json_encode($linhas); exit;
    }

    if ($acao === 'listar_workids') {
        $filtro_linha = $_GET['linha'] ?? '';
        $where_linha = "";
        if ($filtro_linha && $filtro_linha !== 'todas') {
            $where_linha = " AND TRIM(REFERREDVB_COMPANYCODE) IN (SELECT DISTINCT TRIM(BLOCK_NUMBER) FROM relatorios_viagens WHERE data_viagem = '$data_selecionada' AND ROUTE_ID = '$filtro_linha')";
        }
        $sql = "SELECT DISTINCT DUTY_COMPANYCODE as workid 
                FROM relatorios_servicos 
                WHERE '$data_selecionada' BETWEEN data_inicio_vigencia AND data_fim_vigencia 
                $where_linha ORDER BY DUTY_COMPANYCODE ASC";
        $res = $conexao->query($sql);
        $works = []; if ($res) while($r = $res->fetch_assoc()) $works[] = $r['workid'];
        ob_clean(); echo json_encode($works); exit;
    }

    // --- AÇÃO PRINCIPAL: GERAR DIÁRIO ---
    if ($acao === 'gerar_diario') {
        $tipo_busca = $_GET['tipo'] ?? 'workid'; 
        $valor_busca = $_GET['valor'] ?? '';
        if (!$valor_busca) throw new Exception('Valor de busca não informado.');

        $blocos_para_processar = [];

        // 1. Identificar Blocos
        if ($tipo_busca === 'workid') {
            $sql = "SELECT DISTINCT TRIM(REFERREDVB_COMPANYCODE) as bloco
                    FROM relatorios_servicos
                    WHERE TRIM(DUTY_COMPANYCODE) = TRIM('$valor_busca')
                    AND '$data_selecionada' BETWEEN data_inicio_vigencia AND data_fim_vigencia";
            $res = $conexao->query($sql);
            if ($res->num_rows > 0) while ($d = $res->fetch_assoc()) $blocos_para_processar[] = ['bloco' => $d['bloco']];
            else enviar_erro_json("Bloco não encontrado.");

        } elseif ($tipo_busca === 'linha_hora') {
             $hora_ini = $_GET['hora_ini'] ?? '00:00';
            $hora_fim = $_GET['hora_fim'] ?? '23:59';
            $sql = "SELECT DISTINCT TRIM(BLOCK_NUMBER) as bloco FROM relatorios_viagens 
                    WHERE data_viagem = '$data_selecionada' AND ROUTE_ID = '$valor_busca'
                    AND ((START_TIME BETWEEN '$hora_ini' AND '$hora_fim') OR (END_TIME BETWEEN '$hora_ini' AND '$hora_fim') OR (START_TIME < '$hora_ini' AND END_TIME >= '$hora_fim'))
                    ORDER BY BLOCK_NUMBER";
            $res = $conexao->query($sql);
            if ($res->num_rows > 0) while ($d = $res->fetch_assoc()) $blocos_para_processar[] = ['bloco' => $d['bloco']];
            else enviar_erro_json("Nenhum bloco encontrado.");
        } else {
            $sql = "SELECT DISTINCT TRIM(BLOCK_NUMBER) as bloco FROM relatorios_viagens WHERE data_viagem = '$data_selecionada' AND ROUTE_ID = '$valor_busca' ORDER BY BLOCK_NUMBER";
            $res = $conexao->query($sql);
            if ($res) while ($row = $res->fetch_assoc()) $blocos_para_processar[] = ['bloco' => $row['bloco']];
        }

        $resultado_final = [];

        foreach ($blocos_para_processar as $item) {
            $bloco_atual = $item['bloco'];

            // 2. Mapa de Serviços
            $mapa_servicos = [];
            $sql_workids = "SELECT DUTY_COMPANYCODE as workid, START_TIME, END_TIME
                            FROM relatorios_servicos 
                            WHERE TRIM(REFERREDVB_COMPANYCODE) = '$bloco_atual'
                            AND '$data_selecionada' BETWEEN data_inicio_vigencia AND data_fim_vigencia
                            ORDER BY START_TIME ASC";
            $res_w = $conexao->query($sql_workids);
            if ($res_w) while ($w = $res_w->fetch_assoc()) {
                $mapa_servicos[] = [
                    'id' => trim($w['workid']), 
                    'inicio' => substr($w['START_TIME'], 0, 5), 
                    'fim' => substr($w['END_TIME'], 0, 5)
                ];
            }

            // 3. Buscar Viagens do Bloco (Macro)
            // Importante: DIRECTION_NUM e ROUTE_VARIANT para o recheio
            $sql_viagens = "
                SELECT v.ROUTE_ID as linha, v.TRIP_ID, v.START_TIME, v.END_TIME, v.DIRECTION_NUM,
                    l_inicio.name as nome_local_inicio, l_fim.name as nome_local_fim,
                    v.START_PLACE as cod_local_inicio, v.END_PLACE as cod_local_fim,
                    via.descricao as nome_via, v.ROUTE_VARIANT as cod_via
                FROM relatorios_viagens v
                LEFT JOIN cadastros_locais l_inicio ON v.START_PLACE = l_inicio.company_code
                LEFT JOIN cadastros_locais l_fim ON v.END_PLACE = l_fim.company_code
                LEFT JOIN cadastros_vias via ON v.ROUTE_VARIANT = via.codigo
                WHERE v.data_viagem = '$data_selecionada' AND TRIM(v.BLOCK_NUMBER) = '$bloco_atual'
                ORDER BY v.START_TIME ASC
            ";
            
            $res_viagens = $conexao->query($sql_viagens);
            $viagens_macro = $res_viagens->fetch_all(MYSQLI_ASSOC);

            // 4. Filtragem Tipo Portal (WorkID)
            // Lógica Exata: Seleciona apenas as viagens que COMEÇAM dentro do turno
            if ($tipo_busca === 'workid') {
                $meu_servico = null;
                foreach ($mapa_servicos as $svc) {
                    if ($svc['id'] == $valor_busca) { $meu_servico = $svc; break; }
                }

                if ($meu_servico) {
                    $viagens_filtradas = [];
                    foreach ($viagens_macro as $vm) {
                        $h_viagem = substr($vm['START_TIME'], 0, 5);
                        $ini = substr($meu_servico['inicio'], 0, 5);
                        $fim = substr($meu_servico['fim'], 0, 5);
                        $esta_dentro = false;

                        // <--- LÓGICA ESTRITA DO PORTAL (< FIM)
                        if ($ini > $fim) { 
                            if ($h_viagem >= $ini || $h_viagem < $fim) $esta_dentro = true;
                        } else { 
                            if ($h_viagem >= $ini && $h_viagem < $fim) $esta_dentro = true;
                        }

                        if ($esta_dentro) $viagens_filtradas[] = $vm;
                    }
                    $viagens_macro = $viagens_filtradas;
                }
            }

            // 5. Construção das Linhas (Macro + Recheio)
            $linhas_tabela = [];
            
            foreach ($viagens_macro as $vm) {
                // Prepara dados comuns
                $origem = $vm['nome_local_inicio'] ?? $vm['cod_local_inicio'];
                $destino = $vm['nome_local_fim'] ?? $vm['cod_local_fim'];
                $info = $vm['nome_via'] ?? $vm['cod_via'];
                if(empty($info) && $vm['TRIP_ID'] == 0) $info = "DESLOCAMENTO / RECOLHA";

                // Definir WorkID da linha
                $wid_linha = ($tipo_busca === 'workid') ? $valor_busca : obter_workid($vm['START_TIME'], $mapa_servicos);

                // --- A. LINHA MACRO (PARTIDA) ---
                $linhas_tabela[] = [
                    'linha' => $vm['linha'],
                    'tabela' => '-',
                    'workid' => $wid_linha,
                    'chegada_show' => '-',
                    'saida_show' => formatar_hora_gtfs($vm['START_TIME']),
                    'local' => mb_strtoupper($origem, 'UTF-8'),
                    'info' => mb_strtoupper($info, 'UTF-8'),
                    'is_ociosa' => ($vm['TRIP_ID'] == 0)
                ];

                // --- B. RECHEIO (Pontos Intermediários) ---
                // Verifica condições: Tem variante, não é recolha/deslocamento, tem trip_id
                if (!empty($vm['cod_via']) && stripos($info, 'Recolha') === false && stripos($info, 'Deslocamento') === false && $vm['TRIP_ID'] != 0) {
                    
                    // 1. Busca TripCode
                    $sqlFind = "SELECT TRIPCODE FROM relatorios_todos_horarios 
                                WHERE LINE='{$vm['linha']}' 
                                AND PATTERN='{$vm['cod_via']}' 
                                AND NODE='{$vm['cod_local_inicio']}' 
                                AND DIRECTION='{$vm['DIRECTION_NUM']}' 
                                AND data_viagem='$data_selecionada' 
                                AND TIME_FORMAT(DEPARTURETIME, '%H:%i') = '" . substr($vm['START_TIME'], 0, 5) . "' 
                                LIMIT 1";
                    
                    $qFind = $conexao->query($sqlFind);
                    $tripCodeFound = ($qFind && $qFind->num_rows > 0) ? $qFind->fetch_assoc()['TRIPCODE'] : null;

                    if ($tripCodeFound) {
                        // 2. Busca Detalhes
                        $sqlDet = "SELECT t.ARRIVALTIME, t.DEPARTURETIME, t.NODE, COALESCE(l.name, t.NODE) as nome_local_legivel
                                   FROM relatorios_todos_horarios t 
                                   LEFT JOIN cadastros_locais l ON t.NODE = l.company_code 
                                   WHERE t.TRIPCODE = '$tripCodeFound' 
                                   AND t.data_viagem = '$data_selecionada' 
                                   ORDER BY t.PASSAGEORDER ASC";
                        $qDet = $conexao->query($sqlDet);
                        $pontos = $qDet->fetch_all(MYSQLI_ASSOC);

                        // 3. Adiciona (Ignora primeiro e último índice)
                        if (count($pontos) > 2) {
                            for ($k = 1; $k < count($pontos) - 1; $k++) {
                                $p = $pontos[$k];
                                $linhas_tabela[] = [
                                    'linha' => $vm['linha'], // Mostra a linha
                                    'tabela' => '-', 
                                    'workid' => $wid_linha,  // Herda o WorkID
                                    'chegada_show' => formatar_hora_gtfs($p['ARRIVALTIME']),
                                    'saida_show' => formatar_hora_gtfs($p['DEPARTURETIME']),
                                    'local' => mb_strtoupper($p['nome_local_legivel'], 'UTF-8'),
                                    'info' => '', 
                                    'is_ociosa' => false
                                ];
                            }
                        }
                    }
                }
            }
            
            // --- C. LINHA FINAL (CHEGADA DO BLOCO/TURNO) ---
            // Adiciona APENAS UMA vez ao final, pegando os dados da última viagem processada
            if (!empty($viagens_macro)) {
                $ultima_viagem = end($viagens_macro);
                $destino_final = $ultima_viagem['nome_local_fim'] ?? $ultima_viagem['cod_local_fim'];
                $info_final = $ultima_viagem['nome_via'] ?? $ultima_viagem['cod_via'];
                if(empty($info_final) && $ultima_viagem['TRIP_ID'] == 0) $info_final = "DESLOCAMENTO / RECOLHA";

                // WorkID Final
                $wid_fim = ($tipo_busca === 'workid') ? $valor_busca : obter_workid($ultima_viagem['END_TIME'], $mapa_servicos);

                $linhas_tabela[] = [
                    'linha' => $ultima_viagem['linha'],
                    'tabela' => '-',
                    'workid' => $wid_fim,
                    'chegada_show' => formatar_hora_gtfs($ultima_viagem['END_TIME']),
                    'saida_show' => '-', 
                    'local' => mb_strtoupper($destino_final, 'UTF-8'),
                    'info' => mb_strtoupper($info_final, 'UTF-8'),
                    'is_ociosa' => ($ultima_viagem['TRIP_ID'] == 0)
                ];
            }

            // 6. Recálculo WorkID (Apenas se não for busca por WorkID)
            if ($tipo_busca !== 'workid') {
                foreach ($linhas_tabela as &$r) {
                    if ($r['workid'] === '') {
                         $r['workid'] = obter_workid($r['saida_show'] !== '-' ? $r['saida_show'] : $r['chegada_show'], $mapa_servicos);
                    }
                }
            } else {
                // 7. Limpeza Visual das Pontas (Apenas no WorkID)
                if (!empty($linhas_tabela)) {
                    $linhas_tabela[0]['chegada_show'] = '-';
                    $ultimo = count($linhas_tabela) - 1;
                    $linhas_tabela[$ultimo]['saida_show'] = '-';
                }
            }

            // 8. Resumo Cabeçalho
            $linhas_seq = [];
            foreach ($linhas_tabela as $r) {
                $l = trim($r['linha']);
                if (!empty($l) && !in_array($l, $linhas_seq)) $linhas_seq[] = $l;
            }

            if (!empty($linhas_tabela)) {
                $resultado_final[] = [
                    'cabecalho' => [
                        'bloco' => $bloco_atual,
                        'linhas_resumo' => implode('/', $linhas_seq),
                        'workid' => ($tipo_busca === 'workid') ? $valor_busca : 'Vários',
                        'data_formatada' => date('d/m/Y', strtotime($data_selecionada))
                    ],
                    'viagens' => $linhas_tabela
                ];
            }
        }

        ob_clean();
        echo json_encode(empty($resultado_final) ? ['error' => "Nenhum dado encontrado."] : ['diarios' => $resultado_final]);
        exit;
    }
} catch (Exception $e) { enviar_erro_json($e->getMessage()); }

function obter_workid($hora, $mapa) {
    if(!$hora || $hora == '-') return '';
    $h = substr($hora, 0, 5);
    foreach ($mapa as $svc) {
        $ini = substr($svc['inicio'], 0, 5);
        $fim = substr($svc['fim'], 0, 5);
        if ($ini > $fim) {
            if ($h >= $ini || $h < $fim) return $svc['id'];
        } else {
            if ($h >= $ini && $h < $fim) return $svc['id'];
        }
    }
    return '';
}
?>
