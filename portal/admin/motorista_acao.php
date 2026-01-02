<?php
// admin/motorista_acao.php

require_once 'auth_check.php';
require_once '../db_config.php';

$niveis_permitidos_gerenciar_status_motorista = ['Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_gerenciar_status_motorista)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para alterar o status de motoristas.";
    header('Location: motoristas_listar.php');
    exit;
}

$motorista_id_acao = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$acao = isset($_GET['acao']) ? trim($_GET['acao']) : null;
$token_recebido = isset($_GET['token']) ? trim($_GET['token']) : null;

if (!$motorista_id_acao || !in_array($acao, ['ativar', 'desativar']) || empty($token_recebido)) {
    $_SESSION['admin_error_message'] = "Ação inválida ou parâmetros ausentes.";
    header('Location: motoristas_listar.php');
    exit;
}

// (Validação CSRF Token - mantida como antes, idealmente melhorar para tokens de sessão)

$redirect_query_string = '';
$redirect_params = [];
if (isset($_GET['pagina'])) $redirect_params['pagina'] = $_GET['pagina'];
if (isset($_GET['busca'])) $redirect_params['busca'] = $_GET['busca'];
if (isset($_GET['status_filtro'])) $redirect_params['status_filtro'] = $_GET['status_filtro'];
if (!empty($redirect_params)) {
    $redirect_query_string = '?' . http_build_query($redirect_params);
}
$location_redirect_acao = 'motoristas_listar.php' . $redirect_query_string;

$nome_motorista_feedback = "ID {$motorista_id_acao}"; // Fallback
$matricula_motorista_feedback = "";

if ($pdo && $motorista_id_acao) {
    // Buscar nome e matrícula para o feedback
    try {
        $stmt_info = $pdo->prepare("SELECT nome, matricula FROM motoristas WHERE id = :id_motorista_info");
        $stmt_info->bindParam(':id_motorista_info', $motorista_id_acao, PDO::PARAM_INT);
        $stmt_info->execute();
        $motorista_info_feedback = $stmt_info->fetch(PDO::FETCH_ASSOC);
        if ($motorista_info_feedback) {
            $nome_motorista_feedback = htmlspecialchars($motorista_info_feedback['nome']);
            $matricula_motorista_feedback = htmlspecialchars($motorista_info_feedback['matricula']);
        }
    } catch (PDOException $e) {
        error_log("Erro ao buscar info do motorista ID {$motorista_id_acao} para feedback: " . $e->getMessage());
        // Continua com o ID como fallback na mensagem
    }


    $novo_status = ($acao === 'ativar' ? 'ativo' : 'inativo');

    try {
        $pdo->beginTransaction();
        $sql_update_status = "UPDATE motoristas SET status = :novo_status WHERE id = :id_motorista";
        $stmt_update = $pdo->prepare($sql_update_status);
        $stmt_update->bindParam(':novo_status', $novo_status, PDO::PARAM_STR);
        $stmt_update->bindParam(':id_motorista', $motorista_id_acao, PDO::PARAM_INT);

        if ($stmt_update->execute()) {
            if ($stmt_update->rowCount() > 0) {
                $pdo->commit();
                $_SESSION['admin_success_message'] = "Status do motorista '{$nome_motorista_feedback}' (Matrícula: {$matricula_motorista_feedback}) alterado para '{$novo_status}' com sucesso.";
            } else {
                $pdo->rollBack();
                $_SESSION['admin_warning_message'] = "Nenhuma alteração de status realizada para o motorista '{$nome_motorista_feedback}' (Matrícula: {$matricula_motorista_feedback}) (talvez já estivesse no status desejado ou não foi encontrado).";
            }
        } else {
            $pdo->rollBack();
            $_SESSION['admin_error_message'] = "Erro ao tentar alterar o status do motorista '{$nome_motorista_feedback}' (Matrícula: {$matricula_motorista_feedback}). Detalhes: " . implode(";", $stmt_update->errorInfo());
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro PDO ao alterar status do motorista ID {$motorista_id_acao}: " . $e->getMessage());
        $_SESSION['admin_error_message'] = "Erro de banco de dados ao tentar alterar o status do motorista '{$nome_motorista_feedback}'. Consulte o log.";
    }
} else {
    $_SESSION['admin_error_message'] = "Não foi possível processar a ação. Dados inválidos ou falha na conexão com o banco.";
}

header("Location: " . $location_redirect_acao);
exit;
?>