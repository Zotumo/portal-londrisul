<?php
// admin/mensagem_apagar.php

require_once 'auth_check.php'; // Autenticação e define $admin_nivel_acesso_logado
require_once '../db_config.php'; // Conexão com o banco

// ##################################################################################
// ## PASSO 1: DEFINA AQUI OS NÍVEIS DE ACESSO QUE PODEM APAGAR MENSAGENS         ##
// ##################################################################################
$niveis_permitidos_apagar_mensagens = ['Supervisores', 'Gerência', 'Administrador']; // CONFIRME ESTA LISTA!

// Verifica se o nível do usuário logado está na lista de permissões
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_apagar_mensagens)) {
    $_SESSION['admin_error_message'] = "Seu nível de acesso ({$admin_nivel_acesso_logado}) não permite apagar mensagens.";
    header('Location: mensagens_listar.php'); // Redireciona de volta para a lista
    exit;
}

$mensagem_id_para_apagar = null;
// Valida se o ID da mensagem foi fornecido e é um inteiro
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $mensagem_id_para_apagar = (int)$_GET['id'];
} else {
    $_SESSION['admin_error_message'] = "ID da mensagem inválido para exclusão.";
    header('Location: mensagens_listar.php');
    exit;
}

// Constrói os parâmetros de redirecionamento para voltar à página/filtro correto
$redirect_params = '';
if (isset($_GET['pagina'])) {
    $redirect_params .= '&pagina=' . urlencode($_GET['pagina']);
}
if (isset($_GET['busca_motorista'])) {
    $redirect_params .= '&busca_motorista=' . urlencode($_GET['busca_motorista']);
}
// Remove o primeiro '&' se houver parâmetros, e adiciona '?'
$redirect_location = 'mensagens_listar.php' . ($redirect_params ? '?' . ltrim($redirect_params, '&') : '');


if ($pdo && $mensagem_id_para_apagar) {
    try {
        // Não há arquivos associados a mensagens (como imagens em notícias),
        // então podemos ir direto para a exclusão do banco.
        $stmt_delete = $pdo->prepare("DELETE FROM mensagens_motorista WHERE id = :id_msg");
        $stmt_delete->bindParam(':id_msg', $mensagem_id_para_apagar, PDO::PARAM_INT);

        if ($stmt_delete->execute()) {
            if ($stmt_delete->rowCount() > 0) {
                $_SESSION['admin_success_message'] = "Mensagem ID {$mensagem_id_para_apagar} apagada com sucesso.";
            } else {
                // O ID era válido, mas não foi encontrado no banco (talvez já apagado)
                $_SESSION['admin_warning_message'] = "Mensagem ID {$mensagem_id_para_apagar} não encontrada para exclusão ou já havia sido apagada.";
            }
        } else {
            $_SESSION['admin_error_message'] = "Erro ao tentar apagar a mensagem ID {$mensagem_id_para_apagar} do banco de dados.";
        }
    } catch (PDOException $e) {
        error_log("Erro PDO ao apagar mensagem ID {$mensagem_id_para_apagar}: " . $e->getMessage());
        $_SESSION['admin_error_message'] = "Erro de banco de dados ao tentar apagar a mensagem. Consulte o log.";
    }
} else {
    $_SESSION['admin_error_message'] = "Não foi possível apagar a mensagem. Dados inválidos ou falha na conexão com o banco.";
}

// Redireciona de volta para a lista de mensagens, mantendo os filtros e paginação
header("Location: " . $redirect_location);
exit;
?>