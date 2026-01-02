<?php
// admin/linha_formulario.php
// ATUALIZADO: Adicionado campo multi-select para Tipos de Veículo Permitidos.

require_once 'auth_check.php';

// --- Definição de Permissões (manter consistência) ---
$niveis_permitidos_formulario_linhas = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_formulario_linhas)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para criar ou editar linhas.";
    header('Location: linhas_listar.php');
    exit;
}

require_once '../db_config.php';

$linha_id_edicao = null;
$numero_linha_form = '';
$nome_linha_form = '';
$status_linha_form = 'ativa';
$imagem_ponto_ida_atual = '';
$imagem_ponto_volta_atual = '';
$tipos_veiculo_permitidos_selecionados = []; // NOVO: Para guardar os tipos selecionados para esta linha

$modo_edicao_form = false;
$page_title_action = 'Adicionar Nova Linha';

// Tipos de veículos para o select (direto do ENUM definido no banco em veiculos.tipo)
$todos_tipos_veiculo_opcoes = [
    'Convencional Amarelo', 'Convencional Amarelo com Ar', 'Micro', 'Micro com Ar',
    'Convencional Azul', 'Convencional Azul com Ar', 'Padron Azul', 'SuperBus', 'Leve'
];

// Parâmetros GET para voltar para a listagem com filtros corretos
$params_retorno_lista_linhas_form = [];
if (isset($_GET['pagina'])) $params_retorno_lista_linhas_form['pagina'] = $_GET['pagina'];
if (isset($_GET['busca_numero'])) $params_retorno_lista_linhas_form['busca_numero'] = $_GET['busca_numero'];
if (isset($_GET['busca_nome'])) $params_retorno_lista_linhas_form['busca_nome'] = $_GET['busca_nome'];
if (isset($_GET['status_filtro'])) $params_retorno_lista_linhas_form['status_filtro'] = $_GET['status_filtro'];
$query_string_retorno_linhas_form = http_build_query($params_retorno_lista_linhas_form);
$link_voltar_lista_linhas_form = 'linhas_listar.php' . ($query_string_retorno_linhas_form ? '?' . $query_string_retorno_linhas_form : '');

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $linha_id_edicao = (int)$_GET['id'];
    $modo_edicao_form = true;
    $page_title_action = 'Editar Linha';

    if ($pdo) {
        try {
            $stmt_linha = $pdo->prepare("SELECT numero, nome, imagem_ponto_ida_path, imagem_ponto_volta_path, status_linha FROM linhas WHERE id = :id");
            $stmt_linha->bindParam(':id', $linha_id_edicao, PDO::PARAM_INT);
            $stmt_linha->execute();
            $linha_db_data = $stmt_linha->fetch(PDO::FETCH_ASSOC);

            if ($linha_db_data) {
                $numero_linha_form = $linha_db_data['numero'];
                $nome_linha_form = $linha_db_data['nome'];
                $status_linha_form = $linha_db_data['status_linha'];
                $imagem_ponto_ida_atual = $linha_db_data['imagem_ponto_ida_path'];
                $imagem_ponto_volta_atual = $linha_db_data['imagem_ponto_volta_path'];
                $page_title_action .= ' - ' . htmlspecialchars($numero_linha_form) . ($nome_linha_form ? ' (' . htmlspecialchars($nome_linha_form) . ')' : '');

                // NOVO: Buscar os tipos de veículo já associados a esta linha
                $stmt_tipos_assoc = $pdo->prepare("SELECT tipo_veiculo FROM linha_tipos_veiculo_permitidos WHERE linha_id = :linha_id_assoc");
                $stmt_tipos_assoc->bindParam(':linha_id_assoc', $linha_id_edicao, PDO::PARAM_INT);
                $stmt_tipos_assoc->execute();
                $tipos_veiculo_permitidos_selecionados = $stmt_tipos_assoc->fetchAll(PDO::FETCH_COLUMN);

            } else {
                $_SESSION['admin_error_message'] = "Linha ID {$linha_id_edicao} não encontrada para edição.";
                header('Location: ' . $link_voltar_lista_linhas_form);
                exit;
            }
        } catch (PDOException $e) {
            error_log("Erro ao buscar linha para edição (ID: {$linha_id_edicao}): " . $e->getMessage());
            $_SESSION['admin_error_message'] = "Erro ao carregar dados da linha para edição.";
            header('Location: ' . $link_voltar_lista_linhas_form);
            exit;
        }
    } else {
         $_SESSION['admin_error_message'] = "Falha na conexão com o banco de dados (formulário de linha).";
         header('Location: ' . $link_voltar_lista_linhas_form);
         exit;
    }
}

$page_title = $page_title_action;
require_once 'admin_header.php'; // Inclui Select2 CSS se não estiver global

// Para repopulação em caso de erro de validação
$form_data_repop_linha = $_SESSION['form_data_linha'] ?? [];
if (!empty($form_data_repop_linha)) {
    $numero_linha_form = $form_data_repop_linha['numero_linha'] ?? $numero_linha_form;
    $nome_linha_form = $form_data_repop_linha['nome_linha'] ?? $nome_linha_form;
    $status_linha_form = $form_data_repop_linha['status_linha'] ?? $status_linha_form;
    // NOVO: Repopular tipos de veículo selecionados
    $tipos_veiculo_permitidos_selecionados = $form_data_repop_linha['tipos_veiculo_permitidos'] ?? $tipos_veiculo_permitidos_selecionados;
    unset($_SESSION['form_data_linha']);
}

$base_img_path_form = '../img/pontos/';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title_action); ?></h1>
    <a href="<?php echo $link_voltar_lista_linhas_form; ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Voltar para Lista de Linhas
    </a>
</div>

<?php
// Exibir mensagens de erro do formulário
if (isset($_SESSION['admin_form_error_linha'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . nl2br(htmlspecialchars($_SESSION['admin_form_error_linha'])) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
    unset($_SESSION['admin_form_error_linha']);
}
?>

<form action="linha_processa.php<?php echo ($query_string_retorno_linhas_form ? '?' . $query_string_retorno_linhas_form : ''); ?>" method="POST" enctype="multipart/form-data" id="form-linha">
    <?php if ($modo_edicao_form && $linha_id_edicao): ?>
        <input type="hidden" name="linha_id" value="<?php echo $linha_id_edicao; ?>">
    <?php endif; ?>

    <div class="form-row">
        <div class="form-group col-md-3">
            <label for="numero_linha">Número da Linha <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="numero_linha" name="numero_linha" value="<?php echo htmlspecialchars($numero_linha_form); ?>" required maxlength="20">
        </div>
        <div class="form-group col-md-5">
            <label for="nome_linha">Nome da Linha <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nome_linha" name="nome_linha" value="<?php echo htmlspecialchars($nome_linha_form); ?>" required maxlength="150">
        </div>
        <div class="form-group col-md-4">
            <label for="status_linha">Status <span class="text-danger">*</span></label>
            <select class="form-control" id="status_linha" name="status_linha" required>
                <option value="ativa" <?php echo ($status_linha_form == 'ativa') ? 'selected' : ''; ?>>Ativa</option>
                <option value="inativa" <?php echo ($status_linha_form == 'inativa') ? 'selected' : ''; ?>>Inativa</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label for="tipos_veiculo_permitidos_select">Tipos de Veículo Permitidos para esta Linha <span class="text-danger">*</span></label>
        <select class="form-control select2-multiple" id="tipos_veiculo_permitidos_select" name="tipos_veiculo_permitidos[]" multiple="multiple" required data-placeholder="Selecione um ou mais tipos...">
            <?php foreach ($todos_tipos_veiculo_opcoes as $tipo_opcao): ?>
                <option value="<?php echo htmlspecialchars($tipo_opcao); ?>" 
                    <?php echo in_array($tipo_opcao, $tipos_veiculo_permitidos_selecionados) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($tipo_opcao); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small class="form-text text-muted">Selecione todos os tipos de veículos que podem operar nesta linha.</small>
    </div>
    <fieldset class="mt-3 border p-3 rounded">
        <legend class="w-auto px-2 h6">Imagens dos Pontos de Referência <span class="text-danger">*</span></legend>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="imagem_ponto_ida">Imagem Ponto Ida <span class="text-danger">*</span></label>
                <input type="file" class="form-control-file" id="imagem_ponto_ida" name="imagem_ponto_ida" accept="image/jpeg, image/png, image/gif" <?php echo (!$modo_edicao_form || empty($imagem_ponto_ida_atual)) ? 'required' : ''; ?>>
                <?php if ($modo_edicao_form && !empty($imagem_ponto_ida_atual)): ?>
                    <div class="mt-2">
                        <p class="mb-1"><small>Imagem atual (Ida):</small></p>
                        <img src="<?php echo $base_img_path_form . htmlspecialchars($imagem_ponto_ida_atual); ?>" alt="Ponto Ida Atual" style="max-width: 150px; max-height: 100px; margin-bottom: 5px; border:1px solid #ddd; padding:2px;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remover_imagem_ida" id="remover_imagem_ida" value="1">
                            <label class="form-check-label" for="remover_imagem_ida"><small>Remover imagem de Ida atual ao salvar</small></label>
                        </div>
                    </div>
                <?php elseif (!$modo_edicao_form): ?>
                    <small class="form-text text-muted">A imagem de Ida é obrigatória para novas linhas.</small>
                <?php endif; ?>
                <small class="form-text text-muted">Formatos: JPG, PNG, GIF.</small>
            </div>

            <div class="form-group col-md-6">
                <label for="imagem_ponto_volta">Imagem Ponto Volta <span class="text-danger">*</span></label>
                <input type="file" class="form-control-file" id="imagem_ponto_volta" name="imagem_ponto_volta" accept="image/jpeg, image/png, image/gif" <?php echo (!$modo_edicao_form || empty($imagem_ponto_volta_atual)) ? 'required' : ''; ?>>
                 <?php if ($modo_edicao_form && !empty($imagem_ponto_volta_atual)): ?>
                    <div class="mt-2">
                        <p class="mb-1"><small>Imagem atual (Volta):</small></p>
                        <img src="<?php echo $base_img_path_form . htmlspecialchars($imagem_ponto_volta_atual); ?>" alt="Ponto Volta Atual" style="max-width: 150px; max-height: 100px; margin-bottom: 5px; border:1px solid #ddd; padding:2px;">
                         <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remover_imagem_volta" id="remover_imagem_volta" value="1">
                            <label class="form-check-label" for="remover_imagem_volta"><small>Remover imagem de Volta atual ao salvar</small></label>
                        </div>
                    </div>
                 <?php elseif (!$modo_edicao_form): ?>
                    <small class="form-text text-muted">A imagem de Volta é obrigatória para novas linhas.</small>
                <?php endif; ?>
                <small class="form-text text-muted">Formatos: JPG, PNG, GIF.</small>
            </div>
        </div>
    </fieldset>

    <hr>
    <button type="submit" name="salvar_linha" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Linha</button>
    <a href="<?php echo $link_voltar_lista_linhas_form; ?>" class="btn btn-secondary">Cancelar</a>
</form>

<?php
// Adicionar JavaScript para inicializar o Select2 no novo campo
ob_start();
?>
<script>
$(document).ready(function() {
    // Inicializa o Select2 para o campo de tipos de veículo permitidos
    $('#tipos_veiculo_permitidos_select').select2({
        theme: 'bootstrap4', // Usa o tema Bootstrap 4 se você o tiver incluído
        placeholder: $(this).data('placeholder') || 'Selecione um ou mais tipos...',
        width: '100%', // Garante que o select ocupe a largura disponível
        // allowClear: true, // Descomente se quiser um botão para limpar a seleção
        // closeOnSelect: false // Descomente se quiser manter o dropdown aberto após selecionar um item (útil para multi-selects)
    });

    // Validação de submissão do formulário (mantém a sua lógica anterior se houver)
    $('#form-linha').on('submit', function(e){
        var numeroLinha = $('#numero_linha').val().trim();
        var nomeLinha = $('#nome_linha').val().trim();
        var statusLinha = $('#status_linha').val();
        var tiposVeiculoSelecionados = $('#tipos_veiculo_permitidos_select').val(); // Pega os valores do multi-select

        if (numeroLinha === '') {
            alert('O Número da Linha é obrigatório.');
            $('#numero_linha').focus();
            e.preventDefault(); return false;
        }
        if (nomeLinha === '') {
            alert('O Nome da Linha é obrigatório.');
            $('#nome_linha').focus();
            e.preventDefault(); return false;
        }
        if (statusLinha === '') {
            alert('O Status da Linha é obrigatório.');
            $('#status_linha').focus();
            e.preventDefault(); return false;
        }

        // NOVO: Validação para o campo de tipos de veículo
        if (!tiposVeiculoSelecionados || tiposVeiculoSelecionados.length === 0) {
            alert('Selecione pelo menos um Tipo de Veículo Permitido para esta linha.');
            // Tenta focar no Select2 (pode ser um pouco diferente dependendo da versão/tema)
            $('#tipos_veiculo_permitidos_select').select2('open');
            e.preventDefault(); return false;
        }


        // Validação das imagens (mantém a sua lógica anterior)
        var modoEdicao = <?php echo json_encode($modo_edicao_form); ?>;
        var imgIdaAtualExiste = <?php echo json_encode(!empty($imagem_ponto_ida_atual)); ?>;
        var imgVoltaAtualExiste = <?php echo json_encode(!empty($imagem_ponto_volta_atual)); ?>;

        if (!modoEdicao || !imgIdaAtualExiste || (imgIdaAtualExiste && $('#remover_imagem_ida').is(':checked'))) {
            if ($('#imagem_ponto_ida').get(0).files.length === 0) {
                alert('A Imagem do Ponto Ida é obrigatória.');
                $('#imagem_ponto_ida').focus();
                e.preventDefault(); return false;
            }
        }
        if (!modoEdicao || !imgVoltaAtualExiste || (imgVoltaAtualExiste && $('#remover_imagem_volta').is(':checked'))) {
            if ($('#imagem_ponto_volta').get(0).files.length === 0) {
                alert('A Imagem do Ponto Volta é obrigatória.');
                $('#imagem_ponto_volta').focus();
                e.preventDefault(); return false;
            }
        }
    });
});
</script>
<?php
$page_specific_js = ob_get_clean(); // Captura o script
require_once 'admin_footer.php'; // O footer irá ecoar $page_specific_js
?>