<?php
// admin/linhas_listar.php
// ATUALIZADO: Descrição da Rota Editável

require_once 'auth_check.php';

// --- Permissões ---
$niveis_ver = ['Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
$niveis_editar = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
$niveis_status = ['Supervisores', 'Gerência', 'Administrador'];

if (!in_array($admin_nivel_acesso_logado, $niveis_ver)) {
    $_SESSION['admin_error_message'] = "Acesso negado.";
    header('Location: index.php');
    exit;
}

require_once '../db_config.php';
$page_title = 'Gerenciar Linhas e Rotas';
require_once 'admin_header.php';

// Configurações e Filtros
$busca_numero = $_GET['busca_numero'] ?? '';
$busca_nome   = $_GET['busca_nome'] ?? '';
$filtro_status = $_GET['status_filtro'] ?? '';
$itens_pag = 20;
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * $itens_pag;

// Tipos para Checkbox
$tipos_disponiveis = [
    'Convencional Amarelo', 'Convencional Amarelo com Ar',
    'Micro', 'Micro com Ar',
    'Convencional Azul', 'Convencional Azul com Ar',
    'Padron Azul', 'SuperBus', 'Leve'
];

$linhas = [];
$total_paginas = 0;

try {
    $where = [];
    $params = [];

    if ($busca_numero) { $where[] = "v.linha LIKE :num"; $params[':num'] = "%$busca_numero%"; }
    if ($busca_nome) { $where[] = "v.nome_linha LIKE :nome"; $params[':nome'] = "%$busca_nome%"; }
    if ($filtro_status) { $where[] = "v.status_linha = :status"; $params[':status'] = $filtro_status; }

    $where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

    $stmtCount = $pdo->prepare("SELECT COUNT(DISTINCT linha) FROM relatorio.cadastros_vias v $where_sql");
    $stmtCount->execute($params);
    $total_itens = $stmtCount->fetchColumn();
    $total_paginas = ceil($total_itens / $itens_pag);

    // SQL Principal
    $sql = "SELECT 
                v.linha as numero,
                MAX(v.nome_linha) as nome,
                MAX(v.status_linha) as status_linha,
                GROUP_CONCAT(DISTINCT lv.tipo_veiculo SEPARATOR ', ') as tipos_veiculos_str
            FROM relatorio.cadastros_vias v
            LEFT JOIN relatorio.linhas_veiculos lv ON v.linha = lv.linha_numero
            $where_sql
            GROUP BY v.linha
            ORDER BY CAST(v.linha AS UNSIGNED) ASC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $itens_pag, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erro SQL: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-bus"></i> <?php echo htmlspecialchars($page_title); ?></h1>
</div>

<form method="GET" class="card mb-4 shadow-sm bg-light">
    <div class="card-body py-3">
        <div class="form-row">
            <div class="col-md-2">
                <input type="text" name="busca_numero" class="form-control" placeholder="Nº Linha" value="<?php echo htmlspecialchars($busca_numero); ?>">
            </div>
            <div class="col-md-4">
                <input type="text" name="busca_nome" class="form-control" placeholder="Nome da Linha" value="<?php echo htmlspecialchars($busca_nome); ?>">
            </div>
            <div class="col-md-2">
                <select name="status_filtro" class="form-control">
                    <option value="">Todos Status</option>
                    <option value="ativa" <?php echo $filtro_status=='ativa'?'selected':''; ?>>Ativa</option>
                    <option value="inativa" <?php echo $filtro_status=='inativa'?'selected':''; ?>>Inativa</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-filter"></i> Filtrar</button>
            </div>
            <div class="col-md-2">
                <a href="linhas_listar.php" class="btn btn-outline-secondary btn-block">Limpar</a>
            </div>
        </div>
    </div>
</form>

<div class="card shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0 text-center align-middle">
            <thead class="thead-dark">
                <tr>
                    <th>Número</th>
                    <th class="text-left">Nome da Linha</th>
                    <th>Status</th>
                    <th style="width: 250px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($linhas)): ?>
                    <tr><td colspan="4" class="p-4 text-muted">Nenhuma linha encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($linhas as $l): 
                        $status_cor = ($l['status_linha'] == 'ativa') ? 'success' : 'danger';
                        $status_texto = ucfirst($l['status_linha'] ?? 'Ativa');
                        $dados_linha = htmlspecialchars(json_encode($l), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr id="linha-<?php echo $l['numero']; ?>">
                        <td class="font-weight-bold align-middle"><?php echo htmlspecialchars($l['numero']); ?></td>
                        <td class="text-left align-middle">
                            <?php echo htmlspecialchars($l['nome'] ?? '-'); ?>
                            <?php if(!empty($l['tipos_veiculos_str'])): ?>
                                <br><small class="text-muted"><i class="fas fa-bus"></i> <?php echo htmlspecialchars($l['tipos_veiculos_str']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="align-middle">
                            <span class="badge badge-<?php echo $status_cor; ?> p-2 badge-status"><?php echo $status_texto; ?></span>
                        </td>
                        <td class="align-middle">
                            <?php if (in_array($admin_nivel_acesso_logado, $niveis_editar)): ?>
                                <button class="btn btn-primary btn-sm btn-editar" data-linha='<?php echo $dados_linha; ?>' title="Editar"><i class="fas fa-edit"></i></button>
                            <?php endif; ?>
                            <?php if (in_array($admin_nivel_acesso_logado, $niveis_status)): ?>
                                <button class="btn btn-sm btn-toggle-status <?php echo ($l['status_linha'] == 'ativa' ? 'btn-outline-danger' : 'btn-outline-success'); ?>" 
                                        data-numero="<?php echo $l['numero']; ?>" 
                                        data-status="<?php echo $l['status_linha']; ?>" title="Status"><i class="fas fa-power-off"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($total_paginas > 1): ?>
    <nav><ul class="pagination justify-content-center">
        <?php for($i=1; $i<=$total_paginas; $i++): ?>
            <li class="page-item <?php echo ($i == $pagina) ? 'active' : ''; ?>">
                <a class="page-link" href="?pagina=<?php echo $i; ?>&busca_numero=<?php echo $busca_numero; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>
    </ul></nav>
<?php endif; ?>

<div class="modal fade" id="modalEditarLinha" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Editar Linha <span id="modalTituloNumero" class="font-weight-bold"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs nav-justified mb-3" id="abasEdicao">
                    <li class="nav-item"><a class="nav-link active" id="aba-dados" data-toggle="tab" href="#content-dados">Dados Gerais</a></li>
                    <li class="nav-item"><a class="nav-link" id="aba-mapas" data-toggle="tab" href="#content-mapas">Rotas e Mapas</a></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="content-dados">
                        <form id="formDados">
                            <input type="hidden" name="acao" value="salvar_dados">
                            <div class="form-row">
                                <div class="col-md-3 form-group">
                                    <label>Número</label>
                                    <input type="text" name="numero" id="editNumero" class="form-control" readonly style="background:#e9ecef; font-weight:bold;">
                                </div>
                                <div class="col-md-9 form-group">
                                    <label>Nome da Linha</label>
                                    <input type="text" name="nome" id="editNome" class="form-control">
                                </div>
                            </div>
                            <div class="form-group border p-2 rounded">
                                <label class="font-weight-bold d-block mb-2">Tipos de Veículos Permitidos:</label>
                                <div class="row px-2">
                                    <?php foreach ($tipos_disponiveis as $tipo): ?>
                                    <div class="col-md-6 mb-1">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input checkbox-veiculo" 
                                                   id="check_<?php echo md5($tipo); ?>" name="tipos_veiculo[]" value="<?php echo htmlspecialchars($tipo); ?>">
                                            <label class="custom-control-label" for="check_<?php echo md5($tipo); ?>"><?php echo htmlspecialchars($tipo); ?></label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <?php if (in_array($admin_nivel_acesso_logado, $niveis_status)): ?>
                                <select name="status_linha" id="editStatus" class="form-control">
                                    <option value="ativa">Ativa</option>
                                    <option value="inativa">Inativa</option>
                                </select>
                                <?php else: ?>
                                    <input type="text" class="form-control" disabled value="Sem permissão">
                                <?php endif; ?>
                            </div>
                            <div class="text-right"><button type="submit" class="btn btn-success">Salvar Dados</button></div>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="content-mapas">
                        <div class="form-group">
                            <label>Selecione a Variante:</label>
                            <select id="selectVariante" class="form-control"><option>Carregando...</option></select>
                        </div>
                        <div id="boxMapa" style="display:none;" class="card bg-light border-0 p-3">
                            <form id="formMapa">
                                <input type="hidden" name="acao" value="salvar_mapa">
                                <input type="hidden" name="codigo_via" id="mapaCodigo">
                                
                                <div class="form-group">
                                    <label class="font-weight-bold">Descrição da Rota/Variante:</label>
                                    <input type="text" name="descricao" id="mapaDescricao" class="form-control" required placeholder="Ex: Via Lacor p/ Centro">
                                </div>

                                <div class="form-group">
                                    <label>Link do Iframe (My Maps):</label>
                                    <textarea name="iframe_mapa" id="mapaIframe" rows="3" class="form-control" placeholder="<iframe src='...'></iframe>"></textarea>
                                </div>
                                <div class="text-right"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Alterações</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(document).ready(function() {
    $('.btn-editar').click(function() {
        let dados = $(this).data('linha');
        $('#editNumero').val(dados.numero);
        $('#modalTituloNumero').text(dados.numero);
        $('#editNome').val(dados.nome);
        $('#editStatus').val(dados.status_linha);
        
        $('.checkbox-veiculo').prop('checked', false);
        if (dados.tipos_veiculos_str) {
            let tipos = dados.tipos_veiculos_str.split(', ');
            tipos.forEach(t => $(`input[value="${t}"]`).prop('checked', true));
        }

        $('#selectVariante').html('<option>Carregando...</option>');
        $('#boxMapa').hide();
        $('#aba-dados').tab('show');
        
        $.post('ajax_linhas.php', {acao: 'listar_vias', linha: dados.numero}, function(resp) {
            let opts = '<option value="">Selecione...</option>';
            if(resp.vias) {
                resp.vias.forEach(v => {
                    let safeIframe = v.iframe_mapa ? v.iframe_mapa.replace(/"/g, '&quot;') : '';
                    // Inclui a descrição no data-desc e no texto
                    opts += `<option value="${v.codigo}" data-desc="${v.descricao}" data-iframe="${safeIframe}">${v.codigo} - ${v.descricao}</option>`;
                });
            }
            $('#selectVariante').html(opts);
        }, 'json');

        $('#modalEditarLinha').modal('show');
    });

    $('#selectVariante').change(function() {
        let opt = $(this).find(':selected');
        if (opt.val()) {
            $('#mapaCodigo').val(opt.val());
            $('#mapaDescricao').val(opt.data('desc')); // Preenche campo editável
            $('#mapaIframe').val(opt.data('iframe'));
            $('#boxMapa').fadeIn();
        } else { $('#boxMapa').hide(); }
    });

    $('#formDados').submit(function(e) {
        e.preventDefault();
        $.post('ajax_linhas.php', $(this).serialize(), function(r) {
            alert(r.msg);
            if(r.sucesso) location.reload();
        }, 'json');
    });

    // ATUALIZADO: Salvar Mapa e Descrição
    $('#formMapa').submit(function(e) {
        e.preventDefault();
        $.post('ajax_linhas.php', $(this).serialize(), function(r) {
            alert(r.msg);
            if(r.sucesso) {
                // Atualiza o <option> sem recarregar a página
                let novoDesc = $('#mapaDescricao').val();
                let novoIframe = $('#mapaIframe').val().replace(/"/g, '&quot;');
                let codigo = $('#mapaCodigo').val();
                
                let opt = $('#selectVariante option:selected');
                opt.data('desc', novoDesc);
                opt.data('iframe', novoIframe);
                opt.text(`${codigo} - ${novoDesc}`); // Atualiza visualmente o select
            }
        }, 'json');
    });

    $('.btn-toggle-status').click(function() {
        let btn = $(this);
        let novo = btn.data('status') === 'ativa' ? 'inativa' : 'ativa';
        if(!confirm(`Mudar status para ${novo.toUpperCase()}?`)) return;
        $.post('ajax_linhas.php', {acao: 'toggle_status', numero: btn.data('numero'), novo_status: novo}, function(r) {
            if(r.sucesso) location.reload(); else alert(r.msg);
        }, 'json');
    });
});
</script>
<?php 
$page_specific_js = ob_get_clean();
require_once 'admin_footer.php';
?>