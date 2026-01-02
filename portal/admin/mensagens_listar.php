<?php
// admin/mensagens_listar.php

require_once 'auth_check.php';
$niveis_permitidos_ver_mensagens = ['Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_ver_mensagens)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para acessar a área de gerenciamento de mensagens.";
    header('Location: index.php');
    exit;
}
require_once '../db_config.php';
$page_title = 'Gerenciar Mensagens';
require_once 'admin_header.php';

// Configurações da Paginação
$mensagens_por_pagina = 15;
$pagina_atual = isset($_GET['pagina']) && filter_var($_GET['pagina'], FILTER_VALIDATE_INT) && $_GET['pagina'] > 0 ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $mensagens_por_pagina;

// Inicializar variáveis
$mensagens = [];
$total_mensagens = 0;
$total_paginas = 0;
$erro_busca = false;
$termo_busca_motorista = isset($_GET['busca_motorista']) ? trim($_GET['busca_motorista']) : '';

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if (in_array($admin_nivel_acesso_logado, $niveis_permitidos_ver_mensagens)): ?>
        <a href="mensagem_formulario.php" class="btn btn-success">
            <i class="fas fa-paper-plane"></i> Enviar Nova Mensagem
        </a>
        <?php endif; ?>
    </div>
</div>

<?php
// Feedback (mantido como estava)
if (isset($_SESSION['admin_success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_success_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
    unset($_SESSION['admin_success_message']);
}
if (isset($_SESSION['admin_error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_error_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
    unset($_SESSION['admin_error_message']);
}
?>

<form method="GET" action="mensagens_listar.php" class="mb-3">
    <div class="form-row align-items-end">
        <div class="col-md-4 form-group">
            <label for="busca_motorista_input" class="sr-only">Buscar por motorista</label>
            <input type="text" name="busca_motorista" id="busca_motorista_input" class="form-control form-control-sm" placeholder="Buscar por nome ou matrícula do motorista..." value="<?php echo htmlspecialchars($termo_busca_motorista); ?>">
        </div>
        <div class="col-md-2 form-group">
            <button type="submit" class="btn btn-sm btn-primary btn-block"><i class="fas fa-search"></i> Filtrar</button>
        </div>
        <?php if (!empty($termo_busca_motorista)): ?>
        <div class="col-md-2 form-group">
            <a href="mensagens_listar.php" class="btn btn-sm btn-outline-secondary btn-block"><i class="fas fa-times"></i> Limpar Filtro</a>
        </div>
        <?php endif; ?>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-sm table-hover">
        <thead class="thead-light">
            <tr>
                <th>Matrícula</th> <th>Destinatário (Nome)</th>
                <th>Assunto</th>
                <th>Remetente</th>
                <th>Data Envio</th>
                <th>Status Leitura</th>
                <th>Data Leitura</th>
                <th style="width: 100px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($pdo) {
                try {
                    // SQL para contar o total de mensagens (para paginação)
                    $sql_count = "SELECT COUNT(msg.id)
                                  FROM mensagens_motorista AS msg
                                  JOIN motoristas AS mot ON msg.motorista_id = mot.id";
                    $params_sql_count = [];

                    if (!empty($termo_busca_motorista)) {
                        $sql_count .= " WHERE mot.nome LIKE :busca_motorista OR mot.matricula LIKE :busca_motorista_mat";
                        $params_sql_count[':busca_motorista'] = '%' . $termo_busca_motorista . '%';
                        $params_sql_count[':busca_motorista_mat'] = '%' . $termo_busca_motorista . '%';
                    }
                    $stmt_count = $pdo->prepare($sql_count);
                    $stmt_count->execute($params_sql_count);
                    $total_mensagens = (int)$stmt_count->fetchColumn();
                    $total_paginas = ceil($total_mensagens / $mensagens_por_pagina);
                    if ($pagina_atual > $total_paginas && $total_paginas > 0) $pagina_atual = $total_paginas;
                    if ($pagina_atual < 1) $pagina_atual = 1;
                    $offset = ($pagina_atual - 1) * $mensagens_por_pagina;


                    // SQL para buscar as mensagens da página atual
                    $sql_select = "SELECT msg.id AS id_mensagem, msg.assunto, msg.remetente, msg.data_envio, msg.data_leitura,
                                          mot.nome as nome_motorista, mot.matricula as matricula_motorista
                                   FROM mensagens_motorista AS msg
                                   JOIN motoristas AS mot ON msg.motorista_id = mot.id"; // id_mensagem é o ID da tabela mensagens_motorista
                    $params_sql_select = [];

                    if (!empty($termo_busca_motorista)) {
                        $sql_select .= " WHERE mot.nome LIKE :busca_motorista OR mot.matricula LIKE :busca_motorista_mat";
                        $params_sql_select[':busca_motorista'] = '%' . $termo_busca_motorista . '%';
                        $params_sql_select[':busca_motorista_mat'] = '%' . $termo_busca_motorista . '%';
                    }

                    $sql_select .= " ORDER BY msg.data_envio DESC LIMIT :limit OFFSET :offset";
                    $stmt_select = $pdo->prepare($sql_select);

                    foreach ($params_sql_select as $key => $value) {
                        $stmt_select->bindValue($key, $value);
                    }
                    $stmt_select->bindValue(':limit', $mensagens_por_pagina, PDO::PARAM_INT);
                    $stmt_select->bindValue(':offset', $offset, PDO::PARAM_INT);
                    $stmt_select->execute();
                    $mensagens = $stmt_select->fetchAll(PDO::FETCH_ASSOC);

                    if ($mensagens) {
                        foreach ($mensagens as $msg) {
                            echo "<tr>";
                            // ALTERADO: Mostrar matrícula do motorista primeiro
                            echo "<td>" . htmlspecialchars($msg['matricula_motorista']) . "</td>";
                            echo "<td>" . htmlspecialchars($msg['nome_motorista']) . "</td>";
                            echo "<td>" . htmlspecialchars($msg['assunto'] ?: '(Sem assunto)') . "</td>";
                            echo "<td>" . htmlspecialchars($msg['remetente']) . "</td>";
                            echo "<td>" . date('d/m/Y H:i', strtotime($msg['data_envio'])) . "</td>";
                            if ($msg['data_leitura']) {
                                echo "<td class='text-success'><i class='fas fa-check-circle'></i> Lida</td>";
                                echo "<td>" . date('d/m/Y H:i', strtotime($msg['data_leitura'])) . "</td>";
                            } else {
                                echo "<td class='text-warning'><i class='fas fa-envelope'></i> Não Lida</td>";
                                echo "<td>-</td>";
                            }
                            echo "<td class='action-buttons'>";
                            echo "<a href='mensagem_visualizar.php?id=" . $msg['id_mensagem'] . "&pagina=" . $pagina_atual . "&busca_motorista=" . urlencode($termo_busca_motorista) . "' class='btn btn-info btn-sm' title='Visualizar Mensagem Completa'><i class='fas fa-eye'></i></a> "; // ID da msg no title
                            $niveis_permitidos_apagar_msg = ['Supervisores', 'Gerência', 'Administrador']; // Ajuste conforme sua regra
                            if (in_array($admin_nivel_acesso_logado, $niveis_permitidos_apagar_msg)) {
                                // O link de apagar ainda usa o ID da mensagem (id_mensagem)
                                echo "<a href='mensagem_apagar.php?id=" . $msg['id_mensagem'] . "&pagina=" . $pagina_atual . "&busca_motorista=" . urlencode($termo_busca_motorista) . "' class='btn btn-danger btn-sm' title='Apagar Mensagem' onclick='return confirm(\"Tem certeza que deseja apagar esta mensagem (ID Msg: " . $msg['id_mensagem'] . ") enviada para " . htmlspecialchars(addslashes($msg['nome_motorista'])) . "?\");'><i class='fas fa-trash-alt'></i></a>";
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        $msg_vazio = "Nenhuma mensagem encontrada";
                        if (!empty($termo_busca_motorista)) {
                            $msg_vazio .= " para o motorista/matrícula: '" . htmlspecialchars($termo_busca_motorista) . "'";
                        }
                        echo "<tr><td colspan='8' class='text-center'>{$msg_vazio}.</td></tr>"; // Colspan continua 8
                    }
                } catch (PDOException $e) {
                    $erro_msg_exibicao = "Erro ao buscar mensagens";
                    if (!empty($termo_busca_motorista)) {
                        $erro_msg_exibicao .= " para '" . htmlspecialchars($termo_busca_motorista) . "'";
                    }
                    echo "<tr><td colspan='8' class='text-danger text-center'>{$erro_msg_exibicao}: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                    $erro_busca = true;
                }
            } else {
                 echo "<tr><td colspan='8' class='text-danger text-center'>Falha na conexão com o banco de dados.</td></tr>";
                 $erro_busca = true;
            }
            ?>
        </tbody>
    </table>
</div>

<?php if (!$erro_busca && $total_paginas > 1): ?>
<nav aria-label="Navegação das mensagens">
    <ul class="pagination justify-content-center mt-4">
        <?php
        // Lógica da paginação (mantida como estava)
        if ($pagina_atual > 1) {
            echo '<li class="page-item"><a class="page-link" href="?pagina=1&busca_motorista=' . urlencode($termo_busca_motorista) . '">Primeira</a></li>';
            echo '<li class="page-item"><a class="page-link" href="?pagina=' . ($pagina_atual - 1) . '&busca_motorista=' . urlencode($termo_busca_motorista) . '" aria-label="Anterior"><span aria-hidden="true">&laquo;</span><span class="sr-only">Anterior</span></a></li>';
        } else {
            echo '<li class="page-item disabled"><span class="page-link">Primeira</span></li>';
            echo '<li class="page-item disabled"><span class="page-link" aria-label="Anterior"><span aria-hidden="true">&laquo;</span><span class="sr-only">Anterior</span></span></li>';
        }
        $num_links_paginacao = 3;
        $inicio_loop = max(1, $pagina_atual - $num_links_paginacao);
        $fim_loop = min($total_paginas, $pagina_atual + $num_links_paginacao);
        if ($inicio_loop > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        for ($i = $inicio_loop; $i <= $fim_loop; $i++) {
            echo '<li class="page-item ' . ($i == $pagina_atual ? 'active' : '') . '"><a class="page-link" href="?pagina=' . $i . '&busca_motorista=' . urlencode($termo_busca_motorista) . '">' . $i . '</a></li>';
        }
        if ($fim_loop < $total_paginas) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        if ($pagina_atual < $total_paginas) {
            echo '<li class="page-item"><a class="page-link" href="?pagina=' . ($pagina_atual + 1) . '&busca_motorista=' . urlencode($termo_busca_motorista) . '" aria-label="Próxima"><span aria-hidden="true">&raquo;</span><span class="sr-only">Próxima</span></a></li>';
            echo '<li class="page-item"><a class="page-link" href="?pagina=' . $total_paginas . '&busca_motorista=' . urlencode($termo_busca_motorista) . '">Última</a></li>';
        } else {
            echo '<li class="page-item disabled"><span class="page-link" aria-label="Próxima"><span aria-hidden="true">&raquo;</span><span class="sr-only">Próxima</span></span></li>';
            echo '<li class="page-item disabled"><span class="page-link">Última</span></li>';
        }
        ?>
    </ul>
</nav>
<?php endif; ?>

<?php
require_once 'admin_footer.php';
?>