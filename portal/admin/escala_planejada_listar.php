<?php
// admin/escala_planejada_listar.php
// ATUALIZADO: Ajustado para nova estrutura (Linha Texto direto na tabela)

require_once 'auth_check.php';

$niveis_permitidos_ver_escala_planejada = ['Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_ver_escala_planejada)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para acessar a Escala Planejada.";
    header('Location: index.php');
    exit;
}

require_once '../db_config.php';
$page_title = 'Consultar/Gerenciar Escala Planejada';

$dias_semana_pt_map = [
    'Sun' => 'Dom', 'Mon' => 'Seg', 'Tue' => 'Ter', 'Wed' => 'Qua',
    'Thu' => 'Qui', 'Fri' => 'Sex', 'Sat' => 'Sáb'
];

require_once 'admin_header.php';

$escalas_por_pagina = 25;
$pagina_atual = isset($_GET['pagina']) && filter_var($_GET['pagina'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $escalas_por_pagina;

$filtro_data_obrigatoria = isset($_GET['data_escala']) ? trim($_GET['data_escala']) : date('Y-m-d');
$filtro_tipo_busca_adicional = isset($_GET['tipo_busca_adicional']) ? trim($_GET['tipo_busca_adicional']) : 'todos_data';
$filtro_valor_busca_adicional = isset($_GET['valor_busca_adicional']) ? trim($_GET['valor_busca_adicional']) : '';

$escalas_planejadas = [];
$total_escalas = 0;
$total_paginas_escalas = 0;
$erro_busca_escalas = false;

$pode_adicionar_escala = in_array($admin_nivel_acesso_logado, ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador']);
$pode_editar_escala = in_array($admin_nivel_acesso_logado, ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador']);
$pode_excluir_escala = in_array($admin_nivel_acesso_logado, ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador']);
$pode_copiar_escala_dia_inteiro = in_array($admin_nivel_acesso_logado, ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador']);


$linhas_para_filtro = [];
$funcoes_para_filtro = [];

$linhas_para_filtro = [];
$funcoes_para_filtro = [];

if ($pdo) {
    try {
        // CORREÇÃO: Busca linhas do histórico de viagens para o filtro lateral
        $stmt_linhas = $pdo->query("SELECT DISTINCT ROUTE_ID as numero, '' as nome 
                                    FROM relatorios_viagens 
                                    WHERE ROUTE_ID IS NOT NULL AND ROUTE_ID != '' 
                                    ORDER BY ROUTE_ID ASC");
        $linhas_para_filtro = $stmt_linhas->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt_funcoes = $pdo->query("SELECT id, nome_funcao FROM funcoes_operacionais WHERE status = 'ativa' ORDER BY nome_funcao ASC");
        $funcoes_para_filtro = $stmt_funcoes->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) { 
        // Silencioso, apenas o filtro ficará vazio se der erro
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($pode_adicionar_escala): ?>
        <a href="escala_planejada_formulario.php" class="btn btn-success">
            <i class="fas fa-calendar-plus"></i> Adicionar Nova Escala</a>
        <?php endif; ?>
    </div>
</div>

<?php
if (isset($_SESSION['admin_success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_success_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_success_message']); }
if (isset($_SESSION['admin_error_message'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_error_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_error_message']); }
if (isset($_SESSION['admin_warning_message'])) { echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_warning_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_warning_message']); }
?>

<?php if ($pode_copiar_escala_dia_inteiro): ?>
<fieldset class="mb-4 border p-3 rounded bg-light shadow-sm">
    <legend class="w-auto px-2 h5 text-info">Copiar Escala Planejada de um Dia para Outro</legend>
    <form id="formCopiarEscalaDia" action="javascript:void(0);">
        <div class="form-row align-items-end">
            <div class="form-group col-md-4">
                <label for="data_origem_copia_dia">Copiar da Data:</label>
                <input type="date" class="form-control form-control-sm" id="data_origem_copia_dia" name="data_origem_copia_dia" value="<?php echo date('Y-m-d', strtotime('-1 day', strtotime($filtro_data_obrigatoria))); ?>" required>
            </div>
            <div class="form-group col-md-4">
                <label for="data_destino_copia_dia">Para a Data:</label>
                <input type="date" class="form-control form-control-sm" id="data_destino_copia_dia" name="data_destino_copia_dia" value="<?php echo htmlspecialchars($filtro_data_obrigatoria); ?>" required>
            </div>
            <div class="form-group col-md-4 d-flex align-items-end">
                <button type="button" class="btn btn-info btn-sm btn-block" id="btnExecutarCopiaDia">
                    <i class="fas fa-copy"></i> Copiar Escala Completa do Dia
                </button>
            </div>
        </div>
        <div class="form-group mt-2">
             <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="confirmar_substituicao_escala_destino">
                <label class="form-check-label text-danger" for="confirmar_substituicao_escala_destino">
                    <strong>Atenção:</strong> Marque para confirmar que QUALQUER escala planejada existente na DATA DE DESTINO será <strong>APAGADA</strong> antes da cópia.
                </label>
            </div>
        </div>
        <div id="copiar_escala_dia_feedback" class="small mt-1" style="min-height: 20px;"></div>
    </form>
</fieldset>
<?php endif; ?>


<form method="GET" action="escala_planejada_listar.php" class="mb-4 card card-body bg-light p-3 shadow-sm" id="formFiltroEscalaPlanejada">
    <div class="form-row align-items-end">
        <div class="col-md-3 form-group mb-md-0">
            <label for="data_escala_filtro">Data da Escala <span class="text-danger">*</span>:</label>
            <input type="date" class="form-control form-control-sm" id="data_escala_filtro" name="data_escala" value="<?php echo htmlspecialchars($filtro_data_obrigatoria); ?>" required>
        </div>
        <div class="col-md-3 form-group mb-md-0">
            <label for="tipo_busca_adicional_filtro">Busca Adicional por:</label>
            <select class="form-control form-control-sm" id="tipo_busca_adicional_filtro" name="tipo_busca_adicional">
                <option value="todos_data" <?php echo ($filtro_tipo_busca_adicional == 'todos_data') ? 'selected' : ''; ?>>Todos da Data</option>
                <option value="linha" <?php echo ($filtro_tipo_busca_adicional == 'linha') ? 'selected' : ''; ?>>Linha</option>
                <option value="funcao" <?php echo ($filtro_tipo_busca_adicional == 'funcao') ? 'selected' : ''; ?>>Função Operacional</option> 
                <option value="folgas" <?php echo ($filtro_tipo_busca_adicional == 'folgas') ? 'selected' : ''; ?>>Apenas Folgas</option>
                <option value="faltas" <?php echo ($filtro_tipo_busca_adicional == 'faltas') ? 'selected' : ''; ?>>Apenas Faltas</option>
                <option value="fora_escala" <?php echo ($filtro_tipo_busca_adicional == 'fora_escala') ? 'selected' : ''; ?>>Apenas Fora de Escala</option>
                <option value="ferias" <?php echo ($filtro_tipo_busca_adicional == 'ferias') ? 'selected' : ''; ?>>Apenas Férias</option>
                <option value="atestados" <?php echo ($filtro_tipo_busca_adicional == 'atestados') ? 'selected' : ''; ?>>Apenas Atestados</option>
                <option value="workid" <?php echo ($filtro_tipo_busca_adicional == 'workid') ? 'selected' : ''; ?>>WorkID</option>
                <option value="motorista" <?php echo ($filtro_tipo_busca_adicional == 'motorista') ? 'selected' : ''; ?>>Motorista (Nome/Matr.)</option>
            </select>
        </div>
        <div class="col-md-3 form-group mb-md-0" id="campoValorBuscaAdicionalWrapper">
            <label for="valor_busca_adicional_input_text">Valor Específico:</label>
            <input type="text" class="form-control form-control-sm d-none" id="valor_busca_adicional_input_text" name="valor_busca_adicional_text_disabled" value="<?php echo ($filtro_tipo_busca_adicional == 'workid' || $filtro_tipo_busca_adicional == 'motorista') ? htmlspecialchars($filtro_valor_busca_adicional) : ''; ?>" placeholder="WorkID ou Nome/Matr.">
            
            <select class="form-control form-control-sm d-none" id="valor_busca_adicional_select_linha" name="valor_busca_adicional_linha_disabled">
                <option value="">Selecione a linha...</option>
                <?php foreach($linhas_para_filtro as $linha_opt_filtro): ?>
                    <option value="<?php echo htmlspecialchars($linha_opt_filtro['numero']); ?>" <?php echo ($filtro_tipo_busca_adicional == 'linha' && $filtro_valor_busca_adicional == $linha_opt_filtro['numero']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($linha_opt_filtro['numero'] . ($linha_opt_filtro['nome'] ? ' - ' . $linha_opt_filtro['nome'] : '')); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select class="form-control form-control-sm d-none" id="valor_busca_adicional_select_funcao" name="valor_busca_adicional_funcao_disabled">
                <option value="">Selecione a função...</option>
                <?php foreach($funcoes_para_filtro as $funcao_opt_filtro): ?>
                    <option value="<?php echo $funcao_opt_filtro['id']; ?>" <?php echo ($filtro_tipo_busca_adicional == 'funcao' && $filtro_valor_busca_adicional == $funcao_opt_filtro['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($funcao_opt_filtro['nome_funcao']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1 form-group mb-md-0 align-self-end">
            <button type="submit" class="btn btn-sm btn-primary btn-block" title="Aplicar Filtros"><i class="fas fa-filter"></i> Filtrar</button>
        </div>
        <div class="col-md-2 form-group mb-md-0 align-self-end">
            <a href="escala_planejada_listar.php" class="btn btn-sm btn-outline-secondary btn-block" title="Limpar Filtros (Voltar para data atual)"><i class="fas fa-times"></i> Limpar</a>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoBuscaSelect = document.getElementById('tipo_busca_adicional_filtro');
    const campoValorWrapper = document.getElementById('campoValorBuscaAdicionalWrapper');
    const inputValorTexto = document.getElementById('valor_busca_adicional_input_text');
    const selectValorLinha = document.getElementById('valor_busca_adicional_select_linha');
    const selectValorFuncao = document.getElementById('valor_busca_adicional_select_funcao'); 

    function configurarCampoValor() {
        const tipoSelecionado = tipoBuscaSelect.value;
        inputValorTexto.classList.add('d-none');
        selectValorLinha.classList.add('d-none');
        selectValorFuncao.classList.add('d-none'); 
        
        inputValorTexto.name = 'valor_busca_adicional_text_disabled';
        selectValorLinha.name = 'valor_busca_adicional_linha_disabled';
        selectValorFuncao.name = 'valor_busca_adicional_funcao_disabled'; 
        campoValorWrapper.style.visibility = 'hidden';

        if (tipoSelecionado === 'linha') {
            selectValorLinha.classList.remove('d-none');
            selectValorLinha.name = 'valor_busca_adicional';
            campoValorWrapper.style.visibility = 'visible';
        } else if (tipoSelecionado === 'funcao') { 
            selectValorFuncao.classList.remove('d-none');
            selectValorFuncao.name = 'valor_busca_adicional';
            campoValorWrapper.style.visibility = 'visible';
        } else if (tipoSelecionado === 'workid' || tipoSelecionado === 'motorista') {
            inputValorTexto.classList.remove('d-none');
            inputValorTexto.name = 'valor_busca_adicional';
            inputValorTexto.placeholder = (tipoSelecionado === 'workid') ? 'Digite o WorkID' : 'Nome ou Matrícula';
            campoValorWrapper.style.visibility = 'visible';
        }
    }
    if(tipoBuscaSelect) {
        tipoBuscaSelect.addEventListener('change', configurarCampoValor);
        configurarCampoValor(); 
    }
});
</script>

<div class="card mb-4 border-info">
    <div class="card-body bg-light d-flex justify-content-between align-items-center py-2">
        <div>
            <h5 class="mb-0 text-info"><i class="fas fa-bullhorn"></i> Publicação da Escala</h5>
            <small class="text-muted">Motoristas só veem escalas publicadas.</small>
        </div>
        <form class="form-inline" id="formPublicar">
            <label class="mr-2">Data:</label>
            <input type="date" class="form-control mr-2" id="dataPublicacao" value="<?php echo htmlspecialchars($filtro_data_obrigatoria); ?>">
            
            <button type="button" class="btn btn-success mr-2" onclick="alterarPublicacao('publicar_dia')">
                <i class="fas fa-check-circle"></i> Publicar Dia
            </button>
            <button type="button" class="btn btn-outline-danger" onclick="alterarPublicacao('despublicar_dia')">
                <i class="fas fa-eye-slash"></i> Ocultar Dia
            </button>
        </form>
    </div>
</div>

<script>
function alterarPublicacao(acao) {
    const data = document.getElementById('dataPublicacao').value;
    if(!data) { alert('Selecione uma data.'); return; }
    
    const texto = acao === 'publicar_dia' ? 'PUBLICAR' : 'OCULTAR';
    if(!confirm(`Tem certeza que deseja ${texto} a escala do dia ${data}?`)) return;

    $.post('ajax_escala_publicar.php', { acao: acao, data: data }, function(resp) {
        alert(resp.msg);
        if(resp.sucesso) location.reload();
    }, 'json');
}
</script>

<div class="table-responsive">
    <table class="table table-striped table-sm table-hover">
        <thead class="thead-light">
            <tr>
                <th>Data</th>
                <th>Matrícula</th>
                <th>Nome</th>
                <th>Linha / Função</th> <th>Tabela</th>
                <th>WorkID</th>
                <th>Início Pega</th>
                <th>Hora Início</th>
                <th>Hora Final</th>
                <th>Fim Pega</th>
                <?php if ($pode_editar_escala || $pode_excluir_escala): ?>
                <th style="width: 120px;">Ações</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($pdo) {
                try {
                    // QUERY ATUALIZADA:
                    // 1. Removemos LEFT JOIN linhas (tabela foi depreciada/desvinculada)
                    // 2. Trazemos a coluna linha_origem_id direto (agora ela é texto, ex: "209")
                    
                    $sql_select_base = "SELECT esc.id, esc.data, esc.work_id, esc.tabela_escalas, esc.eh_extra,
                                            esc.hora_inicio_prevista, esc.hora_fim_prevista, esc.motorista_id,
                                            esc.funcao_operacional_id, 
                                            esc.linha_origem_id AS numero_linha_direto, /* Coluna direta */
                                            mot.nome AS nome_motorista, mot.matricula AS matricula_motorista,
                                            fo.nome_funcao AS nome_funcao_operacional,
                                            loc_ini.nome AS local_inicio_nome, loc_fim.nome AS local_fim_nome
                                       FROM motorista_escalas AS esc
                                       LEFT JOIN motoristas AS mot ON esc.motorista_id = mot.id
                                       /* LEFT JOIN linhas REMOVIDO */
                                       LEFT JOIN funcoes_operacionais AS fo ON esc.funcao_operacional_id = fo.id
                                       LEFT JOIN locais AS loc_ini ON esc.local_inicio_turno_id = loc_ini.id
                                       LEFT JOIN locais AS loc_fim ON esc.local_fim_turno_id = loc_fim.id";

                    $sql_count_base = "SELECT COUNT(esc.id) FROM motorista_escalas AS esc
                                       LEFT JOIN motoristas AS mot ON esc.motorista_id = mot.id
                                       LEFT JOIN funcoes_operacionais AS fo ON esc.funcao_operacional_id = fo.id";

                    $sql_where_conditions = [];
                    $sql_params_execute = [];

                    $sql_where_conditions[] = "esc.data = :data_f";
                    $sql_params_execute[':data_f'] = $filtro_data_obrigatoria;

                    // Filtros
                    if ($filtro_tipo_busca_adicional === 'linha' && !empty($filtro_valor_busca_adicional)) {
                        // Agora compara string direto com string
                        $sql_where_conditions[] = "esc.linha_origem_id = :valor_adicional_f";
                        $sql_params_execute[':valor_adicional_f'] = $filtro_valor_busca_adicional;
                    
                    } elseif ($filtro_tipo_busca_adicional === 'funcao' && !empty($filtro_valor_busca_adicional)) {
                        $sql_where_conditions[] = "esc.funcao_operacional_id = :valor_adicional_f_func";
                        $sql_params_execute[':valor_adicional_f_func'] = $filtro_valor_busca_adicional;
                    
                    } elseif ($filtro_tipo_busca_adicional === 'folgas') {
                        $sql_where_conditions[] = "UPPER(esc.work_id) = 'FOLGA'";
                    } elseif ($filtro_tipo_busca_adicional === 'faltas') {
                        $sql_where_conditions[] = "UPPER(esc.work_id) = 'FALTA'";
                    } elseif ($filtro_tipo_busca_adicional === 'fora_escala') {
                        $sql_where_conditions[] = "UPPER(esc.work_id) = 'FORADEESCALA'";
                    } elseif ($filtro_tipo_busca_adicional === 'ferias') {
                        $sql_where_conditions[] = "UPPER(esc.work_id) = 'FÉRIAS'";
                    } elseif ($filtro_tipo_busca_adicional === 'atestados') {
                        $sql_where_conditions[] = "UPPER(esc.work_id) = 'ATESTADO'";
                    } elseif ($filtro_tipo_busca_adicional === 'workid' && !empty($filtro_valor_busca_adicional)) {
                        $sql_where_conditions[] = "esc.work_id LIKE :valor_adicional_f";
                        $sql_params_execute[':valor_adicional_f'] = '%' . $filtro_valor_busca_adicional . '%';
                    } elseif ($filtro_tipo_busca_adicional === 'motorista' && !empty($filtro_valor_busca_adicional)) {
                         $sql_where_conditions[] = "(mot.nome LIKE :valor_adicional_f OR mot.matricula LIKE :valor_adicional_f_mat)";
                        $sql_params_execute[':valor_adicional_f'] = '%' . $filtro_valor_busca_adicional . '%';
                        $sql_params_execute[':valor_adicional_f_mat'] = '%' . $filtro_valor_busca_adicional . '%';
                    }

                    $sql_where_clause = "";
                    if (!empty($sql_where_conditions)) $sql_where_clause = " WHERE " . implode(" AND ", $sql_where_conditions);

                    $stmt_count_escalas = $pdo->prepare($sql_count_base . $sql_where_clause);
                    $stmt_count_escalas->execute($sql_params_execute);
                    $total_escalas = (int)$stmt_count_escalas->fetchColumn();
                    $total_paginas_escalas = ceil($total_escalas / $escalas_por_pagina);
                    if ($pagina_atual > $total_paginas_escalas && $total_paginas_escalas > 0) $pagina_atual = $total_paginas_escalas;
                    if ($pagina_atual < 1) $pagina_atual = 1;
                    $offset = ($pagina_atual - 1) * $escalas_por_pagina;

                    $stmt_select_escalas = $pdo->prepare($sql_select_base . $sql_where_clause . " ORDER BY esc.data DESC, mot.nome ASC, esc.hora_inicio_prevista ASC LIMIT :limit OFFSET :offset");
                    foreach ($sql_params_execute as $key => $value) $stmt_select_escalas->bindValue($key, $value);
                    $stmt_select_escalas->bindValue(':limit', $escalas_por_pagina, PDO::PARAM_INT);
                    $stmt_select_escalas->bindValue(':offset', $offset, PDO::PARAM_INT);
                    $stmt_select_escalas->execute();
                    $escalas_planejadas = $stmt_select_escalas->fetchAll(PDO::FETCH_ASSOC);


                    if ($escalas_planejadas) {
                        foreach ($escalas_planejadas as $escala) {
                            $work_id_upper = isset($escala['work_id']) ? strtoupper($escala['work_id']) : '';
                            $is_folga = ($work_id_upper === 'FOLGA');
                            $is_falta = ($work_id_upper === 'FALTA');
                            $is_fora_escala = ($work_id_upper === 'FORADEESCALA');
                            $is_ferias = ($work_id_upper === 'FÉRIAS');
                            $is_atestado = ($work_id_upper === 'ATESTADO');
                            $is_status_especial = $is_folga || $is_falta || $is_fora_escala || $is_ferias || $is_atestado;

                            $classe_linha_tr = '';
                            if (isset($escala['eh_extra']) && $escala['eh_extra'] == 1 && !$is_status_especial) {
                                $classe_linha_tr = 'table-row-extra';
                            } elseif ($is_falta || $is_fora_escala) {
                                $classe_linha_tr = 'table-row-problema';
                            } elseif ($is_folga || $is_ferias) {
                                $classe_linha_tr = 'table-success';
                            } elseif ($is_atestado) {
                                $classe_linha_tr = 'table-warning';
                            }
                            
                            echo "<tr class='{$classe_linha_tr}'>";

                            $timestamp_data = strtotime($escala['data']);
                            $dia_semana_ingles = date('D', $timestamp_data);
                            $dia_semana_portugues = $dias_semana_pt_map[$dia_semana_ingles] ?? $dia_semana_ingles;
                            $data_formatada = date('d/m/Y', $timestamp_data) . " ({$dia_semana_portugues})";

                            if ($is_status_especial) {
                                echo "<td>" . $data_formatada . "</td>";
                                echo "<td>" . htmlspecialchars($escala['matricula_motorista']) . "</td>";
                                echo "<td>" . htmlspecialchars($escala['nome_motorista']) . "</td>";
                                $colspan_status = 7;
                                $status_texto = '';
                                $status_classe_bg = $classe_linha_tr; 

                                if ($is_folga) { $status_texto = "✨ FOLGA ✨"; }
                                elseif ($is_falta) { $status_texto = "⚠️ FALTA ⚠️"; }
                                elseif ($is_fora_escala) { $status_texto = "🚫 FORA DE ESCALA 🚫"; }
                                elseif ($is_ferias) { $status_texto = "🏖️ FÉRIAS 🏖️"; }
                                elseif ($is_atestado) { $status_texto = "⚕️ ATESTADO ⚕️"; }
                                
                                echo "<td colspan='{$colspan_status}' class='text-center {$status_classe_bg} font-weight-bold py-2'>" . $status_texto . "</td>";
                                
                            } else { // Dia de trabalho normal ou extra
                                echo "<td>" . $data_formatada . (isset($escala['eh_extra']) && $escala['eh_extra'] == 1 ? ' <span class="text-danger font-italic small">(extra)</span>' : '') . "</td>";
                                echo "<td>" . htmlspecialchars($escala['matricula_motorista']) . "</td>";
                                echo "<td>" . htmlspecialchars($escala['nome_motorista']) . "</td>";
                                
                                // EXIBIÇÃO DA LINHA OU FUNÇÃO (Lógica Atualizada)
                                $display_linha_funcao = '-';
                                if (!empty($escala['funcao_operacional_id']) && !empty($escala['nome_funcao_operacional'])) {
                                    $display_linha_funcao = htmlspecialchars($escala['nome_funcao_operacional']);
                                } elseif (!empty($escala['numero_linha_direto'])) {
                                    // Mostra diretamente o valor salvo (ex: "209")
                                    $display_linha_funcao = "Linha " . htmlspecialchars($escala['numero_linha_direto']);
                                }
                                echo "<td>" . $display_linha_funcao . "</td>";
                                
                                echo "<td>" . htmlspecialchars($escala['tabela_escalas'] ?: '-') . "</td>";
                                echo "<td>" . htmlspecialchars($escala['work_id']) . "</td>";
                                echo "<td>" . htmlspecialchars($escala['local_inicio_nome'] ?: '-') . "</td>";
                                echo "<td>" . ($escala['hora_inicio_prevista'] ? date('H:i', strtotime($escala['hora_inicio_prevista'])) : '-') . "</td>";
                                echo "<td>" . ($escala['hora_fim_prevista'] ? date('H:i', strtotime($escala['hora_fim_prevista'])) : '-') . "</td>";
                                echo "<td>" . htmlspecialchars($escala['local_fim_nome'] ?: '-') . "</td>";
                            }
                            
                            if ($pode_editar_escala || $pode_excluir_escala) {
                                $params_acao = ['id' => $escala['id'], 'pagina' => $pagina_atual];
                                if (isset($_GET['data_escala'])) $params_acao['data_escala'] = $_GET['data_escala'];
                                if (isset($_GET['tipo_busca_adicional'])) $params_acao['tipo_busca_adicional'] = $_GET['tipo_busca_adicional'];
                                if (isset($_GET['valor_busca_adicional'])) $params_acao['valor_busca_adicional'] = $_GET['valor_busca_adicional'];
                                $query_string_acao = http_build_query($params_acao);

                                echo "<td class='action-buttons " . ($is_status_especial ? $status_classe_bg : '')  . "'>";
                                if ($pode_editar_escala) {
                                    echo "<a href='escala_planejada_formulario.php?" . $query_string_acao . "' class='btn btn-primary btn-sm' title='Editar Escala'><i class='fas fa-edit'></i></a> ";
                                }
                                if ($pode_excluir_escala) {
                                    echo "<a href='escala_planejada_acao.php?acao=excluir&" . $query_string_acao . "&token=" . uniqid('csrf_ep_del_',true) . "' class='btn btn-danger btn-sm' title='Excluir Escala' onclick='return confirm(\"Tem certeza que deseja excluir esta entrada para " . htmlspecialchars(addslashes($escala['nome_motorista'])) . "?\");'><i class='fas fa-trash-alt'></i></a>";
                                }
                                echo "</td>";

                            } elseif ($pode_editar_escala || $pode_excluir_escala) { 
                                echo "<td></td>";
                            }
                            echo "</tr>";
                        }
                    } else { 
                        $colspan_total = 10 + (($pode_editar_escala || $pode_excluir_escala) ? 1 : 0);
                        echo "<tr><td colspan='{$colspan_total}' class='text-center'>Nenhuma escala planejada encontrada para os filtros aplicados.</td></tr>"; 
                    }
                } catch (PDOException $e) { 
                    $colspan_total = 10 + (($pode_editar_escala || $pode_excluir_escala) ? 1 : 0);
                    echo "<tr><td colspan='{$colspan_total}' class='text-danger text-center'>Erro ao buscar escalas: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                    $erro_busca_escalas = true;
                }
            } else { 
                $colspan_total = 10 + (($pode_editar_escala || $pode_excluir_escala) ? 1 : 0);
                echo "<tr><td colspan='{$colspan_total}' class='text-danger text-center'>Falha na conexão com o banco de dados.</td></tr>";
                $erro_busca_escalas = true;
            }
            ?>
        </tbody>
    </table>
</div>

<?php if (!$erro_busca_escalas && $total_paginas_escalas > 1): ?>
    <nav aria-label="Navegação das escalas planejadas">
        <ul class="pagination justify-content-center mt-4">
            <?php
            $query_params_paginacao = $_GET; 
            unset($query_params_paginacao['pagina']); 
            $link_base_paginacao = 'escala_planejada_listar.php?' . http_build_query($query_params_paginacao) . (empty($query_params_paginacao) ? '' : '&');

            if ($pagina_atual > 1) {
                echo '<li class="page-item"><a class="page-link" href="' . $link_base_paginacao . 'pagina=1">Primeira</a></li>';
                echo '<li class="page-item"><a class="page-link" href="' . $link_base_paginacao . 'pagina=' . ($pagina_atual - 1) . '" aria-label="Anterior"><span aria-hidden="true">&laquo;</span></a></li>';
            } else {
                echo '<li class="page-item disabled"><span class="page-link">Primeira</span></li>';
                echo '<li class="page-item disabled"><span class="page-link" aria-label="Anterior"><span aria-hidden="true">&laquo;</span></span></li>';
            }

            $num_links_nav = 2;
            $inicio_nav = max(1, $pagina_atual - $num_links_nav);
            $fim_nav = min($total_paginas_escalas, $pagina_atual + $num_links_nav);

            if ($inicio_nav > 1) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }

            for ($i = $inicio_nav; $i <= $fim_nav; $i++) {
                echo '<li class="page-item ' . ($i == $pagina_atual ? 'active' : '') . '"><a class="page-link" href="' . $link_base_paginacao . 'pagina=' . $i . '">' . $i . '</a></li>';
            }

            if ($fim_nav < $total_paginas_escalas) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }

            if ($pagina_atual < $total_paginas_escalas) {
                echo '<li class="page-item"><a class="page-link" href="' . $link_base_paginacao . 'pagina=' . ($pagina_atual + 1) . '" aria-label="Próxima"><span aria-hidden="true">&raquo;</span></a></li>';
                echo '<li class="page-item"><a class="page-link" href="' . $link_base_paginacao . 'pagina=' . $total_paginas_escalas . '">Última</a></li>';
            } else {
                echo '<li class="page-item disabled"><span class="page-link" aria-label="Próxima"><span aria-hidden="true">&raquo;</span></span></li>';
                echo '<li class="page-item disabled"><span class="page-link">Última</span></li>';
            }
            ?>
        </ul>
    </nav>
<?php endif; ?>

<?php
// ... (Lógica JavaScript para formCopiarEscalaDia mantida) ...
ob_start();
?>
<script>
$(document).ready(function() {
    $('#btnExecutarCopiaDia').on('click', function() {
        var dataOrigem = $('#data_origem_copia_dia').val();
        var dataDestino = $('#data_destino_copia_dia').val();
        var confirmarSubstituicao = $('#confirmar_substituicao_escala_destino').is(':checked');
        var $feedbackDiv = $('#copiar_escala_dia_feedback');
        var $button = $(this);

        $feedbackDiv.html('');

        if (!dataOrigem || !dataDestino) {
            $feedbackDiv.html('<small class="text-danger">Por favor, selecione a Data de Origem e a Data de Destino.</small>');
            return;
        }
        if (dataOrigem === dataDestino) {
            $feedbackDiv.html('<small class="text-danger">A Data de Origem e a Data de Destino não podem ser iguais.</small>');
            return;
        }
        if (!confirmarSubstituicao) {
            $feedbackDiv.html('<small class="text-danger">Você DEVE marcar a caixa de confirmação para prosseguir.</small>');
             $('#confirmar_substituicao_escala_destino').focus();
            return;
        }

        var dataOrigemFormatada = new Date(dataOrigem + 'T00:00:00').toLocaleDateString('pt-BR', { timeZone: 'America/Sao_Paulo' });
        var dataDestinoFormatada = new Date(dataDestino + 'T00:00:00').toLocaleDateString('pt-BR', { timeZone: 'America/Sao_Paulo' });

        if (!confirm("CONFIRMAÇÃO FINAL:\n\nVocê está prestes a COPIAR todas as escalas planejadas do dia " + 
                     dataOrigemFormatada + " para o dia " + dataDestinoFormatada + ".\n\n" + 
                     "TODAS AS ESCALAS PLANEJADAS EXISTENTES no dia " + dataDestinoFormatada + " SERÃO APAGADAS PRIMEIRO.\n\nEsta ação não pode ser desfeita. Deseja continuar?")) {
            return;
        }

        var originalButtonText = $button.html();
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Copiando...');

        $.ajax({
            url: 'copiar_escala_planejada_dia_ajax.php', 
            type: 'POST',
            dataType: 'json',
            data: { data_origem: dataOrigem, data_destino: dataDestino, confirmar_substituicao: confirmarSubstituicao ? 1 : 0 },
            success: function(response) {
                if (response.success) {
                    $feedbackDiv.html('<small class="text-success"><i class="fas fa-check-circle"></i> ' + response.message + '</small>');
                    alert(response.message + "\n\nRecomendamos filtrar pela data de destino (" + dataDestinoFormatada + ") para confirmar as alterações.");
                    $('#data_escala_filtro').val(dataDestino);
                    $('#formFiltroEscalaPlanejada').submit();
                } else {
                    $feedbackDiv.html('<small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Erro: ' + (response.message || 'Ocorreu um problema ao copiar a escala.') + '</small>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Erro AJAX ao copiar escala do dia:", textStatus, errorThrown, jqXHR.responseText);
                $feedbackDiv.html('<small class="text-danger">Erro de comunicação ao tentar copiar a escala. Verifique o console.</small>');
            },
            complete: function() {
                $button.prop('disabled', false).html(originalButtonText);
                $('#confirmar_substituicao_escala_destino').prop('checked', false); 
            }
        });
    });
});
</script>
<?php
$page_specific_js = ob_get_clean();
require_once 'admin_footer.php';
?>