<?php 

// admin/motorista_processa.php
// AJUSTADO: Uso de chaves de sessão corretas para feedback na página de listagem.

require_once 'auth_check.php';
require_once '../db_config.php';

// ... (bloco de permissões e CSRF check - MANTIDO IGUAL AO ANTERIOR) ...
// Permissões base para processar (criar/editar)
$pode_processar_funcionario = false;
$funcionario_id_post = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$acao_original_post = trim($_POST['acao_original'] ?? ($funcionario_id_post ? 'editar' : 'cadastrar'));
$is_editing_self = ($funcionario_id_post && $funcionario_id_post == $_SESSION['admin_user_id']);

// Define quem pode realizar a ação
if ($admin_nivel_acesso_logado === 'Administrador') {
    $pode_processar_funcionario = true;
} elseif ($is_editing_self && in_array($admin_nivel_acesso_logado, ['Supervisores', 'Gerência'])) {
    $pode_processar_funcionario = true;
} elseif (!$funcionario_id_post && in_array($admin_nivel_acesso_logado, ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'])) {
    $pode_processar_funcionario = true;
} elseif ($funcionario_id_post && $acao_original_post !== 'reset_senha' && in_array($admin_nivel_acesso_logado, ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'])) {
    $pode_processar_funcionario = true;
} elseif ($funcionario_id_post && $acao_original_post === 'reset_senha' && in_array($admin_nivel_acesso_logado, ['Supervisores', 'Gerência', 'Administrador'])) {
    $pode_processar_funcionario = true;
}

if (!$pode_processar_funcionario) {
    // ALTERADO: Usa admin_error_message para a lista
    $_SESSION['admin_error_message'] = 'Você não tem permissão para executar esta ação.';
    header('Location: motoristas_listar.php');
    exit;
}

// CSRF Token Check
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token_form_funcionario'] ?? '', $_POST['csrf_token'])) {
    // ALTERADO: Usa admin_error_message para a lista, se for um erro geral de token.
    // Se for redirect para form, usa form_feedback ou form_errors.
    // Como é um erro de segurança, melhor redirecionar para a lista com mensagem geral.
    $_SESSION['admin_error_message'] = 'Erro de validação de segurança. Tente novamente.';
    $redirect_url_csrf = $funcionario_id_post ? 'motorista_formulario.php?id=' . $funcionario_id_post : 'motorista_formulario.php';
    $pagina_retorno_csrf = isset($_POST['pagina_retorno']) ? (int)$_POST['pagina_retorno'] : 1;
    // header('Location: ' . $redirect_url_csrf . '?pagina=' . $pagina_retorno_csrf); // Mantém se quiser erro no form
    header('Location: motoristas_listar.php?pagina=' . $pagina_retorno_csrf); // Ou para a lista
    exit;
}
unset($_SESSION['csrf_token_form_funcionario']);


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome_post = trim($_POST['nome'] ?? '');
    $matricula_post = trim($_POST['matricula'] ?? '');
    $status_post = trim($_POST['status'] ?? 'ativo');
    $cargo_post = trim($_POST['cargo'] ?? '');
    $data_contratacao_post = trim($_POST['data_contratacao'] ?? '');
    $tipo_veiculo_post = trim($_POST['tipo_veiculo'] ?? '');
    $email_post = trim($_POST['email'] ?? '');
    $telefone_post = trim($_POST['telefone'] ?? '');
    $senha_post = $_POST['senha'] ?? '';
    $confirmar_senha_post = $_POST['confirmar_senha'] ?? '';

    $erros_validacao = [];
    $_SESSION['form_data'] = $_POST;

    // --- Validações --- (MANTIDAS COMO ANTES)
    if ($acao_original_post !== 'reset_senha') {
        if (empty($nome_post)) { $erros_validacao[] = "O Nome Completo é obrigatório."; }
        if (empty($matricula_post)) { $erros_validacao[] = "A Matrícula é obrigatória."; }
        if (!empty($matricula_post) && strlen($matricula_post) > 50) { $erros_validacao[] = "A Matrícula não pode exceder 50 caracteres.";}
        if (empty($cargo_post)) { $erros_validacao[] = "O Cargo/Função é obrigatório."; }
        $cargos_permitidos = ['Motorista', 'Agente de Terminal', 'Catraca', 'CIOP Monitoramento', 'CIOP Planejamento', 'Instrutor', 'Porteiro', 'Soltura'];
        if (!empty($cargo_post) && !in_array($cargo_post, $cargos_permitidos)) { $erros_validacao[] = "Cargo/Função inválido selecionado.";}
        if ($cargo_post === 'Motorista' && empty($tipo_veiculo_post)) { $erros_validacao[] = "O Tipo de Veículo é obrigatório para motoristas.";}
        $tipos_veiculo_permitidos = ['Convencional', 'Micro'];
        if ($cargo_post === 'Motorista' && !empty($tipo_veiculo_post) && !in_array($tipo_veiculo_post, $tipos_veiculo_permitidos)) { $erros_validacao[] = "Tipo de Veículo inválido.";}
        if (!empty($data_contratacao_post)) {
            $d = DateTime::createFromFormat('Y-m-d', $data_contratacao_post);
            if (!$d || $d->format('Y-m-d') !== $data_contratacao_post) { $erros_validacao[] = "Formato da Data de Contratação inválido.";}
        }
        if (!empty($email_post) && !filter_var($email_post, FILTER_VALIDATE_EMAIL)) { $erros_validacao[] = "Formato de E-mail inválido.";}
        if (!empty($telefone_post) && strlen($telefone_post) > 20) { $erros_validacao[] = "O Telefone não pode exceder 20 caracteres.";}
    }

    $processar_senha = false;
    if (!$funcionario_id_post || $acao_original_post === 'reset_senha') {
        if (empty($senha_post)) { $erros_validacao[] = "A Senha é obrigatória."; }
        elseif (strlen($senha_post) < 6) { $erros_validacao[] = "A Senha deve ter no mínimo 6 caracteres."; }
        elseif ($senha_post !== $confirmar_senha_post) { $erros_validacao[] = "A Senha e a Confirmação de Senha não coincidem."; }
        else { $processar_senha = true; }
    } elseif ($funcionario_id_post && !empty($senha_post)) {
        if (strlen($senha_post) < 6) { $erros_validacao[] = "A Nova Senha deve ter no mínimo 6 caracteres."; }
        elseif ($senha_post !== $confirmar_senha_post) { $erros_validacao[] = "A Nova Senha e a Confirmação de Senha não coincidem."; }
        else { $processar_senha = true; }
    } elseif ($funcionario_id_post && empty($senha_post) && !empty($confirmar_senha_post)){
         $erros_validacao[] = "Para alterar a senha, preencha o campo 'Nova Senha' também.";
    }

    if ($pdo && !empty($matricula_post) && ($acao_original_post !== 'reset_senha')) {
        try {
            $sql_check_matricula = "SELECT id FROM motoristas WHERE matricula = :matricula";
            $params_check_matricula = [':matricula' => $matricula_post];
            if ($funcionario_id_post) {
                $sql_check_matricula .= " AND id != :id_funcionario";
                $params_check_matricula[':id_funcionario'] = $funcionario_id_post;
            }
            $stmt_check_matricula = $pdo->prepare($sql_check_matricula);
            $stmt_check_matricula->execute($params_check_matricula);
            if ($stmt_check_matricula->fetch()) { $erros_validacao[] = "A Matrícula '" . htmlspecialchars($matricula_post) . "' já está cadastrada.";}
        } catch (PDOException $e) { $erros_validacao[] = "Erro ao verificar duplicidade de matrícula."; error_log("Erro DB check matricula: " . $e->getMessage()); }
    }

    if (!empty($erros_validacao)) {
        $_SESSION['form_errors'] = $erros_validacao; // Esta chave é usada pelo formulário para exibir erros de validação
        $redirect_url = $funcionario_id_post ? 'motorista_formulario.php?id=' . $funcionario_id_post : 'motorista_formulario.php';
        if ($acao_original_post === 'reset_senha') { $redirect_url .= '&acao=reset_senha'; }
        $redirect_url .= (strpos($redirect_url, '?') === false ? '?' : '&') . http_build_query(array_filter([
            'pagina' => $_POST['pagina_retorno'] ?? null,
            'busca' => $_POST['busca_retorno'] ?? null,
            'status_filtro' => $_POST['status_filtro_retorno'] ?? null,
            'filtro_cargo' => $_POST['filtro_cargo_retorno'] ?? null
        ]));
        header('Location: ' . $redirect_url);
        exit;
    }

    $senha_hash = null;
    if ($processar_senha) {
        $senha_hash = password_hash($senha_post, PASSWORD_DEFAULT);
        if ($senha_hash === false) {
            // ALTERADO: Usa admin_error_message se for erro crítico que impede o processamento e redireciona para a lista
            $_SESSION['admin_error_message'] = 'Erro crítico ao processar a senha.';
            $redirect_url_err_pass = 'motoristas_listar.php?' . http_build_query(array_filter([
                'pagina' => $_POST['pagina_retorno'] ?? null,
                'busca' => $_POST['busca_retorno'] ?? null,
                'status_filtro' => $_POST['status_filtro_retorno'] ?? null,
                'filtro_cargo' => $_POST['filtro_cargo_retorno'] ?? null
            ]));
            header('Location: ' . $redirect_url_err_pass);
            exit;
        }
    }

    $sql_parts = [];
    $params = [];

    if ($funcionario_id_post) { // UPDATE
        if ($acao_original_post !== 'reset_senha') {
            $sql_parts = [
                "nome = :nome", "matricula = :matricula", "status = :status", "cargo = :cargo",
                "data_contratacao = :data_contratacao", "tipo_veiculo = :tipo_veiculo",
                "email = :email", "telefone = :telefone"
            ];
            $params = [
                ':nome' => $nome_post, ':matricula' => $matricula_post, ':status' => $status_post,
                ':cargo' => $cargo_post,
                ':data_contratacao' => !empty($data_contratacao_post) ? $data_contratacao_post : null,
                ':tipo_veiculo' => ($cargo_post === 'Motorista' && !empty($tipo_veiculo_post)) ? $tipo_veiculo_post : null,
                ':email' => !empty($email_post) ? $email_post : null,
                ':telefone' => !empty($telefone_post) ? $telefone_post : null,
                ':id' => $funcionario_id_post
            ];
        }
        if ($senha_hash) {
            $sql_parts[] = "senha = :senha";
            $params[':senha'] = $senha_hash;
            if(empty($params[':id'])) $params[':id'] = $funcionario_id_post; // Garante que :id está presente se só a senha mudou
        }

        if (empty($sql_parts)) {
             // ALTERADO: Usa admin_warning_message para a lista
            $_SESSION['admin_warning_message'] = "Nenhuma alteração detectada para o funcionário.";
        } else {
            $sql = "UPDATE motoristas SET " . implode(", ", $sql_parts) . " WHERE id = :id";
        }

    } else { // INSERT
        $sql = "INSERT INTO motoristas (nome, matricula, status, cargo, data_contratacao, tipo_veiculo, email, telefone, senha, data_cadastro)
                VALUES (:nome, :matricula, :status, :cargo, :data_contratacao, :tipo_veiculo, :email, :telefone, :senha, NOW())";
        $params = [
            ':nome' => $nome_post, ':matricula' => $matricula_post, ':status' => $status_post,
            ':cargo' => $cargo_post,
            ':data_contratacao' => !empty($data_contratacao_post) ? $data_contratacao_post : null,
            ':tipo_veiculo' => ($cargo_post === 'Motorista' && !empty($tipo_veiculo_post)) ? $tipo_veiculo_post : null,
            ':email' => !empty($email_post) ? $email_post : null,
            ':telefone' => !empty($telefone_post) ? $telefone_post : null,
            ':senha' => $senha_hash
        ];
    }

    if (isset($sql) && $pdo) {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            // ALTERADO: Usa admin_success_message para a lista
            $_SESSION['admin_success_message'] = 'Funcionário ' . ($funcionario_id_post ? ($acao_original_post === 'reset_senha' ? 'teve a senha redefinida' : 'atualizado') : 'cadastrado') . ' com sucesso!';
            unset($_SESSION['form_data']);
        } catch (PDOException $e) {
            // ALTERADO: Usa admin_error_message para a lista
            $_SESSION['admin_error_message'] = 'Erro ao salvar funcionário: Detalhes no log.';
            error_log("Erro DB ao salvar funcionário: " . $e->getMessage());
        }
    } elseif (!isset($sql) && $funcionario_id_post && empty($_SESSION['admin_warning_message'])) {
        // Caso de edição onde nada foi efetivamente alterado e já setamos a warning message.
        // Se a warning message não foi setada, algo estranho ocorreu.
         $_SESSION['admin_error_message'] = 'Nenhuma operação SQL foi preparada para salvar o funcionário.';
    }

    $redirect_final_url = 'motoristas_listar.php';
    $final_query_params = array_filter([
        'pagina' => $_POST['pagina_retorno'] ?? null,
        'busca' => $_POST['busca_retorno'] ?? null,
        'status_filtro' => $_POST['status_filtro_retorno'] ?? null,
        'filtro_cargo' => $_POST['filtro_cargo_retorno'] ?? null
    ]);
    if (!empty($final_query_params)) {
        $redirect_final_url .= '?' . http_build_query($final_query_params);
    }

    header('Location: ' . $redirect_final_url);
    exit;

} else {
    $_SESSION['admin_error_message'] = 'Acesso inválido.';
    header('Location: motoristas_listar.php');
    exit;
}
?>