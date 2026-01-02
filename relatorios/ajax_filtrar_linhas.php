<?php
// Inclui apenas as configurações e funções necessárias
require 'config_0241_geral.php';

// --- 1. RECEBER E SANITIZAR PARÂMETROS ---
$filtro_route = $_GET['filtro_route'] ?? null;
$aba_selecionada = $_GET['aba'] ?? 'geral';

$ordenacao_coluna = $_GET['ordem_coluna'] ?? 'no_horario_percent';
$ordenacao_direcao = $_GET['ordem_direcao'] ?? 'DESC';

// Lista de colunas permitidas para ordenação (Segurança)
$colunas_permitidas = ['route', 'no_horario_percent', 'atrasado_percent', 'adiantado_percent', 'supressao_percent'];
if (!in_array($ordenacao_coluna, $colunas_permitidas)) {
    $ordenacao_coluna = 'no_horario_percent';
}
if (!in_array(strtoupper($ordenacao_direcao), ['ASC', 'DESC'])) {
    $ordenacao_direcao = 'DESC';
}


// --- 2. CONSTRUIR A CONSULTA SQL DINÂMICA ---

// Cláusulas de filtro para os dias da semana (usado na subquery)
$where_dias_semana = "";
switch ($aba_selecionada) {
    case 'uteis': $where_dias_semana = "AND WEEKDAY(partida_programada) BETWEEN 0 AND 4"; break;
    case 'sabados': $where_dias_semana = "AND WEEKDAY(partida_programada) = 5"; break;
    case 'domingos': $where_dias_semana = "AND WEEKDAY(partida_programada) = 6"; break;
}

// Cláusula de filtro para a busca de texto (usado na query principal)
$where_filtro_texto = "";
if (!empty($filtro_route)) {
    $where_filtro_texto = "WHERE route LIKE '%" . $conexao->real_escape_string($filtro_route) . "%'";
}

// Query final com subqueries para calcular o status antes de agrupar
$sql_final = "
    SELECT
        route, total_viagens, total_no_horario,
        (total_no_horario / total_viagens) * 100 as no_horario_percent,
        total_atrasado,
        (total_atrasado / total_viagens) * 100 as atrasado_percent,
        total_adiantado,
        (total_adiantado / total_viagens) * 100 as adiantado_percent,
        total_supressao,
        (total_supressao / total_viagens) * 100 as supressao_percent
    FROM (
        -- Query do meio: Agrupa e conta os status já calculados
        SELECT
            route,
            COUNT(*) as total_viagens,
            COUNT(CASE WHEN status_chegada = 'No Horário' THEN 1 END) as total_no_horario,
            COUNT(CASE WHEN status_chegada = 'Atrasado' THEN 1 END) as total_atrasado,
            COUNT(CASE WHEN status_chegada = 'Adiantado' THEN 1 END) as total_adiantado,
            COUNT(CASE WHEN status_chegada = 'Supressão' THEN 1 END) as total_supressao
        FROM (
            -- Query interna: Calcula o status para cada viagem
            SELECT
                route,
                partida_programada,
                (CASE 
                    WHEN chegada_real IS NULL THEN 'Sem Dados' 
                    WHEN desvio_chegada > '00:15:00' THEN 'Supressão' 
                    WHEN desvio_chegada >= '00:06:00' THEN 'Atrasado' 
                    WHEN desvio_chegada <= '-00:02:00' THEN 'Adiantado' 
                    ELSE 'No Horário' 
                END) AS status_chegada
            FROM registros_gerais
        ) as viagens_com_status
        -- Aplica os filtros nos dados já calculados
        WHERE status_chegada != 'Sem Dados' {$where_dias_semana}
        GROUP BY route
    ) as stats
    {$where_filtro_texto}
    ORDER BY {$ordenacao_coluna} {$ordenacao_direcao}
";

$resultado = $conexao->query($sql_final);


// --- 3. GERAR O HTML DAS LINHAS DA TABELA (TRs) ---
if ($resultado && $resultado->num_rows > 0) {
    while ($linha = $resultado->fetch_assoc()) {
        echo '<tr>';
        echo '    <td class="py-2 px-4 border-b"><strong class="font-mono">' . htmlspecialchars($linha['route']) . '</strong></td>';
        echo '    <td class="py-2 px-4 border-b text-center text-no-horario">' . $linha['total_no_horario'] . ' <span class="text-xs text-gray-500">/ ' . round($linha['no_horario_percent'], 1) . '%</span></td>';
        echo '    <td class="py-2 px-4 border-b text-center text-atrasado">' . $linha['total_atrasado'] . ' <span class="text-xs text-gray-500">/ ' . round($linha['atrasado_percent'], 1) . '%</span></td>';
        echo '    <td class="py-2 px-4 border-b text-center text-adiantado">' . $linha['total_adiantado'] . ' <span class="text-xs text-gray-500">/ ' . round($linha['adiantado_percent'], 1) . '%</span></td>';
        echo '    <td class="py-2 px-4 border-b text-center text-supressao">' . $linha['total_supressao'] . ' <span class="text-xs text-gray-500">/ ' . round($linha['supressao_percent'], 1) . '%</span></td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="5" class="text-center py-4">Nenhum resultado encontrado.</td></tr>';
}
$conexao->close();
?>