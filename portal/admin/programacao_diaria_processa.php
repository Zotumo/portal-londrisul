<?php
// admin/programacao_diaria_processa.php
// Processa o formulário de Blocos (Programação Diária).

require_once 'auth_check.php';

// --- Definição de Permissões ---
$niveis_permitidos_crud_blocos = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_crud_blocos)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para salvar Tabelas (Blocos).";
    header('Location: programacao_diaria_listar.php');
    exit;
}

require_once '../db_config.php';

// Parâmetros GET para voltar para a listagem com filtros corretos
$params_retorno_lista_prog_array = [];
if (isset($_POST['pagina'])) $params_retorno_lista_prog_array['pagina'] = $_POST['pagina'];
if (isset($_POST['busca_dia_tipo'])) $params_retorno_lista_prog_array['busca_dia_tipo'] = $_POST['busca_dia_tipo'];
if (isset($_POST['busca_tabela_work_id'])) $params_retorno_lista_prog_array['busca_tabela_work_id'] = $_POST['busca_tabela_work_id'];

$query_string_retorno_proc_prog = http_build_query($params_retorno_lista_prog_array);
$link_voltar_lista_proc_prog = 'programacao_diaria_listar.php' . ($query_string_retorno_proc_prog ? '?' . $query_string_retorno_proc_prog : '');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_programacao_diaria'])) {

    $programacao_id = filter_input(INPUT_POST, 'programacao_id', FILTER_VALIDATE_INT);
    $work_id = trim($_POST['work_id'] ?? '');
    $dia_semana_tipo = trim($_POST['dia_semana_tipo'] ?? '');
    $data_atualizacao_input = trim($_POST['data_atualizacao'] ?? ''); // Campo do formulário
    $hora_inicio_bloco = trim($_POST['hora_inicio_bloco'] ?? '');
    $hora_fim_bloco = trim($_POST['hora_fim_bloco'] ?? '');
    // veiculo_id foi removido do formulário

    $erros_validacao_prog = [];

    // --- Validações ---
    if (empty($work_id)) {
        $erros_validacao_prog[] = "O Nome da Tabela (WorkID do Bloco) é obrigatório.";
    } elseif (strlen($work_id) > 50) {
        $erros_validacao_prog[] = "O Nome da Tabela (WorkID do Bloco) não pode exceder 50 caracteres.";
    }

    $tipos_dia_permitidos = ['Uteis', 'Sabado', 'DomingoFeriado'];
    if (empty($dia_semana_tipo)) {
        $erros_validacao_prog[] = "O Tipo de Dia é obrigatório.";
    } elseif (!in_array($dia_semana_tipo, $tipos_dia_permitidos)) {
        $erros_validacao_prog[] = "Tipo de Dia inválido.";
    }

    // Data de Atualização (campo 'data' no banco) - AGORA OBRIGATÓRIO
    $data_atualizacao_db = null;
    if (empty($data_atualizacao_input)) {
        $erros_validacao_prog[] = "A Data de Atualização é obrigatória.";
    } else {
        try {
            $dt_obj = new DateTime($data_atualizacao_input);
            $data_atualizacao_db = $dt_obj->format('Y-m-d'); // Formato para o banco
            if ($data_atualizacao_db !== $data_atualizacao_input) { // Validação extra de formato
                 $erros_validacao_prog[] = "Formato da Data de Atualização inválido. Use AAAA-MM-DD.";
            }
        } catch (Exception $e) {
            $erros_validacao_prog[] = "Formato da Data de Atualização inválido. Use AAAA-MM-DD.";
        }
    }

    if (empty($hora_inicio_bloco)) {
        $erros_validacao_prog[] = "A Hora de Início Geral da Tabela é obrigatória.";
    } elseif (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $hora_inicio_bloco)) {
        $erros_validacao_prog[] = "Formato da Hora de Início inválido. Use HH:MM.";
    }

    if (empty($hora_fim_bloco)) {
        $erros_validacao_prog[] = "A Hora de Fim Geral da Tabela é obrigatória.";
    } elseif (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $hora_fim_bloco)) {
        $erros_validacao_prog[] = "Formato da Hora de Fim inválido. Use HH:MM.";
    }

    // Verificar duplicidade de WorkID + Dia da Semana
    if ($pdo && !empty($work_id) && !empty($dia_semana_tipo) && in_array($dia_semana_tipo, $tipos_dia_permitidos) && empty($erros_validacao_prog)) {
        try {
            $sql_check_duplicidade = "SELECT id FROM programacao_diaria WHERE work_id = :work_id AND dia_semana_tipo = :dia_tipo";
            $params_check_duplicidade = [':work_id' => $work_id, ':dia_tipo' => $dia_semana_tipo];
            if ($programacao_id) { 
                $sql_check_duplicidade .= " AND id != :id_prog";
                $params_check_duplicidade[':id_prog'] = $programacao_id;
            }
            $stmt_check_duplicidade = $pdo->prepare($sql_check_duplicidade);
            $stmt_check_duplicidade->execute($params_check_duplicidade);
            if ($stmt_check_duplicidade->fetch()) {
                $erros_validacao_prog[] = "Já existe uma Tabela com este Nome/WorkID para este Tipo de Dia.";
            }
        } catch (PDOException $e) {
            error_log("Erro ao verificar duplicidade de Bloco: " . $e->getMessage());
            $erros_validacao_prog[] = "Erro ao verificar dados. Tente novamente.";
        }
    } elseif (!$pdo && empty($erros_validacao_prog)) {
        $erros_validacao_prog[] = "Erro de conexão com o banco de dados para validações.";
    }

    if (!empty($erros_validacao_prog)) {
        $_SESSION['admin_form_error_programacao'] = implode("<br>", $erros_validacao_prog);
        $_SESSION['form_data_programacao'] = $_POST; // Guarda dados para repopular
        $redirect_url_form_prog = $programacao_id ? 'programacao_diaria_formulario.php?id=' . $programacao_id : 'programacao_diaria_formulario.php';
        $redirect_url_form_prog .= ($query_string_retorno_proc_prog ? (strpos($redirect_url_form_prog, '?') === false ? '?' : '&') . $query_string_retorno_proc_prog : '');
        header('Location: ' . $redirect_url_form_prog);
        exit;
    }

    // --- Processar no Banco ---
    if ($pdo) {
        try {
            // Assumindo que numero_tabela_diario será o mesmo que work_id do bloco
            $numero_tabela_diario_db = substr($work_id, 0, 10); // Pega os primeiros 10 caracteres do work_id para numero_tabela_diario

            if ($programacao_id) { // Edição
                $sql_prog_op = "UPDATE programacao_diaria 
                                SET work_id = :work_id, dia_semana_tipo = :dia_tipo, data = :data_atualizacao, 
                                    hora_inicio_prevista = :h_ini, hora_fim_prevista = :h_fim,
                                    numero_tabela_diario = :num_tab_diario 
                                    /* veiculo_id foi removido */
                                WHERE id = :id_prog_upd";
                $stmt_prog_op = $pdo->prepare($sql_prog_op);
                $stmt_prog_op->bindParam(':id_prog_upd', $programacao_id, PDO::PARAM_INT);
            } else { // Cadastro
                $sql_prog_op = "INSERT INTO programacao_diaria 
                                (work_id, dia_semana_tipo, data, hora_inicio_prevista, hora_fim_prevista, numero_tabela_diario /*, veiculo_id */) 
                                VALUES (:work_id, :dia_tipo, :data_atualizacao, :h_ini, :h_fim, :num_tab_diario /*, :veic_id */)";
                $stmt_prog_op = $pdo->prepare($sql_prog_op);
            }

            $stmt_prog_op->bindParam(':work_id', $work_id, PDO::PARAM_STR);
            $stmt_prog_op->bindParam(':dia_tipo', $dia_semana_tipo, PDO::PARAM_STR);
            $stmt_prog_op->bindParam(':data_atualizacao', $data_atualizacao_db, PDO::PARAM_STR); // data_atualizacao_db já está Y-m-d
            $stmt_prog_op->bindParam(':h_ini', $hora_inicio_bloco, PDO::PARAM_STR);
            $stmt_prog_op->bindParam(':h_fim', $hora_fim_bloco, PDO::PARAM_STR);
            $stmt_prog_op->bindParam(':num_tab_diario', $numero_tabela_diario_db, PDO::PARAM_STR);
            // $stmt_prog_op->bindParam(':veic_id', $veiculo_id_db, $veiculo_id_db === null ? PDO::PARAM_NULL : PDO::PARAM_INT); // Removido

            if ($stmt_prog_op->execute()) {
                $mensagem_sucesso_prog = $programacao_id ? "Tabela '" . htmlspecialchars($work_id) . "' atualizada com sucesso!" : "Nova Tabela '" . htmlspecialchars($work_id) . "' cadastrada com sucesso!";
                $_SESSION['admin_success_message'] = $mensagem_sucesso_prog;
                header('Location: ' . $link_voltar_lista_proc_prog);
                exit;
            } else {
                $_SESSION['admin_error_message'] = "Erro ao salvar Tabela no banco de dados.";
            }
        } catch (PDOException $e) {
            error_log("Erro PDO ao processar Programação Diária: " . $e->getMessage());
            $_SESSION['admin_error_message'] = "Erro de banco de dados ao salvar a Tabela. Detalhes: " . $e->getMessage();
        }
    } else {
        $_SESSION['admin_error_message'] = "Falha na conexão com o banco de dados. Tabela não foi salva.";
    }

    // Fallback redirect para o formulário em caso de erro PDO pós-validação
    $_SESSION['form_data_programacao'] = $_POST;
    $redirect_url_form_prog_err = $programacao_id ? 'programacao_diaria_formulario.php?id=' . $programacao_id : 'programacao_diaria_formulario.php';
    $redirect_url_form_prog_err .= ($query_string_retorno_proc_prog ? (strpos($redirect_url_form_prog_err, '?') === false ? '?' : '&') . $query_string_retorno_proc_prog : '');
    header('Location: ' . $redirect_url_form_prog_err);
    exit;

} else {
    $_SESSION['admin_error_message'] = "Acesso inválido ao processamento de Tabelas (Blocos).";
    header('Location: ' . $link_voltar_lista_proc_prog);
    exit;
}
?>