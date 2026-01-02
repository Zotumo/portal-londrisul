<?php
// admin/escala_planejada_formulario.php
// ATUALIZADO v12: Validação de Tabela e Lógica de Veículo via AJAX.

require_once 'auth_check.php';

// --- Permissões ---
$niveis_permitidos_gerenciar_escala = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_gerenciar_escala)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para gerenciar a Escala Planejada.";
    header('Location: index.php');
    exit;
}

require_once '../db_config.php';
$page_title_action = 'Adicionar Entrada na Escala Planejada';

// --- Inicialização de Variáveis do Formulário ---
$escala_id_edit = null;
$tipo_escala_form_php = 'linha'; // Default
$data_escala_form_php = date('Y-m-d');
$motorista_id_form_php = '';
$motorista_texto_repop_php = '';

// Para Linha
$linha_origem_id_form_php = '';
$veiculo_id_db_php = '';
$veiculo_prefixo_db_php = '';

// Para Função Operacional
$funcao_operacional_id_form_php = '';
$turno_funcao_form_php = '';
$posicao_letra_form_php = '';

// Comuns
$work_id_form_php = '';
$tabela_escalas_form_php = '';
$hora_inicio_form_php = '';
$local_inicio_id_form_php = '';
$hora_fim_form_php = '';
$local_fim_id_form_php = '';
$eh_extra_form_php = 0;

// Para Status Especiais
$is_folga_check_php = false;
$is_falta_check_php = false;
$is_fora_escala_check_php = false;
$is_ferias_check_php = false;
$is_atestado_check_php = false;

$modo_edicao_escala_php = false;

// Listas para Selects
$lista_linhas_select_php = [];
$lista_locais_select_php = [];
$lista_funcoes_operacionais_php = [];

if ($pdo) {
    
    // --- 1. BUSCAR LINHAS (Do Histórico de Viagens) ---
    try {
        // Usamos DISTINCT para pegar apenas as linhas únicas
        $stmt_linhas_all = $pdo->query("SELECT DISTINCT ROUTE_ID as id, ROUTE_ID as numero, '' as nome 
                                        FROM relatorios_viagens 
                                        WHERE ROUTE_ID IS NOT NULL AND ROUTE_ID != '' 
                                        ORDER BY ROUTE_ID ASC");
        $lista_linhas_select_php = $stmt_linhas_all->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao carregar Linhas: " . $e->getMessage());
        // Não faz nada, a lista fica vazia mas não trava o resto
    }

    // --- 2. BUSCAR LOCAIS (Tenta tabela nova, se falhar tenta a antiga) ---
    try {
        // Tenta primeiro na tabela nova de importação (cadastros_locais)
        // Usamos 'AS' para manter compatibilidade com o HTML (id, nome)
        $sql_locais = "SELECT company_code as id, name as nome, 'ponto' as tipo 
                       FROM cadastros_locais 
                       ORDER BY name ASC";
        
        $stmt_locais_all = $pdo->query($sql_locais);
        $lista_locais_select_php = $stmt_locais_all->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // Se der erro (ex: tabela cadastros_locais não existe), tenta a tabela antiga 'locais'
        try {
            $stmt_locais_old = $pdo->query("SELECT id, nome, tipo FROM locais ORDER BY nome ASC");
            $lista_locais_select_php = $stmt_locais_old->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e2) {
            error_log("Erro crítico ao carregar Locais: " . $e2->getMessage());
        }
    }

    // --- 3. BUSCAR FUNÇÕES OPERACIONAIS ---
    try {
        $stmt_funcoes = $pdo->query("SELECT id, nome_funcao, work_id_prefixo, locais_permitidos_tipo, locais_permitidos_ids, local_fixo_id, turnos_disponiveis, requer_posicao_especifica, max_posicoes_por_turno, ignorar_validacao_jornada 
                                     FROM funcoes_operacionais 
                                     WHERE status = 'ativa' 
                                     ORDER BY nome_funcao ASC");
        $lista_funcoes_operacionais_php = $stmt_funcoes->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao carregar Funções: " . $e->getMessage());
    }
}

// --- Lógica de Edição ---
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
    $escala_id_edit = (int)$_GET['id'];
    $modo_edicao_escala_php = true;
    $page_title_action = 'Editar Entrada da Escala Planejada';
    if ($pdo) {
        try {
            $sql_get_escala = "SELECT esc.*, 
                                      mot.nome as nome_motorista_atual, mot.matricula as matricula_motorista_atual,
                                      veic.prefixo as prefixo_veiculo_atual 
                               FROM motorista_escalas esc 
                               LEFT JOIN motoristas mot ON esc.motorista_id = mot.id 
                               LEFT JOIN veiculos veic ON esc.veiculo_id = veic.id
                               WHERE esc.id = :id_escala";
            $stmt_get_escala = $pdo->prepare($sql_get_escala);
            $stmt_get_escala->bindParam(':id_escala', $escala_id_edit, PDO::PARAM_INT);
            $stmt_get_escala->execute();
            $escala_db = $stmt_get_escala->fetch(PDO::FETCH_ASSOC);

            if ($escala_db) {
                $data_escala_form_php = $escala_db['data'];
                $motorista_id_form_php = $escala_db['motorista_id'];
                if ($motorista_id_form_php && isset($escala_db['nome_motorista_atual'])) { 
                    $motorista_texto_repop_php = htmlspecialchars($escala_db['nome_motorista_atual'] . ' (Mat: ' . $escala_db['matricula_motorista_atual'] . ')'); 
                }
                
                $work_id_form_php = $escala_db['work_id'];
                $funcao_operacional_id_form_php = $escala_db['funcao_operacional_id'];

                if (!empty($funcao_operacional_id_form_php)) {
                    $tipo_escala_form_php = 'funcao';
                    $funcao_obj_edit = null;
                    foreach($lista_funcoes_operacionais_php as $f){
                        if(strval($f['id']) === strval($funcao_operacional_id_form_php)){
                            $funcao_obj_edit = $f;
                            break;
                        }
                    }
                    if ($funcao_obj_edit && $work_id_form_php) {
                        $prefixo_func_edit = $funcao_obj_edit['work_id_prefixo'];
                        $sem_prefixo_edit = preg_replace('/^'.preg_quote($prefixo_func_edit, '/').'-?/i', '', $work_id_form_php);
                        if (!$funcao_obj_edit['local_fixo_id']) { 
                             $sem_prefixo_edit = preg_replace('/^[A-Z0-9]{1,3}-/i', '', $sem_prefixo_edit);
                        }
                        $partes_turno_pos_edit = explode('-', $sem_prefixo_edit);
                        $ultimo_segmento_edit = array_pop($partes_turno_pos_edit);
                        
                        if($funcao_obj_edit['requer_posicao_especifica'] && strlen($ultimo_segmento_edit) > 2 && ctype_alpha(substr($ultimo_segmento_edit,-1))){
                            $posicao_letra_form_php = strtoupper(substr($ultimo_segmento_edit,-1));
                            $turno_funcao_form_php = substr($ultimo_segmento_edit,0,-1);
                        } elseif (strlen($ultimo_segmento_edit) == 2 && ctype_digit($ultimo_segmento_edit)){
                           $turno_funcao_form_php = $ultimo_segmento_edit;
                           $posicao_letra_form_php = '';
                        }
                    }
                } else {
                    $tipo_escala_form_php = 'linha';
                    $linha_origem_id_form_php = $escala_db['linha_origem_id'];
                    $veiculo_id_db_php = $escala_db['veiculo_id'];
                    $veiculo_prefixo_db_php = $escala_db['prefixo_veiculo_atual'];
                }
                
                $work_id_upper = strtoupper($work_id_form_php ?? '');
                $is_folga_check_php = ($work_id_upper === 'FOLGA');
                $is_falta_check_php = ($work_id_upper === 'FALTA');
                $is_fora_escala_check_php = ($work_id_upper === 'FORADEESCALA');
                $is_ferias_check_php = ($work_id_upper === 'FÉRIAS');
                $is_atestado_check_php = ($work_id_upper === 'ATESTADO');
                $is_status_especial = $is_folga_check_php || $is_falta_check_php || $is_fora_escala_check_php || $is_ferias_check_php || $is_atestado_check_php;
                
                $tabela_escalas_form_php = ($is_status_especial || $tipo_escala_form_php === 'funcao') ? '' : $escala_db['tabela_escalas'];
                $hora_inicio_form_php = $is_status_especial ? '' : ($escala_db['hora_inicio_prevista'] ? date('H:i', strtotime($escala_db['hora_inicio_prevista'])) : '');
                $local_inicio_id_form_php = $is_status_especial ? '' : $escala_db['local_inicio_turno_id'];
                $hora_fim_form_php = $is_status_especial ? '' : ($escala_db['hora_fim_prevista'] ? date('H:i', strtotime($escala_db['hora_fim_prevista'])) : '');
                $local_fim_id_form_php = $is_status_especial ? '' : $escala_db['local_fim_turno_id'];
                $eh_extra_form_php = $is_status_especial ? 0 : $escala_db['eh_extra'];
                $page_title_action .= ' (' . $motorista_texto_repop_php . ' em ' . date('d/m/Y', strtotime($data_escala_form_php)) . ')';
            } else { 
                $_SESSION['admin_error_message'] = "Entrada da Escala Planejada ID {$escala_id_edit} não encontrada.";
                header('Location: escala_planejada_listar.php?' . http_build_query(array_intersect_key($_GET, array_flip(['pagina', 'data_escala', 'tipo_busca_adicional', 'valor_busca_adicional']))));
                exit;
             }
        } catch (PDOException $e) { 
            $_SESSION['admin_error_message'] = "Erro ao carregar dados da escala para edição.";
            error_log("Erro PDO ao buscar escala para edição: " . $e->getMessage());
            header('Location: escala_planejada_listar.php?' . http_build_query(array_intersect_key($_GET, array_flip(['pagina', 'data_escala', 'tipo_busca_adicional', 'valor_busca_adicional']))));
            exit;
        }
    }
}

$page_title = $page_title_action;
require_once 'admin_header.php';

// --- Repopulação do Formulário ---
$form_data_repop_session = $_SESSION['form_data_escala_planejada'] ?? [];
if(!empty($form_data_repop_session)) {
    $tipo_escala_form_php = $form_data_repop_session['tipo_escala'] ?? $tipo_escala_form_php;
    $data_escala_form_php = $form_data_repop_session['data_escala'] ?? $data_escala_form_php;
    $motorista_id_form_php = $form_data_repop_session['motorista_id'] ?? $motorista_id_form_php;
    if ($motorista_id_form_php && empty($motorista_texto_repop_php) && $pdo) {
        try {
            $stmt_mot_repop = $pdo->prepare("SELECT nome, matricula FROM motoristas WHERE id = :id_mot_repop");
            $stmt_mot_repop->bindParam(':id_mot_repop', $motorista_id_form_php, PDO::PARAM_INT);
            $stmt_mot_repop->execute();
            $mot_data_repop = $stmt_mot_repop->fetch(PDO::FETCH_ASSOC);
            if($mot_data_repop) {
                $motorista_texto_repop_php = htmlspecialchars($mot_data_repop['nome'] . ' (Mat: ' . $mot_data_repop['matricula'] . ')');
            }
        } catch(PDOException $e_repop) {}
    }
    
    $linha_origem_id_form_php = $form_data_repop_session['linha_origem_id'] ?? $linha_origem_id_form_php;
    $veiculo_id_db_php = $form_data_repop_session['veiculo_id'] ?? $veiculo_id_db_php; 
    if ($veiculo_id_db_php && empty($veiculo_prefixo_db_php) && $pdo) {
        try {
            $stmt_veic_repop = $pdo->prepare("SELECT prefixo FROM veiculos WHERE id = :id_veic_repop");
            $stmt_veic_repop->bindParam(':id_veic_repop', $veiculo_id_db_php, PDO::PARAM_INT);
            $stmt_veic_repop->execute();
            $veiculo_prefixo_db_php = $stmt_veic_repop->fetchColumn();
        } catch(PDOException $e_repop_v) {}
    }

    $funcao_operacional_id_form_php = $form_data_repop_session['funcao_operacional_id'] ?? $funcao_operacional_id_form_php;
    $turno_funcao_form_php = $form_data_repop_session['turno_funcao'] ?? $turno_funcao_form_php;
    $posicao_letra_form_php = $form_data_repop_session['posicao_letra_funcao'] ?? $posicao_letra_form_php;

    $is_folga_check_php = isset($form_data_repop_session['is_folga_check']);
    $is_falta_check_php = isset($form_data_repop_session['is_falta_check']);
    $is_fora_escala_check_php = isset($form_data_repop_session['is_fora_escala_check']);
    $is_ferias_check_php = isset($form_data_repop_session['is_ferias_check']);
    $is_atestado_check_php = isset($form_data_repop_session['is_atestado_check']);
    
    $work_id_repop_val = $form_data_repop_session['work_id'] ?? ($form_data_repop_session['work_id_select_input_disabled'] ?? ($form_data_repop_session['work_id_text_input_disabled'] ?? $work_id_form_php) );
    if ($is_folga_check_php) $work_id_form_php = 'FOLGA';
    elseif ($is_falta_check_php) $work_id_form_php = 'FALTA';
    elseif ($is_fora_escala_check_php) $work_id_form_php = 'FORADEESCALA';
    elseif ($is_ferias_check_php) $work_id_form_php = 'FÉRIAS';
    elseif ($is_atestado_check_php) $work_id_form_php = 'ATESTADO';
    else $work_id_form_php = $work_id_repop_val;
    
    $is_status_especial_repop = $is_folga_check_php || $is_falta_check_php || $is_fora_escala_check_php || $is_ferias_check_php || $is_atestado_check_php;
    $tabela_escalas_form_php = ($is_status_especial_repop || $tipo_escala_form_php === 'funcao') ? '' : ($form_data_repop_session['tabela_escalas'] ?? $tabela_escalas_form_php);
    if (($tipo_escala_form_php === 'funcao' || $is_status_especial_repop)) { 
        $linha_origem_id_form_php = ''; 
        $veiculo_id_db_php = ''; 
        $veiculo_prefixo_db_php = '';
    }
    $hora_inicio_form_php = ($is_status_especial_repop) ? '' : ($form_data_repop_session['hora_inicio_prevista'] ?? $hora_inicio_form_php);
    $local_inicio_id_form_php = ($is_status_especial_repop) ? '' : ($form_data_repop_session['local_inicio_turno_id'] ?? $local_inicio_id_form_php);
    $hora_fim_form_php = ($is_status_especial_repop) ? '' : ($form_data_repop_session['hora_fim_prevista'] ?? $hora_fim_form_php);
    $local_fim_id_form_php = ($is_status_especial_repop) ? '' : ($form_data_repop_session['local_fim_turno_id'] ?? $local_fim_id_form_php);
    $eh_extra_form_php = ($is_status_especial_repop) ? 0 : ($form_data_repop_session['eh_extra'] ?? $eh_extra_form_php);

    unset($_SESSION['form_data_escala_planejada']);
}

// --- Passar dados para JavaScript ---
$js_work_id_inicial_php = $work_id_form_php;
$js_funcoes_operacionais_data = []; foreach($lista_funcoes_operacionais_php as $func) { $js_funcoes_operacionais_data[$func['id']] = $func; }
$js_locais_data_todos = []; foreach ($lista_locais_select_php as $loc) { $js_locais_data_todos[] = ['id' => $loc['id'], 'text' => htmlspecialchars($loc['nome']), 'tipo' => strtolower($loc['tipo'] ?? '')]; }
$js_escala_id_atual_php = $escala_id_edit;
$js_veiculo_id_atual_php = $veiculo_id_db_php;
$js_veiculo_prefixo_atual_php = $veiculo_prefixo_db_php;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title_action); ?></h1>
    <a href="escala_planejada_listar.php?<?php echo http_build_query(array_intersect_key($_GET, array_flip(['pagina', 'data_escala', 'tipo_busca_adicional', 'valor_busca_adicional']))); ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Voltar para Lista
    </a>
</div>

<?php
if (isset($_SESSION['admin_form_error_escala_p'])) { echo '<div class="alert alert-danger alert-dismissible fade show">' . nl2br(htmlspecialchars($_SESSION['admin_form_error_escala_p'])) . '<button type="button" class="close" data-dismiss="alert">&times;</button></div>'; unset($_SESSION['admin_form_error_escala_p']); }
?>

<form action="escala_planejada_processa.php" method="POST" id="form-escala-planejada">
	<input type="hidden" name="salvar_escala_planejada" value="1">
    <?php if ($modo_edicao_escala_php && $escala_id_edit): ?>
        <input type="hidden" name="escala_id" value="<?php echo $escala_id_edit; ?>">
    <?php endif; ?>
    <?php
     $params_to_preserve_submit_planejada = ['pagina_original' => 'pagina', 'filtro_data_original' => 'data_escala', 'filtro_tipo_busca_original' => 'tipo_busca_adicional', 'filtro_valor_busca_original' => 'valor_busca_adicional'];
    foreach ($params_to_preserve_submit_planejada as $hidden_name_planejada => $get_key_planejada):
        if (isset($_GET[$get_key_planejada])):
    ?>
        <input type="hidden" name="<?php echo htmlspecialchars($hidden_name_planejada); ?>" value="<?php echo htmlspecialchars($_GET[$get_key_planejada]); ?>">
    <?php endif; endforeach; ?>

    <fieldset class="mb-4 border p-3 rounded bg-light">
        <legend class="w-auto px-2 h6 text-secondary font-weight-normal">Copiar Dados de Escala Planejada Existente (Opcional)</legend>
        <div class="form-row">
            <div class="form-group col-md-5">
                <label for="copiar_motorista_id_select2" class="small">Motorista da Escala de Origem:</label>
                <select class="form-control form-control-sm" id="copiar_motorista_id_select2" data-placeholder="Buscar motorista para copiar...">
                    <option></option>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="copiar_data_escala_input" class="small">Data da Escala de Origem:</label>
                <input type="date" class="form-control form-control-sm" id="copiar_data_escala_input">
            </div>
            <div class="form-group col-md-3 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-info btn-block" id="btnBuscarCopiarEscala">
                    <i class="fas fa-search-plus"></i> Buscar & Preencher
                </button>
            </div>
        </div>
        <div id="copiar_escala_feedback" class="small mt-1" style="min-height: 20px;"></div>
    </fieldset>

    <div class="form-row">
        <div class="form-group col-md-3">
            <label for="data_escala">Data da Escala <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="data_escala" name="data_escala" value="<?php echo htmlspecialchars($data_escala_form_php); ?>" required>
        </div>
        <div class="form-group col-md-5">
            <label for="motorista_id_select2_escala">Motorista <span class="text-danger">*</span></label>
            <select class="form-control" id="motorista_id_select2_escala" name="motorista_id" required data-placeholder="Selecione ou digite nome/matrícula...">
                <?php if ($motorista_id_form_php && !empty($motorista_texto_repop_php)): ?>
                    <option value="<?php echo htmlspecialchars($motorista_id_form_php); ?>" selected><?php echo $motorista_texto_repop_php; ?></option>
                <?php elseif ($motorista_id_form_php): ?>
                     <option value="<?php echo htmlspecialchars($motorista_id_form_php); ?>" selected>ID: <?php echo htmlspecialchars($motorista_id_form_php); ?> (Carregando...)</option>
                <?php else: ?><option></option><?php endif; ?>
            </select>
        </div>
        <div class="form-group col-md-4 d-flex align-items-center flex-wrap">
            <div class="form-check mb-2 mr-3"><input class="form-check-input status-escala-check" type="checkbox" value="FOLGA" id="is_folga_check" name="is_folga_check" <?php echo $is_folga_check_php ? 'checked' : ''; ?>><label class="form-check-label" for="is_folga_check"><strong>Folga?</strong></label></div>
            <div class="form-check mb-2 mr-3"><input class="form-check-input status-escala-check" type="checkbox" value="FALTA" id="is_falta_check" name="is_falta_check" <?php echo $is_falta_check_php ? 'checked' : ''; ?>><label class="form-check-label" for="is_falta_check"><strong>Falta?</strong></label></div>
            <div class="form-check mb-2 mr-3"><input class="form-check-input status-escala-check" type="checkbox" value="FORADEESCALA" id="is_fora_escala_check" name="is_fora_escala_check" <?php echo $is_fora_escala_check_php ? 'checked' : ''; ?>><label class="form-check-label" for="is_fora_escala_check"><strong>Fora de Escala?</strong></label></div>
            <div class="form-check mb-2 mr-3"><input class="form-check-input status-escala-check" type="checkbox" value="FÉRIAS" id="is_ferias_check" name="is_ferias_check" <?php echo $is_ferias_check_php ? 'checked' : ''; ?>><label class="form-check-label" for="is_ferias_check"><strong>Férias?</strong></label></div>
            <div class="form-check mb-2"><input class="form-check-input status-escala-check" type="checkbox" value="ATESTADO" id="is_atestado_check" name="is_atestado_check" <?php echo $is_atestado_check_php ? 'checked' : ''; ?>><label class="form-check-label" for="is_atestado_check"><strong>Atestado?</strong></label></div>
        </div>
    </div>
    <hr>
    
    <div class="form-row">
        <div class="form-group col-md-4">
            <label for="tipo_escala_select">Tipo de Escala <span class="text-danger">*</span></label>
            <select class="form-control" id="tipo_escala_select" name="tipo_escala">
                <option value="linha" <?php echo ($tipo_escala_form_php === 'linha') ? 'selected' : ''; ?>>Linha de Ônibus</option>
                <option value="funcao" <?php echo ($tipo_escala_form_php === 'funcao') ? 'selected' : ''; ?>>Função Operacional</option>
            </select>
        </div>
    </div>

    <div id="campos_escala_linha_wrapper">
        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="linha_origem_id">Linha de Origem <span class="text-danger">*</span></label>
                
                <select class="form-control select2-simple" id="linha_origem_id" name="linha_visual_auxiliar" data-placeholder="Selecione primeiro a data...">
                    <option value=""></option>
                    <?php 
                    if ($modo_edicao_escala_php && $linha_origem_id_form_php) {
                        // Tenta extrair a primeira linha se vier composto (ex: "213/250" -> mostra "213")
                        $linha_principal_visual = explode('/', $linha_origem_id_form_php)[0];
                        echo '<option value="'.htmlspecialchars($linha_principal_visual).'" selected>'.htmlspecialchars($linha_principal_visual).' (Salvo)</option>';
                    }
                    ?>
                </select>

                <input type="hidden" name="linha_origem_id" id="linha_origem_id_hidden" value="<?php echo htmlspecialchars($linha_origem_id_form_php); ?>">

                <div id="aviso_multiplas_linhas" class="alert alert-info mt-2 small" style="display:none; border-left: 4px solid #17a2b8;">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Atenção:</strong> Este WorkID atende as linhas: <span id="txt_linhas_extras" class="font-weight-bold"></span>
                </div>
            </div>
        </div>
    </div>
    
    <div id="todos_campos_funcao_wrapper">
        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="funcao_operacional_id_select">Função Operacional <span class="text-danger">*</span></label>
                <select class="form-control select2-simple" id="funcao_operacional_id_select" name="funcao_operacional_id" data-placeholder="Selecione...">
                    <option value=""></option>
                    <?php foreach($lista_funcoes_operacionais_php as $fo):?>
                        <option value="<?php echo $fo['id'];?>" 
                                data-prefixo="<?php echo htmlspecialchars($fo['work_id_prefixo']);?>"
                                data-locais-tipo="<?php echo htmlspecialchars($fo['locais_permitidos_tipo']??'');?>"
                                data-locais-ids="<?php echo htmlspecialchars($fo['locais_permitidos_ids']??'');?>"
                                data-local-fixo-id="<?php echo htmlspecialchars($fo['local_fixo_id']??'');?>"
                                data-turnos="<?php echo htmlspecialchars($fo['turnos_disponiveis']);?>"
                                data-requer-posicao="<?php echo $fo['requer_posicao_especifica']?'true':'false';?>"
                                data-max-posicoes="<?php echo htmlspecialchars($fo['max_posicoes_por_turno']??'0');?>"
                                data-ignora-jornada="<?php echo $fo['ignorar_validacao_jornada']?'true':'false';?>"
                                <?php if(strval($fo['id'])==strval($funcao_operacional_id_form_php))echo 'selected';?>>
                            <?php echo htmlspecialchars($fo['nome_funcao']);?>
                        </option>
                    <?php endforeach;?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="turno_funcao_select">Turno da Função <span class="text-danger">*</span></label>
                <select class="form-control" id="turno_funcao_select" name="turno_funcao">
                    <option value="">Selecione...</option>
                    </select>
            </div>
            <div class="form-group col-md-4" id="wrapper_posicao_letra_funcao" style="display:none;">
                <label for="posicao_letra_funcao_select">Posição/Letra <span class="text-danger">*</span></label>
                <select class="form-control" id="posicao_letra_funcao_select" name="posicao_letra_funcao">
                    <option value="">Selecione...</option>
                    </select>
            </div>
        </div>
    </div>

    <div id="campos_comuns_escala_wrapper">
        <div class="form-row">
            <div class="form-group col-md-4" id="div_work_id_campo_unico">
                <label for="work_id_input">WorkID <span id="work_id_obrigatorio_asterisco" class="text-danger">*</span></label>
                <input type="text" class="form-control" id="work_id_input" name="work_id_text_input_disabled" 
                       value="<?php echo htmlspecialchars($work_id_form_php); ?>" maxlength="50">
                <select class="form-control" id="work_id_select" name="work_id_select_input_disabled">
                    <option value="">Selecione Linha e Data...</option>
                </select>
                <small class="form-text" id="work_id_sugestao_text"></small>
            </div>
            
            <div class="form-group col-md-4" id="wrapper_tabela_escalas">
                <label for="tabela_escalas">Nº Tabela da Escala</label>
                <input type="text" class="form-control" id="tabela_escalas" name="tabela_escalas" 
                       value="<?php echo htmlspecialchars($tabela_escalas_form_php); ?>" 
                       maxlength="2" 
                       pattern="\d{2}" 
                       title="Deve conter exatamente 2 dígitos numéricos."
                       placeholder="Ex: 01"
                       inputmode="numeric">
            </div>
            <div class="form-group col-md-4 d-flex align-items-center pt-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="eh_extra" name="eh_extra" <?php echo ($eh_extra_form_php==1)?'checked':'';?>>
                    <label class="form-check-label" for="eh_extra">Turno Extra?</label>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-3">
                <label for="hora_inicio_prevista">Hora Início <span class="text-danger">*</span></label>
                <input type="time" class="form-control" id="hora_inicio_prevista" name="hora_inicio_prevista" value="<?php echo htmlspecialchars($hora_inicio_form_php);?>">
            </div>
             <div class="form-group col-md-3">
                <label for="local_inicio_turno_id">Local Início <span class="text-danger">*</span></label>
                <select class="form-control select2-simple" id="local_inicio_turno_id" name="local_inicio_turno_id" data-placeholder="Selecione...">
                    <option value=""></option>
                    <?php foreach($lista_locais_select_php as $li):?>
                        <option value="<?php echo $li['id'];?>" data-tipo="<?php echo strtolower(htmlspecialchars($li['tipo']??''));?>" <?php if(strval($li['id'])==strval($local_inicio_id_form_php))echo 'selected';?>>
                            <?php echo htmlspecialchars($li['nome']);?>
                        </option>
                    <?php endforeach;?>
                </select>
            </div>
            <div class="form-group col-md-3">
                <label for="hora_fim_prevista">Hora Fim <span class="text-danger">*</span></label>
                <input type="time" class="form-control" id="hora_fim_prevista" name="hora_fim_prevista" value="<?php echo htmlspecialchars($hora_fim_form_php);?>">
            </div>
            <div class="form-group col-md-3">
                <label for="local_fim_turno_id">Local Fim <span class="text-danger">*</span></label>
                <select class="form-control select2-simple" id="local_fim_turno_id" name="local_fim_turno_id" data-placeholder="Selecione...">
                    <option value=""></option>
                     <?php foreach($lista_locais_select_php as $lf):?>
                        <option value="<?php echo $lf['id'];?>" data-tipo="<?php echo strtolower(htmlspecialchars($lf['tipo']??''));?>" <?php if(strval($lf['id'])==strval($local_fim_id_form_php))echo 'selected';?>>
                            <?php echo htmlspecialchars($lf['nome']);?>
                        </option>
                    <?php endforeach;?>
                </select>
            </div>
        </div>
        
        <div class="form-row" id="wrapper_veiculo_ajax" style="display: none;">
            <div class="form-group col-md-4">
                <label for="veiculo_id_ajax">Veículo <span class="text-danger" id="veiculo_obrigatorio_asterisco">*</span></label>
                <select class="form-control" id="veiculo_id_ajax" name="veiculo_id" required>
                    <option value="">Aguardando seleção de linha e horários...</option>
                    <?php if ($modo_edicao_escala_php && !empty($veiculo_id_db_php) && !empty($veiculo_prefixo_db_php) && $tipo_escala_form_php === 'linha'): ?>
                        <option value="<?php echo htmlspecialchars($veiculo_id_db_php); ?>" selected>
                            <?php echo htmlspecialchars($veiculo_prefixo_db_php); ?> (Salvo)
                        </option>
                    <?php endif; ?>
                </select>
                <small id="veiculo_id_ajax_feedback" class="form-text"></small>
            </div>
        </div>

    </div>
    <hr>
    <button type="submit" name="salvar_escala_planejada" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Entrada</button>
    <a href="escala_planejada_listar.php?<?php echo http_build_query(array_intersect_key($_GET, array_flip(['pagina', 'data_escala', 'tipo_busca_adicional', 'valor_busca_adicional']))); ?>" class="btn btn-secondary">Cancelar</a>
</form>

<?php
ob_start();
?>
<script>
    // --- DADOS PHP PARA JS ---
    const funcoesOperacionaisData = <?php echo json_encode($js_funcoes_operacionais_data); ?>;
    const todosOsLocaisData = <?php echo json_encode($js_locais_data_todos); ?>;
    var valorOriginalWorkIdJs = <?php echo json_encode($js_work_id_inicial_php); ?>;
    const escalaIdAtualJs = <?php echo json_encode($js_escala_id_atual_php); ?>;
    let veiculoIdAtualPhp = <?php echo json_encode($js_veiculo_id_atual_php); ?>;
    let veiculoPrefixoAtualPhp = <?php echo json_encode($js_veiculo_prefixo_atual_php); ?>;
    
    // Guarda o ID da linha salvo para pré-selecionar na edição
    let linhaIdOriginalPhp = '<?php echo $linha_origem_id_form_php ?? ""; ?>'; 

    // --- FUNÇÃO AUXILIAR: Normalizar Hora (25:00 -> 01:00) ---
    function normalizarHoraTransporte(horaStr) {
        if (!horaStr) return '';
        // Pega apenas HH:MM (ignora segundos se vierem)
        let partes = horaStr.split(':');
        if (partes.length < 2) return horaStr;

        let h = parseInt(partes[0]);
        let m = partes[1];

        // Se for 24h ou mais, subtrai 24 (ex: 25 -> 1)
        if (h >= 24) { 
            h = h - 24; 
        }

        // Formata para 2 dígitos (ex: 1 -> 01)
        let hFormatado = h < 10 ? '0' + h : h;
        
        // Retorna HH:MM limpo
        return hFormatado + ':' + m.substring(0, 2); 
    }

    // --- FUNÇÃO AJAX WORKID ---
    function verificarWorkIDDetalhes() {
        let wid = '';
        if ($('#tipo_escala_select').val() === 'linha') {
            wid = $('#work_id_select').val();
        } else {
            wid = $('#work_id_input').val();
        }
        let data = $('#data_escala').val();

        if (!wid || !data) {
            $('#aviso_multiplas_linhas').slideUp();
            return;
        }

        const $horaInicio = $('#hora_inicio_prevista');
        const $horaFim = $('#hora_fim_prevista');
        const $checksStatus = $('.status-escala-check');

        $.ajax({
            url: 'ajax_check_workid.php',
            type: 'POST',
            data: { work_id: wid, data: data },
            dataType: 'json',
            success: function(response) {
                if (response.sucesso) {
                    
                    // 1. PREENCHE HORÁRIOS (COM CORREÇÃO DE FORMATO 24H+)
                    if (!$checksStatus.is(':checked')) {
                        let inicioLimpo = normalizarHoraTransporte(response.inicio);
                        let fimLimpo = normalizarHoraTransporte(response.fim);

                        if(inicioLimpo) $horaInicio.val(inicioLimpo);
                        if(fimLimpo) $horaFim.val(fimLimpo);
                        
                        $horaInicio.trigger('change'); 
                    }

                    // 2. ATUALIZA O INPUT HIDDEN
                    if (response.linhas_texto) {
                        $('#linha_origem_id_hidden').val(response.linhas_texto);
                    }

                    // 3. AVISO VISUAL DE MÚLTIPLAS LINHAS
                    // O erro de JS parava a execução aqui antes. Agora deve funcionar.
                    if (parseInt(response.qtd_linhas) > 1 && $('#tipo_escala_select').val() === 'linha') {
                        $('#txt_linhas_extras').text(response.linhas_texto);
                        $('#aviso_multiplas_linhas').slideDown();
                    } else {
                        $('#aviso_multiplas_linhas').slideUp();
                    }

                } else {
                    $('#aviso_multiplas_linhas').slideUp();
                }
            },
            error: function() {
                console.log('Erro silencioso ao verificar WorkID');
            }
        });
    }

$(document).ready(function() {
    // --- SELETORES GLOBAIS ---
    const $tipoEscalaSelect = $('#tipo_escala_select');
    const $camposLinhaWrapper = $('#campos_escala_linha_wrapper');
    const $todosCamposFuncaoWrapper = $('#todos_campos_funcao_wrapper');
    const $funcaoSelect = $('#funcao_operacional_id_select');
    const $turnoFuncaoSelect = $('#turno_funcao_select');
    const $posicaoLetraWrapper = $('#wrapper_posicao_letra_funcao');
    const $posicaoLetraSelect = $('#posicao_letra_funcao_select');
    const $localInicioSelect = $('#local_inicio_turno_id');
    const $localFimSelect = $('#local_fim_turno_id');
    const $tabelaEscalasWrapper = $('#wrapper_tabela_escalas');
    const $camposComunsWrapper = $('#campos_comuns_escala_wrapper');
    const $statusCheckboxes = $('.status-escala-check');
    
    const $workIdInputText = $('#work_id_input');
    const $workIdSelectLinha = $('#work_id_select');
    const $workIdSugestaoText = $('#work_id_sugestao_text');
    const $linhaOrigemSelect = $('#linha_origem_id'); 
    const $dataEscalaInput = $('#data_escala');
    const $horaInicioInput = $('#hora_inicio_prevista');
    const $horaFimInput = $('#hora_fim_prevista');

    const $veiculoWrapper = $('#wrapper_veiculo_ajax');
    const $veiculoSelect = $('#veiculo_id_ajax');
    const $veiculoFeedback = $('#veiculo_id_ajax_feedback');
    const $tabelaEscalasInput = $('#tabela_escalas');

    // --- INICIALIZAÇÃO DE PLUGINS ---
    $('#motorista_id_select2_escala, #copiar_motorista_id_select2').select2({
        theme: 'bootstrap4', language: "pt-BR", width: '100%', allowClear: true,
        placeholder: 'Digite matrícula ou nome...',
        ajax: { 
            url: 'buscar_motoristas_ajax.php', dataType: 'json', delay: 250,
            data: function (params) { return { q: params.term, page: params.page || 1 }; },
            processResults: function (data, params) { params.page = params.page || 1; return { results: data.items, pagination: { more: (params.page * 10) < data.total_count } }; },
        },
        minimumInputLength: 2, escapeMarkup: function (m) { return m; },
        templateResult: function (d) { return d.text || "Buscando..."; },
        templateSelection: function (d) { return d.text || d.id; }
    });

    $('#linha_origem_id, #funcao_operacional_id_select, #local_inicio_turno_id, #local_fim_turno_id').each(function() {
        $(this).select2({ theme: 'bootstrap4', placeholder: $(this).data('placeholder') || 'Selecione...', allowClear: true, width: '100%' });
    });

    // Validação Tabela
    $tabelaEscalasInput.on('input', function() { this.value = this.value.replace(/\D/g, ''); });

    // --- CARREGAR LINHAS POR DATA ---
    function carregarLinhasPorData() {
        const data = $dataEscalaInput.val();
        
        if (!data) {
            $linhaOrigemSelect.empty().append('<option value="">Selecione primeiro a data...</option>').prop('disabled', true);
            return;
        }

        $linhaOrigemSelect.prop('disabled', true).html('<option value="">Buscando linhas do dia...</option>');

        $.ajax({
            url: 'ajax_buscar_linhas_data.php', 
            type: 'GET',
            data: { data: data },
            dataType: 'json',
            success: function(linhas) {
                $linhaOrigemSelect.empty();
                $linhaOrigemSelect.append('<option value="">Selecione...</option>');
                
                if (linhas && linhas.length > 0) {
                    linhas.forEach(function(l) {
                        let linhaSalvaPrincipal = linhaIdOriginalPhp ? linhaIdOriginalPhp.split('/')[0] : '';
                        const isSelected = (String(l.id) === String(linhaSalvaPrincipal));
                        $linhaOrigemSelect.append(new Option(l.text, l.id, false, isSelected));
                    });
                    $linhaOrigemSelect.prop('disabled', false);
                    
                    if (linhaIdOriginalPhp) {
                        $linhaOrigemSelect.trigger('change');
                    }
                } else {
                    $linhaOrigemSelect.append('<option value="">Nenhuma linha encontrada no relatório desta data</option>');
                    $linhaOrigemSelect.prop('disabled', false); 
                }
            },
            error: function() {
                $linhaOrigemSelect.prop('disabled', false).html('<option value="">Erro ao carregar linhas</option>');
            }
        });
    }

    // --- CARREGAR VEÍCULOS ---
    function carregarVeiculosDisponiveis() {
        const linhaId = $('#linha_origem_id_hidden').val() || $linhaOrigemSelect.val(); 
        const dataEscala = $dataEscalaInput.val();
        const horaInicio = $horaInicioInput.val();
        const horaFim = $horaFimInput.val();
        const tipoEscala = $tipoEscalaSelect.val();

        $veiculoWrapper.hide();
        $veiculoSelect.prop('required', false);

        if (tipoEscala === 'linha' && linhaId && dataEscala && horaInicio && horaFim && !$statusCheckboxes.is(':checked')) {
            $veiculoWrapper.show();
            $veiculoSelect.prop('required', true).prop('disabled', true).html('<option value="">Carregando veículos...</option>');
            $veiculoFeedback.removeClass('text-success text-danger').addClass('text-muted').text('Buscando veículos disponíveis...');

            $.ajax({
                url: 'buscar_veiculos_disponiveis_ajax.php',
                type: 'POST',
                data: {
                    linha_id: linhaId, 
                    data_escala: dataEscala,
                    hora_inicio: horaInicio,
                    hora_fim: horaFim,
                    escala_id_atual: escalaIdAtualJs || 0,
                    tabela_escala: 'planejada'
                },
                dataType: 'json',
                success: function(response) {
                    $veiculoSelect.prop('disabled', false).empty();
                    if (response.success && response.veiculos && response.veiculos.length > 0) {
                        $veiculoSelect.append('<option value="">Selecione um veículo...</option>');
                        let veiculoAtualNaLista = false;
                        $.each(response.veiculos, function(index, veiculo) {
                            const isSelected = (String(veiculo.id) === String(veiculoIdAtualPhp));
                            $veiculoSelect.append($('<option>', { value: veiculo.id, text: veiculo.text, selected: isSelected }));
                            if (isSelected) veiculoAtualNaLista = true;
                        });

                        if (escalaIdAtualJs > 0 && veiculoIdAtualPhp && !veiculoAtualNaLista) {
                             $veiculoSelect.append($('<option>', { value: veiculoIdAtualPhp, text: veiculoPrefixoAtualPhp + ' (SALVO - CONFLITO DETECTADO!)', selected: true, style: 'background-color: #f8d7da; color: #721c24;' }));
                        }
                        $veiculoFeedback.text(response.message).removeClass('text-danger text-muted').addClass('text-success');
                    } else {
                        $veiculoSelect.append('<option value="">Nenhum veículo disponível</option>');
                        $veiculoFeedback.text(response.message || 'Nenhum veículo disponível encontrado.').removeClass('text-success text-muted').addClass('text-danger');
                         if (escalaIdAtualJs > 0 && veiculoIdAtualPhp) {
                            $veiculoSelect.append($('<option>', { value: veiculoIdAtualPhp, text: veiculoPrefixoAtualPhp + ' (Salvo - Verificar)', selected: true, style: 'color:orange;' }));
                        }
                    }
                },
                error: function() {
                    $veiculoSelect.prop('disabled', false).html('<option value="">Erro ao carregar</option>');
                    $veiculoFeedback.text('Erro de comunicação ao buscar veículos.').removeClass('text-success text-muted').addClass('text-danger');
                }
            });
        }
    }
    
    // --- CARREGAR WORKIDS ---
    function carregarWorkIDsDisponiveis() {
        const linhaId = $linhaOrigemSelect.val(); 
        const dataEscala = $dataEscalaInput.val();
        const tipoEscalaAtual = $tipoEscalaSelect.val();

        if (tipoEscalaAtual === 'linha' && linhaId && dataEscala && !$statusCheckboxes.is(':checked')) {
            $workIdSelectLinha.prop('disabled', true).html('<option value="">Carregando WorkIDs do Relatório...</option>');
            
            $.ajax({
                url: 'ajax_buscar_workids_escala.php',
                type: 'GET', 
                data: { linha: linhaId, data: dataEscala }, 
                dataType: 'json',
                success: function(workids) {
                    $workIdSelectLinha.prop('disabled', false).empty();
                    
                    if (workids.length > 0) {
                        $workIdSelectLinha.append('<option value="">Selecione...</option>');
                        let encontrado = false;
                        
                        workids.forEach(function(wid) {
                            let isSelected = (String(wid) === String(valorOriginalWorkIdJs));
                            if(isSelected) encontrado = true;
                            $workIdSelectLinha.append(new Option(wid, wid, false, isSelected));
                        });

                        if (valorOriginalWorkIdJs && !encontrado && <?php echo json_encode($modo_edicao_escala_php); ?>) {
                             $workIdSelectLinha.append(new Option(valorOriginalWorkIdJs + ' (Salvo - Não encontrado no dia)', valorOriginalWorkIdJs, true, true));
                        }
                        
                        $workIdSugestaoText.html('<span class="text-success">WorkIDs carregados do histórico.</span>').show();
                        
                        if(valorOriginalWorkIdJs && encontrado) {
                            $workIdSelectLinha.trigger('change');
                        }

                    } else {
                        $workIdSelectLinha.append('<option value="">Nenhum WorkID encontrado para esta linha/data</option>');
                        if (valorOriginalWorkIdJs) {
                             $workIdSelectLinha.append(new Option(valorOriginalWorkIdJs, valorOriginalWorkIdJs, true, true));
                        }
                    }
                },
                error: function() { 
                    $workIdSelectLinha.prop('disabled', false).html('<option value="">Erro ao buscar</option>');
                }
            });
        } else if (tipoEscalaAtual === 'linha' && !$statusCheckboxes.is(':checked')) {
            $workIdSelectLinha.html('<option value="">Selecione Linha e Data...</option>');
            $workIdSugestaoText.html('').hide();
        }
    }

    function atualizarVisibilidadeCampos() { 
        const tipoSelecionado = $tipoEscalaSelect.val();
        let algumStatusMarcado = $statusCheckboxes.is(':checked');
        
        $workIdInputText.attr('name', 'work_id_text_input_disabled').hide();
        $workIdSelectLinha.attr('name', 'work_id_select_input_disabled').hide();
        
        $('#linha_origem_id, #funcao_operacional_id_select, #turno_funcao_select, #posicao_letra_funcao_select, #local_inicio_turno_id, #local_fim_turno_id, #hora_inicio_prevista, #hora_fim_prevista, #veiculo_id_ajax').prop('required', false);
        $workIdInputText.prop('required', false).prop('readonly', false);
        $workIdSelectLinha.prop('required', false);

        if (algumStatusMarcado) {
            $tipoEscalaSelect.prop('disabled', true).val('linha').trigger('change.select2');
            $camposLinhaWrapper.hide();
            $todosCamposFuncaoWrapper.hide();
            $camposComunsWrapper.find('input[type="time"], #tabela_escalas').val('');
            $camposComunsWrapper.find('select.select2-simple:not(#motorista_id_select2_escala)').val(null).trigger('change').prop('disabled', true);
            $camposComunsWrapper.find('#eh_extra').prop('checked', false).prop('disabled',true);
            $camposComunsWrapper.find('input:not(#work_id_input, #data_escala)').prop('disabled', true);
            $tabelaEscalasWrapper.hide();
            $veiculoWrapper.hide();
            let valorWorkIdParaStatus = '';
            $statusCheckboxes.each(function() { if ($(this).is(':checked')) { valorWorkIdParaStatus = $(this).val(); return false; }});
            
            $workIdInputText.val(valorWorkIdParaStatus).show().prop('readonly', true).prop('required', true).attr('name', 'work_id');
            $workIdSelectLinha.hide().val(null).trigger('change');
            $workIdSugestaoText.removeClass('feedback-loading feedback-success feedback-error feedback-info').addClass('feedback-secondary-text').html('<span>WorkID definido pelo status.</span>').show();
        } else {
            $tipoEscalaSelect.prop('disabled', false);
            $camposComunsWrapper.find('select.select2-simple:not(#motorista_id_select2_escala), input[type="time"], #tabela_escalas, #eh_extra').prop('disabled', false);
            $('#hora_inicio_prevista, #hora_fim_prevista, #local_inicio_turno_id, #local_fim_turno_id').prop('required', true);

            if (tipoSelecionado === 'linha') {
                $camposLinhaWrapper.show();
                $todosCamposFuncaoWrapper.hide();
                $funcaoSelect.val(null).trigger('change.select2');
                $('#linha_origem_id').prop('required', true);
                
                $workIdSelectLinha.show().prop('required', true).attr('name', 'work_id');
                $tabelaEscalasWrapper.show();
                
            } else if (tipoSelecionado === 'funcao') {
                $camposLinhaWrapper.hide();
                $todosCamposFuncaoWrapper.show();
                $('#linha_origem_id, #veiculo_id_ajax').val(null).trigger('change.select2');
                $funcaoSelect.prop('required', true);
                $turnoFuncaoSelect.prop('required', true);
                
                $workIdInputText.show().prop('required', true).prop('readonly', false).attr('name', 'work_id');
                
                $tabelaEscalasWrapper.hide(); $('#tabela_escalas').val('');
                $workIdInputText.prop('placeholder', 'WorkID será sugerido pela função');
                $veiculoWrapper.hide();
                atualizarCamposFuncao(); 
                montarWorkIDSugerido();
            }
            
            const workIdAtualUpper = $workIdInputText.val().toUpperCase();
            const statusEspeciaisForm = ['FOLGA', 'FALTA', 'FORADEESCALA', 'FÉRIAS', 'ATESTADO'];
            if (statusEspeciaisForm.includes(workIdAtualUpper)) { 
                if (valorOriginalWorkIdJs && !statusEspeciaisForm.includes(valorOriginalWorkIdJs.toUpperCase())) {
                    if(tipoSelecionado === 'linha') { } 
                    else { $workIdInputText.val(valorOriginalWorkIdJs); }
                } else { 
                     if(tipoSelecionado !== 'linha'){ $workIdInputText.val(''); }
                }
            }
        }
        carregarVeiculosDisponiveis();
    }
    
    function montarWorkIDSugerido() {
        const tipoEscala = $tipoEscalaSelect.val();
        if (tipoEscala !== 'funcao' || $statusCheckboxes.is(':checked')) { 
            if (tipoEscala !== 'linha') { $workIdSugestaoText.text('').hide(); }
            return;
        }
        const funcaoId = $funcaoSelect.val();
        if (!funcaoId || !funcoesOperacionaisData[funcaoId]) { $workIdSugestaoText.text('').hide(); return; }
        const funcaoData = funcoesOperacionaisData[funcaoId];
        const prefixo = funcaoData.work_id_prefixo;
        const turno = $turnoFuncaoSelect.val();
        const requerPosicao = (String(funcaoData.requer_posicao_especifica).toLowerCase() === 'true' || funcaoData.requer_posicao_especifica === 1 || funcaoData.requer_posicao_especifica === true);
        const posicao = $posicaoLetraSelect.val();
        let sugestao = '';
        if (prefixo && turno) {
            sugestao = prefixo;
            if (!funcaoData.local_fixo_id && $localInicioSelect.val()) {
                let nomeLocalCompleto = $localInicioSelect.find('option:selected').text();
                let nomeLocalCurto = '';
                if(nomeLocalCompleto && nomeLocalCompleto.trim().toLowerCase() !== 'selecione...' && nomeLocalCompleto.trim() !== ''){
                    let partesNomeLocal = nomeLocalCompleto.split(' ');
                    if (partesNomeLocal.length > 1 && partesNomeLocal[0].toUpperCase() === 'T.') {
                        nomeLocalCurto = "T" + (partesNomeLocal[1] ? partesNomeLocal[1].substring(0,1).toUpperCase() : '');
                    } else { nomeLocalCurto = nomeLocalCompleto.substring(0,3).toUpperCase().replace(/[^A-Z0-9]/g, '');}
                    if(nomeLocalCurto) sugestao += '-' + nomeLocalCurto;
                }
            }
            sugestao += '-' + turno;
            if (requerPosicao && posicao) { sugestao += posicao.toUpperCase(); }
            $workIdInputText.val(sugestao); 
            $workIdSugestaoText.removeClass('feedback-loading feedback-error feedback-info').addClass('feedback-secondary-text').html('<span>WorkID Sugerido: ' + sugestao + '</span>').show();
        } else { $workIdSugestaoText.text('').hide(); }
    }
    
    function atualizarCamposFuncao(dadosCopia = null) {
        const funcaoId = $funcaoSelect.val();
        let turnoParaSetar = dadosCopia ? dadosCopia.turno_funcao_detectado : <?php echo json_encode($turno_funcao_form_php); ?>;
        let posicaoParaSetar = dadosCopia ? dadosCopia.posicao_letra_detectada : <?php echo json_encode($posicao_letra_form_php); ?>;
        let localInicioParaSetar = dadosCopia ? dadosCopia.localInicio : <?php echo json_encode($local_inicio_id_form_php); ?>;
        let localFimParaSetar = dadosCopia ? dadosCopia.localFim : <?php echo json_encode($local_fim_id_form_php); ?>;

        $posicaoLetraWrapper.hide(); $posicaoLetraSelect.prop('required', false).val('');
        if (!funcaoId || !funcoesOperacionaisData[funcaoId]) {
            $turnoFuncaoSelect.html('<option value="">Selecione a função...</option>').prop('disabled', true).val('');
            filtrarLocais(null, 'qualquer', null, localInicioParaSetar, localFimParaSetar);
            $localInicioSelect.prop('disabled', false).prop('required',true);
            $localFimSelect.prop('disabled', false).prop('required',true);
            montarWorkIDSugerido(); return;
        }
        const funcaoData = funcoesOperacionaisData[funcaoId];
        const turnosArray = funcaoData.turnos_disponiveis ? String(funcaoData.turnos_disponiveis).split(',') : [];
        $turnoFuncaoSelect.html('<option value="">Selecione o turno...</option>');
        const turnoNomes = {'01': 'Manhã', '02': 'Tarde', '03': 'Noite'};
        turnosArray.forEach(function(turno) { $turnoFuncaoSelect.append(new Option(turnoNomes[turno.trim()] || 'Turno ' + turno.trim(), turno.trim())); });
        $turnoFuncaoSelect.prop('disabled', false).prop('required', true).val(turnoParaSetar).trigger('change');
        const requerPosicao = (String(funcaoData.requer_posicao_especifica).toLowerCase() === 'true' || funcaoData.requer_posicao_especifica === 1 || funcaoData.requer_posicao_especifica === true);
        if (requerPosicao && funcaoData.max_posicoes_por_turno > 0) {
            $posicaoLetraSelect.html('<option value="">Selecione...</option>');
            for (let i = 0; i < funcaoData.max_posicoes_por_turno; i++) { let letra = String.fromCharCode(65 + i); $posicaoLetraSelect.append(new Option(letra, letra)); }
            $posicaoLetraWrapper.show(); $posicaoLetraSelect.prop('required', true).val(posicaoParaSetar).trigger('change');
        }
        filtrarLocais(funcaoData.local_fixo_id, funcaoData.locais_permitidos_tipo, funcaoData.locais_permitidos_ids, localInicioParaSetar, localFimParaSetar);
        if (funcaoData.local_fixo_id) { $localInicioSelect.prop('required', false); $localFimSelect.prop('required', false);
        } else { $localInicioSelect.prop('disabled', false).prop('required', true); $localFimSelect.prop('disabled', false).prop('required', true); }
        montarWorkIDSugerido();
    }
    
    function filtrarLocais(localFixoId, tipoPermitido, idsPermitidosStr, valorPreselecaoInicio = null, valorPreselecaoFim = null) {
        const idsPermitidos = idsPermitidosStr ? String(idsPermitidosStr).split(',').map(id => String(id).trim()) : [];
        let valorSelecionarInicio = valorPreselecaoInicio !== null ? valorPreselecaoInicio : $localInicioSelect.val();
        let valorSelecionarFim = valorPreselecaoFim !== null ? valorPreselecaoFim : $localFimSelect.val();
        $localInicioSelect.html('<option value=""></option>'); $localFimSelect.html('<option value=""></option>');    
        todosOsLocaisData.forEach(function(local) {
            let incluirLocal = false;
            if (localFixoId && String(local.id) === String(localFixoId)) { incluirLocal = true; valorSelecionarInicio = local.id; valorSelecionarFim = local.id;
            } else if (!localFixoId && tipoPermitido && tipoPermitido.toLowerCase() !== 'qualquer' && tipoPermitido.toLowerCase() !== 'nenhum') {
                if (local.tipo === tipoPermitido.toLowerCase()) { if (idsPermitidos.length > 0) { if (idsPermitidos.includes(String(local.id))) incluirLocal = true; }  else { incluirLocal = true; } }
            } else if (!localFixoId && (!tipoPermitido || tipoPermitido.toLowerCase() === 'qualquer' || tipoPermitido.toLowerCase() === 'nenhum')) { incluirLocal = true; }
            if (incluirLocal) { $localInicioSelect.append(new Option(local.text, local.id)); $localFimSelect.append(new Option(local.text, local.id)); }
        });
        $localInicioSelect.val(valorSelecionarInicio).trigger('change.select2'); $localFimSelect.val(valorSelecionarFim).trigger('change.select2');
        if (localFixoId) { $localInicioSelect.prop('disabled', true); $localFimSelect.prop('disabled', true);
        } else { $localInicioSelect.prop('disabled', false); $localFimSelect.prop('disabled', false); }
    }


    // --- EVENT LISTENERS E CHAMADAS ---
    
    // ATUALIZAR HIDDEN QUANDO MUDA O SELECT DE LINHA
    $linhaOrigemSelect.on('change', function() {
        var valorSimples = $(this).val();
        // Atualiza o hidden imediatamente com o valor simples
        $('#linha_origem_id_hidden').val(valorSimples);
        
        // Dispara as cargas dependentes
        carregarWorkIDsDisponiveis();
        carregarVeiculosDisponiveis();
    });

    // NOVOS LISTENERS PARA O WORKID
    $workIdSelectLinha.on('change', function() {
        verificarWorkIDDetalhes();
    });

    $workIdInputText.on('blur', function() {
        verificarWorkIDDetalhes();
    });

    $dataEscalaInput.on('change', function() {
        if (document.readyState === 'complete') {
            linhaIdOriginalPhp = ''; 
            $workIdSelectLinha.empty().append('<option value="">Selecione Linha e Data...</option>');
            $veiculoSelect.empty().append('<option value="">Aguardando...</option>');
        }
        if ($tipoEscalaSelect.val() === 'linha') {
            carregarLinhasPorData(); 
        }
        // Se mudar a data e já tiver WorkID, verifica novamente com delay
        setTimeout(function(){ 
            if ($workIdSelectLinha.val() || $workIdInputText.val()) {
                verificarWorkIDDetalhes();
            }
        }, 500); 
    });

    $tipoEscalaSelect.on('change', atualizarVisibilidadeCampos);
    $funcaoSelect.on('change', function(){ $turnoFuncaoSelect.val(null).trigger('change'); $posicaoLetraSelect.val(null).trigger('change'); atualizarCamposFuncao(); });
    $turnoFuncaoSelect.on('change', montarWorkIDSugerido);
    $posicaoLetraSelect.on('change', montarWorkIDSugerido);
    $localInicioSelect.on('change', montarWorkIDSugerido);

    $statusCheckboxes.on('change', function() {
        const $checkboxAtual = $(this);
        if ($checkboxAtual.is(':checked')) {
            $statusCheckboxes.not($checkboxAtual).prop('checked', false);
        }
        atualizarVisibilidadeCampos();
    });
    
    // Quando muda Linha -> Carrega WorkID e Veículos
    // (Listener JÁ DEFINIDO ACIMA no atualizar hidden, não duplicar)
    
    $horaInicioInput.on('change', carregarVeiculosDisponiveis);
    $horaFimInput.on('change', carregarVeiculosDisponiveis);

    // --- Lógica de Cópia ---
    $('#btnBuscarCopiarEscala').on('click', function() {
        var motoristaOrigemId = $('#copiar_motorista_id_select2').val();
        var dataOrigem = $('#copiar_data_escala_input').val();
        var $feedbackDivCopia = $('#copiar_escala_feedback');
        
        if (!motoristaOrigemId || !dataOrigem) { 
            $feedbackDivCopia.html('<small class="text-danger">Selecione Motorista e Data de Origem.</small>'); 
            return; 
        }
        $feedbackDivCopia.html('<small class="text-info"><i class="fas fa-spinner fa-spin"></i> Buscando...</small>');
        
        $.ajax({
            url: 'buscar_escala_para_copia_ajax.php', 
            type: 'GET', 
            dataType: 'json',
            data: { motorista_id: motoristaOrigemId, data_escala: dataOrigem },
            success: function(response) {
                if (response.success && response.escala) {
                    var esc = response.escala;
                    $statusCheckboxes.prop('checked', false); 
                    valorOriginalWorkIdJs = esc.work_id || '';
                    veiculoIdAtualPhp = esc.veiculo_id || ''; 
                    veiculoPrefixoAtualPhp = esc.prefixo_veiculo_atual || '';

                    var workIdCopiadoUpper = (esc.work_id || '').toUpperCase();
                    const statusEspeciaisCopia = ['FOLGA', 'FALTA', 'FORADEESCALA', 'FÉRIAS', 'ATESTADO'];
                    let copiouStatusEspecial = statusEspeciaisCopia.includes(workIdCopiadoUpper);

                    if(copiouStatusEspecial) { $('#is_' + workIdCopiadoUpper.toLowerCase() + '_check').prop('checked', true); }
                    
                    let tipoEscalaCopiada = 'linha';
                    if (esc.funcao_operacional_id) { tipoEscalaCopiada = 'funcao'; } 
                    else if (!esc.linha_origem_id && !copiouStatusEspecial && esc.work_id) {
                        let funcaoInferida = null;
                        for (const idFuncao in funcoesOperacionaisData) {
                            if (esc.work_id && esc.work_id.startsWith(funcoesOperacionaisData[idFuncao].work_id_prefixo)) {
                                funcaoInferida = funcoesOperacionaisData[idFuncao]; break;
                            }
                        }
                        if (funcaoInferida) { tipoEscalaCopiada = 'funcao'; esc.funcao_operacional_id = funcaoInferida.id; }
                    }
                    $tipoEscalaSelect.val(tipoEscalaCopiada).trigger('change');
                    
                    setTimeout(function() {
                        if (tipoEscalaCopiada === 'funcao') {
                            var dadosParaFuncaoCopia = { 
                                turno_funcao_detectado: esc.turno_funcao_detectado || '', 
                                posicao_letra_detectada: esc.posicao_letra_detectada || '', 
                                localInicio: esc.local_inicio_turno_id, 
                                localFim: esc.local_fim_turno_id 
                            };
                            $funcaoSelect.val(esc.funcao_operacional_id || null).trigger('change');
                            atualizarCamposFuncao(dadosParaFuncaoCopia);
                            $('#work_id_input').val(valorOriginalWorkIdJs);
                        } else { 
                            // Define a linha para ser carregada no Select
                            linhaIdOriginalPhp = esc.linha_origem_id;
                            // Se tiver data já preenchida (do form), chama carregamento
                            if ($dataEscalaInput.val()) carregarLinhasPorData();
                            
                            if (!copiouStatusEspecial) { $('#tabela_escalas').val(esc.tabela_escalas || ''); }
                        }
                        
                        $('#hora_inicio_prevista').val(esc.hora_inicio_prevista ? esc.hora_inicio_prevista.substring(0, 5) : '');
                        $('#local_inicio_turno_id').val(esc.local_inicio_turno_id || null).trigger('change');
                        $('#hora_fim_prevista').val(esc.hora_fim_prevista ? esc.hora_fim_prevista.substring(0, 5) : '');
                        $('#local_fim_turno_id').val(esc.local_fim_turno_id || null).trigger('change');
                        $('#eh_extra').prop('checked', esc.eh_extra == 1);
                        
                        if (copiouStatusEspecial) { $statusCheckboxes.filter(':checked').trigger('change'); } 
                        else { atualizarVisibilidadeCampos(); }
                        
                        $feedbackDivCopia.html('<small class="text-success"><i class="fas fa-check"></i> Dados preenchidos.</small>');
                    }, 500);

                } else { $feedbackDivCopia.html('<small class="text-warning">' + (response.message || 'Nenhuma escala encontrada.') + '</small>'); }
            }, 
            error: function() { $feedbackDivCopia.html('<small class="text-danger">Erro de comunicação ao buscar dados.</small>'); }
        });
    });

    // --- Validação e Submit ---
    $('#form-escala-planejada').on('submit', function(e) {
        if ($(this).data('validado') === true) {
            return true;
        }

        e.preventDefault(); 
        var $form = $(this);
        var isStatusChecked = $statusCheckboxes.is(':checked');
        
        if ($tabelaEscalasInput.val() !== "" && !/^\d{2}$/.test($tabelaEscalasInput.val())) {
            alert('O campo "Nº Tabela da Escala" deve conter 2 dígitos numéricos.'); $tabelaEscalasInput.focus(); return false;
        }

        if (!isStatusChecked) {
            const tipoEscalaAtualSubmit = $tipoEscalaSelect.val();
            if (tipoEscalaAtualSubmit === 'linha' && (!$('#linha_origem_id').val())) { alert('Linha de Origem é obrigatória.'); return false; }
            if (tipoEscalaAtualSubmit === 'funcao' && (!$('#funcao_operacional_id_select').val())) { alert('Função Operacional é obrigatória.'); return false; }
            if (tipoEscalaAtualSubmit === 'funcao' && (!$('#turno_funcao_select').val())) { alert('Turno da Função é obrigatório.'); return false; }
            
            let workIdValueToSubmit = (tipoEscalaAtualSubmit === 'linha') ? $workIdSelectLinha.val() : $workIdInputText.val().trim();
            if (!workIdValueToSubmit) { alert('WorkID é obrigatório.'); return false; }

            if (tipoEscalaAtualSubmit === 'linha' && (!$veiculoSelect.val() || $veiculoSelect.val() === "")) { alert('O campo Veículo é obrigatório.'); $veiculoSelect.focus(); return false; }

            if ($('#hora_inicio_prevista').val() === '' || $('#hora_fim_prevista').val() === '') { alert('Horários são obrigatórios.'); return false; }
            if ((!$('#local_inicio_turno_id').val() || !$('#local_fim_turno_id').val()) && !$('#local_inicio_turno_id').is(':disabled') ) { alert('Locais são obrigatórios.'); return false; }
        }

        // VERIFICAÇÃO DE CONFLITO
        var motoristaId = $('#motorista_id_select2_escala').val();
        var dataEscala = $('#data_escala').val();
        var idIgnorar = $('input[name="escala_id"]').val() || 0;

        if (motoristaId && dataEscala) {
            var $btnSubmit = $form.find('button[type="submit"]');
            var textoOriginal = $btnSubmit.html();
            $btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Verificando...');

            $.ajax({
                url: 'ajax_verificar_conflito_motorista.php',
                type: 'GET',
                dataType: 'json',
                data: { motorista_id: motoristaId, data: dataEscala, id_ignorar: idIgnorar },
                success: function(resp) {
                $btnSubmit.prop('disabled', false).html(textoOriginal);

                if (resp.existe) {
                    var estouInserindoStatus = $statusCheckboxes.is(':checked');
                    var existeStatusNoBanco = (resp.tipo_existente === 'status');

                    if (!estouInserindoStatus && !existeStatusNoBanco) {
                        $form.data('validado', true);
                        $form.submit();
                        return;
                    }

                    var msg = `ATENÇÃO: O motorista já possui um status/escala neste dia:\n\n >> ${resp.descricao} <<\n\nDeseja SUBSTITUIR o registro existente por este novo?`;
                    
                    if (confirm(msg)) {
                        $('input[name="escala_id"]').remove();
                        $form.append('<input type="hidden" name="escala_id" value="' + resp.id_conflito + '">');
                        $form.data('validado', true);
                        $form.submit();
                    } else {
                        return false;
                    }
                } else {
                    $form.data('validado', true);
                    $form.submit();
                }
            },
                error: function() {
                    $btnSubmit.prop('disabled', false).html(textoOriginal);
                    if(confirm('Erro ao verificar disponibilidade. Deseja tentar salvar mesmo assim?')) {
                        $form.data('validado', true);
                        $form.submit();
                    }
                }
            });
        } else {
            $form.data('validado', true);
            $form.submit();
        }
    });

    // --- INICIALIZAÇÃO FINAL ---
    atualizarVisibilidadeCampos(); 
    if(<?php echo json_encode($modo_edicao_escala_php); ?>) {
        if ($tipoEscalaSelect.val() === 'funcao' && $funcaoSelect.val()) {
             atualizarCamposFuncao();
        }
        if ($tipoEscalaSelect.val() === 'linha' && $dataEscalaInput.val() && !$statusCheckboxes.is(':checked')) {
             carregarLinhasPorData();
        }
        // NOVO: Verifica o WorkID ao carregar a edição
        setTimeout(verificarWorkIDDetalhes, 800); 
    } else {
        if ($tipoEscalaSelect.val() === 'linha') {
            $linhaOrigemSelect.prop('disabled', true).html('<option value="">Selecione primeiro a data...</option>');
        }
    }
});
</script>
<?php
$page_specific_js = ob_get_clean();
require_once 'admin_footer.php';
?>