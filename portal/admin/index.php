<?php
// admin/index.php
// ATUALIZADO: Novas cores e texto do link para os blocos do dashboard.

require_once 'auth_check.php';
$page_title = 'Dashboard Admin';
// As arrays de permissão ($niveis_acesso_ver_*) são definidas no admin_header.php
require_once 'admin_header.php'; 

// Estrutura para os blocos de funcionalidades com as novas cores e texto
$blocos_funcionalidades = [
    // Azul CMTU
    ['titulo' => 'Gerenciar Notícias',   'link' => 'noticias_listar.php',          'icone' => 'fas fa-newspaper',         'cor_classe' => 'bg-cmtu-azul text-white',    'permissao_array' => $niveis_acesso_ver_noticias,         'descricao' => 'Gerencie as todas as notícias do Portal'],
    // Verde CMTU
    ['titulo' => 'Gerenciar Mensagens', 'link' => 'mensagens_listar.php',         'icone' => 'fas fa-envelope',          'cor_classe' => 'bg-cmtu-verde text-white',   'permissao_array' => $niveis_acesso_ver_mensagens,        'descricao' => 'Gerencie os avisos para motoristas'],
    // Amarelo CMTU
    ['titulo' => 'Escala Planejada',    'link' => 'escala_planejada_listar.php',  'icone' => 'fas fa-calendar-alt',      'cor_classe' => 'bg-cmtu-amarelo text-dark',  'permissao_array' => $niveis_gerenciar_escala_planejada,'descricao' => 'Gerencie as escalas que serão vizualizadas pelos motoristas'],
    // Vermelho CMTU
    ['titulo' => 'Escala Diária',       'link' => 'escala_diaria_consultar.php',  'icone' => 'fas fa-calendar-day',      'cor_classe' => 'bg-cmtu-vermelho text-white','permissao_array' => $niveis_consultar_escala_diaria,   'descricao' => 'Gerencie ou consulte os ajustes da escala do dia'],
    // Azul CMTU
    ['titulo' => 'Gerenciar Tabelas',   'link' => 'tabelas_hub.php',           'icone' => 'fas fa-list',              'cor_classe' => 'bg-cmtu-azul text-white',    'permissao_array' => $niveis_acesso_ver_tabelas,          'descricao' => 'Gerencie os Diários de Bordo de todas as linhas'],
    // Verde CMTU
    ['titulo' => 'Gerenciar Linhas e Rotas','link' => 'linhas_listar.php',           'icone' => 'fas fa-bus-alt',           'cor_classe' => 'bg-cmtu-verde text-white',   'permissao_array' => $niveis_acesso_ver_linhas_rotas,     'descricao' => 'Gerencie todas as linhas e suas rotas'],
    // Amarelo CMTU
    ['titulo' => 'Gerenciar Locais',    'link' => 'locais_listar.php',            'icone' => 'fas fa-map-marker-alt',    'cor_classe' => 'bg-cmtu-amarelo text-dark',  'permissao_array' => $niveis_acesso_ver_locais,           'descricao' => 'Gerencie todos os locais listados no Diário de Bordo'],
    // Vermelho CMTU
    ['titulo' => 'Gerenciar Frota','link' => 'veiculos_listar.php',        'icone' => 'fas fa-bus',           'cor_classe' => 'bg-cmtu-vermelho text-white','permissao_array' => $niveis_acesso_gerenciar_frota,       'descricao' => 'Gerencie ou consulte os veículos da frota'],
    // Azul CMTU
    ['titulo' => 'Gerenciar Funcionários','link' => 'motoristas_listar.php',        'icone' => 'fas fa-id-card',           'cor_classe' => 'bg-cmtu-azul text-white','permissao_array' => $niveis_acesso_ver_motoristas,       'descricao' => 'Gerencie ou consulte o cadastro dos funcionários'],
    // Cinza
    ['titulo' => 'Gerenciar Usuários',  'link' => 'usuarios_listar.php',          'icone' => 'fas fa-user-cog',          'cor_classe' => 'bg-cmtu-cinza text-white',   'permissao_array' => $niveis_acesso_ver_usuarios_admin,   'descricao' => 'Gerencie usuários que terão acesso ao painel'],
    ['titulo' => 'Relatórios',          'link' => 'relatorios_index.php',         'icone' => 'fas fa-chart-bar',         'cor_classe' => 'bg-cmtu-cinza text-white',   'permissao_array' => $niveis_acesso_ver_relatorios,       'descricao' => 'Gere relatórios com dados e análises do Portal do Motorista'],
];

// Garante que as arrays de permissão do admin_header.php estão disponíveis
// Se você moveu a definição delas para dentro do admin_header.php, elas já estarão no escopo.
// Caso contrário, você pode precisar re-declarar ou incluir um arquivo de configuração de permissões aqui.
// Exemplo:
if (!isset($niveis_acesso_ver_tabelas)) { // Apenas como exemplo, se não estiver no header
    $niveis_acesso_ver_tabelas = ['CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador']; 
}

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
</div>

<?php
// Feedback de mensagens (sucesso/erro)
if (isset($_SESSION['admin_success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_success_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_success_message']); }
if (isset($_SESSION['admin_error_message'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_error_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_error_message']); }
if (isset($_SESSION['admin_warning_message'])) { echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_warning_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_warning_message']); }

?>

<div class="alert alert-light border mb-4" role="alert">
    <h4 class="alert-heading">Bem-vindo(a) ao Painel Administrativo, <?php echo htmlspecialchars($_SESSION['admin_user_name']); ?>!</h4>
    <p>Estatísticas relevantes sobre o Portal do Motorista.</p>
    <hr>
    <div class="row">
        <div class="col-md-4">
            <div class="stat-card p-3 mb-2 bg-light text-center border rounded">
                <h5>Motoristas Ativos</h5>
                <p class="display-4" id="stat-motoristas-ativos">--</p>
                <small><a href="motoristas_listar.php?status_filtro=ativo">Ver detalhes</a></small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card p-3 mb-2 bg-light text-center border rounded">
                <h5>Notícias Publicadas</h5>
                <p class="display-4" id="stat-noticias-publicadas">--</p>
                <small><a href="noticias_listar.php">Ver detalhes</a></small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card p-3 mb-2 bg-light text-center border rounded">
                <h5>Escalas Hoje</h5>
                <p class="display-4" id="stat-escalas-hoje">--</p>
                <small><a href="escala_diaria_consultar.php?data_escala=<?php echo date('Y-m-d'); ?>">Ver detalhes</a></small>
            </div>
        </div>
    </div>
    <p class="mb-0 mt-2"><small><em>Dados estatísticos serão carregados dinamicamente (funcionalidade futura).</em></small></p>
</div>

<div class="row mt-2">
    <?php
    foreach ($blocos_funcionalidades as $bloco) {
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
ob_start();
?>
<script>
function carregarEstatisticas() {
   // Simulação de dados, idealmente viria de uma chamada AJAX
   // $('#stat-motoristas-ativos').text('N/A'); 
   // $('#stat-noticias-publicadas').text('N/A');
   // $('#stat-escalas-hoje').text('N/A');
}
$(document).ready(function() {
    carregarEstatisticas();
});
</script>
<?php
$page_specific_js = ob_get_clean();
require_once 'admin_footer.php';
?>