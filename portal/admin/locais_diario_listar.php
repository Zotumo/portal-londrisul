<?php
// admin/locais_diario_listar.php
// ATUALIZADO: Com campo 'mostrar_ponto' (Galeria)

require_once 'auth_check.php';
require_once '../db_config.php';

$niveis_permitidos = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos)) {
    $_SESSION['admin_error_message'] = "Acesso negado.";
    header('Location: locais_hub.php');
    exit;
}

$page_title = 'Locais do Diário de Bordo';
require_once 'admin_header.php';

$busca = $_GET['busca'] ?? '';
$filtro_status = $_GET['filtro_status'] ?? '';
$itens_pag = 20;
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * $itens_pag;

// Inicializa variáveis
$locais = [];
$total_paginas = 0;
$total_itens = 0;

try {
    $where = [];
    $params = [];
    
    if ($busca) {
        $where[] = "(company_code LIKE :b1 OR name LIKE :b2)";
        $params[':b1'] = "%$busca%";
        $params[':b2'] = "%$busca%";
    }
    if ($filtro_status) {
        $where[] = "status = :st";
        $params[':st'] = $filtro_status;
    }

    $where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

    // Contagem
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM relatorio.cadastros_locais $where_sql");
    foreach($params as $k => $v) $stmtCount->bindValue($k, $v);
    $stmtCount->execute();
    $total_itens = $stmtCount->fetchColumn();
    
    if ($total_itens > 0) {
        $total_paginas = ceil($total_itens / $itens_pag);
    }

    // Listagem
    $sql = "SELECT * FROM relatorio.cadastros_locais $where_sql ORDER BY name ASC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $itens_pag, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $locais = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div class='alert alert-danger m-3'>Erro ao buscar dados: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-images"></i> <?php echo htmlspecialchars($page_title); ?></h1>
    <a href="locais_hub.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Voltar</a>
</div>

<form method="GET" class="card mb-4 shadow-sm bg-light">
    <div class="card-body py-3">
        <div class="form-row">
            <div class="col-md-6">
                <input type="text" name="busca" class="form-control" placeholder="Buscar por código ou nome..." value="<?php echo htmlspecialchars($busca); ?>">
            </div>
            <div class="col-md-3">
                <select name="filtro_status" class="form-control">
                    <option value="">Todos Status</option>
                    <option value="ativo" <?php echo $filtro_status=='ativo'?'selected':''; ?>>Ativos</option>
                    <option value="inativa" <?php echo $filtro_status=='inativa'?'selected':''; ?>>Inativos</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary btn-block" type="submit"><i class="fas fa-search"></i> Buscar</button>
            </div>
        </div>
    </div>
</form>

<div class="card shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0 align-middle text-center">
            <thead class="thead-dark">
                <tr>
                    <th width="100">Imagem</th>
                    <th>Código</th>
                    <th class="text-left">Nome</th>
                    <th class="text-left">Descrição</th> 
                    <th>Coord.</th>
                    <th>Exibir Ponto</th> <th>Status</th>
                    <th width="150">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($locais)): ?>
                    <tr><td colspan="8" class="p-4 text-muted">Nenhum local encontrado.</td></tr>
                <?php else: ?>
                    <?php foreach ($locais as $l): 
                        // Lógica da Imagem
                        $nome_arquivo = $l['imagem_path'];
                        $caminho_relativo = "../img/pontos/" . $nome_arquivo;
                        
                        $caminho_servidor = dirname(__DIR__) . "/img/pontos/" . $nome_arquivo;
                        
                        $tem_imagem = !empty($nome_arquivo) && file_exists($caminho_servidor);
                        $img_src = $tem_imagem ? $caminho_relativo : ""; 
                        
                        $status_cor = ($l['status'] == 'ativo') ? 'success' : 'secondary';
                        
                        // Lógica Mostrar Ponto (Padrão é 1 se for null)
                        $mostrar = $l['mostrar_ponto'] ?? 1;

                        // JSON para o botão editar
                        $dados_json = htmlspecialchars(json_encode($l), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr>
                        <td class="align-middle">
                            <?php if($tem_imagem): ?>
                                <img src="<?php echo $img_src; ?>" class="img-thumbnail zoomable" 
                                     style="height: 60px; max-width: 80px; object-fit: cover; cursor: pointer;" 
                                     onclick="verImagem('<?php echo $img_src; ?>')"
                                     title="Clique para ampliar">
                            <?php else: ?>
                                <span class="text-muted small"><i class="fas fa-image fa-2x opacity-25"></i><br>Sem foto</span>
                            <?php endif; ?>
                        </td>
                        <td class="align-middle font-weight-bold"><?php echo htmlspecialchars($l['company_code']); ?></td>
                        <td class="align-middle text-left font-weight-bold"><?php echo htmlspecialchars($l['name']); ?></td>
                        
                        <td class="align-middle text-left small text-muted">
                            <?php echo htmlspecialchars(mb_strimwidth($l['descricao'] ?? '', 0, 40, '...')); ?>
                        </td>

                        <td class="align-middle">
                            <?php if(!empty($l['coordenadas'])): ?>
                                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $l['coordenadas']; ?>" target="_blank" class="text-primary" title="Ver no Mapa">
                                    <i class="fas fa-map-marker-alt fa-lg"></i>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>

                        <td class="align-middle">
                            <?php if($mostrar == 1): ?>
                                <span class="text-success" title="Visível na Galeria"><i class="fas fa-eye"></i> Sim</span>
                            <?php else: ?>
                                <span class="text-muted" title="Oculto da Galeria"><i class="fas fa-eye-slash"></i> Não</span>
                            <?php endif; ?>
                        </td>

                        <td class="align-middle">
                            <span class="badge badge-<?php echo $status_cor; ?>"><?php echo ucfirst($l['status']); ?></span>
                        </td>
                        <td class="align-middle">
                            <button class="btn btn-sm btn-primary btn-editar" data-dados='<?php echo $dados_json; ?>' title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-<?php echo ($l['status']=='ativo'?'outline-danger':'outline-success'); ?> btn-toggle"
                                    data-codigo="<?php echo $l['company_code']; ?>"
                                    data-status="<?php echo $l['status']; ?>" title="Ativar/Desativar">
                                <i class="fas fa-power-off"></i>
                            </button>
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
                <a class="page-link" href="?pagina=<?php echo $i; ?>&busca=<?php echo $busca; ?>&filtro_status=<?php echo $filtro_status; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>
    </ul></nav>
<?php endif; ?>

<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditar" enctype="multipart/form-data">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Editar Local</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">×</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="acao" value="salvar_local">
                    <input type="hidden" name="codigo" id="editCodigo">
                    
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Código</label>
                            <input type="text" class="form-control" id="showCodigo" readonly disabled style="background-color: #e9ecef;">
                        </div>
                        <div class="form-group col-md-8">
                            <label>Status</label>
                            <select name="status" id="editStatus" class="form-control">
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group p-2 border rounded bg-light">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="editMostrarPonto" name="mostrar_ponto" value="1">
                            <label class="custom-control-label font-weight-bold text-dark" for="editMostrarPonto">Exibir na Aba "Pontos"?</label>
                        </div>
                        <small class="form-text text-muted mt-1">Se desmarcado, a imagem do ponto não aparecerá.</small>
                    </div>

                    <div class="form-group">
                        <label>Nome de Exibição</label>
                        <input type="text" name="nome" id="editNome" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Coordenadas (Lat, Long)</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span></div>
                            <input type="text" name="coordenadas" id="editCoords" class="form-control" placeholder="-23.1234, -51.1234">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Descrição</label>
                        <textarea name="descricao" id="editDescricao" class="form-control" rows="2" placeholder="Ex: Ponto de início, apenas desembarque..."></textarea>
                    </div>

                    <div class="form-group border-top pt-3">
                        <label class="font-weight-bold">Imagem do Ponto</label>
                        
                        <div id="previewContainer" class="mb-3 text-center p-2 border rounded bg-light" style="display:none;">
                            <p class="small text-success mb-1"><i class="fas fa-check-circle"></i> Imagem Atual:</p>
                            <img id="previewImagem" src="" class="img-fluid rounded shadow-sm" style="max-height: 150px;">
                            <p class="small text-muted mt-1">Para trocar, selecione um novo arquivo abaixo.</p>
                        </div>

                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="uploadImagem" name="imagem_arquivo" accept="image/*">
                            <label class="custom-file-label" for="uploadImagem">Escolher nova imagem...</label>
                        </div>
                        <small class="text-muted">Formatos: JPG, PNG. Máx 2MB.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalZoom" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body text-center"><img id="imgZoom" src="" class="img-fluid rounded shadow-lg"></div>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
$(document).ready(function() {
    
    // Abrir Modal de Edição
    $('.btn-editar').click(function() {
        let d = $(this).data('dados'); // Pega o JSON do botão
        
        // Preenche campos
        $('#editCodigo').val(d.company_code);
        $('#showCodigo').val(d.company_code);
        $('#editNome').val(d.name);
        $('#editDescricao').val(d.descricao);
        $('#editCoords').val(d.coordenadas);
        $('#editStatus').val(d.status);
        
        // --- LÓGICA DO CHECKBOX (MOSTRAR PONTO) ---
        // Se d.mostrar_ponto for null ou undefined, assume 1 (true). Se for '0', vira false.
        let deveMostrar = (d.mostrar_ponto === null || d.mostrar_ponto == 1);
        $('#editMostrarPonto').prop('checked', deveMostrar);

        // Reset do Input de Arquivo
        $('#uploadImagem').val('').next('.custom-file-label').html('Escolher nova imagem...');

        // Lógica do Preview no Modal
        if (d.imagem_path) {
            let caminhoImg = '../img/pontos/' + d.imagem_path;
            $('#previewImagem').attr('src', caminhoImg);
            $('#previewContainer').show();
        } else {
            $('#previewContainer').hide();
        }

        $('#modalEditar').modal('show');
    });

    // Botão Ativar/Desativar
    $('.btn-toggle').click(function() {
        let cod = $(this).data('codigo');
        let novo = $(this).data('status') === 'ativo' ? 'inativo' : 'ativo';
        if(confirm('Tem certeza que deseja alterar o status?')) {
            $.post('ajax_locais.php', {acao: 'toggle_status', codigo: cod, novo_status: novo}, function(r){
                if(r.sucesso) location.reload(); else alert(r.msg);
            }, 'json');
        }
    });

    // Nome do arquivo no input
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });

    // Salvar
    $('#formEditar').submit(function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        $.ajax({
            url: 'ajax_locais.php', type: 'POST', data: formData,
            contentType: false, processData: false, dataType: 'json',
            success: function(r) { 
                if(r.sucesso){ 
                    alert(r.msg); 
                    location.reload(); 
                } else { 
                    alert('Erro: ' + r.msg); 
                } 
            },
            error: function() { alert('Erro de conexão com o servidor.'); }
        });
    });

    // Zoom da Imagem na Tabela
    window.verImagem = function(src) { 
        $('#imgZoom').attr('src', src); 
        $('#modalZoom').modal('show'); 
    }
});
</script>
<?php 
$page_specific_js = ob_get_clean();
require_once 'admin_footer.php';
?>