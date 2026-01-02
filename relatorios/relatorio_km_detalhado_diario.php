<?php
// =================================================================
//  Parceiro de Programação - Relatório de KM Consolidado (v20.2)
//  - VISÃO DIÁRIA (ABA 1): Mantida V15.0.
//  - VISÃO CONSOLIDADA (ABA 2): Atualizada para visual V2.8 (Cards Coloridos e Detalhados).
//  - REGRA NEGÓCIO: Mantida regra da Route 2 Ociosa após 21/12/2025.
// =================================================================

ini_set('display_errors', 1); error_reporting(E_ALL); set_time_limit(1200);
mb_internal_encoding('UTF-8');

require_once 'config_km.php';

// --- 1. CONEXÃO ---
$servidor = "localhost"; $usuario = "root"; $senha = ""; $banco = "relatorio";
$conexao = new mysqli($servidor, $usuario, $senha, $banco);
if ($conexao->connect_error) { die("Falha na conexão: " . $conexao->connect_error); }
$conexao->set_charset("utf8mb4");

// --- 2. FILTROS ---
$data_hoje = date('Y-m-d');
$primeiro_dia_mes = date('Y-m-01');
$data_inicio = $_GET['data_inicio'] ?? $primeiro_dia_mes;
$data_fim = $_GET['data_fim'] ?? $data_hoje;
$veiculo_filtro = $_GET['veiculo'] ?? ''; 

$where_periodo = "BETWEEN '{$conexao->real_escape_string($data_inicio)}' AND '{$conexao->real_escape_string($data_fim)}'";
$sql_filtro_veiculo_exato = $veiculo_filtro ? " AND vehicle = '{$conexao->real_escape_string($veiculo_filtro)}' " : "";

// --- 3. FUNÇÕES VISUAIS (v15.0) ---
function get_day_type($date) {
    $w = date('N', strtotime($date));
    return ($w == 6) ? 's' : (($w == 7) ? 'd' : 'u');
}

function render_comparison_card($valor_ref, $nome_ref, $valor_comp, $nome_comp) {
    // Cálculos
    $diff = (float)$valor_comp - (float)$valor_ref;
    $abs_diff = abs($diff);
    $perc = ($valor_ref > 0) ? ($diff / $valor_ref) * 100 : 0;
    $abs_perc = abs($perc);
    
    // Formatação
    $km_fmt = number_format($abs_diff, 1, ',', '.'); 
    $perc_fmt = number_format($abs_perc, 1, ',', '.');
    $val_comp_fmt = number_format($valor_comp, 1, ',', '.');
    $val_ref_fmt = number_format($valor_ref, 1, ',', '.');
    
    if ($diff > 0.09) { 
        $icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>';
        $status_text = "A MAIS";
        $text_class = "text-blue-700";
        $bg_icon = "bg-blue-100";
    } elseif ($diff < -0.09) { 
        $icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>';
        $status_text = "A MENOS";
        $text_class = "text-red-700";
        $bg_icon = "bg-red-100";
    } else { 
        $icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
        $status_text = "OK";
        $text_class = "text-green-700";
        $bg_icon = "bg-green-100";
    }

    $csv_texto = "$nome_comp fez $km_fmt km " . strtolower($status_text) . " que $nome_ref";
    if (abs($diff) <= 0.09) $csv_texto = "Valores equivalentes";

    $html = "
    <div class='bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-all p-4 h-full flex flex-col'>
        <div class='flex justify-between items-center border-b border-gray-100 pb-2 mb-3'>
            <div class='text-sm text-gray-700 font-bold'>
                <span class='uppercase tracking-wide text-gray-800'>$nome_comp</span> 
                <span class='text-gray-400 font-normal mx-1'>vs</span> 
                <span class='uppercase tracking-wide text-gray-800'>$nome_ref</span>
            </div>
        </div>
        <div class='flex justify-between items-center mb-3 text-xs font-mono text-gray-500 bg-gray-50 p-2 rounded'>
            <span>$val_comp_fmt</span>
            <span class='font-bold text-gray-400'>vs</span>
            <span>$val_ref_fmt</span>
        </div>
        <div class='flex items-center gap-3 mb-3'>
            <div class='p-2 rounded-full $bg_icon $text_class shadow-sm'>$icon</div>
            <div>
                <div class='text-2xl font-black $text_class leading-none'>" . ($diff > 0 ? '+' : ($diff < 0 ? '-' : '')) . "$km_fmt <span class='text-base font-bold'>km</span></div>
                <div class='text-sm font-bold $text_class opacity-80'>$perc_fmt%</div>
            </div>
        </div>
        <div class='mt-auto pt-2 border-t border-gray-100'>
            <p class='text-xs text-gray-500 leading-tight'><strong>$nome_comp</strong> fez <strong class='$text_class'>$km_fmt km</strong> " . strtolower($status_text) . " que $nome_ref</p>
        </div>
    </div>";

    return ['html' => $html, 'csv_texto' => $csv_texto, 'csv_val' => number_format($diff, 1, ',', '.')];
}

// --- 4. ESTRUTURAS DE DADOS ---
$dados_para_js = []; // Dados Diários
$dados_consolidados_stats = [ // Stats para os Cards da Aba 2
    'dias' => ['uteis'=>0, 'sab'=>0, 'dom'=>0, 'total'=>0],
    'planilha' => ['total'=>0, 'meta_mes_ref' => 0],
    'mtran' => ['total'=>0, 'prod'=>0, 'ocio'=>0, 'prod_u'=>0, 'prod_s'=>0, 'prod_d'=>0, 'ocio_u'=>0, 'ocio_s'=>0, 'ocio_d'=>0],
    'ajuste' => ['total'=>0, 'prod'=>0, 'ocio'=>0, 'prod_u'=>0, 'prod_s'=>0, 'prod_d'=>0, 'ocio_u'=>0, 'ocio_s'=>0, 'ocio_d'=>0],
    'clever004_p' => ['total'=>0, 'prod'=>0, 'ocio'=>0, 'prod_u'=>0, 'prod_s'=>0, 'prod_d'=>0, 'ocio_u'=>0, 'ocio_s'=>0, 'ocio_d'=>0],
    'clever004_r' => ['total'=>0, 'prod'=>0, 'ocio'=>0, 'real_u'=>0, 'real_s'=>0, 'real_d'=>0, 'ocio_u'=>0, 'ocio_s'=>0, 'ocio_d'=>0],
    'roleta' => ['total'=>0, 'u'=>0, 's'=>0, 'd'=>0],
    'life' => ['total'=>0, 'u'=>0, 's'=>0, 'd'=>0],
    'noxxon' => ['total'=>0, 'u'=>0, 's'=>0, 'd'=>0],
    'clever142' => ['total'=>0, 'u'=>0, 's'=>0, 'd'=>0],
];
$dados_veiculos = []; // Para a tabela detalhada da Aba 2

// Inicializadores
function init_registro_dia(&$array, $data) {
    if (!isset($array[$data])) {
        $array[$data] = [
            'cols' => [
                'prog_planilha' => 0.0,
                'prog_mtran_prod' => 0.0, 'prog_mtran_ocio' => 0.0,
                'ajuste_mtran_prod' => 0.0, 'ajuste_mtran_ocio' => 0.0,
                'prog_004_prod' => 0.0, 'prog_004_ocio' => 0.0,
                'real_roleta' => 0.0, 'real_life' => 0.0,
                'real_142' => 0.0, 'real_noxxon' => 0.0,
                'real_004_prod' => 0.0, 'real_004_ocio' => 0.0
            ]
        ];
    }
}
function init_veiculo_dia(&$array, $veic, $data) {
    if (!isset($array[$veic])) $array[$veic] = [];
    if (!isset($array[$veic][$data])) {
        $array[$veic][$data] = ['roleta'=>null, 'noxxon'=>null, '142'=>null, '004_r'=>null, '004_p'=>null, 'life'=>null];
    }
}

// --- 5. EXECUÇÃO DAS QUERIES ---

// A. Planilha Tarifária
if (empty($veiculo_filtro)) {
    $periodo = new DatePeriod(new DateTime($data_inicio), new DateInterval('P1D'), (new DateTime($data_fim))->modify('+1 day'));
    
    // Calcula a meta do mês de referência (para exibir no card)
    $dtInicioObj = new DateTime($data_inicio);
    $ano_ref = $dtInicioObj->format('Y');
    $meta_mensal_fixa = 820790.0; // Valor Padrão
    // Tenta buscar do config se disponível
    if (isset($metas_km_programado_anual[$ano_ref])) {
        $meta_mensal_fixa = $metas_km_programado_anual[$ano_ref];
    }
    
    $dados_consolidados_stats['planilha']['meta_mes_ref'] = $meta_mensal_fixa;

    foreach ($periodo as $dt) {
        $d = $dt->format('Y-m-d');
        $t = get_day_type($d);
        $dados_consolidados_stats['dias'][$t == 's' ? 'sab' : ($t == 'd' ? 'dom' : 'uteis')]++;
        $dados_consolidados_stats['dias']['total']++;

        init_registro_dia($dados_para_js, $d);
        $dias_mes = (int)$dt->format('t');
        $val = $meta_mensal_fixa / $dias_mes;
        $dados_para_js[$d]['cols']['prog_planilha'] = $val;
        
        $dados_consolidados_stats['planilha']['total'] += $val;
    }
}

// B. MTRAN (Geral e Ajuste) - COM REGRA OCIOSA DA V20.0
if (empty($veiculo_filtro)) {
    // Regra v20: Route 2 após 21/12/2025 vira ociosa
    $case_prod = "CASE WHEN (TRIP_ID = 0) OR (ROUTE_ID IN ('2', '002') AND data_viagem > '2025-12-21') THEN 0 ELSE CAST(DISTANCE AS DECIMAL(10,2)) END";
    $case_ocio = "CASE WHEN (TRIP_ID = 0) OR (ROUTE_ID IN ('2', '002') AND data_viagem > '2025-12-21') THEN CAST(DISTANCE AS DECIMAL(10,2)) ELSE 0 END";

    // MTRAN Padrão
    $res = $conexao->query("SELECT data_viagem, SUM($case_prod)/1000.0 as p, SUM($case_ocio)/1000.0 as o FROM relatorios_viagens WHERE data_viagem $where_periodo GROUP BY data_viagem");
    if($res) while($r = $res->fetch_assoc()) {
        $d = $r['data_viagem']; $p = (float)$r['p']; $o = (float)$r['o']; $t = get_day_type($d);
        init_registro_dia($dados_para_js, $d);
        $dados_para_js[$d]['cols']['prog_mtran_prod'] = $p;
        $dados_para_js[$d]['cols']['prog_mtran_ocio'] = $o;
        
        // Stats Aba 2
        $dados_consolidados_stats['mtran']['total'] += ($p + $o);
        $dados_consolidados_stats['mtran']['prod'] += $p; $dados_consolidados_stats['mtran']['ocio'] += $o;
        $dados_consolidados_stats['mtran']['prod_'.$t] += $p; $dados_consolidados_stats['mtran']['ocio_'.$t] += $o;
    }

    // MTRAN Ajuste
    $res = $conexao->query("SELECT data_viagem, SUM($case_prod)/1000.0 as p, SUM($case_ocio)/1000.0 as o FROM relatorios_viagens_ajuste WHERE data_viagem $where_periodo GROUP BY data_viagem");
    if($res) while($r = $res->fetch_assoc()) {
        $d = $r['data_viagem']; $p = (float)$r['p']; $o = (float)$r['o']; $t = get_day_type($d);
        init_registro_dia($dados_para_js, $d);
        $dados_para_js[$d]['cols']['ajuste_mtran_prod'] = $p;
        $dados_para_js[$d]['cols']['ajuste_mtran_ocio'] = $o;
        
        // Stats Aba 2
        $dados_consolidados_stats['ajuste']['total'] += ($p + $o);
        $dados_consolidados_stats['ajuste']['prod'] += $p; $dados_consolidados_stats['ajuste']['ocio'] += $o;
        $dados_consolidados_stats['ajuste']['prod_'.$t] += $p; $dados_consolidados_stats['ajuste']['ocio_'.$t] += $o;
    }
}

// C. Clever 004
$sql_004 = "SELECT data_viagem, vehicle,
            SUM(CAST(distancia_produtiva_p_km AS DECIMAL(10,2))) as pp, 
            SUM(CAST(distancia_ociosa_p_km AS DECIMAL(10,2)) + CAST(distancia_recolha_p_km AS DECIMAL(10,2))) as po, 
            SUM(CAST(distancia_produtiva_r_km AS DECIMAL(10,2))) as rp, 
            SUM(CAST(distancia_ociosa_r_km AS DECIMAL(10,2)) + CAST(distancia_recolha_r_km AS DECIMAL(10,2))) as ro 
            FROM relatorios_frota WHERE data_viagem $where_periodo $sql_filtro_veiculo_exato GROUP BY data_viagem, vehicle";
$res = $conexao->query($sql_004);
if($res) while($r = $res->fetch_assoc()) {
    $d = $r['data_viagem']; $v = trim($r['vehicle'] ?? ''); $t = get_day_type($d);
    $pp = (float)$r['pp']; $po = (float)$r['po']; $rp = (float)$r['rp']; $ro = (float)$r['ro'];

    init_registro_dia($dados_para_js, $d);
    $dados_para_js[$d]['cols']['prog_004_prod'] += $pp;
    $dados_para_js[$d]['cols']['prog_004_ocio'] += $po;
    $dados_para_js[$d]['cols']['real_004_prod'] += $rp;
    $dados_para_js[$d]['cols']['real_004_ocio'] += $ro;
    
    // Se filtro de veículo ativo, MTRAN assume 004 (pois MTRAN não tem coluna vehicle)
    if (!empty($veiculo_filtro)) { 
        $dados_para_js[$d]['cols']['prog_mtran_prod'] += $pp; 
        $dados_para_js[$d]['cols']['prog_mtran_ocio'] += $po; 
    }

    // Stats Aba 2
    $dados_consolidados_stats['clever004_p']['total'] += ($pp + $po);
    $dados_consolidados_stats['clever004_p']['prod'] += $pp;
    $dados_consolidados_stats['clever004_p']['ocio'] += $po;
    $dados_consolidados_stats['clever004_p']['prod_'.$t] += $pp;
    $dados_consolidados_stats['clever004_p']['ocio_'.$t] += $po;

    $dados_consolidados_stats['clever004_r']['total'] += ($rp + $ro);
    $dados_consolidados_stats['clever004_r']['prod'] += $rp; 
    $dados_consolidados_stats['clever004_r']['ocio'] += $ro;
    $dados_consolidados_stats['clever004_r']['real_'.$t] += ($rp + $ro);
    $dados_consolidados_stats['clever004_r']['ocio_'.$t] += $ro;

    if($v) {
        init_veiculo_dia($dados_veiculos, $v, $d);
        $dados_veiculos[$v][$d]['004_r'] = $rp + $ro;
        $dados_veiculos[$v][$d]['004_p'] = $pp + $po;
    }
}

// D. Roleta (Otimizado v15)
$res = $conexao->query("SELECT data_leitura, vehicle, total FROM relatorios_km_roleta_diario WHERE data_leitura $where_periodo $sql_filtro_veiculo_exato");
if($res) while($r = $res->fetch_assoc()) {
    $d = $r['data_leitura']; $v = trim($r['vehicle'] ?? ''); $val = (float)$r['total']; $t = get_day_type($d);
    init_registro_dia($dados_para_js, $d);
    $dados_para_js[$d]['cols']['real_roleta'] += $val;
    
    $dados_consolidados_stats['roleta']['total'] += $val;
    $dados_consolidados_stats['roleta'][$t] += $val;
    
    if($v) { init_veiculo_dia($dados_veiculos, $v, $d); $dados_veiculos[$v][$d]['roleta'] = $val; }
}

// E. Life (Otimizado v15 - CTE)
$sql_life = "WITH Extremos_Dia AS ( SELECT DATE(data_leitura) as d, vehicle, MAX(CASE WHEN rn_last = 1 THEN odometro_km END) as ultimo_km, MAX(CASE WHEN rn_first = 1 THEN odometro_km END) as primeiro_km FROM ( SELECT DATE(data_leitura) as data_leitura, vehicle, odometro_km, ROW_NUMBER() OVER (PARTITION BY DATE(data_leitura), vehicle ORDER BY data_leitura ASC) as rn_first, ROW_NUMBER() OVER (PARTITION BY DATE(data_leitura), vehicle ORDER BY data_leitura DESC) as rn_last FROM registros_odometro_life WHERE DATE(data_leitura) $where_periodo $sql_filtro_veiculo_exato ) ranked WHERE rn_first = 1 OR rn_last = 1 GROUP BY d, vehicle ) SELECT d, vehicle, SUM(ultimo_km - primeiro_km) as km_dia FROM Extremos_Dia WHERE (ultimo_km - primeiro_km) > 0 AND (ultimo_km - primeiro_km) < 1500 GROUP BY d, vehicle";
$res = $conexao->query($sql_life);
if($res) while($r = $res->fetch_assoc()) {
    $d = $r['d']; $v = trim($r['vehicle'] ?? ''); $val = (float)$r['km_dia']; $t = get_day_type($d);
    init_registro_dia($dados_para_js, $d);
    $dados_para_js[$d]['cols']['real_life'] += $val;
    
    $dados_consolidados_stats['life']['total'] += $val;
    $dados_consolidados_stats['life'][$t] += $val;
    
    if($v) { init_veiculo_dia($dados_veiculos, $v, $d); $dados_veiculos[$v][$d]['life'] = $val; }
}

// F. Noxxon e 142
$queries_extra = [
    'real_noxxon' => ["SELECT data_leitura as d, vehicle, CAST(REPLACE(km_percorrido, ',', '.') AS DECIMAL(10,2)) as val FROM relatorios_km_noxxon_diario WHERE data_leitura $where_periodo $sql_filtro_veiculo_exato", 'noxxon'],
    'real_142' => ["SELECT data_viagem as d, vehicle, CAST(REPLACE(total_distancia_km, ',', '.') AS DECIMAL(10,2)) as val FROM relatorios_divisao WHERE data_viagem $where_periodo $sql_filtro_veiculo_exato", 'clever142']
];
foreach($queries_extra as $key_js => $cfg) {
    $res = $conexao->query($cfg[0]);
    if($res) while($r = $res->fetch_assoc()) {
        $d = $r['d']; $v = trim($r['vehicle'] ?? ''); $val = (float)$r['val']; $t = get_day_type($d);
        init_registro_dia($dados_para_js, $d);
        $dados_para_js[$d]['cols'][$key_js] += $val;
        
        $dados_consolidados_stats[$cfg[1]]['total'] += $val;
        $dados_consolidados_stats[$cfg[1]][$t] += $val;
        
        if($v) { 
            init_veiculo_dia($dados_veiculos, $v, $d); 
            $dados_veiculos[$v][$d][$cfg[1] == 'clever142' ? '142' : 'noxxon'] = $val; 
        }
    }
}

// Ordenação e Processamento Final (Diário)
ksort($dados_para_js);
ksort($dados_veiculos);

$cols_tabela = ['prog_planilha','prog_mtran_prod','prog_mtran_ocio','ajuste_mtran_prod','ajuste_mtran_ocio','prog_004_prod','prog_004_ocio','real_roleta','real_life','real_142','real_noxxon','real_004_prod'];
$totais_tabela = array_fill_keys($cols_tabela, 0.0);

// Top Veículos (Para a Aba Consolidada)
$top_veiculos = [];
if (empty($veiculo_filtro)) {
    $sql_top = [
        'Roleta' => "SELECT vehicle, SUM(total) as km FROM relatorios_km_roleta_diario WHERE data_leitura $where_periodo GROUP BY vehicle ORDER BY km DESC LIMIT 5",
        'Noxxon' => "SELECT vehicle, SUM(CAST(REPLACE(km_percorrido, ',', '.') AS DECIMAL(10,2))) as km FROM relatorios_km_noxxon_diario WHERE data_leitura $where_periodo GROUP BY vehicle ORDER BY km DESC LIMIT 5",
        'Clever 142' => "SELECT vehicle, SUM(CAST(REPLACE(total_distancia_km, ',', '.') AS DECIMAL(10,2))) as km FROM relatorios_divisao WHERE data_viagem $where_periodo GROUP BY vehicle ORDER BY km DESC LIMIT 5",
        'Clever 004' => "SELECT vehicle, SUM(CAST(distancia_r_km AS DECIMAL(10,2))) as km FROM relatorios_frota WHERE data_viagem $where_periodo GROUP BY vehicle ORDER BY km DESC LIMIT 5"
    ];
    foreach($sql_top as $k=>$q) { $res=$conexao->query($q); if($res) $top_veiculos[$k]=$res->fetch_all(MYSQLI_ASSOC); }
}

// Processa Cards Diários (V15.0 LOGIC)
foreach($dados_para_js as $data => $item) {
    $cols = $item['cols'];
    foreach($cols_tabela as $k) $totais_tabela[$k] += ($cols[$k] ?? 0);

    $total_mtran_prog = $cols['prog_mtran_prod'] + $cols['prog_mtran_ocio'];
    $total_mtran_ajuste = $cols['ajuste_mtran_prod'] + $cols['ajuste_mtran_ocio'];
    $total_004_prog = $cols['prog_004_prod'] + $cols['prog_004_ocio'];
    $total_004_real = $cols['real_004_prod'] + $cols['real_004_ocio'];
    
    $group1 = [
        render_comparison_card($total_mtran_prog, 'MTRAN (P)', $cols['real_roleta'], 'Roleta'),
        render_comparison_card($total_mtran_ajuste, 'Ajuste', $cols['real_roleta'], 'Roleta'),
        render_comparison_card($total_004_prog, '004 (P)', $cols['real_roleta'], 'Roleta')
    ];
    $group2 = [
        render_comparison_card($cols['real_life'], 'Life', $cols['real_roleta'], 'Roleta'),
        render_comparison_card($cols['real_noxxon'], 'Noxxon', $cols['real_roleta'], 'Roleta'),
        render_comparison_card($cols['real_142'], '142', $cols['real_roleta'], 'Roleta')
    ];
    $group3 = [
        render_comparison_card($total_004_real, '004 (R)', $cols['real_142'], '142')
    ];

    $dados_para_js[$data]['groups'] = ['Roleta vs Programado' => $group1, 'Roleta vs Realizado' => $group2, 'Interno' => $group3];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de KM Consolidado</title>
    <script src="tailwindcss-3.4.17.js"></script>
    <style>
        @import url('css2.css');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; font-size: 0.95rem; }
        
        /* Tabela Estilos v15 */
        .main-table th { background-color: #e2e8f0; position: sticky; top: 0; z-index: 20; font-size: 0.8rem; text-transform: uppercase; padding: 10px 6px; text-align: center; border-bottom: 2px solid #cbd5e1; }
        .main-table td { padding: 12px 8px; text-align: center; border-bottom: 1px solid #e2e8f0; white-space: nowrap; font-size: 0.95rem; font-weight: 500; }
        .main-table tr:hover { background-color: #f1f5f9; cursor: pointer; }
        .main-table .date-col { text-align: left; font-weight: 600; min-width: 100px; }
        .hidden-row, .hidden-col, .hidden-section { display: none !important; }
        .expanded-row { background-color: #e0f2fe !important; border-left: 4px solid #3b82f6; }
        .col-toggle-label { display: inline-flex; align-items: center; margin-right: 12px; font-size: 0.85rem; color: #475569; cursor: pointer; user-select: none; }
        .col-toggle-checkbox { margin-right: 4px; accent-color: #2563eb; transform: scale(1.1); }

        /* Estilos Cards Consolidado (Atualizado v2.8 Visual) */
        .summary-card { color: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: flex; flex-direction: column; justify-content: space-between; padding: 1rem; min-height: 160px; }
        .summary-card-prog { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .summary-card-prog-mtran { background: linear-gradient(135deg, #38b2ac 0%, #319795 100%); color: white; } /* Verde-água */
        .summary-card-prog-004 { background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); color: white; } /* Verde */
        .bg-purple { background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); }
        .bg-teal { background: linear-gradient(135deg, #14b8a6 0%, #0f766e 100%); }
        .bg-blue { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
        .bg-green { background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); }
        
        .card-white { background: white; border-left: 4px solid; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 1rem; border-radius: 8px; }
        .border-yellow { border-color: #eab308; } .text-yellow { color: #ca8a04; }
        .border-blue { border-color: #3b82f6; } .text-blue { color: #2563eb; }
        .border-pink { border-color: #ec4899; } .text-pink { color: #db2777; }
        .border-green { border-color: #22c55e; } .text-green { color: #16a34a; }
        
        /* Ajuste Listas Cards */
        .summary-card ul, .summary-card ol { margin-left: 0; padding-left: 0; list-style: none; }
        .summary-card li { display: flex; justify-content: space-between; align-items: center; padding: 0.25rem 0; border-bottom: 1px solid rgba(255, 255, 255, 0.1); font-size: 0.75rem; }
        .summary-card li:last-child { border-bottom: none; }
        .summary-card li span:first-child { font-weight: 500; }
        .summary-card li span:last-child { font-weight: 600; background-color: rgba(0,0,0,0.1); padding: 0.125rem 0.5rem; border-radius: 99px; }
        
        .card-white-detail { font-size: 0.75rem; color: #64748b; margin-top: 0.5rem; }
        .card-km-top h4 { font-size: 0.875rem; }
        .card-km-top li { display: flex; justify-content: space-between; padding: 0.125rem 0; font-size: 0.8rem; border-bottom: 1px solid #f1f5f9; }
        .card-km-top li span:first-child { font-weight: 500; }
        .card-km-top li strong { font-weight: 600; }
        .card-km-top .fonte-roleta { color: #d97706; }
        .card-km-top .fonte-clever-142 { color: #db2777; }
        .card-km-top .fonte-clever-004 { color: #16a34a; }
        .card-km-top .fonte-life { color: #4f46e5; }
        .card-km-top .fonte-noxxon { color: #2563eb; }
        
        /* Helpers de Tabela */
        .table-container { max-height: 500px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 0.5rem; }
        details > summary { list-style: none; cursor: pointer; padding: 0.75rem 1rem; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; transition: background-color 0.2s ease; }
        details > summary:hover { background-color: #f3f4f6; }
        details[open] > summary { background-color: #e5e7eb; font-weight: 600; }
    </style>
</head>
<body class="p-4">
    <div class="max-w-full mx-auto bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div><h1 class="text-3xl font-bold text-slate-800">Relatório de KM Consolidado</h1><p class="text-sm text-gray-500">Hub central de quilometragem: Diário e Consolidado.</p></div>
            <form class="flex flex-wrap gap-2 items-end">
                <div><label class="block text-xs font-bold text-gray-600">Início</label><input type="date" name="data_inicio" value="<?= $data_inicio ?>" class="border rounded p-2 text-sm"></div>
                <div><label class="block text-xs font-bold text-gray-600">Fim</label><input type="date" name="data_fim" value="<?= $data_fim ?>" class="border rounded p-2 text-sm"></div>
                <div><label class="block text-xs font-bold text-gray-600">Veículo</label><input type="text" name="veiculo" value="<?= htmlspecialchars($veiculo_filtro) ?>" placeholder="Todos" class="border rounded p-2 text-sm w-28"></div>
                <button type="submit" class="bg-slate-800 text-white px-4 py-2 rounded text-sm font-bold hover:bg-slate-900">Filtrar</button>
            </form>
        </div>

        <!-- Abas -->
        <div class="flex gap-4 mb-6 border-b border-gray-200 pb-2">
            <button onclick="switchView('table')" id="btn-table" class="px-6 py-2 rounded-t-lg font-bold transition-colors bg-blue-600 text-white shadow-lg">Visão Diária (Tabela)</button>
            <button onclick="switchView('cards')" id="btn-cards" class="px-6 py-2 rounded-t-lg font-bold transition-colors bg-gray-200 text-gray-600 hover:bg-gray-300">Visão Consolidada (Cards)</button>
        </div>

        <!-- VIEW 1: TABELA DIÁRIA (Restaurada da v15.0) -->
        <div id="view-table" class="block animate-fade-in">
            <!-- Toggles de Coluna -->
            <div class="mb-4 p-4 bg-slate-50 rounded border border-slate-200">
                <h3 class="text-sm font-bold text-slate-600 uppercase mb-3">Visualização da Tabela:</h3>
                <div class="flex flex-wrap gap-y-3">
                    <label class="col-toggle-label"><input type="checkbox" checked data-col="2" onclick="toggleColumn(this)" class="col-toggle-checkbox"> Planilha Tarifária</label>
                    <label class="col-toggle-label"><input type="checkbox" checked data-col="3" onclick="toggleColumn(this)" class="col-toggle-checkbox"> MTRAN Produtiva</label>
                    <label class="col-toggle-label"><input type="checkbox" checked data-col="4" onclick="toggleColumn(this)" class="col-toggle-checkbox"> MTRAN Ociosa</label>
                    <label class="col-toggle-label text-blue-700 font-bold"><input type="checkbox" checked data-col="5" onclick="toggleColumn(this)" class="col-toggle-checkbox"> Ajuste Produtiva</label>
                    <label class="col-toggle-label text-blue-700 font-bold"><input type="checkbox" checked data-col="6" onclick="toggleColumn(this)" class="col-toggle-checkbox"> Ajuste Ociosa</label>
                    <label class="col-toggle-label"><input type="checkbox" checked data-col="7" onclick="toggleColumn(this)" class="col-toggle-checkbox"> 004 Programada - Produtiva</label>
                    <label class="col-toggle-label"><input type="checkbox" checked data-col="8" onclick="toggleColumn(this)" class="col-toggle-checkbox"> 004 Programada -  Ociosa</label>
                    <label class="col-toggle-label bg-yellow-100 px-2 rounded"><input type="checkbox" checked data-col="9" onclick="toggleColumn(this)" class="col-toggle-checkbox"> Roleta Realizada</label>
                    <label class="col-toggle-label"><input type="checkbox" checked data-col="10" onclick="toggleColumn(this)" class="col-toggle-checkbox"> Life Realizada</label>
                    <label class="col-toggle-label"><input type="checkbox" checked data-col="11" onclick="toggleColumn(this)" class="col-toggle-checkbox"> 142 Realizada</label>
                    <label class="col-toggle-label"><input type="checkbox" checked data-col="12" onclick="toggleColumn(this)" class="col-toggle-checkbox"> Noxxon Realizada</label>
                    <label class="col-toggle-label"><input type="checkbox" checked data-col="13" onclick="toggleColumn(this)" class="col-toggle-checkbox"> 004 Realizada</label>
                </div>
            </div>
            
            <!-- Botões Exportar (v15.0) -->
            <div class="flex gap-3 mb-4 border-b border-gray-100 pb-4">
                <button onclick="exportSimpleCSV()" class="bg-gray-600 text-white px-4 py-2 rounded text-sm font-bold hover:bg-gray-700 flex items-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg> CSV Tabela</button>
                <button onclick="exportCompleteCSV()" class="bg-green-600 text-white px-4 py-2 rounded text-sm font-bold hover:bg-green-700 flex items-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg> CSV Comparativo</button>
            </div>

            <!-- Tabela Principal -->
            <div class="overflow-x-auto border rounded-lg shadow-sm">
                <table class="main-table w-full" id="tabela-resumo">
                    <thead>
                        <tr>
                            <th class="w-8"></th>
                            <th class="date-col">Data</th>
                            <th>Planilha<br>Tarifária</th>
                            <th class="bg-indigo-50 text-indigo-900">MTRAN<br>Produtiva</th>
                            <th class="bg-indigo-50 text-indigo-900">MTRAN<br>Ociosa</th>
                            <th class="bg-blue-100 text-blue-900 border-l-2 border-blue-300">Ajuste<br>Produtiva</th>
                            <th class="bg-blue-100 text-blue-900 border-r-2 border-blue-300">Ajuste<br>Ociosa</th>
                            <th class="bg-green-50 text-green-900">004 Programada<br>Produtiva</th>
                            <th class="bg-green-50 text-green-900">004 Programada<br>Ociosa</th>
                            <th class="bg-yellow-100 text-yellow-900 font-bold border-l-2 border-yellow-300">Roleta<br>Realizada</th>
                            <th class="bg-blue-100 text-blue-900">Life<br>Realizada</th>
                            <th class="bg-pink-100 text-pink-900">142<br>Realizada</th>
                            <th class="bg-cyan-100 text-cyan-900">Noxxon<br>Realizada</th>
                            <th class="bg-green-200 text-green-900">004 Produtiva<br>Realizada</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dados_para_js)): ?>
                            <tr><td colspan="14" class="text-center py-8 text-gray-500">Nenhum dado encontrado.</td></tr>
                        <?php else: ?>
                            <?php foreach($dados_para_js as $data => $item): 
                                $cols = $item['cols']; $rowId = 'row-' . $data;
                            ?>
                            <tr onclick="toggleDetails('<?= $rowId ?>')" class="border-b main-row">
                                <td class="text-center font-bold text-gray-400 hover:text-blue-600 transition-colors text-lg">+</td>
                                <td class="date-col"><?= date('d/m/y', strtotime($data)) ?></td>
                                <td><?= number_format($cols['prog_planilha'], 1, ',', '.') ?></td>
                                <td class="bg-indigo-50 font-semibold text-indigo-700"><?= number_format($cols['prog_mtran_prod'], 1, ',', '.') ?></td>
                                <td class="bg-indigo-50 text-indigo-600"><?= number_format($cols['prog_mtran_ocio'], 1, ',', '.') ?></td>
                                <td class="bg-blue-100 border-l-2 border-blue-200 font-bold text-blue-800"><?= number_format($cols['ajuste_mtran_prod'], 1, ',', '.') ?></td>
                                <td class="bg-blue-100 border-r-2 border-blue-200 text-blue-700"><?= number_format($cols['ajuste_mtran_ocio'], 1, ',', '.') ?></td>
                                <td class="bg-green-50 text-green-700"><?= number_format($cols['prog_004_prod'], 1, ',', '.') ?></td>
                                <td class="bg-green-50 text-green-600"><?= number_format($cols['prog_004_ocio'], 1, ',', '.') ?></td>
                                <td class="bg-yellow-50 font-bold text-yellow-900 border-l-2 border-yellow-200"><?= number_format($cols['real_roleta'], 1, ',', '.') ?></td>
                                <td class="bg-blue-50 text-blue-900"><?= number_format($cols['real_life'], 1, ',', '.') ?></td>
                                <td class="bg-pink-50 text-pink-900"><?= number_format($cols['real_142'], 1, ',', '.') ?></td>
                                <td class="bg-cyan-50 text-cyan-900 font-bold"><?= number_format($cols['real_noxxon'], 1, ',', '.') ?></td>
                                <td class="bg-green-100 font-bold text-green-900"><?= number_format($cols['real_004_prod'], 1, ',', '.') ?></td>
                            </tr>
                            <tr id="<?= $rowId ?>" class="hidden-row bg-slate-100 shadow-inner">
                                <td colspan="14" class="p-0">
                                    <div class="p-6">
                                        <?php foreach($item['groups'] as $titulo_grupo => $cards): ?>
                                            <h4 class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-3 mt-4 first:mt-0 border-b border-slate-200 pb-1"><?= $titulo_grupo ?></h4>
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                <?php foreach($cards as $card): ?><?= $card['html'] ?><?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-800 text-white font-bold text-center text-base">
                            <td colspan="2" class="text-left pl-4 py-3">TOTAIS</td>
                            <td><?= number_format($totais_tabela['prog_planilha'], 1, ',', '.') ?></td>
                            <td><?= number_format($totais_tabela['prog_mtran_prod'], 1, ',', '.') ?></td>
                            <td><?= number_format($totais_tabela['prog_mtran_ocio'], 1, ',', '.') ?></td>
                            <td><?= number_format($totais_tabela['ajuste_mtran_prod'], 1, ',', '.') ?></td>
                            <td><?= number_format($totais_tabela['ajuste_mtran_ocio'], 1, ',', '.') ?></td>
                            <td><?= number_format($totais_tabela['prog_004_prod'], 1, ',', '.') ?></td>
                            <td><?= number_format($totais_tabela['prog_004_ocio'], 1, ',', '.') ?></td>
                            <td class="text-yellow-300"><?= number_format($totais_tabela['real_roleta'], 1, ',', '.') ?></td>
                            <td><?= number_format($totais_tabela['real_life'], 1, ',', '.') ?></td>
                            <td><?= number_format($totais_tabela['real_142'], 1, ',', '.') ?></td>
                            <td><?= number_format($totais_tabela['real_noxxon'], 1, ',', '.') ?></td>
                            <td><?= number_format($totais_tabela['real_004_prod'], 1, ',', '.') ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- VIEW 2: CARDS CONSOLIDADOS (Atualizado para Visual V2.8) -->
        <div id="view-cards" class="hidden-section animate-fade-in">
            <section class="mb-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                
                <!-- 1. Planilha -->
                <div class="summary-card-prog p-5 rounded-lg shadow-lg flex flex-col justify-between summary-card">
                    <div>
                        <h3 class="font-semibold opacity-90 mb-1">KM Programado (Planilha Tarifária)</h3>
                        <p class="text-4xl font-bold"><?= number_format($dados_consolidados_stats['planilha']['total'], 1, ',', '.') ?> km</p>
                        <p class="text-base opacity-90 mt-1">
                            (<?= $dados_consolidados_stats['dias']['total'] ?> dias do período)
                        </p>
                        <?php 
                            $mes_nome = $meses_pt_br[(int)date('m', strtotime($data_inicio))] ?? '';
                            $ano_ref = date('Y', strtotime($data_inicio));
                        ?>
                        <p class="text-sm opacity-80 mt-1">
                            Ref. Mês (<?= $mes_nome ?> de <?= $ano_ref ?>): <?= number_format($dados_consolidados_stats['planilha']['meta_mes_ref'], 0, ',', '.') ?> km
                        </p>
                    </div>
                    <p class="text-sm opacity-80 mt-2">
                        Período: <?= $dados_consolidados_stats['dias']['uteis']?> D. Úteis, <?= $dados_consolidados_stats['dias']['sab']?> Sáb, <?= $dados_consolidados_stats['dias']['dom']?> Dom
                    </p>
                </div>

                <!-- 2. MTRAN (Atualizado Layout) -->
                <div class="summary-card-prog-mtran p-5 rounded-lg shadow-lg flex flex-col justify-between summary-card">
                    <div>
                        <h3 class="font-semibold opacity-90 mb-1">KM Programado (MTRAN)</h3>
                        <p class="text-4xl font-bold"><?= number_format($dados_consolidados_stats['mtran']['total'], 1, ',', '.') ?> km</p>
                    </div>
                    <div class="mt-2 text-sm space-y-1">
                        <p>Produtivo: <span class="font-semibold"><?= number_format($dados_consolidados_stats['mtran']['prod'], 1, ',', '.') ?> km</span></p>
                        <p>Ocioso: <span class="font-semibold"><?= number_format($dados_consolidados_stats['mtran']['ocio'], 1, ',', '.') ?> km</span></p>
                    </div>
                    <ul class="mt-2 text-xs opacity-90">
                        <li><span>Produtivo Úteis:</span> <span><?= number_format($dados_consolidados_stats['mtran']['prod_u'], 0, ',', '.') ?> km</span></li>
                        <li><span>Produtivo Sábados:</span> <span><?= number_format($dados_consolidados_stats['mtran']['prod_s'], 0, ',', '.') ?> km</span></li>
                        <li><span>Produtivo Domingos:</span> <span><?= number_format($dados_consolidados_stats['mtran']['prod_d'], 0, ',', '.') ?> km</span></li>
                        <li class="mt-1 pt-1 border-t border-white/10"><span>Ocioso Úteis:</span> <span><?= number_format($dados_consolidados_stats['mtran']['ocio_u'], 0, ',', '.') ?> km</span></li>
                        <li><span>Ocioso Sábados:</span> <span><?= number_format($dados_consolidados_stats['mtran']['ocio_s'], 0, ',', '.') ?> km</span></li>
                        <li><span>Ocioso Domingos:</span> <span><?= number_format($dados_consolidados_stats['mtran']['ocio_d'], 0, ',', '.') ?> km</span></li>
                    </ul>
                </div>

                <!-- 3. Clever 004 (Prog) (Atualizado Layout) -->
                <div class="summary-card-prog-004 p-5 rounded-lg shadow-lg flex flex-col justify-between summary-card">
                    <div>
                        <h3 class="font-semibold opacity-90 mb-1">KM Programado (Clever 004)</h3>
                        <p class="text-4xl font-bold"><?= number_format($dados_consolidados_stats['clever004_p']['total'], 1, ',', '.') ?> km</p>
                    </div>
                    <div class="mt-2 text-sm space-y-1">
                        <p>Produtivo: <span class="font-semibold"><?= number_format($dados_consolidados_stats['clever004_p']['prod'], 1, ',', '.') ?> km</span></p>
                        <p>Ocioso: <span class="font-semibold"><?= number_format($dados_consolidados_stats['clever004_p']['ocio'], 1, ',', '.') ?> km</span></p>
                    </div>
                    <ul class="mt-2 text-xs opacity-90">
                        <li><span>Produtivo Úteis:</span> <span><?= number_format($dados_consolidados_stats['clever004_p']['prod_u'], 0, ',', '.') ?> km</span></li>
                        <li><span>Produtivo Sábados:</span> <span><?= number_format($dados_consolidados_stats['clever004_p']['prod_s'], 0, ',', '.') ?> km</span></li>
                        <li><span>Produtivo Domingos:</span> <span><?= number_format($dados_consolidados_stats['clever004_p']['prod_d'], 0, ',', '.') ?> km</span></li>
                    </ul>
                </div>

                <!-- 4. Roleta -->
                <div class="card bg-white p-5 rounded-lg shadow-lg flex flex-col justify-between summary-card">
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-1">KM Realizado (Roleta)</h3>
                        <p class="text-4xl font-bold text-yellow-600"><?= number_format($dados_consolidados_stats['roleta']['total'], 1, ',', '.') ?> km</p>
                    </div>
                    <div class="mt-2 text-sm text-gray-600 space-y-1">
                        <p>Dias Úteis: <span class="font-semibold"><?= number_format($dados_consolidados_stats['roleta']['u'], 1, ',', '.') ?> km</span></p>
                        <p>Sábados: <span class="font-semibold"><?= number_format($dados_consolidados_stats['roleta']['s'], 1, ',', '.') ?> km</span></p>
                        <p>Domingos: <span class="font-semibold"><?= number_format($dados_consolidados_stats['roleta']['d'], 1, ',', '.') ?> km</span></p>
                    </div>
                </div>

                <!-- Linha 2 -->
                
                <!-- 5. Life -->
                <div class="card bg-white p-5 rounded-lg shadow-lg flex flex-col justify-between summary-card">
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-1">KM Realizado (Life)</h3>
                        <p class="text-4xl font-bold text-indigo-600"><?= number_format($dados_consolidados_stats['life']['total'], 1, ',', '.') ?> km</p>
                    </div>
                    <div class="mt-2 text-sm text-gray-600 space-y-1">
                        <p>Dias Úteis: <span class="font-semibold"><?= number_format($dados_consolidados_stats['life']['u'], 1, ',', '.') ?> km</span></p>
                        <p>Sábados: <span class="font-semibold"><?= number_format($dados_consolidados_stats['life']['s'], 1, ',', '.') ?> km</span></p>
                        <p>Domingos: <span class="font-semibold"><?= number_format($dados_consolidados_stats['life']['d'], 1, ',', '.') ?> km</span></p>
                    </div>
                </div>

                <!-- 6. Clever 142 -->
                <div class="card bg-white p-5 rounded-lg shadow-lg flex flex-col justify-between summary-card">
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-1">KM Realizado (Clever 142)</h3>
                        <p class="text-4xl font-bold text-pink-600"><?= number_format($dados_consolidados_stats['clever142']['total'], 1, ',', '.') ?> km</p>
                    </div>
                    <div class="mt-2 text-sm text-gray-600 space-y-1">
                        <p>Dias Úteis: <span class="font-semibold"><?= number_format($dados_consolidados_stats['clever142']['u'], 1, ',', '.') ?> km</span></p>
                        <p>Sábados: <span class="font-semibold"><?= number_format($dados_consolidados_stats['clever142']['s'], 1, ',', '.') ?> km</span></p>
                        <p>Domingos: <span class="font-semibold"><?= number_format($dados_consolidados_stats['clever142']['d'], 1, ',', '.') ?> km</span></p>
                    </div>
                </div>

                <!-- 7. Clever 004 Realizado -->
                <div class="card bg-white p-5 rounded-lg shadow-lg flex flex-col justify-between summary-card">
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-1">KM Realizado (Clever 004)</h3>
                        <p class="text-4xl font-bold text-green-600"><?= number_format($dados_consolidados_stats['clever004_r']['total'], 1, ',', '.') ?> km</p>
                        <p class="text-sm text-gray-600 mt-1">Ocioso: <span class="font-semibold"><?= number_format($dados_consolidados_stats['clever004_r']['ocio'], 1, ',', '.') ?> km</span></p>
                    </div>
                    <ul class="mt-2 text-xs text-gray-600">
                        <li><span>Realizado Úteis:</span> <span class="font-semibold"><?= number_format($dados_consolidados_stats['clever004_r']['real_u'], 1, ',', '.') ?> km</span></li>
                        <li><span>Realizado Sábados:</span> <span class="font-semibold"><?= number_format($dados_consolidados_stats['clever004_r']['real_s'], 1, ',', '.') ?> km</span></li>
                        <li><span>Realizado Domingos:</span> <span class="font-semibold"><?= number_format($dados_consolidados_stats['clever004_r']['real_d'], 1, ',', '.') ?> km</span></li>
                        <li class="mt-1 pt-1 border-t border-gray-200"><span>Ocioso Úteis:</span> <span class="font-semibold"><?= number_format($dados_consolidados_stats['clever004_r']['ocio_u'], 1, ',', '.') ?> km</span></li>
                        <li><span>Ocioso Sábados:</span> <span class="font-semibold"><?= number_format($dados_consolidados_stats['clever004_r']['ocio_s'], 1, ',', '.') ?> km</span></li>
                        <li><span>Ocioso Domingos:</span> <span class="font-semibold"><?= number_format($dados_consolidados_stats['clever004_r']['ocio_d'], 1, ',', '.') ?> km</span></li>
                    </ul>
                </div>

                <!-- 8. Noxxon -->
                <div class="card bg-white p-5 rounded-lg shadow-lg flex flex-col justify-between summary-card">
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-1">KM Realizado (Noxxon)</h3>
                        <p class="text-4xl font-bold text-blue-600"><?= number_format($dados_consolidados_stats['noxxon']['total'], 1, ',', '.') ?> km</p>
                    </div>
                    <div class="mt-2 text-sm text-gray-600 space-y-1">
                        <p>Dias Úteis: <span class="font-semibold"><?= number_format($dados_consolidados_stats['noxxon']['u'], 1, ',', '.') ?> km</span></p>
                        <p>Sábados: <span class="font-semibold"><?= number_format($dados_consolidados_stats['noxxon']['s'], 1, ',', '.') ?> km</span></p>
                        <p>Domingos: <span class="font-semibold"><?= number_format($dados_consolidados_stats['noxxon']['d'], 1, ',', '.') ?> km</span></p>
                    </div>
                </div>

            </section>

            <!-- Diferenças e Top Veículos -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow lg:col-span-1">
                    <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Diferenças vs. Roleta</h3>
                    <ul class="space-y-3 text-sm">
                        <?php 
                        function calcular_diff_percentual($a, $b) { if($b==0) return 0; return (($a-$b)/$b)*100; }
                        function get_cor_diff_perc($val) { if($val===null) return 'text-gray-500'; return (abs($val)>5) ? 'text-red-600 font-bold' : 'text-green-600'; }
                        function get_cor_diff_abs($val) { if($val===null) return 'text-gray-500'; return abs($val)>10 ? 'text-red-600 font-bold' : 'text-green-600'; }

                        $itens = [
                            'MTRAN' => $dados_consolidados_stats['mtran']['total'],
                            'MTRAN Ajuste' => $dados_consolidados_stats['ajuste']['total'],
                            'Clever 142' => $dados_consolidados_stats['clever142']['total'],
                            'Clever 004 (R)' => $dados_consolidados_stats['clever004_r']['total'],
                            'Noxxon' => $dados_consolidados_stats['noxxon']['total'],
                            'Life' => $dados_consolidados_stats['life']['total']
                        ];
                        foreach($itens as $lbl => $val): 
                            $diff = $val - $dados_consolidados_stats['roleta']['total'];
                            $perc = calcular_diff_percentual($val, $dados_consolidados_stats['roleta']['total']);
                        ?>
                        <li class="flex justify-between items-center">
                            <span class="font-medium text-gray-600"><?= $lbl ?></span>
                            <div class="text-right">
                                <span class="<?= get_cor_diff_abs($diff) ?> block"><?= number_format($diff, 1, ',', '.') ?> km</span>
                                <span class="<?= get_cor_diff_perc($perc) ?> text-xs font-bold">(<?= number_format($perc, 1, ',', '.') ?>%)</span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="bg-white p-6 rounded-lg shadow lg:col-span-2 card-km-top">
                    <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Top 5 Veículos (Maior KM no Período)</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <?php if (!empty($top_veiculos)): foreach ($top_veiculos as $fonte => $lista): ?>
                            <div>
                                <h4 class="font-bold mb-2 text-xs uppercase tracking-wide <?= $fonte=='Roleta'?'fonte-roleta':($fonte=='Noxxon'?'fonte-noxxon':($fonte=='Clever 142'?'fonte-clever-142':'fonte-clever-004')) ?>"><?= $fonte ?></h4>
                                <ul><?php foreach($lista as $v): ?><li><span><?= $v['vehicle'] ?></span> <strong><?= number_format($v['km'], 0, ',', '.') ?></strong></li><?php endforeach; ?></ul>
                            </div>
                        <?php endforeach; else: ?><p class="text-gray-500 text-sm col-span-4">Disponível apenas com filtro "Todos Veículos".</p><?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tabela Comparativa Veículos -->
            <section class="bg-white p-4 rounded-lg shadow border border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Tabela Comparativa Diária por Veículo</h2>
                <div class="table-container">
                    <?php if (empty($dados_veiculos)): ?>
                        <p class="text-center py-4 text-gray-500">Nenhum dado encontrado.</p>
                    <?php else: ?>
                        <?php foreach ($dados_veiculos as $veiculo => $dates): ?>
                            <details class="bg-white rounded border border-gray-200 mb-2">
                                <summary class="p-3 font-semibold text-gray-700 hover:bg-gray-50 flex justify-between items-center cursor-pointer">
                                    <span>Veículo: <?= htmlspecialchars($veiculo) ?></span><span class="text-xs text-gray-500">Clique para expandir</span>
                                </summary>
                                <div class="border-t"><div class="table-container">
                                    <table class="w-full min-w-full divide-y divide-gray-200 text-xs text-center">
                                        <thead><tr class="bg-gray-100">
                                            <th class="p-2">Data</th><th class="bg-yellow-100 p-2">Roleta</th><th class="bg-blue-100 p-2">Noxxon</th><th class="bg-pink-100 p-2">142</th><th class="bg-green-100 p-2">004(R)</th><th class="bg-green-50 p-2">004(P)</th><th class="bg-indigo-100 p-2">Life</th>
                                            <th class="p-2">Noxxon vs Roleta</th><th class="p-2">142 vs Roleta</th><th class="p-2">004 vs Roleta</th><th class="p-2">Life vs Roleta</th><th class="p-2">142 vs 004</th>
                                        </tr></thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <?php ksort($dates); foreach ($dates as $d => $k): 
                                                $rol=$k['roleta']??null; $nox=$k['noxxon']??null; $c142=$k['142']??null; $c004r=$k['004_r']??null; $c004p=$k['004_p']??null; $life=$k['life']??null;
                                                $fmtDiff = function($v1, $v2){ if($v1===null||$v2===null)return'- (-)'; $df=$v1-$v2; $pc=($v2!=0)?($df/$v2)*100:0; return number_format($df,1,',','.').' ('.number_format($pc,1,',','.').'%)'; };
                                            ?>
                                            <tr>
                                                <td class="p-2 font-bold"><?= date('d/m/Y', strtotime($d)) ?></td>
                                                <td class="bg-yellow-50 p-2"><?= $rol!==null?number_format($rol,1,',','.'):'-' ?></td>
                                                <td class="bg-blue-50 p-2"><?= $nox!==null?number_format($nox,1,',','.'):'-' ?></td>
                                                <td class="bg-pink-50 p-2"><?= $c142!==null?number_format($c142,1,',','.'):'-' ?></td>
                                                <td class="bg-green-50 p-2"><?= $c004r!==null?number_format($c004r,1,',','.'):'-' ?></td>
                                                <td class="bg-green-50 p-2"><?= $c004p!==null?number_format($c004p,1,',','.'):'-' ?></td>
                                                <td class="bg-indigo-50 p-2"><?= $life!==null?number_format($life,1,',','.'):'-' ?></td>
                                                <td class="p-2"><?= $fmtDiff($nox,$rol) ?></td>
                                                <td class="p-2"><?= $fmtDiff($c142,$rol) ?></td>
                                                <td class="p-2"><?= $fmtDiff($c004r,$rol) ?></td>
                                                <td class="p-2"><?= $fmtDiff($life,$rol) ?></td>
                                                <td class="p-2"><?= $fmtDiff($c142,$c004r) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div></div>
                            </details>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="text-center mt-8"><a href="index.php" class="bg-gray-500 text-white font-bold py-3 px-8 rounded-lg hover:bg-gray-600 transition-colors">Voltar</a></div>
    </div>

    <script>
        function switchView(v) {
            document.querySelectorAll('[id^="view-"]').forEach(e => e.classList.add('hidden-section'));
            document.querySelectorAll('[id^="btn-"]').forEach(b => b.className="px-6 py-2 rounded-t-lg font-bold transition-colors bg-gray-200 text-gray-600 hover:bg-gray-300");
            document.getElementById('view-'+v).classList.remove('hidden-section');
            document.getElementById('btn-'+v).className="px-6 py-2 rounded-t-lg font-bold transition-colors bg-blue-600 text-white shadow-lg";
        }
        function toggleDetails(rowId) {
            const row = document.getElementById(rowId);
            const parentRow = row.previousElementSibling;
            if (row.classList.contains('hidden-row')) {
                row.classList.remove('hidden-row');
                parentRow.classList.add('expanded-row');
                parentRow.cells[0].innerText = '-';
            } else {
                row.classList.add('hidden-row');
                parentRow.classList.remove('expanded-row');
                parentRow.cells[0].innerText = '+';
            }
        }
        function toggleColumn(checkbox) {
            const colIndex = parseInt(checkbox.dataset.col);
            const show = checkbox.checked;
            const th = document.querySelector(`#tabela-resumo thead tr th:nth-child(${colIndex + 1})`);
            if(th) show ? th.classList.remove('hidden-col') : th.classList.add('hidden-col');
            document.querySelectorAll('#tabela-resumo tbody tr.main-row').forEach(row => {
                const td = row.querySelector(`td:nth-child(${colIndex + 1})`);
                if(td) show ? td.classList.remove('hidden-col') : td.classList.add('hidden-col');
            });
            const footerCell = document.querySelectorAll('#tabela-resumo tfoot tr td')[colIndex - 1];
            if(footerCell) show ? footerCell.classList.remove('hidden-col') : footerCell.classList.add('hidden-col');
        }
        
        const serverData = <?= json_encode($dados_para_js) ?>;
        
        function downloadFile(content, filename) {
            const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", filename);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }
        function numberToCsv(n){return parseFloat(n||0).toFixed(1).replace('.',',');}
        
        function exportSimpleCSV() {
            let csv = "\uFEFF";
            csv += "Data;Planilha;MTRAN (P);MTRAN (O);Ajuste (P);Ajuste (O);004 (P);004 (O);Roleta;Life;142;Noxxon;004 Real (P)\n";
            for (const [date, item] of Object.entries(serverData)) {
                const c = item.cols;
                let row = [
                    date.split('-').reverse().join('/'),
                    numberToCsv(c.prog_planilha), numberToCsv(c.prog_mtran_prod), numberToCsv(c.prog_mtran_ocio),
                    numberToCsv(c.ajuste_mtran_prod), numberToCsv(c.ajuste_mtran_ocio),
                    numberToCsv(c.prog_004_prod), numberToCsv(c.prog_004_ocio),
                    numberToCsv(c.real_roleta), numberToCsv(c.real_life), numberToCsv(c.real_142),
                    numberToCsv(c.real_noxxon), numberToCsv(c.real_004_prod)
                ];
                csv += row.join(";") + "\n";
            }
            downloadFile(csv, 'Relatorio_KM_Tabela.csv');
        }
        
        function exportCompleteCSV() {
            let csv = "\uFEFF";
            csv += "Data;Grupo;Comparativo;Diferenca (KM);Texto Explicativo\n";
            for (const [date, item] of Object.entries(serverData)) {
                const dataFmt = date.split('-').reverse().join('/');
                for (const [group, cards] of Object.entries(item.groups)) {
                    cards.forEach(card => {
                        csv += `${dataFmt};${group};${card.csv_texto.split(' fez')[0]};${card.csv_val};${card.csv_texto}\n`;
                    });
                }
            }
            downloadFile(csv, 'Relatorio_Comparativo_Completo.csv');
        }
    </script>
</body>
</html>
<?php $conexao->close(); ?>