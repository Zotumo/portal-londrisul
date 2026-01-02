<?php
// admin/buscar_escala_para_copia_ajax.php
require_once 'auth_check.php'; // Segurança
require_once '../db_config.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Parâmetros iniciais inválidos ou ausentes.']; // Mensagem inicial mais específica

$motorista_id_origem = isset($_GET['motorista_id']) ? filter_var($_GET['motorista_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null;
$data_escala_origem = isset($_GET['data_escala']) ? trim($_GET['data_escala']) : null;

// Validar formato da data YYYY-MM-DD
if ($data_escala_origem) {
    $d_obj_ajax = DateTime::createFromFormat('Y-m-d', $data_escala_origem);
    if (!$d_obj_ajax || $d_obj_ajax->format('Y-m-d') !== $data_escala_origem) {
        $data_escala_origem = null; // Invalida a data se o formato não for correto
        $response['message'] = 'Formato de data inválido para cópia.';
    }
}

if ($motorista_id_origem && $data_escala_origem && $pdo) {
    try {
        // Listar explicitamente as colunas para evitar problemas com SELECT * e garantir que temos tudo que o JS espera
        $sql = "SELECT 
                    id, motorista_id, data, work_id, tabela_escalas, eh_extra, 
                    veiculo_id, linha_origem_id, hora_inicio_prevista, local_inicio_turno_id, 
                    hora_fim_prevista, local_fim_turno_id, 
                    DATE_FORMAT(data, '%d/%m/%Y') as data_formatada_origem 
                FROM motorista_escalas 
                WHERE motorista_id = :motorista_id AND data = :data_escala 
                ORDER BY hora_inicio_prevista ASC  /* Pega a primeira do dia se houver múltiplas */
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':motorista_id', $motorista_id_origem, PDO::PARAM_INT);
        $stmt->bindParam(':data_escala', $data_escala_origem, PDO::PARAM_STR);
        $stmt->execute();
        $escala_encontrada = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($escala_encontrada) {
            // Formata horas para HH:MM se existirem, para o input type="time"
            if (isset($escala_encontrada['hora_inicio_prevista']) && $escala_encontrada['hora_inicio_prevista']) {
                 $escala_encontrada['hora_inicio_prevista'] = substr($escala_encontrada['hora_inicio_prevista'], 0, 5);
            } else {
                 $escala_encontrada['hora_inicio_prevista'] = null; // Garante null se for string vazia ou null
            }
            if (isset($escala_encontrada['hora_fim_prevista']) && $escala_encontrada['hora_fim_prevista']) {
                 $escala_encontrada['hora_fim_prevista'] = substr($escala_encontrada['hora_fim_prevista'], 0, 5);
            } else {
                 $escala_encontrada['hora_fim_prevista'] = null;
            }
            // Garante que campos numéricos que podem ser NULL sejam realmente NULL se vazios no DB
            foreach (['linha_origem_id', 'local_inicio_turno_id', 'local_fim_turno_id', 'veiculo_id'] as $campo_nullable_id) {
                if (empty($escala_encontrada[$campo_nullable_id])) {
                    $escala_encontrada[$campo_nullable_id] = null;
                }
            }


            $response = ['success' => true, 'escala' => $escala_encontrada];
        } else {
            $response['message'] = 'Nenhuma escala encontrada para este motorista na data especificada.';
        }

    } catch (PDOException $e) {
        error_log("Erro AJAX buscar_escala_para_copia (PDO): " . $e->getMessage() . " | SQL: " . $sql . " | Params: motorista_id=" . $motorista_id_origem . ", data=" . $data_escala_origem);
        $response['message'] = 'Erro no servidor ao buscar dados da escala. (Ref: PDO)'; // Mensagem para o usuário
    }
} else {
    if (!$pdo) {
        $response['message'] = 'Erro de conexão com o banco de dados.';
        error_log("buscar_escala_para_copia_ajax: PDO object is null.");
    } elseif (!$motorista_id_origem) {
        $response['message'] = 'ID do motorista para cópia não fornecido ou inválido.';
    } elseif (!$data_escala_origem) {
        $response['message'] = 'Data da escala para cópia não fornecida ou inválida.';
    }
}

echo json_encode($response);
exit;
?>