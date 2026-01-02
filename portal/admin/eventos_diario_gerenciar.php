<?php
// admin/eventos_diario_gerenciar.php
// Gerencia os eventos do Diário de Bordo para uma Programação Diária (Bloco) específica.
// ATUALIZADO: Com select dinâmico para Informações Adicionais via AJAX.

require_once 'auth_check.php';

// --- Permissões ---
$niveis_permitidos_gerenciar_eventos = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
$niveis_permitidos_ver_eventos = ['CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];

if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_ver_eventos)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para visualizar os eventos do diário de bordo.";
    header('Location: eventos_diario_pesquisar.php');
    exit;
}
$pode_gerenciar_eventos = in_array($admin_nivel_acesso_logado, $niveis_permitidos_gerenciar_eventos);

require_once '../db_config.php';

// --- Obter e Validar Parâmetros da URL e Bloco ---
$programacao_id = isset($_REQUEST['programacao_id']) ? filter_var($_REQUEST['programacao_id'], FILTER_VALIDATE_INT) : null;
$nome_bloco_param_url = isset($_REQUEST['nome_bloco']) ? urldecode($_REQUEST['nome_bloco']) : 'Desconhecido';
$dia_tipo_param_url = isset($_REQUEST['dia_tipo']) ? urldecode($_REQUEST['dia_tipo']) : '';

$tipos_dia_semana_map_eventos = ['Uteis' => 'Dias Úteis', 'Sabado' => 'Sábado', 'DomingoFeriado' => 'Domingo/Feriado'];

if (!$programacao_id) {
    $_SESSION['admin_error_message'] = "ID da Programação (Bloco) não fornecido."; // Ajustado conforme seu arquivo
    header('Location: eventos_diario_pesquisar.php');
    exit;
}

$bloco_info = null;
$nome_bloco_display = $nome_bloco_param_url;
$dia_tipo_do_bloco_atual = $dia_tipo_param_url;
$dia_tipo_legivel_display = $tipos_dia_semana_map_eventos[$dia_tipo_do_bloco_atual] ?? $dia_tipo_do_bloco_atual;

if ($pdo) {
    try {
        $stmt_bloco = $pdo->prepare("SELECT id, work_id, dia_semana_tipo FROM programacao_diaria WHERE id = :pid");
        $stmt_bloco->bindParam(':pid', $programacao_id, PDO::PARAM_INT);
        $stmt_bloco->execute();
        $bloco_info = $stmt_bloco->fetch(PDO::FETCH_ASSOC);

        if (!$bloco_info) {
            $_SESSION['admin_error_message'] = "Bloco de Programação ID {$programacao_id} não encontrado."; // Ajustado conforme seu arquivo
            header('Location: eventos_diario_pesquisar.php');
            exit;
        }
        $nome_bloco_display = htmlspecialchars($bloco_info['work_id']);
        $dia_tipo_do_bloco_atual = $bloco_info['dia_semana_tipo'];
        $dia_tipo_legivel_display = htmlspecialchars($tipos_dia_semana_map_eventos[$dia_tipo_do_bloco_atual] ?? $dia_tipo_do_bloco_atual);
    } catch (PDOException $e) {
        $_SESSION['admin_error_message'] = "Erro ao buscar informações do Bloco: " . $e->getMessage();
        error_log("Erro PDO ao buscar bloco (ID: {$programacao_id}) em eventos_diario_gerenciar: " . $e->getMessage());
        header('Location: eventos_diario_pesquisar.php');
        exit;
    }
} else {
     $_SESSION['admin_error_message'] = "Falha na conexão com o banco de dados.";
     header('Location: eventos_diario_pesquisar.php');
     exit;
}

// --- Título da Página ---
$page_title = 'Diário de Bordo: ' . $nome_bloco_display . ' (' . $dia_tipo_legivel_display . ')';

// --- Inicialização de Variáveis para o Formulário de Evento ---
$evento_id_edicao = null;
$modo_edicao_evento = false;
$sequencia_form = ''; $linha_atual_id_form = ''; $numero_tabela_evento_form = '';
$workid_eventos_form = ''; $local_id_form = ''; $horario_chegada_form = '';
$horario_saida_form = ''; $info_form = ''; // $info_form guardará o TEXTO da info para pré-seleção

// Carregar listas para selects do formulário (Linhas e Locais)
$lista_linhas_eventos_select = []; $lista_locais_eventos_select = [];
if($pdo) {
    try {
        $stmt_linhas_ev = $pdo->query("SELECT id, numero, nome FROM linhas WHERE status_linha = 'ativa' ORDER BY CAST(numero AS UNSIGNED), numero, nome ASC");
        $lista_linhas_eventos_select = $stmt_linhas_ev->fetchAll(PDO::FETCH_ASSOC);
        $stmt_locais_ev = $pdo->query("SELECT id, nome FROM locais ORDER BY nome ASC");
        $lista_locais_eventos_select = $stmt_locais_ev->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $_SESSION['admin_warning_message'] = "Erro ao carregar Linhas/Locais: " . $e->getMessage(); }
}

// --- URL Base e Link de Voltar (Preservando Filtros da Pesquisa de Blocos) ---
$url_base_params_array = ['programacao_id' => $programacao_id, 'nome_bloco' => $bloco_info['work_id'], 'dia_tipo' => $dia_tipo_do_bloco_atual];
$query_string_origem_filtros_array = [];
if(isset($_REQUEST['pagina_origem'])) $query_string_origem_filtros_array['pagina_origem'] = $_REQUEST['pagina_origem'];
if(isset($_REQUEST['busca_tabela_work_id_origem'])) $query_string_origem_filtros_array['busca_tabela_work_id_origem'] = $_REQUEST['busca_tabela_work_id_origem'];
if(isset($_REQUEST['busca_dia_tipo_origem'])) $query_string_origem_filtros_array['busca_dia_tipo_origem'] = $_REQUEST['busca_dia_tipo_origem'];

$url_base_acao_eventos_com_filtros = "eventos_diario_gerenciar.php?" . http_build_query(array_merge($url_base_params_array, $query_string_origem_filtros_array));

$link_voltar_pesquisa_params_array = ['busca_tabela_work_id' => $bloco_info['work_id'], 'busca_dia_tipo' => $dia_tipo_do_bloco_atual, 'pesquisar_bloco_submit' => '1'];
if(isset($_REQUEST['pagina_origem'])) $link_voltar_pesquisa_params_array['pagina'] = $_REQUEST['pagina_origem'];
$link_voltar_pesquisa_eventos = 'eventos_diario_pesquisar.php?' . http_build_query($link_voltar_pesquisa_params_array);


// --- LÓGICA DE PROCESSAMENTO: APAGAR EVENTO (via GET) ---
if (isset($_GET['acao_evento']) && $_GET['acao_evento'] == 'apagar' && isset($_GET['evento_id']) && $pode_gerenciar_eventos) {
    $evento_id_apagar_get = filter_var($_GET['evento_id'], FILTER_VALIDATE_INT);
    $token_del_ev_get = $_GET['token_del_ev'] ?? ''; 
    if(empty($token_del_ev_get)){ $_SESSION['admin_error_message'] = "Ação inválida (token ausente)."; }
    elseif($evento_id_apagar_get && $pdo){
        try {
            $stmt_del_ev = $pdo->prepare("DELETE FROM diario_bordo_eventos WHERE id = :eid_del AND programacao_id = :pid_del_ev");
            $stmt_del_ev->bindParam(':eid_del', $evento_id_apagar_get, PDO::PARAM_INT);
            $stmt_del_ev->bindParam(':pid_del_ev', $programacao_id, PDO::PARAM_INT);
            if ($stmt_del_ev->execute() && $stmt_del_ev->rowCount() > 0) {
                $_SESSION['admin_success_message'] = "Evento apagado com sucesso.";
            } else { $_SESSION['admin_warning_message'] = "Evento não encontrado para apagar ou não pertence a este bloco.";}
        } catch (PDOException $e_del_ev) { $_SESSION['admin_error_message'] = "Erro ao apagar evento: " . $e_del_ev->getMessage();}
    } else { $_SESSION['admin_error_message'] = "Parâmetros inválidos para apagar."; }
    header("Location: " . $url_base_acao_eventos_com_filtros); exit;
}

// --- LÓGICA DE PROCESSAMENTO: Formulário ADICIONAR/EDITAR EVENTO (via POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_evento_diario']) && $pode_gerenciar_eventos) {
    $evento_id_post = filter_input(INPUT_POST, 'evento_id_hidden', FILTER_VALIDATE_INT);
    $sequencia_post_input = trim($_POST['sequencia'] ?? '');
    $linha_atual_id_post = filter_input(INPUT_POST, 'linha_atual_id', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
    $numero_tabela_evento_post = trim($_POST['numero_tabela_evento'] ?? '');
    $workid_eventos_post = trim($_POST['workid_eventos'] ?? '');
    $local_id_post = filter_input(INPUT_POST, 'local_id', FILTER_VALIDATE_INT);
    $horario_chegada_post_str = trim($_POST['horario_chegada'] ?? '');
    $horario_saida_post_str = trim($_POST['horario_saida'] ?? '');
    $info_post = trim($_POST['info'] ?? ''); 
    $erros_form_evento = [];

    // VALIDAÇÃO Sequência
    $sequencia_post_validada = null;
    if ($sequencia_post_input === '') { $erros_form_evento[] = "O campo Sequência é obrigatório."; }
    elseif (!ctype_digit($sequencia_post_input)) { $erros_form_evento[] = "Sequência deve conter apenas números inteiros não negativos."; }
    else {
        $sequencia_post_validada = (int)$sequencia_post_input;
        if ($sequencia_post_validada < 0) { $erros_form_evento[] = "Sequência não pode ser negativa."; }
        else {
            if ($pdo) {
                $sql_check_seq = "SELECT id FROM diario_bordo_eventos WHERE programacao_id = :pid_seq AND sequencia = :seq_val";
                $params_seq = [':pid_seq' => $programacao_id, ':seq_val' => $sequencia_post_validada];
                if($evento_id_post) { $sql_check_seq .= " AND id != :eid_seq"; $params_seq[':eid_seq'] = $evento_id_post; }
                $stmt_seq = $pdo->prepare($sql_check_seq); $stmt_seq->execute($params_seq);
                if($stmt_seq->fetch()) { $erros_form_evento[] = "A Sequência " . htmlspecialchars($sequencia_post_validada) . " já existe para este Diário de Bordo."; }
            }
        }
    }

    // VALIDAÇÃO Tabela (Evento)
    if (!empty($numero_tabela_evento_post)) {
        if (!ctype_digit($numero_tabela_evento_post) || strlen($numero_tabela_evento_post) > 2) { $erros_form_evento[] = "Tabela (Evento), se preenchida, deve ter até 2 dígitos numéricos.";}
    }

    // VALIDAÇÃO WorkID (Evento)
    if (!empty($workid_eventos_post)) {
        if (!ctype_digit($workid_eventos_post)) { $erros_form_evento[] = "WorkID (Evento) deve conter apenas números."; }
        else {
            $len_workid_evento = strlen($workid_eventos_post);
            $primeiro_digito_workid_evento = substr($workid_eventos_post, 0, 1);
            if ($dia_tipo_do_bloco_atual == 'Uteis') {
                if ($len_workid_evento != 7) { $erros_form_evento[] = "WorkID (Evento) para Dias Úteis deve ter 7 dígitos."; }
            } elseif ($dia_tipo_do_bloco_atual == 'Sabado') {
                if (!($len_workid_evento == 8 && $primeiro_digito_workid_evento === '2')) { $erros_form_evento[] = "WorkID (Evento) para Sábado deve ter 8 dígitos e começar com '2'."; }
            } elseif ($dia_tipo_do_bloco_atual == 'DomingoFeriado') {
                if (!($len_workid_evento == 8 && $primeiro_digito_workid_evento === '3')) { $erros_form_evento[] = "WorkID (Evento) para Domingo/Feriado deve ter 8 dígitos e começar com '3'."; }
            } else { $erros_form_evento[] = "Tipo de dia do Bloco inválido para validar WorkID do Evento."; }
        }
    }

    if (empty($local_id_post)) { $erros_form_evento[] = "Local é obrigatório."; }
    
    $horario_chegada_db = null;
    if (!empty($horario_chegada_post_str)) {
        if (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $horario_chegada_post_str)) { $horario_chegada_db = $horario_chegada_post_str . ':00'; }
        else { $erros_form_evento[] = "Formato da Hora de Chegada inválido (HH:MM)."; }
    }
    $horario_saida_db = null;
    if (!empty($horario_saida_post_str)) {
        if (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $horario_saida_post_str)) { $horario_saida_db = $horario_saida_post_str . ':00'; }
        else { $erros_form_evento[] = "Formato da Hora de Saída inválido (HH:MM)."; }
    }
    if (strlen($info_post) > 255) { $erros_form_evento[] = "Informações Adicionais excedem 255 caracteres.";}

    if (empty($erros_form_evento)) {
        if ($pdo) {
            try {
                $pdo->beginTransaction();
                if ($evento_id_post) { // Editar
                    $sql_ev = "UPDATE diario_bordo_eventos SET sequencia = :seq, linha_atual_id = :lid, numero_tabela_evento = :nte, workid_eventos = :we, local_id = :locid, horario_chegada = :hc, horario_saida = :hs, info = :info 
                               WHERE id = :eid AND programacao_id = :pid_ev_crud";
                    $stmt_ev = $pdo->prepare($sql_ev);
                    $stmt_ev->bindParam(':eid', $evento_id_post, PDO::PARAM_INT);
                } else { // Adicionar
                    if ($sequencia_post_validada === null) { 
                        $stmt_max_seq = $pdo->prepare("SELECT MAX(sequencia) FROM diario_bordo_eventos WHERE programacao_id = :pid_max_seq_add");
                        $stmt_max_seq->bindParam(':pid_max_seq_add', $programacao_id, PDO::PARAM_INT);
                        $stmt_max_seq->execute();
                        $max_seq = $stmt_max_seq->fetchColumn();
                        $sequencia_post_validada = ($max_seq === null) ? 0 : $max_seq + 1;
                    }
                    $sql_ev = "INSERT INTO diario_bordo_eventos (programacao_id, sequencia, linha_atual_id, numero_tabela_evento, workid_eventos, local_id, horario_chegada, horario_saida, info) 
                               VALUES (:pid_ev_crud, :seq, :lid, :nte, :we, :locid, :hc, :hs, :info)";
                    $stmt_ev = $pdo->prepare($sql_ev);
                }
                $stmt_ev->bindParam(':pid_ev_crud', $programacao_id, PDO::PARAM_INT);
                $stmt_ev->bindParam(':seq', $sequencia_post_validada, PDO::PARAM_INT);
                $stmt_ev->bindParam(':lid', $linha_atual_id_post, $linha_atual_id_post ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $stmt_ev->bindParam(':nte', $numero_tabela_evento_post, !empty($numero_tabela_evento_post) ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmt_ev->bindParam(':we', $workid_eventos_post, !empty($workid_eventos_post) ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmt_ev->bindParam(':locid', $local_id_post, PDO::PARAM_INT);
                $stmt_ev->bindParam(':hc', $horario_chegada_db, $horario_chegada_db ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmt_ev->bindParam(':hs', $horario_saida_db, $horario_saida_db ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmt_ev->bindParam(':info', $info_post, !empty($info_post) ? PDO::PARAM_STR : PDO::PARAM_NULL);

                if ($stmt_ev->execute()) {
                    $pdo->commit();
                    $_SESSION['admin_success_message'] = "Evento salvo com sucesso!";
                    header("Location: " . $url_base_acao_eventos_com_filtros); exit;
                } else { 
                    $pdo->rollBack(); $errorInfo = $stmt_ev->errorInfo();
                    $_SESSION['admin_error_message'] = "Erro ao salvar evento. SQLSTATE[{$errorInfo[0]}] [{$errorInfo[1]}] {$errorInfo[2]}";
                }
            } catch (PDOException $e_ev_op) { 
                if($pdo->inTransaction()) $pdo->rollBack();
                $_SESSION['admin_error_message'] = "Erro DB ao salvar evento: " . $e_ev_op->getMessage(); 
                error_log("Erro PDO ao salvar evento: " . $e_ev_op->getMessage());
            }
        }
    } else { 
        $_SESSION['admin_form_error_evento'] = implode("<br>", $erros_form_evento);
        $evento_id_edicao = $evento_id_post; $sequencia_form = $sequencia_post_input;
        $linha_atual_id_form = $linha_atual_id_post; $numero_tabela_evento_form = $numero_tabela_evento_post; 
        $workid_eventos_form = $workid_eventos_post; $local_id_form = $local_id_post; 
        $horario_chegada_form = $horario_chegada_post_str; $horario_saida_form = $horario_saida_post_str; 
        $info_form = $info_post; $modo_edicao_evento = (bool)$evento_id_post;
    }
}

// --- Lógica para preencher formulário para EDIÇÃO (via GET) ---
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['acao_evento_edit']) && $_GET['acao_evento_edit'] == 'editar' && isset($_GET['evento_id_edit']) && filter_var($_GET['evento_id_edit'], FILTER_VALIDATE_INT) && $pode_gerenciar_eventos) {
    $evento_id_para_preencher_form = (int)$_GET['evento_id_edit'];
    if ($pdo) {
        try {
            $stmt_ev_ed_get = $pdo->prepare("SELECT * FROM diario_bordo_eventos WHERE id = :eid_get AND programacao_id = :pid_ev_ed_get");
            $stmt_ev_ed_get->bindParam(':eid_get', $evento_id_para_preencher_form, PDO::PARAM_INT);
            $stmt_ev_ed_get->bindParam(':pid_ev_ed_get', $programacao_id, PDO::PARAM_INT);
            $stmt_ev_ed_get->execute();
            $evento_data_db_get = $stmt_ev_ed_get->fetch(PDO::FETCH_ASSOC);
            if ($evento_data_db_get) {
                $modo_edicao_evento = true; $evento_id_edicao = $evento_id_para_preencher_form;
                $sequencia_form = $evento_data_db_get['sequencia'];
                $linha_atual_id_form = $evento_data_db_get['linha_atual_id'];
                $numero_tabela_evento_form = $evento_data_db_get['numero_tabela_evento'];
                $workid_eventos_form = $evento_data_db_get['workid_eventos'];
                $local_id_form = $evento_data_db_get['local_id'];
                $horario_chegada_form = $evento_data_db_get['horario_chegada'] ? date('H:i', strtotime($evento_data_db_get['horario_chegada'])) : '';
                $horario_saida_form = $evento_data_db_get['horario_saida'] ? date('H:i', strtotime($evento_data_db_get['horario_saida'])) : '';
                $info_form = $evento_data_db_get['info'];
            } else { $_SESSION['admin_warning_message'] = "Evento ID {$evento_id_para_preencher_form} não encontrado para edição neste bloco."; $modo_edicao_evento = false; $evento_id_edicao = null;}
        } catch (PDOException $e_get_edit) { $_SESSION['admin_error_message'] = "Erro ao carregar evento para edição: " . $e_get_edit->getMessage(); }
    }
}

// --- Buscar Eventos Existentes para o Bloco Atual ---
$eventos_do_bloco = [];
if ($pdo) {
    try {
        $stmt_eventos = $pdo->prepare(
            "SELECT dbe.*, l.numero as numero_linha_evento, l.nome as nome_linha_evento_desc, loc.nome as nome_local_evento 
             FROM diario_bordo_eventos dbe
             LEFT JOIN linhas l ON dbe.linha_atual_id = l.id
             LEFT JOIN locais loc ON dbe.local_id = loc.id
             WHERE dbe.programacao_id = :pid_eventos 
             ORDER BY dbe.sequencia ASC, dbe.id ASC"
        );
        $stmt_eventos->bindParam(':pid_eventos', $programacao_id, PDO::PARAM_INT);
        $stmt_eventos->execute();
        $eventos_do_bloco = $stmt_eventos->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e_get_ev) {
        error_log("Erro ao carregar eventos do diário de bordo para prog ID {$programacao_id}: " . $e_get_ev->getMessage());
        $erro_listagem_eventos = "Erro ao carregar lista de eventos: " . $e_get_ev->getMessage();
    }
}

require_once 'admin_header.php'; 

$js_tipo_dia_do_bloco_eventos = $dia_tipo_do_bloco_atual; 
$js_info_evento_atual_para_selecionar = $info_form; 
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <a href="<?php echo htmlspecialchars($link_voltar_pesquisa_eventos); ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-search"></i> Voltar para Pesquisa de Blocos
    </a>
</div>

<?php
if (isset($_SESSION['admin_success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_success_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_success_message']); }
if (isset($_SESSION['admin_error_message'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_error_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_error_message']); }
if (isset($_SESSION['admin_warning_message'])) { echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_warning_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_warning_message']); }
if (isset($_SESSION['admin_form_error_evento'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . nl2br($_SESSION['admin_form_error_evento']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_form_error_evento']); }
if (isset($erro_listagem_eventos)) { echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($erro_listagem_eventos) . '</div>';}
?>

<?php if ($pode_gerenciar_eventos): ?>
<div class="card mt-4 mb-4 shadow-sm">
    <div class="card-header">
        <h5 class="mb-0"><?php echo $modo_edicao_evento ? 'Editando Evento (Sequência: ' . htmlspecialchars($sequencia_form) . ')' : 'Adicionar Novo Evento'; ?>
            <?php if ($modo_edicao_evento): ?>
                <a href="<?php echo htmlspecialchars($url_base_acao_eventos_com_filtros); ?>" class="btn btn-sm btn-outline-info float-right" title="Limpar formulário para adicionar um novo evento"><i class="fas fa-plus"></i> Limpar para Adicionar</a>
            <?php endif; ?>
        </h5>
    </div>
    <div class="card-body">
        <form action="<?php echo htmlspecialchars($url_base_acao_eventos_com_filtros); ?>" method="POST" id="form-evento-diario">
            <?php if ($modo_edicao_evento && $evento_id_edicao): ?>
                <input type="hidden" name="evento_id_hidden" value="<?php echo $evento_id_edicao; ?>">
            <?php endif; ?>
            <input type="hidden" name="programacao_id_form_val" value="<?php echo $programacao_id; ?>">

            <div class="form-row">
                <div class="form-group col-md-1">
                    <label for="sequencia">Seq. <span class="text-danger">*</span></label>
                    <input type="number" class="form-control form-control-sm" id="sequencia" name="sequencia" value="<?php echo htmlspecialchars($sequencia_form); ?>" min="0" required placeholder="0" inputmode="numeric">
                </div>
                <div class="form-group col-md-3">
                    <label for="linha_atual_id">Linha</label>
                    <select class="form-control form-control-sm select2-eventos" id="linha_atual_id" name="linha_atual_id" data-placeholder="Selecione Linha...">
                        <option value="">Nenhuma / Garagem</option>
                        <?php foreach($lista_linhas_eventos_select as $linha_ev): ?>
                            <option value="<?php echo $linha_ev['id']; ?>" <?php if($linha_atual_id_form == $linha_ev['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($linha_ev['numero'] . ($linha_ev['nome'] ? ' - ' . $linha_ev['nome'] : '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label for="numero_tabela_evento">Tabela (Ev.)</label>
                    <input type="text" class="form-control form-control-sm" id="numero_tabela_evento" name="numero_tabela_evento" value="<?php echo htmlspecialchars($numero_tabela_evento_form); ?>" maxlength="2" pattern="\d{0,2}" inputmode="numeric" placeholder="Ex: 01">
                </div>
                <div class="form-group col-md-2">
                    <label for="workid_eventos">WorkID (Ev.)</label>
                    <input type="text" class="form-control form-control-sm" id="workid_eventos" name="workid_eventos" value="<?php echo htmlspecialchars($workid_eventos_form); ?>" maxlength="8" inputmode="numeric">
                    <small id="workid_eventos_help" class="form-text text-muted">Formato esperado...</small>
                </div>
                <div class="form-group col-md-4">
                    <label for="local_id">Local <span class="text-danger">*</span></label>
                    <select class="form-control form-control-sm select2-eventos" id="local_id" name="local_id" required data-placeholder="Selecione Local...">
                        <option value="">Selecione...</option>
                        <?php foreach($lista_locais_eventos_select as $local_ev): ?>
                            <option value="<?php echo $local_ev['id']; ?>" <?php if($local_id_form == $local_ev['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($local_ev['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-2">
                    <label for="horario_chegada">Chegada</label>
                    <input type="time" class="form-control form-control-sm" id="horario_chegada" name="horario_chegada" value="<?php echo htmlspecialchars($horario_chegada_form); ?>">
                </div>
                <div class="form-group col-md-2">
                    <label for="horario_saida">Saída</label>
                    <input type="time" class="form-control form-control-sm" id="horario_saida" name="horario_saida" value="<?php echo htmlspecialchars($horario_saida_form); ?>">
                </div>
                <div class="form-group col-md-6"> 
                    <label for="info_evento_select">Informações Adicionais</label>
                    <select class="form-control form-control-sm select2-eventos-info" id="info_evento_select" name="info" data-placeholder="Selecione ou digite uma info...">
                        <option value="">Selecione...</option>
                        </select>
                </div>
                <div class="form-group col-md-2 d-flex align-items-end">
                    <button type="submit" name="salvar_evento_diario" class="btn btn-primary btn-sm btn-block">
                        <i class="fas fa-save"></i> <?php echo $modo_edicao_evento ? 'Atualizar' : 'Adicionar'; ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<h4 class="mt-4">Eventos Cadastrados no Diário de Bordo</h4>
<?php if (isset($erro_listagem_eventos)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($erro_listagem_eventos); ?></div>
<?php elseif (empty($eventos_do_bloco)): ?>
    <p class="text-info">Nenhum evento cadastrado para este Bloco ainda.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered table-sm table-hover" id="tabela-eventos-diario">
            <thead class="thead-light">
                <tr>
                    <th>Seq.</th><th>Linha</th><th>Tabela (Ev.)</th><th>WorkID (Ev.)</th><th>Local</th><th>Chegada</th><th>Saída</th><th>Info</th>
                    <?php if($pode_gerenciar_eventos): ?><th style="width: 100px;">Ações</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($eventos_do_bloco as $evento): ?>
                <tr>
                    <td><?php echo htmlspecialchars($evento['sequencia']); ?></td>
                    <td><?php echo htmlspecialchars(($evento['numero_linha_evento'] ?? '') . ($evento['nome_linha_evento_desc'] ? ' - '.htmlspecialchars($evento['nome_linha_evento_desc']) : '')); ?></td>
                    <td><?php echo htmlspecialchars($evento['numero_tabela_evento'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars($evento['workid_eventos'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars($evento['nome_local_evento']); ?></td>
                    <td><?php echo $evento['horario_chegada'] ? date('H:i', strtotime($evento['horario_chegada'])) : '--:--'; ?></td>
                    <td><?php echo $evento['horario_saida'] ? date('H:i', strtotime($evento['horario_saida'])) : '--:--'; ?></td>
                    <td><?php echo htmlspecialchars($evento['info'] ?: '-'); ?></td>
                    <?php if($pode_gerenciar_eventos): ?>
                    <td class="action-buttons">
                        <a href="<?php echo htmlspecialchars($url_base_acao_eventos_com_filtros); ?>&amp;acao_evento_edit=editar&amp;evento_id_edit=<?php echo $evento['id']; ?>" class="btn btn-primary btn-xs" title="Editar Evento"><i class="fas fa-edit"></i></a>
                        <a href="<?php echo htmlspecialchars($url_base_acao_eventos_com_filtros); ?>&amp;acao_evento=apagar&amp;evento_id=<?php echo $evento['id']; ?>&amp;token_del_ev=<?php echo uniqid('csrf_del_ev_',true);?>" class="btn btn-danger btn-xs" title="Apagar Evento" onclick="return confirm('Tem certeza que deseja apagar o evento da sequência <?php echo htmlspecialchars($evento['sequencia']); ?>?');"><i class="fas fa-trash-alt"></i></a>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
ob_start();
?>
<script>
// Passa o tipo de dia do bloco e a info atual para o JavaScript
const TIPO_DIA_BLOCO_EVENTOS = <?php echo json_encode($dia_tipo_do_bloco_atual); ?>;
const INFO_EVENTO_ATUAL_PHP = <?php echo json_encode($js_info_evento_atual_para_selecionar ?? ''); ?>; 

$(document).ready(function() {
    // Inicializar Select2 para Linha e Local
    $('#linha_atual_id, #local_id').each(function() { 
        $(this).select2({
            theme: 'bootstrap4',
            placeholder: $(this).data('placeholder') || 'Selecione...',
            allowClear: true,
            width: '100%'
        }); 
    });

    // Inicializar Select2 para o novo campo de Info
    const $selectInfoEvento = $('#info_evento_select').select2({
        theme: 'bootstrap4',
        placeholder: 'Selecione ou digite uma info...',
        allowClear: true,
        width: '100%',
        tags: true, 
        createTag: function (params) {
            var term = $.trim(params.term);
            // if (term === '') { return null; } // Permite criar tag vazia se o usuário limpar
            return { id: term, text: term, newTag: true };
        }
    });

    const $selectLinhaEvento = $('#linha_atual_id');
    const $workIdEventoInput = $('#workid_eventos');
    const $workIdHelpText = $('#workid_eventos_help');
    const $numeroTabelaEventoInput = $('#numero_tabela_evento');

    // Função para carregar opções de info (globais + específicas da linha)
    function popularInfoOpcoes(linhaId, valorParaTentarSelecionar) {
        $selectInfoEvento.prop('disabled', true).empty().append(new Option('Carregando...', '')); // Placeholder de carregamento

        $.ajax({
            url: 'buscar_info_opcoes_ajax.php',
            type: 'GET',
            dataType: 'json',
            data: { linha_id: linhaId }, 
            success: function(response) {
                $selectInfoEvento.empty(); 
                $selectInfoEvento.append(new Option('Selecione ou digite...', '')); 

                if (response.success) {
                    const globaisFixas = ["Escala", "Garagem", "Início da Linha", "Fim da Linha"];
                    var $optgroupGlobal = $('<optgroup label="Padrão"></optgroup>');
                    
                    globaisFixas.forEach(function(infoTexto) {
                        $optgroupGlobal.append(new Option(infoTexto, infoTexto));
                    });

                    if (response.globais && response.globais.length > 0) {
                        response.globais.forEach(function(info) {
                            if (!globaisFixas.includes(info.descricao_info)) {
                                $optgroupGlobal.append(new Option(info.descricao_info, info.descricao_info));
                            }
                        });
                    }
                    $selectInfoEvento.append($optgroupGlobal);

                    if (response.especificas && response.especificas.length > 0) {
                        var $optgroupEspecifica = $('<optgroup label="Específicas da Linha"></optgroup>');
                        response.especificas.forEach(function(info) {
                            $optgroupEspecifica.append(new Option(info.descricao_info, info.descricao_info));
                        });
                        $selectInfoEvento.append($optgroupEspecifica);
                    }
                } else { console.warn("AJAX Info Erro:", response.message); }
                
                if (valorParaTentarSelecionar) {
                    var existeOpcao = $selectInfoEvento.find("option").filter(function () { return $(this).val() === valorParaTentarSelecionar; }).length > 0;
                    if (existeOpcao) {
                        $selectInfoEvento.val(valorParaTentarSelecionar);
                    } else if ($selectInfoEvento.data('select2') && $selectInfoEvento.data('select2').options.options.tags) {
                        var newOption = new Option(valorParaTentarSelecionar, valorParaTentarSelecionar, true, true);
                        $selectInfoEvento.append(newOption).val(valorParaTentarSelecionar);
                    } else { $selectInfoEvento.val(""); }
                } else { $selectInfoEvento.val(""); }
                $selectInfoEvento.trigger('change.select2');
            },
            error: function() { 
                console.error("Falha AJAX ao buscar info_opcoes."); 
                $selectInfoEvento.empty().append(new Option('Erro ao carregar', '')).append(new Option('Selecione ou digite...', ''));
            },
            complete: function() { $selectInfoEvento.prop('disabled', false).trigger('change.select2'); }
        });
    }

    $selectLinhaEvento.on('change', function() {
        var linhaId = $(this).val();
        // Ao mudar a linha, se não estiver em modo de edição, limpa a seleção de info.
        // Se estiver editando, tenta manter a INFO_EVENTO_ATUAL_PHP se for uma global ou da nova linha.
        var infoPreencher = ($('input[name="evento_id_hidden"]').val()) ? INFO_EVENTO_ATUAL_PHP : null;
        popularInfoOpcoes(linhaId, infoPreencher); 
    });

    var linhaInicialSelecionada = $selectLinhaEvento.val();
    popularInfoOpcoes(linhaInicialSelecionada, INFO_EVENTO_ATUAL_PHP);


    // Função de validação visual para WorkID do Evento
    function atualizarAjudaEPatternWorkIdEvento() {
        let placeholder = "Ex: 1234567"; 
        let pattern = "\\d{7}"; 
        let maxLength = 7;
        let helpMsg = "Para Dias Úteis, deve ter 7 dígitos numéricos.";

        if (TIPO_DIA_BLOCO_EVENTOS === 'Sabado') {
            placeholder = "Ex: 2xxxxxxx"; pattern = "2\\d{7}"; maxLength = 8;
            helpMsg = "Para Sábado, deve ter 8 dígitos e começar com '2'.";
        } else if (TIPO_DIA_BLOCO_EVENTOS === 'DomingoFeriado') {
            placeholder = "Ex: 3xxxxxxx"; pattern = "3\\d{7}"; maxLength = 8;
            helpMsg = "Para Domingo/Feriado, deve ter 8 dígitos e começar com '3'.";
        }
        $workIdEventoInput.attr('placeholder', placeholder).attr('pattern', pattern).attr('maxlength', maxLength);
        $workIdHelpText.text(helpMsg).removeClass('text-danger text-success').addClass('text-muted');
        
        if($workIdEventoInput.val().trim() !== '') { // Revalida visualmente se já houver valor (modo edição)
            validarWorkIdEventoClienteVisual();
        }
    }
    
    // Função para feedback visual do WorkID do Evento
    function validarWorkIdEventoClienteVisual() { 
        let valor = $workIdEventoInput.val().trim();
        let valido = true;
        let currentPatternString = $workIdEventoInput.attr('pattern');
        
        if (valor === "") { 
             $workIdHelpText.removeClass('text-danger text-success').addClass('text-muted');
             // Restaura o texto de ajuda padrão com base no TIPO_DIA_BLOCO_EVENTOS
             atualizarAjudaEPatternWorkIdEvento(); 
             return;
        }
        if (!/^\d+$/.test(valor)) { valido = false; } 
        else {
            if (currentPatternString && !(new RegExp("^" + currentPatternString + "$").test(valor))) { valido = false; }
        }
        if (!valido) { $workIdHelpText.text("Formato inválido! " + $workIdHelpText.text()).removeClass('text-muted text-success').addClass('text-danger');} 
        else { $workIdHelpText.removeClass('text-muted text-danger').addClass('text-success').text('Formato válido!'); setTimeout(function(){ if (!$workIdHelpText.hasClass('text-danger')) { atualizarAjudaEPatternWorkIdEvento(); }}, 2500); }
    }
    
    atualizarAjudaEPatternWorkIdEvento(); 

    $workIdEventoInput.on('input', function() { this.value = this.value.replace(/[^0-9]/g, ''); validarWorkIdEventoClienteVisual(); });
    $workIdEventoInput.on('blur', validarWorkIdEventoClienteVisual);

    $numeroTabelaEventoInput.on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 2) { this.value = this.value.substring(0,2); }
    });

    // Validação no Submit do Formulário
    $('#form-evento-diario').on('submit', function(e) {
        var seq = $('#sequencia').val().trim();
        var local = $('#local_id').val();
        var tabelaEvento = $numeroTabelaEventoInput.val().trim();
        var workIdEvento = $workIdEventoInput.val().trim();
        var errosCliente = [];

        if (seq === '' || !/^\d+$/.test(seq) || parseInt(seq) < 0) { errosCliente.push('Sequência: obrigatória e deve ser um número não negativo.'); }
        if (local === '' || local === null) { errosCliente.push('Local: obrigatório.'); }
        if (tabelaEvento !== "" && (!/^\d{1,2}$/.test(tabelaEvento))) { errosCliente.push('Tabela (Evento): se preenchida, deve ter 1 ou 2 dígitos numéricos.');}
        
        if (workIdEvento !== "") { 
            let workIdCorreto = false;
            let currentPatternSubmit = $workIdEventoInput.attr('pattern'); 
            let msgErroWorkIdCliente = "Verifique o formato esperado: " + $workIdHelpText.text();
            
            if (!/^\d+$/.test(workIdEvento)) { msgErroWorkIdCliente = "WorkID (Evento) deve conter apenas números. " + msgErroWorkIdCliente; } 
            else { 
                if (currentPatternSubmit && new RegExp("^" + currentPatternSubmit + "$").test(workIdEvento)) { workIdCorreto = true; }
            }
            if (!workIdCorreto) { errosCliente.push('WorkID (Evento) inválido. ' + msgErroWorkIdCliente); }
        }
        
        if (errosCliente.length > 0) {
            alert("Por favor, corrija os seguintes erros:\n- " + errosCliente.join("\n- "));
            e.preventDefault(); 
            return false;
        }
    });
});
</script>
<?php
$page_specific_js = ob_get_clean();
require_once 'admin_footer.php';
?>