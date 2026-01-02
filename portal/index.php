<?php
// index.php (v16 - Filtro Publicado e Correção Linha Texto)

$page_title = 'Início';
require_once 'header.php';
?>

    <main class="container mt-4">
        <section id="boas-vindas">
             <div class="jumbotron"> <h1 class="display-4"> Bem-vindo<?php if ($usuario_logado) echo ', ' . htmlspecialchars(explode(' ', $nome_usuario)[0]); ?>! </h1> <p class="lead"> <?php if ($usuario_logado): ?> Acesse suas informações e horários. Utilize o menu acima. <?php else: ?> Encontre informações sobre tabela horária, notícias e faça login para ver sua escala. <?php endif; ?> </p> </div>
        </section>

        <section id="busca-rapida" class="mb-4">
            <h2>Buscar Tabela Horária</h2>
            <div class="card">
                <div class="card-body"> <form id="form-busca-linha" novalidate> <div class="form-row"> <div class="form-group col-md-4"> <label for="numeroLinha">Número da Linha</label> <input type="text" inputmode="numeric" pattern="\d{3}" maxlength="3" class="form-control" id="numeroLinha" placeholder="Ex: 214" required> <div id="linha-error-msg" class="text-danger small mt-1"></div> </div> </div> <button type="submit" class="btn btn-primary">Buscar por Linha</button> </form> </div>
                <div class="card-body"> <form id="form-busca-workid" novalidate> <div class="form-row"> <div class="form-group col-md-4"> <label for="workid">WorkID</label> <input type="text" inputmode="numeric" pattern="\d{7,8}" maxlength="8" class="form-control" id="workid" placeholder="Ex: 2700101" required> <div id="workid-error-msg" class="text-danger small mt-1"></div> </div> </div> <button type="submit" class="btn btn-primary">Buscar por WorkID</button> </form> </div>
                <div id="resultados-busca" class="card-footer" style="display: none;">
                <h4><i class="fas fa-search"></i> Resultados: <span id="titulo-resultado-linha"></span></h4>
    
                <div id="container-botoes-data" class="d-flex flex-wrap gap-2 mb-3 mt-3">
                </div>

                <div id="feedback-busca" class="text-center my-3" style="display:none;"></div>

                <div id="conteudo-tabela-horaria" class="table-responsive">
                </div>
            </div>
            </div>
        </section>

        <section id="noticias" class="mb-4">
             <h2>Notícias Recentes</h2>
             <div class="row">
                 <?php if ($pdo): try { $sql_noticias = "SELECT id, titulo, resumo, data_publicacao FROM noticias WHERE status = 'publicada' ORDER BY data_publicacao DESC LIMIT 3"; $stmt_noticias = $pdo->query($sql_noticias); if ($stmt_noticias->rowCount() > 0) { while ($noticia = $stmt_noticias->fetch(PDO::FETCH_ASSOC)) { ?> <div class="col-md-4 mb-3"> <div class="card h-100"> <div class="card-body d-flex flex-column"> <h5 class="card-title"><?php echo htmlspecialchars($noticia['titulo']); ?></h5> <p class="card-text"><?php echo htmlspecialchars($noticia['resumo']); ?></p> <p class="card-text mt-auto"> <small class="text-muted">Publ: <?php $data = new DateTime($noticia['data_publicacao']); echo $data->format('d/m/y H:i'); ?></small></p> <a href="ver_noticia.php?id=<?php echo $noticia['id']; ?>" class="btn btn-secondary mt-2">Leia mais</a> </div> </div> </div> <?php } } else { echo "<div class='col-12'><p class='text-info'>Nenhuma notícia recente.</p></div>"; } } catch (PDOException $e) { error_log("Erro Notícias: " . $e->getMessage()); echo "<div class='col-12'><p class='text-danger'>Erro ao carregar notícias.</p></div>"; } else: echo "<div class='col-12'><p class='text-warning'>Notícias indisponíveis.</p></div>"; endif; ?>
             </div>
             <div class="text-center mt-3"> <a href="todas_noticias.php" class="btn btn-outline-primary">Ver todas as notícias</a> </div>
         </section>

        <section id="minha-escala" class="mb-4" <?php if (!$usuario_logado) echo 'style="display: none;"'; ?>>
            <h2>Minha Escala</h2>
            <div class="card">
                 <div class="card-header">
                     <ul class="nav nav-tabs card-header-tabs" id="minhaEscalaPrincipalTab" role="tablist">
                        <li class="nav-item"> <a class="nav-link active" id="escala-principal-tab" data-toggle="tab" href="#escala-principal-content" role="tab"><i class="fas fa-calendar-alt"></i> Escala</a> </li>
                        <li class="nav-item"> <a class="nav-link" id="mensagens-tab" data-toggle="tab" href="#mensagens-content" role="tab"><i class="fas fa-envelope"></i> Mensagens <?php if (isset($unread_message_count) && $unread_message_count > 0){ echo ' <span id="mensagens-count-badge" class="badge badge-danger badge-pill">'.$unread_message_count.'</span>'; } ?></a> </li>
                        <li class="nav-item"> <a class="nav-link" id="senha-tab" data-toggle="tab" href="#senha-content" role="tab"><i class="fas fa-key"></i> Trocar Senha</a> </li>
                     </ul>
                 </div>

                 <div class="card-body"> <div class="tab-content" id="minhaEscalaPrincipalTabContent">

                        <div class="tab-pane fade show active" id="escala-principal-content" role="tabpanel" aria-labelledby="escala-principal-tab">
                            <?php
                            $resumo_semanal_completo = [];
                            $data_selecionada_contexto = date('Y-m-d');
                            $erro_busca_semana = false;
                            $data_inicio_semana = null;
                            $data_fim_semana = null;

                            if ($usuario_logado && $pdo !== null) {
                                $motorista_id_logado = $_SESSION['user_id'];
                                if (isset($_GET['data_escala']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_escala'])) {
                                    $data_selecionada_contexto = $_GET['data_escala'];
                                }
                                try {
                                    $ts_selecionado = strtotime($data_selecionada_contexto);
                                    if($ts_selecionado === false) throw new Exception('Data inválida para Minha Escala.');
                                    $dia_semana_num = date('N', $ts_selecionado);
                                    $data_inicio_semana = date('Y-m-d', strtotime('-'.($dia_semana_num-1).' days', $ts_selecionado));
                                    $data_fim_semana = date('Y-m-d', strtotime('+'.(7-$dia_semana_num).' days', $ts_selecionado));

                                    // QUERY ATUALIZADA: Filtro de Publicação e Coluna Linha Direta
                                    $sql_semana = "SELECT
                                                    esc.id AS escala_id_db, esc.data, esc.work_id, esc.eh_extra,
                                                    esc.tabela_escalas AS numero_tabela_diario,
                                                    esc.funcao_operacional_id, 
                                                    esc.linha_origem_id as numero_linha, -- Coluna texto direta
                                                    fo.nome_funcao AS nome_da_funcao, 
                                                    loc_ini.nome as local_inicio, loc_fim.nome as local_fim,
                                                    esc.hora_inicio_prevista, esc.hora_fim_prevista
                                                 FROM motorista_escalas AS esc
                                                 -- REMOVIDO JOIN COM LINHAS
                                                 LEFT JOIN funcoes_operacionais AS fo ON esc.funcao_operacional_id = fo.id 
                                                 LEFT JOIN locais AS loc_ini ON esc.local_inicio_turno_id = loc_ini.id
                                                 LEFT JOIN locais AS loc_fim ON esc.local_fim_turno_id = loc_fim.id
                                                 WHERE esc.motorista_id = :motorista_id
                                                   AND esc.data BETWEEN :data_inicio AND :data_fim
                                                   AND esc.escala_publicada = 1 -- APENAS ESCALAS PUBLICADAS
                                                 ORDER BY esc.data ASC, esc.hora_inicio_prevista ASC";

                                    $stmt_semana = $pdo->prepare($sql_semana);
                                    $stmt_semana->bindParam(':motorista_id', $motorista_id_logado, PDO::PARAM_INT);
                                    $stmt_semana->bindParam(':data_inicio', $data_inicio_semana, PDO::PARAM_STR);
                                    $stmt_semana->bindParam(':data_fim', $data_fim_semana, PDO::PARAM_STR);
                                    $stmt_semana->execute();
                                    $resumo_semanal_completo = $stmt_semana->fetchAll(PDO::FETCH_ASSOC);

                                } catch (Exception $e) {
                                    error_log("Erro Minha Escala (Planejada): " . $e->getMessage());
                                    $erro_busca_semana = true;
                                }
                            }
                            ?>

                            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                                <h4 class='mb-0 mr-3'>Resumo da Semana (<?php echo isset($data_inicio_semana) ? date('d/m', strtotime($data_inicio_semana)) . " a " . date('d/m', strtotime($data_fim_semana)) : date('d/m/Y'); ?>)</h4>
                                <form action="index.php#minha-escala" method="GET" class="form-inline">
                                    <label for="data_escala_input" class="mr-2"><small>Ver semana de:</small></label>
                                    <input type="date" name="data_escala" id="data_escala_input" class="form-control form-control-sm mr-2" value="<?php echo htmlspecialchars($data_selecionada_contexto); ?>" onchange="this.form.submit()">
                                </form>
                            </div>
                            <?php if($erro_busca_semana): ?>
                                <p class='text-danger'>Erro ao carregar o resumo da sua escala planejada.</p>
                            <?php elseif ($usuario_logado && $pdo !== null): ?>
                                <div class='table-responsive mb-4'>
                                    <table class='table table-sm table-bordered text-center table-hover'>
                                        <thead class='thead-light'><tr><th>Data</th><th>Linha / Função</th><th>Tabela</th><th>WorkID</th><th>Início Pega</th><th>Hora Início</th><th>Hora Final</th><th>Final Pega</th></tr></thead>
                                        <tbody>
                                        <?php
                                        $dias_pt = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
                                        $datas_processadas = [];
                                        if (empty($resumo_semanal_completo)) {
                                            echo "<tr><td colspan='8' class='text-info'>Nenhuma escala publicada encontrada para esta semana.</td></tr>";
                                        } else {
                                            foreach ($resumo_semanal_completo as $escala_dia) {
                                                $data_atual_loop = $escala_dia['data'];
                                                $timestamp_loop = strtotime($data_atual_loop);
                                                $dia_num_semana_loop = (int)date('w', $timestamp_loop);
                                                $nome_dia_semana_loop = $dias_pt[$dia_num_semana_loop] ?? '?';
                                                $data_formatada_loop = date('d/m', $timestamp_loop) . ' (' . $nome_dia_semana_loop . ')';

                                                $classe_linha_dinamica = '';
                                                $work_id_upper = strtoupper($escala_dia['work_id'] ?? '');
                                                $status_especiais_motorista = ['FOLGA', 'FALTA', 'FORADEESCALA', 'FÉRIAS', 'ATESTADO'];
                                                $eh_status_especial = in_array($work_id_upper, $status_especiais_motorista);
                                                
                                                $eh_funcao_operacional = !empty($escala_dia['funcao_operacional_id']);
                                                $linha_clicavel = (!$eh_status_especial && !$eh_funcao_operacional); // Só é clicável se NÃO for status especial E NÃO for função
                                                
                                                $estilo_clique_e_data_attrs = "";

                                                if ($linha_clicavel) { 
                                                    $classe_linha_dinamica .= ' escala-row'; 
                                                    if ($data_atual_loop === $data_selecionada_contexto) {
                                                        $classe_linha_dinamica .= ' table-active';
                                                    }
                                                    $estilo_clique_e_data_attrs = " style='cursor: pointer;' data-workid='" . htmlspecialchars($escala_dia['work_id'] ?? '') . "' data-date='" . $data_atual_loop . "' title='Ver detalhes da escala'";
                                                } else { 
                                                    $titulo_hover = '';
                                                    if ($eh_status_especial) {
                                                        $titulo_hover = htmlspecialchars($work_id_upper);
                                                    } elseif ($eh_funcao_operacional && !empty($escala_dia['nome_da_funcao'])) {
                                                        $titulo_hover = htmlspecialchars($escala_dia['nome_da_funcao']);
                                                    } elseif ($eh_funcao_operacional) {
                                                        $titulo_hover = 'Função Operacional';
                                                    }
                                                    $estilo_clique_e_data_attrs = !empty($titulo_hover) ? " title='" . $titulo_hover . "'" : "";
                                                }
                                                
                                                if (!$eh_status_especial && !empty($escala_dia['eh_extra']) && $escala_dia['eh_extra'] == 1) {
                                                    $classe_linha_dinamica .= ' table-warning'; 
                                                }

                                                echo "<tr class='" . trim($classe_linha_dinamica) . "'" . $estilo_clique_e_data_attrs . ">";

                                                if (!isset($datas_processadas[$data_atual_loop])) {
                                                    $rowspan_count = 0;
                                                    foreach ($resumo_semanal_completo as $check_escala) {
                                                        if ($check_escala['data'] === $data_atual_loop) {
                                                            $rowspan_count++;
                                                        }
                                                    }
                                                    echo "<td" . ($rowspan_count > 1 ? " rowspan='{$rowspan_count}'" : "") . " class='align-middle'>" . $data_formatada_loop . "</td>";
                                                    $datas_processadas[$data_atual_loop] = true;
                                                }

                                                if ($eh_status_especial) { 
                                                    $texto_status = '';
                                                    if ($work_id_upper === 'FOLGA') $texto_status = 'FOLGA';
                                                    elseif ($work_id_upper === 'FÉRIAS') $texto_status = 'FÉRIAS';
                                                    elseif ($work_id_upper === 'ATESTADO') $texto_status = 'ATESTADO MÉDICO';
                                                    elseif ($work_id_upper === 'FALTA') $texto_status = 'FALTA';
                                                    elseif ($work_id_upper === 'FORADEESCALA') $texto_status = 'FORA DE ESCALA';
                                                    
                                                    $classe_cor_status = 'text-muted'; 
                                                    if ($work_id_upper === 'FOLGA' || $work_id_upper === 'FÉRIAS') $classe_cor_status = 'text-success';
                                                    elseif ($work_id_upper === 'ATESTADO') $classe_cor_status = 'text-warning'; 
                                                    elseif ($work_id_upper === 'FALTA' || $work_id_upper === 'FORADEESCALA') $classe_cor_status = 'text-danger';

                                                    echo "<td colspan='7' class='{$classe_cor_status} font-weight-bold align-middle'>{$texto_status}</td>";
                                                } else { // Se não for status especial (pode ser linha ou função)
                                                    $display_linha_funcao = '-';
                                                    if ($eh_funcao_operacional && !empty($escala_dia['nome_da_funcao'])) {
                                                        $display_linha_funcao = htmlspecialchars($escala_dia['nome_da_funcao']);
                                                    } elseif (!empty($escala_dia['numero_linha'])) {
                                                        // MOSTRA DIRETO O NÚMERO DA LINHA (SEM BUSCAR NOME)
                                                        $display_linha_funcao = "Linha " . htmlspecialchars($escala_dia['numero_linha']);
                                                    }
                                                    echo "<td>" . $display_linha_funcao . "</td>";
                                                    echo "<td>" . ($eh_funcao_operacional ? '-' : htmlspecialchars($escala_dia['numero_tabela_diario'] ?? '-')) . "</td>";
                                                    
                                                    echo "<td>" . htmlspecialchars($escala_dia['work_id'] ?? '-') . "</td>";
                                                    echo "<td>" . htmlspecialchars($escala_dia['local_inicio'] ?? '-') . "</td>";
                                                    echo "<td>" . ($escala_dia['hora_inicio_prevista'] ? date('H:i', strtotime($escala_dia['hora_inicio_prevista'])) : '-') . "</td>";
                                                    echo "<td>" . ($escala_dia['hora_fim_prevista'] ? date('H:i', strtotime($escala_dia['hora_fim_prevista'])) : '-') . "</td>";
                                                    echo "<td>" . htmlspecialchars($escala_dia['local_fim'] ?? '-') . "</td>";
                                                }
                                                echo "</tr>";
                                            }
                                        }
                                        ?>
                                        </tbody>
                                    </table>
                                </div>
                                <hr>
                            <?php else: ?>
                                <div class="alert alert-info" role="alert">
                                    <i class="fas fa-info-circle"></i> Faça login para visualizar sua escala planejada.
                                </div>
                                <hr>
                            <?php endif; ?>

                            <div class="mb-2"> <h5 class="mb-0 mr-3">Detalhes da Escala Selecionada</h5> </div>
                            <div id="daily-details-placeholder" class="mt-3 border p-2 mb-3 min-vh-50 bg-light">
                                <p class="text-muted p-3 text-center">
                                    <?php
                                    if ($usuario_logado) {
                                        echo "Clique em uma linha de TRABALHO (que não seja Folga, Férias, Função, etc.) do resumo acima para ver os detalhes aqui.";
                                    } else {
                                        echo "Faça login para interagir com a escala.";
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="mensagens-content" role="tabpanel" aria-labelledby="mensagens-tab">
                            <h4>Suas Mensagens</h4> <hr>
                             <div id="mensagens-lista"> <p class="text-muted p-3">Carregando mensagens...</p> </div>
                        </div>
                        <div class="tab-pane fade" id="senha-content" role="tabpanel" aria-labelledby="senha-tab">
                            <h4>Trocar Sua Senha</h4> <hr>
                            <?php if(isset($_SESSION['senha_success'])){echo '<div class="alert alert-success alert-dismissible fade show">'.htmlspecialchars($_SESSION['senha_success']).'<button type="button" class="close" data-dismiss="alert">&times;</button></div>';unset($_SESSION['senha_success']);} if(isset($_SESSION['senha_error'])){echo '<div class="alert alert-danger alert-dismissible fade show">'.htmlspecialchars($_SESSION['senha_error']).'<button type="button" class="close" data-dismiss="alert">&times;</button></div>';unset($_SESSION['senha_error']);} ?>
                            <form action="processa_troca_senha.php" method="POST" id="form-trocar-senha"> <div class="form-group row"><label for="senha_atual" class="col-md-3 col-form-label">Senha Atual:</label><div class="col-md-6"><input type="password" class="form-control" id="senha_atual" name="senha_atual" required></div></div> <div class="form-group row"><label for="nova_senha" class="col-md-3 col-form-label">Nova Senha:</label><div class="col-md-6"><input type="password" class="form-control" id="nova_senha" name="nova_senha" required minlength="6"><small class="form-text text-muted">Mín. 6 caracteres.</small></div></div> <div class="form-group row"><label for="confirma_nova_senha" class="col-md-3 col-form-label">Confirmar Nova Senha:</label><div class="col-md-6"><input type="password" class="form-control" id="confirma_nova_senha" name="confirma_nova_senha" required minlength="6"></div></div> <div class="form-group row"><div class="col-md-9 offset-md-3"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Alterar Senha</button></div></div> </form>
                        </div>
                     </div>
                 </div>
            </div>
        </section>

        <section id="prompt-login" class="mb-4" <?php if ($usuario_logado) echo 'style="display: none;"'; ?>>
            <div class="alert alert-info" role="alert">
                 <a href="#" data-toggle="modal" data-target="#loginModal">Faça login</a> para ver seus horários.
            </div>
        </section>

    </main>

<?php require_once 'footer.php'; ?>

<script>
    const initialSelectedDateContext = <?php echo json_encode($data_selecionada_contexto ?? date('Y-m-d')); ?>;

    // --- LÓGICA DE CLIQUE NA ESCALA (Minha Escala) ---
    document.addEventListener("DOMContentLoaded", function() {
        
        // Seleciona todas as linhas clicáveis da escala
        const linhasEscala = document.querySelectorAll('.escala-row');
        const containerDetalhes = document.getElementById('daily-details-placeholder');

        linhasEscala.forEach(linha => {
            linha.addEventListener('click', function() {
                // 1. Visual: Marca a linha como ativa
                linhasEscala.forEach(l => l.classList.remove('table-active'));
                this.classList.add('table-active');

                // 2. Dados: Pega WorkID e Data
                const workId = this.getAttribute('data-workid');
                const dataEscala = this.getAttribute('data-date');

                // 3. Feedback de Carregamento
                containerDetalhes.innerHTML = `
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2 text-muted">Carregando Diário de Bordo (${workId})...</div>
                    </div>`;
                
                // Scroll suave até os detalhes
                containerDetalhes.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                // 4. Chamada AJAX ao Backend (Reutiliza a lógica v33)
                // Usamos 'buscar_detalhes' direto, pois já temos a data certa
                fetch(`buscar_horario.php?acao=buscar_detalhes&termo=${workId}&data=${dataEscala}`)
                    .then(response => response.json())
                    .then(resp => {
                        if (resp.erro) {
                            containerDetalhes.innerHTML = `<div class="alert alert-danger m-3">${resp.msg}</div>`;
                        } else if (resp.abas && resp.abas.length > 0) {
                            // Sucesso: Exibe o conteúdo da primeira aba (WorkID único)
                            // Removemos as abas de navegação se for único, ou mantemos se preferir
                            // Como é escala específica, pegamos o conteúdo direto
                            containerDetalhes.innerHTML = resp.abas[0].conteudo;
                        } else {
                            containerDetalhes.innerHTML = `<div class="alert alert-warning m-3">Nenhuma viagem encontrada para este WorkID nesta data.</div>`;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        containerDetalhes.innerHTML = `<div class="alert alert-danger m-3">Erro de conexão ao buscar detalhes.</div>`;
                    });
            });
        });
    });
	
	// ==========================================
    // LÓGICA DE MENSAGENS (Adicione isto ao final do seu script)
    // ==========================================
    
    // Variável de controle para não carregar toda vez que clica
    let mensagensJaCarregadas = false;

    // Escuta o evento do Bootstrap quando a aba é mostrada
    // Usa jQuery pois o Bootstrap 4 depende dele
    $('#mensagens-tab').on('shown.bs.tab', function (e) {
        if (!mensagensJaCarregadas) {
            carregarListaMensagens();
        }
    });

    function carregarListaMensagens() {
        const containerMsg = document.getElementById('mensagens-lista');
        
        containerMsg.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Buscando mensagens...</div></div>';

        // CORREÇÃO: Caminho aponta para a pasta 'parts/' e o nome do arquivo correto
        fetch('parts/listar_mensagens.php') 
            .then(response => {
                if (!response.ok) throw new Error('Erro na rede');
                return response.text();
            })
            .then(html => {
                containerMsg.innerHTML = html;
                mensagensJaCarregadas = true; 
                
                // Adiciona o listener de clique para marcar como lida (agora que o HTML existe)
                // Usamos delegação de evento no container para pegar os cliques nos itens novos
                $('#mensagens-accordion').on('show.bs.collapse', function (e) {
                    const targetId = e.target.id; // ex: collapse-msg-123
                    const trigger = document.querySelector(`[data-target="#${targetId}"]`);
                    
                    if (trigger && trigger.classList.contains('mensagem-nao-lida')) {
                        const msgId = trigger.getAttribute('data-msg-id');
                        marcarMensagemComoLida(msgId, trigger, e.target);
                    }
                });
            })
            .catch(err => {
                console.error('Erro mensagens:', err);
                containerMsg.innerHTML = '<div class="alert alert-danger m-3">Não foi possível carregar as mensagens.</div>';
            });
    }

    // Função para marcar como lida via AJAX
    function marcarMensagemComoLida(msgId, triggerElement, contentElement) {
        // CORREÇÃO: Caminho para o arquivo na pasta 'parts/'
        $.post('parts/marcar_mensagem_lida.php', { msg_id: msgId }, function(resp) {
            if(resp.success) {
                // Atualiza visualmente para "Lida"
                triggerElement.classList.remove('list-group-item-primary', 'mensagem-nao-lida', 'font-weight-bold');
                
                // Atualiza ícone e texto de status no cabeçalho
                const statusSpan = triggerElement.querySelector('.msg-status');
                if(statusSpan) statusSpan.innerHTML = '<i class="fas fa-check-circle text-success"></i> Lida';
                
                // Atualiza detalhe interno
                const detailSpan = contentElement.querySelector('.msg-status-detail');
                if(detailSpan) detailSpan.innerText = 'Lida agora.';
                
                // Opcional: Atualizar badge global
                // ...
            }
        }, 'json');
    }
</script>