<?php
// admin/info_opcoes_formulario.php
// Formulário para adicionar/editar Opções de Informação para o Diário de Bordo.

require_once 'auth_check.php';

// --- Definição de Permissões ---
// Quem pode criar/editar estas opções? Geralmente os mesmos que gerenciam tabelas/blocos.
$niveis_permitidos_crud_info_opcoes = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_crud_info_opcoes)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para gerenciar opções de informação.";
    header('Location: info_opcoes_listar.php'); // Ou tabelas_hub.php
    exit;
}

require_once '../db_config.php';

// --- Inicialização de Variáveis ---
$info_opcao_id_edicao = null;
$descricao_info_form = '';
$linha_id_form = null; // NULL para global/padrão
$status_info_form = 'ativo'; // Padrão para novas opções

$modo_edicao_info_form = false;
$page_title_action = 'Adicionar Nova Opção de Informação';

// Buscar linhas ativas para o select (para associar uma info a uma linha específica)
$lista_linhas_ativas_select = [];
if ($pdo) {
    try {
        $stmt_linhas = $pdo->query("SELECT id, numero, nome FROM linhas WHERE status_linha = 'ativa' ORDER BY CAST(numero AS UNSIGNED), numero, nome ASC");
        $lista_linhas_ativas_select = $stmt_linhas->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['admin_warning_message'] = "Atenção: Erro ao carregar lista de linhas.";
    }
}

// Parâmetros GET para voltar para a listagem com filtros corretos (se houver filtros na listagem)
$params_retorno_lista_info_form = [];
// Ex: if (isset($_GET['pagina'])) $params_retorno_lista_info_form['pagina'] = $_GET['pagina'];
//     if (isset($_GET['filtro_xyz'])) $params_retorno_lista_info_form['filtro_xyz'] = $_GET['filtro_xyz'];
$query_string_retorno_info_form = http_build_query($params_retorno_lista_info_form);
$link_voltar_lista_info_form = 'info_opcoes_listar.php' . ($query_string_retorno_info_form ? '?' . $query_string_retorno_info_form : '');

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $info_opcao_id_edicao = (int)$_GET['id'];
    $modo_edicao_info_form = true;
    $page_title_action = 'Editar Opção de Informação';

    if ($pdo) {
        try {
            $stmt_info_opcao = $pdo->prepare("SELECT descricao_info, linha_id, status_info FROM info_opcoes WHERE id = :id");
            $stmt_info_opcao->bindParam(':id', $info_opcao_id_edicao, PDO::PARAM_INT);
            $stmt_info_opcao->execute();
            $info_db_data = $stmt_info_opcao->fetch(PDO::FETCH_ASSOC);

            if ($info_db_data) {
                $descricao_info_form = $info_db_data['descricao_info'];
                $linha_id_form = $info_db_data['linha_id'];
                $status_info_form = $info_db_data['status_info'];
                $page_title_action .= ' - "' . htmlspecialchars($descricao_info_form) . '"';
            } else {
                $_SESSION['admin_error_message'] = "Opção de Info ID {$info_opcao_id_edicao} não encontrada.";
                header('Location: ' . $link_voltar_lista_info_form);
                exit;
            }
        } catch (PDOException $e) { /* ... (erro PDO) ... */ }
    } else { /* ... (erro conexão) ... */ }
}

$page_title = $page_title_action;
require_once 'admin_header.php'; // Inclui Select2 CSS

// Repopulação em caso de erro de validação
$form_data_repop_info = $_SESSION['form_data_info_opcao'] ?? [];
if (!empty($form_data_repop_info)) {
    $descricao_info_form = $form_data_repop_info['descricao_info'] ?? $descricao_info_form;
    $linha_id_form = $form_data_repop_info['linha_id'] ?? $linha_id_form;
    $status_info_form = $form_data_repop_info['status_info'] ?? $status_info_form;
    unset($_SESSION['form_data_info_opcao']);
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title_action); ?></h1>
    <a href="<?php echo $link_voltar_lista_info_form; ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Voltar para Lista de Opções de Info
    </a>
</div>

<?php
if (isset($_SESSION['admin_form_error_info_opcao'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . nl2br(htmlspecialchars($_SESSION['admin_form_error_info_opcao'])) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
    unset($_SESSION['admin_form_error_info_opcao']);
}
?>

<form action="info_opcoes_processa.php<?php echo ($query_string_retorno_info_form ? '?' . $query_string_retorno_info_form : ''); ?>" method="POST" id="form-info-opcao">
    <?php if ($modo_edicao_info_form && $info_opcao_id_edicao): ?>
        <input type="hidden" name="info_opcao_id" value="<?php echo $info_opcao_id_edicao; ?>">
    <?php endif; ?>

    <div class="form-group">
        <label for="descricao_info">Descrição da Informação <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="descricao_info" name="descricao_info" value="<?php echo htmlspecialchars($descricao_info_form); ?>" required maxlength="150" placeholder="Ex: Via Centro, Intervalo, Rendição...">
        <small class="form-text text-muted">Texto que aparecerá para seleção no Diário de Bordo.</small>
    </div>

    <div class="form-group">
        <label for="linha_id_info">Associar a uma Linha Específica (Opcional)</label>
        <select class="form-control select2-simple" id="linha_id_info" name="linha_id" data-placeholder="Global (para todas as linhas)...">
            <option value="">Global (para todas as linhas)</option>
            <?php foreach ($lista_linhas_ativas_select as $linha_opt_io): ?>
                <option value="<?php echo $linha_opt_io['id']; ?>" <?php echo ($linha_id_form == $linha_opt_io['id']) ? 'selected' : ''; ?>>
                    Linha <?php echo htmlspecialchars($linha_opt_io['numero'] . ($linha_opt_io['nome'] ? ' - ' . $linha_opt_io['nome'] : '')); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small class="form-text text-muted">Se esta informação só se aplica a uma linha específica, selecione-a. Caso contrário, deixe em branco para ser uma opção global.</small>
    </div>
    
    <div class="form-group">
        <label for="status_info">Status <span class="text-danger">*</span></label>
        <select class="form-control" id="status_info" name="status_info" required>
            <option value="ativo" <?php echo ($status_info_form == 'ativo') ? 'selected' : ''; ?>>Ativa</option>
            <option value="inativo" <?php echo ($status_info_form == 'inativo') ? 'selected' : ''; ?>>Inativa</option>
        </select>
        <small class="form-text text-muted">Opções inativas não aparecerão para seleção no Diário de Bordo.</small>
    </div>
    
    <hr>
    <button type="submit" name="salvar_info_opcao" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Opção de Info</button>
    <a href="<?php echo $link_voltar_lista_info_form; ?>" class="btn btn-secondary">Cancelar</a>
</form>

<?php
ob_start();
?>
<script>
$(document).ready(function() {
    $('.select2-simple').each(function() {
        $(this).select2({
            theme: 'bootstrap4',
            placeholder: $(this).data('placeholder') || 'Selecione...',
            allowClear: true,
            width: '100%'
        });
    });

    $('#form-info-opcao').on('submit', function(e){
        var descricao = $('#descricao_info').val().trim();
        if (descricao === '') {
            alert('A Descrição da Informação é obrigatória.');
            $('#descricao_info').focus();
            e.preventDefault();
            return false;
        }
    });
});
</script>
<?php
$page_specific_js = ob_get_clean();
require_once 'admin_footer.php';
?>