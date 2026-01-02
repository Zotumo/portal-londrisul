<?php
// admin/noticia_apagar.php

require_once 'auth_check.php'; // Garante que apenas administradores logados acessem
require_once '../db_config.php'; // Conexão com o banco de dados

// #############################################################################
// ## PASSO 1: DEFINIR NÍVEIS DE ACESSO QUE PODEM APAGAR NOTÍCIAS            ##
// ## Ajuste esta array conforme a sua regra de negócio!                    ##
// #############################################################################
$niveis_permitidos_apagar = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];

// Verifica se o nível do usuário logado está na lista de permissões
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_apagar)) {
    $_SESSION['admin_error_message'] = "Seu nível de acesso ({$admin_nivel_acesso_logado}) não permite apagar notícias.";
    header('Location: noticias_listar.php');
    exit;
}

$noticia_id = null;
// Valida se o ID da notícia foi fornecido e é um inteiro
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $noticia_id = (int)$_GET['id'];
} else {
    // Se o ID não for válido, define uma mensagem de erro e redireciona
    $_SESSION['admin_error_message'] = "ID da notícia inválido para exclusão.";
    header('Location: noticias_listar.php');
    exit;
}

// Verifica se a conexão com o banco foi estabelecida e se temos um ID de notícia
if ($pdo && $noticia_id) {
    try {
        // Inicia uma transação para garantir a integridade dos dados
        // Se algo der errado (ex: apagar imagem mas não o registro), podemos reverter.
        $pdo->beginTransaction();

        // 1. Buscar o nome da imagem de destaque para apagá-la do servidor
        $stmt_img = $pdo->prepare("SELECT imagem_destaque FROM noticias WHERE id = :id");
        $stmt_img->bindParam(':id', $noticia_id, PDO::PARAM_INT);
        $stmt_img->execute();
        $imagem_nome = $stmt_img->fetchColumn(); // Pega apenas o nome do arquivo da imagem

        // 2. Apagar a notícia do banco de dados
        $stmt_delete = $pdo->prepare("DELETE FROM noticias WHERE id = :id");
        $stmt_delete->bindParam(':id', $noticia_id, PDO::PARAM_INT);
        $delete_success = $stmt_delete->execute();

        if ($delete_success) {
            // Se o registro foi apagado do banco com sucesso, tenta apagar a imagem
            if ($imagem_nome) {
                $caminho_imagem = '../img/noticias/' . $imagem_nome;
                if (file_exists($caminho_imagem)) {
                    if (!unlink($caminho_imagem)) {
                        // Se não conseguiu apagar a imagem, não é um erro crítico para a transação,
                        // mas podemos logar ou notificar. Por ora, a operação no banco é o principal.
                        error_log("AVISO: Não foi possível apagar o arquivo de imagem {$caminho_imagem} para a notícia ID {$noticia_id}.");
                    }
                }
            }
            // Confirma a transação
            $pdo->commit();
            $_SESSION['admin_success_message'] = "Notícia ID {$noticia_id} apagada com sucesso.";
        } else {
            // Se a exclusão no banco falhou, reverte a transação
            $pdo->rollBack();
            $_SESSION['admin_error_message'] = "Erro ao apagar a notícia ID {$noticia_id} do banco de dados.";
        }
    } catch (PDOException $e) {
        // Se qualquer erro PDO ocorrer, reverte a transação
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro ao apagar notícia ID {$noticia_id}: " . $e->getMessage());
        $_SESSION['admin_error_message'] = "Erro de banco de dados ao tentar apagar a notícia. Detalhes: " . $e->getMessage();
    }
} else {
    // Mensagem de erro se não houver conexão PDO ou ID da notícia
    $_SESSION['admin_error_message'] = "Não foi possível apagar a notícia. Dados inválidos ou conexão com o banco falhou.";
}

// Redireciona de volta para a lista de notícias
header('Location: noticias_listar.php');
exit;
?>