<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-_8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Passageiros por Câmeras</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Application Structure Plan: 
        A single-page application with a top navigation bar for easy scrolling to different thematic sections.
        1. Visão Geral: Introduces the problem and the camera-based solution.
        2. Como Funciona: Explains the technology using a simple HTML/CSS flow diagram.
        3. Benefícios Chave: Highlights advantages using cards with icons.
        4. Dados Interativos: Presents interactive charts (occupancy, efficiency, alert types) to showcase system capabilities. This section is key for user engagement and understanding data-driven insights.
        5. Desafios e Soluções: Discusses potential issues like privacy and their mitigations.
        6. Próximos Passos: Outlines future developments or implementation ideas.
        This structure was chosen to guide the user logically from the general concept to specific details and data, then to considerations and future outlook, enhancing usability and comprehension of the report's (conceptual) content.
    -->
    <!-- Visualization & Content Choices:
        1. Report Info: General introduction to passenger verification issues. Goal: Inform. Viz/Presentation: Textual intro in "Visão Geral". Interaction: None. Justification: Sets context. Library/Method: HTML.
        2. Report Info: How the camera system works. Goal: Explain. Viz/Presentation: HTML/CSS flow diagram in "Como Funciona". Interaction: None. Justification: Clarifies process visually. Library/Method: HTML/Tailwind.
        3. Report Info: Benefits of the system. Goal: Inform. Viz/Presentation: Icon + Text cards in "Benefícios Chave". Interaction: None. Justification: Clearly lists advantages. Library/Method: HTML/Tailwind, Unicode icons.
        4. Report Info: Occupancy rates. Goal: Show change/trends. Viz/Presentation: Line chart in "Dados Interativos". Interaction: Buttons to switch datasets (weekday/weekend). Tooltips. Justification: Visualizes passenger flow. Library/Method: Chart.js (Canvas).
        5. Report Info: System efficiency/accuracy. Goal: Compare. Viz/Presentation: Bar chart in "Dados Interativos". Interaction: Tooltips. Justification: Demonstrates system effectiveness. Library/Method: Chart.js (Canvas).
        6. Report Info: Types of alerts generated. Goal: Show proportions. Viz/Presentation: Pie chart in "Dados Interativos". Interaction: Tooltips. Justification: Illustrates system's detection capabilities. Library/Method: Chart.js (Canvas).
        7. Report Info: Challenges and solutions. Goal: Inform. Viz/Presentation: Textual content in "Desafios e Soluções". Interaction: None. Justification: Provides a balanced perspective. Library/Method: HTML.
        8. Report Info: Future implementation/roadmap. Goal: Inform. Viz/Presentation: List/timeline in "Próximos Passos". Interaction: None. Justification: Outlines future direction. Library/Method: HTML/Tailwind.
        All choices support the designed application structure and aim for clarity and user engagement.
    -->
    <style>
        body {
            font-family: 'Inter', sans-serif; /* Assuming Inter is loaded or defaults to a sans-serif */
        }
        .chart-container {
            position: relative;
            width: 100%;
            max-width: 600px; /* Max width for readability */
            margin-left: auto;
            margin-right: auto;
            height: 300px; /* Base height */
            max-height: 400px; /* Max height */
        }
        @media (min-width: 768px) { /* md breakpoint */
            .chart-container {
                height: 350px;
            }
        }
        html {
            scroll-behavior: smooth;
        }
        .nav-link {
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: #0d9488; /* teal-600 */
        }
        .section-title {
            font-size: 2.25rem; /* text-4xl */
            font-weight: 700;
            color: #0f766e; /* teal-700 */
            margin-bottom: 1.5rem; /* mb-6 */
            text-align: center;
        }
        .card {
            background-color: white;
            border-radius: 0.5rem; /* rounded-lg */
            padding: 1.5rem; /* p-6 */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); /* shadow-lg */
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .flow-step {
            border: 2px solid #0d9488; /* teal-600 */
            padding: 1rem;
            border-radius: 0.5rem;
            text-align: center;
            background-color: #f0fdfa; /* teal-50 */
            color: #134e4a; /* teal-900 */
        }
        .arrow {
            font-size: 2rem;
            color: #0d9488; /* teal-600 */
            margin: 0 0.5rem;
            align-self: center;
        }
        .tab-button {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            border: 1px solid #0d9488; /* teal-600 */
            color: #0d9488; /* teal-600 */
            background-color: white;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .tab-button.active {
            background-color: #0d9488; /* teal-600 */
            color: white;
        }
        .tab-button:hover:not(.active) {
            background-color: #ccfbf1; /* teal-100 */
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-700">

    <header class="bg-white shadow-md sticky top-0 z-50">
        <nav class="container mx-auto px-6 py-3 flex justify-between items-center">
            <a href="#" class="text-2xl font-bold text-teal-700">VisãoTech Ônibus</a>
            <div class="space-x-4">
                <a href="#visao-geral" class="nav-link text-slate-600">Visão Geral</a>
                <a href="#como-funciona" class="nav-link text-slate-600">Como Funciona</a>
                <a href="#beneficios" class="nav-link text-slate-600">Benefícios</a>
                <a href="#dados" class="nav-link text-slate-600">Dados</a>
                <a href="#desafios" class="nav-link text-slate-600">Desafios</a>
                <a href="#futuro" class="nav-link text-slate-600">Futuro</a>
            </div>
        </nav>
    </header>

    <main class="container mx-auto px-6 py-8">

        <section id="visao-geral" class="py-12">
            <h2 class="section-title">Visão Geral: Modernizando o Transporte Público</h2>
            <p class="text-lg text-center max-w-3xl mx-auto mb-8">
                Este painel explora a implementação de sistemas de verificação de passageiros em ônibus utilizando câmeras inteligentes. O objetivo é apresentar como essa tecnologia pode revolucionar a gestão do transporte coletivo, aumentando a eficiência, segurança e a arrecadação, além de fornecer dados valiosos para o planejamento urbano. Abordaremos o funcionamento, os benefícios diretos, as análises de dados possíveis, os desafios inerentes e os próximos passos para a consolidação dessa inovação.
            </p>
            <div class="text-center">
                <span class="text-6xl text-teal-500">🚌</span> <span class="text-6xl text-slate-400 mx-4">➡️</span> <span class="text-6xl text-teal-500">📸</span>
            </div>
        </section>

        <section id="como-funciona" class="py-12 bg-slate-100 rounded-lg">
            <h2 class="section-title">Como Funciona o Sistema?</h2>
            <p class="text-lg text-center max-w-3xl mx-auto mb-10">
                O sistema de verificação por câmeras integra hardware e software avançados para automatizar a contagem e identificação de passageiros, além de validar pagamentos de forma eficiente. Entenda o fluxo básico da tecnologia:
            </p>
            <div class="grid md:grid-cols-4 items-center gap-4 max-w-4xl mx-auto">
                <div class="flow-step">
                    <span class="text-3xl block mb-2">①</span>
                    <h3 class="font-semibold text-lg">Captura de Imagem</h3>
                    <p class="text-sm">Câmeras de alta resolução instaladas na entrada e saída do veículo registram imagens dos passageiros.</p>
                </div>
                <div class="arrow hidden md:block">➡️</div>
                <div class="flow-step">
                    <span class="text-3xl block mb-2">②</span>
                    <h3 class="font-semibold text-lg">Processamento IA</h3>
                    <p class="text-sm">Algoritmos de Inteligência Artificial analisam as imagens para contar passageiros, detectar padrões e, opcionalmente, verificar identidades ou bilhetes.</p>
                </div>
                 <div class="arrow hidden md:block">➡️</div>
                <div class="flow-step">
                     <span class="text-3xl block mb-2">③</span>
                    <h3 class="font-semibold text-lg">Verificação e Alerta</h3>
                    <p class="text-sm">O sistema compara os dados com informações de bilhetagem, identificando evasões ou outras irregularidades e gerando alertas.</p>
                </div>
                 <div class="arrow hidden md:block">➡️</div>
                <div class="flow-step">
                     <span class="text-3xl block mb-2">④</span>
                    <h3 class="font-semibold text-lg">Registro de Dados</h3>
                    <p class="text-sm">Todas as informações relevantes são registradas em tempo real, alimentando um banco de dados para análises futuras e relatórios.</p>
                </div>
            </div>
        </section>

        <section id="beneficios" class="py-12">
            <h2 class="section-title">Benefícios Chave da Tecnologia</h2>
            <p class="text-lg text-center max-w-3xl mx-auto mb-10">
                A adoção de sistemas de verificação por câmeras traz uma série de vantagens significativas para operadores de transporte, passageiros e para a cidade como um todo. Estes são alguns dos principais benefícios:
            </p>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="card">
                    <span class="text-4xl text-teal-500 block mb-3">🎯</span>
                    <h3 class="text-xl font-semibold text-teal-700 mb-2">Maior Precisão</h3>
                    <p>Redução drástica de erros na contagem de passageiros e na identificação de evasão de tarifas em comparação com métodos manuais.</p>
                </div>
                <div class="card">
                    <span class="text-4xl text-teal-500 block mb-3">⏱️</span>
                    <h3 class="text-xl font-semibold text-teal-700 mb-2">Eficiência Operacional</h3>
                    <p>Automação de processos, otimizando o tempo dos motoristas e fiscais, e permitindo um melhor dimensionamento da frota.</p>
                </div>
                <div class="card">
                    <span class="text-4xl text-teal-500 block mb-3">🛡️</span>
                    <h3 class="text-xl font-semibold text-teal-700 mb-2">Segurança Aprimorada</h3>
                    <p>Monitoramento contínuo que pode inibir comportamentos inadequados e auxiliar na identificação de incidentes.</p>
                </div>
                <div class="card">
                    <span class="text-4xl text-teal-500 block mb-3">📊</span>
                    <h3 class="text-xl font-semibold text-teal-700 mb-2">Insights de Dados</h3>
                    <p>Coleta de dados detalhados sobre fluxo de passageiros, horários de pico e padrões de uso, fundamentais para o planejamento.</p>
                </div>
            </div>
        </section>

        <section id="dados" class="py-12 bg-slate-100 rounded-lg">
            <h2 class="section-title">Dados e Análises Interativas</h2>
            <p class="text-lg text-center max-w-3xl mx-auto mb-10">
                Os dados coletados pelo sistema de câmeras permitem análises detalhadas sobre a operação do transporte público. Explore alguns exemplos interativos abaixo para entender o potencial informativo desta tecnologia. Os dados apresentados são conceituais e servem para ilustrar as capacidades do sistema.
            </p>

            <div class="grid lg:grid-cols-1 gap-8 items-start">
                <div class="card">
                    <h3 class="text-xl font-semibold text-teal-700 mb-1 text-center">Taxa de Ocupação Média Diária</h3>
                    <p class="text-sm text-center text-slate-500 mb-4">Visualize a variação da ocupação dos ônibus ao longo do dia.</p>
                    <div class="flex justify-center space-x-2 mb-4">
                        <button id="btnDiaUtil" class="tab-button active">Dia Útil</button>
                        <button id="btnFimSemana" class="tab-button">Fim de Semana</button>
                    </div>
                    <div class="chart-container">
                        <canvas id="ocupacaoChart"></canvas>
                    </div>
                     <p class="text-xs text-center text-slate-400 mt-2">Interaja com os botões para alterar o conjunto de dados.</p>
                </div>

                <div class="card mt-8">
                    <h3 class="text-xl font-semibold text-teal-700 mb-1 text-center">Eficiência na Verificação (% Erros)</h3>
                     <p class="text-sm text-center text-slate-500 mb-4">Comparativo da taxa de erro entre contagem manual e o sistema de câmeras.</p>
                    <div class="chart-container">
                        <canvas id="eficienciaChart"></canvas>
                    </div>
                    <p class="text-xs text-center text-slate-400 mt-2">Passe o mouse sobre as barras para ver os valores.</p>
                </div>

                <div class="card mt-8">
                    <h3 class="text-xl font-semibold text-teal-700 mb-1 text-center">Tipos de Alertas Gerados</h3>
                    <p class="text-sm text-center text-slate-500 mb-4">Distribuição dos tipos de alertas mais comuns identificados pelo sistema.</p>
                    <div class="chart-container" style="max-height: 350px; height: 300px;">
                        <canvas id="alertasChart"></canvas>
                    </div>
                    <p class="text-xs text-center text-slate-400 mt-2">Passe o mouse sobre as fatias para ver os detalhes.</p>
                </div>
            </div>
        </section>

        <section id="desafios" class="py-12">
            <h2 class="section-title">Desafios e Soluções</h2>
            <p class="text-lg text-center max-w-3xl mx-auto mb-10">
                A implementação de qualquer nova tecnologia enfrenta desafios. É crucial antecipá-los e planejar soluções para garantir o sucesso e a aceitação do sistema de verificação por câmeras.
            </p>
            <div class="grid md:grid-cols-2 gap-8">
                <div class="card bg-amber-50 border-l-4 border-amber-500">
                    <h3 class="text-xl font-semibold text-amber-700 mb-2">Privacidade dos Dados</h3>
                    <p class="text-slate-600"><strong class="text-amber-600">Desafio:</strong> A coleta de imagens levanta preocupações sobre a privacidade dos passageiros e o uso dos dados.</p>
                    <p class="text-slate-600 mt-2"><strong class="text-green-600">Solução:</strong> Implementar políticas rigorosas de anonimização, criptografia, acesso restrito aos dados e conformidade com a LGPD. Foco na contagem e detecção de padrões, não na identificação individual, exceto em casos específicos e regulamentados.</p>
                </div>
                <div class="card bg-rose-50 border-l-4 border-rose-500">
                    <h3 class="text-xl font-semibold text-rose-700 mb-2">Custo de Implementação</h3>
                    <p class="text-slate-600"><strong class="text-rose-600">Desafio:</strong> Aquisição de hardware (câmeras, processadores), software e treinamento podem representar um investimento inicial considerável.</p>
                    <p class="text-slate-600 mt-2"><strong class="text-green-600">Solução:</strong> Análise de ROI demonstrando economias a longo prazo com redução de evasão e otimização de rotas. Busca por financiamentos e parcerias. Implementação gradual.</p>
                </div>
                <div class="card bg-sky-50 border-l-4 border-sky-500">
                    <h3 class="text-xl font-semibold text-sky-700 mb-2">Precisão em Condições Adversas</h3>
                    <p class="text-slate-600"><strong class="text-sky-600">Desafio:</strong> Iluminação variável, superlotação, ou obstruções visuais podem afetar a precisão do sistema.</p>
                    <p class="text-slate-600 mt-2"><strong class="text-green-600">Solução:</strong> Uso de câmeras com WDR, infravermelho, e algoritmos de IA robustos, treinados com vastos conjuntos de dados em diversas condições. Calibração e manutenção periódicas.</p>
                </div>
                <div class="card bg-indigo-50 border-l-4 border-indigo-500">
                    <h3 class="text-xl font-semibold text-indigo-700 mb-2">Aceitação Pública e dos Funcionários</h3>
                    <p class="text-slate-600"><strong class="text-indigo-600">Desafio:</strong> Necessidade de comunicar claramente os benefícios e o funcionamento do sistema para evitar resistência.</p>
                    <p class="text-slate-600 mt-2"><strong class="text-green-600">Solução:</strong> Campanhas de informação, treinamento para motoristas e fiscais sobre como a tecnologia os auxiliará. Transparência sobre o uso dos dados.</p>
                </div>
            </div>
        </section>

        <section id="futuro" class="py-12 bg-slate-100 rounded-lg">
            <h2 class="section-title">Próximos Passos e Visão de Futuro</h2>
             <p class="text-lg text-center max-w-3xl mx-auto mb-10">
                A tecnologia de verificação por câmeras é um campo em constante evolução. Os próximos passos envolvem aprimoramento contínuo e a integração com outras soluções de mobilidade urbana inteligente.
            </p>
            <ul class="list-disc list-inside max-w-2xl mx-auto space-y-3 text-slate-700">
                <li><span class="font-semibold text-teal-700">Integração com Sistemas de Bilhetagem Eletrônica Avançados:</span> Permitir validação facial como forma de pagamento ou verificação de passes.</li>
                <li><span class="font-semibold text-teal-700">Análise Preditiva de Demanda:</span> Utilizar dados históricos para prever fluxos de passageiros e otimizar a oferta de ônibus em tempo real.</li>
                <li><span class="font-semibold text-teal-700">Detecção de Comportamento Anômalo:</span> Aprimorar IA para identificar comportamentos de risco ou emergências a bordo, alertando autoridades.</li>
                <li><span class="font-semibold text-teal-700">Expansão para Outros Modais:</span> Adaptar a tecnologia para uso em trens, metrôs e outros meios de transporte público.</li>
                <li><span class="font-semibold text-teal-700">Plataforma de Dados Unificada:</span> Criar um dashboard centralizado para gestores urbanos com insights de todos os modais, facilitando o planejamento integrado da mobilidade.</li>
            </ul>
        </section>
    </main>

    <footer class="bg-slate-800 text-slate-300 text-center py-6 mt-12">
        <p>&copy; <span id="currentYear"></span> VisãoTech Ônibus. Soluções Inteligentes para Mobilidade Urbana.</p>
    </footer>

    <script>
        document.getElementById('currentYear').textContent = new Date().getFullYear();

        // Smooth scroll for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // Chart.js Configuration
        Chart.defaults.font.family = 'Inter, sans-serif';
        Chart.defaults.color = '#475569'; // slate-600

        const tooltipTitleColor = '#0f766e'; // teal-700
        const tooltipBodyColor = '#334155'; // slate-700
        const gridColor = '#e2e8f0'; // slate-300
        const legendLabelColor = '#475569'; // slate-600

        // Chart Data
        const horas = ['06h', '07h', '08h', '09h', '10h', '11h', '12h', '13h', '14h', '15h', '16h', '17h', '18h', '19h'];
        const ocupacaoDiaUtil = [20, 45, 70, 60, 50, 45, 65, 55, 50, 40, 55, 75, 85, 60];
        const ocupacaoFimSemana = [10, 20, 30, 40, 45, 50, 55, 45, 40, 35, 30, 25, 20, 15];

        const linhasOnibus = ['Linha A', 'Linha B', 'Linha C', 'Linha D'];
        const errosContagemManual = [15, 12, 18, 14]; // % de erro
        const errosSistemaCameras = [2, 1, 3, 1.5]; // % de erro

        const tiposAlertasLabels = ['Evasão de Tarifa', 'Superlotação', 'Objeto Esquecido', 'Vandalismo', 'Outros'];
        const tiposAlertasData = [55, 20, 10, 8, 7];

        // Chart Instances
        let ocupacaoChartInstance, eficienciaChartInstance, alertasChartInstance;

        // Function to wrap labels
        function wrapLabels(labels, maxWidth) {
            return labels.map(label => {
                if (label.length > maxWidth) {
                    const words = label.split(' ');
                    let currentLine = '';
                    const newLabel = [];
                    words.forEach(word => {
                        if ((currentLine + word).length > maxWidth) {
                            newLabel.push(currentLine.trim());
                            currentLine = '';
                        }
                        currentLine += word + ' ';
                    });
                    newLabel.push(currentLine.trim());
                    return newLabel;
                }
                return label;
            });
        }
        
        // Render Ocupacao Chart
        const ctxOcupacao = document.getElementById('ocupacaoChart').getContext('2d');
        function renderOcupacaoChart(dataToShow) {
            if (ocupacaoChartInstance) {
                ocupacaoChartInstance.destroy();
            }
            ocupacaoChartInstance = new Chart(ctxOcupacao, {
                type: 'line',
                data: {
                    labels: horas,
                    datasets: [{
                        label: 'Taxa de Ocupação (%)',
                        data: dataToShow,
                        borderColor: '#0d9488', // teal-600
                        backgroundColor: 'rgba(13, 148, 136, 0.1)', // teal-600 with alpha
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: '#0d9488', // teal-600
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: '#0d9488' // teal-600
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: { display: true, text: 'Ocupação (%)', color: legendLabelColor },
                            grid: { color: gridColor }
                        },
                        x: {
                            title: { display: true, text: 'Horário', color: legendLabelColor },
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(255,255,255,0.9)',
                            titleColor: tooltipTitleColor,
                            bodyColor: tooltipBodyColor,
                            borderColor: '#0d9488', // teal-600
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    return `Ocupação: ${context.parsed.y}%`;
                                }
                            }
                        }
                    }
                }
            });
        }
        renderOcupacaoChart(ocupacaoDiaUtil); // Initial render

        document.getElementById('btnDiaUtil').addEventListener('click', () => {
            renderOcupacaoChart(ocupacaoDiaUtil);
            document.getElementById('btnDiaUtil').classList.add('active');
            document.getElementById('btnFimSemana').classList.remove('active');
        });
        document.getElementById('btnFimSemana').addEventListener('click', () => {
            renderOcupacaoChart(ocupacaoFimSemana);
            document.getElementById('btnFimSemana').classList.add('active');
            document.getElementById('btnDiaUtil').classList.remove('active');
        });

        // Render Eficiencia Chart
        const ctxEficiencia = document.getElementById('eficienciaChart').getContext('2d');
        eficienciaChartInstance = new Chart(ctxEficiencia, {
            type: 'bar',
            data: {
                labels: linhasOnibus,
                datasets: [
                    {
                        label: 'Contagem Manual (% Erro)',
                        data: errosContagemManual,
                        backgroundColor: '#7dd3fc', // sky-300
                        borderColor: '#0ea5e9', // sky-500
                        borderWidth: 1
                    },
                    {
                        label: 'Sistema com Câmeras (% Erro)',
                        data: errosSistemaCameras,
                        backgroundColor: '#2dd4bf', // teal-400
                        borderColor: '#0d9488', // teal-600
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Taxa de Erro (%)', color: legendLabelColor },
                        grid: { color: gridColor }
                    },
                    x: {
                         grid: { display: false }
                    }
                },
                plugins: {
                    legend: { position: 'top', labels: { color: legendLabelColor } },
                    tooltip: {
                        backgroundColor: 'rgba(255,255,255,0.9)',
                        titleColor: tooltipTitleColor,
                        bodyColor: tooltipBodyColor,
                        borderColor: '#0d9488', // teal-600
                        borderWidth: 1,
                         callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.parsed.y}%`;
                            }
                        }
                    }
                }
            }
        });

        // Render Alertas Chart
        const ctxAlertas = document.getElementById('alertasChart').getContext('2d');
        alertasChartInstance = new Chart(ctxAlertas, {
            type: 'pie',
            data: {
                labels: wrapLabels(tiposAlertasLabels, 16), // Wrap labels for pie chart
                datasets: [{
                    label: 'Tipos de Alertas',
                    data: tiposAlertasData,
                    backgroundColor: [
                        '#14b8a6', // teal-500
                        '#2dd4bf', // teal-400
                        '#5eead4', // teal-300
                        '#99f6e4', // teal-200
                        '#ccfbf1'  // teal-100
                    ],
                    borderColor: '#f0fdfa', // teal-50 (almost white for separation)
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom', 
                        labels: { 
                            color: legendLabelColor,
                            boxWidth: 15,
                            padding: 15,
                            generateLabels: function(chart) { // Custom label generation to handle wrapped text
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    return data.labels.map(function(label, i) {
                                        const meta = chart.getDatasetMeta(0);
                                        const style = meta.controller.getStyle(i);
                                        return {
                                            text: Array.isArray(label) ? label.join(' ') : label, // Join wrapped label back for display
                                            fillStyle: style.backgroundColor,
                                            strokeStyle: style.borderColor,
                                            lineWidth: style.borderWidth,
                                            hidden: isNaN(data.datasets[0].data[i]) || meta.data[i].hidden,
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        } 
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255,255,255,0.9)',
                        titleColor: tooltipTitleColor,
                        bodyColor: tooltipBodyColor,
                        borderColor: '#0d9488', // teal-600
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                let label = Array.isArray(context.label) ? context.label.join(' ') : context.label;
                                label += `: ${context.parsed}%`;
                                return label;
                            }
                        }
                    }
                }
            }
        });

    </script>
</body>
</html>
