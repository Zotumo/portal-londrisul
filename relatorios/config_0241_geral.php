<?php
// =================================================================
//  Config GERAL (v1)
// =================================================================

// --- 1. CONFIGURAÇÕES E CONEXÃO ---
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(600);

$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "relatorio";

$conexao = new mysqli($servidor, $usuario, $senha, $banco);
if ($conexao->connect_error) { die("Falha na conexão: " . $conexao->connect_error); }
$conexao->set_charset("utf8mb4");

// --- 2. FUNÇÕES AUXILIARES ---
function formatarTempoSQL($tempo) {
    if (is_null($tempo)) { return 'N/A'; }
    $sinal = (strpos($tempo, '-') === 0) ? '-' : '+';
    $tempo_abs_str = ltrim($tempo, '-');
    sscanf($tempo_abs_str, "%d:%d:%d", $hours, $minutes, $seconds);
    $total_segundos = ($hours * 3600) + ($minutes * 60) + ($seconds ?? 0);
    if ($total_segundos == 0) { return '00:00'; }
    return $sinal . ' ' . gmdate("H:i", $total_segundos);
}
function formatarDesvioExcedente($desvio) {
    if (is_null($desvio)) return '00:00';
    $sinal = (strpos($desvio, '-') === 0) ? -1 : 1;
    $tempo_abs_str = ltrim($desvio, '-');
    sscanf($tempo_abs_str, "%d:%d:%d", $hours, $minutes, $seconds);
    $total_segundos = ($hours * 3600) + ($minutes * 60) + ($seconds ?? 0);
    $excesso_segundos = 0;
    if ($sinal > 0) { $tolerancia = 6 * 60; if ($total_segundos > $tolerancia) $excesso_segundos = $total_segundos - $tolerancia; }
    else { $tolerancia = 2 * 60; if ($total_segundos > $tolerancia) $excesso_segundos = $total_segundos - $tolerancia; }
    if ($excesso_segundos <= 0) return '00:00';
    return gmdate('H:i', $excesso_segundos);
}
function getCorDesvio($tempo) {
    if (is_null($tempo)) return '';
    $tempo_abs_str = ltrim($tempo, '-');
    sscanf($tempo_abs_str, "%d:%d:%d", $hours, $minutes, $seconds);
    $total_segundos = ($hours * 3600) + ($minutes * 60) + ($seconds ?? 0);
    if ($total_segundos < 60) return 'text-no-horario';
    if (strpos($tempo, '-') === 0) return 'text-adiantado';
    else return 'text-atrasado';
}