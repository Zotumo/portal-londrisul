<?php
// processa_troca_senha.php

// 1. Iniciar sessão para verificar login e definir mensagens
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    // Se não estiver logado, redireciona para o início (ou página de login)
    header('Location: index.php');
    exit;
}

// 3. Verificar se o formulário foi enviado via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 4. Incluir config do banco
    require_once 'db_config.php';

    // Verifica conexão PDO
    if ($pdo === null) {
        $_SESSION['senha_error'] = "Erro de conexão com o banco de dados.";
        header('Location: index.php#senha-content'); // Volta para a aba de senha
        exit;
    }

    // 5. Obter dados do formulário
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_nova_senha = $_POST['confirma_nova_senha'] ?? '';
    $motorista_id = $_SESSION['user_id'];

    // 6. Validações
    if (empty($senha_atual) || empty($nova_senha) || empty($confirma_nova_senha)) {
        $_SESSION['senha_error'] = "Todos os campos são obrigatórios.";
        header('Location: index.php#senha-content');
        exit;
    }

    if (strlen($nova_senha) < 6) { // Exemplo: mínimo 6 caracteres
        $_SESSION['senha_error'] = "A nova senha deve ter pelo menos 6 caracteres.";
        header('Location: index.php#senha-content');
        exit;
    }

    if ($nova_senha !== $confirma_nova_senha) {
        $_SESSION['senha_error'] = "A nova senha e a confirmação não coincidem.";
        header('Location: index.php#senha-content');
        exit;
    }

    // 7. Verificar a senha atual no banco
    try {
        $sql_check = "SELECT senha FROM motoristas WHERE id = :id";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->bindParam(':id', $motorista_id, PDO::PARAM_INT);
        $stmt_check->execute();
        $hash_atual_db = $stmt_check->fetchColumn();

        if (!$hash_atual_db || !password_verify($senha_atual, $hash_atual_db)) {
            // Senha atual incorreta
            $_SESSION['senha_error'] = "A senha atual digitada está incorreta.";
            header('Location: index.php#senha-content');
            exit;
        }

        // 8. Se a senha atual está correta, gerar hash da nova senha e atualizar
        $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

        $sql_update = "UPDATE motoristas SET senha = :novo_hash WHERE id = :id";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->bindParam(':novo_hash', $novo_hash, PDO::PARAM_STR);
        $stmt_update->bindParam(':id', $motorista_id, PDO::PARAM_INT);

        if ($stmt_update->execute()) {
            // Sucesso!
            $_SESSION['senha_success'] = "Senha alterada com sucesso!";
            // Limpa erro antigo, se houver
            unset($_SESSION['senha_error']);
            header('Location: index.php#senha-content');
            exit;
        } else {
            // Erro ao atualizar
            $_SESSION['senha_error'] = "Erro ao atualizar a senha no banco de dados.";
            header('Location: index.php#senha-content');
            exit;
        }

    } catch (PDOException $e) {
        error_log("Erro ao trocar senha motorista ID {$motorista_id}: " . $e->getMessage());
        $_SESSION['senha_error'] = "Ocorreu um erro interno ao processar sua solicitação.";
        header('Location: index.php#senha-content');
        exit;
    }

} else {
    // Se não for POST, redireciona
    header('Location: index.php');
    exit;
}
?>