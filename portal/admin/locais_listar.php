<?php
// admin/locais_listar.php
// ATUALIZADO: Removidas colunas ID e Imagem da listagem principal.

require_once 'auth_check.php'; 

// --- Definição de Permissões (AJUSTE CONFORME NECESSÁRIO) ---
$niveis_permitidos_ver_locais = ['Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
$niveis_permitidos_adicionar_locais = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
$niveis_permitidos_editar_locais = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
$niveis_permitidos_apagar_locais = ['Gerência', 'Administrador']; 

if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_ver_locais)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para acessar o gerenciamento de locais.";
    header('Location: index.php');
    exit;
}

require_once '../db_config.php';
$page_title = 'Gerenciar Locais Escala';
require_once 'admin_header.php';

$itens_por_pagina = 20;
$pagina_atual = isset($_GET['pagina']) && filter_var($_GET['pagina'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$filtro_nome = isset($_GET['busca_nome']) ? trim($_GET['busca_nome']) : '';
$filtro_tipo = isset($_GET['busca_tipo']) ? trim($_GET['busca_tipo']) : '';

$tipos_locais_filtro = ['Garagem', 'Terminal', 'Ponto', 'CIOP']; 

$locais = [];
$total_itens = 0;
$total_paginas = 0;
$erro_busca = false;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <?php if (in_array($admin_nivel_acesso_logado, $niveis_permitidos_adicionar_locais)): ?>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="local_formulario.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Adicionar Novo Local
        </a>
    </div>
    <?php endif; ?>
</div>

<?php
if (isset($_SESSION['admin_success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_success_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_success_message']); }
if (isset($_SESSION['admin_error_message'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_error_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_error_message']); }
?>

<form method="GET" action="locais_listar.php" class="mb-4 card card-body bg-light p-3 shadow-sm">
    <div class="form-row align-items-end">
        <div class="col-md-5 form-group mb-md-0">
            <label for="busca_nome_local" class="sr-only">Buscar por nome</label>
            <input type="text" name="busca_nome" id="busca_nome_local" class="form-control form-control-sm" placeholder="Nome do Local..." value="<?php echo htmlspecialchars($filtro_nome); ?>">
        </div>
        <div class="col-md-3 form-group mb-md-0">
            <label for="busca_tipo_local" class="sr-only">Filtrar por tipo</label>
            <select name="busca_tipo" id="busca_tipo_local" class="form-control form-control-sm">
                <option value="">Todos os Tipos</option>
                <?php foreach ($tipos_locais_filtro as $tipo_opt): ?>
                    <option value="<?php echo htmlspecialchars($tipo_opt); ?>" <?php echo ($filtro_tipo === $tipo_opt) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tipo_opt); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 form-group mb-md-0">
            <button type="submit" class="btn btn-sm btn-primary btn-block"><i class="fas fa-filter"></i> Filtrar</button>
        </div>
        <?php if (!empty($filtro_nome) || !empty($filtro_tipo)): ?>
        <div class="col-md-2 form-group mb-md-0">
            <a href="locais_listar.php" class="btn btn-sm btn-outline-secondary btn-block"><i class="fas fa-times"></i> Limpar</a>
        </div>
        <?php endif; ?>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-sm table-hover">
        <thead class="thead-light">
            <tr>
                <th>Nome do Local</th>
                <th>Tipo</th>
                <th style="width: 120px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($pdo) {
                try {
                    $sql_where_parts = [];
                    $params_sql = [];

                    if (!empty($filtro_nome)) {
                        $sql_where_parts[] = "nome LIKE :nome_f";
                        $params_sql[':nome_f'] = '%' . $filtro_nome . '%';
                    }
                    if (!empty($filtro_tipo)) {
                        $sql_where_parts[] = "tipo = :tipo_f";
                        $params_sql[':tipo_f'] = $filtro_tipo;
                    }
                    $sql_where_clause = "";
                    if (!empty($sql_where_parts)) {
                        $sql_where_clause = " WHERE " . implode(" AND ", $sql_where_parts);
                    }

                    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM locais" . $sql_where_clause);
                    $stmt_count->execute($params_sql);
                    $total_itens = (int)$stmt_count->fetchColumn();
                    $total_paginas = ceil($total_itens / $itens_por_pagina);
                    if ($pagina_atual > $total_paginas && $total_paginas > 0) $pagina_atual = $total_paginas;
                    if ($pagina_atual < 1) $pagina_atual = 1;
                    $offset = ($pagina_atual - 1) * $itens_por_pagina;

                    // Query SQL ainda seleciona o ID para as ações, mas não o imagem_path se não for usar
                    $stmt_select = $pdo->prepare("SELECT id, nome, tipo FROM locais" . $sql_where_clause . " ORDER BY nome ASC LIMIT :limit OFFSET :offset");
                    foreach ($params_sql as $key => $value) {
                        $stmt_select->bindValue($key, $value);
                    }
                    $stmt_select->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT);
                    $stmt_select->bindValue(':offset', $offset, PDO::PARAM_INT);
                    $stmt_select->execute();
                    $locais = $stmt_select->fetchAll(PDO::FETCH_ASSOC);

                    if ($locais) {
                        foreach ($locais as $local) {
                            echo "<tr>";
                            // echo "<td>" . htmlspecialchars($local['id']) . "</td>"; // REMOVIDO
                            echo "<td>" . htmlspecialchars($local['nome']) . "</td>";
                            echo "<td>" . htmlspecialchars($local['tipo'] ?: '-') . "</td>";
                            // Seção da imagem REMOVIDA
                            echo "<td class='action-buttons'>";
                            if (in_array($admin_nivel_acesso_logado, $niveis_permitidos_editar_locais)) {
                                echo "<a href='local_formulario.php?id=" . $local['id'] . "&pagina=" . $pagina_atual . "&" . http_build_query(['busca_nome' => $filtro_nome, 'busca_tipo' => $filtro_tipo]) . "' class='btn btn-primary btn-sm' title='Editar Local'><i class='fas fa-edit'></i></a> ";
                            }
                            if (in_array($admin_nivel_acesso_logado, $niveis_permitidos_apagar_locais)) {
                                echo "<a href='local_apagar.php?id=" . $local['id'] . "&pagina=" . $pagina_atual . "&" . http_build_query(['busca_nome' => $filtro_nome, 'busca_tipo' => $filtro_tipo]) . "' class='btn btn-danger btn-sm' title='Apagar Local' onclick='return confirm(\"Tem certeza que deseja apagar o local: " . htmlspecialchars(addslashes($local['nome'])) . "? Esta ação não pode ser desfeita e pode afetar escalas existentes.\");'><i class='fas fa-trash-alt'></i></a>";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        // Ajustar colspan se necessário (eram 5 colunas, agora são 3 + ações = 4 ou 3 se não houver ações)
                        $colspan_atual = 2 + ( (in_array($admin_nivel_acesso_logado, $niveis_permitidos_editar_locais) || in_array($admin_nivel_acesso_logado, $niveis_permitidos_apagar_locais)) ? 1 : 0);
                        echo "<tr><td colspan='" . $colspan_atual . "' class='text-center'>Nenhum local encontrado" . (!empty($filtro_nome) || !empty($filtro_tipo) ? " com os filtros aplicados" : "") . ".</td></tr>";
                    }
                } catch (PDOException $e) {
                     $colspan_atual = 2 + ( (in_array($admin_nivel_acesso_logado, $niveis_permitidos_editar_locais) || in_array($admin_nivel_acesso_logado, $niveis_permitidos_apagar_locais)) ? 1 : 0);
                    echo "<tr><td colspan='" . $colspan_atual . "' class='text-danger text-center'>Erro ao buscar locais: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                    $erro_busca = true;
                }
            } else {
                 $colspan_atual = 2 + ( (in_array($admin_nivel_acesso_logado, $niveis_permitidos_editar_locais) || in_array($admin_nivel_acesso_logado, $niveis_permitidos_apagar_locais)) ? 1 : 0);
                 echo "<tr><td colspan='" . $colspan_atual . "' class='text-danger text-center'>Falha na conexão com o banco de dados.</td></tr>";
                 $erro_busca = true;
            }
            ?>
        </tbody>
    </table>
</div>

<?php if (!$erro_busca && $total_paginas > 1): ?>
<nav aria-label="Navegação dos locais">
    <ul class="pagination justify-content-center mt-4">
        <?php
        // ... (lógica de paginação como antes, já deve estar correta) ...
        $query_params_pag = [];
        if (!empty($filtro_nome)) $query_params_pag['busca_nome'] = $filtro_nome;
        if (!empty($filtro_tipo)) $query_params_pag['busca_tipo'] = $filtro_tipo;
        $link_base_pag = 'locais_listar.php?' . http_build_query($query_params_pag) . (empty($query_params_pag) ? '' : '&');

        if ($pagina_atual > 1):
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag . 'pagina=1">Primeira</a></li>';
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag . 'pagina=' . ($pagina_atual - 1) . '" aria-label="Anterior"><span aria-hidden="true">&laquo;</span></a></li>';
        else:
            echo '<li class="page-item disabled"><span class="page-link">Primeira</span></li>';
            echo '<li class="page-item disabled"><span class="page-link" aria-label="Anterior"><span aria-hidden="true">&laquo;</span></span></li>';
        endif;

        $num_links_nav = 2;
        $inicio_loop = max(1, $pagina_atual - $num_links_nav);
        $fim_loop = min($total_paginas, $pagina_atual + $num_links_nav);

        if ($inicio_loop > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        for ($i = $inicio_loop; $i <= $fim_loop; $i++):
            echo '<li class="page-item ' . ($i == $pagina_atual ? 'active' : '') . '"><a class="page-link" href="' . $link_base_pag . 'pagina=' . $i . '">' . $i . '</a></li>';
        endfor;
        if ($fim_loop < $total_paginas) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';

        if ($pagina_atual < $total_paginas):
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag . 'pagina=' . ($pagina_atual + 1) . '" aria-label="Próxima"><span aria-hidden="true">&raquo;</span></a></li>';
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag . 'pagina=' . $total_paginas . '">Última</a></li>';
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