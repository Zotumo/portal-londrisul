<?php
// admin/tabelas_hub.php
// Página intermediária para direcionar ao gerenciamento de Blocos ou Eventos do Diário de Bordo.

require_once 'auth_check.php'; // Garante que o usuário esteja logado

// --- Definição de Permissões para esta página HUB ---
// Quem pode ver esta página HUB? Provavelmente os mesmos que podem ver qualquer uma das subseções.
// Usaremos $niveis_acesso_ver_tabelas que já deve estar definido em admin_header.php
// Se não estiver, defina-o aqui ou no admin_header.php:
if (!isset($niveis_acesso_ver_tabelas)) {
    $niveis_acesso_ver_tabelas = ['CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
}
if (!isset($niveis_acesso_ver_eventos_diario)) { // Para o link de eventos do diário
    $niveis_acesso_ver_eventos_diario = ['CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
}
// Adicionar arrays de permissão para infos padrão se não existirem no header
if (!isset($niveis_acesso_gerenciar_infos_padrao)) {
    // Defina quem pode gerenciar as infos padrão (geralmente os mesmos que gerenciam tabelas/blocos)
    $niveis_acesso_gerenciar_infos_padrao = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
}
if (!isset($niveis_acesso_ver_infos_padrao)) { // Para visualização
    $niveis_acesso_ver_infos_padrao = ['CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
}

if (!in_array($admin_nivel_acesso_logado, $niveis_acesso_ver_tabelas) && !in_array($admin_nivel_acesso_logado, $niveis_acesso_ver_eventos_diario)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para acessar a seção de gerenciamento de tabelas/diários.";
    header('Location: index.php');
    exit;
}

$page_title = 'Gerenciamento de Tabelas e Diários de Bordo';
require_once 'admin_header.php'; // Inclui o layout do painel

// Definição dos blocos para esta página HUB
$blocos_hub = [
    [
        'titulo' => 'Gerenciar Tabelas',
        'link' => 'programacao_diaria_listar.php', // Página que lista os blocos
        'icone' => 'fas fa-th-large', // Ícone de "blocos"
        'cor_classe' => 'bg-cmtu-azul text-white', // Cor azul CMTU
        'permissao_array' => $niveis_acesso_ver_tabelas, // Reutiliza a permissão de ver tabelas
        'descricao' => 'Cadastre, edite e gerencie as tabelas horárias para diferentes tipos de dia.'
    ],
    [
        'titulo' => 'Gerenciar Horários do Diário de Bordo',
        'link' => 'eventos_diario_pesquisar.php', // Página inicial para pesquisar um Bloco antes de gerenciar seus eventos
        'icone' => 'fas fa-clipboard-list', // Ícone de lista/prancheta
        'cor_classe' => 'bg-cmtu-verde text-white', // Cor verde CMTU
        'permissao_array' => $niveis_acesso_ver_eventos_diario, // Permissão para ver eventos
        'descricao' => 'Cadastre, edite e gerencie os eventos (pontos, horários, informações) de uma tabela.'
    ],
	[
    'titulo' => 'Gerenciar Opções de Info (Diário de Bordo)',
    'link' => 'info_opcoes_listar.php', // Nova página para o CRUD de infos
    'icone' => 'fas fa-tags', 
    'cor_classe' => 'bg-cmtu-amarelo text-dark', 
    'permissao_array' => $niveis_acesso_ver_infos_padrao, // Usa a permissão de ver
    'descricao' => 'Cadastre, edite e gerencie as descrições da coluna "Info" dos Diários de Bordo.'
	],
    // Você pode adicionar mais blocos aqui se houver outras subseções relacionadas
];

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <a href="index.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Voltar para o Dashboard
    </a>
</div>

<?php
// Exibir mensagens de feedback (sucesso/erro/aviso) da sessão
if (isset($_SESSION['admin_success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_success_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_success_message']); }
if (isset($_SESSION['admin_error_message'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_error_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_error_message']); }
if (isset($_SESSION['admin_warning_message'])) { echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_warning_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_warning_message']); }
?>

<p class="lead">
    Esta seção permite o gerenciamento das Tabelas e dos detalhes de cada Diário de Bordo.
</p>

<div class="row mt-4">
    <?php
    foreach ($blocos_hub as $bloco) {
        if (isset($bloco['permissao_array']) && is_array($bloco['permissao_array']) && in_array($admin_nivel_acesso_logado, $bloco['permissao_array'])) {
            echo '<div class="col-md-6 col-lg-4 mb-3">';
            // A classe 'dashboard-card' e a 'cor_classe' (que inclui text-white ou text-dark) são aplicadas aqui
            // Bootstrap cards já são position-relative por padrão.
            echo '  <div class="card dashboard-card ' . $bloco['cor_classe'] . ' h-100 shadow-sm">'; 
            echo '    <div class="card-body">';
            echo '      <div class="d-flex justify-content-between align-items-center">';
            echo '        <div>';
            echo '          <h5 class="card-title mb-1">' . htmlspecialchars($bloco['titulo']) . '</h5>';
            echo '          <p class="card-text small">' . htmlspecialchars($bloco['descricao']) . '</p>';
            echo '        </div>';
            echo '        <i class="' . $bloco['icone'] . ' fa-3x opacity-50"></i>';
            echo '      </div>';
            echo '    </div>'; 
            // O link <a> está DENTRO do card-footer
            echo '      <div class="card-footer small">'; 
            echo '          <a href="' . $bloco['link'] . '" class="stretched-link">'; // Stretched-link aqui para fazer o card todo clicável
            echo '              Acessar seção <i class="fas fa-arrow-circle-right ml-1"></i>'; 
            echo '          </a>';
            echo '      </div>';
            echo '  </div>'; 
            echo '</div>'; 
        }
    }
    ?>
</div>

<?php
require_once 'admin_footer.php';
?>