<?php
// admin/veiculo_processa.php

require_once 'auth_check.php';

// --- Permissões (conforme definido para o formulário) ---
$niveis_permitidos_processar_veiculos = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_processar_veiculos)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para salvar dados de veículos.";
    // Idealmente, o redirect aqui deve manter os filtros da listagem, se vieram do formulário
    $redirect_params_err_perm = [];
    if (isset($_POST['pagina'])) $redirect_params_err_perm['pagina'] = $_POST['pagina'];
    // Adicionar outros filtros se eles forem passados como hidden fields no formulário
    header('Location: veiculos_listar.php' . (!empty($redirect_params_err_perm) ? '?' . http_build_query($redirect_params_err_perm) : ''));
    exit;
}

require_once '../db_config.php';

// Parâmetros GET/POST para voltar para a listagem com filtros corretos
$params_retorno_lista_proc_v = [];
// Coleta parâmetros de POST primeiro, depois de GET como fallback (se o form usa method GET no action, ou se vier de um link)
$param_sources_v = [$_POST, $_GET]; 
foreach ($param_sources_v as $source_v) {
    if (isset($source_v['pagina']) && empty($params_retorno_lista_proc_v['pagina'])) $params_retorno_lista_proc_v['pagina'] = $source_v['pagina'];
    if (isset($source_v['busca_prefixo']) && empty($params_retorno_lista_proc_v['busca_prefixo'])) $params_retorno_lista_proc_v['busca_prefixo'] = $source_v['busca_prefixo'];
    if (isset($source_v['busca_tipo']) && empty($params_retorno_lista_proc_v['busca_tipo'])) $params_retorno_lista_proc_v['busca_tipo'] = $source_v['busca_tipo'];
    if (isset($source_v['busca_status']) && empty($params_retorno_lista_proc_v['busca_status'])) $params_retorno_lista_proc_v['busca_status'] = $source_v['busca_status'];
}
$query_string_retorno_proc_v = http_build_query($params_retorno_lista_proc_v);
$link_voltar_lista_proc_v = 'veiculos_listar.php' . ($query_string_retorno_proc_v ? '?' . $query_string_retorno_proc_v : '');


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_veiculo'])) {

    $veiculo_id = filter_input(INPUT_POST, 'veiculo_id', FILTER_VALIDATE_INT);
    $prefixo_veiculo_input = trim($_POST['prefixo_veiculo'] ?? ''); // Nome do campo no formulário
    $tipo_veiculo = trim($_POST['tipo_veiculo'] ?? '');
    $status_veiculo = trim($_POST['status_veiculo'] ?? 'operação');

    $erros_validacao_v = [];

    // --- Validações ---
    // Validação do Prefixo (NOVA LÓGICA)
    if (empty($prefixo_veiculo_input)) {
        $erros_validacao_v[] = "O Prefixo do Veículo é obrigatório.";
    } elseif (!ctype_digit($prefixo_veiculo_input)) { // Verifica se contém apenas números
        $erros_validacao_v[] = "O Prefixo do Veículo deve conter apenas números.";
    } elseif (strlen($prefixo_veiculo_input) !== 4) { // Verifica se tem exatamente 4 dígitos
        $erros_validacao_v[] = "O Prefixo do Veículo deve conter exatamente 4 números.";
    }

    // Validação do Tipo de Veículo
    $tipos_veiculo_validos = [
        'Convencional Amarelo', 'Convencional Amarelo com Ar', 'Micro', 'Micro com Ar',
        'Convencional Azul', 'Convencional Azul com Ar', 'Padron Azul', 'SuperBus', 'Leve'
    ];
    if (empty($tipo_veiculo) || !in_array($tipo_veiculo, $tipos_veiculo_validos)) {
        $erros_validacao_v[] = "Tipo de Veículo inválido selecionado.";
    }

    // Validação do Status do Veículo
    if (!in_array($status_veiculo, ['operação', 'fora de operação'])) {
        $erros_validacao_v[] = "Status do veículo inválido.";
        $status_veiculo = 'operação'; // Força um padrão seguro
    }

    // Verificar duplicidade de Prefixo (APENAS se o prefixo passou nas validações anteriores)
    if ($pdo && !empty($prefixo_veiculo_input) && ctype_digit($prefixo_veiculo_input) && strlen($prefixo_veiculo_input) === 4) {
        try {
            $sql_check_pref = "SELECT id FROM veiculos WHERE prefixo = :prefixo";
            $params_check_pref = [':prefixo' => $prefixo_veiculo_input];
            if ($veiculo_id) { // Se estiver editando
                $sql_check_pref .= " AND id != :id_veiculo";
                $params_check_pref[':id_veiculo'] = $veiculo_id;
            }
            $stmt_check_pref = $pdo->prepare($sql_check_pref);
            $stmt_check_pref->execute($params_check_pref);
            if ($stmt_check_pref->fetch()) {
                $erros_validacao_v[] = "O prefixo '" . htmlspecialchars($prefixo_veiculo_input) . "' já está cadastrado.";
            }
        } catch (PDOException $e_pref) {
            error_log("Erro ao verificar duplicidade de prefixo: " . $e_pref->getMessage());
            $erros_validacao_v[] = "Erro ao verificar dados do prefixo. Tente novamente.";
        }
    } elseif (!$pdo && empty($erros_validacao_v)) { // Só adiciona erro de conexão se não houver outros erros antes
        $erros_validacao_v[] = "Erro de conexão com o banco de dados para validações.";
    }


    // Se houver erros de validação, redireciona de volta ao formulário
    if (!empty($erros_validacao_v)) {
        $_SESSION['admin_form_error_veiculo'] = implode("<br>", $erros_validacao_v);
        $_SESSION['form_data_veiculo'] = $_POST; 
        
        $redirect_url_form_v = $veiculo_id ? 'veiculo_formulario.php?id=' . $veiculo_id : 'veiculo_formulario.php';
        $redirect_url_form_v .= ($query_string_retorno_proc_v ? (strpos($redirect_url_form_v, '?') === false ? '?' : '&') . $query_string_retorno_proc_v : '');
        
        header('Location: ' . $redirect_url_form_v);
        exit;
    }

    // --- Processar no Banco de Dados ---
    if ($pdo) {
        try {
            if ($veiculo_id) { // Modo EDIÇÃO
                $sql_v = "UPDATE veiculos SET prefixo = :prefixo, tipo = :tipo, status = :status WHERE id = :id";
                $stmt_v_op = $pdo->prepare($sql_v);
                $stmt_v_op->bindParam(':id', $veiculo_id, PDO::PARAM_INT);
            } else { // Modo CADASTRO
                $sql_v = "INSERT INTO veiculos (prefixo, tipo, status) VALUES (:prefixo, :tipo, :status)";
                $stmt_v_op = $pdo->prepare($sql_v);
            }

            $stmt_v_op->bindParam(':prefixo', $prefixo_veiculo_input, PDO::PARAM_STR);
            $stmt_v_op->bindParam(':tipo', $tipo_veiculo, PDO::PARAM_STR); // ENUM é tratado como string no bind
            $stmt_v_op->bindParam(':status', $status_veiculo, PDO::PARAM_STR); // ENUM é tratado como string

            if ($stmt_v_op->execute()) {
                $mensagem_sucesso_v = $veiculo_id ? "Veículo prefixo '" . htmlspecialchars($prefixo_veiculo_input) . "' atualizado!" : "Novo veículo prefixo '" . htmlspecialchars($prefixo_veiculo_input) . "' cadastrado!";
                $_SESSION['admin_success_message'] = $mensagem_sucesso_v;
                header('Location: ' . $link_voltar_lista_proc_v);
                exit;
            } else {
                $_SESSION['admin_error_message'] = "Erro ao salvar veículo no banco de dados.";
            }
        } catch (PDOException $e_v_save) {
            error_log("Erro PDO ao processar veículo: " . $e_v_save->getMessage());
            $_SESSION['admin_error_message'] = "Erro de banco de dados ao salvar o veículo. Detalhes: " . $e_v_save->getMessage();
        }
    } else {
        $_SESSION['admin_error_message'] = "Falha na conexão com o banco. Veículo não foi salvo.";
    }

    $_SESSION['form_data_veiculo'] = $_POST;
    $redirect_url_form_v_err = $veiculo_id ? 'veiculo_formulario.php?id=' . $veiculo_id : 'veiculo_formulario.php';
    $redirect_url_form_v_err .= ($query_string_retorno_proc_v ? (strpos($redirect_url_form_v_err, '?') === false ? '?' : '&') . $query_string_retorno_proc_v : '');
    header('Location: ' . $redirect_url_form_v_err);
    exit;

} else {
    $_SESSION['admin_error_message'] = "Acesso inválido ao processamento de veículos.";
    header('Location: ' . $link_voltar_lista_proc_v);
    exit;
}
?>