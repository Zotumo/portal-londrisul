<?php
// admin/programacao_diaria_listar.php
// Lista os "Blocos" da programação diária (Tabelas).

require_once 'auth_check.php';

$niveis_permitidos_ver_blocos = ['CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
$niveis_permitidos_crud_blocos = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];

if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_ver_blocos)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para acessar o gerenciamento de Tabelas (Blocos).";
    header('Location: tabelas_hub.php');
    exit;
}

require_once '../db_config.php';
$page_title = 'Gerenciar Tabelas';
require_once 'admin_header.php';

$itens_por_pagina = 20;
$pagina_atual = isset($_GET['pagina']) && filter_var($_GET['pagina'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$filtro_dia_tipo = isset($_GET['busca_dia_tipo']) ? trim($_GET['busca_dia_tipo']) : '';
// ATUALIZADO: Validação para filtro_tabela_work_id ser apenas números e até 3 dígitos
$filtro_tabela_work_id_input = isset($_GET['busca_tabela_work_id']) ? trim($_GET['busca_tabela_work_id']) : '';
$filtro_tabela_work_id = '';
if (ctype_digit($filtro_tabela_work_id_input) && strlen($filtro_tabela_work_id_input) <= 3) {
    $filtro_tabela_work_id = $filtro_tabela_work_id_input;
} elseif (!empty($filtro_tabela_work_id_input)) {
    // Se o input não for válido, você pode optar por ignorá-lo ou mostrar um aviso.
    // Por enquanto, vamos apenas usar o valor se for válido.
    // $_SESSION['admin_warning_message'] = "Filtro de Tabela (WorkID) deve conter apenas números (até 3 dígitos).";
}


$tipos_dia_semana_map = [
    'Uteis' => 'Dias Úteis',
    'Sabado' => 'Sábado',
    'DomingoFeriado' => 'Domingo/Feriado'
];

$programacoes = [];
$total_itens = 0;
$total_paginas = 0;
$erro_busca_prog = false;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="tabelas_hub.php" class="btn btn-outline-secondary mr-2"><i class="fas fa-arrow-left"></i> Voltar ao Hub</a>
        <?php if (in_array($admin_nivel_acesso_logado, $niveis_permitidos_crud_blocos)): ?>
        <a href="programacao_diaria_formulario.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Adicionar Nova Tabela
        </a>
        <?php endif; ?>
    </div>
</div>

<?php
if (isset($_SESSION['admin_success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_success_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_success_message']); }
if (isset($_SESSION['admin_error_message'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_error_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_error_message']); }
if (isset($_SESSION['admin_warning_message'])) { echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_warning_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_warning_message']); }
?>

<form method="GET" action="programacao_diaria_listar.php" class="mb-4 card card-body bg-light p-3 shadow-sm">
    <div class="form-row align-items-end">
        <div class="col-md-4 form-group mb-md-0">
            <label for="busca_dia_tipo_filtro_bloco">Filtrar por Dia da Semana:</label>
            <select name="busca_dia_tipo" id="busca_dia_tipo_filtro_bloco" class="form-control form-control-sm">
                <option value="">Todos os Tipos de Dia</option>
                <?php foreach ($tipos_dia_semana_map as $key_dia => $val_dia): ?>
                    <option value="<?php echo $key_dia; ?>" <?php echo ($filtro_dia_tipo === $key_dia) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($val_dia); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 form-group mb-md-0">
            <label for="busca_tabela_work_id_bloco">Filtrar por linha na tabela:</label>
            <input type="text" name="busca_tabela_work_id" id="busca_tabela_work_id_bloco" 
                   class="form-control form-control-sm" 
                   placeholder="Ex: 213" 
                   value="<?php echo htmlspecialchars($filtro_tabela_work_id_input); // Mostra o que o usuário digitou ?>"
                   pattern="\d{1,3}" maxlength="3" inputmode="numeric"
                   oninput="this.value = this.value.replace(/[^0-9]/g, '');">
        </div>
        <div class="col-md-2 form-group mb-md-0 align-self-end">
            <button type="submit" class="btn btn-sm btn-primary btn-block"><i class="fas fa-filter"></i> Filtrar</button>
        </div>
        <?php if (!empty($filtro_dia_tipo) || !empty($filtro_tabela_work_id_input)): // Usa o input original para mostrar/ocultar Limpar ?>
        <div class="col-md-2 form-group mb-md-0 align-self-end">
            <a href="programacao_diaria_listar.php" class="btn btn-sm btn-outline-secondary btn-block"><i class="fas fa-times"></i> Limpar</a>
        </div>
        <?php endif; ?>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-sm table-hover">
        <thead class="thead-light">
            <tr>
                <th>Tabela</th>
                <th>Dia da Semana</th>
                <th>Data de Atualização</th>
                <th style="width: 240px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($pdo) {
                try {
                    $sql_where_parts_prog = [];
                    $params_sql_prog = [];

                    if (!empty($filtro_dia_tipo)) {
                        $sql_where_parts_prog[] = "p.dia_semana_tipo = :dia_tipo_f";
                        $params_sql_prog[':dia_tipo_f'] = $filtro_dia_tipo;
                    }
                    // Usa o $filtro_tabela_work_id validado para a query SQL
                    if (!empty($filtro_tabela_work_id)) {
                        // O filtro aqui pode ser LIKE para pegar "213" dentro de "213/224"
                        // ou um REGEXP se você quiser ser mais específico com o formato do WorkID.
                        // Por enquanto, se o usuário digitar "213", o LIKE '%213%' vai funcionar.
                        // Se você quer que o filtro seja EXATO no início, ou em uma parte específica,
                        // a query precisaria ser mais elaborada.
                        // Vamos manter LIKE por enquanto, assumindo que o WorkID no banco possa conter mais que 3 dígitos.
                        $sql_where_parts_prog[] = "p.work_id LIKE :work_id_f";
                        $params_sql_prog[':work_id_f'] = '%' . $filtro_tabela_work_id . '%';
                    }
                    
                    $sql_where_clause_prog = "";
                    if (!empty($sql_where_parts_prog)) {
                        $sql_where_clause_prog = " WHERE " . implode(" AND ", $sql_where_parts_prog);
                    }

                    $stmt_count_prog = $pdo->prepare("SELECT COUNT(p.id) FROM programacao_diaria p" . $sql_where_clause_prog);
                    $stmt_count_prog->execute($params_sql_prog);
                    $total_itens = (int)$stmt_count_prog->fetchColumn();
                    $total_paginas = ceil($total_itens / $itens_por_pagina);
                    if ($pagina_atual > $total_paginas && $total_paginas > 0) $pagina_atual = $total_paginas;
                    if ($pagina_atual < 1) $pagina_atual = 1;
                    $offset = ($pagina_atual - 1) * $itens_por_pagina;

                    // A coluna 'data' da tabela programacao_diaria será usada como "Data de Atualização"
                    $sql_select_prog = "SELECT p.id, p.dia_semana_tipo, p.work_id, p.data 
                                        FROM programacao_diaria p"
                                     . $sql_where_clause_prog 
                                     . " ORDER BY FIELD(p.dia_semana_tipo, 'Uteis', 'Sabado', 'DomingoFeriado'), p.work_id ASC 
                                       LIMIT :limit OFFSET :offset";
                    
                    $stmt_select_prog = $pdo->prepare($sql_select_prog);
                    foreach ($params_sql_prog as $key_p => $value_p) { $stmt_select_prog->bindValue($key_p, $value_p); }
                    $stmt_select_prog->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT);
                    $stmt_select_prog->bindValue(':offset', $offset, PDO::PARAM_INT);
                    $stmt_select_prog->execute();
                    $programacoes = $stmt_select_prog->fetchAll(PDO::FETCH_ASSOC);

                    if ($programacoes) {
                        foreach ($programacoes as $prog) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($prog['work_id']) . "</td>";
                            echo "<td>" . htmlspecialchars($tipos_dia_semana_map[$prog['dia_semana_tipo']] ?? $prog['dia_semana_tipo']) . "</td>";
                            // Exibe a data formatada. Se for '0000-00-00', você pode decidir o que mostrar.
                            // Você mencionou que já arrumou no banco, então deve sempre haver uma data válida.
                            echo "<td>" . ($prog['data'] && $prog['data'] != '0000-00-00' ? date('d/m/Y', strtotime($prog['data'])) : 'N/A') . "</td>";
                            echo "<td class='action-buttons'>";
                            
                            $query_params_acao = ['busca_dia_tipo' => $filtro_dia_tipo, 'busca_tabela_work_id' => $filtro_tabela_work_id_input]; // Usa o input original para os links

                            if (in_array($admin_nivel_acesso_logado, $niveis_permitidos_crud_blocos)) {
                                echo "<a href='eventos_diario_gerenciar.php?programacao_id=" . $prog['id'] . "&nome_bloco=" . urlencode($prog['work_id']) . "&pagina_origem=" . $pagina_atual . "&" . http_build_query($query_params_acao) . "' class='btn btn-info btn-sm' title='Gerenciar Eventos do Diário de Bordo'><i class='fas fa-tasks'></i> Eventos</a> ";
                                echo "<a href='programacao_diaria_formulario.php?id=" . $prog['id'] . "&pagina=" . $pagina_atual . "&" . http_build_query($query_params_acao) . "' class='btn btn-primary btn-sm' title='Editar Tabela (Bloco)'><i class='fas fa-edit'></i> Tabela</a> ";
                            } else { 
                                 echo "<a href='eventos_diario_gerenciar.php?programacao_id=" . $prog['id'] . "&nome_bloco=" . urlencode($prog['work_id']) . "&pagina_origem=" . $pagina_atual . "&" . http_build_query($query_params_acao) . "' class='btn btn-secondary btn-sm' title='Ver Eventos do Diário de Bordo'><i class='fas fa-eye'></i> Eventos</a> ";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else { echo "<tr><td colspan='4' class='text-center'>Nenhuma tabela (bloco) encontrada" . (!empty($filtro_dia_tipo) || !empty($filtro_tabela_work_id_input) ? " com os filtros aplicados" : "") . ".</td></tr>"; }
                } catch (PDOException $e) { echo "<tr><td colspan='4' class='text-danger text-center'>Erro ao buscar programações: " . htmlspecialchars($e->getMessage()) . "</td></tr>"; $erro_busca_prog = true; }
            } else { echo "<tr><td colspan='4' class='text-danger text-center'>Falha na conexão com o banco de dados.</td></tr>"; $erro_busca_prog = true; }
            ?>
        </tbody>
    </table>
</div>

<?php if (!$erro_busca_prog && $total_paginas > 1): ?>
    <nav aria-label="Navegação das programações">
        <ul class="pagination justify-content-center mt-4">
            <?php
            $query_params_pag_prog = [];
            if (!empty($filtro_dia_tipo)) $query_params_pag_prog['busca_dia_tipo'] = $filtro_dia_tipo;
            if (!empty($filtro_tabela_work_id_input)) $query_params_pag_prog['busca_tabela_work_id'] = $filtro_tabela_work_id_input; // Usa o input original para paginação
            $link_base_pag_prog = 'programacao_diaria_listar.php?' . http_build_query($query_params_pag_prog) . (empty($query_params_pag_prog) ? '' : '&');
            
            // ... (Lógica de paginação completa) ...
            if ($pagina_atual > 1):
                echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_prog . 'pagina=1">Primeira</a></li>';
                echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_prog . 'pagina=' . ($pagina_atual - 1) . '" aria-label="Anterior"><span aria-hidden="true">&laquo;</span></a></li>';
            else:
                echo '<li class="page-item disabled"><span class="page-link">Primeira</span></li>';
                echo '<li class="page-item disabled"><span class="page-link" aria-label="Anterior"><span aria-hidden="true">&laquo;</span></span></li>';
            endif;
            $num_links_nav = 2; $inicio_loop = max(1, $pagina_atual - $num_links_nav); $fim_loop = min($total_paginas, $pagina_atual + $num_links_nav);
            if ($inicio_loop > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            for ($i = $inicio_loop; $i <= $fim_loop; $i++): echo '<li class="page-item ' . ($i == $pagina_atual ? 'active' : '') . '"><a class="page-link" href="' . $link_base_pag_prog . 'pagina=' . $i . '">' . $i . '</a></li>'; endfor;
            if ($fim_loop < $total_paginas) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            if ($pagina_atual < $total_paginas):
                echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_prog . 'pagina=' . ($pagina_atual + 1) . '" aria-label="Próxima"><span aria-hidden="true">&raquo;</span></a></li>';
                echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_prog . 'pagina=' . $total_paginas . '">Última</a></li>';
            else:
                echo '<li class="page-item disabled"><span class="page-link" aria-label="Próxima"><span aria-hidden="true">&raquo;</span></span></li>';
                echo '<li class="page-item disabled"><span class="page-link">Última</span></li>';
            endif;
            ?>
        </ul>
    </nav>
<?php endif; ?>

<?php
require_once 'admin_footer.php';
?>