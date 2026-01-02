<?php
// admin/mensagem_formulario.php

require_once 'auth_check.php';

$niveis_permitidos_enviar_msg = ['Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_enviar_msg)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para enviar mensagens.";
    header('Location: mensagens_listar.php');
    exit;
}

// db_config.php será incluído condicionalmente abaixo se precisarmos pré-popular o Select2
$page_title = 'Enviar Nova Mensagem';
require_once 'admin_header.php'; // Inclui CSS do Select2, etc.

// Valores para repopular o formulário
$enviar_para_todos_check_previo = isset($_SESSION['form_data']['enviar_para_todos_check']) ? (bool)$_SESSION['form_data']['enviar_para_todos_check'] : false;
$destinatario_id_previo = $_SESSION['form_data']['destinatario_id'] ?? null; // Pode ser um ID ou null
$assunto_previo = $_SESSION['form_data']['assunto'] ?? '';
$mensagem_previa = $_SESSION['form_data']['mensagem'] ?? '';

if (isset($_SESSION['form_data'])) {
    unset($_SESSION['form_data']);
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <a href="mensagens_listar.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Voltar para Lista de Mensagens
    </a>
</div>

<?php
if (isset($_SESSION['admin_form_error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_form_error']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
    unset($_SESSION['admin_form_error']);
}
?>

<form action="mensagem_processa.php" method="POST" id="form-enviar-mensagem">

    <div class="form-group">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" value="1" id="enviar_para_todos_check" name="enviar_para_todos_check" <?php echo $enviar_para_todos_check_previo ? 'checked' : ''; ?>>
            <label class="form-check-label" for="enviar_para_todos_check">
                <strong>Enviar para TODOS OS MOTORISTAS</strong>
            </label>
        </div>
    </div>

    <div class="form-group" id="campo_destinatario_individual_wrapper">
        <label for="destinatario_id_select2">Destinatário Individual <span id="asterisco_destinatario_individual" class="text-danger">*</span></label>
        <select class="form-control" id="destinatario_id_select2" name="destinatario_id" data-placeholder="Digite nome ou matrícula para buscar...">
            <option></option> <?php
            // Se um destinatário individual foi previamente selecionado (e não era "todos"), pré-populamos
            if ($destinatario_id_previo && !$enviar_para_todos_check_previo && is_numeric($destinatario_id_previo)) {
                if (!isset($pdo) && file_exists('../db_config.php')) { // Garante $pdo se ainda não incluído
                    require_once '../db_config.php';
                }
                if (isset($pdo)) {
                    $stmt_sel = $pdo->prepare("SELECT id, nome, matricula FROM motoristas WHERE id = :id");
                    $stmt_sel->bindParam(':id', $destinatario_id_previo, PDO::PARAM_INT);
                    $stmt_sel->execute();
                    $motorista_sel = $stmt_sel->fetch(PDO::FETCH_ASSOC);
                    if ($motorista_sel) {
                        echo '<option value="' . htmlspecialchars($motorista_sel['id']) . '" selected>' . htmlspecialchars($motorista_sel['nome']) . ' (Matrícula: ' . htmlspecialchars($motorista_sel['matricula']) . ')</option>';
                    }
                }
            }
            ?>
        </select>
        <small class="form-text text-muted">Selecione um motorista específico se não marcou "Enviar para todos".</small>
    </div>

    <div class="form-group">
        <label for="assunto">Assunto <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="assunto" name="assunto" value="<?php echo htmlspecialchars($assunto_previo); ?>" maxlength="255">
    </div>

    <div class="form-group">
        <label for="mensagem">Mensagem <span class="text-danger">*</span></label>
        <textarea class="form-control" id="mensagem" name="mensagem" rows="8" required><?php echo htmlspecialchars($mensagem_previa); ?></textarea>
    </div>

    <input type="hidden" name="remetente" value="<?php echo htmlspecialchars($_SESSION['admin_user_name'] . ' - ' . $admin_nivel_acesso_logado); ?>">

    <hr>
    <button type="submit" name="enviar_mensagem" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Enviar Mensagem</button>
    <a href="mensagens_listar.php" class="btn btn-secondary">Cancelar</a>
</form>

<?php
// Definir o JavaScript específico para esta página
ob_start(); // Inicia o buffer de saída para capturar o script
?>
<script>
$(document).ready(function() {
    // Inicialização do Select2
    var $select2Destinatario = $('#destinatario_id_select2').select2({
        theme: 'bootstrap4',
        language: "pt-BR",
        width: '100%',
        allowClear: true,
        placeholder: 'Digite nome ou matrícula...', // Placeholder atualizado
        ajax: {
            url: 'buscar_motoristas_ajax.php',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
                    page: params.page || 1
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 10) < data.total_count // Ex: 10 resultados por página AJAX
                    }
                };
            },
            cache: true
        },
        minimumInputLength: 2, // Começa a buscar após N caracteres
        escapeMarkup: function (markup) { return markup; },
        templateResult: function (data) { // Função para formatar os resultados no dropdown
            if (data.loading) { return data.text; }
            // data.text já vem formatado do backend como "Nome (Matrícula)"
            return data.text;
        },
        templateSelection: function (data) { // Função para formatar o item selecionado
            // Se o ID for vazio (placeholder inicial), retorna o texto do placeholder
            if (!data.id) { return data.text; }
            // data.text já vem formatado
            return data.text;
        }
    });

    // Lógica do checkbox para mostrar/ocultar e validar o Select2
    function toggleDestinatarioIndividual() {
        var $wrapper = $('#campo_destinatario_individual_wrapper');
        var $selectInput = $('#destinatario_id_select2');
        var $asterisco = $('#asterisco_destinatario_individual');

        if ($('#enviar_para_todos_check').is(':checked')) {
            $wrapper.hide();
            $selectInput.prop('required', false).val(null).trigger('change'); // Limpa e desrequer o Select2
            $asterisco.hide();
        } else {
            $wrapper.show();
            $selectInput.prop('required', true); // Torna o Select2 obrigatório
            $asterisco.show();
        }
    }

    // Chama a função no carregamento da página para definir o estado inicial
    toggleDestinatarioIndividual();

    // Chama a função quando o estado do checkbox muda
    $('#enviar_para_todos_check').on('change', function() {
        toggleDestinatarioIndividual();
    });

    // Validação do formulário antes de submeter (opcional, mas bom)
    $('#form-enviar-mensagem').on('submit', function(e){
        if (!$('#enviar_para_todos_check').is(':checked')) {
            if (!$('#destinatario_id_select2').val() || $('#destinatario_id_select2').val() === "") {
                alert('Por favor, selecione um destinatário individual ou marque a opção "Enviar para TODOS OS MOTORISTAS".');
                $('#destinatario_id_select2').select2('open'); // Abre o select2 para o usuário
                e.preventDefault(); // Impede o envio do formulário
                return false;
            }
        }
        // Validação do campo mensagem (já tem 'required' no HTML, mas pode adicionar JS se quiser)
        if ($('#mensagem').val().trim() === "") {
            alert('O campo Mensagem é obrigatório.');
            $('#mensagem').focus();
            e.preventDefault();
            return false;
        }
    });
});
</script>
<?php
$page_specific_js = ob_get_clean(); // Captura o script para a variável que será usada no footer

require_once 'admin_footer.php'; // Inclui o rodapé, que agora processará $page_specific_js
?>