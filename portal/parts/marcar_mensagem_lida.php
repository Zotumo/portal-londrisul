<?php
// parts/marcar_mensagem_lida.php
// Marca uma mensagem específica como lida para o usuário logado

header('Content-Type: application/json; charset=utf-8');
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once dirname(__DIR__) . '/db_config.php';

$response = ['success' => false, 'message' => 'Erro desconhecido.'];

// Verifica login e conexão
if (!isset($_SESSION['user_id']) || $pdo === null) {
    $response['message'] = 'Não autorizado ou falha na conexão.';
    echo json_encode($response); exit;
}
$motorista_id_logado = $_SESSION['user_id'];

// Verifica se o ID da mensagem foi enviado via POST
if (!isset($_POST['msg_id']) || !filter_var($_POST['msg_id'], FILTER_VALIDATE_INT)) {
     $response['message'] = 'ID da mensagem inválido.';
     echo json_encode($response); exit;
}
$mensagem_id = (int)$_POST['msg_id'];

// Tenta atualizar o banco de dados
try {
    // Atualiza APENAS se a mensagem pertencer ao usuário e AINDA não estiver lida
    $sql = "UPDATE mensagens_motorista
            SET data_leitura = NOW()
            WHERE id = :msg_id
              AND motorista_id = :motorista_id
              AND data_leitura IS NULL"; // Garante que só atualiza uma vez

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':msg_id', $mensagem_id, PDO::PARAM_INT);
    $stmt->bindParam(':motorista_id', $motorista_id_logado, PDO::PARAM_INT);
    $stmt->execute();

    // Verifica se alguma linha foi afetada (se a mensagem foi realmente marcada como lida agora)
    if ($stmt->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Mensagem marcada como lida.';
    } else {
        // Nenhuma linha afetada: ou a mensagem não existe, não pertence ao usuário, ou já estava lida
        $response['success'] = false; // Ou true se não considerar erro já estar lida? Vamos usar false por clareza.
        $response['message'] = 'Mensagem não encontrada, não pertence a você ou já estava lida.';
    }

} catch (PDOException $e) {
    error_log("Erro ao marcar msg ID {$mensagem_id} como lida: " . $e->getMessage());
    $response['message'] = 'Erro no banco de dados ao marcar mensagem.';
}

echo json_encode($response);
exit;
?>