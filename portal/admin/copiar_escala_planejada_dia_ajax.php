<?php
// admin/copiar_escala_planejada_dia_ajax.php
// Realiza a cópia de todas as entradas da Escala Planejada de um dia para outro.
// ATUALIZADO para incluir funcao_operacional_id na cópia.

require_once 'auth_check.php';
require_once '../db_config.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Ação inválida ou dados ausentes.'];

// Permissão para esta ação específica (ajuste conforme sua necessidade)
$niveis_permitidos_copiar_dia = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_copiar_dia)) {
    $response['message'] = 'Você não tem permissão para executar esta ação de cópia.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data_origem_str = trim($_POST['data_origem'] ?? '');
    $data_destino_str = trim($_POST['data_destino'] ?? '');
    $confirmar_substituicao = isset($_POST['confirmar_substituicao']) && $_POST['confirmar_substituicao'] == '1';

    // Validação de datas
    $data_origem_obj = null;
    $data_destino_obj = null;

    if (empty($data_origem_str) || empty($data_destino_str)) {
        $response['message'] = 'Data de Origem e Data de Destino são obrigatórias.';
        echo json_encode($response);
        exit;
    }

    try {
        $data_origem_obj = new DateTime($data_origem_str);
        if ($data_origem_obj->format('Y-m-d') !== $data_origem_str) throw new Exception();
    } catch (Exception $e) {
        $response['message'] = 'Formato da Data de Origem inválido.';
        echo json_encode($response);
        exit;
    }

    try {
        $data_destino_obj = new DateTime($data_destino_str);
        if ($data_destino_obj->format('Y-m-d') !== $data_destino_str) throw new Exception();
    } catch (Exception $e) {
        $response['message'] = 'Formato da Data de Destino inválido.';
        echo json_encode($response);
        exit;
    }

    if ($data_origem_str === $data_destino_str) {
        $response['message'] = 'A Data de Origem e a Data de Destino não podem ser iguais.';
        echo json_encode($response);
        exit;
    }

    if (!$confirmar_substituicao) {
        $response['message'] = 'A confirmação para substituir a escala do dia de destino é necessária.';
        echo json_encode($response);
        exit;
    }

    if ($pdo) {
        try {
            $pdo->beginTransaction();

            // 1. Apagar todas as entradas da Escala Planejada para a DATA DE DESTINO
            $stmt_delete = $pdo->prepare("DELETE FROM motorista_escalas WHERE data = :data_destino");
            $stmt_delete->bindParam(':data_destino', $data_destino_str, PDO::PARAM_STR);
            $stmt_delete->execute();
            $registros_apagados_destino = $stmt_delete->rowCount();

            // 2. Selecionar todas as entradas da Escala Planejada da DATA DE ORIGEM
            //    Certifique-se de selecionar TODAS as colunas necessárias, incluindo 'funcao_operacional_id'
            $sql_select_origem = "SELECT 
                                    motorista_id, work_id, tabela_escalas, eh_extra, 
                                    veiculo_id, linha_origem_id, funcao_operacional_id, /* <--- INCLUÍDO */
                                    hora_inicio_prevista, local_inicio_turno_id, 
                                    hora_fim_prevista, local_fim_turno_id
                                  FROM motorista_escalas 
                                  WHERE data = :data_origem";
            $stmt_select_origem = $pdo->prepare($sql_select_origem);
            $stmt_select_origem->bindParam(':data_origem', $data_origem_str, PDO::PARAM_STR);
            $stmt_select_origem->execute();
            $escalas_origem = $stmt_select_origem->fetchAll(PDO::FETCH_ASSOC);

            $entradas_copiadas_sucesso = 0;

            if ($escalas_origem) {
                $sql_insert_destino = "INSERT INTO motorista_escalas 
                                        (data, motorista_id, work_id, tabela_escalas, eh_extra, 
                                         veiculo_id, linha_origem_id, funcao_operacional_id, /* <--- INCLUÍDO */
                                         hora_inicio_prevista, local_inicio_turno_id, 
                                         hora_fim_prevista, local_fim_turno_id)
                                       VALUES 
                                        (:data_destino_insert, :motorista_id, :work_id, :tabela_escalas, :eh_extra, 
                                         :veiculo_id, :linha_origem_id, :funcao_operacional_id, /* <--- INCLUÍDO */
                                         :hora_inicio_prevista, :local_inicio_turno_id, 
                                         :hora_fim_prevista, :local_fim_turno_id)";
                $stmt_insert_destino = $pdo->prepare($sql_insert_destino);

                foreach ($escalas_origem as $escala_item) {
                    // Mapeia os dados para os binds, garantindo que NULL seja tratado corretamente
                    $params_insert = [
                        ':data_destino_insert'      => $data_destino_str,
                        ':motorista_id'             => $escala_item['motorista_id'],
                        ':work_id'                  => $escala_item['work_id'],
                        ':tabela_escalas'           => $escala_item['tabela_escalas'], // Se for null no select, será null aqui
                        ':eh_extra'                 => $escala_item['eh_extra'],
                        ':veiculo_id'               => $escala_item['veiculo_id'],
                        ':linha_origem_id'          => $escala_item['linha_origem_id'],
                        ':funcao_operacional_id'    => $escala_item['funcao_operacional_id'], // <--- CAMPO IMPORTANTE
                        ':hora_inicio_prevista'     => $escala_item['hora_inicio_prevista'],
                        ':local_inicio_turno_id'    => $escala_item['local_inicio_turno_id'],
                        ':hora_fim_prevista'        => $escala_item['hora_fim_prevista'],
                        ':local_fim_turno_id'       => $escala_item['local_fim_turno_id']
                    ];
                    
                    // Ajuste para bindParam com tipo explícito para NULLs (melhor prática)
                    $stmt_insert_destino->bindValue(':data_destino_insert', $data_destino_str, PDO::PARAM_STR);
                    $stmt_insert_destino->bindValue(':motorista_id', $escala_item['motorista_id'], PDO::PARAM_INT);
                    $stmt_insert_destino->bindValue(':work_id', $escala_item['work_id'], PDO::PARAM_STR);
                    $stmt_insert_destino->bindValue(':tabela_escalas', $escala_item['tabela_escalas'], $escala_item['tabela_escalas'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                    $stmt_insert_destino->bindValue(':eh_extra', $escala_item['eh_extra'], PDO::PARAM_INT);
                    $stmt_insert_destino->bindValue(':veiculo_id', $escala_item['veiculo_id'], $escala_item['veiculo_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                    $stmt_insert_destino->bindValue(':linha_origem_id', $escala_item['linha_origem_id'], $escala_item['linha_origem_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                    $stmt_insert_destino->bindValue(':funcao_operacional_id', $escala_item['funcao_operacional_id'], $escala_item['funcao_operacional_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                    $stmt_insert_destino->bindValue(':hora_inicio_prevista', $escala_item['hora_inicio_prevista'], $escala_item['hora_inicio_prevista'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                    $stmt_insert_destino->bindValue(':local_inicio_turno_id', $escala_item['local_inicio_turno_id'], $escala_item['local_inicio_turno_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                    $stmt_insert_destino->bindValue(':hora_fim_prevista', $escala_item['hora_fim_prevista'], $escala_item['hora_fim_prevista'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                    $stmt_insert_destino->bindValue(':local_fim_turno_id', $escala_item['local_fim_turno_id'], $escala_item['local_fim_turno_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);

                    if ($stmt_insert_destino->execute()) {
                        $entradas_copiadas_sucesso++;
                    } else {
                        // Logar erro se uma inserção específica falhar
                        error_log("Falha ao copiar entrada da escala planejada. Motorista ID: {$escala_item['motorista_id']}, WorkID: {$escala_item['work_id']} para data {$data_destino_str}. Erro: " . implode(";", $stmt_insert_destino->errorInfo()));
                    }
                }
            }

            $pdo->commit();
            $response['success'] = true;
            if ($entradas_copiadas_sucesso > 0) {
                $response['message'] = "{$entradas_copiadas_sucesso} entrada(s) da escala planejada do dia " . date('d/m/Y', strtotime($data_origem_str)) . " foram copiadas para o dia " . date('d/m/Y', strtotime($data_destino_str)) . " com sucesso!";
                if ($registros_apagados_destino > 0) {
                    $response['message'] .= " ({$registros_apagados_destino} entrada(s) anteriores do dia de destino foram substituídas).";
                }
            } else {
                 $response['message'] = "Nenhuma entrada encontrada na escala planejada do dia " . date('d/m/Y', strtotime($data_origem_str)) . " para copiar.";
                 if ($registros_apagados_destino > 0) {
                    $response['message'] .= " ({$registros_apagados_destino} entrada(s) anteriores do dia de destino foram removidas).";
                }
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erro PDO ao copiar escala planejada de dia inteiro: " . $e->getMessage());
            $response['message'] = 'Erro de banco de dados durante a cópia da escala. Consulte o log do servidor.';
        }
    } else {
        $response['message'] = 'Falha na conexão com o banco de dados.';
    }
} else {
    // Se não for POST ou ação não for 'copiar_dia_inteiro'
    $response['message'] = 'Requisição inválida.';
}

echo json_encode($response);
exit;
?>