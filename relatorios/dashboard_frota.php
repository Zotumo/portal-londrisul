<?php
// =================================================================
//  Parceiro de Programação - Dashboard de Análise de Frota (v6.3)
//  - ADICIONADO: Títulos e cabeçalhos atualizados para "(004)"
//    para diferenciar do Relatório 142.
// =================================================================

// --- 1. CONFIGURAÇÕES E CONEXÃO ---
ini_set('display_errors', 1); error_reporting(E_ALL);
$conexao = new mysqli("localhost", "root", "", "relatorio");
if ($conexao->connect_error) { die("Falha na conexão: " . $conexao->connect_error); }
$conexao->set_charset("utf8mb4");

// --- 2. FUNÇÕES AUXILIARES ---
function formatar_segundos($totalSegundos) {
    if (!is_numeric($totalSegundos)) return "0h 0m";
    $sinal = $totalSegundos < 0 ? '-' : '';
    $totalSegundos = abs($totalSegundos);
    $horas = floor($totalSegundos / 3600);
    $minutos = floor(($totalSegundos % 3600) / 60);
    return $sinal . "{$horas}h {$minutos}m";
}

// --- 3. LÓGICA DE FILTROS ---
$intervalo_datas_db = $conexao->query("SELECT MIN(data_viagem) as min_date, MAX(data_viagem) as max_date FROM relatorios_frota")->fetch_assoc();
$linhas_disponiveis = $conexao->query("SELECT DISTINCT route FROM relatorios_frota WHERE route IS NOT NULL AND route != '' ORDER BY route ASC")->fetch_all(MYSQLI_ASSOC);
$motoristas_disponiveis = $conexao->query("SELECT DISTINCT operator FROM relatorios_frota WHERE operator IS NOT NULL AND operator != '' ORDER BY operator ASC")->fetch_all(MYSQLI_ASSOC);

$data_inicio_padrao = $intervalo_datas_db['min_date'] ?? date('Y-m-d');
$data_fim_padrao = $intervalo_datas_db['max_date'] ?? date('Y-m-d');

$data_inicio = $_GET['data_inicio'] ?? $data_inicio_padrao;
$data_fim = $_GET['data_fim'] ?? $data_fim_padrao;
$periodo_selecionado = $_GET['periodo'] ?? 'geral';
$linha_selecionada = $_GET['linha'] ?? 'todas';
$motorista_selecionado = $_GET['motorista'] ?? 'todos';

$where_conditions = ["data_viagem BETWEEN '{$conexao->real_escape_string($data_inicio)}' AND '{$conexao->real_escape_string($data_fim)}'"];
if ($linha_selecionada !== 'todas') $where_conditions[] = "route = '{$conexao->real_escape_string($linha_selecionada)}'";
if ($motorista_selecionado !== 'todos') $where_conditions[] = "operator = '{$conexao->real_escape_string($motorista_selecionado)}'";
switch ($periodo_selecionado) {
    case 'uteis': $where_conditions[] = "WEEKDAY(data_viagem) BETWEEN 0 AND 4"; break;
    case 'sabados': $where_conditions[] = "WEEKDAY(data_viagem) = 5"; break;
    case 'domingos': $where_conditions[] = "WEEKDAY(data_viagem) = 6"; break;
}
$where_clause = "WHERE " . implode(' AND ', $where_conditions);

$titulo_periodo = ucfirst($periodo_selecionado);
$titulo_linha = ($linha_selecionada === 'todas') ? "Todas as Linhas" : "Linha " . htmlspecialchars($linha_selecionada);
$titulo_motorista = ($motorista_selecionado === 'todos') ? "Todos" : htmlspecialchars($motorista_selecionado);

// --- 4. QUERIES DE ANÁLISE ---
$total_registros = $conexao->query("SELECT COUNT(*) as total FROM relatorios_frota {$where_clause}")->fetch_assoc()['total'] ?? 0;
$dados_para_js = []; $stats_rotas = []; $stats_motoristas = []; $stats_veiculos = []; $kpis = [];

if ($total_registros > 0) {
    // Aliases mantidos para compatibilidade com o código PHP existente
    $sql_kpis = "SELECT 
        SUM(distancia_produtiva_p_km + distancia_recolha_p_km + distancia_ociosa_p_km) as total_distancia_p, 
        SUM(distancia_r_km) as total_distancia_r,
        SUM(tempo_exec_p_seg) as total_tempo_p, SUM(tempo_exec_r_seg) as total_tempo_r,
        SUM(tempo_produtivo_p_seg) as total_tempo_produtivo_p_seg, SUM(tempo_produtivo_r_seg) as total_tempo_produtivo_r_seg,
        SUM(distancia_produtiva_p_km) as total_distancia_produtiva_p_km, SUM(distancia_produtiva_r_km) as total_distancia_produtiva_r_km,
        SUM(tempo_recolha_p_seg) as total_tempo_recolha_p_seg, SUM(tempo_recolha_r_seg) as total_tempo_recolha_r_seg,
        SUM(distancia_recolha_p_km) as total_distancia_recolha_p_km, SUM(distancia_recolha_r_km) as total_distancia_recolha_r_km,
        SUM(tempo_ocioso_p_seg) as total_tempo_ocioso_p_seg, SUM(tempo_ocioso_r_seg) as total_tempo_ocioso_r_seg,
        SUM(distancia_ociosa_p_km) as total_distancia_ociosa_p_km, SUM(distancia_ociosa_r_km) as total_distancia_ociosa_r_km,
        SUM(tempo_ocioso_total_r_seg) as total_tempo_ocioso_total_r_seg
    FROM relatorios_frota {$where_clause}";
    $kpis = $conexao->query($sql_kpis)->fetch_assoc();

    $sql_rotas = "SELECT route, COUNT(*) as total_viagens, SUM(distancia_p_km) as dist_p, SUM(distancia_r_km) as dist_r, SUM(tempo_exec_p_seg) as tempo_p, SUM(tempo_exec_r_seg) as tempo_r FROM relatorios_frota {$where_clause} GROUP BY route ORDER BY route ASC";
    $stats_rotas = $conexao->query($sql_rotas)->fetch_all(MYSQLI_ASSOC);

    $sql_motoristas = "SELECT operator, matricula, COUNT(*) as total_viagens, SUM(distancia_r_km) as dist_r, SUM(tempo_exec_r_seg) as tempo_r, SUM(tempo_exec_r_seg - tempo_exec_p_seg) as desvio_total FROM relatorios_frota {$where_clause} GROUP BY operator, matricula ORDER BY total_viagens DESC";
    $stats_motoristas = $conexao->query($sql_motoristas)->fetch_all(MYSQLI_ASSOC);

    $sql_veiculos = "SELECT vehicle, COUNT(*) as total_viagens, SUM(distancia_r_km) as dist_r, SUM(tempo_exec_r_seg) as tempo_r FROM relatorios_frota {$where_clause} GROUP BY vehicle ORDER BY dist_r DESC";
    $stats_veiculos = $conexao->query($sql_veiculos)->fetch_all(MYSQLI_ASSOC);

    $dados_para_js = [
        'distancia' => [round($kpis['total_distancia_p'] ?? 0, 2), round($kpis['total_distancia_r'] ?? 0, 2)],
        'tempo' => [round(($kpis['total_tempo_p'] ?? 0) / 3600, 2), round(($kpis['total_tempo_r'] ?? 0) / 3600, 2)]
    ];
}
$conexao->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Análise de Frota (004)</title>
    <script src="tailwindcss-3.4.17.js"></script>
    <script src="chart.js"></script>
    <style>
        .card { background-color: white; border-radius: 0.75rem; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1), 0 1px 2px -1px rgba(0,0,0,0.1); }
        .scrolling-container { max-height: 400px; overflow-y: auto; }
        thead th { position: sticky; top: 0; z-index: 10; background-color: #f0fdf4; }
    </style>
</head>
<body class="bg-green-50 font-sans">
    <main class="container mx-auto p-4 md:p-8">
        <header class="text-center mb-8">
            <h1 class="text-4xl font-bold text-green-800">Dashboard de Análise de Frota (004)</h1>
            <p class="mt-2 text-lg text-green-700">Análise de desempenho de distância e horas por veículo.</p>
        </header>

        <div class="card p-4 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                <div><label for="data_inicio" class="block text-sm font-medium text-gray-700">Data Início</label><input type="date" name="data_inicio" id="data_inicio" value="<?= htmlspecialchars($data_inicio) ?>" min="<?= htmlspecialchars($intervalo_datas_db['min_date'] ?? '') ?>" max="<?= htmlspecialchars($intervalo_datas_db['max_date'] ?? '') ?>" class="mt-1 w-full p-2 border border-gray-300 rounded-md"></div>
                <div><label for="data_fim" class="block text-sm font-medium text-gray-700">Data Fim</label><input type="date" name="data_fim" id="data_fim" value="<?= htmlspecialchars($data_fim) ?>" min="<?= htmlspecialchars($intervalo_datas_db['min_date'] ?? '') ?>" max="<?= htmlspecialchars($intervalo_datas_db['max_date'] ?? '') ?>" class="mt-1 w-full p-2 border border-gray-300 rounded-md"></div>
                <div><label for="periodo" class="block text-sm font-medium text-gray-700">Período</label><select name="periodo" id="periodo" class="mt-1 w-full p-2 border border-gray-300 rounded-md"><option value="geral" <?= $periodo_selecionado == 'geral' ? 'selected' : '' ?>>Geral</option><option value="uteis" <?= $periodo_selecionado == 'uteis' ? 'selected' : '' ?>>Dias Úteis</option><option value="sabados" <?= $periodo_selecionado == 'sabados' ? 'selected' : '' ?>>Sábados</option><option value="domingos" <?= $periodo_selecionado == 'domingos' ? 'selected' : '' ?>>Domingos</option></select></div>
                <div><label for="linha" class="block text-sm font-medium text-gray-700">Linha</label><select name="linha" id="linha" class="mt-1 w-full p-2 border border-gray-300 rounded-md"><option value="todas">Todas</option><?php foreach($linhas_disponiveis as $l): ?><option value="<?= htmlspecialchars($l['route']) ?>" <?= $l['route'] == $linha_selecionada ? 'selected' : '' ?>><?= htmlspecialchars($l['route']) ?></option><?php endforeach; ?></select></div>
                <button type="submit" class="bg-green-600 text-white font-bold py-2 px-4 rounded-md h-11 w-full hover:bg-green-700 transition-colors">Filtrar</button>
            </form>
        </div>
        
        <?php if ($total_registros > 0): ?>
            <div class="card p-4 mb-6 text-center text-gray-700">
                <strong>Exibindo Relatório:</strong> Período: <span class="font-semibold text-green-600"><?= $titulo_periodo ?></span> | Linha: <span class="font-semibold text-green-600"><?= $titulo_linha ?></span> | Datas: <span class="font-semibold text-green-600"><?= date('d/m/Y', strtotime($data_inicio)) ?> a <?= date('d/m/Y', strtotime($data_fim)) ?></span>
            </div>
            
            <div class="space-y-8">
                <section class="card p-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4 text-center">Análise Geral (004): Programado vs. Realizado</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                        <div class="flex items-center gap-4"><div class="w-1/2"><canvas id="chartDistancia"></canvas></div><div class="w-1/2 text-sm">
                            <h3 class="font-bold text-lg mb-2">Distância Total (KM)</h3>
                            <p class="flex justify-between"><span>Programado:</span> <span class="font-semibold"><?= number_format($kpis['total_distancia_p'] ?? 0, 2, ',', '.') ?></span></p>
                            <p class="flex justify-between"><span>Realizado:</span> <span class="font-semibold"><?= number_format($kpis['total_distancia_r'] ?? 0, 2, ',', '.') ?></span></p>
                            <p class="flex justify-between border-t mt-1 pt-1"><span>Desvio:</span> <span class="font-bold <?= (($kpis['total_distancia_r'] ?? 0) - ($kpis['total_distancia_p'] ?? 0)) >= 0 ? 'text-red-500' : 'text-green-500' ?>"><?= number_format(($kpis['total_distancia_r'] ?? 0) - ($kpis['total_distancia_p'] ?? 0), 2, ',', '.') ?></span></p>
                        </div></div>
                        <div class="flex items-center gap-4"><div class="w-1/2"><canvas id="chartTempo"></canvas></div><div class="w-1/2 text-sm">
                            <h3 class="font-bold text-lg mb-2">Tempo de Execução Total</h3>
                            <p class="flex justify-between"><span>Programado:</span> <span class="font-semibold"><?= formatar_segundos($kpis['total_tempo_p'] ?? 0) ?></span></p>
                            <p class="flex justify-between"><span>Realizado:</span> <span class="font-semibold"><?= formatar_segundos($kpis['total_tempo_r'] ?? 0) ?></span></p>
                            <p class="flex justify-between border-t mt-1 pt-1"><span>Desvio:</span> <span class="font-bold <?= (($kpis['total_tempo_r'] ?? 0) - ($kpis['total_tempo_p'] ?? 0)) >= 0 ? 'text-red-500' : 'text-green-500' ?>"><?= formatar_segundos(($kpis['total_tempo_r'] ?? 0) - ($kpis['total_tempo_p'] ?? 0)) ?></span></p>
                        </div></div>
                    </div>
                </section>

                <section class="card p-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Análise Detalhada de Tempos e Distâncias (004)</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 text-center">
                        <?php 
                            $metricas = [
                                'tempo_produtivo' => 'Tempo Produtivo', 'distancia_produtiva' => 'Distância Produtiva',
                                'tempo_recolha' => 'Tempo Recolha/Garagem', 'distancia_recolha' => 'Distância Recolha/Garagem',
                                'tempo_ocioso' => 'Tempo Viagem Ociosa', 'distancia_ociosa' => 'Distância Viagem Ociosa'
                            ];
                        ?>
                        <?php foreach($metricas as $key => $label): 
                            $sufixo_kpi_p = "total_{$key}_p_km";
                            $sufixo_kpi_r = "total_{$key}_r_km";
                            if (strpos($key, 'tempo') !== false) {
                                $sufixo_kpi_p = "total_{$key}_p_seg";
                                $sufixo_kpi_r = "total_{$key}_r_seg";
                            }
                            
                            $valor_p = $kpis[$sufixo_kpi_p] ?? 0;
                            $valor_r = $kpis[$sufixo_kpi_r] ?? 0;
                            $desvio = $valor_r - $valor_p;
                            $is_tempo = strpos($key, 'tempo') !== false;
                        ?>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-bold text-md mb-2"><?= $label ?></h3>
                            <div class="text-sm space-y-1">
                                <p class="flex justify-between"><span>Programado:</span> <span class="font-semibold"><?= $is_tempo ? formatar_segundos($valor_p) : number_format($valor_p, 2, ',', '.').' km' ?></span></p>
                                <p class="flex justify-between"><span>Realizado:</span> <span class="font-semibold"><?= $is_tempo ? formatar_segundos($valor_r) : number_format($valor_r, 2, ',', '.').' km' ?></span></p>
                                <p class="flex justify-between border-t mt-1 pt-1"><span>Desvio:</span> <span class="font-bold <?= $desvio >= 0 ? 'text-red-500' : 'text-green-500' ?>"><?= $is_tempo ? formatar_segundos($desvio) : number_format($desvio, 2, ',', '.').' km' ?></span></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <div class="bg-gray-50 p-4 rounded-lg md:col-span-2 lg:col-span-1">
                             <h3 class="font-bold text-md mb-2">Tempo Ocioso Total (R)</h3>
                             <p class="text-3xl font-bold text-gray-700 mt-4"><?= formatar_segundos($kpis['total_tempo_ocioso_total_r_seg'] ?? 0) ?></p>
                        </div>
                    </div>
                </section>

                <section class="card p-6"><h2 class="text-2xl font-semibold text-gray-800 mb-4">Desempenho por Linha (004)</h2>
                    <div class="scrolling-container"><table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase"><tr><th class="px-4 py-3">Linha</th><th class="px-4 py-3 text-center">Viagens</th><th class="px-4 py-3 text-center">Distância (P)</th><th class="px-4 py-3 text-center">Distância (R)</th><th class="px-4 py-3 text-center">Desvio Dist.</th><th class="px-4 py-3 text-center">Tempo (P)</th><th class="px-4 py-3 text-center">Tempo (R)</th><th class="px-4 py-3 text-center">Desvio Tempo</th></tr></thead>
                        <tbody><?php foreach($stats_rotas as $r): $desvio_dist = $r['dist_r'] - $r['dist_p']; $desvio_tempo = $r['tempo_r'] - $r['tempo_p']; ?><tr class="border-b hover:bg-gray-50"><td class="px-4 py-2 font-bold"><?= htmlspecialchars($r['route']) ?></td><td class="px-4 py-2 text-center"><?= $r['total_viagens'] ?></td><td class="px-4 py-2 text-center"><?= number_format($r['dist_p'], 2, ',', '.') ?> km</td><td class="px-4 py-2 text-center"><?= number_format($r['dist_r'], 2, ',', '.') ?> km</td><td class="px-4 py-2 text-center font-semibold <?= $desvio_dist >= 0 ? 'text-red-500' : 'text-green-500' ?>"><?= number_format($desvio_dist, 2, ',', '.') ?> km</td><td class="px-4 py-2 text-center"><?= formatar_segundos($r['tempo_p']) ?></td><td class="px-4 py-2 text-center"><?= formatar_segundos($r['tempo_r']) ?></td><td class="px-4 py-2 text-center font-semibold <?= $desvio_tempo >= 0 ? 'text-red-500' : 'text-green-500' ?>"><?= formatar_segundos($desvio_tempo) ?></td></tr><?php endforeach; ?></tbody>
                    </table></div>
                </section>

                <section class="card p-6"><h2 class="text-2xl font-semibold text-gray-800 mb-4">Desempenho por Motorista (004)</h2>
                    <div class="scrolling-container"><table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase"><tr><th class="px-4 py-3">Motorista</th><th class="px-4 py-3 text-center">Viagens</th><th class="px-4 py-3 text-center">Distância Total</th><th class="px-4 py-3 text-center">Tempo Total</th><th class="px-4 py-3 text-center">Desvio Total</th></tr></thead>
                        <tbody><?php foreach($stats_motoristas as $m): ?><tr class="border-b hover:bg-gray-50"><td class="px-4 py-2 font-bold"><?= htmlspecialchars($m['operator']) ?> (<?= $m['matricula'] ?>)</td><td class="px-4 py-2 text-center"><?= $m['total_viagens'] ?></td><td class="px-4 py-2 text-center"><?= number_format($m['dist_r'], 2, ',', '.') ?> km</td><td class="px-4 py-2 text-center"><?= formatar_segundos($m['tempo_r']) ?></td><td class="px-4 py-2 text-center font-semibold <?= $m['desvio_total'] >= 0 ? 'text-red-500' : 'text-green-500' ?>"><?= formatar_segundos($m['desvio_total']) ?></td></tr><?php endforeach; ?></tbody>
                    </table></div>
                </section>

                 <section class="card p-6"><h2 class="text-2xl font-semibold text-gray-800 mb-4">Desempenho por Veículo (004)</h2>
                    <div class="scrolling-container"><table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase"><tr><th class="px-4 py-3">Veículo</th><th class="px-4 py-3 text-center">Viagens</th><th class="px-4 py-3 text-center">Distância Percorrida</th><th class="px-4 py-3 text-center">Tempo em Execução</th><th class="px-4 py-3 text-center">Produtividade (km/h)</th></tr></thead>
                        <tbody><?php foreach($stats_veiculos as $v): $produtividade = ($v['tempo_r'] > 0) ? ($v['dist_r'] / ($v['tempo_r']/3600)) : 0; ?><tr class="border-b hover:bg-gray-50"><td class="px-4 py-2 font-bold"><?= $v['vehicle'] ?></td><td class="px-4 py-2 text-center"><?= $v['total_viagens'] ?></td><td class="px-4 py-2 text-center"><?= number_format($v['dist_r'], 2, ',', '.') ?> km</td><td class="px-4 py-2 text-center"><?= formatar_segundos($v['tempo_r']) ?></td><td class="px-4 py-2 text-center font-semibold"><?= number_format($produtividade, 2, ',', '.') ?></td></tr><?php endforeach; ?></tbody>
                    </table></div>
                </section>
            </div>
        <?php else: ?>
            <div class="card p-8 text-center"><h2 class="text-2xl font-semibold text-yellow-600">Nenhum dado encontrado</h2><p class="mt-2 text-gray-600">Não há registos na base de dados para o período e filtros selecionados.</p><p class="mt-4 text-sm text-gray-500">Por favor, ajuste os filtros ou <a href="index.php" class="text-blue-500 hover:underline">importe um novo arquivo</a>.</p></div>
        <?php endif; ?>
        
        <div class="text-center mt-8"><a href="index.php" class="bg-gray-500 text-white font-bold py-3 px-8 rounded-lg hover:bg-gray-600 transition-colors">Voltar para a Central de Controle</a></div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const dashboardData = <?php echo json_encode($dados_para_js); ?>;
        if (Object.keys(dashboardData).length > 0) {
            const criarGraficoPizza = (ctxId, labels, data) => {
                const ctx = document.getElementById(ctxId).getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: { labels: labels, datasets: [{ data: data, backgroundColor: ['#16a34a', '#15803d'], borderColor: '#fff', borderWidth: 2 }] },
                    options: { responsive: true, plugins: { legend: { display: false } } }
                });
            };
            criarGraficoPizza('chartDistancia', ['Programado', 'Realizado'], dashboardData.distancia);
            criarGraficoPizza('chartTempo', ['Programado', 'Realizado'], dashboardData.tempo);
        }
    });
    </script>
</body>
</html>