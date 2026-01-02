<?php
// =================================================================
//  Parceiro de Programação - Arquivo de Configuração e Funções
//  para o Relatório Timepoint Geral (v2.0)
//  - Parametrizada a função formatarDesvioExcedente
// =================================================================

// --- 1. CONFIGURAÇÕES GERAIS ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(600);

// --- 2. CONEXÃO COM O BANCO DE DADOS ---
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "relatorio";

$conexao = new mysqli($servidor, $usuario, $senha, $banco);
if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}
$conexao->set_charset("utf8mb4");

// --- 3. FUNÇÕES AUXILIARES ---

/**
 * Formata um tempo SQL (HH:MM:SS ou -HH:MM:SS) para + HH:MM ou - HH:MM
 * Retorna 'N/A' se o tempo for nulo.
 * Retorna '00:00' se o tempo for zero.
 */
function formatarTempoSQL($tempo) {
    if (is_null($tempo)) return 'N/A';
    $sinal = (strpos($tempo, '-') === 0) ? '-' : '+';
    $tempo_abs_str = ltrim($tempo, '-+'); // Remove sinal, se houver
    
    // Tenta extrair horas, minutos e segundos
    $parts = explode(':', $tempo_abs_str);
    $hours = isset($parts[0]) ? intval($parts[0]) : 0;
    $minutes = isset($parts[1]) ? intval($parts[1]) : 0;
    $seconds = isset($parts[2]) ? intval($parts[2]) : 0;

    $total_segundos = ($hours * 3600) + ($minutes * 60) + $seconds;
    
    if ($total_segundos == 0) return '00:00'; // Retorna 00:00 se for zero, sem sinal

    // Calcula horas e minutos a partir do total de segundos (ignorando segundos no output)
    $output_hours = floor($total_segundos / 3600);
    $output_minutes = floor(($total_segundos % 3600) / 60);

    // Formata a saída HH:MM
    return $sinal . ' ' . sprintf('%02d:%02d', $output_hours, $output_minutes);
}


/**
 * Calcula o tempo excedente de um desvio, baseado nas tolerâncias fornecidas.
 * Retorna '00:00' se não houver excesso.
 * * @param string|null $desvio O tempo de desvio no formato SQL (HH:MM:SS ou -HH:MM:SS).
 * @param int $tolerancia_atraso_segundos Tolerância para atraso em segundos (ex: 6 * 60).
 * @param int $tolerancia_adiantado_segundos Tolerância para adiantamento em segundos (ex: 2 * 60).
 * @return string O tempo excedente formatado como HH:MM.
 */
function formatarDesvioExcedente($desvio, $tolerancia_atraso_segundos, $tolerancia_adiantado_segundos) {
    if (is_null($desvio)) return '00:00';
    
    $sinal = (strpos($desvio, '-') === 0) ? -1 : 1;
    $tempo_abs_str = ltrim($desvio, '-+'); // Remove sinal, se houver

    // Tenta extrair horas, minutos e segundos
    $parts = explode(':', $tempo_abs_str);
    $hours = isset($parts[0]) ? intval($parts[0]) : 0;
    $minutes = isset($parts[1]) ? intval($parts[1]) : 0;
    $seconds = isset($parts[2]) ? intval($parts[2]) : 0;
    
    $total_segundos = ($hours * 3600) + ($minutes * 60) + $seconds;
    
    $excesso_segundos = 0;
    if ($sinal > 0) { // Atraso
        if ($total_segundos > $tolerancia_atraso_segundos) {
            $excesso_segundos = $total_segundos - $tolerancia_atraso_segundos;
        }
    } else { // Adiantamento
        if ($total_segundos > $tolerancia_adiantado_segundos) {
            $excesso_segundos = $total_segundos - $tolerancia_adiantado_segundos;
        }
    }
    
    if ($excesso_segundos <= 0) return '00:00';

    // Calcula horas e minutos do excesso
    $output_hours = floor($excesso_segundos / 3600);
    $output_minutes = floor(($excesso_segundos % 3600) / 60);

    // Retorna formatado HH:MM (sem sinal, pois excesso é sempre positivo)
    return sprintf('%02d:%02d', $output_hours, $output_minutes);
}

/**
 * Retorna a classe CSS de cor baseada no tempo de desvio e nas tolerâncias.
 * * @param string|null $tempo O tempo de desvio no formato SQL.
 * @param int $tolerancia_atraso_segundos Tolerância para atraso em segundos.
 * @param int $tolerancia_adiantado_segundos Tolerância para adiantamento em segundos.
 * @return string A classe CSS ('text-no-horario', 'text-atrasado', 'text-adiantado').
 */
function getCorDesvio($tempo, $tolerancia_atraso_segundos, $tolerancia_adiantado_segundos) {
    if (is_null($tempo)) return ''; // Sem cor se não houver tempo

    $sinal = (strpos($tempo, '-') === 0) ? -1 : 1;
    $tempo_abs_str = ltrim($tempo, '-+');

    $parts = explode(':', $tempo_abs_str);
    $hours = isset($parts[0]) ? intval($parts[0]) : 0;
    $minutes = isset($parts[1]) ? intval($parts[1]) : 0;
    $seconds = isset($parts[2]) ? intval($parts[2]) : 0;
    
    $total_segundos = ($hours * 3600) + ($minutes * 60) + $seconds;

    if ($sinal > 0) { // Atraso
        if ($total_segundos >= $tolerancia_atraso_segundos) {
            return 'text-atrasado';
        }
    } else { // Adiantamento
        if ($total_segundos >= $tolerancia_adiantado_segundos) {
            return 'text-adiantado';
        }
    }
    
    // Se não for atrasado nem adiantado (dentro da tolerância)
    return 'text-no-horario';
}

?>
