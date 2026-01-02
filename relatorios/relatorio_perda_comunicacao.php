<?php
// =================================================================
//  Parceiro de Programação - Relatório de Perda de Comunicação (v1.0)
//  - Derivado do antigo Mestre de KM.
//  - Foca apenas na perda de sinal do Clever Reports.
// =================================================================

ini_set('display_errors', 1); error_reporting(E_ALL); set_time_limit(600);
require_once 'config_km.php';

$conexao = new mysqli("localhost", "root", "", "relatorio");
if ($conexao->connect_error) { die("Falha na conexão: " . $conexao->connect_error); }
$conexao->set_charset("utf8mb4");

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$where_datas = "BETWEEN '{$conexao->real_escape_string($data_inicio)}' AND '{$conexao->real_escape_string($data_fim)}'";
$where_datas_comunicacao = "DATE(data_evento_inicio) {$where_datas}";

// --- DADOS ---
$sql_comunicacao_det = "SELECT * FROM registros_comunicacao WHERE {$where_datas_comunicacao} ORDER BY data_evento_inicio, vehicle";
$result_com_det = $conexao->query($sql_comunicacao_det);
$lista_comunicacao = $result_com_det ? $result_com_det->fetch_all(MYSQLI_ASSOC) : [];

$comunicacao_por_linha = [];
$comunicacao_por_veiculo = [];
$categorias_comunicacao_chart = ['Logado em Linha' => 0, 'Em Permanência' => 0, 'Recolhendo' => 0, 'Saindo da Garagem' => 0, 'Sem Login' => 0];
$tempo_total_offline = 0;

foreach ($lista_comunicacao as $evento) {
    $veiculo = trim($evento['vehicle'] ?? '');
    $duracao = (int)($evento['duracao_offline_seg'] ?? 0);
    $tempo_total_offline += $duracao;

    if (!isset($comunicacao_por_veiculo[$veiculo])) $comunicacao_por_veiculo[$veiculo] = [];
    $comunicacao_por_veiculo[$veiculo][] = $evento;

    $linha = trim($evento['linha'] ?? '');
    $categoria = formatar_linha_comunicacao($linha); // Usa função do config_km.php
    
    // Categorização Simplificada
    $cat_grafico = 'Logado em Linha';
    if ($categoria === 'Sem Login') $cat_grafico = 'Sem Login';
    elseif (strpos($categoria, 'Saindo') === 0) $cat_grafico = 'Saindo da Garagem';
    elseif (strpos($categoria, 'Recolhendo') === 0) $cat_grafico = 'Recolhendo';
    elseif (strpos($categoria, 'Permanência') !== false) $cat_grafico = 'Em Permanência';
    
    if (isset($categorias_comunicacao_chart[$cat_grafico])) $categorias_comunicacao_chart[$cat_grafico]++;

    if ($cat_grafico === 'Logado em Linha') {
        $chave_linha = ($linha === 'Not Provided' || empty($linha)) ? '-' : $linha;
        if (!isset($comunicacao_por_linha[$chave_linha])) $comunicacao_por_linha[$chave_linha] = ['count' => 0, 'time' => 0];
        $comunicacao_por_linha[$chave_linha]['count']++;
        $comunicacao_por_linha[$chave_linha]['time'] += $duracao;
    }
}
uasort($comunicacao_por_linha, function($a, $b) { return $b['time'] <=> $a['time']; });

$chart_labels = array_keys($categorias_comunicacao_chart);
$chart_data = array_values($categorias_comunicacao_chart);
$chart_colors = array_values($cores_grafico_comunicacao); // Do config
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Perda de Comunicação</title>
    <script src="tailwindcss-3.4.17.js"></script>
    <script src="chart.js"></script>
    <style>body { font-family: sans-serif; background-color: #f3f4f6; } .card { background: white; padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }</style>
</head>
<body class="p-6">
    <div class="max-w-7xl mx-auto">
        <header class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Relatório de Perda de Comunicação</h1>
                <p class="text-gray-500">Análise detalhada de falhas de sinal GPS.</p>
            </div>
            <a href="index.php" class="text-blue-600 font-bold hover:underline">Voltar</a>
        </header>

        <form class="card mb-6 flex gap-4 items-end">
            <div><label class="block text-xs font-bold text-gray-600">Início</label><input type="date" name="data_inicio" value="<?= $data_inicio ?>" class="border rounded p-2"></div>
            <div><label class="block text-xs font-bold text-gray-600">Fim</label><input type="date" name="data_fim" value="<?= $data_fim ?>" class="border rounded p-2"></div>
            <button class="bg-red-600 text-white px-4 py-2 rounded font-bold hover:bg-red-700">Filtrar</button>
        </form>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="card flex flex-col justify-center items-center text-center">
                <h3 class="text-gray-500 font-bold uppercase text-xs">Tempo Total Offline</h3>
                <p class="text-4xl font-bold text-red-600 mt-2"><?= formatar_intervalo_tempo_extenso($tempo_total_offline) ?></p>
                <p class="text-sm text-gray-400 mt-1"><?= formatar_segundos_hhmmss($tempo_total_offline) ?></p>
            </div>
            <div class="card lg:col-span-2 flex items-center">
                <div class="w-1/3"><canvas id="chartStatus"></canvas></div>
                <div class="w-2/3 pl-6 space-y-2">
                    <?php foreach($categorias_comunicacao_chart as $label => $val): if($val==0) continue; ?>
                    <div class="flex justify-between text-sm border-b border-gray-100 pb-1">
                        <span class="font-medium"><?= $label ?></span>
                        <span class="font-bold"><?= $val ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="card">
                <h3 class="font-bold text-lg mb-4">Top Linhas com Falhas (Logadas)</h3>
                <div class="overflow-auto max-h-96">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-100"><tr><th class="p-2">Linha</th><th class="p-2 text-center">Eventos</th><th class="p-2 text-right">Tempo</th></tr></thead>
                        <tbody>
                            <?php foreach($comunicacao_por_linha as $linha => $dados): ?>
                            <tr class="border-b">
                                <td class="p-2 font-medium"><?= $linha ?></td>
                                <td class="p-2 text-center"><?= $dados['count'] ?></td>
                                <td class="p-2 text-right text-red-600 font-mono"><?= formatar_segundos_hhmmss($dados['time']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card">
                <h3 class="font-bold text-lg mb-4">Detalhes por Veículo</h3>
                <div class="overflow-auto max-h-96 space-y-2">
                    <?php foreach($comunicacao_por_veiculo as $veic => $evts): ?>
                    <details class="border rounded">
                        <summary class="p-2 cursor-pointer bg-gray-50 font-bold text-sm flex justify-between">
                            <span>Carro <?= $veic ?></span> <span class="text-red-600"><?= count($evts) ?> eventos</span>
                        </summary>
                        <div class="p-2 bg-white text-xs">
                            <table class="w-full">
                                <thead><tr><th class="text-left">Data</th><th>Linha</th><th class="text-right">Duração</th><th>Local</th></tr></thead>
                                <tbody>
                                    <?php foreach($evts as $e): ?>
                                    <tr>
                                        <td><?= date('d/m H:i', strtotime($e['data_evento_inicio'])) ?></td>
                                        <td><?= $e['linha'] ?></td>
                                        <td class="text-right font-mono"><?= formatar_segundos_hhmmss($e['duracao_offline_seg']) ?></td>
                                        <td class="text-center">
                                            <?php if(criar_link_maps($e['latitude'], $e['longitude'])): ?>
                                            <a href="<?= criar_link_maps($e['latitude'], $e['longitude']) ?>" target="_blank" class="text-blue-500">Mapa</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </details>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
        new Chart(document.getElementById('chartStatus'), {
            type: 'doughnut',
            data: { labels: <?= json_encode($chart_labels) ?>, datasets: [{ data: <?= json_encode($chart_data) ?>, backgroundColor: <?= json_encode($chart_colors) ?> }] },
            options: { plugins: { legend: { display: false } } }
        });
    </script>
</body>
</html>