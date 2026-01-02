<?php
// portal/parts/display_conteudo_diario.php

// 1. PREPARAÇÃO DOS DADOS
$pontos_unicos = [];
$rotas_processadas = []; 
$scripts_para_rodar = []; 

foreach ($viagens as $v) {
    // --- LÓGICA DE PONTOS (COM O NOVO FILTRO) ---
    $cod_local = $v['START_PLACE'] ?? '';
    $status_pt = $v['status_ponto'] ?? 'ativo';
    $mostrar   = $v['mostrar_ponto'] ?? 1; // Pega o valor do banco (padrão 1)

    // Só adiciona na galeria se mostrar == 1
    if (!empty($cod_local) && $status_pt !== 'inativo' && $mostrar == 1) {
        if (!isset($pontos_unicos[$cod_local])) {
            $pontos_unicos[$cod_local] = [
                'linha' => $v['ROUTE_ID'], 
                'local_nome' => $v['ponto_inicial'], 
                'local_cod' => $cod_local,
                'imagem_path' => $v['imagem_path'] ?? null,
                'descricao' => $v['desc_ponto'] ?? null,
                'coordenadas' => $v['coordenadas'] ?? null
            ];
        }
    }

    // --- LÓGICA DE MAPAS ---
    $nome_via = $v['via_nome'] ?? '';
    $eh_rota_valida = (stripos($nome_via, 'Recolha') === false && stripos($nome_via, 'Deslocamento') === false && !empty($v['ROUTE_VARIANT']));

    if ($eh_rota_valida) {
        $variante = $v['ROUTE_VARIANT'];
        
        if (!isset($rotas_processadas[$variante])) {
            $tipo_mapa = 'indisponivel';
            $dados_mapa = null;

            if (!empty($v['geometria_kml'])) {
                $tipo_mapa = 'digital';
                // Decodifica para garantir que é um JSON válido antes de passar para o JS
                $geo_clean = json_decode($v['geometria_kml']); 
                
                if ($geo_clean) {
                    $dados_mapa = [
                        'geo' => $geo_clean, // Passamos o objeto direto, não string
                        'cor' => $v['cor_kml'] ?? '#0000FF'
                    ];
                } else {
                    $tipo_mapa = 'erro_json';
                }

            } elseif (!empty($v['iframe_mapa'])) {
                $tipo_mapa = 'iframe';
                $dados_mapa = $v['iframe_mapa'];
            }

            $rotas_processadas[$variante] = [
                'linha' => $v['ROUTE_ID'],
                'nome' => $v['via_nome'] ?? 'Rota',
                'variant' => $variante,
                'tipo' => $tipo_mapa,
                'dados' => $dados_mapa
            ];
        }
    }
}

$id_tab_diario = "subtab-diario-" . $wid_safe;
$id_tab_rota   = "subtab-rota-" . $wid_safe;
$id_tab_pontos = "subtab-pontos-" . $wid_safe;
?>

<ul class="nav nav-pills nav-fill mb-3" id="pills-tab-<?php echo $wid_safe; ?>" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="<?php echo $id_tab_diario; ?>-tab" data-toggle="pill" href="#<?php echo $id_tab_diario; ?>" role="tab">
            <i class="fas fa-list-alt"></i> Diário de Bordo
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="<?php echo $id_tab_rota; ?>-tab" data-toggle="pill" href="#<?php echo $id_tab_rota; ?>" role="tab">
            <i class="fas fa-route"></i> Rota / Mapa
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="<?php echo $id_tab_pontos; ?>-tab" data-toggle="pill" href="#<?php echo $id_tab_pontos; ?>" role="tab">
            <i class="fas fa-map-marker-alt"></i> Pontos
        </a>
    </li>
</ul>

<div class="tab-content" id="pills-tabContent-<?php echo $wid_safe; ?>">
    
    <div class="tab-pane fade show active" id="<?php echo $id_tab_diario; ?>" role="tabpanel">
        <div class="table-responsive mb-4 shadow-sm rounded">
            <table class="table table-sm table-hover table-bordered mb-0 text-center" style="font-size: 0.9rem;">
                <thead class="thead-dark">
                    <tr>
                        <th style="width: 8%">LINHA</th>
                        <th style="width: 10%">TABELA</th>
                        <th style="width: 10%">WORK ID</th>
                        <th style="width: 10%">CHEGADA</th>
                        <th style="width: 10%">SAÍDA</th>
                        <th style="width: 25%">LOCAL</th>
                        <th style="width: 27%">INFO / OBSERVAÇÃO</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $chegada_anterior = '-';
                    foreach ($viagens as $v): 
                        $tipo = $v['tipo_linha'] ?? 'macro';
                        
                        if ($tipo == 'recheio') {
                            $chegada_display = $v['chegada_exata'];
                            $saida_display = $v['saida_exata'];
                            $style_tr = "background-color: #f8f9fa;"; 
                            $icon_tipo = "<i class='fas fa-level-up-alt fa-rotate-90 text-muted mr-1'></i>";
                        } elseif ($tipo == 'fim') {
                            $chegada_display = $v['chegada_exata'];
                            $saida_display = '-';
                            $style_tr = "background-color: #e9ecef; font-weight: bold;";
                            $icon_tipo = "";
                        } else {
                            $chegada_display = $chegada_anterior;
                            $saida_display = $v['saida_exata'];
                            $style_tr = "";
                            $icon_tipo = "";
                        }

                        if ($tipo == 'macro') {
                            $chegada_anterior = substr($v['END_TIME'], 0, 5);
                        }

                        $obs = $v['via_nome'];
                        $classe_obs = "";
                        if (stripos($v['via_nome'], 'Recolha') !== false) {
                            $obs = "RECOLHA";
                            $classe_obs = "text-danger font-weight-bold";
                        } elseif (isset($v['TRIP_ID']) && $v['TRIP_ID'] == 0 && empty($obs)) {
                            $obs = "DESLOCAMENTO";
                            $classe_obs = "text-muted font-italic";
                        }
                    ?>
                    <tr style="<?php echo $style_tr; ?>">
                        <td class="align-middle font-weight-bold"><?php echo $v['ROUTE_ID']; ?></td>
                        <td class="align-middle">-</td>
                        <td class="align-middle font-weight-bold"><?php echo $v['work_id_display']; ?></td>
                        <td class="align-middle text-muted"><?php echo $chegada_display; ?></td>
                        <td class="align-middle font-weight-bold text-primary" style="font-size: 1.1em;"><?php echo $saida_display; ?></td>
                        <td class="align-middle text-left small pl-3 font-weight-bold">
                            <?php echo $icon_tipo . $v['ponto_inicial']; ?>
                        </td>
                        <td class="align-middle text-left small pl-2 <?php echo $classe_obs; ?>">
                            <?php echo $obs; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tab-pane fade" id="<?php echo $id_tab_rota; ?>" role="tabpanel">
        <?php if (empty($rotas_processadas)): ?>
            <div class="alert alert-secondary text-center m-3">Nenhum mapa disponível.</div>
        <?php else: ?>
            <?php foreach ($rotas_processadas as $rota): ?>
                <div class="card mb-4 border shadow-sm">
                    <div class="card-header bg-light py-2">
                        <strong class="text-primary"><i class="fas fa-map-marked-alt"></i> Linha <?php echo $rota['linha']; ?></strong> - <?php echo $rota['nome']; ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($rota['tipo'] === 'digital'): 
                            $mapUniqueId = "mapa-leaflet-" . $wid_safe . "-" . $rota['variant'];
                            // Adiciona aos scripts para rodar
                            $scripts_para_rodar[] = [
                                'id' => $mapUniqueId,
                                'geo' => $rota['dados']['geo'],
                                'cor' => $rota['dados']['cor']
                            ];
                        ?>
                            <div id="<?php echo $mapUniqueId; ?>" style="height: 400px; width: 100%; display: block;" class="mapa-leaflet-alvo"></div>

                        <?php elseif ($rota['tipo'] === 'iframe'): ?>
                            <div class="embed-responsive embed-responsive-16by9">
                                <?php echo str_replace(['width="640"', 'height="480"'], ['width="100%"', 'height="400"'], $rota['dados']); ?>
                            </div>
                        <?php else: ?>
                            <div class="p-5 text-center text-muted bg-light">
                                <i class="fas fa-map-signs fa-3x mb-3 text-secondary"></i><br>
                                Mapa digital ou estático não cadastrado para esta variante (<?php echo $rota['variant']; ?>).
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="tab-pane fade" id="<?php echo $id_tab_pontos; ?>" role="tabpanel">
        <?php if (empty($pontos_unicos)): ?>
            <div class="alert alert-info text-center m-3">Nenhum ponto de destaque cadastrado.</div>
        <?php else: ?>
            <div class="row p-2">
                <?php foreach ($pontos_unicos as $ponto): 
                    $img = !empty($ponto['imagem_path']) ? "img/pontos/" . $ponto['imagem_path'] : "img/pontos/default.png";
                    $url = (file_exists("../../" . $img) || file_exists($img)) ? $img : "img/pontos/default.png";
                    $titulo_safe = addslashes($ponto['local_nome']);
                ?>
                <div class="col-6 col-md-3 mb-3">
                    <div class="card h-100 shadow-sm border-0 bg-white">
                        <div class="position-relative">
                            
                            <?php if (!empty($ponto['linha'])): ?>
                                <span class="position-absolute badge badge-primary m-1" style="top:0; left:0; z-index: 10;">
                                    Linha <?php echo $ponto['linha']; ?>
                                </span>
                            <?php endif; ?>

                            <img src="<?php echo $url; ?>" class="card-img-top" 
                                 style="height: 160px; object-fit: cover; cursor: pointer;" 
                                 onclick="abrirZoom(this.src, '<?php echo $titulo_safe; ?>')">
                        </div>
                        
                        <div class="card-body p-2 text-center d-flex flex-column">
                            <h6 class="text-dark font-weight-bold d-block text-truncate mb-1" title="<?php echo $ponto['local_nome']; ?>">
                                <?php echo $ponto['local_nome']; ?>
                            </h6>
                            
                            <?php if(!empty($ponto['descricao'])): ?>
                                <small class="d-block text-muted mb-2" style="line-height: 1.2;">
                                    <?php echo $ponto['descricao']; ?>
                                </small>
                            <?php endif; ?>

                            <?php if(!empty($ponto['coordenadas'])): ?>
                                <div class="mt-auto pt-2">
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $ponto['coordenadas']; ?>" target="_blank" class="btn btn-outline-primary btn-sm btn-block">
                                        <i class="fas fa-map-marked-alt"></i> Ver no Mapa
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($scripts_para_rodar)): ?>
    <div id="dados-mapas-<?php echo $wid_safe; ?>" style="display:none;" class="dados-mapas-json">
        <?php echo json_encode($scripts_para_rodar); ?>
    </div>

    <img src="x" style="display:none" onerror="(function(img){
        // Evita rodar duas vezes
        if(img.dataset.rodou) return;
        img.dataset.rodou = 'true';

        console.log('-> Gatilho JS ativado via Img Error!');
        
        // Pega o JSON da div acima
        var divDados = document.getElementById('dados-mapas-<?php echo $wid_safe; ?>');
        if(!divDados) return;

        var configs = JSON.parse(divDados.textContent);
        var instancias = [];

        // Função de inicialização
        function initMapa(cfg) {
            var el = document.getElementById(cfg.id);
            if (!el || el._leaflet_id) return null; // Já existe ou não encontrado

            console.log('Criando mapa:', cfg.id);
            var map = L.map(cfg.id).setView([-23.3045, -51.1696], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);

            var poly = L.polyline(cfg.geo, { color: cfg.cor, weight: 5, opacity: 0.8 }).addTo(map);
            map.fitBounds(poly.getBounds());
            
            return { map: map, poly: poly, id: cfg.id };
        }

        // Tenta iniciar agora (caso a aba já esteja visível)
        setTimeout(function(){
            configs.forEach(function(c){ 
                var obj = initMapa(c);
                if(obj) instancias.push(obj);
            });
        }, 300);

        // Ouve cliques nas abas para recalcular tamanho (Fix tela cinza)
        // Usamos delegate no 'body' para garantir que pegamos o evento
        $('body').on('shown.bs.tab', 'a[data-toggle=\'pill\']', function(e) {
            // 1. Tenta criar mapas que estavam ocultos
            configs.forEach(function(c){ 
                var obj = initMapa(c);
                if(obj) instancias.push(obj);
            });
            
            // 2. Atualiza tamanho dos existentes
            instancias.forEach(function(obj) {
                if(obj.map) {
                    obj.map.invalidateSize();
                    if(obj.poly) obj.map.fitBounds(obj.poly.getBounds());
                }
            });
        });

    })(this);">
<?php endif; ?>