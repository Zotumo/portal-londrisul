<?php
// admin/processa_login_admin.php

// 1. Iniciar a sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Incluir config do banco (voltando um nível de diretório)
require_once '../db_config.php';

// 3. Verificar se o método é POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 4. Obter dados do formulário
    $username = $_POST['username'] ?? '';
    $senha_digitada = $_POST['senha'] ?? '';

    // 5. Validação básica
    if (empty($username) || empty($senha_digitada)) {
        $_SESSION['admin_login_error'] = "Usuário e senha são obrigatórios.";
        header('Location: login.php'); // Volta para o login do admin
        exit;
    }

    // 6. Verificar conexão PDO
    if ($pdo === null) {
         error_log("Erro login admin: Conexão PDO nula.");
         $_SESSION['admin_login_error'] = "Erro interno ao conectar ao banco de dados.";
         header('Location: login.php');
         exit;
    }

    // 7. Buscar o administrador no banco pelo username
    try {
        $sql = "SELECT id, nome, username, senha, nivel_acesso FROM administradores WHERE username = :username LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // 8. Verificar se encontrou e se a senha confere
        if ($admin && password_verify($senha_digitada, $admin['senha'])) {
            // --- Login de Admin bem-sucedido ---
            session_regenerate_id(true); // Regenera ID da sessão

            // Guarda dados do admin na sessão (prefixo 'admin_' para evitar conflito)
            $_SESSION['admin_user_id'] = $admin['id'];
            $_SESSION['admin_user_name'] = $admin['nome'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_nivel_acesso'] = $admin['nivel_acesso']; // Guarda o nível!

            unset($_SESSION['admin_login_error']); // Limpa erro anterior

            header('Location: index.php'); // Redireciona para o dashboard do admin
            exit;

        } else {
            // --- Falha no login ---
             error_log("Falha no login admin para username: " . $username); // Log para debug
            $_SESSION['admin_login_error'] = "Usuário ou senha inválidos.";
            header('Location: login.php');
            exit;
        }

    } catch (PDOException $e) {
        error_log("Erro PDO login admin: " . $e->getMessage());
        $_SESSION['admin_login_error'] = "Erro interno no servidor. Tente novamente.";
        header('Location: login.php');
        exit;
    }

} else {
    // Acesso via GET não permitido
    header('Location: login.php');
    exit;
}
?>