<?php
// admin/relatorios_index.php (Hub de Relatórios)
require_once 'auth_check.php';

$niveis_permitidos_relatorios = ['CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_relatorios)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para acessar a seção de relatórios.";
    header('Location: index.php');
    exit;
}

$page_title = 'Central de Relatórios';
require_once 'admin_header.php';

// Categorias de Relatórios
$categorias_relatorios = [
    [
        'titulo' => 'Relatórios de Funcionários',
        'link' => 'relatorios/funcionarios_filtros.php',
        'icone' => 'fas fa-users',
        'cor_classe' => 'bg-cmtu-azul text-white', // Reutilize suas classes de cor
        'descricao' => 'Gere relatórios sobre contagem, status e detalhes dos funcionários.'
    ],
    [ // NOVA CATEGORIA
        'titulo' => 'Relatórios de Escalas',
        'link' => 'relatorios/escalas_filtros.php',
        'icone' => 'fas fa-calendar-alt',
        'cor_classe' => 'bg-cmtu-verde text-white', // Pode escolher outra cor
        'descricao' => 'Analise horas trabalhadas, ocorrências e detalhes das escalas.'
        // 'permissao_necessaria_array' => [...] // Se precisar de permissão específica
    ],
    // Adicione mais categorias aqui no futuro
    // [
    //     'titulo' => 'Relatórios de Escalas',
    //     'link' => 'relatorios/escalas_filtros.php',
    //     'icone' => 'fas fa-calendar-alt',
    //     'cor_classe' => 'bg-cmtu-verde text-white',
    //     'descricao' => 'Analise horas trabalhadas, ocorrências e cobertura de escalas.'
    // ],
    // [
    //     'titulo' => 'Relatórios de Operação',
    //     'link' => 'relatorios/operacao_filtros.php',
    //     'icone' => 'fas fa-bus',
    //     'cor_classe' => 'bg-cmtu-amarelo text-dark',
    //     'descricao' => 'Informações sobre utilização de linhas, veículos, etc.'
    // ],
];

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <a href="../index.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Voltar para o Dashboard Principal
    </a>
</div>

<?php
if (isset($_SESSION['admin_feedback'])) {
    $feedback = $_SESSION['admin_feedback'];
    $alert_class = $feedback['type'] === 'success' ? 'alert-success' : ($feedback['type'] === 'error' ? 'alert-danger' : 'alert-warning');
    echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">' . htmlspecialchars($feedback['message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
    unset($_SESSION['admin_feedback']);
}
?>

<p class="lead">
    Bem-vindo à Central de Relatórios. Selecione uma categoria abaixo para começar.
</p>

<div class="row mt-4">
    <?php foreach ($categorias_relatorios as $categoria): ?>
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card dashboard-card <?php echo htmlspecialchars($categoria['cor_classe']); ?> h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($categoria['titulo']); ?></h5>
                            <p class="card-text small"><?php echo htmlspecialchars($categoria['descricao']); ?></p>
                        </div>
                        <i class="<?php echo htmlspecialchars($categoria['icone']); ?> fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer small">
                    <a href="<?php echo htmlspecialchars($categoria['link']); ?>" class="stretched-link">
                        Acessar Relatórios <i class="fas fa-arrow-circle-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php
require_once 'admin_footer.php';
?>