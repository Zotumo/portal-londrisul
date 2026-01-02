<?php
// =================================================================
//  Parceiro de Programação - Dashboard de Análise por Divisão (v3.2)
//  - Corrigida a função de data 'strftime' obsoleta para 'IntlDateFormatter'
//  - Adicionado comparativo de KM do Sistema vs. KM da Roleta
//  - Adicionada linha de totais na tabela de veículos
// =================================================================

// --- 1. CONFIGURAÇÕES E CONEXÃO ---
ini_set('display_errors', 1); error_reporting(E_ALL);
$conexao = new mysqli("localhost", "root", "", "relatorio");
if ($conexao->connect_error) { die("Falha na conexão: " . $conexao->connect_error); }
$conexao->set_charset("utf8mb4");

// --- 2. FUNÇÕES AUXILIARES ---
function formatar_segundos_divisao($totalSegundos) {
    if (!is_numeric($totalSegundos) || $totalSegundos == 0) return "0h 0m";
    $horas = floor($totalSegundos / 3600);
    $minutos = floor(($totalSegundos % 3600) / 60);
    return "{$horas}h {$minutos}m";
}

// --- 3. LÓGICA DE FILTROS ---
$intervalo_datas_db = $conexao->query("SELECT MIN(data_viagem) as min_date, MAX(data_viagem) as max_date FROM relatorios_divisao")->fetch_assoc();
$divisoes_disponiveis = $conexao->query("SELECT DISTINCT division FROM relatorios_divisao ORDER BY division ASC")->fetch_all(MYSQLI_ASSOC);

$data_inicio_padrao = $intervalo_datas_db['min_date'] ?? date('Y-m-d');
$data_fim_padrao = $intervalo_datas_db['max_date'] ?? date('Y-m-d');

$data_inicio = $_GET['data_inicio'] ?? $data_inicio_padrao;
$data_fim = $_GET['data_fim'] ?? $data_fim_padrao;
$divisao_selecionada = $_GET['divisao'] ?? 'todas';

$where_conditions = ["data_viagem BETWEEN '{$conexao->real_escape_string($data_inicio)}' AND '{$conexao->real_escape_string($data_fim)}'"];
if ($divisao_selecionada !== 'todas') $where_conditions[] = "division = '{$conexao->real_escape_string($divisao_selecionada)}'";
$where_clause = "WHERE " . implode(' AND ', $where_conditions);

$titulo_divisao = ($divisao_selecionada === 'todas') ? "Todas as Divisões" : "Divisão " . htmlspecialchars($divisao_selecionada);

// --- NOVA LÓGICA: VERIFICAR SE O FILTRO É UM MÊS COMPLETO ---
$mostrar_comparativo_roleta = false;
$mes_ano_filtro = '';
if (!empty($data_inicio)) {
    $primeiro_dia_mes = date('Y-m-01', strtotime($data_inicio));
    $ultimo_dia_mes = date('Y-m-t', strtotime($data_inicio));
    if ($data_inicio == $primeiro_dia_mes && $data_fim == $ultimo_dia_mes) {
        $mostrar_comparativo_roleta = true;
        $mes_ano_filtro = $primeiro_dia_mes;
    }
}

// --- 4. QUERIES DE ANÁLISE ---
$total_registros = $conexao->query("SELECT COUNT(*) as total FROM relatorios_divisao {$where_clause}")->fetch_assoc()['total'] ?? 0;
$dados_para_js = []; $stats_divisao = []; $stats_veiculos = []; $dados_roleta = [];

if ($total_registros > 0) {
    // Queries existentes ...
    $sql_kpis = "SELECT 
        SUM(manual_duracao_seg) as manual_t, SUM(manual_distancia_km) as manual_d,
        SUM(off_duracao_seg) as off_t, SUM(off_distancia_km) as off_d,
        SUM(scheduled_duracao_seg) as scheduled_t, SUM(scheduled_distancia_km) as scheduled_d,
        SUM(unknown_duracao_seg) as unknown_t, SUM(unknown_distancia_km) as unknown_d,
        SUM(total_duracao_seg) as total_t, SUM(total_distancia_km) as total_d
    FROM relatorios_divisao {$where_clause}";
    $kpis = $conexao->query($sql_kpis)->fetch_assoc();

    $sql_divisao = "SELECT division, COUNT(DISTINCT vehicle) as total_veiculos,
        SUM(total_duracao_seg) as total_t, SUM(total_distancia_km) as total_d,
        SUM(scheduled_duracao_seg) as scheduled_t, SUM(scheduled_distancia_km) as scheduled_d
    FROM relatorios_divisao {$where_clause} GROUP BY division ORDER BY division ASC";
    $stats_divisao = $conexao->query($sql_divisao)->fetch_all(MYSQLI_ASSOC);
    
    $sql_veiculos = "SELECT vehicle, division,
        SUM(total_duracao_seg) as total_t, SUM(total_distancia_km) as total_d
    FROM relatorios_divisao {$where_clause} GROUP BY vehicle, division ORDER BY division, vehicle ASC";
    $stats_veiculos = $conexao->query($sql_veiculos)->fetch_all(MYSQLI_ASSOC);

    // NOVA QUERY: Buscar dados da roleta se a condição for atendida
    if ($mostrar_comparativo_roleta) {
        $sql_roleta = "SELECT vehicle, km_total_roleta FROM relatorios_km_roleta WHERE mes_ano = '{$conexao->real_escape_string($mes_ano_filtro)}'";
        $result_roleta = $conexao->query($sql_roleta);
        while ($row = $result_roleta->fetch_assoc()) {
            $dados_roleta[$row['vehicle']] = $row['km_total_roleta'];
        }
    }

    // --- NOVA LÓGICA: CALCULAR TOTAIS PARA O RODAPÉ DA TABELA ---
    $total_duracao_geral = 0;
    $total_distancia_sistema_geral = 0;
    $total_distancia_roleta_geral = 0;

    foreach($stats_veiculos as $v) {
        $total_duracao_geral += $v['total_t'];
        $total_distancia_sistema_geral += $v['total_d'];
        if ($mostrar_comparativo_roleta) {
            $total_distancia_roleta_geral += $dados_roleta[$v['vehicle']] ?? 0;
        }
    }

    $dados_para_js = [
        'duracao' => [
            'Ocioso' => $kpis['manual_t'] ?? 0, 
            'Aproveitamento' => $kpis['off_t'] ?? 0,
            'Produtivo' => $kpis['scheduled_t'] ?? 0, 
            'Deslogado' => $kpis['unknown_t'] ?? 0
        ],
        'distancia' => [
            'Ocioso' => $kpis['manual_d'] ?? 0, 
            'Aproveitamento' => $kpis['off_d'] ?? 0,
            'Produtivo' => $kpis['scheduled_d'] ?? 0, 
            'Deslogado' => $kpis['unknown_d'] ?? 0
        ]
    ];
}
$conexao->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Análise por Divisão</title>
    <script src="tailwindcss-3.4.17.js"></script>
    <script src="chart.js"></script>
    <style>
        .card { background-color: white; border-radius: 0.75rem; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1), 0 1px 2px -1px rgba(0,0,0,0.1); }
        .scrolling-container { max-height: 400px; overflow-y: auto; }
        thead th { position: sticky; top: 0; z-index: 10; background-color: #f1f5f9; } /* Fundo do cabeçalho da tabela */
    </style>
</head>
<body class="bg-blue-50 font-sans">
    <main class="container mx-auto p-4 md:p-8">
        <header class="text-center mb-8">
            <h1 class="text-4xl font-bold text-blue-800">Dashboard de Análise por Divisão</h1>
            <p class="mt-2 text-lg text-blue-700">Resumo de trabalho da frota por garagem/divisão.</p>
        </header>

        <div class="card p-4 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div><label for="data_inicio" class="block text-sm font-medium text-gray-700">Data Início</label><input type="date" name="data_inicio" id="data_inicio" value="<?= htmlspecialchars($data_inicio) ?>" min="<?= htmlspecialchars($intervalo_datas_db['min_date'] ?? '') ?>" max="<?= htmlspecialchars($intervalo_datas_db['max_date'] ?? '') ?>" class="mt-1 w-full p-2 border border-gray-300 rounded-md"></div>
                <div><label for="data_fim" class="block text-sm font-medium text-gray-700">Data Fim</label><input type="date" name="data_fim" id="data_fim" value="<?= htmlspecialchars($data_fim) ?>" min="<?= htmlspecialchars($intervalo_datas_db['min_date'] ?? '') ?>" max="<?= htmlspecialchars($intervalo_datas_db['max_date'] ?? '') ?>" class="mt-1 w-full p-2 border border-gray-300 rounded-md"></div>
                <div><label for="divisao" class="block text-sm font-medium text-gray-700">Divisão</label><select name="divisao" id="divisao" class="mt-1 w-full p-2 border border-gray-300 rounded-md"><option value="todas">Todas</option><?php foreach($divisoes_disponiveis as $d): ?><option value="<?= htmlspecialchars($d['division']) ?>" <?= $d['division'] == $divisao_selecionada ? 'selected' : '' ?>><?= htmlspecialchars($d['division']) ?></option><?php endforeach; ?></select></div>
                <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-md h-11 w-full hover:bg-blue-700 transition-colors">Filtrar</button>
            </form>
        </div>
        
        <?php if ($total_registros > 0): ?>
            <div class="card p-4 mb-6 text-center text-gray-700">
                <strong>Exibindo Relatório:</strong> Divisão: <span class="font-semibold text-blue-600"><?= $titulo_divisao ?></span> | Datas: <span class="font-semibold text-blue-600"><?= date('d/m/Y', strtotime($data_inicio)) ?> a <?= date('d/m/Y', strtotime($data_fim)) ?></span>
                <?php if ($mostrar_comparativo_roleta): 
                    // CORREÇÃO: Usar IntlDateFormatter para formatação de data localizada e moderna
                    $formatter = new IntlDateFormatter('pt_BR', IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, "MMMM 'de' yyyy");
                    $mes_ano_formatado = ucfirst($formatter->format(new DateTime($mes_ano_filtro)));
                ?>
                    <span class="block mt-2 text-sm bg-indigo-100 text-indigo-700 p-2 rounded-md"><strong>Modo Comparativo Ativado:</strong> Exibindo dados da Ficha de Roleta para o mês de <?= $mes_ano_formatado ?>.</span>
                <?php endif; ?>
            </div>
            
            <div class="space-y-8">
                <section class="card p-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4 text-center">Distribuição por Modo de Trabalho</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                        <div class="flex items-center gap-4">
                            <div class="w-1/2"><canvas id="chartDuracao"></canvas></div>
                            <div class="w-1/2 text-sm space-y-1">
                                <h3 class="font-bold text-lg mb-2">Duração Total</h3>
                                <?php $total_duracao = $kpis['total_t'] ?? 0; ?>
                                <?php foreach($dados_para_js['duracao'] as $label => $valor): ?>
                                <p class="flex justify-between"><span><?= $label ?>:</span> <span class="font-semibold"><?= formatar_segundos_divisao($valor) ?> (<?= $total_duracao > 0 ? round(($valor / $total_duracao) * 100, 1) : 0 ?>%)</span></p>
                                <?php endforeach; ?>
                                <p class="flex justify-between border-t mt-1 pt-1"><strong>Total:</strong> <strong class="font-semibold"><?= formatar_segundos_divisao($total_duracao) ?></strong></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-1/2"><canvas id="chartDistancia"></canvas></div>
                            <div class="w-1/2 text-sm space-y-1">
                                <h3 class="font-bold text-lg mb-2">Distância Total</h3>
                                <?php $total_distancia = $kpis['total_d'] ?? 0; ?>
                                <?php foreach($dados_para_js['distancia'] as $label => $valor): ?>
                                <p class="flex justify-between"><span><?= $label ?>:</span> <span class="font-semibold"><?= number_format($valor, 2, ',', '.') ?> km (<?= $total_distancia > 0 ? round(($valor / $total_distancia) * 100, 1) : 0 ?>%)</span></p>
                                <?php endforeach; ?>
                                <p class="flex justify-between border-t mt-1 pt-1"><strong>Total:</strong> <strong class="font-semibold"><?= number_format($total_distancia, 2, ',', '.') ?> km</strong></p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card p-6"><h2 class="text-2xl font-semibold text-gray-800 mb-4">Resumo por Divisão</h2>
                    <div class="scrolling-container"><table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase"><tr><th class="px-4 py-3">Divisão</th><th class="px-4 py-3 text-center">Veículos</th><th class="px-4 py-3 text-center">Duração Total</th><th class="px-4 py-3 text-center">Distância Total</th><th class="px-4 py-3 text-center">Duração Produtiva</th><th class="px-4 py-3 text-center">Distância Produtiva</th></tr></thead>
                        <tbody><?php foreach($stats_divisao as $d): ?><tr class="border-b hover:bg-gray-50"><td class="px-4 py-2 font-bold"><?= htmlspecialchars($d['division']) ?></td><td class="px-4 py-2 text-center"><?= $d['total_veiculos'] ?></td><td class="px-4 py-2 text-center"><?= formatar_segundos_divisao($d['total_t']) ?></td><td class="px-4 py-2 text-center"><?= number_format($d['total_d'], 2, ',', '.') ?> km</td><td class="px-4 py-2 text-center"><?= formatar_segundos_divisao($d['scheduled_t']) ?></td><td class="px-4 py-2 text-center"><?= number_format($d['scheduled_d'], 2, ',', '.') ?> km</td></tr><?php endforeach; ?></tbody>
                    </table></div>
                </section>

                 <section class="card p-6"><h2 class="text-2xl font-semibold text-gray-800 mb-4">Detalhes por Veículo</h2>
                    <div class="scrolling-container">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-700 uppercase">
                                <tr>
                                    <th class="px-4 py-3">Veículo</th>
                                    <th class="px-4 py-3">Divisão</th>
                                    <th class="px-4 py-3 text-center">Duração Total</th>
                                    <th class="px-4 py-3 text-center">Distância Sistema (km)</th>
                                    <?php if ($mostrar_comparativo_roleta): // Adiciona colunas condicionalmente ?>
                                    <th class="px-4 py-3 text-center bg-indigo-100">Distância Roleta (km)</th>
                                    <th class="px-4 py-3 text-center bg-indigo-100">Diferença (km)</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($stats_veiculos as $v): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-2 font-bold"><?= $v['vehicle'] ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($v['division']) ?></td>
                                    <td class="px-4 py-2 text-center"><?= formatar_segundos_divisao($v['total_t']) ?></td>
                                    <td class="px-4 py-2 text-center font-semibold"><?= number_format($v['total_d'], 2, ',', '.') ?></td>
                                    <?php if ($mostrar_comparativo_roleta): 
                                        $km_roleta = $dados_roleta[$v['vehicle']] ?? 0;
                                        $diferenca = $v['total_d'] - $km_roleta;
                                        $cor_diferenca = (abs($diferenca) > 100) ? 'text-red-500' : 'text-green-600';
                                    ?>
                                    <td class="px-4 py-2 text-center font-semibold bg-gray-50"><?= number_format($km_roleta, 2, ',', '.') ?></td>
                                    <td class="px-4 py-2 text-center font-bold <?= $cor_diferenca ?> bg-gray-50"><?= number_format($diferenca, 2, ',', '.') ?></td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <!-- NOVO RODAPÉ COM TOTAIS -->
                            <tfoot class="bg-slate-200 font-bold text-sm">
                                <tr>
                                    <td class="px-4 py-3" colspan="2">TOTAIS</td>
                                    <td class="px-4 py-3 text-center"><?= formatar_segundos_divisao($total_duracao_geral) ?></td>
                                    <td class="px-4 py-3 text-center"><?= number_format($total_distancia_sistema_geral, 2, ',', '.') ?></td>
                                    <?php if ($mostrar_comparativo_roleta):
                                        $diferenca_total = $total_distancia_sistema_geral - $total_distancia_roleta_geral;
                                        $percentual_diferenca = ($total_distancia_roleta_geral > 0) ? ($diferenca_total / $total_distancia_roleta_geral) * 100 : 0;
                                        // Define a cor baseada num limiar de percentagem, por exemplo 5%
                                        $cor_diferenca_total = (abs($percentual_diferenca) > 5) ? 'text-red-500' : 'text-green-600';
                                    ?>
                                    <td class="px-4 py-3 text-center"><?= number_format($total_distancia_roleta_geral, 2, ',', '.') ?></td>
                                    <td class="px-4 py-3 text-center <?= $cor_diferenca_total ?>">
                                        <?= number_format($diferenca_total, 2, ',', '.') ?>
                                        <span class="block text-xs font-normal">(<?= number_format($percentual_diferenca, 2, ',', '.') ?>%)</span>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
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
            const criarGraficoPizza = (ctxId, chartData) => {
                const ctx = document.getElementById(ctxId).getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: { 
                        labels: Object.keys(chartData), 
                        datasets: [{ data: Object.values(chartData), backgroundColor: ['#FF6384', '#FFCD56', '#4BC0C0', '#36A2EB'], borderColor: '#fff', borderWidth: 2 }] 
                    },
                    options: { responsive: true, plugins: { legend: { display: false } } }
                });
            };
            criarGraficoPizza('chartDuracao', dashboardData.duracao);
            criarGraficoPizza('chartDistancia', dashboardData.distancia);
        }
    });
    </script>
</body>
</html>
