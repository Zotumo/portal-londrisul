<?php
// portal/buscar_horario.php (v44 - Correção da Troca de Turno)
require_once 'db_config.php';
header('Content-Type: application/json');

set_time_limit(120); 

$acao = $_GET['acao'] ?? '';
$termo_raw = $_GET['termo'] ?? ''; 
$data_filtro = $_GET['data'] ?? '';

// 1. TRATAMENTO DE LINHAS ESPECIAIS
$termo = strtoupper(trim($termo_raw));
$linhas_londrisul = ['200', '408', '800', '801', '802', '804', '806', '825', '913'];
if (in_array($termo, $linhas_londrisul)) { $termo = $termo . 'L'; }

if (empty($termo)) { echo json_encode(['erro' => true, 'msg' => 'Informe uma linha ou WorkID.']); exit; }

try {
    if (!isset($pdo_relatorios)) throw new Exception("Conexão ausente.");

    // --- AÇÃO 1: BUSCAR DIAS (MANTIDO) ---
    if ($acao === 'buscar_dias') {
        $mapa_feriados = [];
        try {
            $sqlFer = "SELECT data_feriado, tipo_operacao FROM feriados WHERE data_feriado >= CURDATE() AND data_feriado <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            $stmtFer = $pdo_relatorios->query($sqlFer); 
            while ($f = $stmtFer->fetch(PDO::FETCH_ASSOC)) {
                $mapa_feriados[$f['data_feriado']] = strtolower($f['tipo_operacao']);
            }
        } catch (Exception $e) {}

        $hoje = new DateTime();
        $botoes = [];
        $tipos_encontrados = ['UTEIS' => false, 'SABADO' => false, 'DOMINGO' => false];

        for ($i = 0; $i < 15; $i++) {
            $data_obj = clone $hoje;
            $data_obj->modify("+$i days");
            $data_sql = $data_obj->format('Y-m-d');
            $dia_semana_num = (int)$data_obj->format('w');
            
            $tipo_dia_sistema = ''; $label_base = '';
            if (isset($mapa_feriados[$data_sql])) {
                $operacao = $mapa_feriados[$data_sql];
                if ($operacao == 'sabados') { $tipo_dia_sistema = 'SABADO'; $label_base = 'Sábado (Feriado)'; } 
                else { $tipo_dia_sistema = 'DOMINGO'; $label_base = 'Domingo/Feriado'; }
            } else {
                if ($dia_semana_num == 0) { $tipo_dia_sistema = 'DOMINGO'; $label_base = 'Domingo/Feriado'; } 
                elseif ($dia_semana_num == 6) { $tipo_dia_sistema = 'SABADO'; $label_base = 'Sábado'; } 
                else { $tipo_dia_sistema = 'UTEIS'; $label_base = 'Dias Úteis'; }
            }

            if ($tipos_encontrados[$tipo_dia_sistema]) continue;

            $sql = "SELECT 1 FROM relatorios_viagens v LEFT JOIN relatorios_servicos s ON v.BLOCK_NUMBER = s.REFERREDVB_COMPANYCODE WHERE (v.ROUTE_ID = :t1 OR s.DUTY_ID = :t2 OR s.DUTY_COMPANYCODE = :t3 OR v.BLOCK_NUMBER = :t4) AND v.data_viagem = :data LIMIT 1";
            $stmt = $pdo_relatorios->prepare($sql);
            $stmt->execute([':t1'=>$termo, ':t2'=>$termo, ':t3'=>$termo, ':t4'=>$termo, ':data'=>$data_sql]);

            if ($stmt->fetch()) {
                $botoes[] = ['label' => $label_base, 'data' => $data_sql, 'tipo' => $tipo_dia_sistema];
                $tipos_encontrados[$tipo_dia_sistema] = true;
            }
            if ($tipos_encontrados['UTEIS'] && $tipos_encontrados['SABADO'] && $tipos_encontrados['DOMINGO']) break;
        }
        usort($botoes, function($a, $b) { $ordem = ['UTEIS' => 1, 'SABADO' => 2, 'DOMINGO' => 3]; return $ordem[$a['tipo']] - $ordem[$b['tipo']]; });
        echo json_encode(['sucesso' => true, 'botoes' => $botoes]); exit;
    }

    // --- AÇÃO 2: BUSCAR DETALHES ---
    if ($acao === 'buscar_detalhes') {
        $blocos_para_processar = [];
        $stmtW = $pdo_relatorios->prepare("SELECT DISTINCT TRIM(REFERREDVB_COMPANYCODE) as bloco FROM relatorios_servicos WHERE TRIM(DUTY_COMPANYCODE) = :termo AND :data BETWEEN data_inicio_vigencia AND data_fim_vigencia");
        $stmtW->execute([':termo' => $termo, ':data' => $data_filtro]);
        while ($r = $stmtW->fetch(PDO::FETCH_ASSOC)) $blocos_para_processar[] = ['bloco' => $r['bloco'], 'filtro' => 'workid'];

        if (empty($blocos_para_processar)) {
            $stmtL = $pdo_relatorios->prepare("SELECT DISTINCT TRIM(BLOCK_NUMBER) as bloco FROM relatorios_viagens WHERE data_viagem = :data AND ROUTE_ID = :termo ORDER BY BLOCK_NUMBER");
            $stmtL->execute([':data' => $data_filtro, ':termo' => $termo]);
            while ($r = $stmtL->fetch(PDO::FETCH_ASSOC)) $blocos_para_processar[] = ['bloco' => $r['bloco'], 'filtro' => 'linha'];
        }

        if (empty($blocos_para_processar)) { echo json_encode(['erro' => true, 'msg' => 'Nenhuma escala encontrada.']); exit; }

        $abas = [];
        foreach ($blocos_para_processar as $item) {
            $bloco = $item['bloco'];
            $tipo_filtro = $item['filtro'];

            // Mapa de Serviços (Horários)
            $mapa_servicos = [];
            $stmtS = $pdo_relatorios->prepare("SELECT DUTY_COMPANYCODE as workid, START_TIME, END_TIME FROM relatorios_servicos WHERE TRIM(REFERREDVB_COMPANYCODE) = :bloco AND :data BETWEEN data_inicio_vigencia AND data_fim_vigencia ORDER BY START_TIME ASC");
            $stmtS->execute([':bloco' => $bloco, ':data' => $data_filtro]);
            $raw_servicos = $stmtS->fetchAll(PDO::FETCH_ASSOC);
            foreach($raw_servicos as $s) { $mapa_servicos[] = ['id' => $s['workid'], 'inicio' => substr($s['START_TIME'], 0, 5), 'fim' => substr($s['END_TIME'], 0, 5)]; }

            // 3. BUSCAR VIAGENS MACRO
            $sqlMacro = "SELECT 
                    v.id, v.ROUTE_ID, v.BLOCK_NUMBER as tabela_display,
                    v.START_TIME, v.END_TIME, v.ROUTE_VARIANT, v.DIRECTION_NUM, v.TRIP_ID,
                    COALESCE(l_ini.name, v.START_PLACE) as ponto_inicial, v.START_PLACE,
                    l_ini.imagem_path, l_ini.descricao as desc_ponto, l_ini.status as status_ponto, l_ini.coordenadas, 
                    l_ini.mostrar_ponto as mostrar_ini,
                    COALESCE(l_fim.name, v.END_PLACE) as ponto_final, v.END_PLACE,
                    via.descricao as via_nome, via.iframe_mapa, geo.pontos_json as geometria_kml, geo.cor_hex as cor_kml
                FROM relatorios_viagens v
                LEFT JOIN cadastros_locais l_ini ON v.START_PLACE = l_ini.company_code
                LEFT JOIN cadastros_locais l_fim ON v.END_PLACE = l_fim.company_code
                LEFT JOIN cadastros_vias via ON v.ROUTE_VARIANT = via.codigo
                LEFT JOIN rotas_geometria geo ON v.ROUTE_VARIANT = geo.codigo_variante
                WHERE v.BLOCK_NUMBER = :blk AND v.data_viagem = :data
                GROUP BY v.id ORDER BY v.START_TIME ASC";

            $stmtD = $pdo_relatorios->prepare($sqlMacro);
            $stmtD->execute([':blk' => $bloco, ':data' => $data_filtro]);
            $viagens_macro = $stmtD->fetchAll(PDO::FETCH_ASSOC);

            if (empty($viagens_macro)) continue;

            // ======================================================
            // LÓGICA DE FILTRO (WORKID) - CORREÇÃO DE HORÁRIO
            // ======================================================
            if ($tipo_filtro === 'workid') {
                $meu_servico = null;
                foreach ($mapa_servicos as $svc) {
                    if ($svc['id'] == $termo) {
                        $meu_servico = $svc;
                        break;
                    }
                }

                if ($meu_servico) {
                    $viagens_filtradas = [];
                    foreach ($viagens_macro as $vm) {
                        $h_viagem = substr($vm['START_TIME'], 0, 5);
                        $ini = $meu_servico['inicio'];
                        $fim = $meu_servico['fim'];
                        $esta_dentro = false;

                        // CORREÇÃO AQUI: Usa '<' (estritamente menor) para o fim
                        // Se a viagem sai às 18:23 e o turno acaba às 18:23, NÃO entra.
                        if ($ini > $fim) { // Vira a noite (22:00 -> 02:00)
                            if ($h_viagem >= $ini || $h_viagem < $fim) $esta_dentro = true;
                        } else { // Mesmo dia
                            if ($h_viagem >= $ini && $h_viagem < $fim) $esta_dentro = true;
                        }

                        if ($esta_dentro) {
                            $viagens_filtradas[] = $vm;
                        }
                    }
                    $viagens_macro = $viagens_filtradas; 
                }
            }
            // ======================================================

            if (empty($viagens_macro)) continue;

            $viagens_finais = [];
            foreach ($viagens_macro as $v_macro) {
                $hora_ref = substr($v_macro['START_TIME'], 0, 5);
                $wid_encontrado = '-';
                
                foreach ($mapa_servicos as $svc) {
                    // CORREÇÃO AQUI TAMBÉM: Usa '<' para exibição correta na tabela completa
                    if (($svc['inicio'] > $svc['fim'] && ($hora_ref >= $svc['inicio'] || $hora_ref < $svc['fim'])) || ($hora_ref >= $svc['inicio'] && $hora_ref < $svc['fim'])) {
                        $wid_encontrado = $svc['id']; break;
                    }
                }
                
                $v_macro['work_id_display'] = $wid_encontrado;
                
                // --- LINHA MACRO ---
                $linha_principal = $v_macro;
                $linha_principal['tipo_linha'] = 'macro';
                $linha_principal['saida_exata'] = formatar_hora($v_macro['START_TIME']);
                $linha_principal['chegada_exata'] = '-';
                $linha_principal['mostrar_ponto'] = $v_macro['mostrar_ini'] ?? 1;

                if (empty($v_macro['ROUTE_VARIANT']) || stripos($v_macro['via_nome'], 'Recolha') !== false || stripos($v_macro['via_nome'], 'Deslocamento') !== false || $v_macro['TRIP_ID'] == 0) {
                    $viagens_finais[] = $linha_principal; continue;
                }

                // --- RECHEIO ---
                $stmtFind = $pdo_relatorios->prepare("SELECT TRIPCODE FROM relatorios_todos_horarios WHERE LINE=:linha AND PATTERN=:variante AND NODE=:no_inicio AND DIRECTION=:sentido AND data_viagem=:data_csv AND TIME_FORMAT(DEPARTURETIME, '%H:%i')=:hora_inicio LIMIT 1");
                $stmtFind->execute([':linha' => $v_macro['ROUTE_ID'], ':variante' => $v_macro['ROUTE_VARIANT'], ':no_inicio' => $v_macro['START_PLACE'], ':sentido' => $v_macro['DIRECTION_NUM'], ':data_csv' => $data_filtro, ':hora_inicio' => substr($v_macro['START_TIME'], 0, 5)]);
                $tripCodeFound = $stmtFind->fetchColumn();

                $viagens_finais[] = $linha_principal;

                if ($tripCodeFound) {
                    $sqlDet = "SELECT t.ARRIVALTIME, t.DEPARTURETIME, t.NODE, COALESCE(l.name, t.NODE) as nome_local_legivel, l.imagem_path, l.descricao as desc_ponto, l.status as status_ponto, l.coordenadas, l.mostrar_ponto, t.PASSAGEORDER FROM relatorios_todos_horarios t LEFT JOIN cadastros_locais l ON t.NODE = l.company_code WHERE t.TRIPCODE = :tripcode AND t.data_viagem = :data_csv ORDER BY t.PASSAGEORDER ASC";
                    $stmtDet = $pdo_relatorios->prepare($sqlDet);
                    $stmtDet->execute([':tripcode'=>$tripCodeFound, ':data_csv'=>$data_filtro]);
                    $pontos = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

                    if (count($pontos) > 2) {
                        for ($i = 1; $i < count($pontos) - 1; $i++) {
                            $ponto = $pontos[$i];
                            $linha_recheio = $v_macro; 
                            $linha_recheio['tipo_linha'] = 'recheio'; 
                            $linha_recheio['ponto_inicial'] = $ponto['nome_local_legivel'];
                            $linha_recheio['START_PLACE'] = $ponto['NODE'];
                            $linha_recheio['imagem_path'] = $ponto['imagem_path'];
                            $linha_recheio['desc_ponto'] = $ponto['desc_ponto'];
                            $linha_recheio['status_ponto'] = $ponto['status_ponto'];
                            $linha_recheio['coordenadas'] = $ponto['coordenadas'];
                            $linha_recheio['chegada_exata'] = formatar_hora($ponto['ARRIVALTIME']);
                            $linha_recheio['saida_exata'] = formatar_hora($ponto['DEPARTURETIME']);
                            $linha_recheio['mostrar_ponto'] = $ponto['mostrar_ponto'] ?? 1;
                            $viagens_finais[] = $linha_recheio;
                        }
                    }
                }
            }
            
            // --- FIM ---
            $ultima_viagem = end($viagens_macro);
            
            if ($ultima_viagem) {
                $wid_fim = ($tipo_filtro === 'workid') ? $termo : '-';
                if ($wid_fim === '-') {
                     $hora_ref_fim = substr($ultima_viagem['END_TIME'], 0, 5);
                     // CORREÇÃO TAMBÉM NO FIM (embora menos crítica aqui, bom manter padrão)
                     foreach ($mapa_servicos as $svc) { if (($svc['inicio'] > $svc['fim'] && ($hora_ref_fim >= $svc['inicio'] || $hora_ref_fim < $svc['fim'])) || ($hora_ref_fim >= $svc['inicio'] && $hora_ref_fim < $svc['fim'])) { $wid_fim = $svc['id']; break; } }
                }

                $stmtImg = $pdo_relatorios->prepare("SELECT imagem_path, descricao, status, coordenadas, mostrar_ponto FROM cadastros_locais WHERE company_code = :cod");
                $stmtImg->execute([':cod' => $ultima_viagem['END_PLACE']]);
                $dadosFim = $stmtImg->fetch(PDO::FETCH_ASSOC);

                $linha_fim = $ultima_viagem;
                $linha_fim['work_id_display'] = $wid_fim;
                $linha_fim['tipo_linha'] = 'fim';
                $linha_fim['ponto_inicial'] = $ultima_viagem['ponto_final'];
                $linha_fim['START_PLACE'] = $ultima_viagem['END_PLACE'];
                $linha_fim['imagem_path'] = $dadosFim['imagem_path'] ?? null;
                $linha_fim['desc_ponto'] = $dadosFim['descricao'] ?? null;
                $linha_fim['status_ponto'] = $dadosFim['status'] ?? 'ativo';
                $linha_fim['coordenadas'] = $dadosFim['coordenadas'] ?? null;
                $linha_fim['chegada_exata'] = formatar_hora($ultima_viagem['END_TIME']);
                $linha_fim['saida_exata'] = '-';
                $linha_fim['mostrar_ponto'] = $dadosFim['mostrar_ponto'] ?? 1;
                
                $viagens_finais[] = $linha_fim;
            }

            $viagens = $viagens_finais; 
            if ($tipo_filtro == 'workid') { 
                $titulo_aba = "WorkID: " . $termo; 
            } else { 
                $linhas_brutas = array_column($viagens, 'ROUTE_ID'); 
                $linhas_un = array_unique(array_filter($linhas_brutas)); 
                $titulo_aba = "" . implode('/', $linhas_un); 
            }
            $wid_safe = preg_replace('/[^a-zA-Z0-9]/', '', $bloco);

            ob_start();
            include 'parts/display_conteudo_diario.php';
            $html_conteudo = ob_get_clean();

            $abas[] = ['id' => $wid_safe, 'titulo' => $titulo_aba, 'subtitulo' => "", 'conteudo' => $html_conteudo];
        }
        echo json_encode(['sucesso' => true, 'abas' => $abas]); exit;
    }
} catch (Exception $e) { echo json_encode(['erro' => true, 'msg' => 'Erro interno: ' . $e->getMessage()]); }
function formatar_hora($h) { if(!$h) return '-'; return substr($h, 0, 5); }
?>