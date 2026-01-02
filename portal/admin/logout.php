<?php
// admin/logout.php
// Destrói a sessão do administrador e redireciona para o login

// 1. Inicia a sessão (necessário para acessar e modificar a sessão atual)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Limpa todas as variáveis da sessão
$_SESSION = array();

// 3. Se estiver usando cookies de sessão, apaga o cookie
// Nota: Isso destruirá a sessão, e não apenas os dados da sessão!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Finalmente, destrói a sessão
session_destroy();

// 5. Redireciona para a página de login do admin
header("Location: login.php");
exit; // Garante que o script pare aqui após o redirecionamento
?>