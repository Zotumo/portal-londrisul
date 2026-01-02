<?php
require_once 'auth_check.php';
require_once '../db_config.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Parâmetros inválidos.', 'workids' => []];

if (isset($_POST['linha_id'], $_POST['data_escala'])) {
    $linha_id = filter_var($_POST['linha_id'], FILTER_VALIDATE_INT);
    $data_escala_str = trim($_POST['data_escala']);

    if (!$linha_id || empty($data_escala_str)) {
        echo json_encode($response);
        exit;
    }

    // Determinar tipo de dia da data_escala
    $dia_semana_num = date('N', strtotime($data_escala_str));
    $tipo_dia_filtro = '';
    // Simplificação: Assume que não há tabela de feriados por enquanto.
    // Você precisaria adicionar lógica de feriados aqui se necessário.
    if ($dia_semana_num >= 1 && $dia_semana_num <= 5) { // Seg a Sex
        $tipo_dia_filtro = 'Uteis';
    } elseif ($dia_semana_num == 6) { // Sábado
        $tipo_dia_filtro = 'Sabado';
    } elseif ($dia_semana_num == 7) { // Domingo
        $tipo_dia_filtro = 'DomingoFeriado';
    }

    if (empty($tipo_dia_filtro)) {
        $response['message'] = 'Não foi possível determinar o tipo de dia para a data fornecida.';
        echo json_encode($response);
        exit;
    }

    $workids_encontrados = [];
    if ($pdo) {
        try {
            // Busca WorkIDs de eventos que pertencem a programações do tipo de dia correto
            // e que são da linha especificada.
            $sql = "SELECT DISTINCT dbe.workid_eventos
                    FROM diario_bordo_eventos dbe
                    JOIN programacao_diaria pd ON dbe.programacao_id = pd.id
                    WHERE dbe.linha_atual_id = :linha_id
                      AND pd.dia_semana_tipo = :tipo_dia_programacao"; // Filtra pelo tipo de dia da programação original do evento

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':linha_id', $linha_id, PDO::PARAM_INT);
            // Aqui, :tipo_dia_programacao DEVE ser o tipo de template que você quer buscar.
            // Se a data_escala é dia útil, você provavelmente quer WorkIDs de programações 'Uteis'.
            $stmt->bindParam(':tipo_dia_programacao', $tipo_dia_filtro, PDO::PARAM_STR); 
            $stmt->execute();
            $resultados_db = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($resultados_db as $workid_evento) {
                $workid_valido = false;
                if ($tipo_dia_filtro === 'Uteis' && strlen($workid_evento) == 7) {
                    $workid_valido = true;
                } elseif ($tipo_dia_filtro === 'Sabado' && strlen($workid_evento) == 8 && substr($workid_evento, 0, 1) == '2') {
                    $workid_valido = true;
                } elseif ($tipo_dia_filtro === 'DomingoFeriado' && strlen($workid_evento) == 8 && substr($workid_evento, 0, 1) == '3') {
                    $workid_valido = true;
                }

                if ($workid_valido) {
                    $workids_encontrados[] = $workid_evento;
                }
            }
            
            if (!empty($workids_encontrados)) {
                sort($workids_encontrados); // Ordena
                $response['success'] = true;
                $response['workids'] = array_values(array_unique($workids_encontrados)); // Garante unicidade final
                $response['message'] = 'WorkIDs carregados.';
            } else {
                $response['message'] = 'Nenhum WorkID compatível encontrado para os critérios.';
            }

        } catch (PDOException $e) {
            $response['message'] = 'Erro no banco de dados: ' . $e->getMessage();
            error_log("Erro AJAX buscar_workids: " . $e->getMessage());
        }
    } else {
        $response['message'] = 'Erro de conexão com o banco de dados.';
    }
}

echo json_encode($response);
?>