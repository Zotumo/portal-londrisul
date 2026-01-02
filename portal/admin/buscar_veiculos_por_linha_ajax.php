<?php
// admin/buscar_veiculos_por_linha_ajax.php

require_once 'auth_check.php'; // Segurança básica de autenticação
require_once '../db_config.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Parâmetros inválidos.', 'veiculos' => []];

// Permissão para esta funcionalidade AJAX (pode ser a mesma de gerenciar escalas)
$niveis_permitidos_buscar_veiculos_escala = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_buscar_veiculos_escala)) {
    $response['message'] = 'Acesso negado para buscar veículos para escala.';
    echo json_encode($response);
    exit;
}

$linha_id = isset($_GET['linha_id']) ? filter_var($_GET['linha_id'], FILTER_VALIDATE_INT) : null;

if (!$linha_id) {
    echo json_encode($response);
    exit;
}

if ($pdo) {
    try {
        // 1. Buscar os tipos de veículo permitidos para a linha_id
        $stmt_tipos_permitidos = $pdo->prepare("SELECT tipo_veiculo FROM linha_tipos_veiculo_permitidos WHERE linha_id = :linha_id");
        $stmt_tipos_permitidos->bindParam(':linha_id', $linha_id, PDO::PARAM_INT);
        $stmt_tipos_permitidos->execute();
        $tipos_permitidos = $stmt_tipos_permitidos->fetchAll(PDO::FETCH_COLUMN);

        if (empty($tipos_permitidos)) {
            $response['message'] = 'Nenhum tipo de veículo configurado como permitido para esta linha.';
            // Ainda retornamos success = true, mas com lista de veículos vazia e uma mensagem.
            $response['success'] = true; 
            echo json_encode($response);
            exit;
        }

        // 2. Montar a cláusula IN para os tipos de veículo
        // Isso cria placeholders como :tipo0, :tipo1, etc.
        $in_placeholders = implode(',', array_map(function($index) {
            return ":tipo{$index}";
        }, array_keys($tipos_permitidos)));

        // 3. Buscar os veículos que são dos tipos permitidos e estão em operação
        $sql_veiculos = "SELECT id, prefixo, tipo FROM veiculos 
                         WHERE tipo IN ({$in_placeholders}) AND status = 'operação'
                         ORDER BY prefixo ASC";
        
        $stmt_veiculos = $pdo->prepare($sql_veiculos);
        
        // Bind dos valores para os placeholders dos tipos
        foreach ($tipos_permitidos as $index => $tipo) {
            $stmt_veiculos->bindValue(":tipo{$index}", $tipo);
        }
        
        $stmt_veiculos->execute();
        $veiculos_encontrados = $stmt_veiculos->fetchAll(PDO::FETCH_ASSOC);

        if ($veiculos_encontrados) {
            $response['success'] = true;
            $response['message'] = 'Veículos compatíveis encontrados.';
            // Formatar para o Select2 ou para um select HTML simples
            foreach ($veiculos_encontrados as $veiculo) {
                $response['veiculos'][] = [
                    'id' => $veiculo['id'],
                    // O texto pode incluir o tipo para ajudar o escalador, se desejado
                    'text' => htmlspecialchars($veiculo['prefixo'] . ' (' . $veiculo['tipo'] . ')') 
                ];
            }
        } else {
            $response['success'] = true; // Sucesso na operação, mas nenhum veículo encontrado
            $response['message'] = 'Nenhum veículo em operação encontrado para os tipos permitidos desta linha.';
        }

    } catch (PDOException $e) {
        error_log("Erro AJAX ao buscar veículos por linha: " . $e->getMessage() . " para linha_id: " . $linha_id);
        $response['message'] = 'Erro no servidor ao buscar veículos. Tente novamente.';
    }
} else {
    $response['message'] = 'Erro de conexão com o banco de dados.';
}

echo json_encode($response);
?>