<?php
// =================================================================
//  AJAX Handler para Relatório de Bilhetagem (v3.3 - ATUALIZADO)
//  - INCLUI: dias_semana_receita no retorno JSON
//  - INCLUI: Diferença por tipo de cartão no histórico (Mes/Ano)
//  - Processa Atual, Mês Anterior e Ano Anterior
//  - Calcula Receita Estimada por ano
// =================================================================

header('Content-Type: application/json');
require_once 'config_bilhetagem.php';

// --- RECEBER PARÂMETROS ---
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$linha_selecionada = $_GET['linha'] ?? 'todas';
$dia_semana_filtro = $_GET['dia_semana'] ?? 'todos';

// --- FUNÇÃO DE BUSCA DE DADOS ---
function buscar_dados_periodo($conexao, $dt_ini, $dt_fim, $linha, $dia_sem) {
    global $CUSTO_PASSAGEM;

    $where = ["data_viagem BETWEEN '$dt_ini' AND '$dt_fim'"];
    if ($linha !== 'todas') {
        $where[] = "linha = '{$conexao->real_escape_string($linha)}'";
    }
    
    if ($dia_sem !== 'todos') {
        $mysql_weekday = (int)$dia_sem - 1; 
        $where[] = "WEEKDAY(data_viagem) = $mysql_weekday";
    }

    $where_sql = "WHERE " . implode(' AND ', $where);

    $sql = "
        SELECT 
            data_viagem,
            SUM(total_passageiros) as total,
            SUM(emv) as emv,
            SUM(escolar_100) as esc100,
            SUM(escolar_duplo) as escduplo,
            SUM(escolar) as escolar,
            SUM(comum) as comum,
            SUM(gratuitos) as gratuitos,
            SUM(integracao) as integracao,
            SUM(pagantes) as pagantes,
            SUM(vale_transporte) as vt,
            SUM(contactless) as contactless,
            SUM(bonus) as bonus,
            SUM(funcionario) as funcionario
        FROM relatorios_bilhetagem
        $where_sql
        GROUP BY data_viagem
        ORDER BY data_viagem ASC
    ";

    $result = $conexao->query($sql);
    
    $dados = [
        'total_periodo' => 0,
        'receita_total' => 0,
        'dias_semana_stats' => array_fill(1, 7, 0),
        'dias_semana_receita' => array_fill(1, 7, 0), // Inicializa array de receita
        'tipos_cartao' => [ 
            'Comum' => 0, 'VT' => 0, 'Estudante' => 0, 
            'Gratuitos' => 0, 'Pagantes' => 0, 'Integração' => 0, 
            'EMV' => 0, 'Outros' => 0
        ],
        'timeline' => [],
        'detalhes_diarios' => []
    ];

    while ($row = $result->fetch_assoc()) {
        $total_dia = (float)$row['total'];
        $dt = $row['data_viagem'];
        $custo = get_custo_passagem($dt);
        $receita_dia = $total_dia * $custo;
        
        $dados['total_periodo'] += $total_dia;
        $dados['receita_total'] += $receita_dia;

        // Agrupamento de Tipos
        $estudante = ((float)$row['esc100'] + (float)$row['escduplo'] + (float)$row['escolar']);
        $outros = ((float)$row['contactless'] + (float)$row['bonus'] + (float)$row['funcionario']);

        $dados['tipos_cartao']['EMV'] += (float)$row['emv'];
        $dados['tipos_cartao']['Estudante'] += $estudante;
        $dados['tipos_cartao']['Comum'] += (float)$row['comum'];
        $dados['tipos_cartao']['Gratuitos'] += (float)$row['gratuitos'];
        $dados['tipos_cartao']['Integração'] += (float)$row['integracao'];
        $dados['tipos_cartao']['Pagantes'] += (float)$row['pagantes'];
        $dados['tipos_cartao']['VT'] += (float)$row['vt'];
        $dados['tipos_cartao']['Outros'] += $outros;

        $dia_num = (int)date('N', strtotime($dt));
        $dados['dias_semana_stats'][$dia_num] += $total_dia;
        $dados['dias_semana_receita'][$dia_num] += $receita_dia; // Acumula Receita

        $dados['timeline'][$dt] = $total_dia;

        $dados['detalhes_diarios'][$dt] = [
            'total' => $total_dia,
            'emv' => (float)$row['emv'],
            'estudante' => $estudante,
            'comum' => (float)$row['comum'],
            'gratuitos' => (float)$row['gratuitos'],
            'integracao' => (float)$row['integracao'],
            'pagantes' => (float)$row['pagantes'],
            'vt' => (float)$row['vt'],
            'outros' => $outros
        ];
    }

    return $dados;
}

// --- CALCULAR PERÍODOS ---
$dt_ini_atual = $data_inicio;
$dt_fim_atual = $data_fim;
$dt_ini_mes = date('Y-m-d', strtotime($data_inicio . ' -1 month'));
$dt_fim_mes = date('Y-m-d', strtotime($data_fim . ' -1 month'));
$dt_ini_ano = date('Y-m-d', strtotime($data_inicio . ' -1 year'));
$dt_fim_ano = date('Y-m-d', strtotime($data_fim . ' -1 year'));

// --- BUSCAR DADOS ---
$stats_atual = buscar_dados_periodo($conexao, $dt_ini_atual, $dt_fim_atual, $linha_selecionada, $dia_semana_filtro);
$stats_mes   = buscar_dados_periodo($conexao, $dt_ini_mes, $dt_fim_mes, $linha_selecionada, $dia_semana_filtro);
$stats_ano   = buscar_dados_periodo($conexao, $dt_ini_ano, $dt_fim_ano, $linha_selecionada, $dia_semana_filtro);

// --- PROCESSAR DADOS ---

// 1. KPIs
$dias_no_periodo = count($stats_atual['timeline']);
$media_diaria = $dias_no_periodo > 0 ? $stats_atual['total_periodo'] / $dias_no_periodo : 0;

$pico_val = 0; $pico_data = '-'; $pico_dia_semana = '-';
foreach ($stats_atual['timeline'] as $dt => $val) {
    if ($val > $pico_val) { $pico_val = $val; $pico_data = $dt; $pico_dia_semana = date('l', strtotime($dt)); }
}

// 2. Gráfico e Tabela de Evolução
$labels_grafico = [];
$data_atual = [];
$data_mes = [];
$data_ano = [];

$periodo_range = new DatePeriod(new DateTime($dt_ini_atual), new DateInterval('P1D'), (new DateTime($dt_fim_atual))->modify('+1 day'));

// Prepara valores indexados para comparação (Totais)
$vals_mes = array_values($stats_mes['timeline']);
$vals_ano = array_values($stats_ano['timeline']);

// Prepara valores indexados para comparação (Detalhes por Tipo)
$vals_detalhes_mes = array_values($stats_mes['detalhes_diarios']);
$vals_detalhes_ano = array_values($stats_ano['detalhes_diarios']);

$idx = 0;

foreach ($periodo_range as $date) {
    $dt_iso = $date->format('Y-m-d');
    $labels_grafico[] = $date->format('d/m');
    $data_atual[] = $stats_atual['timeline'][$dt_iso] ?? 0;
    $data_mes[] = $vals_mes[$idx] ?? 0;
    $data_ano[] = $vals_ano[$idx] ?? 0;
    $idx++;
}

// 3. Tabela Detalhada
$tabela_dados = [];
$timeline_atual = $stats_atual['timeline']; 

$idx = 0;
foreach ($timeline_atual as $dt => $val) {
    $timestamp = strtotime($dt);
    $dia_semana_num = (int)date('N', $timestamp); 
    $detalhes = $stats_atual['detalhes_diarios'][$dt] ?? [];
    
    // Lógica de Variação Inteligente (Dia Anterior vs Semana Anterior)
    $prev_val = null;
    $tipo_comparacao = 'anterior';

    if ($dia_semana_num >= 6) { // Fim de semana: compara com semana anterior
        $dt_semana_passada = date('Y-m-d', strtotime($dt . ' -7 days'));
        if (isset($timeline_atual[$dt_semana_passada])) {
            $prev_val = $timeline_atual[$dt_semana_passada];
            $tipo_comparacao = 'semana_anterior';
        }
    } else { // Dias úteis: compara com dia anterior
        $dt_ontem = date('Y-m-d', strtotime($dt . ' -1 day'));
        if (isset($timeline_atual[$dt_ontem])) {
            $prev_val = $timeline_atual[$dt_ontem];
        }
    }

    $variacao = 0;
    if ($prev_val !== null && $prev_val > 0) {
        $variacao = (($val - $prev_val) / $prev_val) * 100;
    } elseif ($prev_val === null) {
        $variacao = null;
    }

    // Dados Comparativos de Totais
    $comp_mes = $vals_mes[$idx] ?? 0;
    $comp_ano = $vals_ano[$idx] ?? 0;

    // --- CÁLCULO DE DIFERENÇAS POR TIPO ---
    $detalhes_mes_item = $vals_detalhes_mes[$idx] ?? [];
    $detalhes_ano_item = $vals_detalhes_ano[$idx] ?? [];
    
    $diff_mes = [];
    $diff_ano = [];
    $keys_tipos = ['comum', 'vt', 'estudante', 'gratuitos', 'pagantes', 'integracao', 'emv', 'outros'];
    
    foreach ($keys_tipos as $k) {
        $val_atual = $detalhes[$k] ?? 0;
        
        // Diferença vs Mês Anterior
        $val_hist_mes = $detalhes_mes_item[$k] ?? 0;
        $diff_mes[$k] = $val_atual - $val_hist_mes;
        
        // Diferença vs Ano Anterior
        $val_hist_ano = $detalhes_ano_item[$k] ?? 0;
        $diff_ano[$k] = $val_atual - $val_hist_ano;
    }

    $tabela_dados[] = [
        'data' => date('d/m/Y', $timestamp),
        'data_iso' => $dt,
        'dia_semana' => date('N', $timestamp),
        'total' => $val,
        'comparacao_base' => $prev_val,
        'tipo_comparacao' => $tipo_comparacao,
        'variacao' => $variacao !== null ? round($variacao, 2) : null,
        'detalhes' => $detalhes,
        'historico' => [
            'mes_anterior' => $comp_mes,
            'ano_anterior' => $comp_ano,
            'diff_tipos_mes' => $diff_mes, // Diferenças detalhadas (Mês)
            'diff_tipos_ano' => $diff_ano  // Diferenças detalhadas (Ano)
        ]
    ];
    $idx++;
}

// --- RETORNO JSON ---
echo json_encode([
    'kpis' => [
        'total' => $stats_atual['total_periodo'],
        'receita' => $stats_atual['receita_total'],
        'media' => $media_diaria,
        'pico_val' => $pico_val,
        'pico_dia' => $pico_dia_semana,
        'dias_semana_stats' => $stats_atual['dias_semana_stats'],
        'dias_semana_receita' => $stats_atual['dias_semana_receita'],
    ],
    'comparativo' => [
        'mes' => [
            'total' => $stats_mes['total_periodo'],
            'receita' => $stats_mes['receita_total'],
            'dias_semana_stats' => $stats_mes['dias_semana_stats'],
            'dias_semana_receita' => $stats_mes['dias_semana_receita'],
            'tipos_cartao' => $stats_mes['tipos_cartao']
        ],
        'ano' => [
            'total' => $stats_ano['total_periodo'],
            'receita' => $stats_ano['receita_total'],
            'dias_semana_stats' => $stats_ano['dias_semana_stats'],
            'dias_semana_receita' => $stats_ano['dias_semana_receita'],
            'tipos_cartao' => $stats_ano['tipos_cartao']
        ]
    ],
    'evolucao' => [
        'labels' => $labels_grafico,
        'atual' => $data_atual,
        'mes' => $data_mes,
        'ano' => $data_ano
    ],
    'distribuicao_tipos' => [
        'atual' => $stats_atual['tipos_cartao'],
        'mes' => $stats_mes['tipos_cartao'],
        'ano' => $stats_ano['tipos_cartao']
    ],
    'tabela_detalhada' => array_reverse($tabela_dados)
]);
$conexao->close();
?>