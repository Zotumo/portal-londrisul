<?php
// admin/linha_processa.php
// ATUALIZADO: Processa os tipos de veículo permitidos e salva na tabela de associação.

require_once 'auth_check.php';

// --- Permissões (manter consistência com o formulário) ---
$niveis_permitidos_processar_linhas = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_processar_linhas)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para salvar dados de linhas.";
    header('Location: linhas_listar.php');
    exit;
}

require_once '../db_config.php';

$pasta_upload_servidor = dirname(__DIR__) . '/img/pontos/'; // Caminho no servidor

// --- Parâmetros de retorno para listagem (mantendo filtros) ---
$params_retorno_lista_array_lp_proc = [];
$param_sources_lp_proc = [$_POST, $_GET]; 
foreach ($param_sources_lp_proc as $source_lp_proc) {
    if (isset($source_lp_proc['pagina']) && empty($params_retorno_lista_array_lp_proc['pagina'])) $params_retorno_lista_array_lp_proc['pagina'] = $source_lp_proc['pagina'];
    if (isset($source_lp_proc['busca_numero']) && empty($params_retorno_lista_array_lp_proc['busca_numero'])) $params_retorno_lista_array_lp_proc['busca_numero'] = $source_lp_proc['busca_numero'];
    if (isset($source_lp_proc['busca_nome']) && empty($params_retorno_lista_array_lp_proc['busca_nome'])) $params_retorno_lista_array_lp_proc['busca_nome'] = $source_lp_proc['busca_nome'];
    if (isset($source_lp_proc['status_filtro']) && empty($params_retorno_lista_array_lp_proc['status_filtro'])) $params_retorno_lista_array_lp_proc['status_filtro'] = $source_lp_proc['status_filtro'];
}
$query_string_retorno_proc_lp = http_build_query($params_retorno_lista_array_lp_proc);
$link_voltar_lista_proc_lp = 'linhas_listar.php' . ($query_string_retorno_proc_lp ? '?' . $query_string_retorno_proc_lp : '');


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_linha'])) {

    $linha_id = filter_input(INPUT_POST, 'linha_id', FILTER_VALIDATE_INT);
    $numero_linha = trim($_POST['numero_linha'] ?? '');
    $nome_linha = trim($_POST['nome_linha'] ?? '');
    $status_linha = trim($_POST['status_linha'] ?? 'ativa');
    
    // NOVO: Receber os tipos de veículo permitidos. Será um array.
    $tipos_veiculo_permitidos_post = $_POST['tipos_veiculo_permitidos'] ?? [];
    if (!is_array($tipos_veiculo_permitidos_post)) { // Segurança básica
        $tipos_veiculo_permitidos_post = [];
    }

    $remover_imagem_ida_chk = isset($_POST['remover_imagem_ida']) && $_POST['remover_imagem_ida'] == '1';
    $remover_imagem_volta_chk = isset($_POST['remover_imagem_volta']) && $_POST['remover_imagem_volta'] == '1';

    $imagem_ida_nome_db = null;
    $imagem_volta_nome_db = null;
    $erros_validacao_linha = [];

    // --- Validações ---
    if (empty($numero_linha)) { $erros_validacao_linha[] = "O Número da Linha é obrigatório."; }
    if (empty($nome_linha)) { $erros_validacao_linha[] = "O Nome da Linha é obrigatório."; }
    if (!in_array($status_linha, ['ativa', 'inativa'])) {
        $erros_validacao_linha[] = "Status da linha inválido.";
        $status_linha = 'ativa';
    }
    // NOVO: Validar se pelo menos um tipo de veículo foi selecionado
    if (empty($tipos_veiculo_permitidos_post)) {
        $erros_validacao_linha[] = "Selecione pelo menos um Tipo de Veículo Permitido para esta linha.";
    } else {
        // Opcional: Validar se os valores em $tipos_veiculo_permitidos_post são válidos (correspondem ao ENUM)
        $todos_tipos_veiculo_validos = [
            'Convencional Amarelo', 'Convencional Amarelo com Ar', 'Micro', 'Micro com Ar',
            'Convencional Azul', 'Convencional Azul com Ar', 'Padron Azul', 'SuperBus', 'Leve'
        ];
        foreach ($tipos_veiculo_permitidos_post as $tipo_selecionado) {
            if (!in_array($tipo_selecionado, $todos_tipos_veiculo_validos)) {
                $erros_validacao_linha[] = "Tipo de veículo permitido inválido detectado: " . htmlspecialchars($tipo_selecionado);
                break; 
            }
        }
    }


    // Verificar duplicidade de Número da Linha
    if ($pdo && !empty($numero_linha)) {
        try {
            $sql_check_num = "SELECT id FROM linhas WHERE numero = :numero";
            $params_check_num = [':numero' => $numero_linha];
            if ($linha_id) { 
                $sql_check_num .= " AND id != :id_linha";
                $params_check_num[':id_linha'] = $linha_id;
            }
            $stmt_check_num = $pdo->prepare($sql_check_num);
            $stmt_check_num->execute($params_check_num);
            if ($stmt_check_num->fetch()) {
                $erros_validacao_linha[] = "Já existe uma linha com o número '" . htmlspecialchars($numero_linha) . "'.";
            }
        } catch (PDOException $e_num) { 
            error_log("Erro ao verificar duplicidade de número de linha: " . $e_num->getMessage());
            $erros_validacao_linha[] = "Erro ao verificar dados da linha. Tente novamente.";
        }
    } elseif (!$pdo && empty($erros_validacao_linha)) {
         $erros_validacao_linha[] = "Erro de conexão com o banco de dados para validações.";
    }

    // --- Processamento de Imagens (mantém sua lógica anterior) ---
    function processarUploadImagemLinha($file_input_name, $remover_chk, $imagem_atual_db_path, $pasta_upload, &$nome_imagem_para_db_ref, &$erros_array_ref, $campo_label) {
        if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == UPLOAD_ERR_OK) {
            if (!is_dir($pasta_upload) && !mkdir($pasta_upload, 0775, true) && !is_dir($pasta_upload)) { $erros_array_ref[] = "Erro ao criar diretório para {$campo_label}."; return; }
            if (!is_writable($pasta_upload)) { $erros_array_ref[] = "Diretório para {$campo_label} não tem permissão de escrita."; return; }

            $nome_arquivo_original = basename($_FILES[$file_input_name]['name']);
            $extensao = strtolower(pathinfo($nome_arquivo_original, PATHINFO_EXTENSION));
            $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif'];
            $tamanho_maximo = 2 * 1024 * 1024; // 2MB

            if (!in_array($extensao, $tipos_permitidos)) { $erros_array_ref[] = "{$campo_label}: Formato inválido (apenas JPG, PNG, GIF)."; return;}
            if ($_FILES[$file_input_name]['size'] > $tamanho_maximo) { $erros_array_ref[] = "{$campo_label}: Imagem excede 2MB."; return; }
            
            if (!empty($imagem_atual_db_path) && file_exists($pasta_upload . $imagem_atual_db_path)) {
                unlink($pasta_upload . $imagem_atual_db_path);
            }
            $novo_nome_arquivo = "linha_" . uniqid() . "_" . time() . "_" . rand(1000,9999) . "." . $extensao;
            if (move_uploaded_file($_FILES[$file_input_name]['tmp_name'], $pasta_upload . $novo_nome_arquivo)) {
                $nome_imagem_para_db_ref = $novo_nome_arquivo;
            } else { $erros_array_ref[] = "Erro ao fazer upload da {$campo_label}."; return; }

        } elseif ($remover_chk && !empty($imagem_atual_db_path)) {
            if (file_exists($pasta_upload . $imagem_atual_db_path)) {
                unlink($pasta_upload . $imagem_atual_db_path);
            }
            $nome_imagem_para_db_ref = null; 
        } elseif (!empty($imagem_atual_db_path) && !$remover_chk) {
            $nome_imagem_para_db_ref = $imagem_atual_db_path; // Mantém a imagem atual
        } else {
            $nome_imagem_para_db_ref = null; // Nenhuma nova, nenhuma atual ou marcada para remover
        }
    }

    $db_imagem_ida_path = null; $db_imagem_volta_path = null;
    if ($linha_id && $pdo) {
        try {
            $stmt_img_paths = $pdo->prepare("SELECT imagem_ponto_ida_path, imagem_ponto_volta_path FROM linhas WHERE id = :id_linha_img_p");
            $stmt_img_paths->bindParam(':id_linha_img_p', $linha_id, PDO::PARAM_INT);
            $stmt_img_paths->execute();
            $paths_db = $stmt_img_paths->fetch(PDO::FETCH_ASSOC);
            if ($paths_db) { $db_imagem_ida_path = $paths_db['imagem_ponto_ida_path']; $db_imagem_volta_path = $paths_db['imagem_ponto_volta_path']; }
        } catch (PDOException $e_img_p) { 
            error_log("Erro ao buscar paths de imagens existentes para linha ID {$linha_id}: " . $e_img_p->getMessage());
            $erros_validacao_linha[] = "Erro ao processar imagens existentes.";
        }
    }
    // Só processa uploads se não houver erros de validação anteriores (exceto os de imagem em si)
    if (empty(array_filter($erros_validacao_linha, function($err) { return strpos(strtolower($err), 'imagem') === false; }))) {
        processarUploadImagemLinha('imagem_ponto_ida', $remover_imagem_ida_chk, $db_imagem_ida_path, $pasta_upload_servidor, $imagem_ida_nome_db, $erros_validacao_linha, "Imagem Ponto Ida");
        processarUploadImagemLinha('imagem_ponto_volta', $remover_imagem_volta_chk, $db_imagem_volta_path, $pasta_upload_servidor, $imagem_volta_nome_db, $erros_validacao_linha, "Imagem Ponto Volta");
    }
    
    // Validação final de obrigatoriedade das imagens (só se não for edição e não houver já uma imagem)
    if (!$linha_id && empty($imagem_ida_nome_db)) { $erros_validacao_linha[] = "A Imagem do Ponto Ida é obrigatória para novas linhas."; }
    if (!$linha_id && empty($imagem_volta_nome_db)) { $erros_validacao_linha[] = "A Imagem do Ponto Volta é obrigatória para novas linhas."; }
    // Para edição, se não houver nova e não removeu, $imagem_xxx_nome_db terá o path antigo. Se removeu, será null.
    // Se removeu e não enviou nova, e o campo é obrigatório, a validação abaixo pegará.
    if ($linha_id && $remover_imagem_ida_chk && empty($_FILES['imagem_ponto_ida']['name'])) { $erros_validacao_linha[] = "A Imagem do Ponto Ida é obrigatória (você marcou para remover a atual e não enviou uma nova)."; }
    if ($linha_id && $remover_imagem_volta_chk && empty($_FILES['imagem_ponto_volta']['name'])) { $erros_validacao_linha[] = "A Imagem do Ponto Volta é obrigatória (você marcou para remover a atual e não enviou uma nova)."; }


    // Se houver erros de validação, redireciona de volta ao formulário
    if (!empty($erros_validacao_linha)) {
        $_SESSION['admin_form_error_linha'] = implode("<br>", $erros_validacao_linha);
        $repop_data_linha_err = $_POST; // Pega todos os dados do POST para repopular
        // As imagens não são repopuladas, o usuário precisaria reenviá-las.
        // Os checkboxes de remoção serão repopulados pelo value='1' no HTML se o POST contiver a chave.
        $_SESSION['form_data_linha'] = $repop_data_linha_err; 
        
        $redirect_url_form_lp_err_val = $linha_id ? 'linha_formulario.php?id=' . $linha_id : 'linha_formulario.php';
        $redirect_url_form_lp_err_val .= ($query_string_retorno_proc_lp ? (strpos($redirect_url_form_lp_err_val, '?') === false ? '?' : '&') . $query_string_retorno_proc_lp : '');
        
        header('Location: ' . $redirect_url_form_lp_err_val);
        exit;
    }

    // --- Processar no Banco de Dados ---
    if ($pdo) {
        try {
            $pdo->beginTransaction(); // Inicia a transação

            // 1. Salvar/Atualizar dados na tabela `linhas`
            if ($linha_id) { // Modo EDIÇÃO
                $sql_linha_op = "UPDATE linhas SET numero = :numero, nome = :nome, status_linha = :status_linha, 
                                       imagem_ponto_ida_path = :img_ida, imagem_ponto_volta_path = :img_volta
                                 WHERE id = :id_linha_upd";
                $stmt_linha_op = $pdo->prepare($sql_linha_op);
                $stmt_linha_op->bindParam(':id_linha_upd', $linha_id, PDO::PARAM_INT);
            } else { // Modo CADASTRO
                $sql_linha_op = "INSERT INTO linhas (numero, nome, status_linha, imagem_ponto_ida_path, imagem_ponto_volta_path) 
                                 VALUES (:numero, :nome, :status_linha, :img_ida, :img_volta)";
                $stmt_linha_op = $pdo->prepare($sql_linha_op);
            }

            $stmt_linha_op->bindParam(':numero', $numero_linha, PDO::PARAM_STR);
            $stmt_linha_op->bindParam(':nome', $nome_linha, PDO::PARAM_STR);
            $stmt_linha_op->bindParam(':status_linha', $status_linha, PDO::PARAM_STR);
            $stmt_linha_op->bindParam(':img_ida', $imagem_ida_nome_db, $imagem_ida_nome_db === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt_linha_op->bindParam(':img_volta', $imagem_volta_nome_db, $imagem_volta_nome_db === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            
            $stmt_linha_op->execute();
            
            $linha_id_operacao = $linha_id ? $linha_id : $pdo->lastInsertId(); // Pega o ID da linha (novo ou existente)

            // 2. Gerenciar associações na tabela `linha_tipos_veiculo_permitidos`
            if ($linha_id_operacao) {
                // Primeiro, remove todas as associações existentes para esta linha (se estiver editando)
                // Isso simplifica a lógica: sempre remove o antigo e insere o novo conjunto.
                $stmt_delete_assoc = $pdo->prepare("DELETE FROM linha_tipos_veiculo_permitidos WHERE linha_id = :linha_id_del_assoc");
                $stmt_delete_assoc->bindParam(':linha_id_del_assoc', $linha_id_operacao, PDO::PARAM_INT);
                $stmt_delete_assoc->execute();

                // Depois, insere as novas associações selecionadas
                if (!empty($tipos_veiculo_permitidos_post)) {
                    $sql_insert_assoc = "INSERT INTO linha_tipos_veiculo_permitidos (linha_id, tipo_veiculo) VALUES (:linha_id_ins_assoc, :tipo_v_ins_assoc)";
                    $stmt_insert_assoc = $pdo->prepare($sql_insert_assoc);
                    $stmt_insert_assoc->bindParam(':linha_id_ins_assoc', $linha_id_operacao, PDO::PARAM_INT);
                    
                    foreach ($tipos_veiculo_permitidos_post as $tipo_permitido_para_inserir) {
                        $stmt_insert_assoc->bindParam(':tipo_v_ins_assoc', $tipo_permitido_para_inserir, PDO::PARAM_STR); // ENUM é string
                        $stmt_insert_assoc->execute();
                    }
                }
            }

            $pdo->commit(); // Confirma todas as operações no banco
            $mensagem_sucesso_linha_final = $linha_id ? "Linha '" . htmlspecialchars($numero_linha) . "' atualizada com sucesso!" : "Nova linha '" . htmlspecialchars($numero_linha) . "' cadastrada com sucesso!";
            $_SESSION['admin_success_message'] = $mensagem_sucesso_linha_final;
            header('Location: ' . $link_voltar_lista_proc_lp);
            exit;

        } catch (PDOException $e_linha_save) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack(); // Desfaz em caso de erro
            }
            error_log("Erro PDO ao processar linha e tipos de veículo: " . $e_linha_save->getMessage());
            $_SESSION['admin_error_message'] = "Erro de banco de dados ao salvar a linha. Detalhes: " . $e_linha_save->getMessage();
        }
    } else {
        $_SESSION['admin_error_message'] = "Falha na conexão com o banco. Linha não foi salva.";
    }

    // Fallback redirect para o formulário em caso de erro PDO pós-validação
    $_SESSION['form_data_linha'] = $_POST; // Repopula com o POST original
    $redirect_url_form_lp_err_pdo = $linha_id ? 'linha_formulario.php?id=' . $linha_id : 'linha_formulario.php';
    $redirect_url_form_lp_err_pdo .= ($query_string_retorno_proc_lp ? (strpos($redirect_url_form_lp_err_pdo, '?') === false ? '?' : '&') . $query_string_retorno_proc_lp : '');
    header('Location: ' . $redirect_url_form_lp_err_pdo);
    exit;

} else {
    $_SESSION['admin_error_message'] = "Acesso inválido ao processamento de linhas.";
    header('Location: ' . $link_voltar_lista_proc_lp); // Redireciona para a lista
    exit;
}
?>