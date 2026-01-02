<?php
// admin/programacao_diaria_formulario.php
// Formulário para adicionar/editar Tabelas (Blocos de Programação Diária).

require_once 'auth_check.php';

$niveis_permitidos_crud_blocos = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_crud_blocos)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para gerenciar Tabelas (Blocos).";
    header('Location: programacao_diaria_listar.php');
    exit;
}

require_once '../db_config.php';

$programacao_id_edicao = null;
$work_id_form = '';
$dia_semana_tipo_form = 'Uteis';
$data_atualizacao_form = date('Y-m-d'); // Nome da variável alinhado com o label
$hora_inicio_bloco_form = '';
$hora_fim_bloco_form = '';
// veiculo_id_form foi removido

$modo_edicao_prog_form = false;
$page_title_action = 'Adicionar Nova Tabela'; // Título padrão

$tipos_dia_semana_map_form = [
    'Uteis' => 'Dias Úteis',
    'Sabado' => 'Sábado',
    'DomingoFeriado' => 'Domingo/Feriado'
];

$params_retorno_lista_prog_form = [];
if (isset($_GET['pagina'])) $params_retorno_lista_prog_form['pagina'] = $_GET['pagina'];
if (isset($_GET['busca_dia_tipo'])) $params_retorno_lista_prog_form['busca_dia_tipo'] = $_GET['busca_dia_tipo'];
if (isset($_GET['busca_tabela_work_id'])) $params_retorno_lista_prog_form['busca_tabela_work_id'] = $_GET['busca_tabela_work_id'];
$query_string_retorno_prog_form = http_build_query($params_retorno_lista_prog_form);
$link_voltar_lista_prog_form = 'programacao_diaria_listar.php' . ($query_string_retorno_prog_form ? '?' . $query_string_retorno_prog_form : '');


if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $programacao_id_edicao = (int)$_GET['id'];
    $modo_edicao_prog_form = true;
    $page_title_action = 'Editar Tabela'; // Título para edição

    if ($pdo) {
        try {
            // Não seleciona mais veiculo_id
            $stmt_prog = $pdo->prepare("SELECT work_id, dia_semana_tipo, data, hora_inicio_prevista, hora_fim_prevista FROM programacao_diaria WHERE id = :id");
            $stmt_prog->bindParam(':id', $programacao_id_edicao, PDO::PARAM_INT);
            $stmt_prog->execute();
            $prog_db_data = $stmt_prog->fetch(PDO::FETCH_ASSOC);

            if ($prog_db_data) {
                $work_id_form = $prog_db_data['work_id'];
                $dia_semana_tipo_form = $prog_db_data['dia_semana_tipo'];
                $data_atualizacao_form = $prog_db_data['data']; // Campo 'data' do banco é a "Data de Atualização"
                $hora_inicio_bloco_form = $prog_db_data['hora_inicio_prevista'] ? date('H:i', strtotime($prog_db_data['hora_inicio_prevista'])) : '';
                $hora_fim_bloco_form = $prog_db_data['hora_fim_prevista'] ? date('H:i', strtotime($prog_db_data['hora_fim_prevista'])) : '';
                $page_title_action .= ' - ' . htmlspecialchars($work_id_form);
            } else { /* ... erro, bloco não encontrado ... */ }
        } catch (PDOException $e) { /* ... erro PDO ... */ }
    } else { /* ... erro conexão ... */ }
}

$page_title = $page_title_action;
require_once 'admin_header.php';

$form_data_repop_prog = $_SESSION['form_data_programacao'] ?? [];
if (!empty($form_data_repop_prog)) {
    $work_id_form = $form_data_repop_prog['work_id'] ?? $work_id_form;
    $dia_semana_tipo_form = $form_data_repop_prog['dia_semana_tipo'] ?? $dia_semana_tipo_form;
    $data_atualizacao_form = $form_data_repop_prog['data_atualizacao'] ?? $data_atualizacao_form; // Nome do campo no form
    $hora_inicio_bloco_form = $form_data_repop_prog['hora_inicio_bloco'] ?? $hora_inicio_bloco_form;
    $hora_fim_bloco_form = $form_data_repop_prog['hora_fim_bloco'] ?? $hora_fim_bloco_form;
    unset($_SESSION['form_data_programacao']);
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title_action); ?></h1>
    <a href="<?php echo $link_voltar_lista_prog_form; ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Voltar para Lista de Tabelas
    </a>
</div>

<?php
if (isset($_SESSION['admin_form_error_programacao'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . nl2br(htmlspecialchars($_SESSION['admin_form_error_programacao'])) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_form_error_programacao']); }
?>

<form action="programacao_diaria_processa.php<?php echo ($query_string_retorno_prog_form ? '?' . $query_string_retorno_prog_form : ''); ?>" method="POST" id="form-programacao-diaria">
    <?php if ($modo_edicao_prog_form && $programacao_id_edicao): ?>
        <input type="hidden" name="programacao_id" value="<?php echo $programacao_id_edicao; ?>">
    <?php endif; ?>

    <div class="form-row">
        <div class="form-group col-md-4">
            <label for="work_id_prog">Nome da Tabela <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="work_id_prog" name="work_id" value="<?php echo htmlspecialchars($work_id_form); ?>" required maxlength="50" placeholder="Ex: 213/211/250">
            <small class="form-text text-muted">Identificador único da tabela para o tipo de dia.</small>
        </div>
        <div class="form-group col-md-4">
            <label for="dia_semana_tipo_prog">Tipo de Dia <span class="text-danger">*</span></label>
            <select class="form-control" id="dia_semana_tipo_prog" name="dia_semana_tipo" required>
                 <option value="">Selecione...</option> <?php foreach ($tipos_dia_semana_map_form as $key_dst => $val_dst): ?>
                    <option value="<?php echo $key_dst; ?>" <?php echo ($dia_semana_tipo_form == $key_dst) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($val_dst); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-4">
            <label for="data_atualizacao_prog">Data de Atualização <span class="text-danger">*</span></label>
            <input type="date" class="form-control" id="data_atualizacao_prog" name="data_atualizacao" value="<?php echo htmlspecialchars($data_atualizacao_form); ?>" required> <small class="form-text text-muted">Data da última atualização/criação desta tabela.</small>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group col-md-3">
            <label for="hora_inicio_bloco">Hora Início Geral da Tabela <span class="text-danger">*</span></label>
            <input type="time" class="form-control" id="hora_inicio_bloco" name="hora_inicio_bloco" value="<?php echo htmlspecialchars($hora_inicio_bloco_form); ?>" required>
        </div>
        <div class="form-group col-md-3">
            <label for="hora_fim_bloco">Hora Fim Geral da Tabela <span class="text-danger">*</span></label>
            <input type="time" class="form-control" id="hora_fim_bloco" name="hora_fim_bloco" value="<?php echo htmlspecialchars($hora_fim_bloco_form); ?>" required>
        </div>
        </div>
    
    <hr>
    <button type="submit" name="salvar_programacao_diaria" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Tabela</button>
    <a href="<?php echo $link_voltar_lista_prog_form; ?>" class="btn btn-secondary">Cancelar</a>
</form>

<?php
ob_start();
?>
<script>
$(document).ready(function() {
    // Não há mais Select2 para veículo aqui

    $('#form-programacao-diaria').on('submit', function(e){
        var workId = $('#work_id_prog').val().trim();
        var diaTipo = $('#dia_semana_tipo_prog').val();
        var dataAtualizacao = $('#data_atualizacao_prog').val(); // Pega o valor da data
        var horaInicio = $('#hora_inicio_bloco').val();
        var horaFim = $('#hora_fim_bloco').val();

        if (workId === '') {
            alert('O Nome da Tabela (WorkID do Bloco) é obrigatório.');
            $('#work_id_prog').focus();
            e.preventDefault(); return false;
        }
        if (diaTipo === '') {
            alert('O Tipo de Dia é obrigatório.');
            $('#dia_semana_tipo_prog').focus();
            e.preventDefault(); return false;
        }
        if (dataAtualizacao === '') { // Validação para Data de Atualização
            alert('A Data de Atualização é obrigatória.');
            $('#data_atualizacao_prog').focus();
            e.preventDefault(); return false;
        }
        if (horaInicio === '') {
            alert('A Hora de Início Geral da Tabela é obrigatória.');
            $('#hora_inicio_bloco').focus();
            e.preventDefault(); return false;
        }
        if (horaFim === '') {
            alert('A Hora de Fim Geral da Tabela é obrigatória.');
            $('#hora_fim_bloco').focus();
            e.preventDefault(); return false;
        }
    });
});
</script>
<?php
$page_specific_js = ob_get_clean();
require_once 'admin_footer.php';
?>