<?php
// =================================================================
//  Parceiro de Programação - Endpoint para Buscar/Filtrar Ocorrências (v5 - Paginação Melhorada)
// =================================================================

require 'config_ocorrencias.php';
header('Content-Type: application/json');

// --- Lógica de Paginação ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15; // Registos por página
$offset = ($page - 1) * $limit;

// --- Lógica de Filtros com OR ---
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

// --- Query para contar o total de resultados ---
$sql_count = "SELECT COUNT(*) as total FROM registros_ocorrencias {$where_clause}";
$stmt_count = $conexao->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_results = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_results / $limit);
$stmt_count->close();

// --- Query para buscar os dados da página atual ---
$sql = "SELECT * FROM registros_ocorrencias {$where_clause} ORDER BY id DESC LIMIT ? OFFSET ?";
$params_paginated = $params; // Cria uma cópia para não afetar a contagem
$params_paginated[] = $limit;
$params_paginated[] = $offset;
$types_paginated = $types . 'ii'; // Adiciona os tipos para LIMIT e OFFSET

$stmt = $conexao->prepare($sql);
$stmt->bind_param($types_paginated, ...$params_paginated);
$stmt->execute();
$result = $stmt->get_result();

// --- Geração do HTML da Tabela ---
$html_tabela = '';
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $html_tabela .= '<tr class="bg-white border-b hover:bg-gray-50">';
        $html_tabela .= '  <td class="px-4 py-2">' . (new DateTime($row['data_ocorrencia'] . ' ' . $row['horario_ocorrencia']))->format('d/m/Y H:i') . '</td>';
        $html_tabela .= '  <td class="px-4 py-2">' . htmlspecialchars($row['workid'] ?? '') . '</td>';
        $html_tabela .= '  <td class="px-4 py-2">' . htmlspecialchars($row['motorista_atual'] ?? '') . '</td>';
        $html_tabela .= '  <td class="px-4 py-2">' . htmlspecialchars($row['linha'] ?? '') . '</td>';
        $html_tabela .= '  <td class="px-4 py-2">' . htmlspecialchars($row['carro_atual'] ?? '') . '</td>';
        $html_tabela .= '  <td class="px-4 py-2">' . htmlspecialchars($row['ocorrencia'] ?? '') . '</td>';
        $html_tabela .= '  <td class="px-4 py-2">' . htmlspecialchars($row['incidente'] ?? '') . '</td>';
        $html_tabela .= '  <td class="px-4 py-2 text-center">' . ($row['socorro'] ? 'Sim' : 'Não') . '</td>';
        $html_tabela .= '  <td class="px-4 py-2">' . htmlspecialchars($row['horario_linha'] ?? '') . '</td>';
        $html_tabela .= '  <td class="px-4 py-2">' . htmlspecialchars($row['terminal'] ?? '') . '</td>';
        $html_tabela .= '  <td class="px-4 py-2">' . htmlspecialchars($row['carro_pos'] ?? '') . '</td>';
        $html_tabela .= '  <td class="px-4 py-2">' . htmlspecialchars($row['monitor'] ?? '') . '</td>';
        $html_tabela .= '  <td class="px-4 py-2 max-w-xs whitespace-normal break-words">' . htmlspecialchars($row['observacao'] ?? '') . '</td>';
        $html_tabela .= '  <td class="px-4 py-2 text-center whitespace-nowrap">';
        $html_tabela .= '      <button class="btn-editar bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600" data-id="' . $row['id'] . '">Editar</button>';
        $html_tabela .= '      <button class="btn-excluir bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 ml-1" data-id="' . $row['id'] . '">Excluir</button>';
        $html_tabela .= '  </td>';
        $html_tabela .= '</tr>';
    }
} else {
    $html_tabela = '<tr><td colspan="14" class="text-center p-4">Nenhuma ocorrência encontrada.</td></tr>';
}

// --- Geração do HTML da Paginação (NOVA LÓGICA) ---
$html_paginacao = '';
if ($total_pages > 1) {
    // Container principal que centraliza tudo
    $html_paginacao .= '<div class="flex flex-col items-center">'; 
    // Container para os botões de navegação
    $html_paginacao .= '<div class="flex items-center justify-center space-x-1">';

    // Botão "Anterior"
    $prev_disabled = ($page <= 1) ? 'disabled' : '';
    $html_paginacao .= '<button class="btn-pagina px-3 py-1 text-sm font-medium text-gray-700 bg-white rounded-md hover:bg-gray-100 disabled:bg-gray-200 disabled:text-gray-500 disabled:cursor-not-allowed" data-page="' . ($page - 1) . '" ' . $prev_disabled . '>Anterior</button>';

    $range = 2; // Define quantos links numéricos aparecerão antes e depois da página atual
    $show_dots = false;

    for ($i = 1; $i <= $total_pages; $i++) {
        // Condições para mostrar um botão de página:
        // 1. É a primeira página (i == 1)
        // 2. É a última página (i == $total_pages)
        // 3. Está dentro do alcance da página atual (ex: se a pág atual é 5, mostra 3, 4, 5, 6, 7)
        if ($i == 1 || $i == $total_pages || ($i >= $page - $range && $i <= $page + $range)) {
            if ($i == $page) {
                // Estilo para a página atualmente selecionada
                $html_paginacao .= '<button class="btn-pagina px-3 py-1 text-sm font-medium text-white bg-blue-600 rounded-md" data-page="' . $i . '">' . $i . '</button>';
            } else {
                // Estilo para as outras páginas
                $html_paginacao .= '<button class="btn-pagina px-3 py-1 text-sm font-medium text-gray-700 bg-white rounded-md hover:bg-gray-100" data-page="' . $i . '">' . $i . '</button>';
            }
            $show_dots = true;
        } elseif ($show_dots) {
            // Adiciona "..." para indicar páginas ocultas e evita repetição
            $html_paginacao .= '<span class="px-3 py-1 text-sm">...</span>';
            $show_dots = false;
        }
    }

    // Botão "Próximo"
    $next_disabled = ($page >= $total_pages) ? 'disabled' : '';
    $html_paginacao .= '<button class="btn-pagina px-3 py-1 text-sm font-medium text-gray-700 bg-white rounded-md hover:bg-gray-100 disabled:bg-gray-200 disabled:text-gray-500 disabled:cursor-not-allowed" data-page="' . ($page + 1) . '" ' . $next_disabled . '>Próximo</button>';

    $html_paginacao .= '</div>'; // Fim do container dos botões
    // Adiciona um texto informativo abaixo dos botões
    $html_paginacao .= '<div class="text-center text-sm text-gray-600 mt-2">Página ' . $page . ' de ' . $total_pages . ' (' . $total_results . ' registros)</div>';
    $html_paginacao .= '</div>'; // Fim do container principal
}


$stmt->close();
$conexao->close();

// --- Resposta JSON ---
echo json_encode([
    'html' => $html_tabela,
    'pagination' => $html_paginacao
]);
?>
