<?php
// =================================================================
//  Parceiro de Programação - Endpoint para Salvar Ocorrências (v4)
// =================================================================

require 'config_ocorrencias.php';

// Define o cabeçalho da resposta como JSON para o AJAX entender
header('Content-Type: application/json');

// --- Validação dos campos obrigatórios ---
$campos_obrigatorios = [
    'workid' => 'WorkID',
    'motorista_atual' => 'Motorista',
    'linha' => 'Linha',
    'ocorrencia' => 'Ocorrência',
    'monitor' => 'Monitor',
    'fiscal' => 'Fiscal'
];

$campos_em_falta = [];
foreach ($campos_obrigatorios as $campo => $nome_amigavel) {
    if (empty($_POST[$campo])) {
        $campos_em_falta[] = $nome_amigavel;
    }
}

if (!empty($campos_em_falta)) {
    $mensagem_erro = 'Erro: Os seguintes campos obrigatórios não foram preenchidos: ' . implode(', ', $campos_em_falta) . '.';
    echo json_encode(['success' => false, 'message' => $mensagem_erro]);
    exit;
}

// --- Fim da Validação ---


// Prepara os dados para inserção, tratando valores vazios como NULL
$data_ocorrencia = date('Y-m-d');
$horario_ocorrencia = date('H:i:s');
$workid = (int)$_POST['workid'];
$motorista_atual = (int)$_POST['motorista_atual'];
$linha = $_POST['linha'];
$carro_atual = !empty($_POST['carro_atual']) ? $_POST['carro_atual'] : null;
$ocorrencia = $_POST['ocorrencia'];
$incidente = !empty($_POST['incidente']) ? $_POST['incidente'] : null;
$socorro = isset($_POST['socorro']) ? 1 : 0; // Converte checkbox para 1 ou 0
$horario_linha = !empty($_POST['horario_linha']) ? $_POST['horario_linha'] : null;
$terminal = !empty($_POST['terminal']) ? $_POST['terminal'] : null;
$carro_pos = !empty($_POST['carro_pos']) ? $_POST['carro_pos'] : null;
$monitor = $_POST['monitor'];
$fiscal = $_POST['fiscal'];
$observacao = !empty($_POST['observacao']) ? trim($_POST['observacao']) : null;

// Prepara a query SQL para evitar injeção de SQL
$sql = "INSERT INTO registros_ocorrencias (data_ocorrencia, horario_ocorrencia, workid, motorista_atual, linha, carro_atual, ocorrencia, incidente, socorro, horario_linha, terminal, carro_pos, monitor, fiscal, observacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conexao->prepare($sql);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar a query: ' . $conexao->error]);
    exit;
}

// 'ssiissssissssss' corresponde aos tipos de dados das colunas
$stmt->bind_param('ssiissssissssss', 
    $data_ocorrencia, $horario_ocorrencia, $workid, $motorista_atual, $linha, 
    $carro_atual, $ocorrencia, $incidente, $socorro, $horario_linha, $terminal, 
    $carro_pos, $monitor, $fiscal, $observacao
);

// Executa a query e retorna a resposta
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Ocorrência registrada com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao registrar a ocorrência: ' . $stmt->error]);
}

$stmt->close();
$conexao->close();
?>
