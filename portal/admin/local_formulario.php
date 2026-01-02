<?php
// admin/local_formulario.php

require_once 'auth_check.php';

// --- Definição de Permissões (AJUSTE CONFORME NECESSÁRIO) ---
// Níveis que podem ACESSAR este formulário (criar ou editar)
$niveis_permitidos_formulario_locais = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];

if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_formulario_locais)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para criar ou editar locais.";
    header('Location: locais_listar.php'); // Redireciona para a lista se não tiver permissão
    exit;
}

require_once '../db_config.php';

// --- Lógica para buscar dados do local em modo de edição ---
$local_id_edicao = null;
$nome_local_form = '';
$tipo_local_form = ''; // Padrão ou o primeiro da lista de tipos
$modo_edicao_form = false;
$page_title_action = 'Adicionar Novo Local'; // Título da ação padrão

// Tipos de locais permitidos (consistente com locais_listar.php e sua tabela)
// Idealmente, se sua coluna 'tipo' na tabela 'locais' for um ENUM, você pode buscar esses valores.
// Por enquanto, vamos defini-los estaticamente.
$tipos_locais_disponiveis = ['Garagem', 'Terminal', 'Ponto', 'CIOP']; // Adicione/Remova conforme sua necessidade

// Parâmetros GET para voltar para a listagem com filtros corretos
$params_retorno_lista = [];
if (isset($_GET['pagina'])) $params_retorno_lista['pagina'] = $_GET['pagina'];
if (isset($_GET['busca_nome'])) $params_retorno_lista['busca_nome'] = $_GET['busca_nome'];
if (isset($_GET['busca_tipo'])) $params_retorno_lista['busca_tipo'] = $_GET['busca_tipo'];
$query_string_retorno = http_build_query($params_retorno_lista);
$link_voltar_lista = 'locais_listar.php' . ($query_string_retorno ? '?' . $query_string_retorno : '');


if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $local_id_edicao = (int)$_GET['id'];
    $modo_edicao_form = true;
    $page_title_action = 'Editar Local';

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT nome, tipo FROM locais WHERE id = :id");
            $stmt->bindParam(':id', $local_id_edicao, PDO::PARAM_INT);
            $stmt->execute();
            $local_db_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($local_db_data) {
                $nome_local_form = $local_db_data['nome'];
                $tipo_local_form = $local_db_data['tipo'];
                $page_title_action .= ' - ' . htmlspecialchars($nome_local_form);
            } else {
                $_SESSION['admin_error_message'] = "Local ID {$local_id_edicao} não encontrado para edição.";
                header('Location: ' . $link_voltar_lista);
                exit;
            }
        } catch (PDOException $e) {
            error_log("Erro ao buscar local para edição (ID: {$local_id_edicao}): " . $e->getMessage());
            $_SESSION['admin_error_message'] = "Erro ao carregar dados do local para edição.";
            header('Location: ' . $link_voltar_lista);
            exit;
        }
    } else {
        $_SESSION['admin_error_message'] = "Falha na conexão com o banco ao tentar carregar local para edição.";
        header('Location: ' . $link_voltar_lista);
        exit;
    }
}

$page_title = $page_title_action;
require_once 'admin_header.php';

// Para repopulação em caso de erro de validação
$form_data_repop = $_SESSION['form_data_local'] ?? [];
if (!empty($form_data_repop)) {
    $nome_local_form = $form_data_repop['nome_local'] ?? $nome_local_form;
    $tipo_local_form = $form_data_repop['tipo_local'] ?? $tipo_local_form;
    unset($_SESSION['form_data_local']);
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title_action); ?></h1>
    <a href="<?php echo $link_voltar_lista; ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Voltar para Lista de Locais
    </a>
</div>

<?php
if (isset($_SESSION['admin_form_error_local'])) { // Usando chave de erro específica
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_form_error_local']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
    unset($_SESSION['admin_form_error_local']);
}
?>

<form action="local_processa.php<?php echo ($query_string_retorno ? '?' . $query_string_retorno : ''); ?>" method="POST" id="form-local">
    <?php if ($modo_edicao_form && $local_id_edicao): ?>
        <input type="hidden" name="local_id" value="<?php echo $local_id_edicao; ?>">
    <?php endif; ?>

    <div class="form-row">
        <div class="form-group col-md-8">
            <label for="nome_local">Nome do Local <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nome_local" name="nome_local" value="<?php echo htmlspecialchars($nome_local_form); ?>" required maxlength="150">
        </div>
        <div class="form-group col-md-4">
            <label for="tipo_local">Tipo <span class="text-danger">*</span></label>
            <select class="form-control" id="tipo_local" name="tipo_local" required>
                <option value="">Selecione o tipo...</option>
                <?php foreach ($tipos_locais_disponiveis as $tipo_disp): ?>
                    <option value="<?php echo htmlspecialchars($tipo_disp); ?>" <?php echo ($tipo_local_form == $tipo_disp) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tipo_disp); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <hr>
    <button type="submit" name="salvar_local" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Local</button>
    <a href="<?php echo $link_voltar_lista; ?>" class="btn btn-secondary">Cancelar</a>
</form>

<?php
require_once 'admin_footer.php';
?>