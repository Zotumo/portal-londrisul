<?php
// =================================================================
//  Relatório de Bilhetagem (Frontend v3.2)
//  - Tabela Diária com Dropdown em Linhas (Comparativo)
//  - Coluna de Receita no lugar de Outros
//  - KPIs com breakdown financeiro e operacional
// =================================================================

require_once 'config_bilhetagem.php';

// Busca linhas para o filtro
$linhas = $conexao->query("SELECT DISTINCT linha FROM relatorios_bilhetagem ORDER BY linha ASC")->fetch_all(MYSQLI_ASSOC);

// Passa a configuração de custos para o JS
$custos_json = json_encode($CUSTO_PASSAGEM);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Bilhetagem e Receita</title>
    <script src="tailwindcss-3.4.17.js"></script>
    <script src="chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
        .card { background: white; border-radius: 10px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        
        /* Cores de Fundo de Comparação */
        .bg-comp-atual { background-color: #dcfce7; }
        .bg-comp-mes { background-color: #fef9c3; }
        .bg-comp-ano { background-color: #fee2e2; }

        /* Cores de Texto */
        .text-up { color: #16a34a; font-weight: 600; }
        .text-down { color: #dc2626; font-weight: 600; }
        
        /* Tabela Detalhes */
        .col-tipo { background-color: #f8fafc; }
        .expanded-row td { background-color: #f8fafc; border-top: 1px dashed #cbd5e1; font-size: 0.75rem; color: #64748b; }
        .expanded-row.mes td { background-color: #fffbeb; }
        .expanded-row.ano td { background-color: #fef2f2; }
        
        /* Print */
        @media print {
            @page { size: landscape; margin: 5mm; }
            body { background: white; -webkit-print-color-adjust: exact; }
            #filtros, #header-actions, .no-print, .toggle-btn { display: none !important; }
            .card { box-shadow: none; border: 1px solid #ddd; break-inside: avoid; margin-bottom: 20px; }
            .hidden-print { display: block !important; }
            .hidden-print-row { display: none !important; } /* Esconde linhas expandidas na impressão se quiser */
            canvas { max-height: 250px !important; }
            th { background-color: #f1f5f9 !important; color: #000 !important; }
        }
    </style>
</head>
<body class="p-4 md:p-6 text-slate-800">

    <header class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Relatório de Bilhetagem & Receita</h1>
            <p class="text-slate-500 text-sm">Análise detalhada de demanda, comparação histórica e financeira.</p>
        </div>
        <div id="header-actions" class="flex gap-2">
            <button onclick="window.print()" class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg shadow-sm flex items-center gap-2 text-sm font-semibold transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Imprimir / PDF
            </button>
            <button onclick="exportarCSVCompleto()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow-sm flex items-center gap-2 text-sm font-semibold transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Exportar CSV
            </button>
        </div>
    </header>

    <!-- Filtros -->
    <div id="filtros" class="card p-4 mb-6 border-l-4 border-blue-500">
        <form id="filterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Data Início</label>
                <input type="date" id="data_inicio" class="w-full border-slate-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" value="<?= date('Y-m-01') ?>">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Data Fim</label>
                <input type="date" id="data_fim" class="w-full border-slate-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" value="<?= date('Y-m-d') ?>">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Linha</label>
                <select id="linha" class="w-full border-slate-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="todas">Todas as Linhas</option>
                    <?php foreach($linhas as $l): ?>
                        <option value="<?= htmlspecialchars($l['linha']) ?>"><?= htmlspecialchars($l['linha']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Dia da Semana</label>
                <select id="dia_semana" class="w-full border-slate-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="todos">Todos</option>
                    <option value="1">Segunda-feira</option>
                    <option value="2">Terça-feira</option>
                    <option value="3">Quarta-feira</option>
                    <option value="4">Quinta-feira</option>
                    <option value="5">Sexta-feira</option>
                    <option value="6">Sábado</option>
                    <option value="7">Domingo</option>
                </select>
            </div>
            <button type="button" onclick="carregarDados()" class="bg-blue-600 hover:bg-blue-700 text-white w-full py-2 rounded-md shadow-md font-bold transition-transform active:scale-95 text-sm h-[38px]">
                Filtrar Dados
            </button>
        </form>
    </div>

    <!-- KPIs Principais -->
    <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-2 gap-6 mb-6">
        
        <!-- Card 1: Total e Receita -->
        <div class="card p-5 border-t-4 border-blue-500 flex flex-col justify-between">
            <div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total do Período</h3>
                <p id="kpi-total" class="text-3xl font-extrabold text-slate-800 mt-1">...</p>
                <p id="kpi-receita" class="text-sm font-semibold text-green-600 mt-1">...</p>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100">
                <h4 class="text-xs font-bold text-slate-400 mb-2">Por Dia da Semana (Passageiros | Receita)</h4>
                <div id="kpi-breakdown-days" class="space-y-1 text-xs text-slate-600">
                    <!-- Preenchido via JS -->
                </div>
            </div>
        </div>

        <!-- Card 2: Médias e Pico -->
        <div class="card p-5 border-t-4 border-indigo-500 flex flex-col">
            <div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Média Diária</h3>
                <p id="kpi-media" class="text-3xl font-extrabold text-slate-800 mt-1">...</p>
            </div>
            <div class="mt-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Dia de Pico</h3>
                <div class="flex items-baseline gap-2 mt-1">
                    <p id="kpi-pico-val" class="text-xl font-bold text-slate-800">...</p>
                    <span id="kpi-pico-dia" class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 text-xs font-bold">...</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Comparativo Mês Anterior -->
        <div class="card p-5 border-t-4 border-amber-400 flex flex-col">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Vs. Mês Anterior</h3>
            <div id="comp-mes-macro">...</div>
            <div id="comp-mes-days" class="mt-4 pt-4 border-t border-slate-100 hidden">
                <!-- Grid preenchido via JS -->
            </div>
        </div>

        <!-- Card 4: Comparativo Ano Anterior -->
        <div class="card p-5 border-t-4 border-red-500 flex flex-col">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Vs. Ano Anterior</h3>
            <div id="comp-ano-macro">...</div>
            <div id="comp-ano-days" class="mt-4 pt-4 border-t border-slate-100 hidden">
                <!-- Grid preenchido via JS -->
            </div>
        </div>
    </div>

    <!-- Seção Evolução (Gráfico + Tabela) -->
    <div class="card p-5 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-slate-700">Evolução Diária da Demanda (Comparativo)</h3>
            <button onclick="toggleTabelaEvolucao()" class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-600 px-3 py-1 rounded text-xs font-semibold toggle-btn">
                Ver Tabela
            </button>
        </div>
        <div class="relative h-72 w-full mb-4">
            <canvas id="chartEvolucao"></canvas>
        </div>
        <!-- Tabela Oculta -->
        <div id="tabela-evolucao-container" class="hidden overflow-x-auto border-t border-slate-100 pt-4">
            <table class="w-full text-sm text-center" id="tabela-evolucao">
                <thead>
                    <tr class="bg-slate-100 text-slate-600 uppercase text-xs">
                        <th class="p-2 text-left">Data (Ref)</th>
                        <th class="p-2 bg-green-100 text-green-900">Atual</th>
                        <th class="p-2 bg-yellow-100 text-yellow-900">Mês Ant.</th>
                        <th class="p-2 bg-red-100 text-red-900">Ano Ant.</th>
                        <th class="p-2">Dif. Mês (Abs | %)</th>
                        <th class="p-2">Dif. Ano (Abs | %)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100"></tbody>
                <tfoot class="bg-slate-50 font-bold"></tfoot>
            </table>
        </div>
    </div>

    <!-- Seção Distribuição por Tipo (Tabela) -->
    <div class="card p-5 mb-6">
        <h3 class="text-lg font-bold text-slate-700 mb-4 border-b pb-2">Distribuição por Tipo de Cartão (Comparativo)</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right" id="tabela-distribuicao">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase text-xs">
                        <th class="p-2 text-left">Tipo de Cartão</th>
                        <th class="p-2 bg-green-50 text-green-800">Atual</th>
                        <th class="p-2 bg-yellow-50 text-yellow-800">Mês Passado</th>
                        <th class="p-2">Dif. (Abs / %)</th>
                        <th class="p-2 bg-red-50 text-red-800">Ano Passado</th>
                        <th class="p-2">Dif. (Abs / %)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700"></tbody>
            </table>
        </div>
    </div>

    <!-- Seção Detalhada (Diária + Dropdown) -->
    <div class="card p-5 overflow-hidden">
        <h3 class="text-lg font-bold text-slate-700 mb-4">Detalhamento Diário e Variação por Tipo</h3>
        <p class="text-xs text-slate-400 mb-4">* Clique na linha ou na seta para ver as linhas de comparação (Mês/Ano Passado).</p>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="tabela-detalhes">
                <thead>
                    <tr class="bg-slate-50 text-xs text-slate-500 uppercase tracking-wide">
                        <th class="p-3 w-8"></th>
                        <th class="p-3">Data</th>
                        <th class="p-3">Dia</th>
                        <th class="p-3 text-right bg-slate-100 text-slate-700">Total</th>
                        <th class="p-3 text-center">Var. (D-1/S-1)</th>
                        <th class="p-3 text-right col-tipo">Comum</th>
                        <th class="p-3 text-right col-tipo">VT</th>
                        <th class="p-3 text-right col-tipo">Estudante</th>
                        <th class="p-3 text-right col-tipo">Gratuito</th>
                        <th class="p-3 text-right col-tipo">Pagantes</th>
                        <th class="p-3 text-right col-tipo">Integ.</th>
                        <th class="p-3 text-right col-tipo">EMV</th>
                        <th class="p-3 text-right col-tipo font-bold text-green-700">Receita</th>
                    </tr>
                </thead>
                <tbody class="text-slate-600 divide-y divide-slate-100 text-xs md:text-sm"></tbody>
            </table>
        </div>
    </div>

    <script>
        // --- 1. CONFIGURAÇÕES ---
        Chart.defaults.font.family = "'Inter', sans-serif";
        let chartEvolucao = null;
        let globalData = null; 

        // Tabela de custos injetada pelo PHP
        const CUSTO_PASSAGEM = <?php echo $custos_json; ?>;

        const diasSemanaFull = {
            'Monday': 'Segunda-feira', 'Tuesday': 'Terça-feira', 'Wednesday': 'Quarta-feira',
            'Thursday': 'Quinta-feira', 'Friday': 'Sexta-feira', 'Saturday': 'Sábado', 'Sunday': 'Domingo'
        };
        const fmtNum = (val) => new Intl.NumberFormat('pt-BR').format(val);
        const fmtMoney = (val) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);

        // --- 2. HELPERS ---
        function getCustoAno(dataIso) {
            const ano = dataIso.split('-')[0];
            return CUSTO_PASSAGEM[ano] || 0;
        }

        // --- 3. CARREGAMENTO ---
        async function carregarDados() {
            const btn = document.querySelector('#filterForm button');
            const originalText = btn.innerHTML;
            btn.innerHTML = 'A carregar...'; btn.disabled = true;

            try {
                const params = new URLSearchParams({
                    data_inicio: document.getElementById('data_inicio').value,
                    data_fim: document.getElementById('data_fim').value,
                    linha: document.getElementById('linha').value,
                    dia_semana: document.getElementById('dia_semana').value
                });

                const req = await fetch(`ajax_bilhetagem.php?${params.toString()}`);
                const data = await req.json();
                globalData = data; 

                renderKPIs(data.kpis);
                renderComparativoMacro(data.kpis, data.comparativo);
                renderGraficoEvolucao(data.evolucao);
                renderTabelaEvolucao(data.evolucao);
                renderTabelaDistribuicao(data.distribuicao_tipos);
                renderTabelaDetalhada(data.tabela_detalhada);

            } catch (err) {
                console.error(err);
                alert("Erro ao carregar dados.");
            } finally {
                btn.innerHTML = originalText; btn.disabled = false;
            }
        }

        // --- 4. RENDERIZADORES ---

        function renderKPIs(kpis) {
            document.getElementById('kpi-total').innerText = fmtNum(kpis.total);
            document.getElementById('kpi-receita').innerText = fmtMoney(kpis.receita);
            document.getElementById('kpi-media').innerText = fmtNum(kpis.media);
            
            document.getElementById('kpi-pico-val').innerText = fmtNum(kpis.pico_val);
            const diaPicoEn = kpis.pico_dia;
            document.getElementById('kpi-pico-dia').innerText = diasSemanaFull[diaPicoEn] || diaPicoEn;

            // Breakdown dias da semana (Com Receita)
            const diasOrder = [1, 2, 3, 4, 5, 6, 7]; 
            const nomesDias = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
            let htmlDays = '<div class="grid grid-cols-7 gap-1 text-center">';
            diasOrder.forEach((d, idx) => {
                const valPax = kpis.dias_semana_stats[d] || 0;
                const valRec = kpis.dias_semana_receita[d] || 0;
                htmlDays += `
                    <div class="flex flex-col">
                        <span class="font-bold bg-slate-100 rounded py-1 mb-1 text-[10px] uppercase">${nomesDias[idx]}</span>
                        <span class="font-bold text-slate-700">${valPax > 0 ? fmtNum(valPax) : '-'}</span>
                        <span class="text-[10px] text-green-600">${valRec > 0 ? fmtMoney(valRec) : '-'}</span>
                    </div>`;
            });
            htmlDays += '</div>';
            document.getElementById('kpi-breakdown-days').innerHTML = htmlDays;
        }

        function renderComparativoMacro(atual, comp) {
            const renderBox = (idMacro, idDays, valAtualTotal, valCompTotal, statsAtual, statsComp, receitaAtual, receitaComp) => {
                // Total Diff
                const diff = valAtualTotal - valCompTotal;
                const perc = valCompTotal > 0 ? (diff / valCompTotal) * 100 : 0;
                const color = diff >= 0 ? 'text-green-600' : 'text-red-600';
                const icon = diff >= 0 ? '▲' : '▼';
                
                // Receita Diff
                const diffRec = receitaAtual - receitaComp;
                const percRec = receitaComp > 0 ? (diffRec / receitaComp) * 100 : 0;
                
                document.getElementById(idMacro).innerHTML = `
                    <div class="${color} flex items-baseline gap-2">
                        <span class="text-3xl font-extrabold">${icon} ${Math.abs(perc).toFixed(1)}%</span>
                        <span class="text-sm font-medium text-slate-500">(${diff >= 0 ? '+' : ''}${fmtNum(diff)})</span>
                    </div>
                    <div class="mt-1 text-xs text-slate-500">
                        Receita: <span class="${diffRec >= 0 ? 'text-green-600' : 'text-red-600'} font-bold">${diffRec >= 0 ? '+' : ''}${fmtMoney(diffRec)} (${Math.abs(percRec).toFixed(1)}%)</span>
                    </div>`;

                // Breakdown Days Diff
                const nomesDias = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
                let htmlD = '<div class="grid grid-cols-7 gap-1 text-center text-[10px]">';
                for(let i=1; i<=7; i++) {
                    const vAtual = statsAtual.dias_semana_stats[i] || 0;
                    const vComp = statsComp.dias_semana_stats[i] || 0;
                    const dDiff = vAtual - vComp;
                    const dPerc = vComp > 0 ? (dDiff / vComp) * 100 : 0;
                    const dClass = dDiff >= 0 ? 'text-green-600' : 'text-red-600';
                    
                    // Receita Dia Diff
                    const rAtual = statsAtual.dias_semana_receita[i] || 0;
                    const rComp = statsComp.dias_semana_receita[i] || 0;
                    const rDiff = rAtual - rComp;
                    const rClass = rDiff >= 0 ? 'text-green-600' : 'text-red-600';
                    
                    htmlD += `
                        <div class="flex flex-col border-r border-slate-100 last:border-0">
                            <span class="font-bold text-slate-400 mb-1">${nomesDias[i-1]}</span>
                            <span class="${dClass} font-bold block leading-tight">${dDiff > 0 ? '+' : ''}${fmtNum(dDiff)}</span>
                            <span class="${dClass} block leading-tight text-[9px] mb-1">(${Math.round(dPerc)}%)</span>
                            <span class="${rClass} block leading-tight border-t border-slate-100 pt-1">${rDiff > 0 ? '+' : ''}${fmtMoney(rDiff)}</span>
                        </div>
                    `;
                }
                htmlD += '</div>';
                
                const containerDays = document.getElementById(idDays);
                containerDays.innerHTML = `<h4 class="text-xs font-bold text-slate-400 mb-2">Diferença por Dia (Pax | Receita)</h4>` + htmlD;
                containerDays.classList.remove('hidden');
            };

            renderBox('comp-mes-macro', 'comp-mes-days', atual.total, comp.mes.total, atual, comp.mes, atual.receita, comp.mes.receita);
            renderBox('comp-ano-macro', 'comp-ano-days', atual.total, comp.ano.total, atual, comp.ano, atual.receita, comp.ano.receita);
        }

        function renderGraficoEvolucao(dados) {
            const ctx = document.getElementById('chartEvolucao').getContext('2d');
            if (chartEvolucao) chartEvolucao.destroy();

            chartEvolucao = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dados.labels,
                    datasets: [
                        { label: 'Atual', data: dados.atual, borderColor: '#22c55e', backgroundColor: 'rgba(34, 197, 94, 0.1)', borderWidth: 3, tension: 0.2, fill: true },
                        { label: 'Mês Anterior', data: dados.mes, borderColor: '#fbbf24', borderWidth: 2, borderDash: [5, 5], tension: 0.2, pointRadius: 0 },
                        { label: 'Ano Anterior', data: dados.ano, borderColor: '#ef4444', borderWidth: 2, borderDash: [2, 2], tension: 0.2, pointRadius: 0 }
                    ]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false, 
                    interaction: {
                        mode: 'index', // MOSTRA TOOLTIP UNIFICADO
                        intersect: false,
                    },
                    plugins: { 
                        legend: { position: 'top' } 
                    }, 
                    scales: { y: { beginAtZero: true } } 
                }
            });
        }

        function renderTabelaEvolucao(dados) {
            const tbody = document.querySelector('#tabela-evolucao tbody');
            const tfoot = document.querySelector('#tabela-evolucao tfoot');
            tbody.innerHTML = ''; tfoot.innerHTML = '';

            let sAtual = 0, sMes = 0, sAno = 0;

            dados.labels.forEach((label, i) => {
                const vAtual = dados.atual[i] || 0;
                const vMes = dados.mes[i] || 0;
                const vAno = dados.ano[i] || 0;
                sAtual += vAtual; sMes += vMes; sAno += vAno;

                const diffMes = vAtual - vMes;
                const percMes = vMes > 0 ? (diffMes/vMes)*100 : 0;
                
                const diffAno = vAtual - vAno;
                const percAno = vAno > 0 ? (diffAno/vAno)*100 : 0;

                const tr = `
                    <tr class="hover:bg-slate-50">
                        <td class="p-2 text-left font-semibold">${label}</td>
                        <td class="p-2 bg-green-50 font-bold">${fmtNum(vAtual)}</td>
                        <td class="p-2 bg-yellow-50">${fmtNum(vMes)}</td>
                        <td class="p-2 bg-red-50">${fmtNum(vAno)}</td>
                        <td class="p-2 ${diffMes >= 0 ? 'text-green-600' : 'text-red-600'} text-xs font-bold">
                            ${diffMes > 0 ? '+' : ''}${fmtNum(diffMes)} <span class="text-[10px] font-normal">(${Math.abs(percMes).toFixed(1)}%)</span>
                        </td>
                        <td class="p-2 ${diffAno >= 0 ? 'text-green-600' : 'text-red-600'} text-xs font-bold">
                            ${diffAno > 0 ? '+' : ''}${fmtNum(diffAno)} <span class="text-[10px] font-normal">(${Math.abs(percAno).toFixed(1)}%)</span>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += tr;
            });

            // Totais com Diferenças e Porcentagens
            const totalDiffMes = sAtual - sMes;
            const totalPercMes = sMes > 0 ? (totalDiffMes/sMes)*100 : 0;
            const totalDiffAno = sAtual - sAno;
            const totalPercAno = sAno > 0 ? (totalDiffAno/sAno)*100 : 0;

            tfoot.innerHTML = `
                <tr>
                    <td class="p-2 text-left">TOTAL</td>
                    <td class="p-2 bg-green-100 text-green-900">${fmtNum(sAtual)}</td>
                    <td class="p-2 bg-yellow-100 text-yellow-900">${fmtNum(sMes)}</td>
                    <td class="p-2 bg-red-100 text-red-900">${fmtNum(sAno)}</td>
                    <td class="p-2 font-bold ${totalDiffMes>=0?'text-green-800':'text-red-800'}">
                        ${totalDiffMes > 0 ? '+' : ''}${fmtNum(totalDiffMes)} (${Math.abs(totalPercMes).toFixed(1)}%)
                    </td>
                    <td class="p-2 font-bold ${totalDiffAno>=0?'text-green-800':'text-red-800'}">
                        ${totalDiffAno > 0 ? '+' : ''}${fmtNum(totalDiffAno)} (${Math.abs(totalPercAno).toFixed(1)}%)
                    </td>
                </tr>
            `;
        }

        function renderTabelaDistribuicao(dados) {
            const tbody = document.querySelector('#tabela-distribuicao tbody');
            tbody.innerHTML = '';
            const tipos = Object.keys(dados.atual);
            tipos.forEach(tipo => {
                const vAtual = dados.atual[tipo] || 0;
                const vMes = dados.mes[tipo] || 0;
                const vAno = dados.ano[tipo] || 0;
                const diffMes = vAtual - vMes;
                const percMes = vMes > 0 ? (diffMes/vMes)*100 : 0;
                const diffAno = vAtual - vAno;
                const percAno = vAno > 0 ? (diffAno/vAno)*100 : 0;
                const colorMes = diffMes >= 0 ? 'text-green-600' : 'text-red-600';
                const colorAno = diffAno >= 0 ? 'text-green-600' : 'text-red-600';

                tbody.innerHTML += `
                    <tr class="hover:bg-slate-50">
                        <td class="p-2 text-left font-bold text-slate-700">${tipo}</td>
                        <td class="p-2 bg-green-50 font-bold">${fmtNum(vAtual)}</td>
                        <td class="p-2 bg-yellow-50 text-slate-600">${fmtNum(vMes)}</td>
                        <td class="p-2 ${colorMes} text-xs font-bold">${diffMes>0?'+':''}${fmtNum(diffMes)} (${Math.abs(percMes).toFixed(1)}%)</td>
                        <td class="p-2 bg-red-50 text-slate-600">${fmtNum(vAno)}</td>
                        <td class="p-2 ${colorAno} text-xs font-bold">${diffAno>0?'+':''}${fmtNum(diffAno)} (${Math.abs(percAno).toFixed(1)}%)</td>
                    </tr>
                `;
            });
        }

        function renderTabelaDetalhada(lista) {
            const tbody = document.querySelector('#tabela-detalhes tbody');
            tbody.innerHTML = '';

            lista.forEach((row, index) => {
                const d = row.detalhes;
                const diaNome = diasSemanaFull[new Date(row.data_iso).toLocaleDateString('en-US', { weekday: 'long' })] || row.dia_semana;
                
                // Calcula Receita do Dia Atual
                const custoAtual = getCustoAno(row.data_iso);
                const receitaDia = row.total * custoAtual;

                let varText = '-'; let varClass = 'text-slate-400';
                if (row.variacao !== null) {
                    varText = (row.variacao > 0 ? '+' : '') + row.variacao + '%';
                    varClass = row.variacao >= 0 ? 'text-green-600 font-bold' : 'text-red-600 font-bold';
                }

                // Linha Principal
                const trMain = document.createElement('tr');
                trMain.className = 'hover:bg-blue-50 transition-colors cursor-pointer border-b border-slate-100';
                trMain.onclick = () => toggleDropdown(index);
                trMain.innerHTML = `
                    <td class="p-3 text-center"><span id="icon-${index}" class="text-slate-400 text-xs">▼</span></td>
                    <td class="p-3 font-medium whitespace-nowrap">${row.data}</td>
                    <td class="p-3 whitespace-nowrap">${diaNome}</td>
                    <td class="p-3 text-right font-bold bg-slate-50 text-slate-900">${fmtNum(row.total)}</td>
                    <td class="p-3 text-center ${varClass} text-xs">${varText}</td>
                    <td class="p-3 text-right col-tipo font-mono">${fmtNum(d.comum)}</td>
                    <td class="p-3 text-right col-tipo font-mono">${fmtNum(d.vt)}</td>
                    <td class="p-3 text-right col-tipo font-mono">${fmtNum(d.estudante)}</td>
                    <td class="p-3 text-right col-tipo font-mono text-amber-600">${fmtNum(d.gratuitos)}</td>
                    <td class="p-3 text-right col-tipo font-mono">${fmtNum(d.pagantes)}</td>
                    <td class="p-3 text-right col-tipo font-mono">${fmtNum(d.integracao)}</td>
                    <td class="p-3 text-right col-tipo font-mono">${fmtNum(d.emv)}</td>
                    <td class="p-3 text-right col-tipo font-bold text-green-700 bg-green-50">${fmtMoney(receitaDia)}</td>
                `;
                tbody.appendChild(trMain);

                // --- 2 LINHAS COMPLETAS DE COMPARAÇÃO (Mês/Ano) ---
                // Nota: O backend v3.0 envia apenas 'mes_anterior' (total) e 'ano_anterior' (total) em 'historico'.
                // Não temos os detalhes por tipo de cartão para as datas passadas.
                // As colunas de tipo ficarão vazias ou com traço, mostrando apenas Total e Receita (Calculada).
                
                // Datas de referência para cálculo de custo histórico
                // (Estimativa: Data Atual - 1 mes/ano para pegar o custo da época)
                const dtObj = new Date(row.data_iso);
                const dtMes = new Date(dtObj); dtMes.setMonth(dtMes.getMonth() - 1);
                const dtAno = new Date(dtObj); dtAno.setFullYear(dtAno.getFullYear() - 1);
                
                const custoMes = getCustoAno(dtMes.toISOString().split('T')[0]);
                const custoAno = getCustoAno(dtAno.toISOString().split('T')[0]);

                const histMes = row.historico.mes_anterior || 0;
                const histAno = row.historico.ano_anterior || 0;
                
                const recMes = histMes * custoMes;
                const recAno = histAno * custoAno;

                const diffMes = row.total - histMes;
                const diffAno = row.total - histAno;
                
                const diffRecMes = receitaDia - recMes;
                const diffRecAno = receitaDia - recAno;

                const createCompRow = (label, valHist, diffPax, valRec, diffRec, bgClass) => {
                    const diffColor = diffPax >= 0 ? 'text-green-600' : 'text-red-600';
                    const diffRecColor = diffRec >= 0 ? 'text-green-600' : 'text-red-600';
                    
                    return `
                        <tr id="detail-${index}-${label}" class="hidden expanded-row ${bgClass}">
                            <td></td>
                            <td colspan="2" class="p-2 text-right font-bold uppercase text-[10px] text-slate-500 tracking-wider">Vs. ${label}</td>
                            <td class="p-2 text-right font-semibold text-slate-600">${fmtNum(valHist)}</td>
                            <td class="p-2 text-center text-xs font-bold ${diffColor}">
                                ${diffPax > 0 ? '+' : ''}${fmtNum(diffPax)}
                            </td>
                            <!-- Colunas de tipos vazias (sem dados no backend) -->
                            <td colspan="7" class="text-center text-xs text-slate-400 opacity-50">-</td>
                            <td class="p-2 text-right font-semibold text-slate-600">
                                ${fmtMoney(valRec)}
                                <span class="block text-[9px] ${diffRecColor}">${diffRec > 0 ? '+' : ''}${fmtMoney(diffRec)}</span>
                            </td>
                        </tr>
                    `;
                };

                const trMes = createCompRow('Mês Ant.', histMes, diffMes, recMes, diffRecMes, 'mes');
                const trAno = createCompRow('Ano Ant.', histAno, diffAno, recAno, diffRecAno, 'ano');

                // Gambiarra para inserir HTML strings como nós
                const tempBody = document.createElement('tbody');
                tempBody.innerHTML = trMes + trAno;
                while (tempBody.firstChild) tbody.appendChild(tempBody.firstChild);
            });
        }

        // --- UTILITÁRIOS ---
        
        function toggleTabelaEvolucao() {
            const div = document.getElementById('tabela-evolucao-container');
            const btn = document.querySelector('.toggle-btn');
            if (div.classList.contains('hidden')) { div.classList.remove('hidden'); btn.innerText = 'Ocultar Tabela'; } 
            else { div.classList.add('hidden'); btn.innerText = 'Ver Tabela'; }
        }

        function toggleDropdown(idx) {
            const rows = [document.getElementById(`detail-${idx}-Mês Ant.`), document.getElementById(`detail-${idx}-Ano Ant.`)];
            const icon = document.getElementById(`icon-${idx}`);
            
            let isHidden = rows[0].classList.contains('hidden');
            rows.forEach(r => {
                if (isHidden) r.classList.remove('hidden');
                else r.classList.add('hidden');
            });
            icon.innerText = isHidden ? '▲' : '▼';
        }

        function exportarCSVCompleto() {
            if (!globalData || !globalData.tabela_detalhada) { alert("Sem dados."); return; }
            
            let csv = "Data;Dia;Total;Var(%);Comum;VT;Estudante;Gratuito;Pagante;Integ;EMV;Receita;Mes_Ant_Total;Dif_Mes_Pax;Ano_Ant_Total;Dif_Ano_Pax\n";
            
            globalData.tabela_detalhada.forEach(row => {
                const d = row.detalhes;
                const dia = diasSemanaFull[new Date(row.data_iso).toLocaleDateString('en-US', { weekday: 'long' })] || row.dia_semana;
                const custo = getCustoAno(row.data_iso);
                const receita = row.total * custo;

                // Linha Principal
                const line = [
                    row.data, dia, row.total, row.variacao,
                    d.comum, d.vt, d.estudante, d.gratuitos, d.pagantes, d.integracao, d.emv,
                    receita.toFixed(2),
                    row.historico.mes_anterior, (row.total - row.historico.mes_anterior),
                    row.historico.ano_anterior, (row.total - row.historico.ano_anterior)
                ].map(v => String(v || 0).replace('.', ',')).join(';');
                csv += line + "\n";
                
                // Aqui poderíamos adicionar linhas extras no CSV para simular o visual, 
                // mas geralmente CSVs preferem uma linha por registro de dados. 
                // As colunas extras acima (Mes_Ant_Total, etc) já cobrem a comparação.
            });

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = "bilhetagem_completo.csv";
            link.click();
        }

        document.addEventListener('DOMContentLoaded', carregarDados);
    </script>
</body>
</html>