<?php
// admin/usuario_formulario.php

require_once 'auth_check.php'; // Define $admin_nivel_acesso_logado, $_SESSION['admin_user_id'], etc.

// Permissão para ACESSAR este formulário (criar ou editar usuários)
// Somente 'Administrador' pode criar/editar outros usuários.
// Outros níveis (Supervisor, Gerência) não devem acessar este form para criar/editar outros.
// Uma exceção pode ser para editar o próprio perfil (com campos limitados).
// Por ora, vamos restringir o acesso geral a este formulário ao 'Administrador'.
if ($admin_nivel_acesso_logado !== 'Administrador') {
    // Se não for Administrador, verifica se é um usuário tentando editar o próprio perfil (ID na URL é o ID logado)
    $id_usuario_para_editar = isset($_GET['id']) ? (int)$_GET['id'] : null;
    if (!($id_usuario_para_editar && $id_usuario_para_editar == $_SESSION['admin_user_id'] && in_array($admin_nivel_acesso_logado, ['Supervisores', 'Gerência']))) {
        $_SESSION['admin_error_message'] = "Você não tem permissão para criar ou editar usuários administrativos.";
        header('Location: usuarios_listar.php');
        exit;
    }
    // Se chegou aqui, é um Supervisor/Gerente editando o PRÓPRIO perfil.
    // O formulário abaixo precisará de lógica para desabilitar campos como 'nivel_acesso'.
}


require_once '../db_config.php';

$usuario_id_edicao = null;
$nome_usuario_form = '';
$username_form = '';
$email_form = '';
$nivel_acesso_form = ''; // Valor padrão ou o primeiro da lista
$modo_edicao_form = false;
$page_title_action = 'Adicionar Novo Usuário do Painel';

// Lista de níveis de acesso disponíveis para o select
// É importante que estes valores correspondam exatamente aos usados na lógica de permissão
$todos_niveis_acesso = ['Agente de Terminal', 'Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];


if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $usuario_id_edicao = (int)$_GET['id'];
    $modo_edicao_form = true;
    $page_title_action = 'Editar Usuário do Painel';

    // Se for um Supervisor/Gerente editando o próprio perfil, ajusta o título
    if ($admin_nivel_acesso_logado !== 'Administrador' && $usuario_id_edicao == $_SESSION['admin_user_id']) {
        $page_title_action = 'Editar Meu Perfil';
    }


    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT nome, username, email, nivel_acesso FROM administradores WHERE id = :id");
            $stmt->bindParam(':id', $usuario_id_edicao, PDO::PARAM_INT);
            $stmt->execute();
            $usuario_db = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario_db) {
                $nome_usuario_form = $usuario_db['nome'];
                $username_form = $usuario_db['username'];
                $email_form = $usuario_db['email'];
                $nivel_acesso_form = $usuario_db['nivel_acesso'];
            } else {
                $_SESSION['admin_error_message'] = "Usuário ID {$usuario_id_edicao} não encontrado para edição.";
                header('Location: usuarios_listar.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log("Erro ao buscar usuário para edição (ID: {$usuario_id_edicao}): " . $e->getMessage());
            $_SESSION['admin_error_message'] = "Erro ao carregar dados do usuário para edição.";
            header('Location: usuarios_listar.php');
            exit;
        }
    } else {
        $_SESSION['admin_error_message'] = "Falha na conexão com o banco ao tentar carregar usuário para edição.";
        header('Location: usuarios_listar.php');
        exit;
    }
}

$page_title = $page_title_action;
require_once 'admin_header.php';

// Para repopulação em caso de erro de validação
$form_data_repop = $_SESSION['form_data_usuario'] ?? [];
if (!empty($form_data_repop)) {
    $nome_usuario_form = $form_data_repop['nome'] ?? $nome_usuario_form;
    $username_form = $form_data_repop['username'] ?? $username_form;
    $email_form = $form_data_repop['email'] ?? $email_form;
    $nivel_acesso_form = $form_data_repop['nivel_acesso'] ?? $nivel_acesso_form;
    // Senha não é repopulada por segurança
    unset($_SESSION['form_data_usuario']);
}

$is_editing_self = ($modo_edicao_form && $usuario_id_edicao == $_SESSION['admin_user_id']);
$can_edit_nivel_acesso = ($admin_nivel_acesso_logado === 'Administrador' && !$is_editing_self) || ($admin_nivel_acesso_logado === 'Administrador' && $is_editing_self && $usuario_id_edicao != 1); // Admin ID 1 é super admin, não pode rebaixar? Decida regra.
// Regra mais simples: Apenas Admin pode mudar nível, e não pode rebaixar a si mesmo se for o único admin, ou o admin principal (ID 1)
// Por enquanto: Apenas Admin pode mudar nível de outros. Admin não pode mudar o próprio nível se for ID 1.
$disable_nivel_acesso_field = !$admin_nivel_acesso_logado === 'Administrador' || ($is_editing_self && $admin_nivel_acesso_logado === 'Administrador' && $usuario_id_edicao === 1); // Exemplo: não deixar admin ID 1 mudar seu próprio nível
if ($admin_nivel_acesso_logado !== 'Administrador' && $is_editing_self) { // Supervisor/Gerente editando próprio perfil
    $disable_nivel_acesso_field = true;
}


?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title_action); ?></h1>
    <a href="usuarios_listar.php?pagina=<?php echo isset($_GET['pagina']) ? htmlspecialchars($_GET['pagina']) : '1'; ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Voltar para Lista de Usuários
    </a>
</div>

<?php
if (isset($_SESSION['admin_form_error_usuario'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_form_error_usuario']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
    unset($_SESSION['admin_form_error_usuario']);
}
?>

<form action="usuario_processa.php" method="POST" id="form-usuario-admin">
    <?php if ($modo_edicao_form && $usuario_id_edicao): ?>
        <input type="hidden" name="usuario_id" value="<?php echo $usuario_id_edicao; ?>">
    <?php endif; ?>

    <div class="form-row">
        <div class="form-group col-md-6">
            <label for="nome">Nome Completo <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($nome_usuario_form); ?>" required maxlength="255">
        </div>
        <div class="form-group col-md-6">
            <label for="username">Usuário (Login) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username_form); ?>" required maxlength="50" <?php echo ($modo_edicao_form && $is_editing_self && $admin_nivel_acesso_logado !== 'Administrador') ? 'readonly' : ''; ?>>
            <?php if ($modo_edicao_form && $is_editing_self && $admin_nivel_acesso_logado !== 'Administrador'): ?>
                <small class="form-text text-muted">Você não pode alterar seu nome de usuário.</small>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group col-md-6">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email_form); ?>" maxlength="255">
        </div>
        <div class="form-group col-md-6">
            <label for="nivel_acesso">Nível de Acesso <span class="text-danger">*</span></label>
            <select class="form-control" id="nivel_acesso" name="nivel_acesso" required <?php echo $disable_nivel_acesso_field ? 'disabled' : ''; ?>>
                <option value="">Selecione o nível...</option>
                <?php foreach ($todos_niveis_acesso as $nivel): ?>
                    <option value="<?php echo htmlspecialchars($nivel); ?>" <?php echo ($nivel_acesso_form == $nivel) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($nivel); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($disable_nivel_acesso_field): ?>
                <small class="form-text text-muted">O nível de acesso não pode ser alterado por você<?php echo $is_editing_self ? ' para este perfil' : ''; ?>.</small>
                <?php // Se estiver desabilitado, precisamos enviar o valor original para o backend se não quisermos que ele seja perdido ao salvar
                if ($modo_edicao_form) { // Envia o valor original se estiver desabilitado para que não se perca no processamento
                    echo '<input type="hidden" name="nivel_acesso" value="' . htmlspecialchars($nivel_acesso_form) . '">';
                }
                ?>
            <?php endif; ?>
        </div>
    </div>

    <fieldset class="mt-3 border p-3">
        <legend class="w-auto px-2 h6"><?php echo $modo_edicao_form ? 'Alterar Senha (Opcional)' : 'Definir Senha <span class="text-danger">*</span>'; ?></legend>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="senha">Senha</label>
                <input type="password" class="form-control" id="senha" name="senha" <?php echo !$modo_edicao_form ? 'required' : ''; ?> minlength="6">
                <?php if ($modo_edicao_form): ?>
                    <small class="form-text text-muted">Deixe em branco para não alterar a senha atual.</small>
                <?php else: ?>
                    <small class="form-text text-muted">Mínimo de 6 caracteres.</small>
                <?php endif; ?>
            </div>
            <div class="form-group col-md-6">
                <label for="confirma_senha">Confirmar Senha</label>
                <input type="password" class="form-control" id="confirma_senha" name="confirma_senha" <?php echo !$modo_edicao_form ? 'required' : ''; ?> minlength="6">
                <small class="form-text text-muted">Repita a senha. <?php echo $modo_edicao_form ? '(Obrigatório somente se a senha for alterada)' : ''; ?></small>
            </div>
        </div>
    </fieldset>

    <hr>
    <button type="submit" name="salvar_usuario_admin" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Usuário</button>
    <a href="usuarios_listar.php?pagina=<?php echo isset($_GET['pagina']) ? htmlspecialchars($_GET['pagina']) : '1'; ?>" class="btn btn-secondary">Cancelar</a>
</form>

<?php
// Lógica JavaScript para o formulário (ex: validação de confirmação de senha)
ob_start();
?>
<script>
$(document).ready(function() {
    $('#form-usuario-admin').on('submit', function(e) {
        var senha = $('#senha').val();
        var confirmaSenha = $('#confirma_senha').val();

        // Se estiver em modo de cadastro, OU se a senha foi preenchida em modo de edição
        var isModoCadastro = !<?php echo json_encode($modo_edicao_form); ?>; // true se cadastro, false se edição
        
        if (isModoCadastro || (senha !== '' && senha !== null)) {
            if (senha.length > 0 && senha.length < 6) {
                alert('A senha deve ter no mínimo 6 caracteres.');
                $('#senha').focus();
                e.preventDefault();
                return false;
            }
            if (senha !== confirmaSenha) {
                alert('A senha e a confirmação de senha não coincidem.');
                $('#confirma_senha').focus();
                e.preventDefault();
                return false;
            }
        } else if (senha === '' && confirmaSenha !== '') {
            // Se a senha estiver vazia mas a confirmação não (em modo de edição)
            alert('Se for alterar a senha, preencha o campo "Senha" também.');
            $('#senha').focus();
            e.preventDefault();
            return false;
        }
        // Outras validações podem ser adicionadas aqui
    });

    // Se o campo nivel_acesso estiver desabilitado, e estamos editando,
    // precisamos garantir que o valor original seja enviado se o select estiver disabled.
    // O input hidden já faz isso, mas aqui é uma alternativa caso o select seja readonly e não disabled.
    // Se o campo nivel_acesso estiver desabilitado, o PHP já adicionou um input hidden
    // com o valor atual, então o JS não precisa intervir para reenviar,
    // a menos que queiramos manipular o select em si.
    <?php if ($disable_nivel_acesso_field): ?>
        // $('#nivel_acesso').prop('disabled', true); // O PHP já faz isso
    <?php endif; ?>
});
</script>
<?php
$page_specific_js = ob_get_clean();
require_once 'admin_footer.php';
?>