<?php
// admin/usuario_apagar.php

require_once 'auth_check.php'; // Define $admin_nivel_acesso_logado, $_SESSION['admin_user_id']
require_once '../db_config.php'; // Conexão com o banco

// Somente 'Administrador' pode apagar usuários
if ($admin_nivel_acesso_logado !== 'Administrador') {
    $_SESSION['admin_error_message'] = "Você não tem permissão para apagar usuários administrativos.";
    header('Location: usuarios_listar.php');
    exit;
}

$usuario_id_para_apagar = null;
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $usuario_id_para_apagar = (int)$_GET['id'];
} else {
    $_SESSION['admin_error_message'] = "ID do usuário inválido para exclusão.";
    header('Location: usuarios_listar.php' . (isset($_GET['pagina']) ? '?pagina=' . $_GET['pagina'] : ''));
    exit;
}

// Constrói os parâmetros de redirecionamento para voltar à página/filtro correto
$redirect_params_apagar = '';
if (isset($_GET['pagina'])) {
    $redirect_params_apagar .= '&pagina=' . urlencode($_GET['pagina']);
}
// Adicione outros filtros se houver na usuarios_listar.php (ex: &busca_usuario=...)
$location_redirect_apagar = 'usuarios_listar.php' . ($redirect_params_apagar ? '?' . ltrim($redirect_params_apagar, '&') : '');


// Autoproteção: Administrador não pode apagar a si mesmo
if ($usuario_id_para_apagar == $_SESSION['admin_user_id']) {
    $_SESSION['admin_error_message'] = "Você não pode apagar seu próprio usuário.";
    header('Location: ' . $location_redirect_apagar);
    exit;
}

// Proteção para o "super administrador" (ex: ID 1) - opcional, mas recomendado
if ($usuario_id_para_apagar === 1) { // Assumindo que ID 1 é o admin principal
    $_SESSION['admin_error_message'] = "O administrador principal (ID 1) não pode ser apagado.";
    header('Location: ' . $location_redirect_apagar);
    exit;
}

// Garantir que sempre haja pelo menos um administrador (opcional, mas importante)
if ($pdo) {
    try {
        $stmt_count_admins = $pdo->query("SELECT COUNT(*) FROM administradores WHERE nivel_acesso = 'Administrador'");
        $count_admins = (int)$stmt_count_admins->fetchColumn();

        if ($count_admins <= 1) {
            // Verifica se o usuário a ser apagado é o último administrador
            $stmt_check_level = $pdo->prepare("SELECT nivel_acesso FROM administradores WHERE id = :id_user_check");
            $stmt_check_level->bindParam(':id_user_check', $usuario_id_para_apagar, PDO::PARAM_INT);
            $stmt_check_level->execute();
            $level_to_delete = $stmt_check_level->fetchColumn();

            if ($level_to_delete === 'Administrador') {
                $_SESSION['admin_error_message'] = "Não é possível apagar o último usuário com nível 'Administrador'. Deve haver pelo menos um administrador no sistema.";
                header('Location: ' . $location_redirect_apagar);
                exit;
            }
        }
    } catch (PDOException $e) {
        error_log("Erro ao verificar contagem de administradores antes de apagar: " . $e->getMessage());
        // Decide se bloqueia a exclusão ou continua com cautela
        $_SESSION['admin_error_message'] = "Erro ao verificar as condições de exclusão. Ação cancelada por segurança.";
        header('Location: ' . $location_redirect_apagar);
        exit;
    }
}


if ($pdo && $usuario_id_para_apagar) {
    try {
        $pdo->beginTransaction();

        // Antes de apagar, pode ser útil verificar se este usuário está ligado a outras coisas críticas,
        // mas para a tabela 'administradores' geralmente a exclusão direta é ok se as regras acima forem cumpridas.

        $stmt_delete_user = $pdo->prepare("DELETE FROM administradores WHERE id = :id_param_delete");
        $stmt_delete_user->bindParam(':id_param_delete', $usuario_id_para_apagar, PDO::PARAM_INT);

        if ($stmt_delete_user->execute()) {
            if ($stmt_delete_user->rowCount() > 0) {
                $pdo->commit();
                $_SESSION['admin_success_message'] = "Usuário administrativo ID {$usuario_id_para_apagar} apagado com sucesso.";
            } else {
                $pdo->rollBack(); // Não encontrou o usuário para apagar
                $_SESSION['admin_warning_message'] = "Usuário ID {$usuario_id_para_apagar} não encontrado ou já havia sido apagado.";
            }
        } else {
            $pdo->rollBack();
            $_SESSION['admin_error_message'] = "Erro ao tentar apagar o usuário ID {$usuario_id_para_apagar} do banco de dados.";
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro PDO ao apagar usuário admin ID {$usuario_id_para_apagar}: " . $e->getMessage());
        $_SESSION['admin_error_message'] = "Erro de banco de dados ao tentar apagar o usuário. Consulte o log.";
    }
} else {
    // Esta condição é improvável se as validações anteriores funcionarem
    $_SESSION['admin_error_message'] = "Não foi possível apagar o usuário. Dados inválidos ou falha na conexão com o banco.";
}

header("Location: " . $location_redirect_apagar);
exit;
?>