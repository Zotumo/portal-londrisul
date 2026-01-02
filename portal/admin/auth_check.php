<?php
// admin/auth_check.php
// Verifica se o administrador está logado. Deve ser incluído no topo das páginas protegidas.

// Garante que a sessão seja iniciada (caso ainda não tenha sido)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se as variáveis de sessão esperadas para um admin logado NÃO existem
if (!isset($_SESSION['admin_user_id']) || !isset($_SESSION['admin_username'])) {
    // Se não existem, o usuário não está logado como admin

    // Define uma mensagem de erro (opcional)
    $_SESSION['admin_login_error'] = "Acesso negado. Por favor, faça o login.";

    // Redireciona para a página de login do admin
    // ATENÇÃO: O path aqui assume que auth_check.php está na pasta admin/
    // Se você colocar este arquivo em outro lugar, ajuste o path 'login.php'
    header('Location: login.php');
    exit; // Interrompe a execução da página atual
}

// Se chegou até aqui, o admin está logado.
// Disponibiliza variáveis de sessão para a página que incluiu este arquivo (opcional)
$admin_id_logado = $_SESSION['admin_user_id'];
$admin_username_logado = $_SESSION['admin_username'];
$admin_nivel_acesso_logado = $_SESSION['admin_nivel_acesso'] ?? null; // Pega o nível de acesso

// Nota: A verificação de NÍVEL DE ACESSO para CADA página específica
// deverá ser feita DENTRO da própria página, após incluir este auth_check.php.
// Exemplo: if ($admin_nivel_acesso_logado !== 'Administrador') { die('Acesso negado a esta funcionalidade.'); }

?>