<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diário de Bordo - V1.7</title>
    
    <link href="../relatorios/bootstrap.min.css" rel="stylesheet">
    <link href="../relatorios/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../relatorios/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .card-header { background-color: #343a40; color: white; font-weight: bold; }
        
        .diario-container {
            background: white; padding: 40px; border: 1px solid #ddd;
            box-shadow: 0 0 15px rgba(0,0,0,0.1); min-height: 800px;
            max-width: 210mm; margin: 20px auto;
        }
        .header-diario h2 { font-weight: 800; text-transform: uppercase; color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 20px; }
        .info-header { font-size: 1.1rem; font-weight: bold; color: #333; margin-bottom: 5px; text-transform: uppercase; }
        
        .table-viagens { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table-viagens th { background-color: #e9ecef; border-top: 2px solid #333; border-bottom: 2px solid #333; padding: 8px 4px; text-align: left; font-size: 0.8rem; text-transform: uppercase; font-weight: 800; }
        .table-viagens td { padding: 6px 4px; border-bottom: 1px solid #ddd; font-size: 0.9rem; vertical-align: middle; }
        .linha-ociosa { color: #6c757d; font-style: italic; background-color: #f8f9fa; }
        .fw-extra-bold { font-weight: 800; }
        
        @media print {
            body * { visibility: hidden; }
            .no-print { display: none !important; }
            .tab-pane.active, .tab-pane.active * { visibility: visible; }
            .tab-pane.active { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 0; }
            .diario-container { margin: 0; box-shadow: none; border: none; width: 100%; max-width: 100%; padding: 10px; }
            .table-viagens th { background-color: #eee !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="container-fluid py-4 no-print">
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-bus-front"></i> Gerador de Diário de Bordo</span>
            <small>Sistema Integrado V1.7</small>
        </div>
        <div class="card-body bg-light">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-bold">Data</label>
                    <input type="date" id="data_selecionada" class="form-control">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold">Modo de Busca</label>
                    <select id="tipo_busca" class="form-select border-primary">
                        <option value="workid">Por WorkID (Serviço)</option>
                        <option value="linha">Por Linha (Todos Blocos)</option>
                        <option value="linha_hora">Por Linha e Hora</option>
                        <option value="motorista">Por Motorista (Escala)</option>
                    </select>
                </div>
                
                <div class="col-md-2" id="container_linha">
                    <label class="form-label fw-bold">Selecione a Linha</label>
                    <select id="filtro_linha" class="form-select select2"><option value="">Aguarde...</option></select>
                </div>
                
                <div class="col-md-2" id="container_workid">
                    <label class="form-label fw-bold">Serviço (WorkID)</label>
                    <select id="filtro_workid" class="form-select select2"><option value="">Selecione...</option></select>
                </div>

                <div class="col-md-3" id="container_motorista" style="display:none;">
                    <label class="form-label fw-bold">Selecione o Motorista</label>
                    <select id="filtro_motorista" class="form-select select2" data-placeholder="Nome ou Matrícula...">
                        <option value="">Selecione...</option>
                    </select>
                </div>

                <div class="col-md-2" id="container_horario" style="display:none;">
                    <label class="form-label fw-bold">Horário (Início - Fim)</label>
                    <div class="input-group">
                        <input type="time" id="hora_inicio" class="form-control" value="00:00">
                        <input type="time" id="hora_fim" class="form-control" value="23:59">
                    </div>
                </div>

                <div class="col-md-auto d-flex gap-2 ms-auto">
                    <button class="btn btn-primary fw-bold px-4" id="btn_gerar">Gerar</button>
                    <button class="btn btn-dark" id="btn_imprimir_layout"><i class="bi bi-printer"></i> Imprimir</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4 mb-5" id="area_resultados">
    <ul class="nav nav-tabs no-print mb-3" id="abas_diarios" role="tablist"></ul>
    <div class="tab-content" id="conteudo_diarios">
        <div class="text-center text-muted py-5">
            <i class="bi bi-clipboard-data" style="font-size: 3rem;"></i>
            <h4 class="mt-3">Selecione os filtros e clique em Gerar</h4>
        </div>
    </div>
</div>

<script src="../relatorios/jquery-3.6.0.min.js"></script>
<script src="../relatorios/bootstrap.bundle.min.js"></script>
<script src="../relatorios/select2.min.js"></script>

<script>
$(document).ready(function() {
    
    // Inicialização do Select2
    $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

    // Select2 Especial para Motorista (AJAX)
    $('#filtro_motorista').select2({
        theme: 'bootstrap-5', width: '100%',
        placeholder: 'Busque por nome ou matrícula...',
        ajax: {
            url: 'buscar_motoristas_ajax.php',
            dataType: 'json', delay: 250,
            data: function (params) { return { q: params.term, page: params.page || 1 }; },
            processResults: function (data) { return { results: data.items }; }
        },
        minimumInputLength: 2
    });

    // Definir Data Inicial (Hoje ou Última)
    $.getJSON('ajax_diario_bordo.php', { acao: 'get_ultima_data' })
        .done(function(r) {
            $('#data_selecionada').val(r.data || new Date().toISOString().split('T')[0]).trigger('change');
        })
        .fail(function() {
            $('#data_selecionada').val(new Date().toISOString().split('T')[0]).trigger('change');
        });

    // --- CONTROLE VISUAL ---
    function atualizarVisibilidade() {
        const modo = $('#tipo_busca').val();
        
        // Esconde tudo primeiro
        $('#container_workid').hide();
        $('#container_linha').hide();
        $('#container_motorista').hide();
        $('#container_horario').hide();

        if (modo === 'workid') {
            $('#container_linha').show();
            $('#container_workid').show();
        } else if (modo === 'linha') {
            $('#container_linha').show();
        } else if (modo === 'linha_hora') {
            $('#container_linha').show();
            $('#container_horario').show();
        } else if (modo === 'motorista') {
            $('#container_motorista').show();
        }
    }

    // --- EVENTOS ---
    $('#tipo_busca').change(function() { 
        atualizarVisibilidade(); 
        if($(this).val() === 'workid') carregarWorkIDs();
    });

    $('#data_selecionada').change(function() { 
        carregarLinhas(); 
        if($('#tipo_busca').val() === 'workid') carregarWorkIDs();
    });

    $('#filtro_linha').change(function() { 
        if($('#tipo_busca').val() === 'workid') carregarWorkIDs();
    });
    
    // Inicialização
    atualizarVisibilidade();

    // --- CARREGAMENTOS AJAX ---
    function carregarLinhas() {
        $.getJSON('ajax_diario_bordo.php', { acao: 'listar_linhas', data: $('#data_selecionada').val() }).done(function(lst) {
            let ops = '<option value="">Selecione...</option>';
            if(Array.isArray(lst)) lst.forEach(l => ops += `<option value="${l}">${l}</option>`);
            $('#filtro_linha').html(ops).trigger('change.select2');
        });
    }

    function carregarWorkIDs() {
        if($('#tipo_busca').val() !== 'workid') return;
        
        $.getJSON('ajax_diario_bordo.php', { acao: 'listar_workids', data: $('#data_selecionada').val(), linha: $('#filtro_linha').val() || 'todas' }).done(function(lst) {
            let ops = '<option value="">Selecione...</option>';
            if(Array.isArray(lst)) lst.forEach(w => ops += `<option value="${w}">${w}</option>`);
            $('#filtro_workid').html(ops).trigger('change.select2');
        });
    }

    // --- BOTÃO GERAR ---
    $('#btn_gerar').click(function() {
        let modo = $('#tipo_busca').val();
        let valor = '';
        let params = { acao: 'gerar_diario', data: $('#data_selecionada').val(), tipo: modo };

        // Validações
        if (modo === 'workid') {
            valor = $('#filtro_workid').val();
            if(!valor) { alert('Selecione um WorkID.'); return; }
        } else if (modo === 'linha' || modo === 'linha_hora') {
            valor = $('#filtro_linha').val();
            if(!valor) { alert('Selecione uma Linha.'); return; }
        } else if (modo === 'motorista') {
            valor = $('#filtro_motorista').val();
            if(!valor) { alert('Selecione um Motorista.'); return; }
        }
        params.valor = valor;

        if (modo === 'linha_hora') {
            params.hora_ini = $('#hora_inicio').val();
            params.hora_fim = $('#hora_fim').val();
            if(!params.hora_ini || !params.hora_fim) { alert('Preencha o horário de início e fim.'); return; }
        }

        // Feedback Loading
        $('#conteudo_diarios').html('<div class="text-center mt-5"><div class="spinner-border text-primary"></div><p class="mt-2">Processando Diários...</p></div>');
        $('#abas_diarios').empty();
        
        $.getJSON('ajax_diario_bordo.php', params).done(function(resp) {
            if(resp.error) { 
                $('#conteudo_diarios').html(`<div class="alert alert-danger text-center"><i class="bi bi-exclamation-triangle"></i> ${resp.error}</div>`); 
                return; 
            }
            renderizar(resp.diarios);
        }).fail(function(jqXHR) {
            $('#conteudo_diarios').html(`<div class="alert alert-danger text-center">Erro crítico no servidor. Verifique o console.</div>`);
            console.error(jqXHR.responseText);
        });
    });

    // --- BOTÃO IMPRIMIR ---
    $('#btn_imprimir_layout').click(function() {
        let modo = $('#tipo_busca').val();
        let valor = '';
        
        if (modo === 'workid') valor = $('#filtro_workid').val();
        else if (modo === 'motorista') valor = $('#filtro_motorista').val();
        else valor = $('#filtro_linha').val();

        if(!valor) { alert('Selecione os filtros antes de imprimir.'); return; }

        let url = `relatorio_diario_bordo_impressao.php?data=${$('#data_selecionada').val()}&tipo=${modo}&valor=${valor}`;
        
        if (modo === 'linha_hora') {
            let hIni = $('#hora_inicio').val();
            let hFim = $('#hora_fim').val();
            if(!hIni || !hFim) { alert('Preencha os horários.'); return; }
            url += `&hora_ini=${hIni}&hora_fim=${hFim}`;
        }

        window.open(url, '_blank');
    });

    // --- RENDERIZAÇÃO NA TELA ---
    function renderizar(lista) {
        $('#abas_diarios').empty(); $('#conteudo_diarios').empty();
        
        if(!lista || lista.length === 0) {
            $('#conteudo_diarios').html('<div class="alert alert-warning text-center">Nenhum dado encontrado para os filtros selecionados.</div>');
            return;
        }

        lista.forEach((d, i) => {
            let id = `diario-${i}`, active = i===0 ? 'active' : '';
            
            // Monta Título da Aba
            let tituloAba = `Bloco ${d.cabecalho.bloco}`;
            if(d.cabecalho.linhas_resumo) tituloAba += ` (${d.cabecalho.linhas_resumo})`;

            // Adiciona Aba
            $('#abas_diarios').append(`<li class="nav-item"><button class="nav-link ${active}" data-bs-toggle="tab" data-bs-target="#${id}">${tituloAba}</button></li>`);
            
            // Adiciona Conteúdo
            let html = `
            <div class="tab-pane fade show ${active}" id="${id}">
                <div class="diario-container">
                    <div class="text-center header-diario">
                        <h2>Diário de Bordo</h2>
                    </div>
                    <div class="row border-bottom border-3 border-dark pb-3 mb-3">
                        <div class="col-6 info-header">DATA: <span class="fw-normal">${d.cabecalho.data_formatada}</span></div>
                        <div class="col-6 info-header text-end">
                            BLOCO: <span class="fw-normal">${d.cabecalho.bloco}</span><br>
                            LINHAS: <span class="fw-normal">${d.cabecalho.linhas_resumo}</span>
                        </div>
                    </div>
                    <table class="table-viagens">
                        <thead>
                            <tr>
                                <th style="width: 8%">LINHA</th>
                                <th style="width: 8%">TABELA</th>
                                <th style="width: 10%">WORK ID</th>
                                <th style="width: 10%">CHEGADA</th>
                                <th style="width: 10%">SAÍDA</th>
                                <th style="width: 30%">LOCAL</th>
                                <th style="width: 24%">INFO / OBSERVAÇÃO</th>
                            </tr>
                        </thead>
                        <tbody>${htmlRows(d.viagens)}</tbody>
                    </table>
                </div>
            </div>`;
            $('#conteudo_diarios').append(html);
        });
    }

    function htmlRows(rows) {
        if(!rows.length) return '<tr><td colspan="7" class="text-center">Sem dados.</td></tr>';
        return rows.map(r => `
            <tr class="${r.is_ociosa ? 'linha-ociosa' : ''}">
                <td class="fw-extra-bold">${r.linha || '-'}</td>
                <td>${r.tabela}</td>
                <td class="fw-bold">${r.workid}</td>
                <td>${r.chegada_show}</td>
                <td class="fw-bold">${r.saida_show}</td>
                <td>${r.local}</td>
                <td style="font-size: 0.8rem">${r.info}</td>
            </tr>
        `).join('');
    }
});
</script>
</body>
</html>