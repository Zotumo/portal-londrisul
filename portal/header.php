<?php
// header.php (Com Timezone Ajustado)

// DEFINE O FUSO HORÁRIO CORRETO PARA O BRASIL/LONDRINA
date_default_timezone_set('America/Sao_Paulo');

// 1. Iniciar Sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Incluir Config BD
require_once 'db_config.php';


// 3. Verificar Erro de Login (lido aqui para ser usado no footer.php)
$login_error_message = '';
if (isset($_SESSION['login_error'])) {
    $login_error_message = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

// 4. Verificar Status de Login (SÓ se $pdo NÃO for null)
$usuario_logado = false;
$nome_usuario = '';
$unread_message_count = 0; // <<<<< INICIALIZA A CONTAGEM
if ($pdo !== null) { // Só verifica o login se a conexão com o banco foi bem-sucedida
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {
        // Poderia adicionar uma verificação extra no banco aqui se quisesse ter certeza
        // que o usuário da sessão ainda é válido, mas por ora, confiar na sessão é suficiente.
        $usuario_logado = true;
		$nome_usuario = $_SESSION['user_name'] ?? 'Motorista';

    // <<< INÍCIO - BUSCA CONTAGEM DE MENSAGENS NÃO LIDAS >>>
    try {
         $sql_count_msg = "SELECT COUNT(*) FROM mensagens_motorista
                           WHERE motorista_id = :motorista_id AND data_leitura IS NULL";
         $stmt_count_msg = $pdo->prepare($sql_count_msg);
         $stmt_count_msg->bindParam(':motorista_id', $_SESSION['user_id'], PDO::PARAM_INT);
         $stmt_count_msg->execute();
         $unread_message_count = (int) $stmt_count_msg->fetchColumn();
    } catch (PDOException $e) {
         error_log("Erro ao contar mensagens não lidas: " . $e->getMessage());
         $unread_message_count = 0; // Define 0 em caso de erro
    }
    // <<< FIM - BUSCA CONTAGEM DE MENSAGENS NÃO LIDAS >>>
    }
} else {
    // Se não há conexão com o banco, força o estado de deslogado
    // ou mantém como estava, dependendo da sua regra de negócio.
    // Forçar deslogado é mais seguro se funcionalidades dependem do banco.
     $usuario_logado = false;
     $nome_usuario = '';
     // Poderia até destruir a sessão se a conexão falhar, mas talvez seja extremo.
     // session_unset(); session_destroy();
}


// 5. Definir Título Padrão (pode ser sobrescrito pela página que incluir o header)
if (!isset($page_title)) {
    $page_title = 'Portal do Motorista';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Portal do Motorista</title>
    <link rel="stylesheet" href="../portal/bootstrap.min.css">
    <link rel="stylesheet" href="../portal/all.min.css">
    <link rel="stylesheet" href="../portal/style.css?v=<?php echo filemtime('style.css'); ?>">
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
	<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
</head>
<body>
    <?php
        // Opcional: Exibir um alerta visual se a conexão com o banco falhou ($pdo === null)
        if ($pdo === null && basename($_SERVER['PHP_SELF']) != 'erro_db.php') {
             echo '<div class="alert alert-danger text-center mb-0" role="alert" style="border-radius: 0;"><strong>Atenção:</strong> Falha na conexão com o banco de dados. Funcionalidades limitadas.</div>';
        }
    ?>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark"> <a class="navbar-brand" href="index.php">Portal do Motorista</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                 <ul class="navbar-nav ml-auto">
                     <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>"> <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Início</a> </li>
                     <li class="nav-item"> <a class="nav-link" href="index.php#busca-rapida"><i class="fas fa-list-alt"></i> Tabela Horária</a> </li>
                     <li class="nav-item" <?php if (!$usuario_logado) echo 'style="display: none;"'; ?>>
    <a class="nav-link" href="index.php#minha-escala"><i class="fas fa-user-clock"></i> Minha Escala</a>
    </li>
                     <li class="nav-item <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['todas_noticias.php', 'ver_noticia.php'])) ? 'active' : ''; ?>"> <a class="nav-link" href="todas_noticias.php"><i class="fas fa-newspaper"></i> Notícias</a> </li>
                     <li class="nav-item" id="nav-login" <?php if ($usuario_logado) echo 'style="display: none;"'; ?>> <a class="nav-link" href="#" data-toggle="modal" data-target="#loginModal"> <i class="fas fa-sign-in-alt"></i> Login </a> </li>
                     <li class="nav-item" id="nav-perfil" <?php if (!$usuario_logado) echo 'style="display: none;"'; ?>> <a class="nav-link" href="#"><i class="fas fa-user"></i> <span id="nome-usuario-nav"><?php echo htmlspecialchars($nome_usuario); ?></span></a> </li>
                     <li class="nav-item" id="nav-logout" <?php if (!$usuario_logado) echo 'style="display: none;"'; ?>> <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a> </li>
                 </ul>
             </div>
         </nav>
     </header>