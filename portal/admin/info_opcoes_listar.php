<?php
// admin/info_opcoes_listar.php
// ATUALIZADO: Removida a coluna ID da listagem.

require_once 'auth_check.php';

// Permissões
$niveis_permitidos_ver_infos = $niveis_acesso_ver_infos_padrao ?? ['CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
$niveis_permitidos_crud_infos = $niveis_acesso_gerenciar_infos_padrao ?? ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];

if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_ver_infos)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para gerenciar opções de informação.";
    header('Location: tabelas_hub.php');
    exit;
}

require_once '../db_config.php';
$page_title = 'Gerenciar Opções de Informação (Diário de Bordo)';
require_once 'admin_header.php';

// Filtros e Paginação
$itens_por_pagina = 20;
$pagina_atual = isset($_GET['pagina']) && filter_var($_GET['pagina'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ? (int)$_GET['pagina'] : 1;
// Garantir que a página não seja menor que 1
if ($pagina_atual < 1) $pagina_atual = 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$filtro_descricao = isset($_GET['busca_descricao']) ? trim($_GET['busca_descricao']) : '';
$filtro_linha_id_get = isset($_GET['busca_linha_id']) ? $_GET['busca_linha_id'] : null; // Mantém string vazia ou ID
$filtro_status_info = isset($_GET['status_filtro']) ? trim($_GET['status_filtro']) : '';

$lista_linhas_select = [];
if ($pdo) { 
    try {
        $stmt_linhas = $pdo->query("SELECT id, numero, nome FROM linhas WHERE status_linha = 'ativa' ORDER BY CAST(numero AS UNSIGNED), numero, nome ASC");
        $lista_linhas_select = $stmt_linhas->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { /* erro */ }
}

$info_opcoes = []; $total_itens = 0; $total_paginas = 0; $erro_busca = false;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="tabelas_hub.php" class="btn btn-outline-secondary mr-2"><i class="fas fa-arrow-left"></i> Voltar ao Hub</a>
        <?php if (in_array($admin_nivel_acesso_logado, $niveis_permitidos_crud_infos)): ?>
        <a href="info_opcoes_formulario.php" class="btn btn-success"><i class="fas fa-plus"></i> Adicionar Nova Opção</a>
        <?php endif; ?>
    </div>
</div>

<?php 
if (isset($_SESSION['admin_success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_success_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_success_message']); }
if (isset($_SESSION['admin_error_message'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_error_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_error_message']); }
?>

<form method="GET" action="info_opcoes_listar.php" class="mb-4 card card-body bg-light p-3 shadow-sm">
    <div class="form-row align-items-end">
        <div class="col-md-4 form-group mb-md-0">
            <label for="busca_descricao_info" class="sr-only">Buscar por descrição</label>
            <input type="text" name="busca_descricao" id="busca_descricao_info" class="form-control form-control-sm" placeholder="Descrição da Info..." value="<?php echo htmlspecialchars($filtro_descricao); ?>">
        </div>
        <div class="col-md-3 form-group mb-md-0">
            <label for="busca_linha_id_info" class="sr-only">Filtrar por Linha</label>
            <select name="busca_linha_id" id="busca_linha_id_info" class="form-control form-control-sm select2-simple-filter-info">
                <option value="">Todas as Linhas / Globais</option>
                <option value="global" <?php if ($filtro_linha_id_get === 'global') echo 'selected';?>>Apenas Globais (sem linha)</option>
                <?php foreach($lista_linhas_select as $linha_f): ?>
                    <option value="<?php echo $linha_f['id']; ?>" <?php echo ($filtro_linha_id_get == $linha_f['id']) ? 'selected' : ''; ?>>
                        Linha <?php echo htmlspecialchars($linha_f['numero'] . ($linha_f['nome'] ? ' - ' . $linha_f['nome'] : '')); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 form-group mb-md-0">
            <label for="status_filtro_info" class="sr-only">Filtrar por status</label>
            <select name="status_filtro" id="status_filtro_info" class="form-control form-control-sm">
                <option value="">Todos os Status</option>
                <option value="ativo" <?php echo ($filtro_status_info === 'ativo') ? 'selected' : ''; ?>>Ativa</option>
                <option value="inativo" <?php echo ($filtro_status_info === 'inativo') ? 'selected' : ''; ?>>Inativa</option>
            </select>
        </div>
        <div class="col-md-1 form-group mb-md-0">
            <button type="submit" class="btn btn-sm btn-primary btn-block"><i class="fas fa-filter"></i> Filtrar</button>
        </div>
        <?php if (!empty($filtro_descricao) || $filtro_linha_id_get !== null || !empty($filtro_status_info)): ?>
        <div class="col-md-2 form-group mb-md-0">
            <a href="info_opcoes_listar.php" class="btn btn-sm btn-outline-secondary btn-block"><i class="fas fa-times"></i> Limpar</a>
        </div>
        <?php endif; ?>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-sm table-hover">
        <thead class="thead-light">
            <tr>
                <th>Descrição da Informação</th>
                <th>Linha Associada</th>
                <th>Status</th>
                <th style="width: 150px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($pdo) {
                try {
                    $sql_where = []; $params = [];
                    if (!empty($filtro_descricao)) { $sql_where[] = "io.descricao_info LIKE :desc"; $params[':desc'] = '%' . $filtro_descricao . '%'; }
                    
                    if ($filtro_linha_id_get !== null && $filtro_linha_id_get !== '') {
                        if (strtolower($filtro_linha_id_get) === 'global') {
                             $sql_where[] = "io.linha_id IS NULL";
                        } else {
                             $valid_linha_id_filter = filter_var($filtro_linha_id_get, FILTER_VALIDATE_INT);
                             if ($valid_linha_id_filter) {
                                $sql_where[] = "io.linha_id = :lid"; $params[':lid'] = $valid_linha_id_filter;
                             }
                        }
                    }
                    if (!empty($filtro_status_info)) { $sql_where[] = "io.status_info = :stat"; $params[':stat'] = $filtro_status_info; }

                    $sql_where_clause = !empty($sql_where) ? " WHERE " . implode(" AND ", $sql_where) : "";

                    $stmt_count = $pdo->prepare("SELECT COUNT(io.id) FROM info_opcoes io" . $sql_where_clause);
                    $stmt_count->execute($params);
                    $total_itens = (int)$stmt_count->fetchColumn();
                    $total_paginas = ceil($total_itens / $itens_por_pagina);
                    if ($pagina_atual > $total_paginas && $total_paginas > 0) $pagina_atual = $total_paginas;
                    if ($pagina_atual < 1) $pagina_atual = 1;
                    $offset = ($pagina_atual - 1) * $itens_por_pagina;

                    // O ID ainda é selecionado para as ações, mas não será exibido
                    $sql_select = "SELECT io.id, io.descricao_info, io.linha_id, io.status_info, l.numero as linha_numero, l.nome as linha_nome 
                                   FROM info_opcoes io 
                                   LEFT JOIN linhas l ON io.linha_id = l.id "
                                . $sql_where_clause 
                                . " ORDER BY io.linha_id ASC, io.descricao_info ASC LIMIT :limit OFFSET :offset";
                    $stmt = $pdo->prepare($sql_select);
                    foreach ($params as $key => $value) { $stmt->bindValue($key, $value); }
                    $stmt->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT);
                    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                    $stmt->execute();
                    $info_opcoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($info_opcoes) {
                        foreach ($info_opcoes as $info) {
                            echo "<tr>";
                            // echo "<td>" . htmlspecialchars($info['id']) . "</td>"; // REMOVIDO
                            echo "<td>" . htmlspecialchars($info['descricao_info']) . "</td>";
                            echo "<td>" . ($info['linha_id'] ? htmlspecialchars($info['linha_numero'] . ($info['linha_nome'] ? ' - ' . $info['linha_nome'] : '')) : '<em>Global</em>') . "</td>";
                            echo "<td><span class='badge badge-" . ($info['status_info'] == 'ativo' ? 'success' : 'danger') . " p-2'>" . ucfirst($info['status_info']) . "</span></td>";
                            echo "<td class='action-buttons'>";
                            if (in_array($admin_nivel_acesso_logado, $niveis_permitidos_crud_infos)) {
                                echo "<a href='info_opcoes_formulario.php?id=" . $info['id'] . "&pagina=" . $pagina_atual . "&" . http_build_query(['busca_descricao' => $filtro_descricao, 'busca_linha_id' => $filtro_linha_id_get, 'status_filtro' => $filtro_status_info]) . "' class='btn btn-primary btn-sm' title='Editar'><i class='fas fa-edit'></i></a> ";
                                $acao_status = $info['status_info'] == 'ativo' ? 'desativar' : 'ativar';
                                $btn_classe_status = $info['status_info'] == 'ativo' ? 'btn-warning' : 'btn-success';
                                $icone_status = $info['status_info'] == 'ativo' ? 'fa-toggle-off' : 'fa-toggle-on';
                                echo "<a href='info_opcoes_acao.php?acao={$acao_status}&id=" . $info['id'] . "&pagina=" . $pagina_atual . "&" . http_build_query(['busca_descricao' => $filtro_descricao, 'busca_linha_id' => $filtro_linha_id_get, 'status_filtro' => $filtro_status_info]) . "' class='btn {$btn_classe_status} btn-sm' title='" . ucfirst($acao_status) . "' onclick='return confirm(\"Tem certeza?\");'><i class='fas {$icone_status}'></i></a>";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else { 
                        $colspan_atual = 3 + ( (in_array($admin_nivel_acesso_logado, $niveis_permitidos_crud_infos)) ? 1 : 0); // Descrição, Linha, Status + Ações
                        echo "<tr><td colspan='".$colspan_atual."' class='text-center'>Nenhuma opção de informação encontrada.</td></tr>"; 
                    }
                } catch (PDOException $e) { 
                    $colspan_atual = 3 + ( (in_array($admin_nivel_acesso_logado, $niveis_permitidos_crud_infos)) ? 1 : 0);
                    echo "<tr><td colspan='".$colspan_atual."' class='text-danger text-center'>Erro: " . $e->getMessage() . "</td></tr>"; $erro_busca = true; 
                }
            } else { 
                $colspan_atual = 3 + ( (in_array($admin_nivel_acesso_logado, $niveis_permitidos_crud_infos)) ? 1 : 0);
                echo "<tr><td colspan='".$colspan_atual."' class='text-danger text-center'>Falha DB.</td></tr>"; $erro_busca = true;
            }
            ?>
        </tbody>
    </table>
</div>

<?php if (!$erro_busca && $total_paginas > 1): ?>
    <nav aria-label="Navegação das opções de informação">
        <ul class="pagination justify-content-center mt-4">
        <?php
        $query_params_pag_info = [];
        if (!empty($filtro_descricao)) $query_params_pag_info['busca_descricao'] = $filtro_descricao;
        if ($filtro_linha_id_get !== null) $query_params_pag_info['busca_linha_id'] = $filtro_linha_id_get;
        if (!empty($filtro_status_info)) $query_params_pag_info['status_filtro'] = $filtro_status_info;
        $link_base_pag_info = 'info_opcoes_listar.php?' . http_build_query($query_params_pag_info) . (empty($query_params_pag_info) ? '' : '&');

        if ($pagina_atual > 1):
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_info . 'pagina=1">Primeira</a></li>';
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_info . 'pagina=' . ($pagina_atual - 1) . '" aria-label="Anterior"><span aria-hidden="true">&laquo;</span></a></li>';
        else:
            echo '<li class="page-item disabled"><span class="page-link">Primeira</span></li>';
            echo '<li class="page-item disabled"><span class="page-link" aria-label="Anterior"><span aria-hidden="true">&laquo;</span></span></li>';
        endif;

        $num_links_nav = 2;
        $inicio_loop = max(1, $pagina_atual - $num_links_nav);
        $fim_loop = min($total_paginas, $pagina_atual + $num_links_nav);

        if ($inicio_loop > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        for ($i = $inicio_loop; $i <= $fim_loop; $i++):
            echo '<li class="page-item ' . ($i == $pagina_atual ? 'active' : '') . '"><a class="page-link" href="' . $link_base_pag_info . 'pagina=' . $i . '">' . $i . '</a></li>';
        endfor;
        if ($fim_loop < $total_paginas) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';

        if ($pagina_atual < $total_paginas):
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_info . 'pagina=' . ($pagina_atual + 1) . '" aria-label="Próxima"><span aria-hidden="true">&raquo;</span></a></li>';
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_info . 'pagina=' . $total_paginas . '">Última</a></li>';
        else:
            echo '<li class="page-item disabled"><span class="page-link" aria-label="Próxima"><span aria-hidden="true">&raquo;</span></span></li>';
            echo '<li class="page-item disabled"><span class="page-link">Última</span></li>';
        endif;
        ?>
        </ul>
    </nav>
<?php endif; ?>

<?php
$page_specific_js = "<script>$(document).ready(function() { $('.select2-simple-filter-info').select2({theme: 'bootstrap4', placeholder: 'Filtrar por Linha...', allowClear: true, width: '100%'}); });</script>";
require_once 'admin_footer.php';
?>