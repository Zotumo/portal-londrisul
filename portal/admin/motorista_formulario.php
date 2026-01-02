<?php
// admin/motorista_formulario.php
// ATUALIZADO: Inclui máscara de telefone dinâmica.

require_once 'auth_check.php'; // Centraliza verificação de autenticação e nível
require_once '../db_config.php';

// Níveis de acesso e permissões (adaptar conforme sua necessidade)
$pode_cadastrar_editar_basico = in_array($admin_nivel_acesso_logado, ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador']);
$pode_gerenciar_status_senha = in_array($admin_nivel_acesso_logado, ['Supervisores', 'Gerência', 'Administrador']);

$page_title = "Novo Funcionário"; // Título padrão
$funcionario_id = null; // Usar nome genérico
$nome_form = '';
$matricula_form = '';
$status_form = 'ativo'; // Padrão para novo
$cargo_form = 'Motorista'; // Padrão para novo, já que o DB também tem default
$data_contratacao_form = '';
$tipo_veiculo_form = '';
$email_form = '';
$telefone_form = '';

$acao_original = ''; // Para o caso de reset de senha
$token_form = uniqid('csrf_form_func_', true); // CSRF Token

// Parâmetros de retorno para listagem
$pagina_retorno = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$busca_retorno = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$status_filtro_retorno = isset($_GET['status_filtro']) ? trim($_GET['status_filtro']) : '';
$filtro_cargo_retorno = isset($_GET['filtro_cargo']) ? trim($_GET['filtro_cargo']) : '';

$query_params_retorno_array = [
    'pagina' => $pagina_retorno,
    'busca' => $busca_retorno,
    'status_filtro' => $status_filtro_retorno,
    'filtro_cargo' => $filtro_cargo_retorno
];
// Remove chaves com valores vazios para não poluir a URL
$query_params_retorno_array = array_filter($query_params_retorno_array, function($value) { return $value !== '' && $value !== null; });
$query_params_retorno_string = http_build_query($query_params_retorno_array);


if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $funcionario_id = (int)$_GET['id'];

    if (!$pode_cadastrar_editar_basico && !(isset($_GET['acao']) && $_GET['acao'] == 'reset_senha' && $pode_gerenciar_status_senha)) {
        $_SESSION['admin_error_message'] = "Você não tem permissão para editar este funcionário.";
        header('Location: motoristas_listar.php?' . $query_params_retorno_string);
        exit;
    }

    if (isset($_GET['acao']) && $_GET['acao'] == 'reset_senha') {
        if (!$pode_gerenciar_status_senha) {
            $_SESSION['admin_error_message'] = "Você não tem permissão para redefinir a senha deste funcionário.";
            header('Location: motoristas_listar.php?' . $query_params_retorno_string);
            exit;
        }
        $page_title = "Redefinir Senha do Funcionário";
        $acao_original = 'reset_senha'; // Para identificar no processa
    } else {
        $page_title = "Editar Funcionário";
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM motoristas WHERE id = :id");
        $stmt->bindParam(':id', $funcionario_id, PDO::PARAM_INT);
        $stmt->execute();
        $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($funcionario) {
            $nome_form = $funcionario['nome'];
            $matricula_form = $funcionario['matricula'];
            $status_form = $funcionario['status'];
            $cargo_form = $funcionario['cargo']; // Carrega o cargo do DB
            $data_contratacao_form = $funcionario['data_contratacao'];
            $tipo_veiculo_form = $funcionario['tipo_veiculo'];
            $email_form = $funcionario['email'];
            $telefone_form = $funcionario['telefone'];
        } else {
            $_SESSION['admin_error_message'] = "Funcionário não encontrado.";
            header('Location: motoristas_listar.php?' . $query_params_retorno_string);
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['admin_error_message'] = "Erro ao buscar funcionário: " . $e->getMessage();
        header('Location: motoristas_listar.php?' . $query_params_retorno_string);
        exit;
    }
} else {
    if (!$pode_cadastrar_editar_basico) {
        $_SESSION['admin_error_message'] = "Você não tem permissão para cadastrar novos funcionários.";
        header('Location: motoristas_listar.php?' . $query_params_retorno_string);
        exit;
    }
    // Se não tem ID, é um novo funcionário
}

$_SESSION['csrf_token_form_funcionario'] = $token_form;

// Lista de cargos para o select (conforme definido pelo usuário)
$cargos_disponiveis = ['Motorista', 'Agente de Terminal', 'Catraca', 'CIOP Monitoramento', 'CIOP Planejamento', 'Instrutor', 'Porteiro', 'Soltura'];

require_once 'admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="motoristas_listar.php?<?php echo $query_params_retorno_string; ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Voltar para Lista
        </a>
    </div>
</div>

<?php
// Feedback de ações (do processa.php)
if (isset($_SESSION['form_feedback'])) {
    $feedback = $_SESSION['form_feedback'];
    $alert_class = $feedback['type'] === 'success' ? 'alert-success' : 'alert-danger';
    echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">' . htmlspecialchars($feedback['message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
    unset($_SESSION['form_feedback']);
}
// Feedback de erros de validação (do processa.php, se houver redirect com erros)
if (isset($_SESSION['form_errors'])) {
    echo '<div class="alert alert-danger" role="alert"><strong>Por favor, corrija os seguintes erros:</strong><ul>';
    foreach ($_SESSION['form_errors'] as $error) {
        echo '<li>' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul></div>';
    // Repopular campos se houver erros e dados de formulário antigos
    if(isset($_SESSION['form_data'])) {
        $form_data = $_SESSION['form_data'];
        $nome_form = htmlspecialchars($form_data['nome'] ?? $nome_form);
        $matricula_form = htmlspecialchars($form_data['matricula'] ?? $matricula_form);
        $status_form = htmlspecialchars($form_data['status'] ?? $status_form);
        $cargo_form = htmlspecialchars($form_data['cargo'] ?? $cargo_form);
        $data_contratacao_form = htmlspecialchars($form_data['data_contratacao'] ?? $data_contratacao_form);
        $tipo_veiculo_form = htmlspecialchars($form_data['tipo_veiculo'] ?? $tipo_veiculo_form);
        $email_form = htmlspecialchars($form_data['email'] ?? $email_form);
        $telefone_form = htmlspecialchars($form_data['telefone'] ?? $telefone_form);
    }
    unset($_SESSION['form_errors']);
    unset($_SESSION['form_data']);
}
?>

<form method="POST" action="motorista_processa.php" id="funcionarioForm">
    <input type="hidden" name="id" value="<?php echo $funcionario_id; ?>">
    <input type="hidden" name="acao_original" value="<?php echo htmlspecialchars($acao_original); ?>">
    <input type="hidden" name="csrf_token" value="<?php echo $token_form; ?>">
    <input type="hidden" name="pagina_retorno" value="<?php echo $pagina_retorno; ?>">
    <input type="hidden" name="busca_retorno" value="<?php echo htmlspecialchars($busca_retorno); ?>">
    <input type="hidden" name="status_filtro_retorno" value="<?php echo htmlspecialchars($status_filtro_retorno); ?>">
    <input type="hidden" name="filtro_cargo_retorno" value="<?php echo htmlspecialchars($filtro_cargo_retorno); ?>">


    <?php if ($acao_original != 'reset_senha'): ?>
        <div class="row">
            <div class="form-group col-md-6">
                <label for="nome">Nome Completo <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($nome_form); ?>" required maxlength="255">
            </div>
            <div class="form-group col-md-6">
                <label for="matricula">Matrícula <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="matricula" name="matricula" value="<?php echo htmlspecialchars($matricula_form); ?>" required maxlength="50">
            </div>
        </div>

        <div class="row">
            <div class="form-group col-md-4">
                <label for="cargo">Cargo/Função <span class="text-danger">*</span></label>
                <select class="form-control" id="cargo" name="cargo" required>
                    <option value="">Selecione o Cargo...</option>
                    <?php foreach ($cargos_disponiveis as $cargo_opt): ?>
                        <option value="<?php echo htmlspecialchars($cargo_opt); ?>" <?php echo ($cargo_form == $cargo_opt) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cargo_opt); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-4" id="campo_tipo_veiculo_wrapper" style="<?php echo ($cargo_form == 'Motorista') ? '' : 'display: none;'; ?>">
                <label for="tipo_veiculo">Tipo de Veículo <span class="text-danger" id="tipo_veiculo_required_star" style="<?php echo ($cargo_form == 'Motorista') ? '' : 'display: none;'; ?>">*</span></label>
                <select class="form-control" id="tipo_veiculo" name="tipo_veiculo">
                    <option value="">Selecione o tipo...</option>
                    <option value="Convencional" <?php echo ($tipo_veiculo_form == 'Convencional') ? 'selected' : ''; ?>>Convencional</option>
                    <option value="Micro" <?php echo ($tipo_veiculo_form == 'Micro') ? 'selected' : ''; ?>>Micro</option>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="data_contratacao">Data de Contratação</label>
                <input type="date" class="form-control" id="data_contratacao" name="data_contratacao" value="<?php echo htmlspecialchars($data_contratacao_form); ?>">
            </div>
        </div>

        <div class="row">
            <div class="form-group col-md-6">
                <label for="email">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email_form); ?>" maxlength="255">
            </div>
            <div class="form-group col-md-6">
                <label for="telefone">Telefone / Contato</label>
                <input type="tel" class="form-control" id="telefone" name="telefone" value="<?php echo htmlspecialchars($telefone_form); ?>" placeholder="(XX) XXXXX-XXXX" maxlength="15">
            </div>
        </div>

        <?php if ($pode_gerenciar_status_senha && $funcionario_id): // Só mostra status para edição e se tiver permissão ?>
        <div class="form-group">
            <label for="status">Status <span class="text-danger">*</span></label>
            <select class="form-control" id="status" name="status" required>
                <option value="ativo" <?php echo ($status_form == 'ativo') ? 'selected' : ''; ?>>Ativo</option>
                <option value="inativo" <?php echo ($status_form == 'inativo') ? 'selected' : ''; ?>>Inativo</option>
            </select>
        </div>
        <?php elseif (!$funcionario_id): // Se for novo, status é ativo por padrão e não precisa ser editável aqui, a menos que queira ?>
            <input type="hidden" name="status" value="ativo">
        <?php endif; ?>
    <?php endif; // Fim do if ($acao_original != 'reset_senha') ?>


    <?php if (!$funcionario_id || $acao_original == 'reset_senha'): // Campos de senha para novo ou reset ?>
        <hr>
        <h5><?php echo ($acao_original == 'reset_senha' ? 'Redefinir Senha' : 'Definir Senha de Acesso ao Portal'); ?></h5>
        <p class="small text-muted">
            <?php if ($acao_original == 'reset_senha'): ?>
                Digite uma nova senha para o funcionário.
            <?php elseif($cargo_form == 'Motorista'): // Verifica se o cargo padrão ou selecionado é Motorista ?>
                Esta senha será usada para o funcionário acessar o Portal do Motorista. Mínimo 6 caracteres.
            <?php else: ?>
                Defina uma senha caso este funcionário precise acessar alguma área restrita do sistema (opcional). Mínimo 6 caracteres.
            <?php endif; ?>
        </p>
        <div class="row">
            <div class="form-group col-md-6">
                <label for="senha">Nova Senha</label>
                <input type="password" class="form-control" id="senha" name="senha" <?php echo (!$funcionario_id ? 'required' : ''); ?> minlength="6">
            </div>
            <div class="form-group col-md-6">
                <label for="confirmar_senha">Confirmar Nova Senha</label>
                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" <?php echo (!$funcionario_id ? 'required' : ''); ?> minlength="6">
            </div>
        </div>
    <?php endif; ?>

    <hr>
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i>
        <?php echo ($funcionario_id && $acao_original != 'reset_senha' ? 'Atualizar Funcionário' : ($acao_original == 'reset_senha' ? 'Redefinir Senha' : 'Cadastrar Funcionário')); ?>
    </button>
    <a href="motoristas_listar.php?<?php echo $query_params_retorno_string; ?>" class="btn btn-secondary">Cancelar</a>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cargoSelect = document.getElementById('cargo');
    const tipoVeiculoWrapper = document.getElementById('campo_tipo_veiculo_wrapper');
    const tipoVeiculoInput = document.getElementById('tipo_veiculo'); // Agora é o <select>
    const tipoVeiculoRequiredStar = document.getElementById('tipo_veiculo_required_star');

    function toggleTipoVeiculoField() {
        if (cargoSelect && tipoVeiculoWrapper && tipoVeiculoInput && tipoVeiculoRequiredStar) {
            if (cargoSelect.value === 'Motorista') {
                tipoVeiculoWrapper.style.display = '';
                tipoVeiculoInput.required = true;
                tipoVeiculoRequiredStar.style.display = '';
            } else {
                tipoVeiculoWrapper.style.display = 'none';
                tipoVeiculoInput.value = '';
                tipoVeiculoInput.required = false;
                tipoVeiculoRequiredStar.style.display = 'none';
            }
        }
    }

    if (cargoSelect) {
        cargoSelect.addEventListener('change', toggleTipoVeiculoField);
        toggleTipoVeiculoField();
    }

    const telefoneInput = document.getElementById('telefone');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é dígito
            let formattedValue = '';

            if (value.length > 0) {
                formattedValue = '(' + value.substring(0, 2);
            }
            if (value.length > 2) {
                formattedValue += ') ';
                if (value.length <= 10) { // Fixo ou celular incompleto (sem o 9 extra ainda)
                    formattedValue += value.substring(2, 6);
                    if (value.length > 6) {
                        formattedValue += '-' + value.substring(6, 10);
                    }
                } else { // Celular com 9 dígitos
                    formattedValue += value.substring(2, 7); // Pega os 5 dígitos (incluindo o 9)
                    if (value.length > 7) {
                        formattedValue += '-' + value.substring(7, 11);
                    }
                }
            }
            e.target.value = formattedValue;
        });
    }

    const form = document.getElementById('funcionarioForm');
    const senhaInput = document.getElementById('senha');
    const confirmarSenhaInput = document.getElementById('confirmar_senha');

    if (form && senhaInput && confirmarSenhaInput) {
        form.addEventListener('submit', function(event) {
            if (document.body.contains(senhaInput) && document.body.contains(confirmarSenhaInput)) {
                if (senhaInput.value !== '' || !<?php echo json_encode(boolval($funcionario_id)); ?>) {
                    if (senhaInput.value !== confirmarSenhaInput.value) {
                        alert('As senhas não coincidem!');
                        event.preventDefault();
                        if(confirmarSenhaInput) confirmarSenhaInput.focus();
                    }
                }
            }
        });
    }
});
</script>

<?php
require_once 'admin_footer.php';
?>