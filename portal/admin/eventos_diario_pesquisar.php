<?php
// admin/eventos_diario_pesquisar.php
// Página para pesquisar uma Tabela antes de gerenciar seus eventos do Diário de Bordo.

require_once 'auth_check.php';

// Permissões para acessar esta funcionalidade de pesquisa
$niveis_permitidos_pesquisar_eventos = ['CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_pesquisar_eventos)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para acessar o gerenciamento de eventos do diário de bordo.";
    header('Location: tabelas_hub.php'); // Volta para o Hub de Tabelas/Diários
    exit;
}

require_once '../db_config.php';
$page_title = 'Pesquisar Tabela para Diário de Bordo';
require_once 'admin_header.php';

// Variáveis para os filtros e resultados da pesquisa
$resultados_pesquisa_blocos = [];
// O campo de busca para "Tabela" pode ser alfanumérico
$termo_pesquisa_tabela_work_id = isset($_GET['busca_tabela_work_id']) ? trim($_GET['busca_tabela_work_id']) : '';
$filtro_pesquisa_dia_tipo = isset($_GET['busca_dia_tipo']) ? trim($_GET['busca_dia_tipo']) : '';

$tipos_dia_semana_map_pesquisa = [
    'Uteis' => 'Dias Úteis',
    'Sabado' => 'Sábado',
    'DomingoFeriado' => 'Domingo/Feriado'
];

$pesquisa_realizada = false;
// A pesquisa é executada se o formulário foi submetido (botão 'pesquisar_bloco_submit' existe)
// OU se algum dos parâmetros de filtro está presente na URL (útil para links diretos ou refresh)
if (isset($_GET['pesquisar_bloco_submit']) || !empty($termo_pesquisa_tabela_work_id) || !empty($filtro_pesquisa_dia_tipo)) {
    $pesquisa_realizada = true; // Indica que uma tentativa de pesquisa foi feita

    if ($pdo) {
        try {
            $sql_pesquisa_blocos = "SELECT id, work_id, dia_semana_tipo, data FROM programacao_diaria";
            $sql_where_pesquisa = [];
            $params_pesquisa = [];

            if (!empty($termo_pesquisa_tabela_work_id)) {
                $sql_where_pesquisa[] = "work_id LIKE :work_id_pesq";
                $params_pesquisa[':work_id_pesq'] = '%' . $termo_pesquisa_tabela_work_id . '%';
            }
            if (!empty($filtro_pesquisa_dia_tipo)) {
                $sql_where_pesquisa[] = "dia_semana_tipo = :dia_tipo_pesq";
                $params_pesquisa[':dia_tipo_pesq'] = $filtro_pesquisa_dia_tipo;
            }

            // Só executa a query se houver pelo menos um critério de filtro
            if (!empty($sql_where_pesquisa)) {
                $sql_pesquisa_blocos .= " WHERE " . implode(" AND ", $sql_where_pesquisa);
                $sql_pesquisa_blocos .= " ORDER BY FIELD(dia_semana_tipo, 'Uteis', 'Sabado', 'DomingoFeriado'), work_id ASC LIMIT 50"; 

                $stmt_pesquisa = $pdo->prepare($sql_pesquisa_blocos);
                $stmt_pesquisa->execute($params_pesquisa);
                $resultados_pesquisa_blocos = $stmt_pesquisa->fetchAll(PDO::FETCH_ASSOC);
            } elseif (isset($_GET['pesquisar_bloco_submit'])) {
                // Se o botão foi clicado mas nenhum filtro foi preenchido, não retorna erro,
                // mas $resultados_pesquisa_blocos permanecerá vazio e a mensagem de "nenhum critério" será mostrada.
                 $_SESSION['admin_warning_message'] = "Por favor, forneça um critério de pesquisa (Linha da Tabela ou Tipo de Dia).";
            }


        } catch (PDOException $e) {
            $_SESSION['admin_error_message'] = "Erro ao pesquisar Tabelas: " . $e->getMessage();
        }
    } else {
        $_SESSION['admin_error_message'] = "Falha na conexão com o banco de dados.";
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <a href="tabelas_hub.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Voltar ao Hub de Tabelas/Diários
    </a>
</div>

<?php
// Exibição de mensagens de feedback
if (isset($_SESSION['admin_success_message'])) { echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_success_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_success_message']); }
if (isset($_SESSION['admin_error_message'])) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_error_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_error_message']); }
if (isset($_SESSION['admin_warning_message'])) { echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_warning_message']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>'; unset($_SESSION['admin_warning_message']); }
?>

<p>Para gerenciar os eventos de um Diário de Bordo, primeiro localize a Tabela desejada utilizando os filtros abaixo.</p>

<form method="GET" action="eventos_diario_pesquisar.php" class="mb-4 card card-body bg-light p-3 shadow-sm">
    <div class="form-row align-items-end">
        <div class="col-md-5 form-group mb-md-0">
            <label for="busca_tabela_work_id_pesq">Pesquisar Tabela:</label>
            <input type="text" name="busca_tabela_work_id" id="busca_tabela_work_id_pesq" class="form-control form-control-sm" placeholder="Digite a linha da tabela..." value="<?php echo htmlspecialchars($termo_pesquisa_tabela_work_id); ?>">
        </div>
        <div class="col-md-3 form-group mb-md-0">
            <label for="busca_dia_tipo_pesq">Filtrar por Tipo de Dia:</label>
            <select name="busca_dia_tipo" id="busca_dia_tipo_pesq" class="form-control form-control-sm">
                <option value="">Todos os Tipos</option>
                <?php foreach ($tipos_dia_semana_map_pesquisa as $key_dia_pesq => $val_dia_pesq): ?>
                    <option value="<?php echo $key_dia_pesq; ?>" <?php echo ($filtro_pesquisa_dia_tipo === $key_dia_pesq) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($val_dia_pesq); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 form-group mb-md-0 align-self-end">
            <button type="submit" name="pesquisar_bloco_submit" value="1" class="btn btn-sm btn-primary btn-block"><i class="fas fa-search"></i> Pesquisar Tabela</button>
        </div>
        <?php if ($pesquisa_realizada || !empty($termo_pesquisa_tabela_work_id) || !empty($filtro_pesquisa_dia_tipo)): ?>
        <div class="col-md-2 form-group mb-md-0 align-self-end">
            <a href="eventos_diario_pesquisar.php" class="btn btn-sm btn-outline-secondary btn-block"><i class="fas fa-times"></i> Limpar Pesquisa</a>
        </div>
        <?php endif; ?>
    </div>
</form>

<?php if ($pesquisa_realizada): ?>
    <h3 class="mt-4 mb-3">Resultados da Pesquisa:</h3>
    <?php if (!empty($resultados_pesquisa_blocos)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-sm table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Tabela</th>
                        <th>Tipo de Dia</th>
                        <th>Data de Atualização</th>
                        <th style="width: 180px;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados_pesquisa_blocos as $bloco_pesq): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($bloco_pesq['work_id']); ?></td>
                            <td><?php echo htmlspecialchars($tipos_dia_semana_map_pesquisa[$bloco_pesq['dia_semana_tipo']] ?? $bloco_pesq['dia_semana_tipo']); ?></td>
                            <td><?php echo ($bloco_pesq['data'] && $bloco_pesq['data'] != '0000-00-00' ? date('d/m/Y', strtotime($bloco_pesq['data'])) : 'Modelo Genérico'); ?></td>
                            <td>
                                <a href="eventos_diario_gerenciar.php?programacao_id=<?php echo $bloco_pesq['id']; ?>&nome_bloco=<?php echo urlencode($bloco_pesq['work_id']); ?>&dia_tipo=<?php echo urlencode($bloco_pesq['dia_semana_tipo']); ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-tasks"></i> Gerenciar Eventos
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif (isset($_GET['pesquisar_bloco_submit'])): // Se a pesquisa foi submetida, mas não houve resultados
        echo '<p class="text-info mt-3">Nenhuma Tabela encontrada com os critérios da pesquisa.</p>';
    ?>
    <?php endif; ?>
<?php endif; ?>


<?php
require_once 'admin_footer.php';
?>