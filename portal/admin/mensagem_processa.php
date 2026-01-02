<?php
// admin/mensagem_processa.php

require_once 'auth_check.php'; // Garante autenticação e define $admin_nivel_acesso_logado, $_SESSION['admin_user_name']
require_once '../db_config.php'; // Conexão com o banco de dados

// Verificar permissão para ENVIAR/PROCESSAR mensagens
$niveis_permitidos_processar_msg = ['Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_processar_msg)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para processar o envio de mensagens.";
    header('Location: mensagens_listar.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enviar_mensagem'])) {

    // Obter dados do formulário
    $enviar_para_todos = isset($_POST['enviar_para_todos_check']) && $_POST['enviar_para_todos_check'] == '1';
    $destinatario_id_individual = null;
    if (!$enviar_para_todos && isset($_POST['destinatario_id'])) {
        // Só considera destinatario_id se não for para todos E se ele foi enviado
        $destinatario_id_individual = filter_var($_POST['destinatario_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($destinatario_id_individual === false) $destinatario_id_individual = null; // Invalido ou vazio
    }

    $assunto = trim($_POST['assunto'] ?? '');
    $mensagem_corpo = trim($_POST['mensagem'] ?? '');
    $remetente = trim($_POST['remetente'] ?? ($_SESSION['admin_user_name'] . ' - ' . $admin_nivel_acesso_logado));


    // --- Validações ---
    if (empty($mensagem_corpo)) {
        $_SESSION['admin_form_error'] = "O corpo da mensagem é obrigatório.";
        $_SESSION['form_data'] = $_POST; // Salva dados para repopular
        header('Location: mensagem_formulario.php');
        exit;
    }

    if (!$enviar_para_todos && empty($destinatario_id_individual)) {
        $_SESSION['admin_form_error'] = "Se não marcou 'Enviar para TODOS', um destinatário individual deve ser selecionado.";
        $_SESSION['form_data'] = $_POST;
        header('Location: mensagem_formulario.php');
        exit;
    }

    $ids_destinatarios_finais = [];

    if ($enviar_para_todos) {
        if ($pdo) {
            try {
                // Considerar adicionar "WHERE status = 'ativo'" se houver essa coluna em 'motoristas'
                $stmt_todos = $pdo->query("SELECT id FROM motoristas");
                $ids_destinatarios_finais = $stmt_todos->fetchAll(PDO::FETCH_COLUMN);
                if (empty($ids_destinatarios_finais)) {
                    $_SESSION['admin_error_message'] = "Nenhum motorista encontrado no sistema para enviar a mensagem para todos.";
                    header('Location: mensagem_formulario.php'); // Volta para o form, não para a lista
                    exit;
                }
            } catch (PDOException $e) {
                error_log("Erro ao buscar todos os motoristas para mensagem: " . $e->getMessage());
                $_SESSION['admin_error_message'] = "Erro ao obter lista de todos os motoristas. Mensagem não enviada.";
                header('Location: mensagem_formulario.php');
                exit;
            }
        } else {
            $_SESSION['admin_error_message'] = "Falha na conexão com o banco. Mensagem não enviada.";
            header('Location: mensagem_formulario.php');
            exit;
        }
    } else {
        // Já validamos que $destinatario_id_individual não está vazio se não for para todos
        $ids_destinatarios_finais[] = $destinatario_id_individual;
    }

    // --- Inserir no Banco de Dados ---
    if ($pdo && !empty($ids_destinatarios_finais)) {
        $sucesso_geral = true;
        $mensagens_enviadas_count = 0;
        $falhas_envio_count = 0;
        $motoristas_falha = [];

        try {
            $pdo->beginTransaction();

            $sql_insert = "INSERT INTO mensagens_motorista (motorista_id, remetente, assunto, mensagem, data_envio)
                           VALUES (:motorista_id, :remetente, :assunto, :mensagem, NOW())";
            $stmt_insert = $pdo->prepare($sql_insert);

            foreach ($ids_destinatarios_finais as $motorista_id_alvo) {
                $params_insert = [
                    ':motorista_id' => $motorista_id_alvo,
                    ':remetente' => $remetente,
                    ':assunto' => !empty($assunto) ? $assunto : null, // Permite assunto vazio como NULL
                    ':mensagem' => $mensagem_corpo
                ];

                if ($stmt_insert->execute($params_insert)) {
                    $mensagens_enviadas_count++;
                } else {
                    $falhas_envio_count++;
                    $motoristas_falha[] = $motorista_id_alvo; // Guarda ID do motorista que falhou
                    error_log("Falha ao enviar mensagem para motorista ID: {$motorista_id_alvo}. Detalhes: " . implode(", ", $stmt_insert->errorInfo()));
                }
            }

            if ($falhas_envio_count > 0 && $mensagens_enviadas_count == 0) {
                // Nenhuma mensagem foi enviada com sucesso
                $pdo->rollBack();
                $_SESSION['admin_error_message'] = "Erro: Nenhuma mensagem pôde ser enviada. Verifique os logs.";
            } elseif ($falhas_envio_count > 0) {
                // Algumas falharam, mas outras podem ter tido sucesso
                $pdo->commit(); // Commita as que tiveram sucesso
                $_SESSION['admin_warning_message'] = "Mensagem enviada para {$mensagens_enviadas_count} motorista(s). Falha ao enviar para {$falhas_envio_count} motorista(s) (IDs: " . implode(", ", $motoristas_falha) . ").";
            } else {
                // Todas enviadas com sucesso
                $pdo->commit();
                if ($enviar_para_todos) {
                    $_SESSION['admin_success_message'] = "Mensagem enviada para {$mensagens_enviadas_count} motorista(s) com sucesso!";
                } else {
                    $_SESSION['admin_success_message'] = "Mensagem enviada com sucesso para o motorista selecionado!";
                }
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erro PDO CRÍTICO ao enviar mensagem(ns): " . $e->getMessage());
            $_SESSION['admin_error_message'] = "Erro CRÍTICO de banco de dados ao enviar mensagem(ns). Consulte o log do servidor. Nenhuma mensagem foi enviada.";
        }
    } elseif (empty($ids_destinatarios_finais)) {
        // Esta condição não deveria ser atingida se as validações anteriores funcionarem
        $_SESSION['admin_form_error'] = "Nenhum destinatário foi especificado para o envio.";
        $_SESSION['form_data'] = $_POST; // Para repopular o formulário
        header('Location: mensagem_formulario.php');
        exit;
    } else { // Caso $pdo seja null
         $_SESSION['admin_error_message'] = "Falha na conexão com o banco. Nenhuma mensagem foi enviada.";
         header('Location: mensagem_formulario.php');
         exit;
    }

    header('Location: mensagens_listar.php'); // Redireciona para a lista após o processamento
    exit;

} else {
    // Se não for POST ou o botão 'enviar_mensagem' não estiver presente
    $_SESSION['admin_error_message'] = "Acesso inválido à página de processamento de mensagens.";
    header('Location: mensagem_formulario.php');
    exit;
}
?>