<?php
// admin/buscar_veiculos_disponiveis_ajax.php
// CORRIGIDO: Recebe o número da linha diretamente, sem buscar ID

require_once 'auth_check.php';
require_once '../db_config.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Parâmetros inválidos.', 'veiculos' => []];

$linha_numero = $_POST['linha_id'] ?? ''; // Agora recebe "217" direto
$data_escala_str = $_POST['data_escala'] ?? '';
$hora_inicio_str = $_POST['hora_inicio'] ?? '';
$hora_fim_str = $_POST['hora_fim'] ?? '';
$escala_id_atual = $_POST['escala_id_atual'] ?? 0;
$tabela_alvo = $_POST['tabela_escala'] ?? 'planejada';

// Validações Básicas
if (empty($linha_numero) || empty($data_escala_str) || empty($hora_inicio_str) || empty($hora_fim_str)) {
    $response['message'] = 'Preencha todos os campos obrigatórios.';
    echo json_encode($response);
    exit;
}

// Validação de Datas/Horas (Igual ao anterior)
try {
    $inicio_req_dt = new DateTime($data_escala_str . ' ' . $hora_inicio_str);
    $fim_req_dt = new DateTime($data_escala_str . ' ' . $hora_fim_str);
    if ($fim_req_dt <= $inicio_req_dt) {
        $fim_req_dt->modify('+1 day');
    }
} catch (Exception $e) {
    $response['message'] = 'Horário inválido.';
    echo json_encode($response);
    exit;
}

try {
    // 1. CONEXÃO DIRETA AO BANCO RELATORIO (Para buscar tipos permitidos)
    // Usamos a mesma lógica do ajax_buscar_workids para evitar conflito de tabelas
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

    // 2. BUSCAR OS TIPOS PERMITIDOS
    // Agora usamos diretamente a variável $linha_numero
    $stmt_tipos = $pdo_rel->prepare("SELECT tipo_veiculo FROM linhas_veiculos WHERE linha_numero = :num");
    $stmt_tipos->execute([':num' => $linha_numero]);
    $tipos_permitidos = $stmt_tipos->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tipos_permitidos)) {
        // Se não tem configuração, libera tudo ou bloqueia (depende da sua regra)
        // Aqui vamos bloquear e avisar para configurar
        $response['success'] = true;
        $response['veiculos'] = [];
        $response['message'] = "Linha $linha_numero sem tipos de veículo configurados no Gerenciador.";
        echo json_encode($response);
        exit;
    }

    // 3. BUSCAR VEÍCULOS (No banco PORTAL - variável $pdo padrão)
    // A partir daqui usamos o $pdo do db_config.php pois a tabela 'veiculos' e 'motorista_escalas' estão lá
    
    $in_placeholders = implode(',', array_fill(0, count($tipos_permitidos), '?'));
    
    $sql_candidatos = "SELECT id, prefixo, tipo FROM veiculos WHERE status = 'operação' AND tipo IN ({$in_placeholders}) ORDER BY prefixo ASC";
    $stmt_candidatos = $pdo->prepare($sql_candidatos);
    $stmt_candidatos->execute($tipos_permitidos);
    $veiculos_candidatos = $stmt_candidatos->fetchAll(PDO::FETCH_ASSOC);

    if (empty($veiculos_candidatos)) {
        $response['success'] = true;
        $response['message'] = 'Nenhum veículo disponível na frota para os tipos: ' . implode(', ', $tipos_permitidos);
        echo json_encode($response);
        exit;
    }

    // 4. VERIFICAR CONFLITOS (Banco PORTAL)
    $ids_candidatos = array_column($veiculos_candidatos, 'id');
    $tabela_db = ($tabela_alvo === 'planejada') ? 'motorista_escalas' : 'motorista_escalas_diaria';
    
    $in_placeholders_v = implode(',', array_fill(0, count($ids_candidatos), '?'));
    
    // Busca escalas que usam esses carros na data
    $sql_conflitos = "SELECT veiculo_id, hora_inicio_prevista, hora_fim_prevista 
                      FROM {$tabela_db} 
                      WHERE data = ? AND veiculo_id IN ({$in_placeholders_v}) AND id != ?";
                      
    $params_conflitos = array_merge([$data_escala_str], $ids_candidatos, [$escala_id_atual]);
    $stmt_conflitos = $pdo->prepare($sql_conflitos);
    $stmt_conflitos->execute($params_conflitos);
    $conflitos = $stmt_conflitos->fetchAll(PDO::FETCH_ASSOC);

    $ocupados = [];
    foreach ($conflitos as $c) {
        if ($c['hora_inicio_prevista'] && $c['hora_fim_prevista']) {
            $ini_exist = new DateTime($data_escala_str . ' ' . $c['hora_inicio_prevista']);
            $fim_exist = new DateTime($data_escala_str . ' ' . $c['hora_fim_prevista']);
            if ($fim_exist <= $ini_exist) $fim_exist->modify('+1 day');

            // Interseção de horários
            if ($inicio_req_dt < $fim_exist && $fim_req_dt > $ini_exist) {
                $ocupados[$c['veiculo_id']] = true;
            }
        }
    }

    // 5. LISTA FINAL
    $finais = [];
    foreach ($veiculos_candidatos as $v) {
        if (!isset($ocupados[$v['id']])) {
            $finais[] = [
                'id' => $v['id'],
                'text' => $v['prefixo'] . ' (' . $v['tipo'] . ')'
            ];
        }
    }

    if (!empty($finais)) {
        $response['success'] = true;
        $response['veiculos'] = $finais;
        $response['message'] = count($finais) . ' veículo(s) encontrado(s).';
    } else {
        $response['success'] = true;
        $response['message'] = 'Todos os veículos compatíveis estão ocupados neste horário.';
    }

} catch (PDOException $e) {
    error_log("Erro veiculos: " . $e->getMessage());
    $response['message'] = 'Erro interno ao buscar veículos.';
}

echo json_encode($response);
exit;
?>