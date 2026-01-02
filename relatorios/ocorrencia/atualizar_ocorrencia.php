<?php
// =================================================================
//  Parceiro de Programação - Endpoint para Atualizar Ocorrências (v2)
// =================================================================

require 'config_ocorrencias.php';
header('Content-Type: application/json');

$id = $_POST['edit_id'] ?? 0;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Erro: ID da ocorrência não encontrado.']);
    exit;
}

// Prepara os dados para atualização
$workid = !empty($_POST['edit_workid']) ? (int)$_POST['edit_workid'] : null;
$motorista_atual = !empty($_POST['edit_motorista_atual']) ? (int)$_POST['edit_motorista_atual'] : null;
$linha = $_POST['edit_linha'] ?? null;
$carro_atual = $_POST['edit_carro_atual'] ?? null;
$ocorrencia = $_POST['edit_ocorrencia'] ?? null;
$incidente = $_POST['edit_incidente'] ?? null;
$socorro = isset($_POST['edit_socorro']) ? 1 : 0;
$horario_linha = !empty($_POST['edit_horario_linha']) ? $_POST['edit_horario_linha'] : null;
$terminal = $_POST['edit_terminal'] ?? null;
$carro_pos = $_POST['edit_carro_pos'] ?? null;
$monitor = $_POST['edit_monitor'] ?? null;
$fiscal = $_POST['edit_fiscal'] ?? null;
$observacao = !empty($_POST['edit_observacao']) ? trim($_POST['edit_observacao']) : null;

$sql = "UPDATE registros_ocorrencias SET 
            workid = ?, motorista_atual = ?, linha = ?, carro_atual = ?, ocorrencia = ?, 
            incidente = ?, socorro = ?, horario_linha = ?, terminal = ?, carro_pos = ?, 
            monitor = ?, fiscal = ?, observacao = ?
        WHERE id = ?";

$stmt = $conexao->prepare($sql);
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar a query: ' . $conexao->error]);
    exit;
}

// Tipos corrigidos: iissssissssssi (13 campos + id)
$stmt->bind_param('iissssissssssi', 
    $workid, $motorista_atual, $linha, $carro_atual, $ocorrencia, $incidente, 
    $socorro, $horario_linha, $terminal, $carro_pos, $monitor, $fiscal, $observacao, $id
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Ocorrência atualizada com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar a ocorrência: ' . $stmt->error]);
}

$stmt->close();
$conexao->close();
?>
