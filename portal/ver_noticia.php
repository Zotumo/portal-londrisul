<?php
// ver_noticia.php (Estrutura Correta com Header Centralizado)

// A inicialização (session, db, login check) será feita pelo header.php
// Mas precisamos buscar a notícia ANTES de incluir o header para definir o título da página.

// 1. Buscar dados da notícia (precisa do ID e talvez do $pdo se o header falhar?)
// Vamos incluir db_config aqui temporariamente só para buscar o título antes do header.
// Se header.php garantir $pdo, esta inclusão pode sair depois.
require_once 'db_config.php'; // Inclui para buscar o título

$noticia_id = null;
$noticia_encontrada = false;
$noticia_titulo = null; // Apenas para o título
$noticia_completa = null; // Para o conteúdo principal
$erro_busca = false;
$page_title = 'Notícia não encontrada'; // Título padrão

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT) && $_GET['id'] > 0) {
    $noticia_id = (int)$_GET['id'];
    if (isset($pdo)) { // Usa o $pdo do db_config incluído acima
        try {
            $sql = "SELECT titulo, conteudo_completo, data_publicacao FROM noticias WHERE id = :id AND status = 'publicada' LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $noticia_id, PDO::PARAM_INT);
            $stmt->execute();
            $noticia_completa = $stmt->fetch(PDO::FETCH_ASSOC); // Guarda a notícia toda
            if ($noticia_completa) {
                $noticia_encontrada = true;
                $page_title = $noticia_completa['titulo']; // Define título específico
            }
        } catch (PDOException $e) {
            error_log("Erro ao buscar notícia específica (ID: $noticia_id): " . $e->getMessage());
            $erro_busca = true;
        }
    } else { $erro_busca = true; } // Erro se $pdo não foi definido por db_config
}

// 2. Inclui o Header (que fará session_start, definirá $usuario_logado, $nome_usuario, etc.)
// Ele também re-incluirá db_config.php, mas require_once evita problemas.
require_once 'header.php';
?>

    <main class="container mt-4">
        <?php if ($noticia_encontrada): ?>
            <article>
                <header class="mb-4">
                    <h1><?php echo htmlspecialchars($noticia_completa['titulo']); ?></h1>
                    <p class="text-muted">
                        Publicado em: <?php
                            $data = new DateTime($noticia_completa['data_publicacao']);
                            echo $data->format('d/m/Y \à\s H:i');
                        ?>
                    </p>
                </header>
                <hr>
                <div class="conteudo-noticia">
                    <?php echo nl2br(htmlspecialchars($noticia_completa['conteudo_completo'])); ?>
                </div>
            </article>
            <hr>
            <a href="todas_noticias.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left"></i> Voltar para Notícias</a>

        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                <h4 class="alert-heading">Notícia não encontrada</h4>
                <p>
                    <?php
                        // A variável $pdo pode ser null aqui se a conexão falhou no header
                        if ($erro_busca || $pdo === null) { echo "Ocorreu um erro ao tentar buscar a notícia. Verifique a conexão com o banco ou tente novamente mais tarde."; }
                        elseif ($noticia_id === null) { echo "O link que você acessou parece ser inválido."; }
                        else { echo "A notícia que você está procurando não foi encontrada ou não está mais disponível."; }
                    ?>
                </p>
                <hr>
                <a href="index.php" class="btn btn-primary"><i class="fas fa-home"></i> Voltar para a Página Inicial</a>
            </div>
        <?php endif; ?>
    </main>

<?php
// 3. Inclui o Rodapé
require_once 'footer.php';
?>