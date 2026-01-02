<?php
// =================================================================
//  Parceiro de Programação - Script para Exportar Ocorrências (CSV)
//  v2.0 - Corrigida quebra de linha no campo de observação
// =================================================================

require 'config_ocorrencias.php';

// --- Lógica de Filtros (replicada de buscar_ocorrencias.php) ---
$where_conditions = [];
$params = [];
$types = '';

$filtro_map = [
    'workid_filtro' => 'workid', 'motorista_filtro' => 'motorista_atual', 'linha_filtro' => 'linha',
    'carro_filtro' => 'carro_atual', 'ocorrencia_filtro' => 'ocorrencia', 'incidente_filtro' => 'incidente',
    'socorro_filtro' => 'socorro', 'horario_linha_filtro' => 'horario_linha', 'terminal_filtro' => 'terminal',
    'carro_pos_filtro' => 'carro_pos', 'monitor_filtro' => 'monitor'
];

$data_inicio = $_GET['data_inicio_filtro'] ?? null;
$data_fim = $_GET['data_fim_filtro'] ?? null;

if ($data_inicio && $data_fim) {
    $where_conditions[] = "(data_ocorrencia BETWEEN ? AND ?)";
    $params[] = $data_inicio; $params[] = $data_fim; $types .= 'ss';
} elseif ($data_inicio) {
    $where_conditions[] = "data_ocorrencia >= ?";
    $params[] = $data_inicio; $types .= 's';
} elseif ($data_fim) {
    $where_conditions[] = "data_ocorrencia <= ?";
    $params[] = $data_fim; $types .= 's';
}

$other_filters = [];
foreach ($filtro_map as $key => $column) {
    if (isset($_GET[$key]) && $_GET[$key] !== '') {
        $value = $_GET[$key];
        if ($column === 'workid' || $column === 'motorista_atual') {
            $other_filters[] = "{$column} LIKE ?"; $params[] = "%{$value}%";
        } else if ($column === 'horario_linha') {
            $other_filters[] = "horario_linha = ?"; $params[] = $value;
        } else {
            $other_filters[] = "{$column} = ?"; $params[] = $value;
        }
        $types .= 's';
    }
}

if (!empty($other_filters)) {
    $where_conditions[] = "(" . implode(' OR ', $other_filters) . ")";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(' AND ', $where_conditions) : "";

// --- Preparação do Arquivo CSV ---
$filename_data_part = date('Y-m-d');
if ($data_inicio && $data_fim) {
    $filename_data_part = "{$data_inicio}_a_{$data_fim}";
}
$filename = "ocorrencias_{$filename_data_part}.csv";


header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// --- Cabeçalho do CSV ---
fputcsv($output, [
    'ID', 'Data', 'Horario', 'WorkID', 'Motorista', 'Linha', 'Carro Atual', 
    'Ocorrencia', 'Incidente', 'Socorro', 'Horario Linha', 'Terminal', 
    'Carro Pos', 'Monitor', 'Fiscal', 'Observacao', 'Registado Em'
]);

// --- Busca e Escrita dos Dados ---
$sql = "SELECT * FROM registros_ocorrencias {$where_clause} ORDER BY id ASC";
$stmt = $conexao->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Converte o booleano de 'socorro' para 'Sim'/'Nao' para clareza no CSV
        $row['socorro'] = $row['socorro'] ? 'Sim' : 'Nao';

        // *** CORREÇÃO APLICADA AQUI ***
        // Remove quebras de linha da observação para não quebrar a estrutura do CSV
        if (isset($row['observacao'])) {
            $row['observacao'] = str_replace(["\r\n", "\r", "\n"], " ", $row['observacao']);
        }
        
        // Tratamento de encoding para compatibilidade com Excel
        $row_encoded = array_map('utf8_decode', $row);
        fputcsv($output, $row_encoded);
    }
}

$stmt->close();
$conexao->close();
exit();
?>

