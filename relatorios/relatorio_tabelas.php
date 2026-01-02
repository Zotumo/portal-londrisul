<?php
// =================================================================
//  Relatório Operacional de Frota - v12.0 (VALIDADO VIA EXCEL/CSV)
// =================================================================
require_once 'config_km.php'; 
$conexao = new mysqli("localhost", "root", "", "relatorio");
$conexao->set_charset("utf8mb4");

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d');
$data_fim    = $_GET['data_fim']    ?? date('Y-m-d');
$linha_sel   = $_GET['linha']       ?? 'todos';

// Formatação HH:MM (exatamente como no seu Excel: 1808:48)
function formatar_excel_hms($segundos) {
    $h = floor($segundos / 3600);
    $m = floor(($segundos % 3600) / 60);
    return sprintf("%02d:%02d", $h, $m);
}

// 1. KPI WORKIDs ÚNICOS (Meta: 460 para dia 06 conforme seu Excel)
$res_w = $conexao->query("SELECT COUNT(DISTINCT DUTY_COMPANYCODE) as total FROM relatorios_servicos WHERE data_inicio_vigencia <= '$data_fim' AND data_fim_vigencia >= '$data_inicio'")->fetch_assoc();
$total_workids = $res_w['total'] ?? 0;

// 2. KPI TOTAL HORAS (Lógica Excel: Soma de (Max Fim - Min Inicio) por WorkID -> Meta: 1808:48)
$sql_h = "SELECT SUM(TIMESTAMPDIFF(SECOND, min_s, max_e)) as s FROM (SELECT MIN(START_TIME) as min_s, MAX(END_TIME) as max_e FROM relatorios_servicos WHERE data_inicio_vigencia <= '$data_fim' AND data_fim_vigencia >= '$data_inicio' GROUP BY DUTY_COMPANYCODE) t";
$total_seg_work = $conexao->query($sql_h)->fetch_assoc()['s'] ?? 0;

// 3. KPI VIAGENS E KM
$where_v = "WHERE data_viagem BETWEEN '$data_inicio' AND '$data_fim'";
if($linha_sel != 'todos') $where_v .= " AND ROUTE_ID = '$linha_sel'";
$kpis_v = $conexao->query("SELECT COUNT(CASE WHEN TRIP_ID != 0 THEN 1 END) as qp, SUM(DISTANCE/1000) as kt, SUM(CASE WHEN TRIP_ID != 0 THEN DISTANCE/1000 ELSE 0 END) as kp, SUM(CASE WHEN TRIP_ID = 0 THEN DISTANCE/1000 ELSE 0 END) as ko FROM relatorios_viagens $where_v")->fetch_assoc();

$linhas_db = $conexao->query("SELECT DISTINCT ROUTE_ID FROM relatorios_viagens WHERE ROUTE_ID != '' ORDER BY ROUTE_ID ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de WorkIDs e KM de Linhas</title>
    <script src="tailwindcss-3.4.17.js"></script>
    <script src="chart.js"></script>
    <script src="chartjs-plugin-datalabels@2.0.0.js"></script>
    <script>
        function aplicarFiltro() {
            window.location.href = `?data_inicio=${document.getElementById('f_ini').value}&data_fim=${document.getElementById('f_fim').value}&linha=${document.getElementById('f_linha').value}`;
        }
    </script>
    <style>
        body { background-color: #f1f5f9; font-family: sans-serif; }
        .card { background: white; border-radius: 12px; border-top: 4px solid #0e7490; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .table-custom th { font-size: 1.0rem; background: #f8fafc; padding: 6px; border: 1px solid #e2e8f0; text-transform:  ; }
        .table-custom td { font-size: 0.75rem; padding: 6px; border: 1px solid #e2e8f0; text-align: center; }
        .scroll-div { max-height: 400px; overflow-y: auto; position: relative; }
        thead th { position: sticky; top: 0; z-index: 10; }
    </style>
</head>
<body class="p-6">
    <div class="max-w-7xl mx-auto space-y-8">
        
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-black text-slate-800   tracking-tight">Relatório de WorkIDs e KM de Linhas</h1>
            <div class="bg-white p-4 rounded-xl shadow-sm border flex gap-4 items-end">
                <input type="date" id="f_ini" value="<?= $data_inicio ?>" class="text-lg border rounded p-2">
                <input type="date" id="f_fim" value="<?= $data_fim ?>" class="text-lg border rounded p-2">
                <select id="f_linha" class="text-lg border rounded p-3">
                    <option value="todos">Todas as Linhas</option>
                    <?php foreach($linhas_db as $ln) echo "<option value='{$ln['ROUTE_ID']}' ".($linha_sel==$ln['ROUTE_ID']?'selected':'').">{$ln['ROUTE_ID']}</option>"; ?>
                </select>
                <button onclick="aplicarFiltro()" class="bg-cyan-600 hover:bg-cyan-700 text-white text-lg font-bold px-6 py-2 rounded-lg">FILTRAR</button>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="card p-5"><span class="text-xl font-bold text-slate-400  ">Total de WorkIDs</span><div class="text-3xl font-black text-cyan-700 mt-1"><?= number_format($total_workids,0,',','.') ?></div></div>
            <div class="card p-5"><span class="text-xl font-bold text-slate-400  ">Viagens Produtivas</span><div class="text-3xl font-black text-cyan-700 mt-1"><?= number_format($kpis_v['qp'],0,',','.') ?></div></div>
            <div class="card p-5">
                <span class="text-xl font-bold text-slate-400  ">Total KM</span>
                <div class="text-3xl font-black text-cyan-700 mt-1"><?= number_format($kpis_v['kt'],1,',','.') ?> km</div>
                <div class="text-xl mt-2 flex gap-2 font-bold">
                    <span class="text-green-600">Produtivo: <?= number_format($kpis_v['kp'],1) ?> km</span>
                    <span class="text-orange-500">Ocioso: <?= number_format($kpis_v['ko'],1) ?> km</span>
                </div>
            </div>
            <div class="card p-5"><span class="text-xl font-bold text-slate-400  ">Total Horas WorkIDs</span><div class="text-3xl font-black text-cyan-700 mt-1"><?= formatar_excel_hms($total_seg_work) ?></div></div>
        </div>

        <?php if($linha_sel == 'todos'): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-5 rounded-xl border shadow-sm">
                <h3 class="text-xl font-black text-slate-700 mb-4   border-b pb-1">Top 5 Linhas por KM</h3>
                <?php $q=$conexao->query("SELECT ROUTE_ID, SUM(DISTANCE/1000) as v FROM relatorios_viagens WHERE TRIP_ID != 0 AND ROUTE_ID != '' AND data_viagem BETWEEN '$data_inicio' AND '$data_fim' GROUP BY ROUTE_ID ORDER BY v DESC LIMIT 5");
                while($r=$q->fetch_assoc()) echo "<div class='flex justify-between text-sm py-1 border-b border-slate-50 last:border-0'><span>{$r['ROUTE_ID']}</span><span class='font-bold text-cyan-600'>".number_format($r['v'],1)." km</span></div>"; ?>
            </div>
            <div class="bg-white p-5 rounded-xl border shadow-sm">
                <h3 class="text-xl font-black text-slate-700 mb-4   border-b pb-1">Top 5 Linhas por Horas</h3>
                <?php $q=$conexao->query("SELECT ROUTE_ID, SUM(seg) as s FROM (SELECT ROUTE_ID, TIMESTAMPDIFF(SECOND, MIN(START_TIME), MAX(END_TIME)) as seg FROM relatorios_viagens WHERE ROUTE_ID != '' AND data_viagem BETWEEN '$data_inicio' AND '$data_fim' GROUP BY ROUTE_ID, BLOCK_NUMBER) t GROUP BY ROUTE_ID ORDER BY s DESC LIMIT 5");
                while($r=$q->fetch_assoc()) echo "<div class='flex justify-between text-sm py-1 border-b border-slate-50 last:border-0'><span>{$r['ROUTE_ID']}</span><span class='font-bold text-cyan-600'>".floor($r['s']/3600)."h</span></div>"; ?>
            </div>
            <div class="bg-white p-5 rounded-xl border shadow-sm">
                <h3 class="text-xl font-black text-slate-700 mb-4   border-b pb-1">Top 5 Linhas por Nº de Serviços</h3>
                <?php $q=$conexao->query("SELECT v.ROUTE_ID, COUNT(DISTINCT s.DUTY_COMPANYCODE) as q FROM relatorios_viagens v JOIN relatorios_servicos s ON v.BLOCK_NUMBER = s.REFERREDVB_COMPANYCODE WHERE v.ROUTE_ID != '' AND v.data_viagem BETWEEN '$data_inicio' AND '$data_fim' GROUP BY v.ROUTE_ID ORDER BY q DESC LIMIT 5");
                while($r=$q->fetch_assoc()) echo "<div class='flex justify-between text-sm py-1 border-b border-slate-50 last:border-0'><span>{$r['ROUTE_ID']}</span><span class='font-bold text-cyan-600'>{$r['q']}</span></div>"; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-slate-700 text-xl">Serviços Ativos (WorkIDs) (a cada 10 min)</h3>
                <select onchange="carregarGrafico('ativos', this.value)" class="text-xl border rounded p-1">
                    <?php for($i=0;$i<24;$i++) echo "<option value='".sprintf("%02d",$i)."' ".($i==7?'selected':'').">".sprintf("%02d",$i).":00</option>"; ?>
                </select>
            </div>
            <div class="h-80"><canvas id="chartAtivos"></canvas></div>
        </div>

        <div class="card p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-slate-700 text-xl">Viagens Produtivas Iniciadas (a cada 10 min)</h3>
                <select onchange="carregarGrafico('viagens', this.value)" class="text-xl border rounded p-1">
                    <?php for($i=0;$i<24;$i++) echo "<option value='".sprintf("%02d",$i)."' ".($i==7?'selected':'').">".sprintf("%02d",$i).":00</option>"; ?>
                </select>
            </div>
            <div class="h-80"><canvas id="chartViagens"></canvas></div>
        </div>

        <div class="card p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-slate-700 text-xl">Fluxo em Terminais e Garagens (Por Hora)</h3>
                <div class="flex bg-slate-100 p-1 rounded-lg gap-1">
                    <button onclick="carregarFluxo('inicio')" id="btn-ini" class="font-bold px-4 py-1.5 rounded bg-white shadow text-cyan-700 text-xs">INÍCIO</button>
                    <button onclick="carregarFluxo('fim')" id="btn-fim" class="font-bold px-4 py-1.5 rounded text-slate-500 text-xs">FIM</button>
                </div>
            </div>
            <div class="overflow-x-auto"><table class="w-full table-custom"><thead><tr><th class="text-left w-48">Local</th><?php for($h=0;$h<24;$h++) echo "<th>{$h}h</th>"; ?><th>Total</th></tr></thead><tbody id="corpoFluxo"></tbody></table></div>
        </div>

        <div class="card p-6">
            <h3 class="font-bold text-slate-700 mb-6 text-xl">Análise por Sentido</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php foreach(['Ida (0)'=>0, 'Volta (1)'=>1] as $label => $val): 
                    $d = $conexao->query("SELECT COUNT(*) as q, SUM(DISTANCE/1000) as k FROM relatorios_viagens WHERE DIRECTION_NUM=$val AND TRIP_ID!=0 AND data_viagem BETWEEN '$data_inicio' AND '$data_fim' " . ($linha_sel!='todos'?"AND ROUTE_ID='$linha_sel'":""))->fetch_assoc();
                ?>
                <div class="bg-slate-50 p-6 rounded-xl border">
                    <h4 class="text-center font-bold text-cyan-800 mb-4"><?= $label ?></h4>
                    <div class="flex justify-between border-b py-2 text-sm"><span>Viagens Produtivas:</span><b><?= $d['q'] ?? 0 ?></b></div>
                    <div class="flex justify-between py-2 text-sm"><span>KM Total:</span><b><?= number_format($d['k'] ?? 0, 1, ',', '.') ?> km</b></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card p-6">
            <h3 class="font-bold text-slate-700 mb-4 text-xl">Análise por Linha</h3>
            <div class="scroll-div border rounded-lg">
                <table class="w-full table-custom border-collapse">
                    <thead>
                        <tr class="bg-slate-50">
                            <th>Linha</th>
                            <th>Nº Viagens</th>
                            <th>KM Produtiva</th>
                            <th>Vel. Média (Prod)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $q_linha = $conexao->query("SELECT ROUTE_ID, COUNT(*) as q, SUM(CASE WHEN TRIP_ID!=0 THEN DISTANCE/1000 ELSE 0 END) as kp, SUM(CASE WHEN TRIP_ID=0 THEN DISTANCE/1000 ELSE 0 END) as ko, (SUM(CASE WHEN TRIP_ID!=0 THEN DISTANCE/1000 ELSE 0 END) / (SUM(CASE WHEN TRIP_ID!=0 THEN TIMESTAMPDIFF(SECOND, START_TIME, END_TIME) ELSE 0 END) / 3600)) as vel FROM relatorios_viagens WHERE ROUTE_ID != '' AND data_viagem BETWEEN '$data_inicio' AND '$data_fim' GROUP BY ROUTE_ID ORDER BY kp DESC");
                        while($r = $q_linha->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="text-left font-bold text-cyan-800"><?= $r['ROUTE_ID'] ?></td>
                            <td class="font-bold"><?= $r['q'] ?></td>
                            <td class="text-green-600 font-bold"><?= number_format($r['kp'], 1, ',', '.') ?> km</td>
                            <td class="font-bold"><?= number_format($r['vel'], 1, ',', '.') ?> km/h</td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        Chart.register(ChartDataLabels);
        let chartAtivos, chartViagens;

        async function carregarGrafico(tipo, hora) {
            const res = await fetch(`ajax_graficos_tabelas.php?tipo_grafico=${tipo}&hora_selecionada=${hora}&data_inicio=<?= $data_inicio ?>&data_fim=<?= $data_fim ?>`);
            const data = await res.json();
            const canvas = document.getElementById(tipo === 'ativos' ? 'chartAtivos' : 'chartViagens').getContext('2d');
            
            if(tipo==='ativos' && chartAtivos) chartAtivos.destroy();
            if(tipo==='viagens' && chartViagens) chartViagens.destroy();

            const chart = new Chart(canvas, {
                type: 'bar',
                data: { labels: data.labels, datasets: [{ data: data.data, backgroundColor: tipo==='ativos' ? '#0891b2' : '#10b981', borderRadius: 4 }] },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false }, 
                        datalabels: { anchor: 'end', align: 'top', color: '#475569', font: { weight: 'bold', size: 10 }, formatter: v => v > 0 ? v : '' } 
                    },
                    scales: { y: { beginAtZero: true, max: tipo === 'ativos' ? 140 : 60 } }
                }
            });
            if(tipo==='ativos') chartAtivos = chart; else chartViagens = chart;
        }

        async function carregarFluxo(modo) {
            document.getElementById('btn-ini').className = modo === 'inicio' ? "text-[1rem] font-bold px-4 py-1.5 rounded bg-white shadow text-cyan-700" : "text-xs font-bold px-4 py-1.5 rounded text-slate-500 transition";
            document.getElementById('btn-fim').className = modo === 'fim' ? "text-[1rem] font-bold px-4 py-1.5 rounded bg-white shadow text-cyan-700" : "text-xs font-bold px-4 py-1.5 rounded text-slate-500 transition";

            const res = await fetch(`ajax_graficos_tabelas.php?tipo_grafico=locais_tabela&modo_local=${modo}&data_inicio=<?= $data_inicio ?>&data_fim=<?= $data_fim ?>`);
            const dados = await res.json();
            let html = '';
            for(let local in dados) {
                const isGaragem = local.toLowerCase().includes('garagem') || local.startsWith('G.');
                const cor = isGaragem ? 'rgba(8, 145, 178,' : 'rgba(34, 197, 94,';
                html += `<tr><td class="text-left font-bold ${isGaragem?'text-cyan-700':'text-green-700'} bg-slate-50">${local}</td>`;
                for(let h=0; h<24; h++) {
                    let v = dados[local][h] || 0;
                    html += `<td style="background:${cor} ${v/40})">${v || ''}</td>`;
                }
                html += `<td class="font-bold bg-slate-100 text-slate-700">${dados[local].total}</td></tr>`;
            }
            document.getElementById('corpoFluxo').innerHTML = html;
        }

        document.addEventListener('DOMContentLoaded', () => {
            carregarGrafico('ativos', '07');
            carregarGrafico('viagens', '07');
            carregarFluxo('inicio');
        });
    </script>
</body>
</html>