<?php
// =================================================================
//  Parceiro de Programação - Relatório de Projeção de KM (v1.5)
//  - VERSÃO HÍBRIDA: Histórico (Banco) ou Manual (Inputs na Tela).
//  - ADICIONADO: Exibição da Média Mensal Projetada (Total / 12).
// =================================================================

ini_set('display_errors', 1); error_reporting(E_ALL);
$data_ini_default = date('Y-m-01', strtotime('-3 month'));
$data_fim_default = date('Y-m-t', strtotime('-1 month'));
$ano_atual = date('Y');
$proximo_ano = $ano_atual + 1;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projeção de Quilometragem</title>
    <script src="tailwindcss-3.4.17.js"></script>
    <script src="chart.js"></script>
    <style>
        @import url('css2.css');
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
        .card { background-color: white; border-radius: 0.75rem; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .input-field { border: 1px solid #cbd5e1; padding: 0.5rem; border-radius: 0.375rem; width: 100%; font-size: 0.875rem; }
        .hidden-inputs { display: none; }
    </style>
</head>
<body class="p-4 md:p-8 text-slate-800">
    <main class="max-w-7xl mx-auto">
        
        <header class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">Projeção de Cenários de KM</h1>
                <p class="text-slate-500 mt-1">Simulação baseada em histórico ou definição manual.</p>
            </div>
            <a href="gerenciar_metas.php" class="bg-white text-blue-600 border border-blue-200 font-semibold py-2 px-4 rounded shadow-sm hover:bg-blue-50 transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Configurar Metas & Feriados
            </a>
        </header>

        <!-- Filtros -->
        <div class="card p-6 mb-8 border-l-4 border-indigo-500">
            <h2 class="text-lg font-bold text-slate-700 mb-4">Parâmetros da Simulação</h2>
            <form id="formProjecao">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end mb-4">
                    
                    <!-- Fonte -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fonte de Dados</label>
                        <select id="fonte" class="input-field bg-slate-50 font-semibold text-slate-700" onchange="toggleInputsManual()">
                            <option value="mtran_precisao" selected>MTRAN (Novo)</option>
                            <option value="mtran">MTRAN (Programado - Antigo)</option>
                            <option value="004">Clever 004 (Realizado)</option>
                            <option value="roleta">Roleta (Realizado)</option>
                            <option value="noxxon">Noxxon (Telemetria)</option>
                            <option value="life">Life (Odômetro)</option>
                            <option value="manual" class="text-indigo-600 font-bold">Manual (Digitar Médias)</option>
                        </select>
                    </div>

                    <!-- Campos Históricos (Escondidos se Manual) -->
                    <div class="campos-historico">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Início Histórico</label>
                        <input type="date" id="data_inicio" value="<?= $data_ini_default ?>" class="input-field">
                    </div>
                    <div class="campos-historico">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fim Histórico</label>
                        <input type="date" id="data_fim" value="<?= $data_fim_default ?>" class="input-field">
                    </div>

                    <!-- Ano Projeção -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Ano de Projeção</label>
                        <select id="ano_projecao" class="input-field">
                            <option value="<?= $ano_atual ?>"><?= $ano_atual ?></option>
                            <option value="<?= $proximo_ano ?>" selected><?= $proximo_ano ?></option>
                            <option value="<?= $proximo_ano + 1 ?>"><?= $proximo_ano + 1 ?></option>
                        </select>
                    </div>

                    <!-- Botão -->
                    <div class="flex gap-2 items-end">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Ajuste (%)</label>
                            <input type="number" id="ajuste" value="0" step="0.1" class="input-field text-right" placeholder="0.0">
                        </div>
                        <button type="button" onclick="calcularProjecao()" class="bg-indigo-600 text-white font-bold py-2 px-4 rounded shadow hover:bg-indigo-700 transition-colors h-[38px]">
                            Projetar
                        </button>
                    </div>
                </div>

                <!-- Campos Manuais (Aparecem só se fonte=manual) -->
                <div id="inputs-manual" class="hidden-inputs bg-indigo-50 p-4 rounded border border-indigo-100 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-indigo-700 uppercase mb-1">Média Dias Úteis (Km)</label>
                        <input type="number" id="manual_uteis" class="input-field border-indigo-300" placeholder="Ex: 35000.00" step="0.01">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-indigo-700 uppercase mb-1">Média Sábados (Km)</label>
                        <input type="number" id="manual_sab" class="input-field border-indigo-300" placeholder="Ex: 22000.00" step="0.01">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-indigo-700 uppercase mb-1">Média Domingos (Km)</label>
                        <input type="number" id="manual_dom" class="input-field border-indigo-300" placeholder="Ex: 15000.00" step="0.01">
                    </div>
                </div>
            </form>
        </div>

        <!-- Resultados -->
        <div id="resultados" class="hidden space-y-8 animate-fade-in">
            
            <!-- Cards de Resumo -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card Médias Calculadas -->
                <div class="card p-5 border-t-4 border-blue-400">
                    <h3 class="text-xs font-bold text-slate-400 uppercase mb-3">Base de Cálculo (Médias Diárias)</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between border-b border-slate-100 pb-1">
                            <span class="text-slate-600">Dias Úteis:</span>
                            <span class="font-bold text-slate-800" id="media-uteis">...</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-100 pb-1">
                            <span class="text-slate-600">Sábados:</span>
                            <span class="font-bold text-slate-800" id="media-sab">...</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">Domingos:</span>
                            <span class="font-bold text-slate-800" id="media-dom">...</span>
                        </div>
                    </div>
                    <p class="text-xs text-right text-slate-400 mt-3" id="info-base">Período: ...</p>
                </div>

                <!-- Card Projeção Anual -->
                <div class="card p-5 border-t-4 border-indigo-600 flex flex-col justify-center text-center">
                    <h3 class="text-xs font-bold text-slate-400 uppercase mb-2">Projeção Total (Ano)</h3>
                    <p class="text-4xl font-extrabold text-indigo-700" id="total-projetado">...</p>
                    <!-- Média Mensal Adicionada Abaixo -->
                    <p class="text-xl font-semibold text-slate-500 mt-2 bg-slate-100 rounded px-2 py-1 inline-block">
                        Média Mensal: <span id="media-mensal-proj" class="text-indigo-600">...</span>
                    </p>
                </div>

                <!-- Card Diferença Meta -->
                <div class="card p-5 border-t-4 border-gray-400 flex flex-col justify-center text-center">
                    <h3 class="text-xs font-bold text-slate-400 uppercase mb-2">Diferença vs. Planilha Tarifária</h3>
                    <div class="flex items-center justify-center gap-3">
                        <p class="text-3xl font-bold" id="total-diff">...</p>
                        <span class="px-2 py-1 rounded text-xs font-bold" id="total-diff-perc">...</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-2">Meta Anual: <span id="meta-anual-val">...</span></p>
                </div>
            </div>

            <!-- Gráfico -->
            <div class="card p-6">
                <h3 class="font-bold text-slate-700 mb-4">Evolução Mensal: Projetado vs. Meta</h3>
                <div class="h-80 w-full"><canvas id="chartProjecao"></canvas></div>
            </div>

            <!-- Tabela Detalhada -->
            <div class="card overflow-hidden">
                <div class="p-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-700">Detalhamento Mensal da Projeção</h3>
                    <span class="text-xs text-slate-500 bg-white px-2 py-1 rounded border">Ano Base: <strong id="ano-base-display"></strong></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-100 text-slate-500 uppercase text-xs">
                            <tr>
                                <th class="p-3">Mês</th>
                                <th class="p-3 text-center">D. Úteis</th>
                                <th class="p-3 text-center">Sáb</th>
                                <th class="p-3 text-center">Dom</th>
                                <th class="p-3 text-right bg-indigo-50 text-indigo-800">KM Projetado</th>
                                <th class="p-3 text-right">Meta (Planilha)</th>
                                <th class="p-3 text-center">Diferença</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-mensal" class="divide-y divide-slate-100 text-slate-700"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        let chartInstance = null;

        function toggleInputsManual() {
            const fonte = document.getElementById('fonte').value;
            const inputsManual = document.getElementById('inputs-manual');
            const camposHist = document.querySelectorAll('.campos-historico');

            if (fonte === 'manual') {
                inputsManual.classList.remove('hidden-inputs');
                camposHist.forEach(el => el.classList.add('hidden-inputs'));
            } else {
                inputsManual.classList.add('hidden-inputs');
                camposHist.forEach(el => el.classList.remove('hidden-inputs'));
            }
        }

        async function calcularProjecao() {
            const btn = document.querySelector('#formProjecao button');
            const originalText = btn.innerText;
            btn.innerText = 'Calculando...'; btn.disabled = true;
            document.getElementById('resultados').classList.add('hidden');

            const anoProj = document.getElementById('ano_projecao').value;
            document.getElementById('ano-base-display').innerText = anoProj;

            // Coleta dados
            const params = new URLSearchParams({
                fonte: document.getElementById('fonte').value,
                data_inicio: document.getElementById('data_inicio').value,
                data_fim: document.getElementById('data_fim').value,
                ano_projecao: anoProj,
                ajuste: document.getElementById('ajuste').value,
                // Novos campos manuais
                manual_uteis: document.getElementById('manual_uteis').value,
                manual_sab: document.getElementById('manual_sab').value,
                manual_dom: document.getElementById('manual_dom').value
            });

            try {
                const res = await fetch(`ajax_projecao_km.php?${params.toString()}`);
                const data = await res.json();

                if (data.error) { alert(data.error); return; }

                // Preencher Cards
                document.getElementById('media-uteis').innerText = Number(data.medias_base.uteis).toLocaleString('pt-BR') + ' km';
                document.getElementById('media-sab').innerText = Number(data.medias_base.sab).toLocaleString('pt-BR') + ' km';
                document.getElementById('media-dom').innerText = Number(data.medias_base.dom).toLocaleString('pt-BR') + ' km';
                document.getElementById('info-base').innerText = `Base: ${data.medias_base.periodo} (Ajuste: ${data.medias_base.ajuste_aplicado})`;

                // Totais Anuais e Médias
                const totalProj = Number(data.totais_ano.projetado);
                const mediaMensalProj = totalProj / 12; // Cálculo da média mensal simples

                document.getElementById('total-projetado').innerText = totalProj.toLocaleString('pt-BR') + ' km';
                document.getElementById('media-mensal-proj').innerText = mediaMensalProj.toLocaleString('pt-BR', {maximumFractionDigits: 3}) + ' km';
                document.getElementById('meta-anual-val').innerText = Number(data.totais_ano.meta).toLocaleString('pt-BR') + ' km';

                // Diferença
                const diffPerc = data.totais_ano.diff_perc;
                const elDiff = document.getElementById('total-diff');
                const elPerc = document.getElementById('total-diff-perc');
                
                elDiff.innerText = (data.totais_ano.diff > 0 ? '+' : '') + Number(data.totais_ano.diff).toLocaleString('pt-BR');
                elPerc.innerText = Math.abs(diffPerc) + '%';
                
                elPerc.className = diffPerc > 0 
                    ? 'px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-700 ml-2' 
                    : 'px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-700 ml-2';
                elDiff.className = data.totais_ano.diff > 0 ? 'text-3xl font-bold text-red-600' : 'text-3xl font-bold text-green-600';

                // Tabela
                const tbody = document.getElementById('tbody-mensal');
                tbody.innerHTML = '';
                const labels = []; const dataProj = []; const dataMeta = [];

                Object.keys(data.mensal).forEach(k => {
                    const m = data.mensal[k];
                    labels.push(m.nome_mes);
                    dataProj.push(m.projetado);
                    dataMeta.push(m.meta);

                    const colorDiff = m.diff > 0 ? 'text-red-600' : 'text-green-600';

                    tbody.innerHTML += `
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-3 font-semibold">${m.nome_mes}</td>
                            <td class="p-3 text-center">${m.dias.uteis}</td>
                            <td class="p-3 text-center">${m.dias.sabados}</td>
                            <td class="p-3 text-center">${m.dias.domingos}</td>
                            <td class="p-3 text-right bg-indigo-50 font-bold text-indigo-700">${Number(m.projetado).toLocaleString('pt-BR')}</td>
                            <td class="p-3 text-right text-slate-500">${Number(m.meta).toLocaleString('pt-BR')}</td>
                            <td class="p-3 text-center text-xs font-bold ${colorDiff}">
                                ${(m.diff > 0 ? '+' : '')}${Number(m.diff).toLocaleString('pt-BR')}
                                <span class="block text-[10px] opacity-80">${Math.abs(m.diff_perc)}%</span>
                            </td>
                        </tr>
                    `;
                });

                renderChart(labels, dataProj, dataMeta);
                document.getElementById('resultados').classList.remove('hidden');

            } catch (err) {
                console.error(err); alert("Erro ao processar simulação.");
            } finally {
                btn.innerText = originalText; btn.disabled = false;
            }
        }

        function renderChart(labels, dataProj, dataMeta) {
            const ctx = document.getElementById('chartProjecao').getContext('2d');
            if (chartInstance) chartInstance.destroy();

            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'KM Projetado', data: dataProj, backgroundColor: '#4f46e5', borderRadius: 4, order: 2 },
                        { type: 'line', label: 'Meta', data: dataMeta, borderColor: '#9ca3af', borderWidth: 2, borderDash: [5, 5], pointRadius: 3, order: 1 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
    </script>
</body>
</html>