<?php
// admin/ajax_check_workid.php
require_once 'auth_check.php';
require_once '../db_config.php';

header('Content-Type: application/json');

$work_id = $_POST['work_id'] ?? '';
$data_escala = $_POST['data'] ?? date('Y-m-d');

if (empty($work_id)) {
    echo json_encode(['sucesso' => false, 'msg' => 'WorkID vazio']);
    exit;
}

try {
    // 1. BUSCAR DADOS DO SERVIÇO (Vigência)
    // CORREÇÃO: Usamos MAX() no bloco para garantir que não pegamos uma linha vazia
    // se houver agrupamento.
    $sqlServico = "SELECT 
                        MIN(START_TIME) as inicio, 
                        MAX(END_TIME) as fim, 
                        MAX(TRIM(REFERREDVB_COMPANYCODE)) as bloco
                   FROM relatorios_servicos
                   WHERE TRIM(DUTY_COMPANYCODE) = :wid 
                   AND :data BETWEEN data_inicio_vigencia AND data_fim_vigencia
                   GROUP BY DUTY_COMPANYCODE";
    
    $stmtS = $pdo_relatorios->prepare($sqlServico);
    $stmtS->execute([':wid' => $work_id, ':data' => $data_escala]);
    $dadosServico = $stmtS->fetch(PDO::FETCH_ASSOC);

    if (!$dadosServico) {
        echo json_encode(['sucesso' => false, 'msg' => 'WorkID não encontrado na vigência.']);
        exit;
    }

    $inicio = substr($dadosServico['inicio'], 0, 5);
    $fim = substr($dadosServico['fim'], 0, 5);
    $bloco_raw = $dadosServico['bloco']; 

    // --- PLANO B: Se o bloco veio vazio, tenta usar o WorkID como Bloco ---
    if (empty($bloco_raw) || $bloco_raw === '') {
        $bloco_raw = $work_id; 
    }

    // 2. BUSCAR LINHAS ASSOCIADAS AO BLOCO NA TABELA DE VIAGENS
    // Tenta buscar pelo bloco exato string OU pelo bloco numérico
    $bloco_int = (int)$bloco_raw; 

    // Query otimizada para buscar as linhas
    $sqlLinhas = "SELECT DISTINCT ROUTE_ID 
                  FROM relatorios_viagens 
                  WHERE (
                        TRIM(BLOCK_NUMBER) = :blk_raw 
                        OR BLOCK_NUMBER = :blk_int
                        OR TRIM(BLOCK_NUMBER) = :wid_raw -- Tenta bater direto com WorkID
                  )
                  AND data_viagem = :data
                  AND ROUTE_ID IS NOT NULL AND ROUTE_ID != ''
                  ORDER BY ROUTE_ID ASC";
    
    $stmtL = $pdo_relatorios->prepare($sqlLinhas);
    $stmtL->execute([
        ':blk_raw' => (string)$bloco_raw, 
        ':blk_int' => $bloco_int,
        ':wid_raw' => (string)$work_id, // Passa o WorkID original também por segurança
        ':data' => $data_escala
    ]);
    
    $linhas_array = $stmtL->fetchAll(PDO::FETCH_COLUMN);

    // --- PLANO C (Fallback de Data): Se não achou na data específica ---
    // (Pode ser que a importação de viagens dessa data futura ainda não foi feita)
    // Busca a ocorrência mais recente desse bloco para "adivinhar" as linhas.
    if (empty($linhas_array)) {
        $sqlFallback = "SELECT DISTINCT ROUTE_ID 
                        FROM relatorios_viagens 
                        WHERE (TRIM(BLOCK_NUMBER) = :blk_raw OR BLOCK_NUMBER = :blk_int)
                        ORDER BY data_viagem DESC 
                        LIMIT 10"; 
        $stmtF = $pdo_relatorios->prepare($sqlFallback);
        $stmtF->execute([':blk_raw' => (string)$bloco_raw, ':blk_int' => $bloco_int]);
        $linhas_array = $stmtF->fetchAll(PDO::FETCH_COLUMN);
    }

    // Formatação Final
    $linhas_unicas = array_unique($linhas_array);
    sort($linhas_unicas); 
    
    $linhas_formatadas = implode('/', $linhas_unicas);
    $qtd_linhas = count($linhas_unicas);

    echo json_encode([
        'sucesso' => true,
        'inicio' => $inicio,
        'fim' => $fim,
        'linhas_texto' => $linhas_formatadas,
        'qtd_linhas' => $qtd_linhas,
        'bloco' => $bloco_raw
    ]);

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'msg' => 'Erro: ' . $e->getMessage()]);
}
?>