<?php
// admin/ajax_buscar_workids_escala.php
// CORRIGIDO: Usa conexão direta ao banco 'relatorio' (Igual ao ajax_diario_bordo.php)

require_once 'auth_check.php';
// Não incluimos db_config.php para evitar conflito, vamos conectar direto.

header('Content-Type: application/json');

$linha = $_GET['linha'] ?? ''; // Ex: "094"
$data = $_GET['data'] ?? '';   // Ex: "2025-12-30"

if (!$linha || !$data) {
    echo json_encode([]);
    exit;
}

try {
    // 1. CONEXÃO DIRETA AO BANCO RELATORIO
    // (Mesmas credenciais do seu ajax_diario_bordo.php)
    $host = 'localhost';
    $db   = 'relatorio';
    $user = 'root';
    $pass = ''; 
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo_rel = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 2. BUSCA WORKIDS (Lógica idêntica ao ajax_diario_bordo.php -> listar_workids)
    // Busca na tabela relatorios_servicos filtrando pela data e pela linha
    // A subquery garante que pegamos apenas serviços que realmente rodaram na linha escolhida
    
    $sql = "SELECT DISTINCT s.DUTY_COMPANYCODE as workid 
            FROM relatorios_servicos s
            WHERE :data BETWEEN s.data_inicio_vigencia AND s.data_fim_vigencia
            AND TRIM(s.REFERREDVB_COMPANYCODE) IN (
                SELECT DISTINCT TRIM(v.BLOCK_NUMBER) 
                FROM relatorios_viagens v 
                WHERE v.data_viagem = :data 
                AND v.ROUTE_ID = :linha
            )
            ORDER BY s.DUTY_COMPANYCODE ASC";

    $stmt = $pdo_rel->prepare($sql);
    $stmt->execute([':data' => $data, ':linha' => $linha]);
    
    $workids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($workids);

} catch (Exception $e) {
    // Retorna vazio em caso de erro para não quebrar o front
    error_log("Erro buscar workids: " . $e->getMessage());
    echo json_encode([]);
}
?>