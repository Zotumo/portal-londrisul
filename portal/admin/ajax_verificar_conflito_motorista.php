<?php
// admin/ajax_verificar_conflito_motorista.php
require_once 'auth_check.php';
require_once '../db_config.php';

header('Content-Type: application/json');

$motorista_id = $_GET['motorista_id'] ?? '';
$data = $_GET['data'] ?? '';
$id_ignorar = $_GET['id_ignorar'] ?? 0;

if (!$motorista_id || !$data) {
    echo json_encode(['existe' => false]);
    exit;
}

try {
    // Busca se existe alguma escala para este motorista nesta data
    $sql = "SELECT id, work_id, linha_origem_id 
            FROM motorista_escalas 
            WHERE motorista_id = :mot 
            AND data = :data 
            AND id != :ignorar
            LIMIT 1"; // Pega pelo menos uma pra saber o tipo

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':mot' => $motorista_id,
        ':data' => $data,
        ':ignorar' => $id_ignorar
    ]);

    $escala = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($escala) {
        // Verifica se é Status Especial
        $status_keywords = ['FOLGA', 'FALTA', 'FÉRIAS', 'ATESTADO', 'FORADEESCALA'];
        $eh_status = in_array(strtoupper($escala['work_id']), $status_keywords);
        
        $tipo_conflito = $eh_status ? 'status' : 'trabalho';

        // Formata descrição
        $desc = "Indefinido";
        if ($eh_status) {
            $desc = strtoupper($escala['work_id']);
        } elseif ($escala['linha_origem_id']) {
            $desc = "Linha " . $escala['linha_origem_id'];
        } else {
            $desc = "WorkID " . $escala['work_id'];
        }

        echo json_encode([
            'existe' => true,
            'id_conflito' => $escala['id'],
            'tipo_existente' => $tipo_conflito, // <--- NOVO CAMPO IMPORTANTE
            'descricao' => $desc
        ]);
    } else {
        echo json_encode(['existe' => false]);
    }

} catch (Exception $e) {
    echo json_encode(['existe' => false, 'erro' => $e->getMessage()]);
}
?>