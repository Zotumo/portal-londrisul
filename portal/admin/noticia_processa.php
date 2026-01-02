<?php
// admin/noticia_processa.php

require_once 'auth_check.php'; // Verifica se o admin está logado e pega $admin_nivel_acesso_logado
require_once '../db_config.php'; // Conexão com o banco

// VERIFICAR PERMISSÃO para processar notícias
$niveis_permitidos_processar_noticias = ['Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_processar_noticias)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para salvar notícias.";
    // Redireciona para a lista ou formulário dependendo do contexto anterior (se possível)
    $redirect_url = isset($_POST['noticia_id']) ? 'noticia_formulario.php?id=' . $_POST['noticia_id'] : 'noticias_listar.php';
    header('Location: ' . $redirect_url);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_noticia'])) {

    // --- Obter dados do formulário ---
    $noticia_id = filter_input(INPUT_POST, 'noticia_id', FILTER_VALIDATE_INT); // Retorna null se não for int ou não existir
    $titulo = trim($_POST['titulo'] ?? '');
    $resumo = trim($_POST['resumo'] ?? '');
    // Para o conteúdo completo, não vamos remover tags HTML simples intencionalmente,
    // mas a sanitização na exibição (htmlspecialchars) é crucial.
    // Se você quiser permitir HTML mais complexo, precisará de um editor WYSIWYG e uma biblioteca de sanitização de HTML robusta.
    $conteudo_completo = trim($_POST['conteudo_completo'] ?? '');
    $status = $_POST['status'] ?? 'rascunho';
    $remover_imagem_atual = isset($_POST['remover_imagem_atual']) ? 1 : 0;
    $data_publicacao_form = trim($_POST['data_publicacao'] ?? ''); // Data do formulário (pode estar vazia)

    $imagem_destaque_nome_final = null; // Nome da imagem a ser salvo no banco
    $modo_edicao = (bool)$noticia_id; // Define se estamos em modo de edição

    // --- Validações Básicas ---
    if (empty($titulo) || empty($conteudo_completo)) {
        $_SESSION['admin_form_error'] = "Título e Conteúdo Completo são obrigatórios.";
        $redirect_url = $modo_edicao ? 'noticia_formulario.php?id=' . $noticia_id : 'noticia_formulario.php';
        header('Location: ' . $redirect_url);
        exit;
    }
    if (!in_array($status, ['publicada', 'rascunho', 'arquivada'])) {
        $_SESSION['admin_form_error'] = "Status inválido selecionado.";
        $redirect_url = $modo_edicao ? 'noticia_formulario.php?id=' . $noticia_id : 'noticia_formulario.php';
        header('Location: ' . $redirect_url);
        exit;
    }
    // Validação da data_publicacao_form se não estiver vazia
    if (!empty($data_publicacao_form)) {
        $d = DateTime::createFromFormat('Y-m-d\TH:i', $data_publicacao_form);
        if (!$d || $d->format('Y-m-d\TH:i') !== $data_publicacao_form) {
            $_SESSION['admin_form_error'] = "Formato da Data de Publicação inválido.";
            $redirect_url = $modo_edicao ? 'noticia_formulario.php?id=' . $noticia_id : 'noticia_formulario.php';
            header('Location: ' . $redirect_url);
            exit;
        }
    }

    // --- Lógica de Upload da Imagem (se uma nova foi enviada) ---
    $pasta_upload = '../img/noticias/';
    if (isset($_FILES['imagem_destaque']) && $_FILES['imagem_destaque']['error'] == UPLOAD_ERR_OK) {
        if (!is_dir($pasta_upload) && !mkdir($pasta_upload, 0775, true) && !is_dir($pasta_upload)) {
            $_SESSION['admin_form_error'] = "Erro ao criar o diretório de upload de imagens.";
             $redirect_url = $modo_edicao ? 'noticia_formulario.php?id=' . $noticia_id : 'noticia_formulario.php';
            header('Location: ' . $redirect_url);
            exit;
        }
        if (!is_writable($pasta_upload)) {
            $_SESSION['admin_form_error'] = "O diretório de upload de imagens não tem permissão de escrita.";
            $redirect_url = $modo_edicao ? 'noticia_formulario.php?id=' . $noticia_id : 'noticia_formulario.php';
            header('Location: ' . $redirect_url);
            exit;
        }


        $nome_arquivo_original = basename($_FILES['imagem_destaque']['name']);
        $extensao_arquivo = strtolower(pathinfo($nome_arquivo_original, PATHINFO_EXTENSION));
        $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($extensao_arquivo, $tipos_permitidos)) {
            $imagem_destaque_nome_final = 'noticia_' . uniqid() . '_' . time() . '.' . $extensao_arquivo;
            $caminho_completo_upload = $pasta_upload . $imagem_destaque_nome_final;

            if (move_uploaded_file($_FILES['imagem_destaque']['tmp_name'], $caminho_completo_upload)) {
                // Upload bem-sucedido da nova imagem.
                // Se estava editando e tinha uma imagem antiga, apaga a antiga.
                if ($modo_edicao) {
                    $stmt_old_img = $pdo->prepare("SELECT imagem_destaque FROM noticias WHERE id = :id");
                    $stmt_old_img->bindParam(':id', $noticia_id, PDO::PARAM_INT);
                    $stmt_old_img->execute();
                    $old_img_name = $stmt_old_img->fetchColumn();
                    if ($old_img_name && $old_img_name != $imagem_destaque_nome_final && file_exists($pasta_upload . $old_img_name)) {
                        unlink($pasta_upload . $old_img_name);
                    }
                }
            } else {
                $_SESSION['admin_form_error'] = "Erro ao fazer upload da nova imagem. Verifique as permissões da pasta.";
                $redirect_url = $modo_edicao ? 'noticia_formulario.php?id=' . $noticia_id : 'noticia_formulario.php';
                header('Location: ' . $redirect_url);
                exit;
            }
        } else {
            $_SESSION['admin_form_error'] = "Formato de imagem inválido. Apenas JPG, JPEG, PNG, GIF são permitidos.";
            $redirect_url = $modo_edicao ? 'noticia_formulario.php?id=' . $noticia_id : 'noticia_formulario.php';
            header('Location: ' . $redirect_url);
            exit;
        }
    } elseif ($modo_edicao) { // Se não enviou nova imagem, mas está editando
        if ($remover_imagem_atual) {
            // Marcou para remover a imagem atual
            $stmt_old_img = $pdo->prepare("SELECT imagem_destaque FROM noticias WHERE id = :id");
            $stmt_old_img->bindParam(':id', $noticia_id, PDO::PARAM_INT);
            $stmt_old_img->execute();
            $old_img_name = $stmt_old_img->fetchColumn();
            if ($old_img_name && file_exists($pasta_upload . $old_img_name)) {
                unlink($pasta_upload . $old_img_name);
            }
            $imagem_destaque_nome_final = null; // Define como null no banco
        } else {
            // Não enviou nova E não marcou para remover, então mantém a imagem que já estava no banco.
            $stmt_curr_img = $pdo->prepare("SELECT imagem_destaque FROM noticias WHERE id = :id");
            $stmt_curr_img->bindParam(':id', $noticia_id, PDO::PARAM_INT);
            $stmt_curr_img->execute();
            $imagem_destaque_nome_final = $stmt_curr_img->fetchColumn();
        }
    }
    // Se for criação e não enviou imagem, $imagem_destaque_nome_final continua null.

    // --- Processar no Banco de Dados ---
    try {
        $pdo->beginTransaction(); // Inicia transação

        if ($modo_edicao) {
            // Modo Edição: UPDATE
            $sql_parts = [
                "titulo = :titulo",
                "resumo = :resumo",
                "conteudo_completo = :conteudo_completo",
                "status = :status",
                // imagem_destaque será sempre atualizada, mesmo que para o mesmo valor ou null
                "imagem_destaque = :imagem_destaque"
                // data_modificacao é atualizada automaticamente pelo ON UPDATE CURRENT_TIMESTAMP no banco
            ];
            $params = [
                ':titulo' => $titulo,
                ':resumo' => $resumo,
                ':conteudo_completo' => $conteudo_completo,
                ':status' => $status,
                ':imagem_destaque' => $imagem_destaque_nome_final, // Pode ser o novo nome, o antigo ou null
                ':id' => $noticia_id
            ];

            // Lógica para data_publicacao na EDIÇÃO
            if (!empty($data_publicacao_form)) {
                $sql_parts[] = "data_publicacao = :data_publicacao";
                $params[':data_publicacao'] = $data_publicacao_form;
            } elseif ($status == 'publicada') {
                // Se está publicando e não forneceu data no form, verifica se já existia uma data.
                // Se não existia (era rascunho/arquivada), define para agora.
                $stmt_check_pub = $pdo->prepare("SELECT data_publicacao FROM noticias WHERE id = :id_check");
                $stmt_check_pub->bindParam(':id_check', $noticia_id, PDO::PARAM_INT);
                $stmt_check_pub->execute();
                $existing_pub_date = $stmt_check_pub->fetchColumn();
                if (empty($existing_pub_date)) {
                    $sql_parts[] = "data_publicacao = NOW()";
                }
                // Se já existia uma data de publicação e o campo do form veio vazio, não alteramos a data.
            }
            // Se o status não for 'publicada' e o campo do form estiver vazio, não alteramos data_publicacao.

            $sql = "UPDATE noticias SET " . implode(", ", $sql_parts) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);

        } else {
            // Modo Criação: INSERT
            $data_publicacao_final_insert = null;
            if (!empty($data_publicacao_form)) {
                $data_publicacao_final_insert = $data_publicacao_form;
            } elseif ($status == 'publicada') {
                $data_publicacao_final_insert = date('Y-m-d H:i:s'); // NOW()
            }
            // Se status for rascunho/arquivada e form vazio, $data_publicacao_final_insert permanece null.

            $sql = "INSERT INTO noticias (titulo, resumo, conteudo_completo, status, imagem_destaque, data_publicacao)
                    VALUES (:titulo, :resumo, :conteudo_completo, :status, :imagem_destaque, :data_publicacao)";
            $stmt = $pdo->prepare($sql);

            // Bind dos parâmetros para INSERT
            $params = [
                ':titulo' => $titulo,
                ':resumo' => $resumo,
                ':conteudo_completo' => $conteudo_completo,
                ':status' => $status,
                ':imagem_destaque' => $imagem_destaque_nome_final, // Pode ser o nome do arquivo ou null
                // Para data_publicacao, o bind precisa ser cuidadoso com NULL
                ':data_publicacao' => $data_publicacao_final_insert
            ];
        }

        // Executar a query com os parâmetros
        if ($stmt->execute($params)) {
            $pdo->commit(); // Confirma a transação
            $_SESSION['admin_success_message'] = $modo_edicao ? "Notícia (ID: {$noticia_id}) atualizada com sucesso!" : "Notícia adicionada com sucesso!";
        } else {
            $pdo->rollBack(); // Desfaz a transação em caso de erro na execução
            $_SESSION['admin_error_message'] = "Erro ao salvar notícia no banco de dados.";
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack(); // Desfaz a transação em caso de exceção PDO
        }
        error_log("Erro ao salvar notícia: " . $e->getMessage());
        // Não exponha e->getMessage() diretamente ao usuário em produção se contiver info sensível.
        $_SESSION['admin_error_message'] = "Erro de banco de dados ao salvar notícia. Consulte o log para detalhes.";
    }

    header('Location: noticias_listar.php');
    exit;

} else {
    // Se não for POST ou faltar 'salvar_noticia', redireciona
    $_SESSION['admin_error_message'] = "Acesso inválido ao processamento.";
    header('Location: noticias_listar.php');
    exit;
}
?>