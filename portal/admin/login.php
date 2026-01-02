<?php
// admin/login.php
// Página de login para a área administrativa

// Iniciar sessão para checar se já está logado ou para exibir erros
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Se já estiver logado como admin, redireciona para o painel principal
if (isset($_SESSION['admin_user_id'])) {
    header('Location: index.php');
    exit;
}

// Verificar se há mensagem de erro da tentativa anterior
$error_message = '';
if (isset($_SESSION['admin_login_error'])) {
    $error_message = $_SESSION['admin_login_error'];
    unset($_SESSION['admin_login_error']); // Limpa após ler
}

$page_title = 'Login Admin'; // Define o título da página

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Portal do Motorista</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../style.css?v=<?php echo filemtime('../style.css'); ?>"> <style>
        /* Estilos simples para centralizar o login */
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa; /* Fundo cinza claro */
        }
        .login-container {
            max-width: 400px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card shadow">
            <div class="card-header text-center" style="background-color: var(--cmtu-vermelho); color: white;"> <h4 class="mb-0"><i class="fas fa-user-shield"></i> Painel Administrativo</h4>
            </div>
            <div class="card-body">
                <h5 class="card-title text-center mb-4">Login de Administrador</h5>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form action="processa_login_admin.php" method="POST" id="form-admin-login"  novalidate>
                    <div class="form-group">
                        <label for="username"><i class="fas fa-user"></i> Usuário:</label>
                        <input type="text" class="form-control" id="username" name="username" required placeholder="Digite seu usuário">
                    </div>
                    <div class="form-group">
                        <label for="senha"><i class="fas fa-lock"></i> Senha:</label>
                        <input type="password" class="form-control" id="senha" name="senha" required placeholder="Digite sua senha">
                    </div>
                    <button type="submit" class="btn btn-danger btn-block"><i class="fas fa-sign-in-alt"></i> Entrar</button> </form>
            </div>
             <div class="card-footer text-center">
                <small><a href="../index.php">&larr; Voltar para o Portal do Motorista</a></small>
             </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>