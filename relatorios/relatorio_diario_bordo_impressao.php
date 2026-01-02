<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Impressão Diário de Bordo</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* --- A4 RETRATO --- */
        @page {
            size: A4 portrait;
            margin: 5mm;
        }

        body {
            background: #fff;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            -webkit-print-color-adjust: exact;
        }

        /* Container da Página A4 */
        .pagina-a4 {
            width: 200mm;
            height: 286mm; /* Altura útil exata (297mm - margens) */
            display: flex;
            flex-direction: row; /* LADO A LADO */
            justify-content: center; 
            page-break-after: always;
            overflow: hidden;
            margin: 0 auto;
        }

        /* Coluna do Diário (Metade da Largura) */
        .diario-coluna {
            width: 50%;      /* Metade da página */
            height: 100%;    /* Altura total da página */
            padding: 0 3px;  /* Pequeno espaçamento entre colunas */
            box-sizing: border-box;
            display: block;
        }

        /* A Caixa Visível do Diário (Borda Preta) */
        .diario-box {
            width: 100%;
            height: 100%;    /* Ocupa 100% da altura da coluna */
            border: 2px solid #000;
            display: flex;
            flex-direction: column;
            background-color: white;
            overflow: hidden; /* O que passar daqui será escalado */
            position: relative;
        }

        /* Cabeçalho Fixo (Não sofre zoom) */
        .header-box {
            text-align: center;
            border-bottom: 2px solid #000;
            padding: 5px;
            flex-shrink: 0; 
        }
        .header-box h2 { font-size: 11pt; font-weight: 800; margin: 0; text-transform: uppercase; }
        .header-info { font-size: 8pt; font-weight: bold; margin-top: 2px; line-height: 1.2; }

        /* Área de Conteúdo (Onde vai a tabela) */
        .content-area {
            flex: 1; /* Ocupa todo o resto da altura disponível */
            position: relative;
            display: block;
            width: 100%;
            overflow: hidden; 
        }
        
        /* Wrapper que sofre o Scale (Zoom) */
        .content-wrapper {
            display: block;
            width: 100%;
            transform-origin: top left; /* Ponto de origem do zoom */
        }

        /* Tabela */
        .table-print {
            width: 100% !important;
            border-collapse: collapse;
            font-size: 9pt; 
        }
        .table-print th { background-color: #ddd !important; border: 1px solid #000; padding: 2px; text-transform: uppercase; font-size: 7.5pt; text-align: center; }
        .table-print td { border: 1px solid #000; padding: 1px; vertical-align: middle; text-align: center; line-height: 1.0; font-size: 8.5pt; }
        .table-print td.text-left { text-align: left; padding-left: 2px; }

        .linha-ociosa { background-color: #f0f0f0 !important; color: #555; font-style: italic; }
        
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div id="loading" class="text-center mt-5 no-print">
        <h3>Calculando Zoom Automático...</h3>
        <div class="spinner-border text-primary"></div>
    </div>

    <div id="area_impressao"></div>

    <script>
    $(document).ready(function() {
        const params = new URLSearchParams(window.location.search);
        
        $.getJSON('ajax_diario_bordo.php', {
            acao: 'gerar_diario',
            data: params.get('data'),
            tipo: params.get('tipo'),
            valor: params.get('valor'),
            hora_ini: params.get('hora_ini'),
            hora_fim: params.get('hora_fim')
        }).done(function(resp) {
            $('#loading').remove();
            if(resp.error) {
                $('body').html(`<div class="alert alert-danger m-5">${resp.error}</div>`);
                return;
            }
            renderizarImpressao(resp.diarios);
        }).fail(function() {
            $('body').html('<div class="alert alert-danger m-5">Erro ao comunicar com servidor.</div>');
        });

        function renderizarImpressao(diarios) {
            let html = '';
            
            // Loop de 2 em 2 (Lado a Lado)
            for (let i = 0; i < diarios.length; i += 2) {
                let d1 = diarios[i];
                let d2 = diarios[i+1] || null;

                html += `<div class="pagina-a4">`;
                
                // Coluna da Esquerda
                html += `<div class="diario-coluna">`;
                html += criarHtmlDiario(d1);
                html += `</div>`;
                
                // Coluna da Direita
                html += `<div class="diario-coluna">`;
                if(d2) {
                    html += criarHtmlDiario(d2);
                }
                html += `</div>`;
                
                html += `</div>`; // Fim Página
            }

            $('#area_impressao').html(html);
            
            // Delay um pouco maior para garantir que a renderização do DOM está completa
            setTimeout(aplicarZoomLogico, 800);
        }

        function criarHtmlDiario(d) {
            return `
            <div class="diario-box">
                <div class="header-box">
                    <h2>Diário de Bordo</h2>
                    <div class="header-info d-flex justify-content-between text-start">
                        <div>DATA: ${d.cabecalho.data_formatada}</div>
                        <div class="text-end">
                            BLOCO: ${d.cabecalho.bloco}<br>
                            <span style="font-weight:normal; font-size: 7pt">(${d.cabecalho.linhas_resumo})</span>
                        </div>
                    </div>
                </div>

                <div class="content-area">
                    <div class="content-wrapper">
                        <table class="table-print">
                            <thead>
                                <tr>
                                    <th width="8%">LIN</th>
                                    <th width="8%">TAB</th>
                                    <th width="14%">W.ID</th>
                                    <th width="12%">CHEG</th>
                                    <th width="12%">SAÍ</th>
                                    <th width="20%">LOCAL</th>
                                    <th width="26%">INFO</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${d.viagens.map(v => `
                                    <tr class="${v.is_ociosa ? 'linha-ociosa' : ''}">
                                        <td><b>${v.linha || '-'}</b></td>
                                        <td>${v.tabela}</td>
                                        <td><b>${v.workid}</b></td>
                                        <td>${v.chegada_show}</td>
                                        <td><b>${v.saida_show}</b></td>
                                        <td class="text-left">${v.local}</td>
                                        <td class="text-left">${v.info}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            `;
        }

        function aplicarZoomLogico() {
            $('.diario-box').each(function() {
                const box = $(this);
                const contentArea = box.find('.content-area');
                const contentWrapper = box.find('.content-wrapper');
                
                // 1. Reset para medição limpa
                contentWrapper.attr('style', '');
                contentWrapper.css('width', '100%');

                // 2. Medir Alturas
                const availableHeight = contentArea.height();      // Altura disponível (Caixa - Header)
                const contentHeight = contentWrapper.outerHeight(); // Altura real da tabela
                
                // 3. Lógica do Zoom
                if (contentHeight > availableHeight) {
                    
                    // A Mágica: Qual a proporção necessária para caber?
                    // Ex: Espaço=800px, Tabela=1000px -> Scale = 0.8
                    let scale = availableHeight / contentHeight;

                    // Ajuste de segurança imperceptível (evita erro de pixel)
                    scale = scale - 0.005; 

                    // Limite mínimo (para não ficar ilegível)
                    if(scale < 0.80) scale = 0.792;

                    // 4. Compensação de Largura
                    // Ao dar scale(0.5), a largura visual vira 50%.
                    // Para voltar a ser 100% visual, a largura física deve ser 200%.
                    const widthCompensation = (100 / scale);

                    contentWrapper.css({
                        'transform': `scale(${scale})`,
                        'transform-origin': 'top left',
                        'width': `${widthCompensation}%`
                    });
                }
                // Se a tabela for menor que o espaço, Scale = 1 (padrão).
                // O espaço em branco em baixo permanece, o que é natural para tabelas curtas.
            });
            
        }
    });
    </script>
</body>
</html>