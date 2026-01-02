<?php
// admin/escala_diaria_acao.php
// ATUALIZADO para copiar funcao_operacional_id ao importar da Planejada para a Diária.

require_once 'auth_check.php';
require_once '../db_config.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Ação inválida ou dados ausentes.'];

// Permissão para esta ação específica
$niveis_permitidos_importar = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_importar)) {
    $response['message'] = 'Você não tem permissão para executar esta ação.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['acao']) && $_POST['acao'] == 'importar_dia_da_planejada') {
    $data_escala_importar = trim($_POST['data_escala'] ?? '');

    if (empty($data_escala_importar)) {
        $response['message'] = 'Data para importação não fornecida.';
        echo json_encode($response);
        exit;
    }

    try {
        $data_obj = new DateTime($data_escala_importar);
        if ($data_obj->format('Y-m-d') !== $data_escala_importar) {
            throw new Exception();
        }
    } catch (Exception $e) {
        $response['message'] = 'Formato de data inválido para importação.';
        echo json_encode($response);
        exit;
    }

    if ($pdo) {
        try {
            $pdo->beginTransaction();

            // 1. Limpar a Escala Diária para a data especificada
            $stmt_delete_diaria = $pdo->prepare("DELETE FROM motorista_escalas_diaria WHERE data = :data_escala");
            $stmt_delete_diaria->bindParam(':data_escala', $data_escala_importar, PDO::PARAM_STR);
            $stmt_delete_diaria->execute();
            $registros_apagados = $stmt_delete_diaria->rowCount();

            // 2. Buscar todas as entradas da Escala Planejada para a data
            //    É crucial que 'funcao_operacional_id' esteja presente na tabela 'motorista_escalas'
            //    e seja selecionada aqui. Se 'SELECT *' já o inclui, ótimo.
            //    Para clareza, vamos listar as colunas explicitamente.
            $stmt_select_planejada = $pdo->prepare(
                "SELECT motorista_id, data, work_id, tabela_escalas, eh_extra, 
                        veiculo_id, linha_origem_id, funcao_operacional_id, /* <--- CAMPO CHAVE */
                        hora_inicio_prevista, local_inicio_turno_id, 
                        hora_fim_prevista, local_fim_turno_id
                 FROM motorista_escalas WHERE data = :data_escala"
            );
            $stmt_select_planejada->bindParam(':data_escala', $data_escala_importar, PDO::PARAM_STR);
            $stmt_select_planejada->execute();
            $escalas_planejadas_dia = $stmt_select_planejada->fetchAll(PDO::FETCH_ASSOC);

            $admin_id_logado_atual = $_SESSION['admin_user_id'];
            $observacao_padrao = "Importado da Escala Planejada em " . date('d/m/Y H:i:s');
            $entradas_importadas = 0;

            if ($escalas_planejadas_dia) {
                // ALTERADO: Incluir 'funcao_operacional_id' na query INSERT
                $sql_insert_diaria = "INSERT INTO motorista_escalas_diaria 
                                        (motorista_id, data, work_id, tabela_escalas, eh_extra, 
                                         veiculo_id, linha_origem_id, funcao_operacional_id, /* <--- INCLUÍDO */
                                         hora_inicio_prevista, local_inicio_turno_id, 
                                         hora_fim_prevista, local_fim_turno_id, 
                                         modificado_por_admin_id, observacoes_ajuste)
                                      VALUES 
                                        (:motorista_id, :data, :work_id, :tabela_escalas, :eh_extra, 
                                         :veiculo_id, :linha_origem_id, :funcao_operacional_id, /* <--- INCLUÍDO */
                                         :hora_inicio_prevista, :local_inicio_turno_id, 
                                         :hora_fim_prevista, :local_fim_turno_id,
                                         :modificado_por_admin_id, :observacoes_ajuste)";
                $stmt_insert = $pdo->prepare($sql_insert_diaria);

                foreach ($escalas_planejadas_dia as $escala_p) {
                    // Os binds já tratam os valores, incluindo NULLs se vierem da $escala_p
                    $stmt_insert->bindValue(':motorista_id', $escala_p['motorista_id'], PDO::PARAM_INT);
                    $stmt_insert->bindValue(':data', $escala_p['data'], PDO::PARAM_STR); // Usará a data da escala planejada (que é a mesma $data_escala_importar)
                    $stmt_insert->bindValue(':work_id', $escala_p['work_id'], PDO::PARAM_STR);
                    $stmt_insert->bindValue(':tabela_escalas', $escala_p['tabela_escalas'], $escala_p['tabela_escalas'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                    $stmt_insert->bindValue(':eh_extra', $escala_p['eh_extra'], PDO::PARAM_INT);
                    $stmt_insert->bindValue(':veiculo_id', $escala_p['veiculo_id'], $escala_p['veiculo_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                    $stmt_insert->bindValue(':linha_origem_id', $escala_p['linha_origem_id'], $escala_p['linha_origem_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                    
                    // ALTERADO: Bind do campo funcao_operacional_id
                    $stmt_insert->bindValue(':funcao_operacional_id', $escala_p['funcao_operacional_id'], $escala_p['funcao_operacional_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                    
                    $stmt_insert->bindValue(':hora_inicio_prevista', $escala_p['hora_inicio_prevista'], $escala_p['hora_inicio_prevista'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                    $stmt_insert->bindValue(':local_inicio_turno_id', $escala_p['local_inicio_turno_id'], $escala_p['local_inicio_turno_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                    $stmt_insert->bindValue(':hora_fim_prevista', $escala_p['hora_fim_prevista'], $escala_p['hora_fim_prevista'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                    $stmt_insert->bindValue(':local_fim_turno_id', $escala_p['local_fim_turno_id'], $escala_p['local_fim_turno_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                    $stmt_insert->bindValue(':modificado_por_admin_id', $admin_id_logado_atual, PDO::PARAM_INT);
                    $stmt_insert->bindValue(':observacoes_ajuste', $observacao_padrao, PDO::PARAM_STR);
                    
                    if ($stmt_insert->execute()) {
                        $entradas_importadas++;
                    } else {
                        error_log("Falha ao importar entrada da planejada para diária. Motorista ID: {$escala_p['motorista_id']}, WorkID: {$escala_p['work_id']}. Erro: " . implode(";", $stmt_insert->errorInfo()));
                    }
                }
            }

            $pdo->commit();
            $response['success'] = true;
            if ($entradas_importadas > 0) {
                $response['message'] = "{$entradas_importadas} entrada(s) importada(s) da Escala Planejada para a Diária do dia " . date('d/m/Y', strtotime($data_escala_importar)) . " com sucesso!";
                if($registros_apagados > 0) {
                    $response['message'] .= " ({$registros_apagados} entrada(s) diária(s) anterior(es) foram substituídas).";
                }
            } else {
                $response['message'] = "Nenhuma entrada encontrada na Escala Planejada para o dia " . date('d/m/Y', strtotime($data_escala_importar)) . " para importar.";
                 if($registros_apagados > 0) {
                    $response['message'] .= " ({$registros_apagados} entrada(s) diária(s) anterior(es) foram removidas).";
                }
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erro PDO ao importar escala planejada para diária: " . $e->getMessage());
            $response['message'] = 'Erro de banco de dados durante a importação.';
        }
    } else {
        $response['message'] = 'Falha na conexão com o banco de dados.';
    }
}

echo json_encode($response);
exit;
?>