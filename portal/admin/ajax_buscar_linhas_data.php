<?php
// admin/ajax_buscar_linhas_data.php
// CORRIGIDO: Conecta diretamente ao banco 'relatorio' para garantir que ache os dados

require_once 'auth_check.php';
// Não precisamos do db_config.php do portal aqui para a consulta, 
// vamos criar uma conexão específica para o relatorio, igual ao ajax_diario_bordo.php

header('Content-Type: application/json');

$data = $_GET['data'] ?? '';

if (!$data) {
    echo json_encode([]);
    exit;
}

try {
    // 1. CONEXÃO DIRETA AO BANCO RELATORIO (Igual ao seu ajax_diario_bordo.php)
    // Ajuste "root" e senha "" se o seu servidor for diferente, mas baseando-se no XAMPP padrão:
    $host = 'localhost';
    $db   = 'relatorio';
    $user = 'root';
    $pass = ''; // Geralmente vazio no XAMPP, ou coloque sua senha se tiver
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo_rel = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 2. BUSCA AS LINHAS
    $sql = "SELECT DISTINCT ROUTE_ID 
            FROM relatorios_viagens 
            WHERE data_viagem = :data 
            AND ROUTE_ID IS NOT NULL 
			AND ROUTE_ID != '002'
            ORDER BY CAST(ROUTE_ID AS UNSIGNED) ASC";

    $stmt = $pdo_rel->prepare($sql);
    $stmt->execute([':data' => $data]);
    $linhas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $resultado = [];
    foreach ($linhas as $numero_linha) {
        $resultado[] = [
            'id' => $numero_linha, // Agora o ID é o próprio número (ex: "209")
            'text' => "" . $numero_linha
        ];
    }

    echo json_encode($resultado);

} catch (Exception $e) {
    // Retorna erro silencioso no JSON para não quebrar o JS
    // Você pode ver o erro no Inspecionar Elemento > Network se precisar
    echo json_encode([]); 
}
?>