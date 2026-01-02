<?php
// =================================================================
//  Parceiro de Programação - Endpoint para Excluir Ocorrências
// =================================================================

require 'config_ocorrencias.php';
header('Content-Type: application/json');

// Recebe o ID via POST para mais segurança
$id = $_POST['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID não fornecido.']);
    exit;
}

$sql = "DELETE FROM registros_ocorrencias WHERE id = ?";
$stmt = $conexao->prepare($sql);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar a query: ' . $conexao->error]);
    exit;
}

$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    // Verifica se alguma linha foi de fato afetada
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Ocorrência excluída com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhuma ocorrência encontrada com o ID fornecido.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir a ocorrência: ' . $stmt->error]);
}

$stmt->close();
$conexao->close();
?>
