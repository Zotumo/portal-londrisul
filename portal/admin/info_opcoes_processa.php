<?php
// admin/info_opcoes_processa.php
// Processa o formulário para adicionar/editar Opções de Informação.

require_once 'auth_check.php';

// --- Definição de Permissões ---
$niveis_permitidos_crud_info_opcoes = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_crud_info_opcoes)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para gerenciar opções de informação.";
    header('Location: info_opcoes_listar.php');
    exit;
}

require_once '../db_config.php';

// Parâmetros GET/POST para voltar para a listagem com filtros corretos
$params_retorno_lista_info_array = [];
$param_sources_for_redirect_info = [$_POST, $_GET];
foreach ($param_sources_for_redirect_info as $source) {
    if (isset($source['pagina']) && empty($params_retorno_lista_info_array['pagina'])) $params_retorno_lista_info_array['pagina'] = $source['pagina'];
    if (isset($source['busca_descricao']) && empty($params_retorno_lista_info_array['busca_descricao'])) $params_retorno_lista_info_array['busca_descricao'] = $source['busca_descricao'];
    if (isset($source['busca_linha_id']) && !isset($params_retorno_lista_info_array['busca_linha_id'])) $params_retorno_lista_info_array['busca_linha_id'] = $source['busca_linha_id']; // Permite string vazia
    if (isset($source['status_filtro']) && empty($params_retorno_lista_info_array['status_filtro'])) $params_retorno_lista_info_array['status_filtro'] = $source['status_filtro'];
}

$query_string_retorno_proc_info = http_build_query($params_retorno_lista_info_array);
$link_voltar_lista_proc_info = 'info_opcoes_listar.php' . ($query_string_retorno_proc_info ? '?' . $query_string_retorno_proc_info : '');


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_info_opcao'])) {

    $info_opcao_id = filter_input(INPUT_POST, 'info_opcao_id', FILTER_VALIDATE_INT);
    $descricao_info = trim($_POST['descricao_info'] ?? '');
    // Se 'linha_id' vier vazio do select "Global", será uma string vazia. Convertemos para NULL.
    $linha_id_input = $_POST['linha_id'] ?? '';
    $linha_id_db = ($linha_id_input === '') ? null : filter_var($linha_id_input, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($linha_id_input !== '' && $linha_id_db === false) { // Se foi enviado algo mas não é um INT válido (e não era para ser global)
        $linha_id_db = null; // Ou tratar como erro
    }
    
    $status_info = trim($_POST['status_info'] ?? 'ativo');

    $erros_validacao_info = [];

    // --- Validações ---
    if (empty($descricao_info)) {
        $erros_validacao_info[] = "A Descrição da Informação é obrigatória.";
    } elseif (strlen($descricao_info) > 150) {
        $erros_validacao_info[] = "A Descrição da Informação não pode exceder 150 caracteres.";
    }

    if (!in_array($status_info, ['ativo', 'inativo'])) {
        $erros_validacao_info[] = "Status da opção de informação inválido.";
        $status_info = 'ativo'; // Padrão seguro
    }

    // Verificar duplicidade de: descricao_info + linha_id (onde linha_id pode ser NULL)
    if ($pdo && !empty($descricao_info) && empty($erros_validacao_info)) { // Só verifica se a descrição não está vazia
        try {
            if ($linha_id_db === null) { // Verificando duplicidade para uma info GLOBAL
                $sql_check_duplicidade = "SELECT id FROM info_opcoes WHERE descricao_info = :desc AND linha_id IS NULL";
                $params_check_duplicidade = [':desc' => $descricao_info];
            } else { // Verificando duplicidade para uma info ESPECÍFICA DE LINHA
                $sql_check_duplicidade = "SELECT id FROM info_opcoes WHERE descricao_info = :desc AND linha_id = :lid";
                $params_check_duplicidade = [':desc' => $descricao_info, ':lid' => $linha_id_db];
            }
            
            if ($info_opcao_id) { // Se estiver editando
                $sql_check_duplicidade .= " AND id != :id_info_opcao";
                $params_check_duplicidade[':id_info_opcao'] = $info_opcao_id;
            }
            
            $stmt_check_duplicidade = $pdo->prepare($sql_check_duplicidade);
            $stmt_check_duplicidade->execute($params_check_duplicidade);
            if ($stmt_check_duplicidade->fetch()) {
                $erros_validacao_info[] = "Esta Descrição ('" . htmlspecialchars($descricao_info) . "') já existe " . ($linha_id_db === null ? "como uma opção global." : "para a linha selecionada.");
            }
        } catch (PDOException $e_dup) {
            error_log("Erro ao verificar duplicidade de info_opcoes: " . $e_dup->getMessage());
            $erros_validacao_info[] = "Erro ao verificar dados. Tente novamente.";
        }
    }


    if (!empty($erros_validacao_info)) {
        $_SESSION['admin_form_error_info_opcao'] = implode("<br>", $erros_validacao_info);
        $_SESSION['form_data_info_opcao'] = $_POST; 
        
        $redirect_url_form_io = $info_opcao_id ? 'info_opcoes_formulario.php?id=' . $info_opcao_id : 'info_opcoes_formulario.php';
        $redirect_url_form_io .= ($query_string_retorno_proc_info ? (strpos($redirect_url_form_io, '?') === false ? '?' : '&') . $query_string_retorno_proc_info : '');
        
        header('Location: ' . $redirect_url_form_io);
        exit;
    }

    // --- Processar no Banco ---
    if ($pdo) {
        try {
            if ($info_opcao_id) { // Modo EDIÇÃO
                $sql_info_op = "UPDATE info_opcoes 
                                SET descricao_info = :desc_info, linha_id = :linha_id, status_info = :status_info
                                WHERE id = :id_info";
                $stmt_info_op = $pdo->prepare($sql_info_op);
                $stmt_info_op->bindParam(':id_info', $info_opcao_id, PDO::PARAM_INT);
            } else { // Modo CADASTRO
                $sql_info_op = "INSERT INTO info_opcoes (descricao_info, linha_id, status_info) 
                                VALUES (:desc_info, :linha_id, :status_info)";
                $stmt_info_op = $pdo->prepare($sql_info_op);
            }

            $stmt_info_op->bindParam(':desc_info', $descricao_info, PDO::PARAM_STR);
            $stmt_info_op->bindParam(':linha_id', $linha_id_db, $linha_id_db === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt_info_op->bindParam(':status_info', $status_info, PDO::PARAM_STR);

            if ($stmt_info_op->execute()) {
                $mensagem_sucesso_info = $info_opcao_id ? "Opção de Informação '" . htmlspecialchars($descricao_info) . "' atualizada!" : "Nova Opção de Informação '" . htmlspecialchars($descricao_info) . "' cadastrada!";
                $_SESSION['admin_success_message'] = $mensagem_sucesso_info;
                header('Location: ' . $link_voltar_lista_proc_info);
                exit;
            } else {
                $_SESSION['admin_error_message'] = "Erro ao salvar Opção de Informação no banco de dados.";
            }
        } catch (PDOException $e) {
            error_log("Erro PDO ao processar info_opcoes: " . $e->getMessage());
            if ($e->errorInfo[1] == 1062) { // Código de erro para entrada duplicada (UK)
                $_SESSION['admin_error_message'] = "Erro: Esta Descrição já existe para a linha selecionada (ou como global).";
            } else {
                $_SESSION['admin_error_message'] = "Erro de banco de dados ao salvar. Detalhes: " . $e->getMessage();
            }
        }
    } else {
        $_SESSION['admin_error_message'] = "Falha na conexão com o banco. Opção não salva.";
    }

    $_SESSION['form_data_info_opcao'] = $_POST;
    $redirect_url_form_io_err = $info_opcao_id ? 'info_opcoes_formulario.php?id=' . $info_opcao_id : 'info_opcoes_formulario.php';
    $redirect_url_form_io_err .= ($query_string_retorno_proc_info ? (strpos($redirect_url_form_io_err, '?') === false ? '?' : '&') . $query_string_retorno_proc_info : '');
    header('Location: ' . $redirect_url_form_io_err);
    exit;

} else {
    $_SESSION['admin_error_message'] = "Acesso inválido ao processamento de opções de informação.";
    header('Location: ' . $link_voltar_lista_proc_info);
    exit;
}
?>