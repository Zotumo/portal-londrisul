<?php
// admin/veiculos_listar.php

require_once 'auth_check.php'; // Autenticação e permissões básicas

// --- Definição de Permissões para ESTA PÁGINA ---
// Visualizar a lista (já verificado em auth_check.php de certa forma, mas podemos refinar)
$niveis_permitidos_ver_frota = ['Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_ver_frota)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para acessar o gerenciamento da frota.";
    header('Location: index.php'); // Ou para um dashboard principal do admin
    exit;
}

// Permissões para ações específicas dentro da página
$pode_adicionar_veiculo = in_array($admin_nivel_acesso_logado, ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador']);
$pode_editar_veiculo = in_array($admin_nivel_acesso_logado, ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador']);
$pode_mudar_status_veiculo = in_array($admin_nivel_acesso_logado, ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador']);

require_once '../db_config.php';
$page_title = 'Gerenciar Frota de Veículos';
require_once 'admin_header.php';

// --- Lógica de Filtros e Paginação ---
$itens_por_pagina = 15;
$pagina_atual = isset($_GET['pagina']) && filter_var($_GET['pagina'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$filtro_prefixo = isset($_GET['busca_prefixo']) ? trim($_GET['busca_prefixo']) : '';
$filtro_tipo_veiculo = isset($_GET['busca_tipo']) ? trim($_GET['busca_tipo']) : '';
$filtro_status_veiculo = isset($_GET['busca_status']) ? trim($_GET['busca_status']) : '';

// Opções para os filtros de select
$tipos_veiculo_filtro = [
    'Convencional Amarelo', 'Convencional Amarelo com Ar', 'Micro', 'Micro com Ar',
    'Convencional Azul', 'Convencional Azul com Ar', 'Padron Azul', 'SuperBus', 'Leve'
];
$status_veiculo_filtro_opcoes = ['operação', 'fora de operação'];

$veiculos = [];
$total_itens = 0;
$total_paginas = 0;
$erro_busca_veiculos = false;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <?php if ($pode_adicionar_veiculo): ?>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="veiculo_formulario.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Adicionar Novo Veículo
        </a>
    </div>
    <?php endif; ?>
</div>

<?php
// Exibir mensagens de feedback (sucesso/erro)
if (isset($_SESSION['admin_success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_success_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_success_message']); }
if (isset($_SESSION['admin_error_message'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_error_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_error_message']); }
if (isset($_SESSION['admin_warning_message'])) { echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_warning_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_warning_message']); }
?>

<form method="GET" action="veiculos_listar.php" class="mb-4 card card-body bg-light p-3 shadow-sm">
    <div class="form-row align-items-end">
        <div class="col-md-3 form-group mb-md-0">
            <label for="busca_prefixo_veiculo" class="sr-only">Buscar por Prefixo</label>
            <input type="text" name="busca_prefixo" id="busca_prefixo_veiculo" class="form-control form-control-sm" placeholder="Prefixo do Veículo..." value="<?php echo htmlspecialchars($filtro_prefixo); ?>">
        </div>
        <div class="col-md-3 form-group mb-md-0">
            <label for="busca_tipo_veiculo" class="sr-only">Filtrar por Tipo</label>
            <select name="busca_tipo" id="busca_tipo_veiculo" class="form-control form-control-sm">
                <option value="">Todos os Tipos</option>
                <?php foreach ($tipos_veiculo_filtro as $tipo_opt_v): ?>
                    <option value="<?php echo htmlspecialchars($tipo_opt_v); ?>" <?php echo ($filtro_tipo_veiculo === $tipo_opt_v) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tipo_opt_v); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 form-group mb-md-0">
            <label for="busca_status_veiculo" class="sr-only">Filtrar por Status</label>
            <select name="busca_status" id="busca_status_veiculo" class="form-control form-control-sm">
                <option value="">Todos os Status</option>
                <?php foreach ($status_veiculo_filtro_opcoes as $status_opt_v): ?>
                    <option value="<?php echo htmlspecialchars($status_opt_v); ?>" <?php echo ($filtro_status_veiculo === $status_opt_v) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst($status_opt_v)); // Deixa a primeira letra maiúscula para exibição ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1 form-group mb-md-0">
            <button type="submit" class="btn btn-sm btn-primary btn-block"><i class="fas fa-filter"></i> Filtrar</button>
        </div>
        <?php if (!empty($filtro_prefixo) || !empty($filtro_tipo_veiculo) || !empty($filtro_status_veiculo)): ?>
        <div class="col-md-2 form-group mb-md-0">
            <a href="veiculos_listar.php" class="btn btn-sm btn-outline-secondary btn-block"><i class="fas fa-times"></i> Limpar</a>
        </div>
        <?php endif; ?>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-sm table-hover">
        <thead class="thead-light">
            <tr>
                <th>Prefixo</th>
                <th>Tipo</th>
                <th>Status</th>
                <?php if ($pode_editar_veiculo || $pode_mudar_status_veiculo): ?>
                <th style="width: 120px;">Ações</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($pdo) {
                try {
                    $sql_where_parts_veiculos = [];
                    $params_sql_veiculos = [];

                    if (!empty($filtro_prefixo)) {
                        $sql_where_parts_veiculos[] = "prefixo LIKE :prefixo_f";
                        $params_sql_veiculos[':prefixo_f'] = '%' . $filtro_prefixo . '%';
                    }
                    if (!empty($filtro_tipo_veiculo)) {
                        $sql_where_parts_veiculos[] = "tipo = :tipo_f";
                        $params_sql_veiculos[':tipo_f'] = $filtro_tipo_veiculo;
                    }
                    if (!empty($filtro_status_veiculo)) {
                        $sql_where_parts_veiculos[] = "status = :status_f";
                        $params_sql_veiculos[':status_f'] = $filtro_status_veiculo;
                    }
                    
                    $sql_where_clause_veiculos = "";
                    if (!empty($sql_where_parts_veiculos)) {
                        $sql_where_clause_veiculos = " WHERE " . implode(" AND ", $sql_where_parts_veiculos);
                    }

                    $stmt_count_veiculos = $pdo->prepare("SELECT COUNT(id) FROM veiculos" . $sql_where_clause_veiculos);
                    $stmt_count_veiculos->execute($params_sql_veiculos);
                    $total_itens = (int)$stmt_count_veiculos->fetchColumn();
                    $total_paginas = ceil($total_itens / $itens_por_pagina);

                    if ($pagina_atual > $total_paginas && $total_paginas > 0) $pagina_atual = $total_paginas;
                    if ($pagina_atual < 1) $pagina_atual = 1; // Garante que não seja menor que 1
                    $offset = ($pagina_atual - 1) * $itens_por_pagina;

                    $sql_select_veiculos = "SELECT id, prefixo, tipo, status FROM veiculos"
                                         . $sql_where_clause_veiculos 
                                         . " ORDER BY prefixo ASC 
                                           LIMIT :limit OFFSET :offset";
                    
                    $stmt_select_veiculos = $pdo->prepare($sql_select_veiculos);
                    foreach ($params_sql_veiculos as $key_v => $value_v) {
                        $stmt_select_veiculos->bindValue($key_v, $value_v);
                    }
                    $stmt_select_veiculos->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT);
                    $stmt_select_veiculos->bindValue(':offset', $offset, PDO::PARAM_INT);
                    $stmt_select_veiculos->execute();
                    $veiculos = $stmt_select_veiculos->fetchAll(PDO::FETCH_ASSOC);

                    if ($veiculos) {
                        foreach ($veiculos as $veiculo) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($veiculo['prefixo']) . "</td>";
                            echo "<td>" . htmlspecialchars($veiculo['tipo'] ?: '-') . "</td>";
                            echo "<td><span class='badge badge-" . ($veiculo['status'] == 'operação' ? 'success' : 'danger') . " p-2'>" . htmlspecialchars(ucfirst($veiculo['status'])) . "</span></td>";
                            
                            if ($pode_editar_veiculo || $pode_mudar_status_veiculo) {
                                echo "<td class='action-buttons'>";
                                if ($pode_editar_veiculo) {
                                    echo "<a href='veiculo_formulario.php?id=" . $veiculo['id'] . "&pagina=" . $pagina_atual . "&" . http_build_query(['busca_prefixo' => $filtro_prefixo, 'busca_tipo' => $filtro_tipo_veiculo, 'busca_status' => $filtro_status_veiculo]) . "' class='btn btn-primary btn-sm' title='Editar Veículo'><i class='fas fa-edit'></i></a> ";
                                }
                                if ($pode_mudar_status_veiculo) {
                                    $nova_acao_status_veiculo = ($veiculo['status'] == 'operação' ? 'desativar' : 'ativar');
                                    $btn_classe_status_veiculo = ($veiculo['status'] == 'operação' ? 'btn-warning' : 'btn-success');
                                    $icone_status_veiculo = ($veiculo['status'] == 'operação' ? 'fa-toggle-off' : 'fa-toggle-on');
                                    $query_string_acao_veiculo = http_build_query(['busca_prefixo' => $filtro_prefixo, 'busca_tipo' => $filtro_tipo_veiculo, 'busca_status' => $filtro_status_veiculo]);

                                    echo "<a href='veiculo_acao.php?acao={$nova_acao_status_veiculo}&id=" . $veiculo['id'] . "&pagina=" . $pagina_atual . "&" . $query_string_acao_veiculo . "&token=" . uniqid('csrf_v_status_',true) . "' class='btn {$btn_classe_status_veiculo} btn-sm' title='" . ucfirst($nova_acao_status_veiculo) . " Veículo' onclick='return confirm(\"Tem certeza que deseja " . $nova_acao_status_veiculo . " o veículo prefixo " . htmlspecialchars(addslashes($veiculo['prefixo'])) . "?\");'><i class='fas {$icone_status_veiculo}'></i></a>";
                                }
                                echo "</td>";
                            }
                            echo "</tr>";
                        }
                    } else {
                        $colspan_veiculos = 3 + (($pode_editar_veiculo || $pode_mudar_status_veiculo) ? 1 : 0);
                        echo "<tr><td colspan='{$colspan_veiculos}' class='text-center'>Nenhum veículo encontrado" . (!empty($filtro_prefixo) || !empty($filtro_tipo_veiculo) || !empty($filtro_status_veiculo) ? " com os filtros aplicados" : "") . ".</td></tr>";
                    }
                } catch (PDOException $e) {
                    $colspan_veiculos = 3 + (($pode_editar_veiculo || $pode_mudar_status_veiculo) ? 1 : 0);
                    echo "<tr><td colspan='{$colspan_veiculos}' class='text-danger text-center'>Erro ao buscar veículos: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                    $erro_busca_veiculos = true;
                }
            } else {
                 $colspan_veiculos = 3 + (($pode_editar_veiculo || $pode_mudar_status_veiculo) ? 1 : 0);
                 echo "<tr><td colspan='{$colspan_veiculos}' class='text-danger text-center'>Falha na conexão com o banco de dados.</td></tr>";
                 $erro_busca_veiculos = true;
            }
            ?>
        </tbody>
    </table>
</div>

<?php if (!$erro_busca_veiculos && $total_paginas > 1): ?>
<nav aria-label="Navegação dos veículos">
    <ul class="pagination justify-content-center mt-4">
        <?php
        $query_params_pag_veiculos = [];
        if (!empty($filtro_prefixo)) $query_params_pag_veiculos['busca_prefixo'] = $filtro_prefixo;
        if (!empty($filtro_tipo_veiculo)) $query_params_pag_veiculos['busca_tipo'] = $filtro_tipo_veiculo;
        if (!empty($filtro_status_veiculo)) $query_params_pag_veiculos['busca_status'] = $filtro_status_veiculo;
        $link_base_pag_veiculos = 'veiculos_listar.php?' . http_build_query($query_params_pag_veiculos) . (empty($query_params_pag_veiculos) ? '' : '&');
        
        // Lógica de paginação (Primeira, Anterior)
        if ($pagina_atual > 1):
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_veiculos . 'pagina=1">Primeira</a></li>';
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_veiculos . 'pagina=' . ($pagina_atual - 1) . '" aria-label="Anterior"><span aria-hidden="true">&laquo;</span></a></li>';
        else:
            echo '<li class="page-item disabled"><span class="page-link">Primeira</span></li>';
            echo '<li class="page-item disabled"><span class="page-link" aria-label="Anterior"><span aria-hidden="true">&laquo;</span></span></li>';
        endif;

        // Lógica de paginação (Números de página)
        $num_links_nav = 2; // Quantos links antes e depois da página atual
        $inicio_loop = max(1, $pagina_atual - $num_links_nav);
        $fim_loop = min($total_paginas, $pagina_atual + $num_links_nav);

        if ($inicio_loop > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        for ($i = $inicio_loop; $i <= $fim_loop; $i++):
            echo '<li class="page-item ' . ($i == $pagina_atual ? 'active' : '') . '"><a class="page-link" href="' . $link_base_pag_veiculos . 'pagina=' . $i . '">' . $i . '</a></li>';
        endfor;
        if ($fim_loop < $total_paginas) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';

        // Lógica de paginação (Próxima, Última)
        if ($pagina_atual < $total_paginas):
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_veiculos . 'pagina=' . ($pagina_atual + 1) . '" aria-label="Próxima"><span aria-hidden="true">&raquo;</span></a></li>';
            echo '<li class="page-item"><a class="page-link" href="' . $link_base_pag_veiculos . 'pagina=' . $total_paginas . '">Última</a></li>';
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