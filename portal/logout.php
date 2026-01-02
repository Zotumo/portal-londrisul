<?php
// logout.php

// 1. Iniciar a sessão para poder manipulá-la
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Limpar todas as variáveis da sessão
// $_SESSION = array(); // Alternativa mais antiga
session_unset(); // Remove todas as variáveis de sessão

// 3. Destruir a sessão
// Isso remove os dados da sessão do armazenamento do servidor
session_destroy();

// 4. Redirecionar para a página inicial
// O usuário será visto como deslogado ao chegar em index.php
header("Location: index.php");
exit; // Garante que nenhum código adicional seja executado após o redirecionamento

?>