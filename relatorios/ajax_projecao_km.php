<?php
// =================================================================
//  Parceiro de Programação - AJAX Projeção de KM (v1.5)
//  - ADIÇÃO: Fonte 'mtran_precisao' lendo de relatorios_viagens_precisao.
//  - PRECISÃO: Alterado para 3 casas decimais em todos os retornos.
//  - Mantida lógica de Feriados e Inputs Manuais.
// =================================================================

header('Content-Type: application/json');
require_once 'config_km.php';

$conexao = new mysqli("localhost", "root", "", "relatorio");
if ($conexao->connect_error) { die(json_encode(['error' => "Erro DB: " . $conexao->connect_error])); }
$conexao->set_charset("utf8mb4");

// --- RECEBER PARÂMETROS ---
$data_inicio_base = $_GET['data_inicio'] ?? date('Y-m-01', strtotime('-3 months'));
$data_fim_base = $_GET['data_fim'] ?? date('Y-m-d');
$ano_projecao = (int)($_GET['ano_projecao'] ?? date('Y'));
$fonte_base = $_GET['fonte'] ?? 'mtran'; 
$ajuste_pct = (float)($_GET['ajuste'] ?? 0);

// --- 0. CARREGAR FERIADOS ---
$feriados_map = [];
$res_feriados = $conexao->query("SELECT data_feriado, tipo_operacao FROM feriados");
if ($res_feriados) while ($f = $res_feriados->fetch_assoc()) $feriados_map[$f['data_feriado']] = $f['tipo_operacao'];

// --- 1. FUNÇÃO DE CONTAGEM INTELIGENTE ---
function contar_dias_classificados($dt_ini, $dt_fim, $mapa_feriados) {
    $dias = ['uteis' => 0, 'sabados' => 0, 'domingos' => 0, 'total' => 0];
    $periodo = new DatePeriod(new DateTime($dt_ini), new DateInterval('P1D'), (new DateTime($dt_fim))->modify('+1 day'));
    foreach ($periodo as $dt) {
        $dias['total']++;
        $iso = $dt->format('Y-m-d');
        if (isset($mapa_feriados[$iso])) {
            $tipo = $mapa_feriados[$iso]; 
            if (isset($dias[$tipo])) $dias[$tipo]++;
        } else {
            $w = $dt->format('N'); 
            if ($w == 7) $dias['domingos']++;
            elseif ($w == 6) $dias['sabados']++;
            else $dias['uteis']++;
        }
    }
    return $dias;
}

// --- 2. DEFINIÇÃO DAS MÉDIAS (MANUAL OU SQL) ---

if ($fonte_base === 'manual') {
    $media_uteis = (float)($_GET['manual_uteis'] ?? 0);
    $media_sab   = (float)($_GET['manual_sab'] ?? 0);
    $media_dom   = (float)($_GET['manual_dom'] ?? 0);
    
    $data_inicio_base = 'Manual';
    $data_fim_base = 'Manual';
} else {
    $stats_historico = ['total_km' => 0, 'km_uteis' => 0, 'km_sab' => 0, 'km_dom' => 0];
    $contagem_dias_base = contar_dias_classificados($data_inicio_base, $data_fim_base, $feriados_map);
    $where_hist = "BETWEEN '$data_inicio_base' AND '$data_fim_base'";

    switch ($fonte_base) {
        // NOVA FONTE DE PRECISÃO
        case 'mtran_precisao': 
            $sql = "SELECT data_viagem, SUM(CAST(REPLACE(length_km, ',', '.') AS DECIMAL(15,3))/1000.0) as km 
                    FROM relatorios_viagens_precisao WHERE data_viagem $where_hist GROUP BY data_viagem"; 
            break;
        case 'mtran': 
            $sql = "SELECT data_viagem, SUM(DISTANCE/1000.0) as km FROM relatorios_viagens WHERE data_viagem $where_hist GROUP BY data_viagem"; 
            break;
        case '004': 
            $sql = "SELECT data_viagem, SUM(CAST(distancia_produtiva_r_km AS DECIMAL(15,3)) + CAST(distancia_ociosa_r_km AS DECIMAL(15,3)) + CAST(distancia_recolha_r_km AS DECIMAL(15,3))) as km FROM relatorios_frota WHERE data_viagem $where_hist GROUP BY data_viagem"; 
            break;
        case 'roleta': 
            $sql = "SELECT data_leitura as data_viagem, SUM(total) as km FROM relatorios_km_roleta_diario WHERE data_leitura $where_hist GROUP BY data_leitura"; 
            break;
        case 'noxxon': 
            $sql = "SELECT data_leitura as data_viagem, SUM(CAST(REPLACE(km_percorrido, ',', '.') AS DECIMAL(15,3))) as km FROM relatorios_km_noxxon_diario WHERE data_leitura $where_hist GROUP BY data_leitura"; 
            break;
        case 'life': 
            $sql = "WITH Extremos AS (SELECT DATE(data_leitura) as d, vehicle, MIN(odometro_km) as min_km, MAX(odometro_km) as max_km FROM registros_odometro_life WHERE DATE(data_leitura) $where_hist GROUP BY d, vehicle) SELECT d as data_viagem, SUM(max_km - min_km) as km FROM Extremos WHERE (max_km - min_km) > 0 AND (max_km - min_km) < 1500 GROUP BY d"; 
            break;
        default: die(json_encode(['error' => 'Fonte desconhecida']));
    }

    $result = $conexao->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $km = (float)$row['km'];
            $dt = $row['data_viagem'];
            $tipo_dia = isset($feriados_map[$dt]) ? $feriados_map[$dt] : (date('N', strtotime($dt)) == 7 ? 'domingos' : (date('N', strtotime($dt)) == 6 ? 'sabados' : 'uteis'));
            
            $stats_historico['total_km'] += $km;
            if ($tipo_dia == 'domingos') $stats_historico['km_dom'] += $km;
            elseif ($tipo_dia == 'sabados') $stats_historico['km_sab'] += $km;
            else $stats_historico['km_uteis'] += $km;
        }
    }
    
    $media_uteis = ($contagem_dias_base['uteis'] > 0) ? $stats_historico['km_uteis'] / $contagem_dias_base['uteis'] : 0;
    $media_sab   = ($contagem_dias_base['sabados'] > 0) ? $stats_historico['km_sab'] / $contagem_dias_base['sabados'] : 0;
    $media_dom   = ($contagem_dias_base['domingos'] > 0) ? $stats_historico['km_dom'] / $contagem_dias_base['domingos'] : 0;
}

// Aplicar Ajuste
if ($ajuste_pct != 0) {
    $fator = 1 + ($ajuste_pct / 100);
    $media_uteis *= $fator; $media_sab *= $fator; $media_dom *= $fator;
}

// --- 3. PROJEÇÃO FUTURA ---
$projecao_mensal = [];
$total_ano_projetado = 0; $total_ano_meta = 0;
$meses_pt_br = [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'];

$metas_db = [];
$res_meta = $conexao->query("SELECT mes, km_meta FROM metas_planilha_tarifaria WHERE ano = $ano_projecao");
if ($res_meta) while ($m = $res_meta->fetch_assoc()) $metas_db[$m['mes']] = (float)$m['km_meta'];

for ($mes = 1; $mes <= 12; $mes++) {
    $primeiro_dia = sprintf('%04d-%02d-01', $ano_projecao, $mes);
    $ultimo_dia = date('Y-m-t', strtotime($primeiro_dia));
    $dias_count = contar_dias_classificados($primeiro_dia, $ultimo_dia, $feriados_map);
    
    $proj_mes = ($dias_count['uteis'] * $media_uteis) + 
                ($dias_count['sabados'] * $media_sab) + 
                ($dias_count['domingos'] * $media_dom);
    
    $meta_mes = $metas_db[$mes] ?? 0;
    
    $projecao_mensal[$mes] = [
        'nome_mes' => $meses_pt_br[$mes],
        'dias' => $dias_count,
        'projetado' => round($proj_mes, 3),
        'meta' => round($meta_mes, 3),
        'diff' => round($proj_mes - $meta_mes, 3),
        'diff_perc' => ($meta_mes > 0) ? round((($proj_mes - $meta_mes) / $meta_mes) * 100, 2) : 0
    ];
    $total_ano_projetado += $proj_mes; $total_ano_meta += $meta_mes;
}

echo json_encode([
    'medias_base' => [
        'periodo' => ($data_inicio_base === 'Manual') ? 'Definição Manual' : date('d/m/Y', strtotime($data_inicio_base)) . ' a ' . date('d/m/Y', strtotime($data_fim_base)),
        'uteis' => round($media_uteis, 3),
        'sab' => round($media_sab, 3),
        'dom' => round($media_dom, 3),
        'ajuste_aplicado' => $ajuste_pct . '%'
    ],
    'totais_ano' => [
        'projetado' => round($total_ano_projetado, 3),
        'meta' => round($total_ano_meta, 3),
        'diff' => round($total_ano_projetado - $total_ano_meta, 3),
        'diff_perc' => ($total_ano_meta > 0) ? round((($total_ano_projetado - $total_ano_meta) / $total_ano_meta) * 100, 2) : 0
    ],
    'mensal' => $projecao_mensal
]);
$conexao->close();
?>