<?php
// admin/veiculo_formulario.php

require_once 'auth_check.php'; // Autenticação e permissões básicas

// --- Definição de Permissões para ESTA PÁGINA ---
$niveis_permitidos_formulario_veiculos = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_formulario_veiculos)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para criar ou editar veículos.";
    header('Location: veiculos_listar.php'); // Redireciona para a lista se não tiver permissão
    exit;
}

require_once '../db_config.php';

// --- Inicialização de Variáveis do Formulário ---
$veiculo_id_edicao = null;
$prefixo_form = '';
$tipo_veiculo_form = ''; // Será o valor do ENUM
$status_veiculo_form = 'operação'; // Padrão para novo veículo

$modo_edicao_veiculo_form = false;
$page_title_action = 'Adicionar Novo Veículo'; // Título da ação padrão

// Tipos de veículos para o select (direto do ENUM definido no banco)
$tipos_veiculo_opcoes = [
    'Convencional Amarelo', 'Convencional Amarelo com Ar', 'Micro', 'Micro com Ar',
    'Convencional Azul', 'Convencional Azul com Ar', 'Padron Azul', 'SuperBus', 'Leve'
];
$status_veiculo_opcoes_form = ['operação', 'fora de operação'];

// Parâmetros GET para voltar para a listagem com filtros corretos (se houver filtros na listagem)
$params_retorno_lista_veic_form = [];
if (isset($_GET['pagina'])) $params_retorno_lista_veic_form['pagina'] = $_GET['pagina'];
if (isset($_GET['busca_prefixo'])) $params_retorno_lista_veic_form['busca_prefixo'] = $_GET['busca_prefixo'];
if (isset($_GET['busca_tipo'])) $params_retorno_lista_veic_form['busca_tipo'] = $_GET['busca_tipo'];
if (isset($_GET['busca_status'])) $params_retorno_lista_veic_form['busca_status'] = $_GET['busca_status'];

$query_string_retorno_veic_form = http_build_query($params_retorno_lista_veic_form);
$link_voltar_lista_veic_form = 'veiculos_listar.php' . ($query_string_retorno_veic_form ? '?' . $query_string_retorno_veic_form : '');


// --- Lógica para Modo de Edição ---
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $veiculo_id_edicao = (int)$_GET['id'];
    $modo_edicao_veiculo_form = true;
    $page_title_action = 'Editar Veículo';

    if ($pdo) {
        try {
            $stmt_veiculo = $pdo->prepare("SELECT prefixo, tipo, status FROM veiculos WHERE id = :id");
            $stmt_veiculo->bindParam(':id', $veiculo_id_edicao, PDO::PARAM_INT);
            $stmt_veiculo->execute();
            $veiculo_db_data = $stmt_veiculo->fetch(PDO::FETCH_ASSOC);

            if ($veiculo_db_data) {
                $prefixo_form = $veiculo_db_data['prefixo'];
                $tipo_veiculo_form = $veiculo_db_data['tipo'];
                $status_veiculo_form = $veiculo_db_data['status'];
                $page_title_action .= ' - Prefixo: ' . htmlspecialchars($prefixo_form);
            } else {
                $_SESSION['admin_error_message'] = "Veículo ID {$veiculo_id_edicao} não encontrado para edição.";
                header('Location: ' . $link_voltar_lista_veic_form);
                exit;
            }
        } catch (PDOException $e) {
            error_log("Erro ao buscar veículo para edição (ID: {$veiculo_id_edicao}): " . $e->getMessage());
            $_SESSION['admin_error_message'] = "Erro ao carregar dados do veículo para edição.";
            header('Location: ' . $link_voltar_lista_veic_form);
            exit;
        }
    } else {
         $_SESSION['admin_error_message'] = "Falha na conexão com o banco de dados (formulário de veículo).";
         header('Location: ' . $link_voltar_lista_veic_form);
         exit;
    }
}

$page_title = $page_title_action; // Define o título que será usado no admin_header.php
require_once 'admin_header.php';

// Para repopulação em caso de erro de validação do veiculo_processa.php
$form_data_repop_veiculo = $_SESSION['form_data_veiculo'] ?? [];
if (!empty($form_data_repop_veiculo)) {
    $prefixo_form = $form_data_repop_veiculo['prefixo_veiculo'] ?? $prefixo_form;
    $tipo_veiculo_form = $form_data_repop_veiculo['tipo_veiculo'] ?? $tipo_veiculo_form;
    $status_veiculo_form = $form_data_repop_veiculo['status_veiculo'] ?? $status_veiculo_form;
    unset($_SESSION['form_data_veiculo']);
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title_action); ?></h1>
    <a href="<?php echo $link_voltar_lista_veic_form; ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Voltar para Lista de Veículos
    </a>
</div>

<?php
// Exibir mensagens de erro do formulário (se houver, de uma tentativa anterior de salvar que falhou e redirecionou de volta)
if (isset($_SESSION['admin_form_error_veiculo'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . nl2br(htmlspecialchars($_SESSION['admin_form_error_veiculo'])) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
    unset($_SESSION['admin_form_error_veiculo']);
}
?>

<form action="veiculo_processa.php<?php echo ($query_string_retorno_veic_form ? '?' . $query_string_retorno_veic_form : ''); ?>" method="POST" id="form-veiculo">
    <?php if ($modo_edicao_veiculo_form && $veiculo_id_edicao): ?>
        <input type="hidden" name="veiculo_id" value="<?php echo $veiculo_id_edicao; ?>">
    <?php endif; ?>

    <div class="form-row">
        <div class="form-group col-md-4">
            <label for="prefixo_veiculo">Prefixo <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="prefixo_veiculo" name="prefixo_veiculo" 
                   value="<?php echo htmlspecialchars($prefixo_form); ?>" 
                   required 
                   maxlength="4" 
                   pattern="\d{4}" 
                   title="O prefixo deve conter exatamente 4 números."
                   inputmode="numeric"
                   placeholder="Ex: 5100">
            <small class="form-text text-muted">Exatamente 4 números.</small>
        </div>
        <div class="form-group col-md-4">
            <label for="tipo_veiculo_select">Tipo de Veículo <span class="text-danger">*</span></label>
            <select class="form-control" id="tipo_veiculo_select" name="tipo_veiculo" required>
                <option value="">Selecione o tipo...</option>
                <?php foreach ($tipos_veiculo_opcoes as $tipo_v_opt): ?>
                    <option value="<?php echo htmlspecialchars($tipo_v_opt); ?>" <?php echo ($tipo_veiculo_form == $tipo_v_opt) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tipo_v_opt); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-4">
            <label for="status_veiculo_select">Status <span class="text-danger">*</span></label>
            <select class="form-control" id="status_veiculo_select" name="status_veiculo" required>
                <?php foreach ($status_veiculo_opcoes_form as $status_v_opt): ?>
                    <option value="<?php echo htmlspecialchars($status_v_opt); ?>" <?php echo ($status_veiculo_form == $status_v_opt) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst($status_v_opt)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <hr>
    <button type="submit" name="salvar_veiculo" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Veículo</button>
    <a href="<?php echo $link_voltar_lista_veic_form; ?>" class="btn btn-secondary">Cancelar</a>
</form>

<?php
// Adicionar um pequeno script para feedback visual da validação do prefixo, se desejado
ob_start();
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const prefixoInput = document.getElementById('prefixo_veiculo');
    if (prefixoInput) {
        prefixoInput.addEventListener('input', function(e) {
            // Remove caracteres não numéricos
            e.target.value = e.target.value.replace(/\D/g, '');
            // Limita a 4 dígitos (maxlength já faz isso, mas é uma segurança extra)
            if (e.target.value.length > 4) {
                e.target.value = e.target.value.slice(0, 4);
            }
        });

        // Feedback visual da validação HTML5 (opcional, mas útil)
        prefixoInput.addEventListener('invalid', function() {
            if (prefixoInput.validity.patternMismatch) {
                prefixoInput.setCustomValidity('O prefixo deve conter exatamente 4 números.');
            } else if (prefixoInput.validity.valueMissing) {
                prefixoInput.setCustomValidity('O campo Prefixo é obrigatório.');
            }
        });
        prefixoInput.addEventListener('input', function() { // Limpa mensagem customizada ao corrigir
            prefixoInput.setCustomValidity('');
        });
    }
});
</script>
<?php
$page_specific_js = ob_get_clean(); // Captura o script
require_once 'admin_footer.php'; // O footer irá ecoar $page_specific_js
?>