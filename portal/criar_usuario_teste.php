<?php
// criar_usuario_teste.php
// ATENÇÃO: Script temporário para inserir um usuário de teste.
// Exclua ou renomeie este arquivo após o uso!

require_once 'db_config.php'; // Inclui a conexão com o banco ($pdo)

// --- Defina os dados do motorista de teste ---
$nome_teste = "Motorista Teste";
$matricula_teste = "12345";        // <<< Use esta matrícula para logar
$senha_plana_teste = "senha123";   // <<< Use esta senha para logar
// -----------------------------------------

echo "<h1>Criar Usuário de Teste</h1>";

// 1. Criptografar a senha
// PASSWORD_DEFAULT usa o algoritmo mais forte disponível no PHP (atualmente bcrypt)
$senha_hash = password_hash($senha_plana_teste, PASSWORD_DEFAULT);

if ($senha_hash === false) {
    die("<p style='color: red;'>Erro ao gerar o hash da senha.</p>");
}

echo "<p>Nome: " . htmlspecialchars($nome_teste) . "</p>";
echo "<p>Matrícula: " . htmlspecialchars($matricula_teste) . "</p>";
echo "<p>Senha Plana: " . htmlspecialchars($senha_plana_teste) . "</p>";
echo "<p>Senha Hash (a ser salva no banco): " . htmlspecialchars($senha_hash) . "</p>";
echo "<hr>";

// 2. Preparar e executar a inserção no banco de dados
try {
    // Verificar se a matrícula já existe
    $sql_check = "SELECT id FROM motoristas WHERE matricula = :matricula LIMIT 1";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->bindParam(':matricula', $matricula_teste, PDO::PARAM_STR);
    $stmt_check->execute();

    if ($stmt_check->fetch()) {
        // Matrícula já existe
        echo "<p style='color: orange; font-weight: bold;'>AVISO: Motorista com matrícula '" . htmlspecialchars($matricula_teste) . "' já existe no banco de dados. Nenhum novo usuário foi inserido.</p>";
    } else {
        // Matrícula não existe, pode inserir
        $sql_insert = "INSERT INTO motoristas (nome, matricula, senha) VALUES (:nome, :matricula, :senha)";
        $stmt_insert = $pdo->prepare($sql_insert);

        // Associar os parâmetros
        $stmt_insert->bindParam(':nome', $nome_teste, PDO::PARAM_STR);
        $stmt_insert->bindParam(':matricula', $matricula_teste, PDO::PARAM_STR);
        $stmt_insert->bindParam(':senha', $senha_hash, PDO::PARAM_STR); // Salva o HASH!

        // Executar a inserção
        if ($stmt_insert->execute()) {
            echo "<p style='color: green; font-weight: bold;'>SUCESSO: Motorista de teste inserido no banco de dados!</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>ERRO: Falha ao inserir motorista no banco de dados.</p>";
        }
    }

} catch (PDOException $e) {
    // Erro na conexão ou execução SQL
    echo "<p style='color: red; font-weight: bold;'>ERRO PDO: Não foi possível executar a operação no banco de dados.</p>";
    // Em um cenário real, logar o erro $e->getMessage() em vez de exibir
    error_log("Erro em criar_usuario_teste.php: " . $e->getMessage());
}

echo "<hr>";
echo "<p>Pronto. Agora você pode tentar fazer login com a matrícula <strong>" . htmlspecialchars($matricula_teste) . "</strong> e a senha <strong>" . htmlspecialchars($senha_plana_teste) . "</strong>.</p>";
echo "<p style='color: red; font-weight: bold;'>Lembre-se de excluir ou renomear este arquivo ('criar_usuario_teste.php') do servidor após o teste por motivos de segurança!</p>";

?>