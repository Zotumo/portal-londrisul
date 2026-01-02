<?php
// admin/buscar_info_opcoes_ajax.php
// Busca opções de informação (globais e/ou específicas de linha) para o Diário de Bordo.

require_once 'auth_check.php'; // Garante que apenas usuários logados do admin acessem
require_once '../db_config.php';

header('Content-Type: application/json');
$response = ['success' => false, 'globais' => [], 'especificas' => [], 'message' => 'Parâmetros inválidos.'];

$linha_id = isset($_GET['linha_id']) ? filter_var($_GET['linha_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null;

// Permissão para buscar (pode ser a mesma de ver eventos ou mais genérica)
$niveis_permitidos_buscar_infos = $niveis_acesso_ver_eventos_diario ?? ['CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_buscar_infos)) {
    $response['message'] = 'Não autorizado.';
    echo json_encode($response);
    exit;
}

if ($pdo) {
    try {
        // Buscar opções GLOBAIS (sempre busca estas)
        $stmt_globais = $pdo->prepare("SELECT id, descricao_info FROM info_opcoes WHERE linha_id IS NULL AND status_info = 'ativo' ORDER BY descricao_info ASC");
        $stmt_globais->execute();
        $response['globais'] = $stmt_globais->fetchAll(PDO::FETCH_ASSOC);
        $response['success'] = true; // Sucesso parcial mesmo que não haja específicas

        // Buscar opções ESPECÍFICAS da linha, se uma linha_id foi fornecida
        if ($linha_id) {
            $stmt_especificas = $pdo->prepare("SELECT id, descricao_info FROM info_opcoes WHERE linha_id = :linha_id AND status_info = 'ativo' ORDER BY descricao_info ASC");
            $stmt_especificas->bindParam(':linha_id', $linha_id, PDO::PARAM_INT);
            $stmt_especificas->execute();
            $response['especificas'] = $stmt_especificas->fetchAll(PDO::FETCH_ASSOC);
        }
        $response['message'] = 'Opções carregadas.';

    } catch (PDOException $e) {
        error_log("Erro AJAX ao buscar info_opcoes: " . $e->getMessage());
        $response['message'] = 'Erro no servidor ao buscar opções de informação.';
        $response['success'] = false; // Garante que o sucesso seja falso em caso de erro
    }
} else {
    $response['message'] = 'Falha na conexão com o banco de dados.';
    $response['success'] = false;
}

echo json_encode($response);
exit;
?>