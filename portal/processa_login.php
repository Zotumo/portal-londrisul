<?php
// processa_login.php

// 1. Iniciar a sessão SEMPRE no início
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Incluir o arquivo de configuração do banco de dados
require_once 'db_config.php'; // Garante que $pdo está disponível

// 3. Verificar se o formulário foi enviado (método POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 4. Obter os dados do formulário
    // Usamos ?? '' para evitar erros caso os campos não sejam enviados (embora sejam 'required' no HTML)
    $matricula = $_POST['matricula'] ?? '';
    $senha_digitada = $_POST['senha'] ?? '';

    // 5. Validação básica (campos vazios)
    if (empty($matricula) || empty($senha_digitada)) {
        // Se algum campo estiver vazio, define mensagem de erro e redireciona
        $_SESSION['login_error'] = "Por favor, preencha a matrícula e a senha.";
        header('Location: index.php');
        exit; // Interrompe a execução do script após redirecionar
    }

    // 6. Buscar o usuário no banco de dados pela matrícula
    try {
        // Prepara a consulta SQL usando Prepared Statements para segurança
        $sql = "SELECT id, nome, matricula, senha FROM motoristas WHERE matricula = :matricula LIMIT 1";
        $stmt = $pdo->prepare($sql);

        // Associa o valor da matrícula ao placeholder :matricula
        $stmt->bindParam(':matricula', $matricula, PDO::PARAM_STR);

        // Executa a consulta
        $stmt->execute();

        // Busca o resultado como um array associativo
        $motorista = $stmt->fetch(PDO::FETCH_ASSOC);

        // 7. Verificar se o motorista foi encontrado E se a senha está correta
        if ($motorista && password_verify($senha_digitada, $motorista['senha'])) {
            // --- Login bem-sucedido! ---

            // Regenera o ID da sessão para segurança (prevenir session fixation)
            session_regenerate_id(true);

            // Armazena informações do usuário na sessão
            $_SESSION['user_id'] = $motorista['id'];
            $_SESSION['user_name'] = $motorista['nome'];
            $_SESSION['user_matricula'] = $motorista['matricula'];
            // Você pode adicionar mais informações se necessário (ex: nível de acesso)

            // Limpa qualquer mensagem de erro de login anterior
            unset($_SESSION['login_error']);

            // Redireciona para a página principal (index.php)
            header('Location: index.php#minha-escala');
            exit;

        } else {
            // --- Falha no login (matrícula não encontrada ou senha incorreta) ---
            $_SESSION['login_error'] = "Matrícula ou senha inválida.";
            header('Location: index.php');
            exit;
        }

    } catch (PDOException $e) {
        // Em caso de erro no banco de dados durante a consulta
        error_log("Erro ao buscar motorista: " . $e->getMessage()); // Loga o erro real
        $_SESSION['login_error'] = "Ocorreu um erro interno. Tente novamente mais tarde."; // Mensagem genérica
        header('Location: index.php');
        exit;
    }

} else {
    // Se alguém tentar acessar processa_login.php diretamente via GET
    // Redireciona para a página inicial
    header('Location: index.php');
    exit;
}
?>