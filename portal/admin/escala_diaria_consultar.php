<?php
// admin/escala_diaria_consultar.php
// ATUALIZADO para mostrar Linha/Função e adicionar filtro por Função.

require_once 'auth_check.php';

$niveis_permitidos_consultar_escala_diaria = ['Agente de Terminal', 'Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_consultar_escala_diaria)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para consultar a Escala Diária.";
    header('Location: index.php');
    exit;
}

require_once '../db_config.php';
$page_title = 'Consultar Escala Diária';

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

$escalas_diarias = [];
$total_escalas = 0;
$total_paginas_escalas = 0;
$erro_busca_escalas = false;

$pode_gerenciar_escala_diaria = in_array($admin_nivel_acesso_logado, ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador']);
$pode_importar_planejada_para_diaria = in_array($admin_nivel_acesso_logado, ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador']);

$linhas_para_filtro = [];
$funcoes_para_filtro_diaria = []; // NOVO: Para o filtro de função na diária
if ($pdo) {
    try {
        $stmt_linhas = $pdo->query("SELECT id, numero, nome FROM linhas ORDER BY CAST(numero AS UNSIGNED), numero, nome ASC");
        $linhas_para_filtro = $stmt_linhas->fetchAll(PDO::FETCH_ASSOC);
        
        // NOVO: Buscar funções operacionais para o filtro da diária
        $stmt_funcoes_diaria = $pdo->query("SELECT id, nome_funcao FROM funcoes_operacionais WHERE status = 'ativa' ORDER BY nome_funcao ASC");
        $funcoes_para_filtro_diaria = $stmt_funcoes_diaria->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) { error_log("Erro ao buscar linhas/funções para filtro (escala diária): " . $e->getMessage()); }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($pode_importar_planejada_para_diaria): ?>
        <button type="button" class="btn btn-success mr-2" id="btnImportarPlanejada"
                data-data_importar="<?php echo htmlspecialchars($filtro_data_obrigatoria); ?>"
                title="Copia todas as escalas da Planejada para a Diária para a data selecionada (<?php echo date('d/m/Y', strtotime($filtro_data_obrigatoria)); ?>). ATENÇÃO: Isso SUBSTITUIRÁ ajustes já feitos na Diária para esta data.">
            <i class="fas fa-download"></i> Importar da Planejada (<?php echo date('d/m', strtotime($filtro_data_obrigatoria)); ?>)
        </button>
        <?php endif; ?>
        <?php if ($pode_gerenciar_escala_diaria): ?>
        <a href="escala_diaria_formulario.php?data_escala=<?php echo htmlspecialchars($filtro_data_obrigatoria); ?>" class="btn btn-warning">
            <i class="fas fa-edit"></i> Ajustar/Adicionar na Diária
        </a>
        <?php endif; ?>
    </div>
</div>

<?php
// Feedback de ações
if (isset($_SESSION['admin_success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_success_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_success_message']); }
if (isset($_SESSION['admin_error_message'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_error_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_error_message']); }
if (isset($_SESSION['admin_warning_message'])) { echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_warning_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_warning_message']); }
?>

<form method="GET" action="escala_diaria_consultar.php" class="mb-4 card card-body bg-light p-3 shadow-sm" id="formFiltroEscalaDiaria">
    <div class="form-row align-items-end">
        <div class="col-md-3 form-group mb-md-0">
            <label for="data_escala_filtro">Data da Escala <span class="text-danger">*</span>:</label>
            <input type="date" class="form-control form-control-sm" id="data_escala_filtro" name="data_escala" value="<?php echo htmlspecialchars($filtro_data_obrigatoria); ?>" required>
        </div>
        <div class="col-md-3 form-group mb-md-0">
            <label for="tipo_busca_adicional_filtro_diaria">Busca Adicional por:</label>
            <select class="form-control form-control-sm" id="tipo_busca_adicional_filtro_diaria" name="tipo_busca_adicional">
                <option value="todos_data" <?php echo ($filtro_tipo_busca_adicional == 'todos_data') ? 'selected' : ''; ?>>Todos da Data</option>
                <option value="linha" <?php echo ($filtro_tipo_busca_adicional == 'linha') ? 'selected' : ''; ?>>Linha</option>
                <option value="funcao" <?php echo ($filtro_tipo_busca_adicional == 'funcao') ? 'selected' : ''; ?>>Função Operacional</option> <option value="folgas" <?php echo ($filtro_tipo_busca_adicional == 'folgas') ? 'selected' : ''; ?>>Apenas Folgas</option>
                <option value="faltas" <?php echo ($filtro_tipo_busca_adicional == 'faltas') ? 'selected' : ''; ?>>Apenas Faltas</option>
                <option value="fora_escala" <?php echo ($filtro_tipo_busca_adicional == 'fora_escala') ? 'selected' : ''; ?>>Apenas Fora de Escala</option>
                <option value="ferias" <?php echo ($filtro_tipo_busca_adicional == 'ferias') ? 'selected' : ''; ?>>Apenas Férias</option>
                <option value="atestados" <?php echo ($filtro_tipo_busca_adicional == 'atestados') ? 'selected' : ''; ?>>Apenas Atestados</option>
                <option value="workid" <?php echo ($filtro_tipo_busca_adicional == 'workid') ? 'selected' : ''; ?>>WorkID</option>
                <option value="motorista" <?php echo ($filtro_tipo_busca_adicional == 'motorista') ? 'selected' : ''; ?>>Motorista (Nome/Matr.)</option>
            </select>
        </div>
        <div class="col-md-3 form-group mb-md-0" id="campoValorBuscaAdicionalWrapperDiaria">
            <label for="valor_busca_adicional_input_text_diaria">Valor Específico:</label>
            <input type="text" class="form-control form-control-sm d-none" id="valor_busca_adicional_input_text_diaria" name="valor_busca_adicional_text_disabled" value="<?php echo ($filtro_tipo_busca_adicional == 'workid' || $filtro_tipo_busca_adicional == 'motorista') ? htmlspecialchars($filtro_valor_busca_adicional) : ''; ?>" placeholder="WorkID ou Nome/Matr.">
            <select class="form-control form-control-sm d-none" id="valor_busca_adicional_select_linha_diaria" name="valor_busca_adicional_linha_disabled">
                <option value="">Selecione a linha...</option>
                <?php foreach($linhas_para_filtro as $linha_opt_filtro): ?>
                    <option value="<?php echo $linha_opt_filtro['id']; ?>" <?php echo ($filtro_tipo_busca_adicional == 'linha' && $filtro_valor_busca_adicional == $linha_opt_filtro['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($linha_opt_filtro['numero'] . ($linha_opt_filtro['nome'] ? ' - ' . $linha_opt_filtro['nome'] : '')); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select class="form-control form-control-sm d-none" id="valor_busca_adicional_select_funcao_diaria" name="valor_busca_adicional_funcao_disabled">
                <option value="">Selecione a função...</option>
                <?php foreach($funcoes_para_filtro_diaria as $funcao_opt_filtro_d): ?>
                    <option value="<?php echo $funcao_opt_filtro_d['id']; ?>" <?php echo ($filtro_tipo_busca_adicional == 'funcao' && $filtro_valor_busca_adicional == $funcao_opt_filtro_d['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($funcao_opt_filtro_d['nome_funcao']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1 form-group mb-md-0 align-self-end">
            <button type="submit" class="btn btn-sm btn-primary btn-block" title="Aplicar Filtros"><i class="fas fa-filter"></i> Filtrar</button>
        </div>
        <div class="col-md-2 form-group mb-md-0 align-self-end">
            <a href="escala_diaria_consultar.php" class="btn btn-sm btn-outline-secondary btn-block" title="Limpar Filtros (Voltar para data atual)"><i class="fas fa-times"></i> Limpar</a>
        </div>
    </div>
</form>
<script>
// JavaScript para controlar a visibilidade do campo de valor específico do filtro (similar ao da planejada)
document.addEventListener('DOMContentLoaded', function() {
    const tipoBuscaSelectDiaria = document.getElementById('tipo_busca_adicional_filtro_diaria');
    const campoValorWrapperDiaria = document.getElementById('campoValorBuscaAdicionalWrapperDiaria');
    const inputValorTextoDiaria = document.getElementById('valor_busca_adicional_input_text_diaria');
    const selectValorLinhaDiaria = document.getElementById('valor_busca_adicional_select_linha_diaria');
    const selectValorFuncaoDiaria = document.getElementById('valor_busca_adicional_select_funcao_diaria'); // NOVO

    function configurarCampoValorDiaria() {
        const tipoSelecionado = tipoBuscaSelectDiaria.value;
        inputValorTextoDiaria.classList.add('d-none');
        selectValorLinhaDiaria.classList.add('d-none');
        selectValorFuncaoDiaria.classList.add('d-none'); // NOVO
        
        inputValorTextoDiaria.name = 'valor_busca_adicional_text_disabled';
        selectValorLinhaDiaria.name = 'valor_busca_adicional_linha_disabled';
        selectValorFuncaoDiaria.name = 'valor_busca_adicional_funcao_disabled'; // NOVO
        campoValorWrapperDiaria.style.visibility = 'hidden';

        if (tipoSelecionado === 'linha') {
            selectValorLinhaDiaria.classList.remove('d-none');
            selectValorLinhaDiaria.name = 'valor_busca_adicional';
            campoValorWrapperDiaria.style.visibility = 'visible';
        } else if (tipoSelecionado === 'funcao') { // NOVO
            selectValorFuncaoDiaria.classList.remove('d-none');
            selectValorFuncaoDiaria.name = 'valor_busca_adicional';
            campoValorWrapperDiaria.style.visibility = 'visible';
        } else if (tipoSelecionado === 'workid' || tipoSelecionado === 'motorista') {
            inputValorTextoDiaria.classList.remove('d-none');
            inputValorTextoDiaria.name = 'valor_busca_adicional';
            inputValorTextoDiaria.placeholder = (tipoSelecionado === 'workid') ? 'Digite o WorkID' : 'Nome ou Matrícula';
            campoValorWrapperDiaria.style.visibility = 'visible';
        }
    }
    if (tipoBuscaSelectDiaria) {
        tipoBuscaSelectDiaria.addEventListener('change', configurarCampoValorDiaria);
        configurarCampoValorDiaria();
    }
});
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
                <th>H. Início</th>
                <th>H. Fim</th>
                <th>Fim Pega</th>
                <th style="min-width: 150px;">Observações</th>
                <th>Últ. Mod. Por</th>
                <?php if ($pode_gerenciar_escala_diaria): ?>
                <th style="width: 120px;">Ações</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($pdo) {
                try {
                    // ALTERADO: Adiciona LEFT JOIN com funcoes_operacionais
                    $sql_select_base = "SELECT esc_d.id, esc_d.data, esc_d.work_id, esc_d.tabela_escalas, esc_d.eh_extra,
                                            esc_d.hora_inicio_prevista, esc_d.hora_fim_prevista, esc_d.motorista_id,
                                            esc_d.funcao_operacional_id, /* NOVO CAMPO */
                                            esc_d.observacoes_ajuste, esc_d.data_ultima_modificacao,
                                            mot.nome AS nome_motorista, mot.matricula AS matricula_motorista,
                                            lin.numero AS numero_linha, lin.nome as nome_linha_desc,
                                            fo.nome_funcao AS nome_funcao_operacional, /* NOVO CAMPO */
                                            loc_ini.nome AS local_inicio_nome, loc_fim.nome AS local_fim_nome,
                                            adm.username AS admin_modificador_username
                                     FROM motorista_escalas_diaria AS esc_d
                                     LEFT JOIN motoristas AS mot ON esc_d.motorista_id = mot.id
                                     LEFT JOIN linhas AS lin ON esc_d.linha_origem_id = lin.id
                                     LEFT JOIN funcoes_operacionais AS fo ON esc_d.funcao_operacional_id = fo.id /* NOVO JOIN */
                                     LEFT JOIN locais AS loc_ini ON esc_d.local_inicio_turno_id = loc_ini.id
                                     LEFT JOIN locais AS loc_fim ON esc_d.local_fim_turno_id = loc_fim.id
                                     LEFT JOIN administradores AS adm ON esc_d.modificado_por_admin_id = adm.id";

                    // ALTERADO: Adiciona LEFT JOIN com funcoes_operacionais para contagem
                    $sql_count_base = "SELECT COUNT(esc_d.id) FROM motorista_escalas_diaria AS esc_d
                                       LEFT JOIN motoristas AS mot ON esc_d.motorista_id = mot.id
                                       LEFT JOIN linhas AS lin ON esc_d.linha_origem_id = lin.id
                                       LEFT JOIN funcoes_operacionais AS fo ON esc_d.funcao_operacional_id = fo.id"; /* NOVO JOIN */

                    $sql_where_conditions = [];
                    $sql_params_execute = [];

                    $sql_where_conditions[] = "esc_d.data = :data_f";
                    $sql_params_execute[':data_f'] = $filtro_data_obrigatoria;

                    if ($filtro_tipo_busca_adicional === 'linha' && !empty($filtro_valor_busca_adicional)) {
                        $sql_where_conditions[] = "esc_d.linha_origem_id = :valor_adicional_f";
                        $sql_params_execute[':valor_adicional_f'] = $filtro_valor_busca_adicional;
                    } elseif ($filtro_tipo_busca_adicional === 'funcao' && !empty($filtro_valor_busca_adicional)) { // NOVO FILTRO
                        $sql_where_conditions[] = "esc_d.funcao_operacional_id = :valor_adicional_f_func";
                        $sql_params_execute[':valor_adicional_f_func'] = $filtro_valor_busca_adicional;
                    } elseif (in_array($filtro_tipo_busca_adicional, ['folgas', 'faltas', 'fora_escala', 'ferias', 'atestados'])) {
                        $status_map_diaria = [
                            'folgas' => 'FOLGA', 'faltas' => 'FALTA', 'fora_escala' => 'FORADEESCALA',
                            'ferias' => 'FÉRIAS', 'atestados' => 'ATESTADO'
                        ];
                        if (isset($status_map_diaria[$filtro_tipo_busca_adicional])) {
                            $sql_where_conditions[] = "UPPER(esc_d.work_id) = :status_work_id";
                            $sql_params_execute[':status_work_id'] = $status_map_diaria[$filtro_tipo_busca_adicional];
                        }
                    } elseif ($filtro_tipo_busca_adicional === 'workid' && !empty($filtro_valor_busca_adicional)) {
                        $sql_where_conditions[] = "esc_d.work_id LIKE :valor_adicional_f";
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

                    $stmt_select_escalas = $pdo->prepare($sql_select_base . $sql_where_clause . " ORDER BY esc_d.data DESC, mot.nome ASC, esc_d.hora_inicio_prevista ASC LIMIT :limit OFFSET :offset");
                    foreach ($sql_params_execute as $key => $value) $stmt_select_escalas->bindValue($key, $value);
                    $stmt_select_escalas->bindValue(':limit', $escalas_por_pagina, PDO::PARAM_INT);
                    $stmt_select_escalas->bindValue(':offset', $offset, PDO::PARAM_INT);
                    $stmt_select_escalas->execute();
                    $escalas_diarias = $stmt_select_escalas->fetchAll(PDO::FETCH_ASSOC);

                    if ($escalas_diarias) {
                        foreach ($escalas_diarias as $escala) {
                            // ... (lógica de $is_status_especial e $classe_linha_tr mantida) ...
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

                            echo "<td>" . $data_formatada . ($is_status_especial ? '' : (isset($escala['eh_extra']) && $escala['eh_extra'] == 1 ? ' <span class="text-danger font-italic small">(extra)</span>' : '')) . "</td>";
                            echo "<td>" . htmlspecialchars($escala['matricula_motorista'] ?: 'N/A') . "</td>";
                            echo "<td>" . htmlspecialchars($escala['nome_motorista'] ?: 'N/A') . "</td>";

                            if ($is_status_especial) {
                                $colspan_status = 7;
                                $status_texto = '';
                                $status_classe_bg = $classe_linha_tr;

                                if ($is_folga) { $status_texto = "✨ FOLGA ✨"; }
                                elseif ($is_falta) { $status_texto = "⚠️ FALTA ⚠️"; }
                                elseif ($is_fora_escala) { $status_texto = "🚫 FORA DE ESCALA 🚫"; }
                                elseif ($is_ferias) { $status_texto = "🏖️ FÉRIAS 🏖️"; }
                                elseif ($is_atestado) { $status_texto = "⚕️ ATESTADO ⚕️"; }

                                echo "<td colspan='{$colspan_status}' class='text-center {$status_classe_bg} font-weight-bold py-2'>" . $status_texto . "</td>";
                            } else {
                                // ALTERADO: Exibir Linha ou Função
                                $display_linha_funcao_diaria = '-';
                                if (!empty($escala['funcao_operacional_id']) && !empty($escala['nome_funcao_operacional'])) {
                                    $display_linha_funcao_diaria = htmlspecialchars($escala['nome_funcao_operacional']); // Sem o badge "Função:"
                                } elseif (!empty($escala['numero_linha'])) {
                                    $display_linha_funcao_diaria = htmlspecialchars($escala['numero_linha'] . (isset($escala['nome_linha_desc']) && $escala['nome_linha_desc'] ? ' / ' . $escala['nome_linha_desc'] : ''));
                                }
                                echo "<td>" . $display_linha_funcao_diaria . "</td>";
                                
                                echo "<td>" . htmlspecialchars($escala['tabela_escalas'] ?: '-') . "</td>";
                                echo "<td>" . htmlspecialchars($escala['work_id']) . "</td>";
                                echo "<td>" . htmlspecialchars($escala['local_inicio_nome'] ?: '-') . "</td>";
                                echo "<td>" . ($escala['hora_inicio_prevista'] ? date('H:i', strtotime($escala['hora_inicio_prevista'])) : '-') . "</td>";
                                echo "<td>" . ($escala['hora_fim_prevista'] ? date('H:i', strtotime($escala['hora_fim_prevista'])) : '-') . "</td>";
                                echo "<td>" . htmlspecialchars($escala['local_fim_nome'] ?: '-') . "</td>";
                            }

                            echo "<td title=\"" . htmlspecialchars($escala['observacoes_ajuste'] ?? '') . "\">" . htmlspecialchars(mb_strimwidth($escala['observacoes_ajuste'] ?? '-', 0, 25, "...")) . "</td>";
                            echo "<td title=\"Modificado em: " . ($escala['data_ultima_modificacao'] ? date('d/m/Y H:i', strtotime($escala['data_ultima_modificacao'])) : 'N/A') . "\">" . htmlspecialchars($escala['admin_modificador_username'] ?: '-') . "</td>";

                            if ($pode_gerenciar_escala_diaria) {
                                // ... (lógica de botões de ação mantida) ...
                                $params_acao_diaria = ['id' => $escala['id'], 'pagina' => $pagina_atual];
                                if (isset($_GET['data_escala'])) $params_acao_diaria['data_escala'] = $_GET['data_escala'];
                                if (isset($_GET['tipo_busca_adicional'])) $params_acao_diaria['tipo_busca_adicional'] = $_GET['tipo_busca_adicional'];
                                if (isset($_GET['valor_busca_adicional'])) $params_acao_diaria['valor_busca_adicional'] = $_GET['valor_busca_adicional'];
                                $query_string_acao_diaria = http_build_query($params_acao_diaria);

                                echo "<td class='action-buttons " . ($is_status_especial && !empty($status_classe_bg) && $status_classe_bg != 'table-secondary' ? $status_classe_bg : '')  . "'>";
                                echo "<a href='escala_diaria_formulario.php?" . $query_string_acao_diaria . "' class='btn btn-primary btn-sm' title='Editar Escala Diária'><i class='fas fa-edit'></i></a> ";
                                echo "</td>";
                            }
                            echo "</tr>";
                        }
                    } else {
                        $colspan_total = 12 + ($pode_gerenciar_escala_diaria ? 1 : 0);
                        echo "<tr><td colspan='{$colspan_total}' class='text-center'>Nenhuma escala diária encontrada para os filtros aplicados.</td></tr>";
                    }
                } catch (PDOException $e) {
                    $colspan_total = 12 + ($pode_gerenciar_escala_diaria ? 1 : 0);
                    echo "<tr><td colspan='{$colspan_total}' class='text-danger text-center'>Erro ao buscar escalas diárias: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                    $erro_busca_escalas = true;
                }
            } else {
                $colspan_total = 12 + ($pode_gerenciar_escala_diaria ? 1 : 0);
                echo "<tr><td colspan='{$colspan_total}' class='text-danger text-center'>Falha na conexão com o banco de dados.</td></tr>";
                $erro_busca_escalas = true;
            }
            ?>
        </tbody>
    </table>
</div>

<?php if (!$erro_busca_escalas && $total_paginas_escalas > 1): ?>
    <nav aria-label="Navegação das escalas diárias">
        <ul class="pagination justify-content-center mt-4">
            <?php
            // ... (lógica de paginação mantida, mas garanta que preserve todos os filtros GET) ...
            $query_params_paginacao_diaria = $_GET; // Pega todos os parâmetros GET atuais
            unset($query_params_paginacao_diaria['pagina']); // Remove o parâmetro de página para reconstruir
            $link_base_paginacao_diaria = 'escala_diaria_consultar.php?' . http_build_query($query_params_paginacao_diaria) . (empty($query_params_paginacao_diaria) ? '' : '&');

            if ($pagina_atual > 1) {
                echo '<li class="page-item"><a class="page-link" href="' . $link_base_paginacao_diaria . 'pagina=1">Primeira</a></li>';
                echo '<li class="page-item"><a class="page-link" href="' . $link_base_paginacao_diaria . 'pagina=' . ($pagina_atual - 1) . '" aria-label="Anterior"><span aria-hidden="true">&laquo;</span></a></li>';
            } else {
                echo '<li class="page-item disabled"><span class="page-link">Primeira</span></li>';
                echo '<li class="page-item disabled"><span class="page-link" aria-label="Anterior"><span aria-hidden="true">&laquo;</span></span></li>';
            }
            $num_links_nav = 2; $inicio_nav = max(1, $pagina_atual - $num_links_nav); $fim_nav = min($total_paginas_escalas, $pagina_atual + $num_links_nav);
            if ($inicio_nav > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            for ($i = $inicio_nav; $i <= $fim_nav; $i++) {
                echo '<li class="page-item ' . ($i == $pagina_atual ? 'active' : '') . '"><a class="page-link" href="' . $link_base_paginacao_diaria . 'pagina=' . $i . '">' . $i . '</a></li>';
            }
            if ($fim_nav < $total_paginas_escalas) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            if ($pagina_atual < $total_paginas_escalas) {
                 echo '<li class="page-item"><a class="page-link" href="' . $link_base_paginacao_diaria . 'pagina=' . ($pagina_atual + 1) . '" aria-label="Próxima"><span aria-hidden="true">&raquo;</span></a></li>';
                echo '<li class="page-item"><a class="page-link" href="' . $link_base_paginacao_diaria . 'pagina=' . $total_paginas_escalas . '">Última</a></li>';
            } else {
                echo '<li class="page-item disabled"><span class="page-link" aria-label="Próxima"><span aria-hidden="true">&raquo;</span></span></li>';
                echo '<li class="page-item disabled"><span class="page-link">Última</span></li>';
            }
            ?>
        </ul>
    </nav>
<?php endif; ?>

<?php
// JavaScript para o botão de importar (mantido)
ob_start();
?>
<script>
$(document).ready(function() {
    $('#btnImportarPlanejada').on('click', function() {
        var dataParaImportar = $(this).data('data_importar');
        var dataFormatadaBotao = $(this).text().match(/\(([^)]+)\)/) ? $(this).text().match(/\(([^)]+)\)/)[1] : dataParaImportar;

        if (confirm("ATENÇÃO!\n\nImportar da Planejada para o dia " + dataFormatadaBotao + "?\n\nISSO PODERÁ SUBSTITUIR AJUSTES JÁ FEITOS NA ESCALA DIÁRIA DESTE DIA.\n\nDeseja continuar?")) {
            var $button = $(this);
            var originalButtonText = $button.html();
            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Importando...');

            $.ajax({
                url: 'escala_diaria_acao.php', 
                type: 'POST',
                data: {
                    acao: 'importar_dia_da_planejada', // Ação específica para o backend
                    data_escala: dataParaImportar,
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Sucesso: ' + response.message);
                        window.location.href = 'escala_diaria_consultar.php?data_escala=' + dataParaImportar; 
                    } else {
                        alert('Erro na importação: ' + (response.message || 'Ocorreu um problema desconhecido.'));
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('Erro de comunicação ao tentar importar. Status: ' + textStatus + '. Erro: ' + errorThrown);
                    console.error("Erro AJAX importar para diária:", jqXHR.responseText);
                },
                complete: function() {
                    $button.prop('disabled', false).html(originalButtonText);
                }
            });
        }
    });
});
</script>
<?php
$page_specific_js = ob_get_clean();
require_once 'admin_footer.php';
?>