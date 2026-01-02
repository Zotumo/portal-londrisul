<?php
// =================================================================
//  Parceiro de Programação - Configuração do Banco de Dados
// =================================================================

// Define o fuso horário para garantir que a data e hora estejam corretas
date_default_timezone_set('America/Sao_Paulo');

$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "relatorio"; // Usando o mesmo banco de dados

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    // Em um ambiente de produção, seria melhor logar o erro do que exibi-lo
    die("Falha na conexão com o banco de dados: " . $conexao->connect_error);
}

$conexao->set_charset("utf8mb4");
?>
