<?php
// admin/admin_header.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Tenta carregar auth_check.php do diretório atual primeiro.
// Se não encontrar (ex: header incluído de uma subpasta como 'relatorios'),
// tenta carregar do diretório pai.
if (file_exists('auth_check.php')) {
    require_once 'auth_check.php';
} elseif (file_exists('../auth_check.php')) {
    require_once '../auth_check.php';
} else {
    // Fallback se não encontrar de jeito nenhum (deve ser raro com a estrutura correta)
    // Idealmente, as páginas que incluem o header já devem ter feito o auth_check
    // ou o caminho deve ser ajustado corretamente no require_once da página que inclui.
    // Esta verificação aqui é mais uma salvaguarda.
    if (!isset($_SESSION['admin_user_id'])) { // Verifica se as variáveis de sessão já existem
        header('Location: login.php'); // Pode precisar ajustar para ../login.php dependendo do contexto
        exit;
    }
}


if (!isset($page_title)) {
    $page_title = 'Admin Portal';
}

// Permissões para itens de menu
$niveis_acesso_ver_noticias = ['Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
$niveis_acesso_ver_mensagens = ['Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
$niveis_gerenciar_escala_planejada = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
$niveis_consultar_escala_diaria = ['Agente de Terminal', 'Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
$niveis_acesso_ver_tabelas = ['CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
$niveis_acesso_ver_linhas_rotas = ['Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
$niveis_acesso_ver_locais = ['Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
// NOVA PERMISSÃO PARA FROTA (VEÍCULOS)
$niveis_acesso_gerenciar_frota = ['Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador']; //
$niveis_acesso_ver_motoristas = ['Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
$niveis_acesso_ver_usuarios_admin = ['Supervisores', 'Gerência', 'Administrador'];
$niveis_acesso_ver_relatorios = ['CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];

// --- Lógica para determinar o caminho base para os links do menu ---
$path_to_admin_root_for_links = '';
$current_script_directory = dirname($_SERVER['SCRIPT_NAME']); // Ex: /v1/admin ou /v1/admin/relatorios

// Define o caminho base esperado para a pasta admin
// IMPORTANTE: Ajuste $expected_admin_path se a sua pasta admin não estiver diretamente em /v1/admin/
// Por exemplo, se estiver em /projetoX/admin/, mude para '/projetoX/admin'
$expected_admin_path = '/v1/admin'; // Assumindo que a pasta admin está em /v1/admin/

// Normaliza as barras para comparação (útil em Windows)
$current_script_directory = str_replace('\\', '/', $current_script_directory);
$expected_admin_path = str_replace('\\', '/', $expected_admin_path);

// Verifica se o script atual está em uma subpasta de 'admin' (ex: 'admin/relatorios')
if (strpos($current_script_directory, $expected_admin_path . '/') !== false && $current_script_directory !== $expected_admin_path) {
    // Conta quantos níveis de subpasta existem
    $relative_path = substr($current_script_directory, strlen($expected_admin_path) + 1); // Pega o que vem depois de /admin/
    $depth = substr_count($relative_path, '/');
    $path_to_admin_root_for_links = str_repeat('../', $depth + 1);
} elseif ($current_script_directory === $expected_admin_path) {
    $path_to_admin_root_for_links = ''; // Já está na raiz do admin
} else {
    // Fallback ou lógica para outros cenários, se necessário.
    // Se o header for incluído de um local inesperado, os links podem precisar de ajuste manual
    // ou uma URL base absoluta seria melhor. Para a estrutura atual, isso deve cobrir.
}

// Define $admin_base_link para simplificar nos hrefs
$admin_base_link = $path_to_admin_root_for_links;

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Portal do Motorista Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    
    <link rel="stylesheet" href="<?php echo $admin_base_link; ?>admin_style.css?v=<?php echo file_exists(dirname(__FILE__) . '/admin_style.css') ? filemtime(dirname(__FILE__) . '/admin_style.css') : time(); ?>">
    <link rel="stylesheet" href="<?php echo $admin_base_link; ?>../style.css?v=<?php echo file_exists(dirname(__FILE__) . '/../style.css') ? filemtime(dirname(__FILE__) . '/../style.css') : time(); ?>">
    
</head>
<body>
    <nav class="navbar navbar-expand-md navbar-dark bg-danger fixed-top">
        <a class="navbar-brand" href="<?php echo $admin_base_link; ?>index.php"><i class="fas fa-user-shield"></i> Admin Portal</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item d-md-none nav-admin-main-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false) ? 'active' : ''; ?>" href="<?php echo $admin_base_link; ?>index.php">
                        <i class="fas fa-tachometer-alt fa-fw"></i> Dashboard
                    </a>
                </li>
                <?php if (in_array($admin_nivel_acesso_logado, $niveis_acesso_ver_noticias)): ?>
                <li class="nav-item d-md-none nav-admin-main-item">
                    <a class="nav-link <?php $pags_noticias = ['noticias_listar.php', 'noticia_formulario.php']; echo in_array(basename($_SERVER['PHP_SELF']), $pags_noticias) && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false ? 'active' : ''; ?>" href="<?php echo $admin_base_link; ?>noticias_listar.php">
                        <i class="fas fa-newspaper fa-fw"></i> Gerenciar Notícias
                    </a>
                </li>
                <?php endif; ?>
                <?php if (in_array($admin_nivel_acesso_logado, $niveis_acesso_ver_mensagens)): ?>
                <li class="nav-item d-md-none nav-admin-main-item">
                    <a class="nav-link <?php $pags_mensagens = ['mensagens_listar.php', 'mensagem_formulario.php', 'mensagem_visualizar.php']; echo in_array(basename($_SERVER['PHP_SELF']), $pags_mensagens) && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false ? 'active' : ''; ?>" href="<?php echo $admin_base_link; ?>mensagens_listar.php">
                        <i class="fas fa-envelope fa-fw"></i> Gerenciar Mensagens
                    </a>
                </li>
                <?php endif; ?>
                <?php if (in_array($admin_nivel_acesso_logado, $niveis_gerenciar_escala_planejada)): ?>
                    <li class="nav-item d-md-none nav-admin-main-item">
                        <a class="nav-link <?php $pags_esc_plan = ['escala_planejada_listar.php', 'escala_planejada_formulario.php']; echo in_array(basename($_SERVER['PHP_SELF']), $pags_esc_plan) && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false ? 'active' : ''; ?>" href="<?php echo $admin_base_link; ?>escala_planejada_listar.php">
                            <i class="fas fa-calendar-alt fa-fw"></i> Escala Planejada
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (in_array($admin_nivel_acesso_logado, $niveis_consultar_escala_diaria)): ?>
                    <li class="nav-item d-md-none nav-admin-main-item">
                        <a class="nav-link <?php $pags_esc_diaria = ['escala_diaria_consultar.php', 'escala_diaria_formulario.php']; echo in_array(basename($_SERVER['PHP_SELF']), $pags_esc_diaria) && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false ? 'active' : ''; ?>" href="<?php echo $admin_base_link; ?>escala_diaria_consultar.php">
                            <i class="fas fa-calendar-day fa-fw"></i> Escala Diária
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (in_array($admin_nivel_acesso_logado, $niveis_acesso_ver_linhas_rotas)): ?>
                <li class="nav-item d-md-none nav-admin-main-item">
                    <a class="nav-link <?php $pags_linhas_rotas = ['linhas_listar.php', 'linha_formulario.php', 'rotas_linha_gerenciar.php']; echo in_array(basename($_SERVER['PHP_SELF']), $pags_linhas_rotas) && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false ? 'active' : ''; ?>" href="<?php echo $admin_base_link; ?>linhas_listar.php">
                        <i class="fas fa-route fa-fw"></i> Gerenciar Linhas e Rotas </a>
                </li>
                <?php endif; ?>
                <?php if (in_array($admin_nivel_acesso_logado, $niveis_acesso_ver_locais)): ?>
                <li class="nav-item d-md-none nav-admin-main-item">
                    <a class="nav-link <?php $pags_locais = ['locais_hub.php', 'locais_listar.php', 'local_formulario.php', 'locais_diario_listar.php']; echo in_array(basename($_SERVER['PHP_SELF']), $pags_locais) && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false ? 'active' : ''; ?>" href="<?php echo $admin_base_link; ?>locais_hub.php">
                        <i class="fas fa-map-marker-alt fa-fw"></i> Gerenciar Locais
                    </a>
                </li>
                <?php endif; ?>
                <?php if (in_array($admin_nivel_acesso_logado, $niveis_acesso_gerenciar_frota)): ?>
                <li class="nav-item d-md-none nav-admin-main-item">
                    <a class="nav-link <?php $pags_frota = ['veiculos_listar.php', 'veiculo_formulario.php']; echo in_array(basename($_SERVER['PHP_SELF']), $pags_frota) && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false ? 'active' : ''; ?>" href="<?php echo $admin_base_link; ?>veiculos_listar.php">
                        <i class="fas fa-bus fa-fw"></i> Gerenciar Frota
                    </a>
                </li>
                <?php endif; ?>
                <?php if (in_array($admin_nivel_acesso_logado, $niveis_acesso_ver_motoristas)): ?>
                <li class="nav-item d-md-none nav-admin-main-item">
                    <a class="nav-link <?php $pags_motoristas = ['motoristas_listar.php', 'motorista_formulario.php']; echo in_array(basename($_SERVER['PHP_SELF']), $pags_motoristas) && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false ? 'active' : ''; ?>" href="<?php echo $admin_base_link; ?>motoristas_listar.php">
                        <i class="fas fa-id-card fa-fw"></i> Gerenciar Funcionários
                    </a>
                </li>
                <?php endif; ?>
                <?php if (in_array($admin_nivel_acesso_logado, $niveis_acesso_ver_usuarios_admin)):?>
                    <li class="nav-item d-md-none nav-admin-main-item">
                        <a class="nav-link <?php $pags_usuarios_admin = ['usuarios_listar.php', 'usuario_formulario.php']; echo in_array(basename($_SERVER['PHP_SELF']), $pags_usuarios_admin) && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false ? 'active' : ''; ?>" href="<?php echo $admin_base_link; ?>usuarios_listar.php">
                            <i class="fas fa-user-cog fa-fw"></i> Gerenciar Usuários
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (in_array($admin_nivel_acesso_logado, $niveis_acesso_ver_relatorios)): ?>
                <li class="nav-item d-md-none nav-admin-main-item">
                    <a class="nav-link <?php echo (strpos($current_script_directory, $expected_admin_path . '/relatorios') !== false || basename($_SERVER['PHP_SELF']) == 'relatorios_index.php') ? 'active' : ''; ?>" href="<?php echo $admin_base_link; ?>relatorios_index.php">
                        <i class="fas fa-chart-bar fa-fw"></i> Relatórios 
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ml-auto"> 
                 <li class="nav-item d-md-none nav-admin-main-item"> 
                     <span class="navbar-text">
                         <i class="fas fa-user"></i> <?php echo htmlspecialchars($admin_username_logado ?? 'Usuário'); ?>
                         <small>(<?php echo htmlspecialchars($admin_nivel_acesso_logado ?? 'N/A'); ?>)</small>
                     </span>
                 </li>
                 <li class="nav-item"> 
                    <a class="nav-link" href="<?php echo $admin_base_link; ?>logout.php" title="Sair">
                        <i class="fas fa-sign-out-alt"></i> <span class="d-md-none">Sair</span>
                    </a>
                 </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-none d-md-block bg-light sidebar">
                <div class="sidebar-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false) ? 'active' : ''; ?>" href="<?php echo $admin_base_link; ?>index.php">
                                <i class="fas fa-tachometer-alt fa-fw"></i> Dashboard
                            </a>
                        </li>
                        <?php if (in_array($admin_nivel_acesso_logado, $niveis_acesso_ver_noticias)): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php $pags_noticias = ['noticias_listar.php', 'noticia_formulario.php']; echo in_array(basename($_SERVER['PHP_SELF']), $pags_noticias) && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false ? 'active' : ''; ?>" href="<?php echo $admin_base_link; ?>noticias_listar.php">
                                <i class="fas fa-newspaper fa-fw"></i> Gerenciar Notícias
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (in_array($admin_nivel_acesso_logado, $niveis_acesso_ver_mensagens)): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php $pags_mensagens = ['mensagens_listar.php', 'mensagem_formulario.php', 'mensagem_visualizar.php']; echo in_array(basename($_SERVER['PHP_SELF']), $pags_mensagens) && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false ? 'active' : ''; ?>" href="<?php echo $admin_base_link; ?>mensagens_listar.php">
                                <i class="fas fa-envelope fa-fw"></i> Gerenciar Mensagens
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (in_array($admin_nivel_acesso_logado, $niveis_gerenciar_escala_planejada)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php $pags_esc_plan = ['escala_planejada_listar.php', 'escala_planejada_formulario.php']; echo in_array(basename($_SERVER['PHP_SELF']), $pags_esc_plan) && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false ? 'active' : ''; ?>" href="<?php echo $admin_base_link; ?>escala_planejada_listar.php">
                                    <i class="fas fa-calendar-alt fa-fw"></i> Escala Planejada
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (in_array($admin_nivel_acesso_logado, $niveis_consultar_escala_diaria)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php $pags_esc_diaria = ['escala_diaria_consultar.php', 'escala_diaria_formulario.php']; echo in_array(basename($_SERVER['PHP_SELF']), $pags_esc_diaria) && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false ? 'active' : ''; ?>" href="<?php echo $admin_base_link; ?>escala_diaria_consultar.php">
                                    <i class="fas fa-calendar-day fa-fw"></i> Escala Diária
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (in_array($admin_nivel_acesso_logado, $niveis_acesso_ver_linhas_rotas)): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php $pags_linhas_rotas = ['linhas_listar.php', 'linha_formulario.php', 'rotas_linha_gerenciar.php']; echo in_array(basename($_SERVER['PHP_SELF']), $pags_linhas_rotas) && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false ? 'active' : ''; ?>" href="<?php echo $admin_base_link; ?>linhas_listar.php">
                                <i class="fas fa-route fa-fw"></i> Gerenciar Linhas e Rotas </a>
                        </li>
                        <?php endif; ?>
                        <?php if (in_array($admin_nivel_acesso_logado, $niveis_acesso_ver_locais)): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php $pags_locais = ['locais_hub.php', 'local_formulario.php', 'locais_diario_listar.php', 'locais_listar.php']; echo in_array(basename($_SERVER['PHP_SELF']), $pags_locais) && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false ? 'active' : ''; ?>" href="<?php echo $admin_base_link; ?>locais_hub.php">
                                <i class="fas fa-map-marker-alt fa-fw"></i> Gerenciar Locais
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (in_array($admin_nivel_acesso_logado, $niveis_acesso_gerenciar_frota)): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php $pags_frota = ['veiculos_listar.php', 'veiculo_formulario.php']; echo in_array(basename($_SERVER['PHP_SELF']), $pags_frota) && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false ? 'active' : ''; ?>" href="<?php echo $admin_base_link; ?>veiculos_listar.php">
                                <i class="fas fa-bus fa-fw"></i> Gerenciar Frota
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (in_array($admin_nivel_acesso_logado, $niveis_acesso_ver_motoristas)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php
                                    $pags_motoristas = ['motoristas_listar.php', 'motorista_formulario.php'];
                                    echo in_array(basename($_SERVER['PHP_SELF']), $pags_motoristas) && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false ? 'active' : '';
                                ?>" href="<?php echo $admin_base_link; ?>motoristas_listar.php">
                                    <i class="fas fa-id-card fa-fw"></i> Gerenciar Funcionários
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (in_array($admin_nivel_acesso_logado, $niveis_acesso_ver_usuarios_admin)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php
                                    $pags_usuarios_admin = ['usuarios_listar.php', 'usuario_formulario.php'];
                                    echo in_array(basename($_SERVER['PHP_SELF']), $pags_usuarios_admin) && strpos($current_script_directory, $expected_admin_path . '/relatorios') === false ? 'active' : '';
                                ?>" href="<?php echo $admin_base_link; ?>usuarios_listar.php">
                                    <i class="fas fa-user-cog fa-fw"></i> Gerenciar Usuários
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (in_array($admin_nivel_acesso_logado, $niveis_acesso_ver_relatorios)): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php 
                                echo (basename($_SERVER['PHP_SELF']) == 'relatorios_index.php' || strpos($current_script_directory, $expected_admin_path . '/relatorios') !== false) ? 'active' : ''; 
                            ?>" href="<?php echo $admin_base_link; ?>relatorios_index.php"> 
                                <i class="fas fa-chart-bar fa-fw"></i> Relatórios
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <div class="sidebar-user-info">
                         <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-3 mb-1 text-muted" style="font-size: 0.7rem;">
                            <span>LOGADO COMO</span>
                         </h6>
                         <ul class="nav flex-column mb-2">
                            <li class="nav-item px-3">
                                <small>
                                    <i class="fas fa-user fa-fw"></i> <?php echo htmlspecialchars($admin_username_logado ?? 'Usuário'); ?><br>
                                    <i class="fas fa-shield-alt fa-fw"></i> <span class="text-muted">(<?php echo htmlspecialchars($admin_nivel_acesso_logado ?? 'N/A'); ?>)</span>
                                </small>
                            </li>
                         </ul>
                    </div>
                </div>
            </nav>

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4 main-content">