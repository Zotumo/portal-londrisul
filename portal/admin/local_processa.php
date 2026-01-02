<?php
// admin/local_processa.php

require_once 'auth_check.php'; // Autenticação e permissões

// --- Definição de Permissões (DEVE SER CONSISTENTE COM local_formulario.php) ---
$niveis_permitidos_processar_locais = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];

if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_processar_locais)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para salvar dados de locais.";
    header('Location: locais_listar.php');
    exit;
}

require_once '../db_config.php';

// Parâmetros GET para voltar para a listagem com filtros corretos
$params_retorno_lista_array = [];
if (isset($_POST['pagina'])) $params_retorno_lista_array['pagina'] = $_POST['pagina']; // Se vier do form via GET no action
elseif (isset($_GET['pagina'])) $params_retorno_lista_array['pagina'] = $_GET['pagina']; // Se vier do form via GET no action

if (isset($_POST['busca_nome'])) $params_retorno_lista_array['busca_nome'] = $_POST['busca_nome'];
elseif (isset($_GET['busca_nome'])) $params_retorno_lista_array['busca_nome'] = $_GET['busca_nome'];

if (isset($_POST['busca_tipo'])) $params_retorno_lista_array['busca_tipo'] = $_POST['busca_tipo'];
elseif (isset($_GET['busca_tipo'])) $params_retorno_lista_array['busca_tipo'] = $_GET['busca_tipo'];

$query_string_retorno_proc = http_build_query($params_retorno_lista_array);
$link_voltar_lista_proc = 'locais_listar.php' . ($query_string_retorno_proc ? '?' . $query_string_retorno_proc : '');


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_local'])) {

    $local_id = filter_input(INPUT_POST, 'local_id', FILTER_VALIDATE_INT);
    $nome_local = trim($_POST['nome_local'] ?? '');
    $tipo_local = trim($_POST['tipo_local'] ?? '');

    $erros_validacao = [];

    // --- Validações ---
    if (empty($nome_local)) {
        $erros_validacao[] = "O Nome do Local é obrigatório.";
    } elseif (strlen($nome_local) > 150) {
        $erros_validacao[] = "O Nome do Local não pode exceder 150 caracteres.";
    }

    // Validação do Tipo de Local
    $tipos_locais_permitidos = ['Garagem', 'Terminal', 'Ponto', 'CIOP']; // Mantenha consistente com o formulário
    if (empty($tipo_local)) {
        $erros_validacao[] = "O Tipo do Local é obrigatório.";
    } elseif (!in_array($tipo_local, $tipos_locais_permitidos)) {
        $erros_validacao[] = "Tipo de Local inválido selecionado.";
    }

    // Verificar duplicidade de Nome do Local (exceto para o próprio local em edição)
    if ($pdo && !empty($nome_local)) {
        try {
            $sql_check_nome = "SELECT id FROM locais WHERE nome = :nome";
            $params_check_nome = [':nome' => $nome_local];
            if ($local_id) { // Se estiver editando
                $sql_check_nome .= " AND id != :id_local";
                $params_check_nome[':id_local'] = $local_id;
            }
            $stmt_check_nome = $pdo->prepare($sql_check_nome);
            $stmt_check_nome->execute($params_check_nome);
            if ($stmt_check_nome->fetch()) {
                $erros_validacao[] = "Já existe um local com o nome '" . htmlspecialchars($nome_local) . "'.";
            }
        } catch (PDOException $e) {
            error_log("Erro ao verificar duplicidade de nome de local: " . $e->getMessage());
            $erros_validacao[] = "Erro ao verificar dados. Tente novamente.";
        }
    } elseif (!$pdo) {
        $erros_validacao[] = "Erro de conexão com o banco de dados para validações.";
    }


    // Se houver erros de validação, redireciona de volta ao formulário
    if (!empty($erros_validacao)) {
        $_SESSION['admin_form_error_local'] = implode("<br>", $erros_validacao);
        $_SESSION['form_data_local'] = $_POST; // Guarda dados para repopular (exceto senha, imagem)
        
        $redirect_url_form = $local_id ? 'local_formulario.php?id=' . $local_id : 'local_formulario.php';
        // Adiciona os parâmetros de retorno da lista ao redirect do formulário
        $redirect_url_form .= ($query_string_retorno_proc ? (strpos($redirect_url_form, '?') === false ? '?' : '&') . $query_string_retorno_proc : '');
        
        header('Location: ' . $redirect_url_form);
        exit;
    }

    // --- Processar no Banco de Dados ---
    if ($pdo) {
        try {
            if ($local_id) { // Modo EDIÇÃO
                $sql = "UPDATE locais SET nome = :nome, tipo = :tipo WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id', $local_id, PDO::PARAM_INT);
            } else { // Modo CADASTRO
                $sql = "INSERT INTO locais (nome, tipo) VALUES (:nome, :tipo)";
                $stmt = $pdo->prepare($sql);
            }

            $stmt->bindParam(':nome', $nome_local, PDO::PARAM_STR);
            $stmt->bindParam(':tipo', $tipo_local, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $mensagem_sucesso = $local_id ? "Local '" . htmlspecialchars($nome_local) . "' atualizado com sucesso!" : "Novo local '" . htmlspecialchars($nome_local) . "' cadastrado com sucesso!";
                $_SESSION['admin_success_message'] = $mensagem_sucesso;
                header('Location: ' . $link_voltar_lista_proc);
                exit;
            } else {
                $_SESSION['admin_error_message'] = "Erro ao salvar local no banco de dados.";
            }
        } catch (PDOException $e) {
            error_log("Erro PDO ao processar local: " . $e->getMessage());
            // Não exponha e->getMessage() diretamente ao usuário em produção se contiver info sensível.
            $_SESSION['admin_error_message'] = "Erro de banco de dados ao salvar o local. Consulte o log para detalhes.";
        }
    } else {
        $_SESSION['admin_error_message'] = "Falha na conexão com o banco de dados. Local não foi salvo.";
    }

    // Se chegou aqui, algo deu errado após as validações iniciais (ex: erro PDO)
    // Então, redireciona de volta para o formulário com os dados para repopulação
    $_SESSION['form_data_local'] = $_POST;
    $redirect_url_form_erro = $local_id ? 'local_formulario.php?id=' . $local_id : 'local_formulario.php';
    $redirect_url_form_erro .= ($query_string_retorno_proc ? (strpos($redirect_url_form_erro, '?') === false ? '?' : '&') . $query_string_retorno_proc : '');
    header('Location: ' . $redirect_url_form_erro);
    exit;

} else {
    // Se não for POST ou faltar 'salvar_local', redireciona para a lista
    $_SESSION['admin_error_message'] = "Acesso inválido ao processamento de locais.";
    header('Location: ' . $link_voltar_lista_proc);
    exit;
}
?>