<?php
// =================================================================
//  Configuração para Relatório de Bilhetagem (v2.0)
//  - Adicionado Custo de Passagem por Ano
// =================================================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "relatorio";

$conexao = new mysqli($servidor, $usuario, $senha, $banco);
if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}
$conexao->set_charset("utf8mb4");

// --- CONFIGURAÇÃO DE CUSTOS ---
// Define o valor da passagem (Tarifa Técnica ou Pública) por ano
$CUSTO_PASSAGEM = [
    '2024' => 5.75,
    '2025' => 5.75,
    '2026' => 5.75
];

/**
 * Retorna o custo da passagem baseado na data da viagem.
 */
function get_custo_passagem($data_viagem) {
    global $CUSTO_PASSAGEM;
    $ano = date('Y', strtotime($data_viagem));
    return $CUSTO_PASSAGEM[$ano] ?? 0.00;
}

function limpar_numero_csv($valor) {
    if (empty($valor)) return 0;
    // Remove pontos de milhar
    $valor = str_replace('.', '', $valor);
    // Substitui vírgula decimal por ponto
    $valor = str_replace(',', '.', $valor);
    return (float)$valor;
}

/**
 * Calcula variação percentual
 */
function calcular_variacao($atual, $anterior) {
    if ($anterior == 0) return $atual > 0 ? 100 : 0;
    return (($atual - $anterior) / $anterior) * 100;
}
?>