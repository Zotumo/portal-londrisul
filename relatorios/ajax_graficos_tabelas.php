<?php
// =================================================================
//  Parceiro de Programação - AJAX Backend v10.0 (REESTRUTURADO)
// =================================================================
ini_set('display_errors', 0); 
error_reporting(0);
require_once 'config_km.php'; 

$conexao = new mysqli("localhost", "root", "", "relatorio");
$conexao->set_charset("utf8mb4");

$tipo_grafico = $_GET['tipo_grafico'] ?? null;
$hora_selecionada = $_GET['hora_selecionada'] ?? null;
$modo_local = $_GET['modo_local'] ?? 'inicio';
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$linha_selecionada = $_GET['linha'] ?? 'todos';

header('Content-Type: application/json');

// --- LÓGICA 1: SERVIÇOS ATIVOS (WorkIDs) ---
if ($tipo_grafico === 'ativos') {
    $labels = [];
    for ($m = 0; $m < 60; $m += 10) $labels[] = sprintf('%02d:%02d', $hora_selecionada, $m);
    
    $data_grafico = array_fill(0, 6, 0);
    foreach ($labels as $i => $time) {
        $sql = "SELECT COUNT(DISTINCT DUTY_COMPANYCODE) as total 
                FROM relatorios_servicos 
                WHERE '$time:00' BETWEEN START_TIME AND END_TIME 
                AND data_inicio_vigencia <= '$data_fim' AND data_fim_vigencia >= '$data_inicio'";
        $res = $conexao->query($sql);
        $data_grafico[$i] = (int)$res->fetch_assoc()['total'];
    }
    echo json_encode(['labels' => $labels, 'data' => $data_grafico]);

// --- LÓGICA 2: VIAGENS INICIADAS ---
} elseif ($tipo_grafico === 'viagens') {
    $labels = [];
    for ($m = 0; $m < 60; $m += 10) $labels[] = sprintf('%02d:%02d', $hora_selecionada, $m);
    
    $data_grafico = array_fill(0, 6, 0);
    $sql = "SELECT FLOOR(MINUTE(START_TIME) / 10) as idx, COUNT(*) as qtd 
            FROM relatorios_viagens 
            WHERE TRIP_ID != 0 AND HOUR(START_TIME) = '$hora_selecionada' 
            AND data_viagem BETWEEN '$data_inicio' AND '$data_fim'
            " . ($linha_selecionada !== 'todos' ? "AND ROUTE_ID = '$linha_selecionada'" : "") . "
            GROUP BY idx";
    
    $res = $conexao->query($sql);
    while($r = $res->fetch_assoc()) $data_grafico[(int)$r['idx']] = (int)$r['qtd'];
    echo json_encode(['labels' => $labels, 'data' => $data_grafico]);

// --- LÓGICA 3: FLUXO TERMINAIS E GARAGENS ---
} elseif ($tipo_grafico === 'locais_tabela') {
    $col_place = ($modo_local === 'inicio') ? 'START_PLACE' : 'END_PLACE';
    $col_time = ($modo_local === 'inicio') ? 'START_TIME' : 'END_TIME';

    $case_locais = "CASE 
        WHEN $col_place LIKE 'TA-%' OR $col_place = 'TA-IN' THEN 'Terminal Acapulco'
        WHEN $col_place LIKE 'TC-%' OR $col_place IN ('TCPI', 'TCPS', 'T-CENTRAL') THEN 'Terminal Central'
        WHEN $col_place = 'T-IRERE' THEN 'Terminal Irerê'
        WHEN $col_place = 'TOVER' THEN 'Terminal Ouro Verde'
        WHEN $col_place LIKE 'TSHOP%' THEN 'Terminal Shopping'
        WHEN $col_place LIKE 'TVX%' THEN 'Terminal Vivi Xavier'
        WHEN $col_place LIKE '%GARCIA%' THEN 'Garagem Garcia'
        WHEN $col_place LIKE '%G-BELGICA%' THEN 'Garagem Bélgica'
        WHEN $col_place LIKE '%G-IRERE%' THEN 'Garagem Irerê'
        WHEN $col_place LIKE '%G-GUAIRACA%' THEN 'Garagem Guairacá'
        WHEN $col_place LIKE '%G.GUARAV%' THEN 'Garagem Guaravera'
        WHEN $col_place LIKE '%G.LERROV%' THEN 'Garagem Lerroville'
        WHEN $col_place LIKE '%G.MARAV%' THEN 'Garagem Maravilha'
        WHEN $col_place LIKE '%G.NATA%' OR $col_place LIKE '%G.NATA2%' THEN 'Garagem Faz. Nata'
        WHEN $col_place LIKE '%G.PAIQUERE%' THEN 'Garagem Paiquerê'
        WHEN $col_place LIKE '%G.SLUIS%' THEN 'Garagem São Luiz'
        WHEN $col_place LIKE '%G.TAMARAN%' THEN 'Garagem Tamarana'
        ELSE NULL END";

    $sql = "SELECT $case_locais as local_nome, HOUR($col_time) as hora, COUNT(*) as qtd 
            FROM relatorios_viagens 
            WHERE data_viagem BETWEEN '$data_inicio' AND '$data_fim' 
            AND ($case_locais) IS NOT NULL 
            GROUP BY local_nome, hora ORDER BY local_nome, hora";
    
    $result = $conexao->query($sql);
    $matriz = [];
    while ($row = $result->fetch_assoc()) {
        $loc = $row['local_nome'];
        $hr = (int)$row['hora'];
        if (!isset($matriz[$loc])) { $matriz[$loc] = array_fill(0, 24, 0); $matriz[$loc]['total'] = 0; }
        $matriz[$loc][$hr] = (int)$row['qtd'];
        $matriz[$loc]['total'] += (int)$row['qtd'];
    }
    echo json_encode($matriz);
}
$conexao->close();
?>