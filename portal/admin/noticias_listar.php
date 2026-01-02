<?php
// admin/noticias_listar.php (v2 - Sem coluna ID na tabela)

require_once 'auth_check.php'; 

$niveis_permitidos_ver_lista = ['Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_ver_lista)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para acessar a área de gerenciamento de notícias.";
    header('Location: index.php');
    exit;
}

require_once '../db_config.php';

$page_title = 'Gerenciar Notícias';

// --- Lógica de Filtro e Paginação ---
$itens_por_pagina_noticias = 15; 
$pagina_atual_noticias = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_atual_noticias < 1) $pagina_atual_noticias = 1;

$filtro_titulo_noticia = isset($_GET['busca_titulo']) ? trim($_GET['busca_titulo']) : '';
$filtro_status_noticia = isset($_GET['status_filtro_noticia']) ? trim($_GET['status_filtro_noticia']) : '';

$noticias = [];
$total_noticias = 0;
$total_paginas_noticias = 0;
$erro_busca_noticias = false;

if ($pdo) {
    try {
        $sql_base_noticias = "FROM noticias";
        $sql_where_parts_noticias = [];
        $params_sql_noticias = [];

        if (!empty($filtro_titulo_noticia)) {
            $sql_where_parts_noticias[] = "titulo LIKE :busca_titulo";
            $params_sql_noticias[':busca_titulo'] = '%' . $filtro_titulo_noticia . '%';
        }
        if (!empty($filtro_status_noticia)) {
            $sql_where_parts_noticias[] = "status = :status_filtro";
            $params_sql_noticias[':status_filtro'] = $filtro_status_noticia;
        }
        
        $sql_where_clause_noticias = "";
        if (!empty($sql_where_parts_noticias)) {
            $sql_where_clause_noticias = " WHERE " . implode(" AND ", $sql_where_parts_noticias);
        }

        $stmt_count_noticias = $pdo->prepare("SELECT COUNT(id) " . $sql_base_noticias . $sql_where_clause_noticias);
        $stmt_count_noticias->execute($params_sql_noticias);
        $total_noticias = (int)$stmt_count_noticias->fetchColumn();
        $total_paginas_noticias = ceil($total_noticias / $itens_por_pagina_noticias);
        
        if ($pagina_atual_noticias > $total_paginas_noticias && $total_paginas_noticias > 0) {
            $pagina_atual_noticias = $total_paginas_noticias;
        }
        $offset_noticias = ($pagina_atual_noticias - 1) * $itens_por_pagina_noticias;

        // O ID ainda é selecionado para as ações, mas não será exibido na tabela
        $sql_select_noticias = "SELECT id, titulo, status, data_publicacao, data_modificacao " 
                             . $sql_base_noticias . $sql_where_clause_noticias 
                             . " ORDER BY COALESCE(data_modificacao, data_publicacao) DESC, id DESC 
                               LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql_select_noticias);
        foreach ($params_sql_noticias as $key_n => $value_n) {
            $stmt->bindValue($key_n, $value_n);
        }
        $stmt->bindValue(':limit', $itens_por_pagina_noticias, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset_noticias, PDO::PARAM_INT);
        $stmt->execute();
        $noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Erro ao buscar notícias: " . $e->getMessage());
        $erro_busca_noticias = true;
        $_SESSION['admin_error_message'] = "Erro ao carregar notícias do banco de dados.";
    }
} else {
    $erro_busca_noticias = true;
    $_SESSION['admin_error_message'] = "Falha na conexão com o banco de dados.";
}

require_once 'admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php
        $niveis_permitidos_adicionar = ['Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
        if (in_array($admin_nivel_acesso_logado, $niveis_permitidos_adicionar)):
        ?>
        <a href="noticia_formulario.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Adicionar Nova Notícia
        </a>
        <?php endif; ?>
    </div>
</div>

<?php
if (isset($_SESSION['admin_success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_success_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
    unset($_SESSION['admin_success_message']);
}
if (isset($_SESSION['admin_error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_error_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
    unset($_SESSION['admin_error_message']);
}
?>

<form method="GET" action="noticias_listar.php" class="mb-3 card card-body bg-light p-3">
    <div class="form-row align-items-end">
        <div class="col-md-5 form-group mb-md-0">
            <label for="busca_titulo_noticia_input" class="sr-only">Buscar por título</label>
            <input type="text" name="busca_titulo" id="busca_titulo_noticia_input" class="form-control form-control-sm" placeholder="Buscar por título da notícia..." value="<?php echo htmlspecialchars($filtro_titulo_noticia); ?>">
        </div>
        <div class="col-md-3 form-group mb-md-0">
            <label for="status_filtro_noticia_select" class="sr-only">Filtrar por status</label>
            <select name="status_filtro_noticia" id="status_filtro_noticia_select" class="form-control form-control-sm">
                <option value="">Todos os Status</option>
                <option value="publicada" <?php echo ($filtro_status_noticia === 'publicada') ? 'selected' : ''; ?>>Publicada</option>
                <option value="rascunho" <?php echo ($filtro_status_noticia === 'rascunho') ? 'selected' : ''; ?>>Rascunho</option>
                <option value="arquivada" <?php echo ($filtro_status_noticia === 'arquivada') ? 'selected' : ''; ?>>Arquivada</option>
            </select>
        </div>
        <div class="col-md-2 form-group mb-md-0">
            <button type="submit" class="btn btn-sm btn-primary btn-block"><i class="fas fa-search"></i> Filtrar</button>
        </div>
        <?php if (!empty($filtro_titulo_noticia) || !empty($filtro_status_noticia)): ?>
        <div class="col-md-2 form-group mb-md-0">
            <a href="noticias_listar.php" class="btn btn-sm btn-outline-secondary btn-block"><i class="fas fa-times"></i> Limpar</a>
        </div>
        <?php endif; ?>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-sm table-hover">
        <thead class="thead-light">
            <tr>
                <th>Título</th>
                <th>Status</th>
                <th>Data Publicação</th>
                <th>Última Modificação</th>
                <th style="width: 150px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!$erro_busca_noticias && $noticias) {
                foreach ($noticias as $noticia) {
                    echo "<tr>";
                    // echo "<td>" . htmlspecialchars($noticia['id']) . "</td>"; REMOVIDO
                    echo "<td>" . htmlspecialchars($noticia['titulo']) . "</td>";
                    echo "<td><span class='status-" . htmlspecialchars(strtolower($noticia['status'])) . "'>" . htmlspecialchars(ucfirst($noticia['status'])) . "</span></td>";
                    echo "<td>" . ($noticia['data_publicacao'] ? date('d/m/Y H:i', strtotime($noticia['data_publicacao'])) : 'Não publicada') . "</td>";
                    echo "<td>" . ($noticia['data_modificacao'] ? date('d/m/Y H:i', strtotime($noticia['data_modificacao'])) : '-') . "</td>";
                    echo "<td class='action-buttons'>";

                    echo "<a href='../ver_noticia.php?id=" . $noticia['id'] . "' class='btn btn-info btn-sm' title='Ver no Portal' target='_blank'><i class='fas fa-eye'></i></a> ";

                    $niveis_permitidos_editar = ['Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
                    if (in_array($admin_nivel_acesso_logado, $niveis_permitidos_editar)) {
                        echo "<a href='noticia_formulario.php?id=" . $noticia['id'] . "&pagina=" . $pagina_atual_noticias . "&" . http_build_query(['busca_titulo' => $filtro_titulo_noticia, 'status_filtro_noticia' => $filtro_status_noticia]) ."' class='btn btn-primary btn-sm' title='Editar'><i class='fas fa-edit'></i></a> ";
                    }

                    $niveis_permitidos_apagar = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
                    if (in_array($admin_nivel_acesso_logado, $niveis_permitidos_apagar)) {
                        echo "<a href='noticia_apagar.php?id=" . $noticia['id'] . "&pagina=" . $pagina_atual_noticias . "&" . http_build_query(['busca_titulo' => $filtro_titulo_noticia, 'status_filtro_noticia' => $filtro_status_noticia]) ."' class='btn btn-danger btn-sm' title='Apagar' onclick='return confirm(\"Tem certeza que deseja apagar a notícia ID: " . $noticia['id'] . " - Título: " . htmlspecialchars(addslashes($noticia['titulo'])) . "? Esta ação não pode ser desfeita.\");'><i class='fas fa-trash-alt'></i></a>";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
            } elseif (!$erro_busca_noticias) {
                // Colspan ajustado de 6 para 5
                echo "<tr><td colspan='5' class='text-center'>Nenhuma notícia encontrada" . (!empty($filtro_titulo_noticia) || !empty($filtro_status_noticia) ? " com os filtros aplicados" : "") . ".</td></tr>";
            } else {
                 // Colspan ajustado de 6 para 5
                 echo "<tr><td colspan='5' class='text-danger text-center'>Erro ao carregar notícias.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php if (!$erro_busca_noticias && $total_paginas_noticias > 1): ?>
<nav aria-label="Navegação das notícias">
    <ul class="pagination justify-content-center mt-4">
        <?php
        $query_params_pag_noticias = [];
        if (!empty($filtro_titulo_noticia)) $query_params_pag_noticias['busca_titulo'] = $filtro_titulo_noticia;
        if (!empty($filtro_status_noticia)) $query_params_pag_noticias['status_filtro_noticia'] = $filtro_status_noticia;
        $link_base_pag_noticias = 'noticias_listar.php?' . http_build_query($query_params_pag_noticias) . (empty($query_params_pag_noticias) ? '' : '&');

        if ($pagina_atual_noticias > 1):
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_noticias . 'pagina=1">Primeira</a></li>';
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_noticias . 'pagina=' . ($pagina_atual_noticias - 1) . '" aria-label="Anterior"><span aria-hidden="true">&laquo;</span></a></li>';
        else:
            echo '<li class="page-item disabled"><span class="page-link">Primeira</span></li>';
            echo '<li class="page-item disabled"><span class="page-link" aria-label="Anterior"><span aria-hidden="true">&laquo;</span></span></li>';
        endif;

        $num_links_nav_noticias = 2; 
        $inicio_loop_noticias = max(1, $pagina_atual_noticias - $num_links_nav_noticias);
        $fim_loop_noticias = min($total_paginas_noticias, $pagina_atual_noticias + $num_links_nav_noticias);

        if ($inicio_loop_noticias > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        for ($i = $inicio_loop_noticias; $i <= $fim_loop_noticias; $i++):
            echo '<li class="page-item ' . ($i == $pagina_atual_noticias ? 'active' : '') . '"><a class="page-link" href="' . $link_base_pag_noticias . 'pagina=' . $i . '">' . $i . '</a></li>';
        endfor;
        if ($fim_loop_noticias < $total_paginas_noticias) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';

        if ($pagina_atual_noticias < $total_paginas_noticias):
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_noticias . 'pagina=' . ($pagina_atual_noticias + 1) . '" aria-label="Próxima"><span aria-hidden="true">&raquo;</span></a></li>';
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_noticias . 'pagina=' . $total_paginas_noticias . '">Última</a></li>';
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