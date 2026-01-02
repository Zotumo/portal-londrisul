<?php
// admin/noticia_formulario.php

// Passo 1: Autenticação e verificação de sessão/permissões.
require_once 'auth_check.php'; // Define $admin_nivel_acesso_logado

// Passo 2: Verificar permissão específica para ACESSAR/USAR o formulário de notícias
$niveis_permitidos_formulario_noticias = ['Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_formulario_noticias)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para criar ou editar notícias.";
    header('Location: noticias_listar.php'); // Redireciona para a lista se não tiver permissão
    exit;
}

// Passo 3: Conexão com o banco de dados
require_once '../db_config.php';

// Passo 4: Lógica para buscar dados da notícia em modo de edição
$noticia_id = null;
$titulo_noticia = ''; // Renomeado para evitar conflito com $page_title
$resumo_noticia = '';
$conteudo_completo_noticia = '';
$status_noticia = 'rascunho'; // Padrão para nova notícia
$imagem_destaque_atual = '';
$data_publicacao_db_formatada = ''; // Para o campo de data/hora
$modo_edicao = false;
$page_title_action = 'Adicionar Nova Notícia'; // Título da ação padrão

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $noticia_id = (int)$_GET['id'];
    $modo_edicao = true;
    $page_title_action = 'Editar Notícia';

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM noticias WHERE id = :id");
            $stmt->bindParam(':id', $noticia_id, PDO::PARAM_INT);
            $stmt->execute();
            $noticia_db_data = $stmt->fetch(PDO::FETCH_ASSOC); // Renomeado para evitar conflito

            if ($noticia_db_data) {
                $titulo_noticia = $noticia_db_data['titulo'];
                $resumo_noticia = $noticia_db_data['resumo'];
                $conteudo_completo_noticia = $noticia_db_data['conteudo_completo'];
                $status_noticia = $noticia_db_data['status'];
                $imagem_destaque_atual = $noticia_db_data['imagem_destaque'];
                $data_publicacao_db_formatada = $noticia_db_data['data_publicacao'] ? date('Y-m-d\TH:i', strtotime($noticia_db_data['data_publicacao'])) : '';
            } else {
                $_SESSION['admin_error_message'] = "Notícia ID {$noticia_id} não encontrada para edição.";
                header('Location: noticias_listar.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log("Erro ao buscar notícia para edição (ID: {$noticia_id}): " . $e->getMessage());
            $_SESSION['admin_error_message'] = "Erro ao carregar dados da notícia para edição.";
            header('Location: noticias_listar.php');
            exit;
        }
    } else {
        $_SESSION['admin_error_message'] = "Falha na conexão com o banco ao tentar carregar notícia para edição.";
        header('Location: noticias_listar.php');
        exit;
    }
}

// Passo 5: Definir o título completo da página (usado no <title> e <h1>).
$page_title = $page_title_action;

// Passo 6: Incluir o cabeçalho comum do admin.
require_once 'admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title_action); // Usa o título da ação para o H1 ?></h1>
    <a href="noticias_listar.php" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Voltar para Lista de Notícias
    </a>
</div>

<?php
// Exibir mensagens de erro da sessão (se houver, de uma tentativa anterior de salvar que falhou e redirecionou de volta)
if (isset($_SESSION['admin_form_error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['admin_form_error']) . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
    unset($_SESSION['admin_form_error']);
}
?>

<form action="noticia_processa.php" method="POST" enctype="multipart/form-data" id="form-noticia">
    <?php if ($modo_edicao && $noticia_id): ?>
        <input type="hidden" name="noticia_id" value="<?php echo $noticia_id; ?>">
    <?php endif; ?>

    <div class="form-row">
        <div class="form-group col-md-8">
            <label for="titulo">Título da Notícia <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($titulo_noticia); ?>" required maxlength="255">
        </div>
        <div class="form-group col-md-4">
            <label for="status">Status <span class="text-danger">*</span></label>
            <select class="form-control" id="status" name="status" required>
                <option value="rascunho" <?php echo ($status_noticia == 'rascunho') ? 'selected' : ''; ?>>Rascunho</option>
                <option value="publicada" <?php echo ($status_noticia == 'publicada') ? 'selected' : ''; ?>>Publicada</option>
                <option value="arquivada" <?php echo ($status_noticia == 'arquivada') ? 'selected' : ''; ?>>Arquivada</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label for="resumo">Resumo (visível na listagem principal do portal, máx. ~300 caracteres)</label>
        <textarea class="form-control" id="resumo" name="resumo" rows="3" maxlength="500"><?php echo htmlspecialchars($resumo_noticia); ?></textarea>
    </div>

    <div class="form-group">
        <label for="conteudo_completo">Conteúdo Completo <span class="text-danger">*</span></label>
        <textarea class="form-control" id="conteudo_completo" name="conteudo_completo" rows="10" required><?php echo htmlspecialchars($conteudo_completo_noticia); ?></textarea>
        <small class="form-text text-muted">Você pode usar HTML básico aqui (ex: &lt;strong&gt;, &lt;em&gt;, &lt;br&gt;, &lt;p&gt;, &lt;ul&gt;&lt;li&gt;).</small>
    </div>

    <div class="form-row">
        <div class="form-group col-md-6">
            <label for="imagem_destaque">Imagem de Destaque (Opcional)</label>
            <input type="file" class="form-control-file" id="imagem_destaque" name="imagem_destaque" accept="image/jpeg, image/png, image/gif">
            <?php if ($modo_edicao && !empty($imagem_destaque_atual)): ?>
                <div class="mt-2">
                    <p class="mb-1"><small>Imagem atual: <?php echo htmlspecialchars($imagem_destaque_atual); ?></small></p>
                    <img src="../img/noticias/<?php echo htmlspecialchars($imagem_destaque_atual); ?>" alt="Imagem atual da notícia: <?php echo htmlspecialchars($titulo_noticia); ?>" style="max-width: 200px; max-height: 100px; margin-bottom: 5px; border: 1px solid #ddd; padding: 2px;">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remover_imagem_atual" id="remover_imagem_atual" value="1">
                        <label class="form-check-label" for="remover_imagem_atual"><small>Remover imagem atual ao salvar (se nenhuma nova for enviada)</small></label>
                    </div>
                </div>
            <?php endif; ?>
            <small class="form-text text-muted">Formatos: JPG, PNG, GIF. Tamanho máx: 2MB (exemplo).</small>
        </div>
        <div class="form-group col-md-6">
            <label for="data_publicacao">Data de Publicação (Opcional)</label>
            <input type="datetime-local" class="form-control" id="data_publicacao" name="data_publicacao" value="<?php echo $data_publicacao_db_formatada; ?>">
            <small class="form-text text-muted">Se "Publicada" e vazio, usa data atual. Se "Rascunho", fica sem data até publicar.</small>
        </div>
    </div>

    <hr>
    <button type="submit" name="salvar_noticia" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Notícia</button>
    <a href="noticias_listar.php" class="btn btn-secondary">Cancelar</a>
</form>
<?php
// Passo 7: Incluir o rodapé comum do admin.
require_once 'admin_footer.php';
?>