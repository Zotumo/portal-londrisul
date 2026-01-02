<?php
// todas_noticias.php (Estrutura Correta com Header Centralizado)

// A inicialização será feita pelo header, mas precisamos da paginação ANTES para o título

// 1. Inclui DB config só para a paginação (se header garantir $pdo, pode remover)
require_once 'db_config.php';

// --- Lógica da Paginação ---
$itens_por_pagina = 10; $pagina_atual = 1; $total_noticias = 0; $total_paginas = 0; $noticias_pagina = []; $erro_busca_pagina = false;
if (isset($_GET['pagina']) && filter_var($_GET['pagina'], FILTER_VALIDATE_INT) && $_GET['pagina'] > 0) { $pagina_atual = (int)$_GET['pagina']; }

if (isset($pdo)) { // Usa $pdo do db_config incluído acima
    try {
        $sql_total = "SELECT COUNT(*) FROM noticias WHERE status = 'publicada'"; $stmt_total = $pdo->query($sql_total); $total_noticias = (int)$stmt_total->fetchColumn();
        if ($total_noticias > 0) {
            $total_paginas = ceil($total_noticias / $itens_por_pagina);
            if ($pagina_atual > $total_paginas) { $pagina_atual = $total_paginas; } if ($pagina_atual < 1) { $pagina_atual = 1; } $offset = ($pagina_atual - 1) * $itens_por_pagina;
            $sql_pagina = "SELECT id, titulo, resumo, data_publicacao FROM noticias WHERE status = 'publicada' ORDER BY data_publicacao DESC LIMIT :limit OFFSET :offset"; $stmt_pagina = $pdo->prepare($sql_pagina); $stmt_pagina->bindValue(':limit', $itens_por_pagina, PDO::PARAM_INT); $stmt_pagina->bindValue(':offset', $offset, PDO::PARAM_INT); $stmt_pagina->execute(); $noticias_pagina = $stmt_pagina->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) { error_log("Erro na paginação de notícias: " . $e->getMessage()); $erro_busca_pagina = true; }
} else { $erro_busca_pagina = true; } // Erro se $pdo não foi definido por db_config
// --- Fim Paginação ---

// 2. Definir Título da Página
$page_title = 'Todas as Notícias';
if ($total_noticias > 0 && $total_paginas > 1) { $page_title .= " - Página " . $pagina_atual; }

// 3. Incluir o Header (fará session_start, definirá $usuario_logado, etc.)
require_once 'header.php';
?>

    <main class="container mt-4">
        <h1 class="mb-4">Todas as Notícias</h1>

        <?php
        // Verifica o erro da paginação ou se $pdo é null (indicando falha na conexão via header)
        if ($erro_busca_pagina || $pdo === null): ?>
            <div class="alert alert-danger">Erro ao carregar notícias. Verifique a conexão com o banco ou tente novamente mais tarde.</div>
        <?php elseif (empty($noticias_pagina)): ?>
            <div class="alert alert-info">Nenhuma notícia publicada encontrada.</div>
        <?php else: ?>
            <?php foreach ($noticias_pagina as $noticia): ?>
                <div class="card mb-3 shadow-sm"> <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($noticia['titulo']); ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted">
                            <small>Publicado em: <?php
                                $data = new DateTime($noticia['data_publicacao']);
                                echo $data->format('d/m/Y H:i'); // Voltei a mostrar a hora aqui? Ou mantenha só data?
                            ?></small>
                        </h6>
                        <p class="card-text"><?php echo htmlspecialchars($noticia['resumo']); ?></p>
                        <a href="ver_noticia.php?id=<?php echo $noticia['id']; ?>" class="btn btn-sm btn-outline-primary">Leia mais <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($total_paginas > 1): ?>
                 <nav aria-label="Navegação das notícias"> <ul class="pagination justify-content-center"> <li class="page-item <?php echo ($pagina_atual <= 1) ? 'disabled' : ''; ?>"> <a class="page-link" href="?pagina=<?php echo $pagina_atual - 1; ?>" aria-label="Anterior"> <span aria-hidden="true">&laquo;</span> <span class="sr-only">Anterior</span> </a> </li> <?php for ($i = 1; $i <= $total_paginas; $i++): ?> <li class="page-item <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>"> <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a> </li> <?php endfor; ?> <li class="page-item <?php echo ($pagina_atual >= $total_paginas) ? 'disabled' : ''; ?>"> <a class="page-link" href="?pagina=<?php echo $pagina_atual + 1; ?>" aria-label="Próxima"> <span aria-hidden="true">&raquo;</span> <span class="sr-only">Próxima</span> </a> </li> </ul> </nav>
             <?php endif; ?>

        <?php endif; ?>
    </main>

<?php
// 4. Incluir o Rodapé
require_once 'footer.php';
?>