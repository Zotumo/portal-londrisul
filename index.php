<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Central de Controle do Portal e Importação</title>
    <script src="tailwindcss-3.4.17.js"></script>
    <link href="css2.css" rel="stylesheet">
    <style>
        /* CONFIGURAÇÃO DO FUNDO FIXO */
        body { 
            font-family: 'Inter', sans-serif; 
            /* Imagem de fundo fixa e preenchendo a tela */
            background-image: url('fundo-body.png');
            background-attachment: fixed; /* Fixa a imagem ao rolar */
            background-size: auto;       /* Cobre toda a área */
            background-position: center;  /* Centraliza a imagem */
            background-repeat: no-repeat;
        }

        /* CONFIGURAÇÃO DO CONTAINER PRINCIPAL PARA LEITURA */
        /* Adicionamos um fundo branco semitransparente para o texto não sumir em cima da imagem */
        .main-container {
            background-color: rgba(255, 255, 255, 0.92); /* Branco com 92% de opacidade */
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        /* Estilos dos Cards (Mantidos) */
        .card { transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); }
        
        /* Estilos dos Botões de Upload (Mantidos) */
        input[type="file"]::file-selector-button { margin-right: 0.5rem; padding: 0.5rem 1rem; border-radius: 9999px; border-width: 0px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: background-color 0.2s ease-in-out; }
        #csv_0241::file-selector-button { background-color: #ebf8ff; color: #2b6cb0; } #csv_0241::file-selector-button:hover { background-color: #bee3f8; }
        #csv_timepoint::file-selector-button { background-color: #faf5ff; color: #805ad5; } #csv_timepoint::file-selector-button:hover { background-color: #e9d8fd; }
        #csv_frota::file-selector-button { background-color: #f0fff4; color: #2f855a; } #csv_frota::file-selector-button:hover { background-color: #c6f6d5; }
        #csv_divisao::file-selector-button { background-color: #fff5f7; color: #d53f8c; } #csv_divisao::file-selector-button:hover { background-color: #fed7e2; }
        #csv_km_roleta::file-selector-button { background-color: #fffbeb; color: #b45309; } #csv_km_roleta::file-selector-button:hover { background-color: #fef3c7; }
        #csv_km_noxxon::file-selector-button { background-color: #ecfeff; color: #0891b2; } #csv_km_noxxon::file-selector-button:hover { background-color: #a5f3fc; }
        #csv_odometro_life::file-selector-button { background-color: #eef2ff; color: #4f46e5; } #csv_odometro_life::file-selector-button:hover { background-color: #c7d2fe; }
        #csv_comunicacao::file-selector-button { background-color: #fee2e2; color: #dc2626; } #csv_comunicacao::file-selector-button:hover { background-color: #fecaca; }
        #csv_icv_ipv::file-selector-button { background-color: #f3f4f6; color: #4b5563; } #csv_icv_ipv::file-selector-button:hover { background-color: #e5e7eb; }
        #csv_on_time::file-selector-button { background-color: #f3f4f6; color: #4b5563; } #csv_on_time::file-selector-button:hover { background-color: #e5e7eb; }
        #csv_servicos::file-selector-button { background-color: #ecfeff; color: #0e7490; } #csv_servicos::file-selector-button:hover { background-color: #cffafe; }
        #csv_viagens::file-selector-button { background-color: #f0fdf4; color: #15803d; } #csv_viagens::file-selector-button:hover { background-color: #dcfce7; }
        #csv_viagens_ajuste::file-selector-button { background-color: #f0fdf4; color: #15803d; } #csv_viagens_ajuste::file-selector-button:hover { background-color: #dcfce7; }
        #csv_bilhetagem::file-selector-button { background-color: #fff7ed; color: #c2410c; } #csv_bilhetagem::file-selector-button:hover { background-color: #ffedd5; }
    </style>
</head>
<body class="p-4 md:p-8 min-h-screen">
    
    <main class="max-w-7xl mx-auto main-container p-6 md:p-10">
        
        <header class="flex flex-col md:flex-row items-center justify-between mb-10 gap-6 border-b pb-8 border-gray-200">
            
            <div class="w-full md:w-1/4 flex justify-center md:justify-start">
                <img src="logo-londrisul.png" alt="Londrisul" class="h-16 md:h-20 object-contain">
            </div>

            <div class="w-full md:w-2/4 text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-800 tracking-tight">Central de Controle</h1>
                <p class="mt-2 text-lg text-gray-600 font-medium">Selecione a área que gostaria de acessar.</p>
            </div>

            <div class="w-full md:w-1/4 flex justify-center md:justify-end">
                <img src="logo-ciop.png" alt="CIOP" class="h-12 md:h-16 object-contain">
            </div>

        </header>

        <section class="mb-12">
             <h2 class="text-3xl font-semibold text-gray-700 mb-6 border-b pb-3 flex items-center gap-2">
                Escolha a Seção
             </h2>
             <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
                 <a href="portal/index.php" target="_blank" class="card block bg-gradient-to-br from-amber-400 to-orange-500 p-6 rounded-xl shadow-lg text-white hover:shadow-xl transition-shadow duration-300">
                     <h3 class="text-2xl font-bold mb-2">Portal do Motorista <p>&nbsp</p></h3>
                     <p class="text-lg opacity-90 mb-4">Acesse o portal do motorista e suas ferramentas</p>
                     <span class="font-semibold inline-block border-b-2 border-transparent hover:border-white transition-colors">Acessar Portal &rarr;</span>
                 </a>
                 <a href="relatorios/index.php" target="_blank" class="card block bg-gradient-to-br from-red-400 to-blue-500 p-6 rounded-xl shadow-lg text-white hover:shadow-xl transition-shadow duration-300">
                     <h3 class="text-2xl font-bold mb-2">Relatórios <p>&nbsp</p></h3>
                     <p class="text-lg opacity-90 mb-4">Importe relatórios e acesse os dashboards de análise.</p>
                     <span class="font-semibold inline-block border-b-2 border-transparent hover:border-white transition-colors">Acessar Painel &rarr;</span>
                 </a>
             </div>
         </section>
     </main>
 </body>
 </html>