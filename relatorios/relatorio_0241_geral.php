<?php
// Inclui apenas as configurações e funções necessárias
require 'config_0241_geral.php';


// --- 3. LÓGICA DE FILTROS ---
$aba_selecionada = isset($_GET['aba']) ? $_GET['aba'] : 'geral';
$linha_selecionada = isset($_GET['linha']) ? $_GET['linha'] : 'todas';
$linhas_disponiveis = $conexao->query("SELECT DISTINCT route FROM registros_gerais ORDER BY route ASC")->fetch_all(MYSQLI_ASSOC);
$where_conditions = [];
$titulo_periodo = "Geral";
$titulo_linha = "Todas as Linhas";

$where_clause_for_tabs = '';
if ($linha_selecionada !== 'todas' && !empty($linha_selecionada)) {
    $where_clause_for_tabs = " WHERE route = '" . $conexao->real_escape_string($linha_selecionada) . "'";
}
$abas_disponiveis = [];
$result_abas = $conexao->query("SELECT DISTINCT WEEKDAY(partida_programada) as dia FROM registros_gerais {$where_clause_for_tabs};");
$dias_com_dados = [];
if ($result_abas) { while($row = $result_abas->fetch_assoc()) { $dias_com_dados[] = $row['dia']; } }
if (count(array_intersect($dias_com_dados, [0,1,2,3,4])) > 0) $abas_disponiveis['uteis'] = 'Dias Úteis';
if (in_array(5, $dias_com_dados)) $abas_disponiveis['sabados'] = 'Sábados';
if (in_array(6, $dias_com_dados)) $abas_disponiveis['domingos'] = 'Domingos';

if ($linha_selecionada !== 'todas' && !empty($linha_selecionada)) {
    $where_conditions[] = "route = '" . $conexao->real_escape_string($linha_selecionada) . "'";
    $titulo_linha = "Linha " . htmlspecialchars($linha_selecionada);
}
switch ($aba_selecionada) {
    case 'uteis': $where_conditions[] = "WEEKDAY(partida_programada) BETWEEN 0 AND 4"; $titulo_periodo = "Dias Úteis"; break;
    case 'sabados': $where_conditions[] = "WEEKDAY(partida_programada) = 5"; $titulo_periodo = "Sábados"; break;
    case 'domingos': $where_conditions[] = "WEEKDAY(partida_programada) = 6"; $titulo_periodo = "Domingos"; break;
}
$where_clause = '';
if (!empty($where_conditions)) { $where_clause = " WHERE " . implode(' AND ', $where_conditions); }

$sql_intervalo_datas = "SELECT MIN(DATE(partida_programada)) as data_inicio, MAX(DATE(partida_programada)) as data_fim FROM registros_gerais $where_clause;";
$resultado_intervalo = $conexao->query($sql_intervalo_datas);
$intervalo = $resultado_intervalo->fetch_assoc();
$info_datas = '';
if ($intervalo['data_inicio'] && $intervalo['data_fim']) {
    $data_inicio_fmt = (new DateTime($intervalo['data_inicio']))->format('d/m/Y');
    $data_fim_fmt = (new DateTime($intervalo['data_fim']))->format('d/m/Y');
    $info_datas = ($data_inicio_fmt == $data_fim_fmt) ? " ($data_inicio_fmt)" : " ($data_inicio_fmt a $data_fim_fmt)";
}

// --- 4. LÓGICA DE CÁLCULO ---
$base_classificacao_sql = "
    FROM (
        SELECT *,
            (CASE WHEN chegada_real IS NULL OR partida_real IS NULL THEN 'Sem Dados' WHEN desvio_chegada > '00:15:00' OR desvio_partida > '00:15:00' THEN 'Supressão' ELSE 'Válida' END) as status_icv,
            (CASE WHEN chegada_real IS NULL THEN 'Sem Dados' WHEN desvio_chegada > '00:15:00' THEN 'Supressão' WHEN desvio_chegada >= '00:06:00' THEN 'Atrasado' WHEN desvio_chegada <= '-00:02:00' THEN 'Adiantado' ELSE 'No Horário' END) AS status_chegada,
            (CASE WHEN partida_real IS NULL THEN 'Sem Dados' WHEN desvio_partida > '00:15:00' THEN 'Supressão' WHEN desvio_partida >= '00:06:00' THEN 'Atrasado' WHEN desvio_partida <= '-00:02:00' THEN 'Adiantado' ELSE 'No Horário' END) AS status_partida,
            GREATEST(IFNULL(desvio_chegada, '00:00:00'), IFNULL(desvio_partida, '00:00:00')) as pior_atraso_viagem,
            LEAST(IFNULL(desvio_chegada, '00:00:00'), IFNULL(desvio_partida, '00:00:00')) as pior_adiantamento_viagem,
            (CASE WHEN HOUR(partida_programada) BETWEEN 6 AND 8 THEN 'Pico Manhã (06:00-08:59)' WHEN HOUR(partida_programada) BETWEEN 9 AND 10 THEN 'Entrepico Manhã (09:00-10:59)' WHEN HOUR(partida_programada) BETWEEN 11 AND 13 THEN 'Horário Escolar (11:00-13:59)' WHEN HOUR(partida_programada) BETWEEN 14 AND 15 THEN 'Início Tarde (14:00-15:59)' WHEN HOUR(partida_programada) BETWEEN 16 AND 19 THEN 'Pico Tarde (16:00-19:00)' WHEN HOUR(partida_programada) >= 19 THEN 'Fim Noite (19:01-23:59)' ELSE 'Madrugada' END) AS faixa_horaria
        FROM registros_gerais {$where_clause}
    ) AS viagens_classificadas
";

// --- 5. EXECUÇÃO DAS QUERIES ---
$sql_indices = "SELECT COUNT(*) as total_registros, COUNT(CASE WHEN status_icv = 'Válida' THEN 1 END) as viagens_cumpridas, COUNT(CASE WHEN status_chegada = 'No Horário' THEN 1 END) as chegadas_no_horario, COUNT(CASE WHEN status_partida = 'No Horário' THEN 1 END) as partidas_no_horario, COUNT(desvio_chegada IS NOT NULL) as total_chegadas_validas, COUNT(desvio_partida IS NOT NULL) as total_partidas_validas " . $base_classificacao_sql;
$stats_indices = $conexao->query($sql_indices)->fetch_assoc();
$icv = (($stats_indices['total_registros'] ?? 0) > 0) ? round(($stats_indices['viagens_cumpridas'] / $stats_indices['total_registros']) * 100, 1) : 0;
$ipv_chegada = (($stats_indices['total_chegadas_validas'] ?? 0) > 0) ? round(($stats_indices['chegadas_no_horario'] / $stats_indices['total_chegadas_validas']) * 100, 1) : 0;
$ipv_partida = (($stats_indices['total_partidas_validas'] ?? 0) > 0) ? round(($stats_indices['partidas_no_horario'] / $stats_indices['total_partidas_validas']) * 100, 1) : 0;

// === CHEGADAS ===
$where_chegada = " WHERE status_chegada != 'N/A'"; // Mantém o filtro para não contar N/A como um status válido
$sql_status_chegada = "SELECT 
    COUNT(status_chegada) as total_registros, 
    COUNT(CASE WHEN status_chegada = 'No Horário' THEN 1 END) as total_no_horario, 
    COUNT(CASE WHEN status_chegada = 'Atrasado' THEN 1 END) as total_atrasado, 
    COUNT(CASE WHEN status_chegada = 'Adiantado' THEN 1 END) as total_adiantado, 
    COUNT(CASE WHEN status_chegada = 'Supressão' THEN 1 END) as total_supressao,
    COUNT(CASE WHEN status_chegada = 'Sem Dados' THEN 1 END) as total_sem_dados -- ESTA LINHA ESTAVA A FALTAR
" . $base_classificacao_sql . $where_chegada;
$stats_chegada = $conexao->query($sql_status_chegada)->fetch_assoc();
$sql_linhas_chegada = "SELECT route, COUNT(*) as total_viagens, COUNT(CASE WHEN status_chegada = 'No Horário' THEN 1 END) as total_no_horario, COUNT(CASE WHEN status_chegada = 'Atrasado' THEN 1 END) as total_atrasado, COUNT(CASE WHEN status_chegada = 'Adiantado' THEN 1 END) as total_adiantado, COUNT(CASE WHEN status_chegada = 'Supressão' THEN 1 END) as total_supressao " . $base_classificacao_sql . $where_chegada . " GROUP BY route ORDER BY (COUNT(CASE WHEN status_chegada = 'No Horário' THEN 1 END) / COUNT(*)) DESC, COUNT(CASE WHEN status_chegada = 'Atrasado' THEN 1 END) ASC;";
$stats_por_linha_chegada = $conexao->query($sql_linhas_chegada)->fetch_all(MYSQLI_ASSOC);
$sql_sentido_chegada = "SELECT direction, COUNT(*) as total_viagens, COUNT(CASE WHEN status_chegada = 'No Horário' THEN 1 END) as total_no_horario, COUNT(CASE WHEN status_chegada = 'Atrasado' THEN 1 END) as total_atrasado, COUNT(CASE WHEN status_chegada = 'Adiantado' THEN 1 END) as total_adiantado, SEC_TO_TIME(AVG(TIME_TO_SEC(desvio_chegada))) as desvio_medio " . $base_classificacao_sql . " WHERE status_chegada != 'N/A' AND direction IN ('IDA', 'VOLTA') GROUP BY direction;";
$stats_sentido_chegada = $conexao->query($sql_sentido_chegada)->fetch_all(MYSQLI_ASSOC);
$sql_faixa_horaria_chegada = "SELECT faixa_horaria, COUNT(*) as total_viagens, COUNT(CASE WHEN status_chegada = 'No Horário' THEN 1 END) as total_no_horario, COUNT(CASE WHEN status_chegada = 'Atrasado' THEN 1 END) as total_atrasado, COUNT(CASE WHEN status_chegada = 'Adiantado' THEN 1 END) as total_adiantado, SEC_TO_TIME(AVG(TIME_TO_SEC(desvio_chegada))) as desvio_medio " . $base_classificacao_sql . " WHERE status_chegada != 'N/A' AND faixa_horaria != 'Madrugada' GROUP BY faixa_horaria ORDER BY FIELD(faixa_horaria, 'Pico Manhã (06:00-08:59)', 'Entrepico Manhã (09:00-10:59)', 'Horário Escolar (11:00-13:59)', 'Início Tarde (14:00-15:59)', 'Pico Tarde (16:00-19:00)', 'Fim Noite (19:01-23:59)');";
$stats_faixa_horaria_chegada = $conexao->query($sql_faixa_horaria_chegada)->fetch_all(MYSQLI_ASSOC);
$sql_motoristas_chegada = "SELECT operator, COUNT(*) as total_viagens, COUNT(CASE WHEN status_chegada = 'No Horário' THEN 1 END) as total_no_horario, COUNT(CASE WHEN status_chegada = 'Atrasado' THEN 1 END) as total_atrasado, COUNT(CASE WHEN status_chegada = 'Adiantado' THEN 1 END) as total_adiantado, COUNT(CASE WHEN status_chegada = 'Supressão' THEN 1 END) as total_supressao, SEC_TO_TIME(AVG(TIME_TO_SEC(desvio_chegada))) as desvio_medio, SEC_TO_TIME(STDDEV_SAMP(TIME_TO_SEC(desvio_chegada))) as consistencia, MAX(pior_atraso_viagem) as pior_atraso, MIN(pior_adiantamento_viagem) as pior_adiantamento " . $base_classificacao_sql . " WHERE status_chegada != 'N/A' AND operator != 'No Badge Provided' AND operator IS NOT NULL AND operator != '' GROUP BY operator ORDER BY total_viagens DESC;";
$stats_motoristas_chegada = $conexao->query($sql_motoristas_chegada)->fetch_all(MYSQLI_ASSOC);
$sql_veiculos_motorista_chegada = "SELECT operator, route, vehicle, COUNT(*) as total_viagens, SEC_TO_TIME(AVG(TIME_TO_SEC(desvio_chegada))) as desvio_medio " . $base_classificacao_sql . " WHERE status_chegada != 'N/A' AND operator != 'No Badge Provided' AND vehicle IS NOT NULL AND TRIM(vehicle) != '' GROUP BY operator, route, vehicle ORDER BY operator, total_viagens DESC;";
$stats_veiculos_motorista_chegada = $conexao->query($sql_veiculos_motorista_chegada)->fetch_all(MYSQLI_ASSOC);
$motoristas_veiculos_chegada = [];
foreach ($stats_veiculos_motorista_chegada as $registo) { $motoristas_veiculos_chegada[$registo['operator']][] = $registo; }
$sql_top_pontos_atraso_chegada = "SELECT route, nome_parada, COUNT(*) as quantidade " . $base_classificacao_sql . " WHERE status_chegada = 'Atrasado' GROUP BY route, nome_parada ORDER BY quantidade DESC LIMIT 50;";
$top_pontos_atraso_chegada = $conexao->query($sql_top_pontos_atraso_chegada)->fetch_all(MYSQLI_ASSOC);
$sql_top_pontos_adiantado_chegada = "SELECT route, nome_parada, COUNT(*) as quantidade " . $base_classificacao_sql . " WHERE status_chegada = 'Adiantado' GROUP BY route, nome_parada ORDER BY quantidade DESC LIMIT 50;";
$top_pontos_adiantado_chegada = $conexao->query($sql_top_pontos_adiantado_chegada)->fetch_all(MYSQLI_ASSOC);
// --- Base de condições para o Top 50 ---
// Usamos a mesma base de filtros das abas (Dias Úteis, etc.)
$base_conditions = $where_conditions;

// --- Top 50 Maiores Atrasos de CHEGADA ---
$conditions_atraso_chegada = $base_conditions;
$conditions_atraso_chegada[] = "desvio_chegada >= '00:06:00'";
$conditions_atraso_chegada[] = "desvio_chegada < '00:15:00'";
$where_atraso_chegada = "WHERE " . implode(' AND ', $conditions_atraso_chegada);

$sql_maiores_atrasos_chegada = "
    SELECT route, nome_parada, partida_programada, chegada_real, desvio_chegada
    FROM registros_gerais
    {$where_atraso_chegada}
    ORDER BY desvio_chegada DESC
    LIMIT 50;
";
$top_maiores_atrasos_chegada = $conexao->query($sql_maiores_atrasos_chegada)->fetch_all(MYSQLI_ASSOC);


// --- Top 50 Maiores Adiantamentos de CHEGADA ---
$conditions_adiantamento_chegada = $base_conditions;
$conditions_adiantamento_chegada[] = "desvio_chegada <= '-00:02:00'";
$where_adiantamento_chegada = "WHERE " . implode(' AND ', $conditions_adiantamento_chegada);

$sql_maiores_adiantamentos_chegada = "
    SELECT route, nome_parada, partida_programada, chegada_real, desvio_chegada
    FROM registros_gerais
    {$where_adiantamento_chegada}
    ORDER BY desvio_chegada ASC
    LIMIT 50;
";
$top_maiores_adiantamentos_chegada = $conexao->query($sql_maiores_adiantamentos_chegada)->fetch_all(MYSQLI_ASSOC);

// === PARTIDAS ===
$where_partida = " WHERE status_partida != 'N/A'";
$sql_status_partida = "SELECT 
    COUNT(status_partida) as total_registros, 
    COUNT(CASE WHEN status_partida = 'No Horário' THEN 1 END) as total_no_horario, 
    COUNT(CASE WHEN status_partida = 'Atrasado' THEN 1 END) as total_atrasado, 
    COUNT(CASE WHEN status_partida = 'Adiantado' THEN 1 END) as total_adiantado, 
    COUNT(CASE WHEN status_partida = 'Supressão' THEN 1 END) as total_supressao,
    COUNT(CASE WHEN status_partida = 'Sem Dados' THEN 1 END) as total_sem_dados -- ESTA LINHA ESTAVA A FALTAR
" . $base_classificacao_sql . $where_partida;
$stats_partida = $conexao->query($sql_status_partida)->fetch_assoc();
$sql_linhas_partida = "SELECT route, COUNT(*) as total_viagens, COUNT(CASE WHEN status_partida = 'No Horário' THEN 1 END) as total_no_horario, COUNT(CASE WHEN status_partida = 'Atrasado' THEN 1 END) as total_atrasado, COUNT(CASE WHEN status_partida = 'Adiantado' THEN 1 END) as total_adiantado, COUNT(CASE WHEN status_partida = 'Supressão' THEN 1 END) as total_supressao " . $base_classificacao_sql . $where_partida . " GROUP BY route ORDER BY (COUNT(CASE WHEN status_partida = 'No Horário' THEN 1 END) / COUNT(*)) DESC, COUNT(CASE WHEN status_partida = 'Atrasado' THEN 1 END) ASC;";
$stats_por_linha_partida = $conexao->query($sql_linhas_partida)->fetch_all(MYSQLI_ASSOC);
$sql_sentido_partida = "SELECT direction, COUNT(*) as total_viagens, COUNT(CASE WHEN status_partida = 'No Horário' THEN 1 END) as total_no_horario, COUNT(CASE WHEN status_partida = 'Atrasado' THEN 1 END) as total_atrasado, COUNT(CASE WHEN status_partida = 'Adiantado' THEN 1 END) as total_adiantado, SEC_TO_TIME(AVG(TIME_TO_SEC(desvio_partida))) as desvio_medio " . $base_classificacao_sql . " WHERE status_partida != 'N/A' AND direction IN ('IDA', 'VOLTA') GROUP BY direction;";
$stats_sentido_partida = $conexao->query($sql_sentido_partida)->fetch_all(MYSQLI_ASSOC);
$sql_faixa_horaria_partida = "SELECT faixa_horaria, COUNT(*) as total_viagens, COUNT(CASE WHEN status_partida = 'No Horário' THEN 1 END) as total_no_horario, COUNT(CASE WHEN status_partida = 'Atrasado' THEN 1 END) as total_atrasado, COUNT(CASE WHEN status_partida = 'Adiantado' THEN 1 END) as total_adiantado, SEC_TO_TIME(AVG(TIME_TO_SEC(desvio_partida))) as desvio_medio " . $base_classificacao_sql . " WHERE status_partida != 'N/A' AND faixa_horaria != 'Madrugada' GROUP BY faixa_horaria ORDER BY FIELD(faixa_horaria, 'Pico Manhã (06:00-08:59)', 'Entrepico Manhã (09:00-10:59)', 'Horário Escolar (11:00-13:59)', 'Início Tarde (14:00-15:59)', 'Pico Tarde (16:00-19:00)', 'Fim Noite (19:01-23:59)');";
$stats_faixa_horaria_partida = $conexao->query($sql_faixa_horaria_partida)->fetch_all(MYSQLI_ASSOC);
$sql_motoristas_partida = "SELECT operator, COUNT(*) as total_viagens, COUNT(CASE WHEN status_partida = 'No Horário' THEN 1 END) as total_no_horario, COUNT(CASE WHEN status_partida = 'Atrasado' THEN 1 END) as total_atrasado, COUNT(CASE WHEN status_partida = 'Adiantado' THEN 1 END) as total_adiantado, COUNT(CASE WHEN status_partida = 'Supressão' THEN 1 END) as total_supressao, SEC_TO_TIME(AVG(TIME_TO_SEC(desvio_partida))) as desvio_medio, SEC_TO_TIME(STDDEV_SAMP(TIME_TO_SEC(desvio_partida))) as consistencia, MAX(pior_atraso_viagem) as pior_atraso, MIN(pior_adiantamento_viagem) as pior_adiantamento " . $base_classificacao_sql . " WHERE status_partida != 'N/A' AND operator != 'No Badge Provided' AND operator IS NOT NULL AND operator != '' GROUP BY operator ORDER BY total_viagens DESC;";
$stats_motoristas_partida = $conexao->query($sql_motoristas_partida)->fetch_all(MYSQLI_ASSOC);
$sql_veiculos_motorista_partida = "SELECT operator, route, vehicle, COUNT(*) as total_viagens, SEC_TO_TIME(AVG(TIME_TO_SEC(desvio_partida))) as desvio_medio " . $base_classificacao_sql . " WHERE status_partida != 'N/A' AND operator != 'No Badge Provided' AND vehicle IS NOT NULL AND TRIM(vehicle) != '' GROUP BY operator, route, vehicle ORDER BY operator, total_viagens DESC;";
$stats_veiculos_motorista_partida = $conexao->query($sql_veiculos_motorista_partida)->fetch_all(MYSQLI_ASSOC);
$motoristas_veiculos_partida = [];
foreach ($stats_veiculos_motorista_partida as $registo) { $motoristas_veiculos_partida[$registo['operator']][] = $registo; }
$sql_top_pontos_atraso_partida = "SELECT route, nome_parada, COUNT(*) as quantidade " . $base_classificacao_sql . " WHERE status_partida = 'Atrasado' GROUP BY route, nome_parada ORDER BY quantidade DESC LIMIT 50;";
$top_pontos_atraso_partida = $conexao->query($sql_top_pontos_atraso_partida)->fetch_all(MYSQLI_ASSOC);
$sql_top_pontos_adiantado_partida = "SELECT route, nome_parada, COUNT(*) as quantidade " . $base_classificacao_sql . " WHERE status_partida = 'Adiantado' GROUP BY route, nome_parada ORDER BY quantidade DESC LIMIT 50;";
$top_pontos_adiantado_partida = $conexao->query($sql_top_pontos_adiantado_partida)->fetch_all(MYSQLI_ASSOC);
// --- Top 50 Maiores Atrasos de PARTIDA ---
$conditions_atraso_partida = $base_conditions;
$conditions_atraso_partida[] = "desvio_partida >= '00:06:00'";
$conditions_atraso_partida[] = "desvio_partida < '00:15:00'";
$where_atraso_partida = "WHERE " . implode(' AND ', $conditions_atraso_partida);

$sql_maiores_atrasos_partida = "
    SELECT route, nome_parada, partida_programada, partida_real, desvio_partida
    FROM registros_gerais
    {$where_atraso_partida}
    ORDER BY desvio_partida DESC
    LIMIT 50;
";
$top_maiores_atrasos_partida = $conexao->query($sql_maiores_atrasos_partida)->fetch_all(MYSQLI_ASSOC);


// --- Top 50 Maiores Adiantamentos de PARTIDA ---
$conditions_adiantamento_partida = $base_conditions;
$conditions_adiantamento_partida[] = "desvio_partida <= '-00:02:00'";
$where_adiantamento_partida = "WHERE " . implode(' AND ', $conditions_adiantamento_partida);

$sql_maiores_adiantamentos_partida = "
    SELECT route, nome_parada, partida_programada, partida_real, desvio_partida
    FROM registros_gerais
    {$where_adiantamento_partida}
    ORDER BY desvio_partida ASC
    LIMIT 50;
";
$top_maiores_adiantamentos_partida = $conexao->query($sql_maiores_adiantamentos_partida)->fetch_all(MYSQLI_ASSOC);

// --- 6. PREPARAÇÃO DOS DADOS PARA OS GRÁFICOS ---
$chart_data_chegada = [ 'labels' => ['No Horário', 'Atrasado', 'Adiantado', 'Supressão'], 'counts' => array_map('intval', [$stats_chegada['total_no_horario'] ?? 0, $stats_chegada['total_atrasado'] ?? 0, $stats_chegada['total_adiantado'] ?? 0, $stats_chegada['total_supressao'] ?? 0]), 'colors' => ['#22c55e', '#f59e0b', '#ef4444', '#8b5cf6'] ];
$chart_data_partida = [ 'labels' => ['No Horário', 'Atrasado', 'Adiantado', 'Supressão'], 'counts' => array_map('intval', [$stats_partida['total_no_horario'] ?? 0, $stats_partida['total_atrasado'] ?? 0, $stats_partida['total_adiantado'] ?? 0, $stats_partida['total_supressao'] ?? 0]), 'colors' => ['#22c55e', '#f59e0b', '#ef4444', '#8b5cf6'] ];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Geral de Desempenho</title>
    <script src="tailwindcss-3.4.17.js"></script>
    <script src="chart.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        @import url('inter.css');
        .card { background-color: white; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); }
        .tab-button { display: inline-block; padding: 0.5rem 1rem; cursor: pointer; border-bottom: 4px solid transparent; transition: all 0.3s ease; white-space: nowrap; text-decoration: none; color: #374151; }
        .tab-button.active { border-color: #3b82f6; color: #3b82f6; font-weight: 600; }
        .text-adiantado, .bg-adiantado-dot { color: #ef4444; } .bg-adiantado-dot { background-color: #ef4444; }
        .text-atrasado, .bg-atrasado-dot { color: #f59e0b; } .bg-atrasado-dot { background-color: #f59e0b; }
        .text-no-horario, .bg-no-horario-dot { color: #22c55e; } .bg-no-horario-dot { background-color: #22c55e; }
        .text-supressao, .bg-supressao-dot { color: #8b5cf6; } .bg-supressao-dot { background-color: #8b5cf6; }
        .text-sem-dados, .bg-sem-dados-dot { color: #6b7280; } .bg-sem-dados-dot { background-color: #6b7280; }
        th, td { padding: 0.75rem; text-align: left; vertical-align: middle; white-space: nowrap;}
        thead th { background-color: #f9fafb; font-weight: 600; position: sticky; top: 0; z-index: 10;}
        tbody tr:nth-child(even) { background-color: #f9fafb; }
        .scrolling-container { max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 0.5rem; }
		
    </style>
</head>
<body class="p-4 md:p-8 bg-gray-100">

    <header class="mb-10">
        <h1 class="text-4xl font-bold text-gray-800">Relatório de Desempenho Geral</h1>
        <p class="text-xl text-gray-600"><?php echo htmlspecialchars($titulo_linha); ?> | Período: <?php echo htmlspecialchars($titulo_periodo); ?><span class="text-base font-normal"><?php echo htmlspecialchars($info_datas); ?></span></p>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <form method="GET" action="relatorio_0241_geral.php" class="card bg-white p-4 mb-8 flex flex-wrap items-center gap-4 rounded-lg shadow">
        <div>
            <label for="linha" class="text-sm font-medium text-gray-700">Filtrar por Linha:</label>
            <select name="linha" id="linha" class="mt-1 block w-full md:w-auto pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                <option value="todas">-- Todas as Linhas --</option>
                <?php foreach ($linhas_disponiveis as $linha): ?>
                    <option value="<?php echo htmlspecialchars($linha['route']); ?>" <?php echo ($linha_selecionada == $linha['route']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($linha['route']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="hidden" name="aba" value="<?php echo $aba_selecionada; ?>">
        <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors duration-300">Aplicar Filtro</button>
    </form>

    <div class="card p-6 bg-white rounded-lg shadow">
            <h2 class="text-xl font-semibold text-gray-900 border-b pb-2 mb-4">Índices de Desempenho</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <div class="flex justify-between items-baseline"><h3 class="text-lg font-semibold text-gray-700">1. Cumprimento (ICV)</h3><span class="text-2xl font-bold text-blue-600"><?php echo $icv; ?>%</span></div>
                    <p class="text-sm text-gray-500 mt-1">Viagens realizadas (sem Supressão ou Sem Dados).</p>
                </div>
                <div class="md:border-l md:pl-8">
                    <div class="flex justify-between items-baseline"><h3 class="text-lg font-semibold text-gray-700">2. IPV Chegada</h3><span class="text-2xl font-bold text-green-600"><?php echo $ipv_chegada; ?>%</span></div>
                    <p class="text-sm text-gray-500 mt-1">Das chegadas válidas, % que operou no horário.</p>
                </div>
                <div class="md:border-l md:pl-8">
                    <div class="flex justify-between items-baseline"><h3 class="text-lg font-semibold text-gray-700">3. IPV Partida</h3><span class="text-2xl font-bold text-green-600"><?php echo $ipv_partida; ?>%</span></div>
                    <p class="text-sm text-gray-500 mt-1">Das partidas válidas, % que operou no horário.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div id="tabs" class="mb-8">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex" aria-label="Tabs">
                <a href="?linha=<?php echo $linha_selecionada; ?>&aba=geral" class="tab-button <?php echo ($aba_selecionada == 'geral') ? 'active' : ''; ?>">Geral</a>
                <?php foreach ($abas_disponiveis as $key => $value): ?>
                    <a href="?linha=<?php echo $linha_selecionada; ?>&aba=<?php echo $key; ?>" class="tab-button <?php echo ($aba_selecionada == $key) ? 'active' : ''; ?>"><?php echo $value; ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>
    
    <main class="space-y-8">

        <div class="pt-4 space-y-8">
             <h2 class="text-3xl font-bold text-center text-gray-800 border-t-2 border-b-2 py-4 border-gray-300 bg-white shadow-md">Análise de Pontualidade de CHEGADAS</h2>
            
            <section class="card p-6">
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">Distribuição de Status (Chegada)</h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                    <div class="space-y-4">
                        <?php $divisor_perc = ($stats_chegada['total_registros'] ?? 0) > 0 ? $stats_chegada['total_registros'] : 1; ?>
                        <div class="flex items-center"><span class="w-4 h-4 rounded-full bg-no-horario-dot mr-3"></span><div class="flex-grow"><span class="font-semibold text-no-horario">No Horário</span></div><div class="text-right"><span class="font-bold"><?php echo $stats_chegada['total_no_horario']; ?></span><span class="text-gray-500 text-sm ml-2">/ <?php echo round(($stats_chegada['total_no_horario'] / $divisor_perc) * 100, 1); ?>%</span></div></div>
                        <div class="flex items-center"><span class="w-4 h-4 rounded-full bg-atrasado-dot mr-3"></span><div class="flex-grow"><span class="font-semibold text-atrasado">Atrasado</span></div><div class="text-right"><span class="font-bold"><?php echo $stats_chegada['total_atrasado']; ?></span><span class="text-gray-500 text-sm ml-2">/ <?php echo round(($stats_chegada['total_atrasado'] / $divisor_perc) * 100, 1); ?>%</span></div></div>
                        <div class="flex items-center"><span class="w-4 h-4 rounded-full bg-adiantado-dot mr-3"></span><div class="flex-grow"><span class="font-semibold text-adiantado">Adiantado</span></div><div class="text-right"><span class="font-bold"><?php echo $stats_chegada['total_adiantado']; ?></span><span class="text-gray-500 text-sm ml-2">/ <?php echo round(($stats_chegada['total_adiantado'] / $divisor_perc) * 100, 1); ?>%</span></div></div>
                        <div class="flex items-center"><span class="w-4 h-4 rounded-full bg-supressao-dot mr-3"></span><div class="flex-grow"><span class="font-semibold text-supressao">Supressão</span></div><div class="text-right"><span class="font-bold"><?php echo $stats_chegada['total_supressao']; ?></span><span class="text-gray-500 text-sm ml-2">/ <?php echo round(($stats_chegada['total_supressao'] / $divisor_perc) * 100, 1); ?>%</span></div></div>
                        <div class="flex items-center"><span class="w-4 h-4 rounded-full bg-sem-dados-dot mr-3"></span><div class="flex-grow"><span class="font-semibold text-sem-dados">Sem Dados</span></div><div class="text-right"><span class="font-bold"><?php echo $stats_chegada['total_sem_dados']; ?></span><span class="text-gray-500 text-sm ml-2">/ <?php echo round(($stats_chegada['total_sem_dados'] / $divisor_perc) * 100, 1); ?>%</span></div></div>
                        <div class="border-t pt-4 mt-4"><div class="flex items-center text-lg"><div class="flex-grow"><span class="font-bold">Total de Chegadas Analisadas</span></div><div class="text-right"><span class="font-bold"><?php echo $stats_chegada['total_registros']; ?></span></div></div></div>
                    </div>
                    <div class="h-64 lg:h-80"><canvas id="statusChartChegada"></canvas></div>
                </div>
            </section>
            
            <?php if ($linha_selecionada === 'todas' && !empty($stats_por_linha_chegada)): ?>
<div class="bg-white p-4 rounded-lg shadow-md mb-6 scrolling-container">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-bold text-gray-700">Análise por Linha - Chegadas</h3>
        <button id="reset-filtros-linha" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-300">
            Limpar Filtros
        </button>
    </div>

    <table class="w-full bg-white" id="tabela-analise-linha">
        <thead>
            <tr class="bg-gray-100">
                <th class="py-2 px-4 border-b cursor-pointer" data-coluna="route">Linha <span></span></th>
                <th class="py-2 px-4 border-b cursor-pointer text-center text-no-horario" data-coluna="no_horario_percent">Nº / % No Horário <span></span></th>
                <th class="py-2 px-4 border-b cursor-pointer text-center text-atrasado" data-coluna="atrasado_percent">Nº / % Atrasado <span></span></th>
                <th class="py-2 px-4 border-b cursor-pointer text-center text-adiantado" data-coluna="adiantado_percent">Nº / % Adiantado <span></span></th>
                <th class="py-2 px-4 border-b cursor-pointer text-center text-supressao" data-coluna="supressao_percent">Nº / % Supressão <span></span></th>
            </tr>
            <tr class="bg-gray-50">
                <td class="p-2 border-b"><input type="text" placeholder="Filtrar por linha..." class="w-full p-1 border rounded" data-coluna="route"></td>
                <td class="p-2 border-b"></td>
                <td class="p-2 border-b"></td>
                <td class="p-2 border-b"></td>
                <td class="p-2 border-b"></td>
            </tr>
        </thead>
        <tbody id="corpo-tabela-analise-linha">
            <?php foreach ($stats_por_linha_chegada as $linha_data): ?>
                <tr>
                    <td class="py-2 px-4 border-b"><strong class="font-mono"><?php echo htmlspecialchars($linha_data['route']); ?></strong></td>
                    <?php $divisor = $linha_data['total_viagens'] > 0 ? $linha_data['total_viagens'] : 1; ?>
                    <td class="py-2 px-4 border-b text-center text-no-horario"><?php echo $linha_data['total_no_horario']; ?> <span class="text-xs text-gray-500">/ <?php echo round(($linha_data['total_no_horario'] / $divisor) * 100, 1); ?>%</span></td>
                    <td class="py-2 px-4 border-b text-center text-atrasado"><?php echo $linha_data['total_atrasado']; ?> <span class="text-xs text-gray-500">/ <?php echo round(($linha_data['total_atrasado'] / $divisor) * 100, 1); ?>%</span></td>
                    <td class="py-2 px-4 border-b text-center text-adiantado"><?php echo $linha_data['total_adiantado']; ?> <span class="text-xs text-gray-500">/ <?php echo round(($linha_data['total_adiantado'] / $divisor) * 100, 1); ?>%</span></td>
                    <td class="py-2 px-4 border-b text-center text-supressao"><?php echo $linha_data['total_supressao']; ?> <span class="text-xs text-gray-500">/ <?php echo round(($linha_data['total_supressao'] / $divisor) * 100, 1); ?>%</span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
            <div class="card p-6">
                <h3 class="text-xl font-semibold text-gray-700 mb-4">Desempenho por Sentido</h3>
                <div class="scrolling-container">
                    <table class="w-full text-sm">
                        <thead><tr><th>Sentido</th><th class="text-center text-no-horario">Nº / % No Horário</th><th class="text-center text-atrasado">Nº / % Atrasado</th><th class="text-center text-adiantado">Nº / % Adiantado</th><th class="text-center">Desvio Médio</th></tr></thead>
                        <tbody>
                            <?php foreach ($stats_sentido_chegada as $linha_chegada): $divisor_chegada = $linha_chegada['total_viagens'] > 0 ? $linha_chegada['total_viagens'] : 1; ?>
                                <tr>
                                    <td><strong><?php echo $linha_chegada['direction']; ?></strong></td>
                                    <td class="text-center"><?php echo $linha_chegada['total_no_horario']; ?> <span class="text-xs text-gray-500">/ <?php echo round($linha_chegada['total_no_horario'] / $divisor_chegada * 100, 1); ?>%</span></td>
                                    <td class="text-center"><?php echo $linha_chegada['total_atrasado']; ?> <span class="text-xs text-gray-500">/ <?php echo round($linha_chegada['total_atrasado'] / $divisor_chegada * 100, 1); ?>%</span></td>
                                    <td class="text-center"><?php echo $linha_chegada['total_adiantado']; ?> <span class="text-xs text-gray-500">/ <?php echo round($linha_chegada['total_adiantado'] / $divisor_chegada * 100, 1); ?>%</span></td>
                                    <td class="text-center font-semibold <?php echo getCorDesvio($linha_chegada['desvio_medio']); ?>"><?php echo formatarTempoSQL($linha_chegada['desvio_medio']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-6">
                <h3 class="text-xl font-semibold text-gray-700 mb-4">Desvio Médio por Faixa Horária</h3>
                <div class="scrolling-container">
                    <table class="w-full text-sm">
                        <thead><tr><th>Faixa Horária</th><th class="text-center text-no-horario">Nº / % No Horário</th><th class="text-center text-atrasado">Nº / % Atrasado</th><th class="text-center text-adiantado">Nº / % Adiantado</th><th class="text-center">Desvio Médio</th></tr></thead>
                        <tbody>
                            <?php foreach ($stats_faixa_horaria_chegada as $linha): $divisor = $linha['total_viagens'] > 0 ? $linha['total_viagens'] : 1; ?>
                                <tr>
                                    <td><?php echo $linha['faixa_horaria']; ?></td>
                                    <td class="text-center"><?php echo $linha['total_no_horario']; ?> <span class="text-xs text-gray-500">/ <?php echo round($linha['total_no_horario'] / $divisor * 100, 1); ?>%</span></td>
                                    <td class="text-center"><?php echo $linha['total_atrasado']; ?> <span class="text-xs text-gray-500">/ <?php echo round($linha['total_atrasado'] / $divisor * 100, 1); ?>%</span></td>
                                    <td class="text-center"><?php echo $linha['total_adiantado']; ?> <span class="text-xs text-gray-500">/ <?php echo round($linha['total_adiantado'] / $divisor * 100, 1); ?>%</span></td>
                                    <td class="text-center font-semibold <?php echo getCorDesvio($linha['desvio_medio']); ?>"><?php echo formatarTempoSQL($linha['desvio_medio']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-6 space-y-8">
                <h2 class="text-2xl font-semibold text-gray-700">Painel de Desempenho por Motorista</h2>
                <div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">1. Resumo Quantitativo</h3>
                    <div class="scrolling-container">
                        <table class="w-full text-sm">
                            <thead><tr><th>Motorista</th><th class="text-center text-no-horario">Nº / % No Horário</th><th class="text-center text-atrasado">Nº / % Atrasado</th><th class="text-center text-adiantado">Nº / % Adiantado</th><th class="text-center text-supressao">Nº / % Supressão</th></tr></thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($stats_motoristas_chegada as $motorista): $divisor = $motorista['total_viagens'] > 0 ? $motorista['total_viagens'] : 1; ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($motorista['operator']); ?></strong></td>
                                        <td class="text-center"><?php echo $motorista['total_no_horario']; ?> <span class="text-xs text-gray-500">/ <?php echo round($motorista['total_no_horario'] / $divisor * 100, 1); ?>%</span></td>
                                        <td class="text-center"><?php echo $motorista['total_atrasado']; ?> <span class="text-xs text-gray-500">/ <?php echo round($motorista['total_atrasado'] / $divisor * 100, 1); ?>%</span></td>
                                        <td class="text-center"><?php echo $motorista['total_adiantado']; ?> <span class="text-xs text-gray-500">/ <?php echo round($motorista['total_adiantado'] / $divisor * 100, 1); ?>%</span></td>
                                        <td class="text-center"><?php echo $motorista['total_supressao']; ?> <span class="text-xs text-gray-500">/ <?php echo round($motorista['total_supressao'] / $divisor * 100, 1); ?>%</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">2. Análise de Desvio e Consistência</h3>
                    <div class="scrolling-container">
                        <table class="w-full text-sm">
                            <thead><tr><th>Motorista</th><th class="text-center">Desvio Médio</th><th class="text-center">Consistência</th><th class="text-center text-atrasado">Excesso Atraso</th><th class="text-center text-adiantado">Excesso Adiant.</th></tr></thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($stats_motoristas_chegada as $motorista): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($motorista['operator']); ?></strong></td>
                                        <td class="text-center font-semibold <?php echo getCorDesvio($motorista['desvio_medio']); ?>"><?php echo formatarTempoSQL($motorista['desvio_medio']); ?></td>
                                        <td class="text-center"><?php echo formatarTempoSQL($motorista['consistencia']); ?></td>
                                        <td class="text-center text-atrasado"><?php echo formatarDesvioExcedente($motorista['pior_atraso']); ?></td>
                                        <td class="text-center text-adiantado"><?php echo formatarDesvioExcedente($motorista['pior_adiantamento']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">3. Análise dos Motoristas e Carros</h2>
                <div class="scrolling-container p-2">
                    <div class="space-y-6">
                        <?php foreach ($motoristas_veiculos_chegada as $motorista => $veiculos): ?>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-2"><?php echo htmlspecialchars($motorista); ?></h3>
                                <ul class="space-y-1">
                                    <?php foreach ($veiculos as $veiculo): ?>
                                        <li class="flex justify-between items-center text-sm p-1 rounded hover:bg-gray-50">
                                            <span>Veículo: <strong class="font-mono bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($veiculo['vehicle']); ?></strong></span>
                                            <span>Linha: <strong class="font-semibold"><?php echo htmlspecialchars($veiculo['route']); ?></strong></span>
                                            <span>Viagens: <span class="font-semibold"><?php echo $veiculo['total_viagens']; ?></span></span>
                                            <span class="font-semibold <?php echo getCorDesvio($veiculo['desvio_medio']); ?>">Desvio Médio: <?php echo formatarTempoSQL($veiculo['desvio_medio']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <section class="lg:col-span-2 space-y-8">
                <div class="card p-6"><h3 class="text-xl font-semibold text-gray-700 mb-4 text-atrasado">📍 Top 50 Pontos com Mais Atrasos</h3>
                    <div class="scrolling-container">
                        <table class="w-full text-sm">
                            <thead><tr><th>Ponto de Controle</th><th>Linha</th><th class="text-center">Ocorrências</th></tr></thead>
                            <tbody><?php foreach($top_pontos_atraso_chegada as $ponto): ?><tr><td><?php echo htmlspecialchars($ponto['nome_parada']); ?></td><td><?php echo htmlspecialchars($ponto['route']); ?></td><td class="text-center font-bold text-atrasado"><?php echo $ponto['quantidade']; ?></td></tr><?php endforeach; ?></tbody>
                        </table>
                    </div>
                </div>
                <div class="card p-6"><h3 class="text-xl font-semibold text-gray-700 mb-4 text-adiantado">📍 Top 50 Pontos com Mais Adiantamentos</h3>
                    <div class="scrolling-container">
                        <table class="w-full text-sm">
                             <thead><tr><th>Ponto de Controle</th><th>Linha</th><th class="text-center">Ocorrências</th></tr></thead>
                             <tbody><?php foreach($top_pontos_adiantado_chegada as $ponto): ?><tr><td><?php echo htmlspecialchars($ponto['nome_parada']); ?></td><td><?php echo htmlspecialchars($ponto['route']); ?></td><td class="text-center font-bold text-adiantado"><?php echo $ponto['quantidade']; ?></td></tr><?php endforeach; ?></tbody>
                        </table>
                    </div>
                </div>
                <div class="card p-6"><h3 class="text-xl font-semibold text-gray-700 mb-4 text-atrasado">⚠️ Top 50 Maiores Atrasos Registrados (Chegada)</h3>
    <div class="scrolling-container">
        <table class="w-full text-sm">
            <thead><tr><th>Ponto</th><th>Linha</th><th>Data</th><th>Chegada Real</th><th class="text-center">Pior Desvio</th><th class="text-center">Excesso</th></tr></thead>
            <tbody>
                <?php foreach($top_maiores_atrasos_chegada as $registo): ?>
                <tr>
                    <td><?php echo htmlspecialchars($registo['nome_parada']); ?></td>
                    <td><?php echo htmlspecialchars($registo['route']); ?></td>
                    <td><?php echo (new DateTime($registo['partida_programada']))->format('d/m H:i'); ?></td>
                    <td><?php echo $registo['chegada_real'] ? (new DateTime($registo['chegada_real']))->format('H:i') : '-'; ?></td>
                    <td class="text-center"><?php echo formatarTempoSQL($registo['desvio_chegada']); ?></td>
                    <td class="font-bold text-center text-atrasado"><?php echo formatarDesvioExcedente($registo['desvio_chegada']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
                <div class="card p-6"><h3 class="text-xl font-semibold text-gray-700 mb-4 text-adiantado">🏃 Top 50 Maiores Adiantamentos Registrados (Chegada)</h3>
    <div class="scrolling-container">
        <table class="w-full text-sm">
            <thead><tr><th>Ponto</th><th>Linha</th><th>Data</th><th>Chegada Real</th><th class="text-center">Pior Desvio</th><th class="text-center">Excesso</th></tr></thead>
            <tbody>
                <?php foreach($top_maiores_adiantamentos_chegada as $registo): ?>
                <tr>
                    <td><?php echo htmlspecialchars($registo['nome_parada']); ?></td>
                    <td><?php echo htmlspecialchars($registo['route']); ?></td>
                    <td><?php echo (new DateTime($registo['partida_programada']))->format('d/m H:i'); ?></td>
                    <td><?php echo $registo['chegada_real'] ? (new DateTime($registo['chegada_real']))->format('H:i') : '-'; ?></td>
                    <td class="text-center"><?php echo formatarTempoSQL($registo['desvio_chegada']); ?></td>
                    <td class="font-bold text-center text-adiantado"><?php echo formatarDesvioExcedente($registo['desvio_chegada']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
            </section>

            </div>

        <div class="pt-8 mt-8 space-y-8">
             <h2 class="text-3xl font-bold text-center text-gray-800 border-t-2 border-b-2 py-4 border-gray-300 bg-white shadow-md">Análise de Pontualidade de PARTIDAS</h2>
        
            <section class="card p-6">
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">Distribuição de Status (Partida)</h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                    <div class="space-y-4">
                         <?php $divisor_perc = ($stats_partida['total_registros'] ?? 0) > 0 ? $stats_partida['total_registros'] : 1; ?>
                        <div class="flex items-center"><span class="w-4 h-4 rounded-full bg-no-horario-dot mr-3"></span><div class="flex-grow"><span class="font-semibold text-no-horario">No Horário</span></div><div class="text-right"><span class="font-bold"><?php echo $stats_partida['total_no_horario']; ?></span><span class="text-gray-500 text-sm ml-2">/ <?php echo round(($stats_partida['total_no_horario'] / $divisor_perc) * 100, 1); ?>%</span></div></div>
                        <div class="flex items-center"><span class="w-4 h-4 rounded-full bg-atrasado-dot mr-3"></span><div class="flex-grow"><span class="font-semibold text-atrasado">Atrasado</span></div><div class="text-right"><span class="font-bold"><?php echo $stats_partida['total_atrasado']; ?></span><span class="text-gray-500 text-sm ml-2">/ <?php echo round(($stats_partida['total_atrasado'] / $divisor_perc) * 100, 1); ?>%</span></div></div>
                        <div class="flex items-center"><span class="w-4 h-4 rounded-full bg-adiantado-dot mr-3"></span><div class="flex-grow"><span class="font-semibold text-adiantado">Adiantado</span></div><div class="text-right"><span class="font-bold"><?php echo $stats_partida['total_adiantado']; ?></span><span class="text-gray-500 text-sm ml-2">/ <?php echo round(($stats_partida['total_adiantado'] / $divisor_perc) * 100, 1); ?>%</span></div></div>
                        <div class="flex items-center"><span class="w-4 h-4 rounded-full bg-supressao-dot mr-3"></span><div class="flex-grow"><span class="font-semibold text-supressao">Supressão</span></div><div class="text-right"><span class="font-bold"><?php echo $stats_partida['total_supressao']; ?></span><span class="text-gray-500 text-sm ml-2">/ <?php echo round(($stats_partida['total_supressao'] / $divisor_perc) * 100, 1); ?>%</span></div></div>
                        <div class="flex items-center"><span class="w-4 h-4 rounded-full bg-sem-dados-dot mr-3"></span><div class="flex-grow"><span class="font-semibold text-sem-dados">Sem Dados</span></div><div class="text-right"><span class="font-bold"><?php echo $stats_partida['total_sem_dados']; ?></span><span class="text-gray-500 text-sm ml-2">/ <?php echo round(($stats_partida['total_sem_dados'] / $divisor_perc) * 100, 1); ?>%</span></div></div>
                        <div class="border-t pt-4 mt-4"><div class="flex items-center text-lg"><div class="flex-grow"><span class="font-bold">Total de Partidas Analisadas</span></div><div class="text-right"><span class="font-bold"><?php echo $stats_partida['total_registros']; ?></span></div></div></div>
                    </div>
                    <div class="h-64 lg:h-80"><canvas id="statusChartPartida"></canvas></div>
                </div>
            </section>
            
             <?php if ($linha_selecionada === 'todas' && !empty($stats_por_linha_partida)): ?>
                <div class="card p-6">
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">Desempenho por Linhas (Partida)</h3>
                    <div class="scrolling-container">
                        <table class="w-full text-sm">
                           <thead><tr><th>Linha</th><th class="text-center text-no-horario">Nº / % No Horário</th><th class="text-center text-atrasado">Nº / % Atrasado</th><th class="text-center text-adiantado">Nº / % Adiantado</th><th class="text-center text-supressao">Nº / % Supressão</th></tr></thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($stats_por_linha_partida as $linha_data): $divisor = $linha_data['total_viagens'] > 0 ? $linha_data['total_viagens'] : 1; ?>
                                    <tr>
                                        <td><strong class="font-mono"><?php echo htmlspecialchars($linha_data['route']); ?></strong></td>
                                        <td class="text-center"><?php echo $linha_data['total_no_horario']; ?> <span class="text-xs text-gray-500">/ <?php echo round(($linha_data['total_no_horario'] / $divisor) * 100, 1); ?>%</span></td>
                                        <td class="text-center"><?php echo $linha_data['total_atrasado']; ?> <span class="text-xs text-gray-500">/ <?php echo round(($linha_data['total_atrasado'] / $divisor) * 100, 1); ?>%</span></td>
                                        <td class="text-center"><?php echo $linha_data['total_adiantado']; ?> <span class="text-xs text-gray-500">/ <?php echo round(($linha_data['total_adiantado'] / $divisor) * 100, 1); ?>%</span></td>
                                        <td class="text-center"><?php echo $linha_data['total_supressao']; ?> <span class="text-xs text-gray-500">/ <?php echo round(($linha_data['total_supressao'] / $divisor) * 100, 1); ?>%</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="card p-6">
                <h3 class="text-xl font-semibold text-gray-700 mb-4">Desempenho por Sentido</h3>
                <div class="scrolling-container">
                    <table class="w-full text-sm">
                        <thead><tr><th>Sentido</th><th class="text-center text-no-horario">Nº / % No Horário</th><th class="text-center text-atrasado">Nº / % Atrasado</th><th class="text-center text-adiantado">Nº / % Adiantado</th><th class="text-center">Desvio Médio</th></tr></thead>
                        <tbody>
                            <?php foreach ($stats_sentido_partida as $linha): $divisor = $linha['total_viagens'] > 0 ? $linha['total_viagens'] : 1; ?>
                                <tr>
                                    <td><strong><?php echo $linha['direction']; ?></strong></td>
                                    <td class="text-center"><?php echo $linha['total_no_horario']; ?> <span class="text-xs text-gray-500">/ <?php echo round($linha['total_no_horario'] / $divisor * 100, 1); ?>%</span></td>
                                    <td class="text-center"><?php echo $linha['total_atrasado']; ?> <span class="text-xs text-gray-500">/ <?php echo round($linha['total_atrasado'] / $divisor * 100, 1); ?>%</span></td>
                                    <td class="text-center"><?php echo $linha['total_adiantado']; ?> <span class="text-xs text-gray-500">/ <?php echo round($linha['total_adiantado'] / $divisor * 100, 1); ?>%</span></td>
                                    <td class="text-center font-semibold <?php echo getCorDesvio($linha['desvio_medio']); ?>"><?php echo formatarTempoSQL($linha['desvio_medio']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-6">
                <h3 class="text-xl font-semibold text-gray-700 mb-4">Desvio Médio por Faixa Horária</h3>
                <div class="scrolling-container">
                    <table class="w-full text-sm">
                        <thead><tr><th>Faixa Horária</th><th class="text-center text-no-horario">Nº / % No Horário</th><th class="text-center text-atrasado">Nº / % Atrasado</th><th class="text-center text-adiantado">Nº / % Adiantado</th><th class="text-center">Desvio Médio</th></tr></thead>
                        <tbody>
                            <?php foreach ($stats_faixa_horaria_partida as $linha): $divisor = $linha['total_viagens'] > 0 ? $linha['total_viagens'] : 1; ?>
                                <tr>
                                    <td><?php echo $linha['faixa_horaria']; ?></td>
                                    <td class="text-center"><?php echo $linha['total_no_horario']; ?> <span class="text-xs text-gray-500">/ <?php echo round($linha['total_no_horario'] / $divisor * 100, 1); ?>%</span></td>
                                    <td class="text-center"><?php echo $linha['total_atrasado']; ?> <span class="text-xs text-gray-500">/ <?php echo round($linha['total_atrasado'] / $divisor * 100, 1); ?>%</span></td>
                                    <td class="text-center"><?php echo $linha['total_adiantado']; ?> <span class="text-xs text-gray-500">/ <?php echo round($linha['total_adiantado'] / $divisor * 100, 1); ?>%</span></td>
                                    <td class="text-center font-semibold <?php echo getCorDesvio($linha['desvio_medio']); ?>"><?php echo formatarTempoSQL($linha['desvio_medio']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-6 space-y-8">
                <h2 class="text-2xl font-semibold text-gray-700">Painel de Desempenho por Motorista</h2>
                <div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">1. Resumo Quantitativo</h3>
                    <div class="scrolling-container">
                        <table class="w-full text-sm">
                            <thead><tr><th>Motorista</th><th class="text-center text-no-horario">Nº / % No Horário</th><th class="text-center text-atrasado">Nº / % Atrasado</th><th class="text-center text-adiantado">Nº / % Adiantado</th><th class="text-center text-supressao">Nº / % Supressão</th></tr></thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($stats_motoristas_partida as $motorista): $divisor = $motorista['total_viagens'] > 0 ? $motorista['total_viagens'] : 1; ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($motorista['operator']); ?></strong></td>
                                        <td class="text-center"><?php echo $motorista['total_no_horario']; ?> <span class="text-xs text-gray-500">/ <?php echo round($motorista['total_no_horario'] / $divisor * 100, 1); ?>%</span></td>
                                        <td class="text-center"><?php echo $motorista['total_atrasado']; ?> <span class="text-xs text-gray-500">/ <?php echo round($motorista['total_atrasado'] / $divisor * 100, 1); ?>%</span></td>
                                        <td class="text-center"><?php echo $motorista['total_adiantado']; ?> <span class="text-xs text-gray-500">/ <?php echo round($motorista['total_adiantado'] / $divisor * 100, 1); ?>%</span></td>
                                        <td class="text-center"><?php echo $motorista['total_supressao']; ?> <span class="text-xs text-gray-500">/ <?php echo round($motorista['total_supressao'] / $divisor * 100, 1); ?>%</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">2. Análise de Desvio e Consistência</h3>
                    <div class="scrolling-container">
                        <table class="w-full text-sm">
                            <thead><tr><th>Motorista</th><th class="text-center">Desvio Médio</th><th class="text-center">Consistência</th><th class="text-center text-atrasado">Excesso Atraso</th><th class="text-center text-adiantado">Excesso Adiant.</th></tr></thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($stats_motoristas_partida as $motorista): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($motorista['operator']); ?></strong></td>
                                        <td class="text-center font-semibold <?php echo getCorDesvio($motorista['desvio_medio']); ?>"><?php echo formatarTempoSQL($motorista['desvio_medio']); ?></td>
                                        <td class="text-center"><?php echo formatarTempoSQL($motorista['consistencia']); ?></td>
                                        <td class="text-center text-atrasado"><?php echo formatarDesvioExcedente($motorista['pior_atraso']); ?></td>
                                        <td class="text-center text-adiantado"><?php echo formatarDesvioExcedente($motorista['pior_adiantamento']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">3. Análise dos Motoristas e Carros</h2>
                <div class="scrolling-container p-2">
                    <div class="space-y-6">
                        <?php foreach ($motoristas_veiculos_partida as $motorista => $veiculos): ?>
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-2"><?php echo htmlspecialchars($motorista); ?></h3>
                                <ul class="space-y-1">
                                    <?php foreach ($veiculos as $veiculo): ?>
                                        <li class="flex justify-between items-center text-sm p-1 rounded hover:bg-gray-50">
                                            <span>Veículo: <strong class="font-mono bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($veiculo['vehicle']); ?></strong></span>
                                            <span>Linha: <strong class="font-semibold"><?php echo htmlspecialchars($veiculo['route']); ?></strong></span>
                                            <span>Viagens: <span class="font-semibold"><?php echo $veiculo['total_viagens']; ?></span></span>
                                            <span class="font-semibold <?php echo getCorDesvio($veiculo['desvio_medio']); ?>">Desvio Médio: <?php echo formatarTempoSQL($veiculo['desvio_medio']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <section class="lg:col-span-2 space-y-8">
                <div class="card p-6"><h3 class="text-xl font-semibold text-gray-700 mb-4 text-atrasado">📍 Top 50 Pontos com Mais Atrasos</h3>
                    <div class="scrolling-container">
                        <table class="w-full text-sm">
                            <thead><tr><th>Ponto de Controle</th><th>Linha</th><th class="text-center">Ocorrências</th></tr></thead>
                            <tbody><?php foreach($top_pontos_atraso_partida as $ponto): ?><tr><td><?php echo htmlspecialchars($ponto['nome_parada']); ?></td><td><?php echo htmlspecialchars($ponto['route']); ?></td><td class="text-center font-bold text-atrasado"><?php echo $ponto['quantidade']; ?></td></tr><?php endforeach; ?></tbody>
                        </table>
                    </div>
                </div>
                <div class="card p-6"><h3 class="text-xl font-semibold text-gray-700 mb-4 text-adiantado">📍 Top 50 Pontos com Mais Adiantamentos</h3>
                    <div class="scrolling-container">
                        <table class="w-full text-sm">
                             <thead><tr><th>Ponto de Controle</th><th>Linha</th><th class="text-center">Ocorrências</th></tr></thead>
                             <tbody><?php foreach($top_pontos_adiantado_partida as $ponto): ?><tr><td><?php echo htmlspecialchars($ponto['nome_parada']); ?></td><td><?php echo htmlspecialchars($ponto['route']); ?></td><td class="text-center font-bold text-adiantado"><?php echo $ponto['quantidade']; ?></td></tr><?php endforeach; ?></tbody>
                        </table>
                    </div>
                </div>
                <div class="card p-6"><h3 class="text-xl font-semibold text-gray-700 mb-4 text-atrasado">⚠️ Top 50 Maiores Atrasos Registrados</h3>
    <div class="scrolling-container">
        <table class="w-full text-sm">
            <thead><tr><th>Ponto</th><th>Linha</th><th>Data</th><th>Real</th><th class="text-center">Desvio Total</th><th class="text-center">Excesso</th></tr></thead>
            <tbody>
                <?php foreach($top_maiores_atrasos_partida as $registo): ?>
                <tr>
                    <td><?php echo htmlspecialchars($registo['nome_parada']); ?></td>
                    <td><?php echo htmlspecialchars($registo['route']); ?></td>
                    <td><?php echo (new DateTime($registo['partida_programada']))->format('d/m H:i'); ?></td>
                    <td><?php echo (new DateTime($registo['partida_real']))->format('H:i'); ?></td>
                    <td class="text-center"><?php echo formatarTempoSQL($registo['desvio_partida']); ?></td>
                    <td class="font-bold text-center text-atrasado"><?php echo formatarDesvioExcedente($registo['desvio_partida']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
                <div class="card p-6"><h3 class="text-xl font-semibold text-gray-700 mb-4 text-adiantado">🏃 Top 50 Maiores Adiantamentos Registrados</h3>
    <div class="scrolling-container">
        <table class="w-full text-sm">
            <thead><tr><th>Ponto</th><th>Linha</th><th>Data</th><th>Real</th><th class="text-center">Desvio Total</th><th class="text-center">Excesso</th></tr></thead>
            <tbody>
                <?php foreach($top_maiores_adiantamentos_partida as $registo): ?>
                <tr>
                    <td><?php echo htmlspecialchars($registo['nome_parada']); ?></td>
                    <td><?php echo htmlspecialchars($registo['route']); ?></td>
                    <td><?php echo (new DateTime($registo['partida_programada']))->format('d/m H:i'); ?></td>
                    <td><?php echo (new DateTime($registo['partida_real']))->format('H:i'); ?></td>
                    <td class="text-center"><?php echo formatarTempoSQL($registo['desvio_partida']); ?></td>
                    <td class="font-bold text-center text-adiantado"><?php echo formatarDesvioExcedente($registo['desvio_partida']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
            </section>
             </div>
    </main>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chartDataChegada = <?php echo json_encode($chart_data_chegada); ?>;
            const ctxChegada = document.getElementById('statusChartChegada');
            if (ctxChegada && chartDataChegada) { new Chart(ctxChegada.getContext('2d'), { type: 'doughnut', data: { labels: chartDataChegada.labels, datasets: [{ data: chartDataChegada.counts, backgroundColor: chartDataChegada.colors, borderColor: '#fff' }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, cutout: '60%' } }); }

            const chartDataPartida = <?php echo json_encode($chart_data_partida); ?>;
            const ctxPartida = document.getElementById('statusChartPartida');
            if (ctxPartida && chartDataPartida) { new Chart(ctxPartida.getContext('2d'), { type: 'doughnut', data: { labels: chartDataPartida.labels, datasets: [{ data: chartDataPartida.counts, backgroundColor: chartDataPartida.colors, borderColor: '#fff' }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, cutout: '60%' } }); }
        });
    </script>
	<script>
document.addEventListener('DOMContentLoaded', () => {

    // --- 1. Seletores e Variáveis de Estado ---
    const tabelaLinha = document.getElementById('tabela-analise-linha');
    if (!tabelaLinha) return; // Se a tabela não existir, não faz nada

    const corpoTabela = document.getElementById('corpo-tabela-analise-linha');
    const headers = tabelaLinha.querySelectorAll('thead th[data-coluna]');
    const inputsFiltro = tabelaLinha.querySelectorAll('thead input[data-coluna]');
    const btnReset = document.getElementById('reset-filtros-linha');

    let estadoOrdenacao = {
        coluna: 'total_no_horario_percent', // Padrão inicial
        direcao: 'DESC'
    };

    // --- 2. Função Principal de Requisição AJAX ---
    async function fetchTabelaLinhas() {
    // --- ESTE BLOCO ESTAVA FALTANDO ---
    const filtros = {};
    inputsFiltro.forEach(input => {
        if (input.value) {
            // Usa o 'data-coluna' do input para criar o nome do filtro
            filtros['filtro_' + input.dataset.coluna] = input.value;
        }
    });
    // --- FIM DO BLOCO FALTANTE ---

    // Pega a aba selecionada da URL da página
    const paginaParams = new URLSearchParams(window.location.search);
    const abaAtual = paginaParams.get('aba') || 'geral';

    // Monta a URL com os parâmetros de ordenação e filtro
    const params = new URLSearchParams({
        aba: abaAtual,
        ordem_coluna: estadoOrdenacao.coluna,
        ordem_direcao: estadoOrdenacao.direcao,
        ...filtros // Agora a variável 'filtros' existe e o erro vai desaparecer
    });
    
    const url = `ajax_filtrar_linhas.php?${params.toString()}`;

    try {
        // Adiciona um feedback visual de carregamento
        corpoTabela.style.opacity = '0.5';

        const response = await fetch(url);
        if (!response.ok) {
            throw new Error('A resposta da rede não foi OK');
        }
        const html = await response.text();
        
        // Atualiza a tabela com os novos dados
        corpoTabela.innerHTML = html;

    } catch (error) {
        console.error('Erro ao buscar dados:', error);
        // Aumentei o colspan para 5 para corresponder à nova tabela
        corpoTabela.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-red-500">Erro ao carregar os dados.</td></tr>`;
    } finally {
        // Remove o feedback de carregamento
        corpoTabela.style.opacity = '1';
        atualizarIndicadoresOrdenacao();
    }
}

    // --- 3. Função para Atualizar Símbolos de Ordenação (▲/▼) ---
    function atualizarIndicadoresOrdenacao() {
        headers.forEach(header => {
            const span = header.querySelector('span');
            if (header.dataset.coluna === estadoOrdenacao.coluna) {
                span.textContent = estadoOrdenacao.direcao === 'DESC' ? ' ▼' : ' ▲';
            } else {
                span.textContent = '';
            }
        });
    }

    // --- 4. Event Listeners (Gatilhos de Ação) ---

    // Ordenar ao clicar no cabeçalho
    headers.forEach(header => {
        header.addEventListener('click', () => {
            const novaColuna = header.dataset.coluna;
            if (estadoOrdenacao.coluna === novaColuna) {
                // Se já está ordenando por esta coluna, inverte a direção
                estadoOrdenacao.direcao = estadoOrdenacao.direcao === 'DESC' ? 'ASC' : 'DESC';
            } else {
                // Se for uma nova coluna, define como padrão
                estadoOrdenacao.coluna = novaColuna;
                estadoOrdenacao.direcao = 'DESC';
            }
            fetchTabelaLinhas();
        });
    });

    // Filtrar ao digitar (com um pequeno delay para não sobrecarregar)
    let typingTimer;
    inputsFiltro.forEach(input => {
        input.addEventListener('keyup', () => {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(fetchTabelaLinhas, 500); // Espera 500ms após o usuário parar de digitar
        });
    });

    // Limpar filtros ao clicar no botão
    btnReset.addEventListener('click', () => {
        inputsFiltro.forEach(input => input.value = '');
        estadoOrdenacao.coluna = 'total_no_horario_percent';
        estadoOrdenacao.direcao = 'DESC';
        fetchTabelaLinhas();
    });

    // --- 5. Inicialização ---
    // Carrega os indicadores de ordenação na primeira vez
    atualizarIndicadoresOrdenacao();
});
</script>
</body>
</html>