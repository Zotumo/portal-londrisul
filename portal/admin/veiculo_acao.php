<?php
// admin/veiculo_acao.php

require_once 'auth_check.php'; // Autenticação e permissões básicas

// --- Definição de Permissões para ESTA AÇÃO ---
// Mesmos níveis que podem mudar status na listagem
$niveis_permitidos_mudar_status_veiculo = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_mudar_status_veiculo)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para alterar o status de veículos.";
    header('Location: veiculos_listar.php');
    exit;
}

require_once '../db_config.php';

// --- Validação dos Parâmetros GET ---
$veiculo_id_acao = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$acao_veiculo = isset($_GET['acao']) ? trim($_GET['acao']) : null;
// Token CSRF para segurança (idealmente gerado e validado de forma mais robusta com sessões)
$token_recebido_v_acao = isset($_GET['token']) ? trim($_GET['token']) : null;

if (!$veiculo_id_acao || !in_array($acao_veiculo, ['ativar', 'desativar']) || empty($token_recebido_v_acao)) {
    $_SESSION['admin_error_message'] = "Ação inválida ou parâmetros ausentes para alterar status do veículo.";
    header('Location: veiculos_listar.php');
    exit;
}

// Validação de token CSRF (simplificada para este exemplo)
// Em um cenário real, o token seria gerado no formulário/link e validado aqui com o da sessão.
// Para este exemplo, vamos assumir que o token passado (mesmo que seja uniqid) é para evitar replay simples.
// No futuro, você pode querer integrar um sistema de token CSRF mais robusto.

// --- Preparar para Redirecionamento (preservando filtros da lista) ---
$redirect_query_params_v_acao = [];
if (isset($_GET['pagina'])) $redirect_query_params_v_acao['pagina'] = $_GET['pagina'];
if (isset($_GET['busca_prefixo'])) $redirect_query_params_v_acao['busca_prefixo'] = $_GET['busca_prefixo'];
if (isset($_GET['busca_tipo'])) $redirect_query_params_v_acao['busca_tipo'] = $_GET['busca_tipo'];
if (isset($_GET['busca_status'])) $redirect_query_params_v_acao['busca_status'] = $_GET['busca_status'];

$location_redirect_veic_acao = 'veiculos_listar.php' . (!empty($redirect_query_params_v_acao) ? '?' . http_build_query($redirect_query_params_v_acao) : '');


// --- Executar Ação ---
if ($pdo && $veiculo_id_acao) {
    $novo_status_db_veiculo = ($acao_veiculo === 'ativar' ? 'operação' : 'fora de operação');
    $feedback_prefixo_veiculo = "ID " . $veiculo_id_acao; // Fallback

    try {
        // Opcional: Buscar prefixo para mensagem de feedback mais clara
        $stmt_prefixo_v = $pdo->prepare("SELECT prefixo FROM veiculos WHERE id = :id_veic_info");
        $stmt_prefixo_v->bindParam(':id_veic_info', $veiculo_id_acao, PDO::PARAM_INT);
        $stmt_prefixo_v->execute();
        $prefixo_fetch_v = $stmt_prefixo_v->fetchColumn();
        if ($prefixo_fetch_v) {
            $feedback_prefixo_veiculo = "prefixo '" . htmlspecialchars($prefixo_fetch_v) . "'";
        }

        $pdo->beginTransaction();
        $sql_update_status_v = "UPDATE veiculos SET status = :novo_status WHERE id = :id_veiculo_upd";
        $stmt_update_v = $pdo->prepare($sql_update_status_v);
        $stmt_update_v->bindParam(':novo_status', $novo_status_db_veiculo, PDO::PARAM_STR); // ENUM é string
        $stmt_update_v->bindParam(':id_veiculo_upd', $veiculo_id_acao, PDO::PARAM_INT);

        if ($stmt_update_v->execute()) {
            if ($stmt_update_v->rowCount() > 0) {
                $pdo->commit();
                $_SESSION['admin_success_message'] = "Status do veículo com {$feedback_prefixo_veiculo} alterado para '" . ucfirst($novo_status_db_veiculo) . "' com sucesso.";
            } else {
                $pdo->rollBack();
                $_SESSION['admin_warning_message'] = "Nenhuma alteração de status realizada para o veículo com {$feedback_prefixo_veiculo} (talvez já estivesse no status desejado ou não foi encontrado).";
            }
        } else {
            $pdo->rollBack();
            $errorInfoUpdV = $stmt_update_v->errorInfo();
            $_SESSION['admin_error_message'] = "Erro ao tentar alterar o status do veículo com {$feedback_prefixo_veiculo}. Detalhes: SQLSTATE[{$errorInfoUpdV[0]}] {$errorInfoUpdV[1]}";
            error_log("Erro SQL ao alterar status do veículo: SQLSTATE[{$errorInfoUpdV[0]}] [{$errorInfoUpdV[1]}] {$errorInfoUpdV[2]} para ID: {$veiculo_id_acao}");
        }
    } catch (PDOException $e_v_status) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro PDO ao alterar status do veículo ID {$veiculo_id_acao}: " . $e_v_status->getMessage());
        $_SESSION['admin_error_message'] = "Erro de banco de dados ao tentar alterar o status do veículo com {$feedback_prefixo_veiculo}. Consulte o log.";
    }
} else {
    // Mensagem de erro se não houver conexão PDO ou ID do veículo inválido (já tratado no início, mas como fallback)
    if (!$pdo) {
         $_SESSION['admin_error_message'] = "Falha na conexão com o banco de dados. Status do veículo não alterado.";
    }
}

header("Location: " . $location_redirect_veic_acao);
exit;
?>