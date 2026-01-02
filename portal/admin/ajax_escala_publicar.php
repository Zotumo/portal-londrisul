<?php
// admin/ajax_escala_publicar.php
require_once 'auth_check.php';
require_once '../db_config.php';

header('Content-Type: application/json');

// Apenas gestores podem publicar
$niveis_perm = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_perm)) {
    echo json_encode(['sucesso' => false, 'msg' => 'Sem permissão.']);
    exit;
}

$acao = $_POST['acao'] ?? '';
$data = $_POST['data'] ?? '';

try {
    if ($acao === 'publicar_dia' && !empty($data)) {
        // Atualiza todas as escalas daquela data para publicadas
        $sql = "UPDATE motorista_escalas SET escala_publicada = 1 WHERE data = :data";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':data' => $data]);
        
        $afetados = $stmt->rowCount();
        
        echo json_encode(['sucesso' => true, 'msg' => "Escala do dia " . date('d/m/Y', strtotime($data)) . " publicada! ($afetados registros atualizados)."]);
        exit;
    }
    
    if ($acao === 'despublicar_dia' && !empty($data)) {
        $sql = "UPDATE motorista_escalas SET escala_publicada = 0 WHERE data = :data";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':data' => $data]);
        
        echo json_encode(['sucesso' => true, 'msg' => "Escala do dia " . date('d/m/Y', strtotime($data)) . " ocultada (Rascunho)."]);
        exit;
    }

    throw new Exception("Ação inválida.");

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'msg' => 'Erro: ' . $e->getMessage()]);
}
?>