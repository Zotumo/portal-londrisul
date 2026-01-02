<?php
// admin/usuarios_listar.php (v2 - Sem coluna ID na tabela)

require_once 'auth_check.php'; 

$niveis_permitidos_ver_lista_usuarios = ['Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_ver_lista_usuarios)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para acessar a área de gerenciamento de usuários do painel.";
    header('Location: index.php');
    exit;
}

require_once '../db_config.php';
$page_title = 'Gerenciar Usuários do Painel';

// --- Lógica de Filtro e Paginação ---
$usuarios_por_pagina = 15;
$pagina_atual = isset($_GET['pagina']) && filter_var($_GET['pagina'], FILTER_VALIDATE_INT) && $_GET['pagina'] > 0 ? (int)$_GET['pagina'] : 1;
if ($pagina_atual < 1) $pagina_atual = 1;

$filtro_busca_usuario = isset($_GET['busca_usuario']) ? trim($_GET['busca_usuario']) : '';
$filtro_nivel_usuario = isset($_GET['nivel_filtro_usuario']) ? trim($_GET['nivel_filtro_usuario']) : '';

$todos_niveis_acesso_filtro = ['Agente de Terminal', 'Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];

$usuarios_admin = [];
$total_usuarios = 0;
$total_paginas = 0;
$erro_busca_usuarios = false;

if ($pdo) {
    try {
        $sql_base_usuarios = "FROM administradores";
        $sql_where_parts_usuarios = [];
        $params_sql_usuarios = [];

        if (!empty($filtro_busca_usuario)) {
            $sql_where_parts_usuarios[] = "(nome LIKE :busca_usr OR username LIKE :busca_usr OR email LIKE :busca_usr)";
            $params_sql_usuarios[':busca_usr'] = '%' . $filtro_busca_usuario . '%';
        }
        if (!empty($filtro_nivel_usuario)) {
            $sql_where_parts_usuarios[] = "nivel_acesso = :nivel_filtro";
            $params_sql_usuarios[':nivel_filtro'] = $filtro_nivel_usuario;
        }
        
        $sql_where_clause_usuarios = "";
        if (!empty($sql_where_parts_usuarios)) {
            $sql_where_clause_usuarios = " WHERE " . implode(" AND ", $sql_where_parts_usuarios);
        }

        $stmt_count_usuarios = $pdo->prepare("SELECT COUNT(id) " . $sql_base_usuarios . $sql_where_clause_usuarios);
        $stmt_count_usuarios->execute($params_sql_usuarios);
        $total_usuarios = (int)$stmt_count_usuarios->fetchColumn();
        $total_paginas = ceil($total_usuarios / $usuarios_por_pagina);
         
        if ($pagina_atual > $total_paginas && $total_paginas > 0) $pagina_atual = $total_paginas;
        $offset = ($pagina_atual - 1) * $usuarios_por_pagina;

        // O ID ainda é selecionado para as ações, mas não será exibido
        $sql_select_usuarios = "SELECT id, nome, username, email, nivel_acesso, data_cadastro " 
                             . $sql_base_usuarios . $sql_where_clause_usuarios 
                             . " ORDER BY nome ASC 
                               LIMIT :limit OFFSET :offset";
        
        $stmt_select = $pdo->prepare($sql_select_usuarios);
        foreach ($params_sql_usuarios as $key_u => $value_u) {
            $stmt_select->bindValue($key_u, $value_u);
        }
        $stmt_select->bindValue(':limit', $usuarios_por_pagina, PDO::PARAM_INT);
        $stmt_select->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt_select->execute();
        $usuarios_admin = $stmt_select->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Erro ao buscar usuários admin: " . $e->getMessage());
        $erro_busca_usuarios = true;
        $_SESSION['admin_error_message'] = "Erro ao carregar usuários do banco de dados.";
    }
} else {
    $erro_busca_usuarios = true;
    $_SESSION['admin_error_message'] = "Falha na conexão com o banco de dados.";
}

require_once 'admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php
        if ($admin_nivel_acesso_logado === 'Administrador'):
        ?>
        <a href="usuario_formulario.php" class="btn btn-success">
            <i class="fas fa-user-plus"></i> Adicionar Novo Usuário
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

<form method="GET" action="usuarios_listar.php" class="mb-3 card card-body bg-light p-3">
    <div class="form-row align-items-end">
        <div class="col-md-4 form-group mb-md-0">
            <label for="busca_usuario_input" class="sr-only">Buscar usuário</label>
            <input type="text" name="busca_usuario" id="busca_usuario_input" class="form-control form-control-sm" placeholder="Nome, Usuário (login) ou Email..." value="<?php echo htmlspecialchars($filtro_busca_usuario); ?>">
        </div>
        <div class="col-md-3 form-group mb-md-0">
            <label for="nivel_filtro_usuario_select" class="sr-only">Filtrar por nível de acesso</label>
            <select name="nivel_filtro_usuario" id="nivel_filtro_usuario_select" class="form-control form-control-sm">
                <option value="">Todos os Níveis</option>
                <?php foreach ($todos_niveis_acesso_filtro as $nivel_opt): ?>
                    <option value="<?php echo htmlspecialchars($nivel_opt); ?>" <?php echo ($filtro_nivel_usuario === $nivel_opt) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($nivel_opt); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 form-group mb-md-0">
            <button type="submit" class="btn btn-sm btn-primary btn-block"><i class="fas fa-search"></i> Filtrar</button>
        </div>
        <?php if (!empty($filtro_busca_usuario) || !empty($filtro_nivel_usuario)): ?>
        <div class="col-md-2 form-group mb-md-0">
            <a href="usuarios_listar.php" class="btn btn-sm btn-outline-secondary btn-block"><i class="fas fa-times"></i> Limpar</a>
        </div>
        <?php endif; ?>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-sm table-hover">
        <thead class="thead-light">
            <tr>
                <th>Nome</th>
                <th>Usuário (Login)</th>
                <th>Email</th>
                <th>Nível de Acesso</th>
                <th>Data Cadastro</th>
                <?php if ($admin_nivel_acesso_logado === 'Administrador'): ?>
                    <th style="width: 120px;">Ações</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!$erro_busca_usuarios && $usuarios_admin) {
                foreach ($usuarios_admin as $usuario) {
                    echo "<tr>";
                    // echo "<td>" . htmlspecialchars($usuario['id']) . "</td>"; REMOVIDO
                    echo "<td>" . htmlspecialchars($usuario['nome']) . "</td>";
                    echo "<td>" . htmlspecialchars($usuario['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($usuario['email'] ?: '-') . "</td>";
                    echo "<td>" . htmlspecialchars($usuario['nivel_acesso']) . "</td>";
                    echo "<td>" . date('d/m/Y H:i', strtotime($usuario['data_cadastro'])) . "</td>";

                    if ($admin_nivel_acesso_logado === 'Administrador') {
                        $query_params_acao_usr = ['pagina' => $pagina_atual];
                        if (!empty($filtro_busca_usuario)) $query_params_acao_usr['busca_usuario'] = $filtro_busca_usuario;
                        if (!empty($filtro_nivel_usuario)) $query_params_acao_usr['nivel_filtro_usuario'] = $filtro_nivel_usuario;
                        $query_string_acao_usr = http_build_query($query_params_acao_usr);

                        echo "<td class='action-buttons'>";
                        if ($_SESSION['admin_user_id'] == $usuario['id']) {
                            echo "<a href='usuario_formulario.php?id=" . $usuario['id'] . "&" . $query_string_acao_usr . "' class='btn btn-primary btn-sm' title='Editar Perfil (Exceto Nível)'><i class='fas fa-user-edit'></i></a> ";
                            echo "<button class='btn btn-danger btn-sm' title='Não pode apagar a si mesmo' disabled><i class='fas fa-trash-alt'></i></button>";
                        } else {
                            echo "<a href='usuario_formulario.php?id=" . $usuario['id'] . "&" . $query_string_acao_usr . "' class='btn btn-primary btn-sm' title='Editar Usuário'><i class='fas fa-edit'></i></a> ";
                            echo "<a href='usuario_apagar.php?id=" . $usuario['id'] . "&" . $query_string_acao_usr . "' class='btn btn-danger btn-sm' title='Apagar Usuário' onclick='return confirm(\"Tem certeza que deseja apagar o usuário " . htmlspecialchars(addslashes($usuario['nome'])) . " (Login: " . htmlspecialchars(addslashes($usuario['username'])) . ")? Esta ação não pode ser desfeita.\");'><i class='fas fa-trash-alt'></i></a>";
                        }
                        echo "</td>";
                    }
                    echo "</tr>";
                }
            } elseif (!$erro_busca_usuarios) {
                // Colspan ajustado de 7 para 6 (ou 6 para 5 se admin não puder ver ações)
                $colspan_usuarios = ($admin_nivel_acesso_logado === 'Administrador') ? 6 : 5;
                echo "<tr><td colspan='" . $colspan_usuarios . "' class='text-center'>Nenhum usuário administrativo encontrado" . (!empty($filtro_busca_usuario) || !empty($filtro_nivel_usuario) ? " com os filtros aplicados" : "") . ".</td></tr>";
            } else {
                $colspan_usuarios = ($admin_nivel_acesso_logado === 'Administrador') ? 6 : 5;
                echo "<tr><td colspan='" . $colspan_usuarios . "' class='text-danger text-center'>Erro ao carregar usuários.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php if (!$erro_busca_usuarios && $total_paginas > 1): ?>
<nav aria-label="Navegação dos usuários">
    <ul class="pagination justify-content-center mt-4">
        <?php
        $query_params_pag_usuarios = [];
        if (!empty($filtro_busca_usuario)) $query_params_pag_usuarios['busca_usuario'] = $filtro_busca_usuario;
        if (!empty($filtro_nivel_usuario)) $query_params_pag_usuarios['nivel_filtro_usuario'] = $filtro_nivel_usuario;
        $link_base_pag_usuarios = 'usuarios_listar.php?' . http_build_query($query_params_pag_usuarios) . (empty($query_params_pag_usuarios) ? '' : '&');
        
        if ($pagina_atual > 1):
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_usuarios . 'pagina=1">Primeira</a></li>';
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_usuarios . 'pagina=' . ($pagina_atual - 1) . '" aria-label="Anterior"><span aria-hidden="true">&laquo;</span></a></li>';
        else:
            echo '<li class="page-item disabled"><span class="page-link">Primeira</span></li>';
            echo '<li class="page-item disabled"><span class="page-link" aria-label="Anterior"><span aria-hidden="true">&laquo;</span></span></li>';
        endif;

        $num_links_paginacao = 3;
        $inicio_loop = max(1, $pagina_atual - $num_links_paginacao);
        $fim_loop = min($total_paginas, $pagina_atual + $num_links_paginacao);

        if ($inicio_loop > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        for ($i = $inicio_loop; $i <= $fim_loop; $i++):
            echo '<li class="page-item ' . ($i == $pagina_atual ? 'active' : '') . '"><a class="page-link" href="' . $link_base_pag_usuarios . 'pagina=' . $i . '">' . $i . '</a></li>';
        endfor;
        if ($fim_loop < $total_paginas) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';

        if ($pagina_atual < $total_paginas):
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_usuarios . 'pagina=' . ($pagina_atual + 1) . '" aria-label="Próxima"><span aria-hidden="true">&raquo;</span></a></li>';
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_usuarios . 'pagina=' . $total_paginas . '">Última</a></li>';
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