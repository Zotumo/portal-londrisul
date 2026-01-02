<?php
// admin/local_apagar.php

require_once 'auth_check.php'; // Autenticação e permissões

// --- Definição de Permissões (DEVE SER CONSISTENTE COM locais_listar.php) ---
$niveis_permitidos_apagar_locais = ['Gerência', 'Administrador']; // Ajuste conforme necessário

if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_apagar_locais)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para apagar locais.";
    header('Location: locais_listar.php');
    exit;
}

require_once '../db_config.php';

$local_id_para_apagar = null;
// Valida se o ID do local foi fornecido e é um inteiro
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $local_id_para_apagar = (int)$_GET['id'];
} else {
    $_SESSION['admin_error_message'] = "ID do local inválido para exclusão.";
    header('Location: locais_listar.php');
    exit;
}

// Construir parâmetros de redirecionamento para voltar à página/filtro correto
$redirect_params_apagar_array = [];
if (isset($_GET['pagina'])) $redirect_params_apagar_array['pagina'] = $_GET['pagina'];
if (isset($_GET['busca_nome'])) $redirect_params_apagar_array['busca_nome'] = $_GET['busca_nome'];
if (isset($_GET['busca_tipo'])) $redirect_params_apagar_array['busca_tipo'] = $_GET['busca_tipo'];
$query_string_retorno_apagar = http_build_query($redirect_params_apagar_array);
$location_redirect_lista_apagar = 'locais_listar.php' . ($query_string_retorno_apagar ? '?' . $query_string_retorno_apagar : '');


if ($pdo && $local_id_para_apagar) {
    // Opcional: Antes de apagar, buscar o nome do local para mensagem de feedback
    $nome_local_apagado = "ID " . $local_id_para_apagar; // Fallback
    try {
        $stmt_nome = $pdo->prepare("SELECT nome FROM locais WHERE id = :id_local_nome");
        $stmt_nome->bindParam(':id_local_nome', $local_id_para_apagar, PDO::PARAM_INT);
        $stmt_nome->execute();
        $nome_fetch = $stmt_nome->fetchColumn();
        if ($nome_fetch) {
            $nome_local_apagado = htmlspecialchars($nome_fetch);
        }
    } catch (PDOException $e_nome) {
        // Não crítico, continua com o ID
    }


    try {
        // Tenta apagar o local do banco de dados
        $stmt_delete = $pdo->prepare("DELETE FROM locais WHERE id = :id_local");
        $stmt_delete->bindParam(':id_local', $local_id_para_apagar, PDO::PARAM_INT);
        
        if ($stmt_delete->execute()) {
            if ($stmt_delete->rowCount() > 0) {
                $_SESSION['admin_success_message'] = "Local '{$nome_local_apagado}' apagado com sucesso.";
            } else {
                // O ID era válido, mas não foi encontrado no banco (talvez já apagado por outra ação)
                $_SESSION['admin_warning_message'] = "Local '{$nome_local_apagado}' não encontrado para exclusão ou já havia sido apagado.";
            }
        } else {
            // Erro na execução do DELETE, mas não uma exceção PDO (raro, mas possível)
            $_SESSION['admin_error_message'] = "Erro ao tentar apagar o local '{$nome_local_apagado}' do banco de dados.";
        }
    } catch (PDOException $e) {
        // Erro PDO, provavelmente devido a restrições de chave estrangeira
        error_log("Erro PDO ao apagar local ID {$local_id_para_apagar}: " . $e->getMessage());
        $mensagem_erro_usuario = "Erro de banco de dados ao tentar apagar o local '{$nome_local_apagado}'. ";
        
        // Códigos de erro comuns para restrição de FK no MySQL: 1451 (Cannot delete or update a parent row)
        if ($e->errorInfo[1] == 1451) {
            $mensagem_erro_usuario .= "Este local não pode ser apagado porque está sendo utilizado em outras partes do sistema (como em linhas, funções ou escalas). Por favor, verifique as dependências antes de tentar apagar.";
        } else {
            $mensagem_erro_usuario .= "Consulte o log do servidor para mais detalhes. Código do erro: " . $e->getCode();
        }
        $_SESSION['admin_error_message'] = $mensagem_erro_usuario;
    }
} else {
    // Mensagem de erro se não houver conexão PDO ou ID do local inválido (já tratado no início, mas como fallback)
    if (!$pdo) $_SESSION['admin_error_message'] = "Falha na conexão com o banco de dados.";
    // Se o ID já foi invalidado, a mensagem já foi definida.
}

// Redireciona de volta para a lista de locais
header("Location: " . $location_redirect_lista_apagar);
exit;
?>