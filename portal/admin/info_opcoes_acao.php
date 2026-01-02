<?php
// admin/info_opcoes_acao.php
// Lida com a ativação e desativação das Opções de Informação.

require_once 'auth_check.php';
require_once '../db_config.php';

// --- Definição de Permissões ---
if (!isset($niveis_acesso_gerenciar_infos_padrao)) {
    $niveis_acesso_gerenciar_infos_padrao = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
}
if (!in_array($admin_nivel_acesso_logado, $niveis_acesso_gerenciar_infos_padrao)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para alterar o status das opções de informação.";
    header('Location: info_opcoes_listar.php');
    exit;
}

// --- Validação dos Parâmetros ---
$info_opcao_id_acao = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$acao_info = isset($_GET['acao']) ? trim($_GET['acao']) : null;
$token_recebido_info = isset($_GET['token']) ? trim($_GET['token']) : ''; // Para CSRF

// Validação CSRF básica (implementar uma mais robusta em produção)
$csrf_valido = false;
if (!empty($token_recebido_info) && isset($_SESSION['csrf_token_info_opcoes']) && hash_equals($_SESSION['csrf_token_info_opcoes'], $token_recebido_info)) {
    $csrf_valido = true;
    unset($_SESSION['csrf_token_info_opcoes']); // Usar o token apenas uma vez
}

if (!$info_opcao_id_acao || !in_array($acao_info, ['ativar', 'desativar']) /* || !$csrf_valido */ ) { // Descomente a validação do CSRF quando implementado
    $_SESSION['admin_error_message'] = "Ação inválida, parâmetros ausentes ou falha na verificação de segurança para alterar status da Opção de Informação.";
    if (!$csrf_valido && !empty($token_recebido_info)) { // Se o token falhou
        $_SESSION['admin_error_message'] = "Falha na verificação de segurança (token inválido). Ação cancelada.";
    }
    header('Location: info_opcoes_listar.php');
    exit;
}

// --- Preparar para Redirecionamento ---
$redirect_query_string_info_acao = '';
$redirect_params_info_acao = [];
if (isset($_GET['pagina'])) $redirect_params_info_acao['pagina'] = $_GET['pagina'];
if (isset($_GET['busca_descricao'])) $redirect_params_info_acao['busca_descricao'] = $_GET['busca_descricao'];
if (isset($_GET['busca_linha_id'])) $redirect_params_info_acao['busca_linha_id'] = $_GET['busca_linha_id'];
if (isset($_GET['status_filtro'])) $redirect_params_info_acao['status_filtro'] = $_GET['status_filtro'];

if (!empty($redirect_params_info_acao)) {
    $redirect_query_string_info_acao = '?' . http_build_query($redirect_params_info_acao);
}
$location_redirect_info_acao = 'info_opcoes_listar.php' . $redirect_query_string_info_acao;


// --- Executar Ação ---
if ($pdo) {
    $novo_status_info_db = ($acao_info === 'ativar' ? 'ativo' : 'inativo');
    $descricao_feedback_info = "ID " . $info_opcao_id_acao; 

    try {
        $stmt_nome_info = $pdo->prepare("SELECT descricao_info FROM info_opcoes WHERE id = :id_info_nome");
        $stmt_nome_info->bindParam(':id_info_nome', $info_opcao_id_acao, PDO::PARAM_INT);
        $stmt_nome_info->execute();
        $desc_fetch = $stmt_nome_info->fetchColumn();
        if ($desc_fetch) {
            $descricao_feedback_info = htmlspecialchars($desc_fetch);
        }

        $sql_update_status_info = "UPDATE info_opcoes SET status_info = :novo_status WHERE id = :id_info_upd";
        $stmt_update_info = $pdo->prepare($sql_update_status_info);
        // GARANTIR QUE ESTAMOS PASSANDO OS VALORES CORRETOS PARA O ENUM: 'ativo' ou 'inativa'
        $stmt_update_info->bindParam(':novo_status', $novo_status_info_db, PDO::PARAM_STR);
        $stmt_update_info->bindParam(':id_info_upd', $info_opcao_id_acao, PDO::PARAM_INT);

        if ($stmt_update_info->execute()) {
            if ($stmt_update_info->rowCount() > 0) {
                $_SESSION['admin_success_message'] = "Status da Opção de Informação '{$descricao_feedback_info}' alterado para '" . ucfirst($novo_status_info_db) . "' com sucesso.";
            } else {
                // Nenhuma linha afetada - pode ser que o status já era o desejado ou ID não encontrado
                // Para ter certeza, buscamos o status atual no banco
                $stmt_check_current_status = $pdo->prepare("SELECT status_info FROM info_opcoes WHERE id = :id_check_status");
                $stmt_check_current_status->bindParam(':id_check_status', $info_opcao_id_acao, PDO::PARAM_INT);
                $stmt_check_current_status->execute();
                $current_status_in_db = $stmt_check_current_status->fetchColumn();

                if ($current_status_in_db === $novo_status_info_db) {
                     $_SESSION['admin_warning_message'] = "Status da Opção de Informação '{$descricao_feedback_info}' já era '" . ucfirst($novo_status_info_db) . "'. Nenhuma alteração necessária.";
                } elseif ($current_status_in_db === false) { // Não encontrou o ID
                     $_SESSION['admin_error_message'] = "Opção de Informação '{$descricao_feedback_info}' (ID: {$info_opcao_id_acao}) não encontrada para alteração de status.";
                } else { // Outro motivo para rowCount ser 0
                     $_SESSION['admin_warning_message'] = "Nenhuma alteração de status realizada para a Opção de Informação '{$descricao_feedback_info}'.";
                }
            }
        } else {
            $errorInfoUpdate = $stmt_update_info->errorInfo();
            $_SESSION['admin_error_message'] = "Erro ao tentar alterar o status da Opção de Informação '{$descricao_feedback_info}'. Detalhes: SQLSTATE[{$errorInfoUpdate[0]}] {$errorInfoUpdate[1]} {$errorInfoUpdate[2]}";
            error_log("Erro SQL ao alterar status info_opcoes: SQLSTATE[{$errorInfoUpdate[0]}] [{$errorInfoUpdate[1]}] {$errorInfoUpdate[2]} para ID: {$info_opcao_id_acao}");
        }
    } catch (PDOException $e) {
        error_log("Erro PDO ao alterar status da info_opcoes ID {$info_opcao_id_acao}: " . $e->getMessage());
        $_SESSION['admin_error_message'] = "Erro de banco de dados ao tentar alterar o status da Opção de Informação '{$descricao_feedback_info}'. Consulte o log do servidor.";
    }
} else {
    $_SESSION['admin_error_message'] = "Falha na conexão com o banco de dados. Status da Opção de Informação não alterado.";
}

header("Location: " . $location_redirect_info_acao);
exit;
?>