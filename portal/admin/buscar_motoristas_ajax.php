<?php
// admin/buscar_motoristas_ajax.php

require_once 'auth_check.php'; // Garante que apenas admins logados possam acessar
require_once '../db_config.php'; // Conexão com o banco

header('Content-Type: application/json'); // Define o tipo de conteúdo da resposta

$resultados_por_pagina_ajax = 10; // Quantos resultados retornar por chamada AJAX (para paginação AJAX)
$itens_retornados = [];
$total_geral_filtro = 0;

$termo_busca = isset($_GET['q']) ? trim($_GET['q']) : '';
$pagina_ajax = isset($_GET['page']) && filter_var($_GET['page'], FILTER_VALIDATE_INT) ? (int)$_GET['page'] : 1;
$offset_ajax = ($pagina_ajax - 1) * $resultados_por_pagina_ajax;

if (strlen($termo_busca) < 2) { // Não busca se o termo for muito curto (consistente com minimumInputLength do Select2)
    echo json_encode(['items' => [], 'total_count' => 0]);
    exit;
}

if ($pdo) {
    try {
        // Contar total de resultados para o filtro (para paginação do Select2)
        $sql_count = "SELECT COUNT(id) FROM motoristas WHERE nome LIKE :termo_nome OR matricula LIKE :termo_matricula";
        $stmt_count = $pdo->prepare($sql_count);
        $stmt_count->bindValue(':termo_nome', '%' . $termo_busca . '%', PDO::PARAM_STR);
        $stmt_count->bindValue(':termo_matricula', '%' . $termo_busca . '%', PDO::PARAM_STR);
        $stmt_count->execute();
        $total_geral_filtro = (int)$stmt_count->fetchColumn();

        // Buscar os motoristas para a página AJAX atual
        $sql = "SELECT id, nome, matricula FROM motoristas
                WHERE nome LIKE :termo_nome OR matricula LIKE :termo_matricula
                ORDER BY nome ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':termo_nome', '%' . $termo_busca . '%', PDO::PARAM_STR);
        $stmt->bindValue(':termo_matricula', '%' . $termo_busca . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $resultados_por_pagina_ajax, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset_ajax, PDO::PARAM_INT);
        $stmt->execute();

        $motoristas_encontrados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($motoristas_encontrados as $motorista) {
            $itens_retornados[] = [
                'id' => $motorista['id'],
                'text' => htmlspecialchars($motorista['nome'] . " (Matrícula: " . $motorista['matricula'] . ")"),
                // Você pode adicionar outros campos aqui se precisar deles na formatação do resultado
                // 'matricula' => htmlspecialchars($motorista['matricula'])
            ];
        }

    } catch (PDOException $e) {
        error_log("Erro AJAX buscar motoristas: " . $e->getMessage());
        // Não envie detalhes do erro PDO no JSON em produção
        echo json_encode(['items' => [], 'total_count' => 0, 'error' => 'Erro ao buscar dados.']);
        exit;
    }
} else {
     echo json_encode(['items' => [], 'total_count' => 0, 'error' => 'Falha na conexão com o banco.']);
     exit;
}

// Retorna o JSON que o Select2 espera
echo json_encode([
    'items' => $itens_retornados,
    'total_count' => $total_geral_filtro // Total de itens que correspondem à busca (não apenas os desta página)
]);
exit;
?>