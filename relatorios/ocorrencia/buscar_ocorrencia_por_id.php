<?php
// =================================================================
//  Parceiro de Programação - Endpoint para buscar uma ocorrência por ID (v2)
// =================================================================

require 'config_ocorrencias.php';
header('Content-Type: application/json');

$id = $_GET['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID não fornecido.']);
    exit;
}

$sql = "SELECT * FROM registros_ocorrencias WHERE id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($ocorrencia = $result->fetch_assoc()) {
    // A formatação do horário já não é necessária, pois o campo agora é VARCHAR(5)
    // e o JavaScript já espera o formato HH:MM.
    echo json_encode(['success' => true, 'data' => $ocorrencia]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ocorrência não encontrada.']);
}

$stmt->close();
$conexao->close();
?>
