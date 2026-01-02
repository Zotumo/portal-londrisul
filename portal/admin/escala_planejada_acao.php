<?php
// admin/escala_planejada_acao.php

require_once 'auth_check.php';
require_once '../db_config.php';

// ... (verificação de permissões, obtenção de parâmetros e redirect_params como antes) ...
$niveis_permitidos_excluir_escala = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_excluir_escala)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para excluir entradas da Escala Planejada.";
    header('Location: escala_planejada_listar.php');
    exit;
}

$acao = isset($_GET['acao']) ? trim($_GET['acao']) : null;
$escala_id_para_excluir = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$token_recebido = isset($_GET['token']) ? trim($_GET['token']) : null;

$redirect_params_list = [];
// ... (lógica para $redirect_params_list como antes) ...
$location_redirect_list = 'escala_planejada_listar.php' . (!empty($redirect_params_list) ? '?' . http_build_query($redirect_params_list) : '');


if ($acao === 'excluir' && $escala_id_para_excluir && $pdo) {
    // Validação CSRF Token (idealmente)

    try {
        $pdo->beginTransaction();

        // Busca informações da escala para feedback, AGORA INCLUINDO A MATRÍCULA
        $stmt_info = $pdo->prepare("SELECT mot.nome as nome_motorista, mot.matricula as matricula_motorista, esc.data 
                                    FROM motorista_escalas esc 
                                    LEFT JOIN motoristas mot ON esc.motorista_id = mot.id 
                                    WHERE esc.id = :id_escala_info");
        $stmt_info->bindParam(':id_escala_info', $escala_id_para_excluir, PDO::PARAM_INT);
        $stmt_info->execute();
        $info_escala_excluida = $stmt_info->fetch(PDO::FETCH_ASSOC);
        
        $feedback_nome_motorista = $info_escala_excluida ? htmlspecialchars($info_escala_excluida['nome_motorista']) : "Motorista ID " . $escala_id_para_excluir; // Ajuste no fallback
        $feedback_matricula_motorista = $info_escala_excluida && isset($info_escala_excluida['matricula_motorista']) ? htmlspecialchars($info_escala_excluida['matricula_motorista']) : "N/A";
        $feedback_data_escala = $info_escala_excluida && isset($info_escala_excluida['data']) ? date('d/m/Y', strtotime($info_escala_excluida['data'])) : "Data Desconhecida";


        $stmt_delete = $pdo->prepare("DELETE FROM motorista_escalas WHERE id = :id_escala_delete");
        $stmt_delete->bindParam(':id_escala_delete', $escala_id_para_excluir, PDO::PARAM_INT);

        if ($stmt_delete->execute()) {
            if ($stmt_delete->rowCount() > 0) {
                $pdo->commit();
                // <<< MENSAGEM DE SUCESSO ATUALIZADA >>>
                $_SESSION['admin_success_message'] = "Escala para '{$feedback_nome_motorista} - {$feedback_matricula_motorista}' em {$feedback_data_escala} excluída com sucesso.";
            } else {
                $pdo->rollBack();
                $_SESSION['admin_warning_message'] = "Nenhuma escala encontrada com a matrícula {$feedback_matricula_motorista} para exclusão ou já havia sido apagada.";
            }
        } else {
            $pdo->rollBack();
            $errorInfoDel = $stmt_delete->errorInfo();
            $_SESSION['admin_error_message'] = "Erro ao tentar excluir a entrada da escala ID {$escala_id_para_excluir}. Detalhes: SQLSTATE[{$errorInfoDel[0]}] {$errorInfoDel[1]}";
            error_log("Erro SQL ao excluir escala: SQLSTATE[{$errorInfoDel[0]}] [{$errorInfoDel[1]}] {$errorInfoDel[2]}");
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro PDO ao excluir escala planejada ID {$escala_id_para_excluir}: " . $e->getMessage());
        $_SESSION['admin_error_message'] = "Erro de banco de dados ao tentar excluir a entrada da escala. Consulte o log.";
    }
} else {
    // ... (lógica de erro como antes) ...
    if ($acao !== 'excluir') {
        $_SESSION['admin_error_message'] = "Ação inválida especificada.";
    } elseif (!$escala_id_para_excluir) {
        $_SESSION['admin_error_message'] = "ID da escala para exclusão não fornecido ou inválido.";
    } elseif (!$pdo) {
        $_SESSION['admin_error_message'] = "Falha na conexão com o banco de dados.";
    }
}

header("Location: " . $location_redirect_list);
exit;
?>