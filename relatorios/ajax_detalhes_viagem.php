<?php
// =================================================================
//  Parceiro de Programação - AJAX Handler para Detalhes (v8.1)
//  - Chamado por 'relatorio_tabelas.php' ao clicar em "Ver Viagens".
//  - Recebe filtros + um 'workid'.
//  - Executa a 'Query 6' (detalhes) APENAS para esse workid.
//  - Retorna o HTML da tabela de detalhes para o modal.
// =================================================================

// --- 1. CONFIGURAÇÕES E CONEXÃO ---
ini_set('display_errors', 0); error_reporting(0);
set_time_limit(300);
require_once 'config_km.php'; 

$conexao = new mysqli("localhost", "root", "", "relatorio");
if ($conexao->connect_error) { die("<p class='text-red-500'>Erro de Conexão DB</p>"); }
$conexao->set_charset("utf8mb4");

// --- 2. FUNÇÕES AUXILIARES ---
function get_dia_semana_where_sql($dia_semana_selecionado) {
    switch ($dia_semana_selecionado) {
        case 'UTEIS': return " WEEKDAY(data_viagem) BETWEEN 0 AND 4 ";
        case 'SABADO': return " WEEKDAY(data_viagem) = 5 ";
        case 'DOMINGO': return " WEEKDAY(data_viagem) = 6 ";
        default: return " 1=1 ";
    }
}
function get_duracao_viagem_sql($start_col = 'v.START_TIME', $end_col = 'v.END_TIME') {
    return "
        (CASE 
            WHEN TIME($end_col) < TIME($start_col) 
            THEN TIMESTAMPDIFF(SECOND, TIME($start_col), TIME($end_col) + INTERVAL 1 DAY) 
            ELSE TIMESTAMPDIFF(SECOND, TIME($start_col), TIME($end_col)) 
        END)
    ";
}

// --- 3. RECEBER E VALIDAR FILTROS ---
$workid = $_GET['workid'] ?? null;
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$dia_semana_selecionado = $_GET['dia_semana'] ?? 'todos';
$linha_selecionada = $_GET['linha'] ?? 'todos'; // Linha principal (para CTE)

if (empty($workid)) {
    die("<p class='text-red-500'>Erro: WorkID não fornecido.</p>");
}

// --- 4. CONSTRUIR AS CLÁUSULAS WHERE E CTEs ---
// (Precisamos recriar as CTEs e WHERES para que a Query 6 funcione no contexto certo)
$where_date_range_sql_str = "data_viagem BETWEEN '{$conexao->real_escape_string($data_inicio)}' AND '{$conexao->real_escape_string($data_fim)}'";
$where_dia_semana_sql_str = get_dia_semana_where_sql($dia_semana_selecionado);

// CTEs (Apenas se o filtro de linha 'todas' estiver ativo, senão não são necessárias)
$ctes_completas_sql = "";
if ($linha_selecionada == 'todos') {
    $cte_ranked_linhas_sql = "
        RankedLinhas AS (
            SELECT v_lp.BLOCK_NUMBER, v_lp.ROUTE_ID,
                ROW_NUMBER() OVER(PARTITION BY v_lp.BLOCK_NUMBER ORDER BY COUNT(*) DESC, SUM(v_lp.DISTANCE) DESC) as rn
            FROM relatorios_viagens AS v_lp
            WHERE v_lp.TRIP_ID != 0 AND v_lp.ROUTE_ID IS NOT NULL
            AND v_lp.{$where_date_range_sql_str}
            AND (" . str_replace('data_viagem', 'v_lp.data_viagem', $where_dia_semana_sql_str) . ")
            GROUP BY v_lp.BLOCK_NUMBER, v_lp.ROUTE_ID
        )
    ";
    $cte_linha_primaria_sql = "
        linha_primaria_cte AS (
            SELECT BLOCK_NUMBER, ROUTE_ID as linha_primaria FROM RankedLinhas WHERE rn = 1
        )
    ";
    $ctes_completas_sql = "WITH $cte_ranked_linhas_sql, $cte_linha_primaria_sql";
}

$where_linha_sql_str = ($linha_selecionada !== 'todos') 
    ? " COALESCE(lp.linha_primaria, v.ROUTE_ID) = '{$conexao->real_escape_string($linha_selecionada)}' "
    : " 1=1 ";
$sql_base_viagens_joins = "
    FROM relatorios_viagens AS v
    LEFT JOIN linha_primaria_cte AS lp ON v.BLOCK_NUMBER = lp.BLOCK_NUMBER
";
$sql_base_viagens_where = "
    WHERE
        v.{$where_date_range_sql_str}
        AND (" . str_replace('data_viagem', 'v.data_viagem', $where_dia_semana_sql_str) . ")
        AND $where_linha_sql_str
";
$duracao_viagem_sql = get_duracao_viagem_sql('v.START_TIME', 'v.END_TIME');

// --- 5. EXECUTAR A QUERY 6 (OTIMIZADA) ---
$sql_viagens_detalhe_ajax = "
    $ctes_completas_sql
    SELECT 
        v.START_TIME, v.END_TIME, 
        v.ROUTE_ID as linha, 
        v.DIRECTION_NUM, 
        (v.DISTANCE / 1000) as distancia_km, 
        ($duracao_viagem_sql) as duracao_seg, 
        v.TRIP_ID
    $sql_base_viagens_joins
    INNER JOIN relatorios_servicos AS s ON v.BLOCK_NUMBER = s.REFERREDVB_COMPANYCODE
    $sql_base_viagens_where
    AND s.DUTY_COMPANYCODE = '{$conexao->real_escape_string($workid)}' -- FILTRO PRINCIPAL
    ORDER BY v.START_TIME
";

$result_viagens_detalhe = $conexao->query($sql_viagens_detalhe_ajax);

if (!$result_viagens_detalhe || $result_viagens_detalhe->num_rows === 0) {
    die("<p class='text-center'>Nenhuma viagem encontrada para este serviço nos filtros selecionados.</p>");
}

// --- 6. GERAR E RETORNAR O HTML DA TABELA ---
?>
<table class="details-table">
    <thead>
        <tr>
            <th>Início</th>
            <th>Fim</th>
            <th>Duração</th>
            <th>Linha (Viagem)</th>
            <th>Sentido</th>
            <th>KM</th>
        </tr>
    </thead>
    <tbody>
        <?php while($viagem = $result_viagens_detalhe->fetch_assoc()): 
            $is_ociosa_detalhe = ($viagem['TRIP_ID'] == 0);
        ?>
            <tr class="<?= $is_ociosa_detalhe ? 'bg-yellow-50' : 'bg-green-50' ?>">
                <td><?= $viagem['START_TIME'] ?></td>
                <td><?= $viagem['END_TIME'] ?></td>
                <td><?= formatar_segundos_hhmmss($viagem['duracao_seg']) ?></td>
                <td><?= $is_ociosa_detalhe ? '<i>Ociosa</i>' : htmlspecialchars($viagem['linha']) ?></td>
                <td><?= $is_ociosa_detalhe ? '-' : ($viagem['DIRECTION_NUM'] == 0 ? 'Ida' : 'Volta') ?></td>
                <td class="text-right"><?= number_format($viagem['distancia_km'], 1, ',', '.') ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php $conexao->close(); ?>