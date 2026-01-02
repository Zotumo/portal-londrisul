<?php
// admin/rotas_linha_gerenciar.php
// Gerencia Adição, Edição e Exclusão de variações de rota para uma linha específica.

require_once 'auth_check.php';

$niveis_permitidos_gerenciar_rotas = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_gerenciar_rotas)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para gerenciar rotas de linhas.";
    header('Location: linhas_listar.php');
    exit;
}

require_once '../db_config.php';

// --- Obter e Validar Parâmetros Principais ---
$linha_id_param = isset($_REQUEST['linha_id']) ? filter_var($_REQUEST['linha_id'], FILTER_VALIDATE_INT) : null;
// Usamos $_REQUEST para pegar nome_linha pois pode vir de GET (link inicial) ou POST (do form, se precisar preservar)
$nome_linha_param_raw = isset($_REQUEST['nome_linha']) ? urldecode($_REQUEST['nome_linha']) : null;

if (!$linha_id_param) {
    $_SESSION['admin_error_message'] = "ID da linha não fornecido para gerenciar rotas.";
    header('Location: linhas_listar.php');
    exit;
}

// Buscar informações da linha principal para exibir e consistência
$linha_principal_info = null;
$display_nome_linha = 'Linha Desconhecida';
if ($pdo) {
    try {
        $stmt_l = $pdo->prepare("SELECT numero, nome FROM linhas WHERE id = :lid");
        $stmt_l->bindParam(':lid', $linha_id_param, PDO::PARAM_INT);
        $stmt_l->execute();
        $linha_principal_info = $stmt_l->fetch(PDO::FETCH_ASSOC);
        if ($linha_principal_info) {
            $display_nome_linha = htmlspecialchars($linha_principal_info['numero'] . ($linha_principal_info['nome'] ? ' - ' . $linha_principal_info['nome'] : ''));
        } else {
            $_SESSION['admin_error_message'] = "Linha ID {$linha_id_param} não encontrada.";
            header('Location: linhas_listar.php');
            exit;
        }
    } catch (PDOException $e) { /* ... (tratamento de erro) ... */ }
}
// Fim Buscar informações da linha principal


// --- Inicialização de Variáveis do Formulário de Rota ---
$rota_id_edicao = null;
$variacao_nome_form = '';
$mapa_iframe_ida_form = '';
$mapa_iframe_volta_form = '';
$modo_edicao_rota = false;
$acao_form = 'adicionar'; // 'adicionar' ou 'editar'

// --- Parâmetros para manter contexto da lista de linhas principal ---
$params_retorno_lista_geral_array = [];
$get_params_to_preserve = ['pagina', 'busca_numero', 'busca_nome', 'status_filtro'];
foreach($get_params_to_preserve as $param_key) {
    if (isset($_REQUEST[$param_key])) { // Usa REQUEST para pegar de GET ou POST (se foram submetidos no form como hidden)
        $params_retorno_lista_geral_array[$param_key] = $_REQUEST[$param_key];
    }
}
$query_string_retorno_geral = http_build_query($params_retorno_lista_geral_array);
$link_voltar_linhas_geral_page = 'linhas_listar.php' . ($query_string_retorno_geral ? '?' . $query_string_retorno_geral : '');
// URL base para redirecionamentos DENTRO desta página de rotas (mantém linha_id e nome_linha)
$base_url_rotas_gerenciar = "rotas_linha_gerenciar.php?linha_id=" . $linha_id_param . "&nome_linha=" . urlencode($nome_linha_param_raw) . ($query_string_retorno_geral ? '&' . $query_string_retorno_geral : '');


// --- LÓGICA DE PROCESSAMENTO: APAGAR ROTA ---
if (isset($_GET['acao_rota']) && $_GET['acao_rota'] == 'apagar' && isset($_GET['rota_id']) && filter_var($_GET['rota_id'], FILTER_VALIDATE_INT)) {
    // Adicionar verificação de token CSRF aqui seria ideal
    $rota_id_apagar = (int)$_GET['rota_id'];
    if ($pdo) {
        try {
            $stmt_del_rota = $pdo->prepare("DELETE FROM rotas_linha WHERE id = :rid_del AND linha_id = :lid_del");
            $stmt_del_rota->bindParam(':rid_del', $rota_id_apagar, PDO::PARAM_INT);
            $stmt_del_rota->bindParam(':lid_del', $linha_id_param, PDO::PARAM_INT); // Garante que apague apenas da linha correta
            if ($stmt_del_rota->execute() && $stmt_del_rota->rowCount() > 0) {
                $_SESSION['admin_success_message'] = "Variação de rota apagada com sucesso.";
            } else {
                $_SESSION['admin_warning_message'] = "Variação de rota não encontrada para exclusão ou não pertence a esta linha.";
            }
        } catch (PDOException $e_del_rota) {
            $_SESSION['admin_error_message'] = "Erro ao apagar variação de rota: " . $e_del_rota->getMessage();
        }
        header("Location: " . $base_url_rotas_gerenciar); // Redireciona para limpar GET params da ação
        exit;
    }
}

// --- LÓGICA DE PROCESSAMENTO: SALVAR/EDITAR ROTA (VIA POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_rota_linha'])) {
    $rota_id_post = filter_input(INPUT_POST, 'rota_id', FILTER_VALIDATE_INT); // Se for edição
    $variacao_nome_post = trim($_POST['variacao_nome'] ?? '');
    $mapa_iframe_ida_post = trim($_POST['mapa_iframe_ida'] ?? '');
    $mapa_iframe_volta_post = trim($_POST['mapa_iframe_volta'] ?? '');
    $erros_form_rota = [];

    if (empty($variacao_nome_post)) { $erros_form_rota[] = "O Nome da Variação é obrigatório."; }
    // ... (outras validações como antes) ...
    if (!empty($mapa_iframe_ida_post) && (stripos($mapa_iframe_ida_post, '<iframe') === false || stripos($mapa_iframe_ida_post, 'src=') === false)) { $erros_form_rota[] = "Mapa de Ida: código <iframe> inválido."; }
    if (!empty($mapa_iframe_volta_post) && (stripos($mapa_iframe_volta_post, '<iframe') === false || stripos($mapa_iframe_volta_post, 'src=') === false)) { $erros_form_rota[] = "Mapa de Volta: código <iframe> inválido."; }
    
    if ($pdo && !empty($variacao_nome_post) && empty($erros_form_rota)) { // Só verifica duplicidade se não houver outros erros
        try {
            $sql_check_var = "SELECT id FROM rotas_linha WHERE linha_id = :lid_cv AND variacao_nome = :vn_cv";
            $params_check_var = [':lid_cv' => $linha_id_param, ':vn_cv' => $variacao_nome_post];
            if ($rota_id_post) { $sql_check_var .= " AND id != :rid_cv"; $params_check_var[':rid_cv'] = $rota_id_post; }
            $stmt_check_var = $pdo->prepare($sql_check_var); $stmt_check_var->execute($params_check_var);
            if ($stmt_check_var->fetch()) { $erros_form_rota[] = "Já existe uma variação com o nome '" . htmlspecialchars($variacao_nome_post) . "' para esta linha."; }
        } catch (PDOException $e_cv) { $erros_form_rota[] = "Erro ao verificar duplicidade de variação.";}
    }

    if (empty($erros_form_rota)) {
        if ($pdo) {
            try {
                if ($rota_id_post) { // Edição
                    $sql_op_rota = "UPDATE rotas_linha SET variacao_nome = :vn, mapa_iframe_ida = :mida, mapa_iframe_volta = :mvolta 
                                    WHERE id = :rid_op AND linha_id = :lid_op";
                    $stmt_op_rota = $pdo->prepare($sql_op_rota);
                    $stmt_op_rota->bindParam(':rid_op', $rota_id_post, PDO::PARAM_INT);
                } else { // Cadastro
                    $sql_op_rota = "INSERT INTO rotas_linha (linha_id, variacao_nome, mapa_iframe_ida, mapa_iframe_volta) 
                                    VALUES (:lid_op, :vn, :mida, :mvolta)";
                    $stmt_op_rota = $pdo->prepare($sql_op_rota);
                }
                $stmt_op_rota->bindParam(':lid_op', $linha_id_param, PDO::PARAM_INT);
                $stmt_op_rota->bindParam(':vn', $variacao_nome_post, PDO::PARAM_STR);
                $stmt_op_rota->bindParam(':mida', $mapa_iframe_ida_post, !empty($mapa_iframe_ida_post) ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $stmt_op_rota->bindParam(':mvolta', $mapa_iframe_volta_post, !empty($mapa_iframe_volta_post) ? PDO::PARAM_STR : PDO::PARAM_NULL);

                if ($stmt_op_rota->execute()) {
                    $_SESSION['admin_success_message'] = "Variação de rota '" . htmlspecialchars($variacao_nome_post) . "' salva com sucesso!";
                    header("Location: " . $base_url_rotas_gerenciar); // Redireciona para limpar POST e recarregar lista
                    exit;
                } else { $_SESSION['admin_error_message'] = "Erro ao salvar variação de rota."; }
            } catch (PDOException $e_op_r) { $_SESSION['admin_error_message'] = "Erro DB: " . $e_op_r->getMessage(); }
        }
    } else {
        $_SESSION['admin_form_error_rota'] = implode("<br>", $erros_form_rota);
        // Repopular para o formulário
        $rota_id_edicao = $rota_id_post; 
        $variacao_nome_form = $variacao_nome_post;
        $mapa_iframe_ida_form = $mapa_iframe_ida_post;
        $mapa_iframe_volta_form = $mapa_iframe_volta_post;
        $modo_edicao_rota = (bool)$rota_id_post;
        $acao_form = $modo_edicao_rota ? 'editar' : 'adicionar';
    }
}

// --- LÓGICA PARA PREENCHER FORMULÁRIO PARA EDIÇÃO (VIA GET) ---
if (isset($_GET['acao_rota']) && $_GET['acao_rota'] == 'editar' && isset($_GET['rota_id']) && filter_var($_GET['rota_id'], FILTER_VALIDATE_INT)) {
    $rota_id_edicao_get = (int)$_GET['rota_id'];
    if ($pdo) {
        try {
            $stmt_rota_ed_get = $pdo->prepare("SELECT * FROM rotas_linha WHERE id = :rid_get AND linha_id = :lid_get_ed");
            $stmt_rota_ed_get->bindParam(':rid_get', $rota_id_edicao_get, PDO::PARAM_INT);
            $stmt_rota_ed_get->bindParam(':lid_get_ed', $linha_id_param, PDO::PARAM_INT);
            $stmt_rota_ed_get->execute();
            $rota_db_data_get = $stmt_rota_ed_get->fetch(PDO::FETCH_ASSOC);
            if ($rota_db_data_get) {
                $modo_edicao_rota = true;
                $acao_form = 'editar';
                $rota_id_edicao = $rota_id_edicao_get; // Define o ID para o formulário
                $variacao_nome_form = $rota_db_data_get['variacao_nome'];
                $mapa_iframe_ida_form = $rota_db_data_get['mapa_iframe_ida'];
                $mapa_iframe_volta_form = $rota_db_data_get['mapa_iframe_volta'];
            } else { $_SESSION['admin_warning_message'] = "Variação de rota não encontrada para edição.";}
        } catch (PDOException $e_rota_ed_get) { $_SESSION['admin_error_message'] = "Erro ao carregar dados da rota para edição."; }
    }
}


// --- Buscar Variações de Rota Existentes para Listagem (sempre executa) ---
$variacoes_rota_existentes = [];
if ($pdo) {
    try {
        $stmt_get_rotas = $pdo->prepare("SELECT * FROM rotas_linha WHERE linha_id = :lid_get_list ORDER BY variacao_nome ASC");
        $stmt_get_rotas->bindParam(':lid_get_list', $linha_id_param, PDO::PARAM_INT);
        $stmt_get_rotas->execute();
        $variacoes_rota_existentes = $stmt_get_rotas->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e_get_rotas_list) {
        // Não define $_SESSION['admin_error_message'] aqui para não sobrescrever erros do formulário.
        // Apenas loga ou trata de outra forma se a listagem falhar.
        error_log("Erro ao buscar variações de rota para listagem: " . $e_get_rotas_list->getMessage());
    }
}

$page_title = 'Gerenciar Rotas da Linha: ' . $display_nome_linha;
require_once 'admin_header.php'; // Atualiza o título com o nome correto da linha
// admin_header.php já foi incluído, então não precisa de novo.
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo $page_title; ?></h1>
    <a href="<?php echo $link_voltar_linhas_geral_page; ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Voltar para Lista de Linhas
    </a>
</div>

<?php
if (isset($_SESSION['admin_success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_success_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_success_message']); }
if (isset($_SESSION['admin_error_message'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_error_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_error_message']); }
if (isset($_SESSION['admin_warning_message'])) { echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_warning_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_warning_message']); }
if (isset($_SESSION['admin_form_error_rota'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . nl2br($_SESSION['admin_form_error_rota']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_form_error_rota']); }
?>

<div class="card mt-4">
    <div class="card-header">
        <?php echo ($modo_edicao_rota && $rota_id_edicao) ? 'Editando Variação: ' . htmlspecialchars($variacao_nome_form) : 'Adicionar Nova Variação de Rota'; ?>
    </div>
    <div class="card-body">
        <form action="<?php echo $base_url_rotas_gerenciar; ?>" method="POST" id="form-rota-linha">
            <?php if ($modo_edicao_rota && $rota_id_edicao): ?>
                <input type="hidden" name="rota_id" value="<?php echo $rota_id_edicao; ?>">
            <?php endif; ?>
            <input type="hidden" name="linha_id" value="<?php echo $linha_id_param; ?>">
            <input type="hidden" name="nome_linha" value="<?php echo htmlspecialchars($nome_linha_param_raw); ?>">
            <?php // Passar parâmetros de filtro da lista de linhas para manter contexto no redirect
                foreach($params_retorno_lista_geral_array as $key => $value) {
                    echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($value).'">';
                }
            ?>


            <div class="form-group">
                <label for="variacao_nome">Nome da Variação (Mesmo que o Diário de Bordo) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="variacao_nome" name="variacao_nome" value="<?php echo htmlspecialchars($variacao_nome_form); ?>" required maxlength="100" placeholder="Ex: Via Madre">
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="mapa_iframe_ida">Código <code>&lt;iframe&gt;</code> do Mapa de IDA (Opcional)</label>
                    <textarea class="form-control" id="mapa_iframe_ida" name="mapa_iframe_ida" rows="5" placeholder="Cole o código <iframe> do Google Maps aqui..."><?php echo htmlspecialchars($mapa_iframe_ida_form); ?></textarea>
                    <small class="form-text text-muted">Obtenha em "Incorporar um mapa" no Google Maps.</small>
                </div>
                <div class="form-group col-md-6">
                    <label for="mapa_iframe_volta">Código <code>&lt;iframe&gt;</code> do Mapa de VOLTA (Opcional)</label>
                    <textarea class="form-control" id="mapa_iframe_volta" name="mapa_iframe_volta" rows="5" placeholder="Cole o código <iframe> do Google Maps aqui..."><?php echo htmlspecialchars($mapa_iframe_volta_form); ?></textarea>
                </div>
            </div>

            <button type="submit" name="salvar_rota_linha" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Variação</button>
            <?php if ($modo_edicao_rota): ?>
                <a href="<?php echo $base_url_rotas_gerenciar; ?>" class="btn btn-secondary">Cancelar Edição / Adicionar Nova</a>
            <?php endif; ?>
        </form>
    </div>
</div>


<h3 class="mt-5">Variações de Rota Cadastradas para a Linha: <?php echo $display_nome_linha; ?></h3>
<?php if (empty($variacoes_rota_existentes)): ?>
    <p class="text-info">Nenhuma variação de rota cadastrada para esta linha ainda.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead class="thead-light">
                <tr>
                    <th>Nome da Variação</th>
                    <th>Mapa Ida (Preview)</th>
                    <th>Mapa Volta (Preview)</th>
                    <th style="width: 120px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($variacoes_rota_existentes as $rota_item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($rota_item['variacao_nome']); ?></td>
                    <td>
                        <?php if (!empty($rota_item['mapa_iframe_ida'])): ?>
                            <button type="button" class="btn btn-outline-info btn-sm" data-toggle="modal" data-target="#mapPreviewModal" data-map-title="Rota Ida: <?php echo htmlspecialchars($rota_item['variacao_nome']); ?>" data-map-iframe="<?php echo htmlspecialchars($rota_item['mapa_iframe_ida']); ?>">
                                <i class="fas fa-map-marked-alt"></i> Ver Ida
                            </button>
                        <?php else: echo '-'; endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($rota_item['mapa_iframe_volta'])): ?>
                             <button type="button" class="btn btn-outline-info btn-sm" data-toggle="modal" data-target="#mapPreviewModal" data-map-title="Rota Volta: <?php echo htmlspecialchars($rota_item['variacao_nome']); ?>" data-map-iframe="<?php echo htmlspecialchars($rota_item['mapa_iframe_volta']); ?>">
                                <i class="fas fa-map-marked-alt"></i> Ver Volta
                            </button>
                        <?php else: echo '-'; endif; ?>
                    </td>
                    <td class="action-buttons">
                        <a href="<?php echo $base_url_rotas_gerenciar; ?>&acao_rota=editar&rota_id=<?php echo $rota_item['id']; ?>" class="btn btn-primary btn-sm" title="Editar Rota"><i class="fas fa-edit"></i></a>
                        <a href="<?php echo $base_url_rotas_gerenciar; ?>&acao_rota=apagar&rota_id=<?php echo $rota_item['id']; ?>" class="btn btn-danger btn-sm" title="Apagar Rota" onclick="return confirm('Tem certeza que deseja apagar a variação de rota: <?php echo htmlspecialchars(addslashes($rota_item['variacao_nome'])); ?>?');"><i class="fas fa-trash-alt"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<div class="modal fade" id="mapPreviewModal" tabindex="-1" role="dialog" aria-labelledby="mapPreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="mapPreviewModalLabel">Preview do Mapa</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="embed-responsive embed-responsive-16by9" id="mapPreviewIframeContainer">
          </div>
      </div>
    </div>
  </div>
</div>

<?php
// JavaScript (como antes)
ob_start();
?>
<script>
$(document).ready(function() {
    $('#mapPreviewModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); 
        var mapTitle = button.data('map-title'); 
        var mapIframeCode = button.data('map-iframe'); 
        var modal = $(this);
        modal.find('.modal-title').text(mapTitle);
        var iframeContainer = modal.find('#mapPreviewIframeContainer');
        iframeContainer.html(''); 
        if (mapIframeCode) {
            var $iframe = $(mapIframeCode);
            $iframe.attr('width', '100%').attr('height', '100%').addClass('embed-responsive-item');
            iframeContainer.append($iframe);
        } else {
            iframeContainer.html('<p class="text-muted">Conteúdo do mapa não disponível.</p>');
        }
    });
    $('#mapPreviewModal').on('hidden.bs.modal', function (e) {
        $(this).find('#mapPreviewIframeContainer').html('');
    });

     $('#form-rota-linha').on('submit', function(e) {
        var variacaoNome = $('#variacao_nome').val().trim();
        if (variacaoNome === '') {
            alert('O Nome da Variação da Rota é obrigatório.');
            $('#variacao_nome').focus(); e.preventDefault(); return false;
        }
        // Validação básica do iframe (mantida)
        function isPotentiallyValidIframe(iframeCode) {
            if (iframeCode === '') return true;
            return iframeCode.toLowerCase().includes("<iframe") && iframeCode.toLowerCase().includes("src=");
        }
        if (!isPotentiallyValidIframe($('#mapa_iframe_ida').val().trim())) {
            alert('Mapa de Ida: código <iframe> inválido.'); $('#mapa_iframe_ida').focus(); e.preventDefault(); return false;
        }
        if (!isPotentiallyValidIframe($('#mapa_iframe_volta').val().trim())) {
            alert('Mapa de Volta: código <iframe> inválido.'); $('#mapa_iframe_volta').focus(); e.preventDefault(); return false;
        }
    });
});
</script>
<?php
$page_specific_js = ob_get_clean();
require_once 'admin_footer.php';
?>