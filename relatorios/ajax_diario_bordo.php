<?php
// =================================================================
//  Parceiro de Programação - AJAX Diário de Bordo (v1.6)
//  - NOVO: Busca por "Linha e Hora" (Interseção de horários).
//  - MANTIDO: Filtro de WorkID e Resumo de Linhas na aba.
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
        if (empty($hora_db)) return '';
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
        ob_clean();
        echo json_encode(['data' => $row['ultima_data'] ?? date('Y-m-d')]);
        exit;
    }

    if ($acao === 'listar_linhas') {
        $res = $conexao->query("SELECT DISTINCT ROUTE_ID as linha FROM relatorios_viagens WHERE data_viagem = '$data_selecionada' AND ROUTE_ID IS NOT NULL ORDER BY ROUTE_ID");
        $linhas = [];
        if ($res) while($r = $res->fetch_assoc()) $linhas[] = $r['linha'];
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
                $where_linha 
                ORDER BY DUTY_COMPANYCODE ASC";
        $res = $conexao->query($sql);
        $works = [];
        if ($res) while($r = $res->fetch_assoc()) $works[] = $r['workid'];
        ob_clean(); echo json_encode($works); exit;
    }

    // --- AÇÃO PRINCIPAL: GERAR DIÁRIO ---
    if ($acao === 'gerar_diario') {
        $tipo_busca = $_GET['tipo'] ?? 'workid'; 
        $valor_busca = $_GET['valor'] ?? '';

        if (!$valor_busca) throw new Exception('Valor de busca não informado.');

        $blocos_para_processar = [];

        // 1. Identificar Blocos baseados no Tipo de Busca
        if ($tipo_busca === 'workid') {
            // BUSCA POR SERVIÇO (WORKID)
            $sql = "SELECT DISTINCT TRIM(REFERREDVB_COMPANYCODE) as bloco
                    FROM relatorios_servicos
                    WHERE TRIM(DUTY_COMPANYCODE) = TRIM('$valor_busca')
                    AND '$data_selecionada' BETWEEN data_inicio_vigencia AND data_fim_vigencia";
            
            $res = $conexao->query($sql);
            if ($res->num_rows > 0) while ($d = $res->fetch_assoc()) $blocos_para_processar[] = ['bloco' => $d['bloco']];
            else enviar_erro_json("Bloco não encontrado para este WorkID.");

        } elseif ($tipo_busca === 'linha_hora') {
            // NOVO: BUSCA POR LINHA E INTERVALO DE HORÁRIO
            $hora_ini = $_GET['hora_ini'] ?? '00:00';
            $hora_fim = $_GET['hora_fim'] ?? '23:59';
            
            // Query inteligente: Busca blocos que tiveram viagens na LINHA X
            // que começaram, terminaram ou atravessaram o intervalo H1-H2
            $sql = "
                SELECT DISTINCT TRIM(BLOCK_NUMBER) as bloco 
                FROM relatorios_viagens 
                WHERE data_viagem = '$data_selecionada' 
                AND ROUTE_ID = '$valor_busca'
                AND (
                    (START_TIME BETWEEN '$hora_ini' AND '$hora_fim') OR 
                    (END_TIME BETWEEN '$hora_ini' AND '$hora_fim') OR 
                    (START_TIME <= '$hora_ini' AND END_TIME >= '$hora_fim')
                )
                ORDER BY BLOCK_NUMBER
            ";
            
            $res = $conexao->query($sql);
            if ($res->num_rows > 0) while ($d = $res->fetch_assoc()) $blocos_para_processar[] = ['bloco' => $d['bloco']];
            else enviar_erro_json("Nenhum bloco encontrado na linha $valor_busca entre $hora_ini e $hora_fim.");

        } else {
            // BUSCA POR LINHA (TODOS)
            $sql = "SELECT DISTINCT TRIM(BLOCK_NUMBER) as bloco FROM relatorios_viagens WHERE data_viagem = '$data_selecionada' AND ROUTE_ID = '$valor_busca' ORDER BY BLOCK_NUMBER";
            $res = $conexao->query($sql);
            if ($res) while ($row = $res->fetch_assoc()) $blocos_para_processar[] = ['bloco' => $row['bloco']];
        }

        $resultado_final = [];

        // Loop para gerar o diário completo de cada bloco encontrado
        foreach ($blocos_para_processar as $item) {
            $bloco_atual = $item['bloco'];

            // 2. Mapa de Serviços (WorkIDs)
            $mapa_servicos = [];
            $sql_workids = "SELECT DUTY_COMPANYCODE as workid, START_TIME, END_TIME
                            FROM relatorios_servicos 
                            WHERE TRIM(REFERREDVB_COMPANYCODE) = '$bloco_atual'
                            AND '$data_selecionada' BETWEEN data_inicio_vigencia AND data_fim_vigencia
                            ORDER BY START_TIME ASC";
            $res_w = $conexao->query($sql_workids);
            if ($res_w) while ($w = $res_w->fetch_assoc()) {
                $mapa_servicos[] = ['id' => $w['workid'], 'inicio' => substr($w['START_TIME'], 0, 5), 'fim' => substr($w['END_TIME'], 0, 5)];
            }

            // 3. Buscar Viagens do Bloco Inteiro
            $sql_viagens = "
                SELECT v.ROUTE_ID as linha, v.TRIP_ID, v.START_TIME, v.END_TIME,
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
            $viagens_raw = $res_viagens->fetch_all(MYSQLI_ASSOC);

            // 4. Processamento Ponto a Ponto (Lógica V1)
            $linhas_tabela = [];
            $total = count($viagens_raw);

            for ($i = 0; $i < $total; $i++) {
                $atual = $viagens_raw[$i];
                $hora_saida = substr($atual['START_TIME'], 0, 5);
                
                $origem = $atual['nome_local_inicio'] ?? $atual['cod_local_inicio'];
                $destino = $atual['nome_local_fim'] ?? $atual['cod_local_fim'];
                $info = $atual['nome_via'] ?? $atual['cod_via'];
                if(empty($info) && $atual['TRIP_ID'] == 0) $info = "DESLOCAMENTO / RECOLHA";

                // A. Partida
                if ($i == 0 || ($viagens_raw[$i-1]['cod_local_fim'] !== $atual['cod_local_inicio'])) {
                    $linhas_tabela[] = [
                        'linha' => $atual['linha'], 'tabela' => '-', 'hora_ref' => $hora_saida,
                        'chegada_show' => '-', 'saida_show' => formatar_hora_gtfs($atual['START_TIME']),
                        'local' => mb_strtoupper($origem, 'UTF-8'), 'info' => mb_strtoupper($info, 'UTF-8'),
                        'is_ociosa' => ($atual['TRIP_ID'] == 0)
                    ];
                }

                // B. Chegada e Conexão
                $proxima = ($i < $total - 1) ? $viagens_raw[$i+1] : null;
                $chegada_str = formatar_hora_gtfs($atual['END_TIME']);
                $saida_str = ''; $loc_str = mb_strtoupper($destino, 'UTF-8');
                $info_str = ''; $linha_str = ''; $hora_ref = substr($atual['END_TIME'], 0, 5);
                $is_oc = false;

                if ($proxima && $proxima['cod_local_inicio'] == $atual['cod_local_fim']) {
                    $saida_str = formatar_hora_gtfs($proxima['START_TIME']);
                    $info_prox = $proxima['nome_via'] ?? $proxima['cod_via'];
                    if(empty($info_prox) && $proxima['TRIP_ID'] == 0) $info_prox = "DESLOCAMENTO / RECOLHA";
                    $info_str = mb_strtoupper($info_prox, 'UTF-8');
                    $linha_str = $proxima['linha'];
                    $is_oc = ($proxima['TRIP_ID'] == 0);
                    $hora_ref = substr($proxima['START_TIME'], 0, 5);
                } else {
                    $info_str = mb_strtoupper($info, 'UTF-8') . " (CHEGADA)";
                    $linha_str = $atual['linha'];
                    $is_oc = ($atual['TRIP_ID'] == 0);
                }

                $linhas_tabela[] = [
                    'linha' => $linha_str, 'tabela' => '-', 'hora_ref' => $hora_ref,
                    'chegada_show' => $chegada_str, 'saida_show' => $saida_str,
                    'local' => $loc_str, 'info' => $info_str, 'is_ociosa' => $is_oc
                ];
            }

            // 5. WorkID
            foreach ($linhas_tabela as &$r) {
                $wid = "-"; $h = $r['hora_ref'];
                foreach ($mapa_servicos as $svc) {
                    if (($svc['inicio'] > $svc['fim'] && ($h >= $svc['inicio'] || $h <= $svc['fim'])) || ($h >= $svc['inicio'] && $h <= $svc['fim'])) {
                        $wid = $svc['id']; break;
                    }
                }
                $r['workid'] = ($wid != '-') ? $wid : ''; unset($r['hora_ref']);
            }

            // 6. Filtro WorkID (se necessário)
            if ($tipo_busca === 'workid') {
                $linhas_tabela = array_values(array_filter($linhas_tabela, fn($r) => trim($r['workid']) == trim($valor_busca)));
            }

            // 7. Resumo Linhas
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
?>