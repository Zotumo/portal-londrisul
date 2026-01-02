<?php
// portal/db_config.php - Configurações de Conexão Múltipla

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuração dos Nomes dos Bancos
define('DB_NAME_PORTAL', 'portal_motorista-v1'); // Banco original
define('DB_NAME_RELATORIOS', 'relatorio');       // Novo banco de dados

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // 1. Conexão Principal (Portal) - Mantém compatibilidade com o resto do site
    $dsn_portal = "mysql:host=".DB_HOST.";dbname=".DB_NAME_PORTAL.";charset=".DB_CHARSET;
    $pdo = new PDO($dsn_portal, DB_USER, DB_PASS, $options);

    // 2. Conexão Secundária (Relatórios) - Para a nova busca inteligente
    $dsn_relatorios = "mysql:host=".DB_HOST.";dbname=".DB_NAME_RELATORIOS.";charset=".DB_CHARSET;
    $pdo_relatorios = new PDO($dsn_relatorios, DB_USER, DB_PASS, $options);

} catch (\PDOException $e) {
    error_log("Erro de conexão DB: " . $e->getMessage());
    die("Erro interno de conexão. Contate o suporte.");
}
?>