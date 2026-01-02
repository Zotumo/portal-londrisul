<?php
// admin/usuario_processa.php

require_once 'auth_check.php'; // Garante autenticação e define $admin_nivel_acesso_logado, $_SESSION['admin_user_id'], etc.
require_once '../db_config.php'; // Conexão com o banco

// Permissões base para processar (criar/editar)
// A lógica fina de quem pode editar o quê será feita mais abaixo.
$pode_processar_usuario = false;
$usuario_id_param = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT); // ID do usuário sendo editado, se houver
$is_editing_self = ($usuario_id_param && $usuario_id_param == $_SESSION['admin_user_id']);

if ($admin_nivel_acesso_logado === 'Administrador') {
    $pode_processar_usuario = true; // Administrador pode processar qualquer um (com ressalvas para si mesmo)
} elseif ($is_editing_self && in_array($admin_nivel_acesso_logado, ['Supervisores', 'Gerência'])) {
    $pode_processar_usuario = true; // Supervisor/Gerente editando o próprio perfil
}

if (!$pode_processar_usuario) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para executar esta ação.";
    header('Location: usuarios_listar.php');
    exit;
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_usuario_admin'])) {

    // Obter dados do formulário
    $nome = trim($_POST['nome'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? null); // Email pode ser nulo/opcional
    $nivel_acesso_post = trim($_POST['nivel_acesso'] ?? ''); // Nível de acesso do formulário
    $senha = $_POST['senha'] ?? ''; // Não fazer trim na senha antes de verificar se está vazia
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    $erros_validacao = [];

    // --- Validações ---
    if (empty($nome)) {
        $erros_validacao[] = "O campo Nome Completo é obrigatório.";
    }
    if (empty($username)) {
        $erros_validacao[] = "O campo Usuário (Login) é obrigatório.";
    }
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros_validacao[] = "O formato do Email é inválido.";
    }
    // Validação do nível de acesso (deve estar na lista permitida)
    $todos_niveis_acesso_validos = ['Agente de Terminal', 'Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
    if (empty($nivel_acesso_post) || !in_array($nivel_acesso_post, $todos_niveis_acesso_validos)) {
        // Se o campo estava desabilitado, o valor POST pode não vir. O hidden input no form deve enviar.
        // Se veio e é inválido, é um erro. Se não veio e deveria (admin editando outro), é erro.
        if ($admin_nivel_acesso_logado === 'Administrador' && !($is_editing_self && $usuario_id_param === 1) ) { // Admin pode mudar nível de outros e de si mesmo (exceto ID 1)
            if(empty($nivel_acesso_post) || !in_array($nivel_acesso_post, $todos_niveis_acesso_validos)){
                 $erros_validacao[] = "Nível de Acesso inválido selecionado.";
            }
        } elseif ($is_editing_self && !in_array($admin_nivel_acesso_logado, ['Administrador'])) {
            // Se Supervisor/Gerente está editando o próprio perfil, o nível de acesso não deveria ter sido enviado para alteração.
            // O valor será pego do banco ou da sessão para manter o nível atual.
            // Se o input hidden enviou um valor e ele é diferente do nível atual, pode ser uma tentativa de manipulação.
            // Vamos buscar o nível atual do banco para estes casos para garantir.
        }
    }


    // Validação de Senha
    if (!$usuario_id_param) { // Modo CADASTRO (senha obrigatória)
        if (empty($senha)) {
            $erros_validacao[] = "O campo Senha é obrigatório para novos usuários.";
        } elseif (strlen($senha) < 6) {
            $erros_validacao[] = "A senha deve ter no mínimo 6 caracteres.";
        } elseif ($senha !== $confirma_senha) {
            $erros_validacao[] = "A senha e a confirmação de senha não coincidem.";
        }
    } elseif (!empty($senha)) { // Modo EDIÇÃO e senha foi preenchida (quer alterar)
        if (strlen($senha) < 6) {
            $erros_validacao[] = "A nova senha deve ter no mínimo 6 caracteres.";
        } elseif ($senha !== $confirma_senha) {
            $erros_validacao[] = "A nova senha e a confirmação de senha não coincidem.";
        }
    } elseif (empty($senha) && !empty($confirma_senha) && $usuario_id_param) { // Senha vazia mas confirmação não, em edição
         $erros_validacao[] = "Se deseja alterar a senha, preencha o campo 'Senha' também.";
    }


    // Verificar duplicidade de Username (exceto para o próprio usuário em edição)
    if ($pdo) {
        $sql_check_username = "SELECT id FROM administradores WHERE username = :username";
        if ($usuario_id_param) {
            $sql_check_username .= " AND id != :id";
        }
        $stmt_check_username = $pdo->prepare($sql_check_username);
        $stmt_check_username->bindParam(':username', $username, PDO::PARAM_STR);
        if ($usuario_id_param) {
            $stmt_check_username->bindParam(':id', $usuario_id_param, PDO::PARAM_INT);
        }
        $stmt_check_username->execute();
        if ($stmt_check_username->fetch()) {
            $erros_validacao[] = "Este nome de Usuário (Login) já está em uso. Escolha outro.";
        }

        // Verificar duplicidade de Email (se preenchido e se for uma regra)
        if (!empty($email)) {
            $sql_check_email = "SELECT id FROM administradores WHERE email = :email";
            if ($usuario_id_param) {
                $sql_check_email .= " AND id != :id";
            }
            $stmt_check_email = $pdo->prepare($sql_check_email);
            $stmt_check_email->bindParam(':email', $email, PDO::PARAM_STR);
            if ($usuario_id_param) {
                $stmt_check_email->bindParam(':id', $usuario_id_param, PDO::PARAM_INT);
            }
            $stmt_check_email->execute();
            if ($stmt_check_email->fetch()) {
                $erros_validacao[] = "Este Email já está cadastrado. Utilize outro ou deixe em branco.";
            }
        }
    } else {
        $erros_validacao[] = "Erro de conexão com o banco de dados para validações.";
    }


    // Se houver erros de validação, redireciona de volta ao formulário
    if (!empty($erros_validacao)) {
        $_SESSION['admin_form_error_usuario'] = implode("<br>", $erros_validacao);
        $_SESSION['form_data_usuario'] = $_POST; // Guarda dados para repopular (exceto senha)
        $redirect_url = $usuario_id_param ? 'usuario_formulario.php?id=' . $usuario_id_param : 'usuario_formulario.php';
        header('Location: ' . $redirect_url . (isset($_GET['pagina']) ? '&pagina='.$_GET['pagina'] : ''));
        exit;
    }

    // --- Processar no Banco de Dados ---
    try {
        $pdo->beginTransaction();

        // Determinar o nível de acesso final
        $nivel_acesso_final = $nivel_acesso_post;
        if ($admin_nivel_acesso_logado !== 'Administrador' && $is_editing_self) {
            // Supervisor/Gerente editando próprio perfil, não pode mudar o nível
            // Busca o nível atual do banco para garantir
            $stmt_current_level = $pdo->prepare("SELECT nivel_acesso FROM administradores WHERE id = :id_self");
            $stmt_current_level->bindParam(':id_self', $_SESSION['admin_user_id'], PDO::PARAM_INT);
            $stmt_current_level->execute();
            $nivel_acesso_final = $stmt_current_level->fetchColumn();
        } elseif ($admin_nivel_acesso_logado === 'Administrador' && $is_editing_self && $usuario_id_param === 1 && $nivel_acesso_post !== 'Administrador') {
            // Impede que o Admin ID 1 se rebaixe (exemplo de regra)
             $_SESSION['admin_error_message'] = "O administrador principal (ID 1) não pode ter seu nível de acesso alterado de 'Administrador'.";
             $pdo->rollBack();
             header('Location: usuario_formulario.php?id=' . $usuario_id_param . (isset($_GET['pagina']) ? '&pagina='.$_GET['pagina'] : ''));
             exit;
        }


        if ($usuario_id_param) { // Modo EDIÇÃO
            $sql_update_parts = [
                "nome = :nome",
                // Username só pode ser alterado por Admin ou se não for edição do próprio perfil por não-admin
                ($admin_nivel_acesso_logado === 'Administrador' || !$is_editing_self) ? "username = :username" : "",
                "email = :email",
                "nivel_acesso = :nivel_acesso" // Sempre atualiza, mas $nivel_acesso_final tem a lógica de permissão
            ];
             // Remove partes vazias (ex: username se não puder ser alterado)
            $sql_update_parts = array_filter($sql_update_parts);


            $params_update = [
                ':nome' => $nome,
                ':email' => !empty($email) ? $email : null,
                ':nivel_acesso' => $nivel_acesso_final,
                ':id' => $usuario_id_param
            ];
            if ($admin_nivel_acesso_logado === 'Administrador' || !$is_editing_self) {
                $params_update[':username'] = $username;
            }


            // Se uma nova senha foi fornecida, adiciona à query e aos parâmetros
            if (!empty($senha)) {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                if ($senha_hash === false) throw new Exception("Erro ao gerar hash da senha.");
                $sql_update_parts[] = "senha = :senha";
                $params_update[':senha'] = $senha_hash;
            }

            if (empty($sql_update_parts)) { // Nenhuma alteração permitida ou feita
                 $_SESSION['admin_warning_message'] = "Nenhuma alteração foi aplicada ao usuário ID {$usuario_id_param}.";
                 $pdo->rollBack();
            } else {
                $sql = "UPDATE administradores SET " . implode(", ", $sql_update_parts) . " WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute($params_update)) {
                    $pdo->commit();
                    $_SESSION['admin_success_message'] = "Usuário (ID: {$usuario_id_param}) atualizado com sucesso!";
                } else {
                    $pdo->rollBack();
                    $_SESSION['admin_error_message'] = "Erro ao atualizar usuário no banco de dados.";
                }
            }

        } else { // Modo CADASTRO
            if (empty($senha)) { // Segurança extra, já validado antes
                 $_SESSION['admin_form_error_usuario'] = "Senha é obrigatória para novos usuários.";
                 $pdo->rollBack(); // Não há o que reverter, mas por consistência
                 header('Location: usuario_formulario.php'.(isset($_GET['pagina']) ? '?pagina='.$_GET['pagina'] : ''));
                 exit;
            }
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            if ($senha_hash === false) throw new Exception("Erro ao gerar hash da senha.");

            $sql = "INSERT INTO administradores (nome, username, email, nivel_acesso, senha, data_cadastro)
                    VALUES (:nome, :username, :email, :nivel_acesso, :senha, NOW())";
            $stmt = $pdo->prepare($sql);
            $params_insert = [
                ':nome' => $nome,
                ':username' => $username,
                ':email' => !empty($email) ? $email : null,
                ':nivel_acesso' => $nivel_acesso_final, // No cadastro, Admin define
                ':senha' => $senha_hash
            ];

            if ($stmt->execute($params_insert)) {
                $pdo->commit();
                $_SESSION['admin_success_message'] = "Novo usuário administrativo '{$nome}' cadastrado com sucesso!";
            } else {
                $pdo->rollBack();
                $_SESSION['admin_error_message'] = "Erro ao cadastrar novo usuário no banco de dados.";
            }
        }
    } catch (Exception $e) { // Captura exceções gerais (como do password_hash) e PDOExceptions
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao processar usuário admin: " . $e->getMessage());
        $_SESSION['admin_error_message'] = "Erro crítico ao processar o usuário: " . $e->getMessage();
    }

    $redirect_url_final = 'usuarios_listar.php' . (isset($_GET['pagina']) ? '?pagina='.$_GET['pagina'] : '');
    header('Location: ' . $redirect_url_final);
    exit;

} else {
    $_SESSION['admin_error_message'] = "Acesso inválido ao processamento de usuários.";
    header('Location: usuarios_listar.php');
    exit;
}
?>