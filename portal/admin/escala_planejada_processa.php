<?php
// admin/escala_planejada_processa.php
// ATUALIZADO para incluir Linha e Função Operacional, e validação de jornada condicional.

require_once 'auth_check.php';
require_once '../db_config.php';

// Permissões (mantenha como no seu original)
$niveis_permitidos_proc_escala = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_proc_escala)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para processar esta ação na Escala Planejada.";
    // Adicionar lógica de redirect_params se necessário
    header('Location: escala_planejada_listar.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_escala_planejada'])) {

    $escala_id = filter_input(INPUT_POST, 'escala_id', FILTER_VALIDATE_INT);

    // --- Novos campos para Tipo de Escala e Função Operacional ---
    $tipo_escala = trim($_POST['tipo_escala'] ?? 'linha'); // 'linha' ou 'funcao'
    $funcao_operacional_id_input = filter_input(INPUT_POST, 'funcao_operacional_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $funcao_operacional_id_val = $funcao_operacional_id_input ?: null;
    $turno_funcao_input = trim($_POST['turno_funcao'] ?? '');
    $posicao_letra_funcao_input = trim($_POST['posicao_letra_funcao'] ?? '');
    // --- Fim Novos campos ---

    $data_escala_str = trim($_POST['data_escala'] ?? '');
    $motorista_id = filter_input(INPUT_POST, 'motorista_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    
    $is_folga = isset($_POST['is_folga_check']);
    $is_falta = isset($_POST['is_falta_check']);
    $is_fora_escala = isset($_POST['is_fora_escala_check']);
    $is_ferias = isset($_POST['is_ferias_check']);
    $is_atestado = isset($_POST['is_atestado_check']);
    
    $work_id_input = trim($_POST['work_id'] ?? ''); // WorkID vindo do formulário
    $tabela_escalas_input = trim($_POST['tabela_escalas'] ?? '');
    // Agora aceita texto (ex: "209") e não valida mais como Inteiro
	$linha_origem_id_input = trim($_POST['linha_origem_id'] ?? '');
	$linha_origem_id_val = !empty($linha_origem_id_input) ? $linha_origem_id_input : null;
    
    $hora_inicio_str = trim($_POST['hora_inicio_prevista'] ?? '');
    $local_inicio_id_input = filter_input(INPUT_POST, 'local_inicio_turno_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $local_inicio_id_val = $local_inicio_id_input ?: null;
    
    $hora_fim_str = trim($_POST['hora_fim_prevista'] ?? '');
    $local_fim_id_input = filter_input(INPUT_POST, 'local_fim_turno_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $local_fim_id_val = $local_fim_id_input ?: null;
    
    $eh_extra_val = (isset($_POST['eh_extra']) && $_POST['eh_extra'] == '1') ? 1 : 0;
    $veiculo_id_val_input = filter_input(INPUT_POST, 'veiculo_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $veiculo_id_val = $veiculo_id_val_input ?: null;

    // Parâmetros para redirecionamento (manter lógica original)
    $redirect_query_params = [];
    if (isset($_POST['pagina_original'])) $redirect_query_params['pagina'] = $_POST['pagina_original'];
    if (isset($_POST['filtro_data_original'])) $redirect_query_params['data_escala'] = $_POST['filtro_data_original'];
    if (isset($_POST['filtro_tipo_busca_original'])) $redirect_query_params['tipo_busca_adicional'] = $_POST['filtro_tipo_busca_original'];
    if (isset($_POST['filtro_valor_busca_original'])) $redirect_query_params['valor_busca_adicional'] = $_POST['filtro_valor_busca_original'];
    $redirect_form_location = ($escala_id ? 'escala_planejada_formulario.php?id=' . $escala_id : 'escala_planejada_formulario.php');
    if (!empty($redirect_query_params)) {
        $redirect_form_location .= (strpos($redirect_form_location, '?') === false ? '?' : '&') . http_build_query($redirect_query_params);
    }
    $redirect_list_location = 'escala_planejada_listar.php' . (!empty($redirect_query_params) ? '?' . http_build_query($redirect_query_params) : '');

    $validation_errors = [];
    $data_obj = null;
    $funcao_data_db = null; // Para armazenar dados da função operacional
    $ignorar_validacao_jornada_final = false; // Padrão é validar

    if (empty($data_escala_str)) { $validation_errors[] = "Data da Escala é obrigatória."; }
    else {
        try { $data_obj = new DateTime($data_escala_str); if ($data_obj->format('Y-m-d') !== $data_escala_str) { throw new Exception(); }}
        catch (Exception $e) { $validation_errors[] = "Data da Escala inválida. Use AAAA-MM-DD."; $data_obj = null; }
    }
    if (empty($motorista_id)) { $validation_errors[] = "Motorista é obrigatório."; }

    // --- Lógica para Status Especiais (Folga, Falta, etc.) ---
    $is_status_especial = false;
    $work_id_to_save = $work_id_input; // WorkID final a ser salvo

    if ($is_folga) { $work_id_to_save = 'FOLGA'; $is_status_especial = true; }
    elseif ($is_falta) { $work_id_to_save = 'FALTA'; $is_status_especial = true; }
    elseif ($is_fora_escala) { $work_id_to_save = 'FORADEESCALA'; $is_status_especial = true; }
    elseif ($is_ferias) { $work_id_to_save = 'FÉRIAS'; $is_status_especial = true; }
    elseif ($is_atestado) { $work_id_to_save = 'ATESTADO'; $is_status_especial = true; }

    // --- Lógica específica para TIPO DE ESCALA (Linha ou Função) ---
    if (!$is_status_especial) {
        if ($tipo_escala === 'funcao') {
            if (empty($funcao_operacional_id_val)) {
                $validation_errors[] = "Função Operacional é obrigatória quando o tipo é 'Função'.";
            } else {
                if ($pdo) {
                    $stmt_funcao = $pdo->prepare("SELECT * FROM funcoes_operacionais WHERE id = :id_funcao");
                    $stmt_funcao->bindParam(':id_funcao', $funcao_operacional_id_val, PDO::PARAM_INT);
                    $stmt_funcao->execute();
                    $funcao_data_db = $stmt_funcao->fetch(PDO::FETCH_ASSOC);
                    if (!$funcao_data_db) {
                        $validation_errors[] = "Função Operacional selecionada é inválida.";
                    } else {
                        $ignorar_validacao_jornada_final = (bool)$funcao_data_db['ignorar_validacao_jornada'];
                    }
                }
            }
            $linha_origem_id_val = null;
            $veiculo_id_val = null;
            $tabela_escalas_input = null;

        } elseif ($tipo_escala === 'linha') {
            if (empty($work_id_input)) { $validation_errors[] = "WorkID é obrigatório para escala de linha."; }
            if (empty($linha_origem_id_val)) { $validation_errors[] = "Linha de Origem é obrigatória para escala de linha."; }
            if (empty($veiculo_id_val)) { $validation_errors[] = "Veículo é obrigatório para escala de linha."; }
            if (!empty($tabela_escalas_input) && (!ctype_digit($tabela_escalas_input) || strlen($tabela_escalas_input) !== 2)) {
                $validation_errors[] = "O campo 'Nº Tabela da Escala' deve conter exatamente 2 dígitos numéricos.";
            }
            $funcao_operacional_id_val = null;
            $ignorar_validacao_jornada_final = false;
        } else {
            $validation_errors[] = "Tipo de Escala inválido selecionado.";
        }
    } else {
        $ignorar_validacao_jornada_final = true;
    }
    // --- Fim Lógica Tipo de Escala ---


    // --- Validação de Horas ---
    $hora_inicio_for_db = null; $hora_fim_for_db = null;
    $current_start_dt = null; $current_end_dt = null;

    if (!$is_status_especial && !$ignorar_validacao_jornada_final) {
        if (empty($hora_inicio_str) || empty($hora_fim_str)) {
            $validation_errors[] = "Hora de Início e Hora Final são obrigatórias se não for um status especial ou função que ignora validação.";
        } else {
            try {
                $start_time_obj_val = new DateTime($hora_inicio_str);
                $end_time_obj_val = new DateTime($hora_fim_str);
                if (!$start_time_obj_val || $start_time_obj_val->format('H:i') !== $hora_inicio_str) { $validation_errors[] = "Formato Hora Início inválido."; }
                if (!$end_time_obj_val || $end_time_obj_val->format('H:i') !== $hora_fim_str) { $validation_errors[] = "Formato Hora Fim inválido."; }
                if ($start_time_obj_val && $end_time_obj_val && $data_obj) {
                    $current_start_dt = new DateTime($data_escala_str . ' ' . $start_time_obj_val->format('H:i:s'));
                    $current_end_dt = new DateTime($data_escala_str . ' ' . $end_time_obj_val->format('H:i:s'));
                    if ($current_end_dt <= $current_start_dt) { $current_end_dt->modify('+1 day'); }
                    $hora_inicio_for_db = $current_start_dt->format('H:i:s');
                    $hora_fim_for_db = $end_time_obj_val->format('H:i:s');
                }
            } catch (Exception $e) { $validation_errors[] = "Erro ao processar horas: " . $e->getMessage(); }
        }
    }

    // --- VALIDAÇÃO DE CONFLITO DE VEÍCULO (AJUSTADA) ---
    if (!$is_status_especial && $tipo_escala === 'linha' && $veiculo_id_val && $data_obj && $hora_inicio_for_db && $hora_fim_for_db && empty($validation_errors)) {
        if ($pdo) {
            try {
                $inicio_req_dt = new DateTime($data_escala_str . ' ' . $hora_inicio_for_db);
                $fim_req_dt = new DateTime($data_escala_str . ' ' . $hora_fim_for_db);
                if ($fim_req_dt <= $inicio_req_dt) { $fim_req_dt->modify('+1 day'); }

                // SQL simplificada, não precisa mais do JOIN com motoristas
                $sql_conflito = "SELECT id, hora_inicio_prevista, hora_fim_prevista FROM motorista_escalas WHERE data = :data AND veiculo_id = :veiculo_id";
                $params_conflito = [':data' => $data_escala_str, ':veiculo_id' => $veiculo_id_val];
                if ($escala_id) {
                    $sql_conflito .= " AND id != :escala_id_atual";
                    $params_conflito[':escala_id_atual'] = $escala_id;
                }
                $stmt_conflito = $pdo->prepare($sql_conflito);
                $stmt_conflito->execute($params_conflito);
                $escalas_conflitantes = $stmt_conflito->fetchAll(PDO::FETCH_ASSOC);

                foreach ($escalas_conflitantes as $conflito) {
                    if ($conflito['hora_inicio_prevista'] && $conflito['hora_fim_prevista']) {
                        $inicio_existente_dt = new DateTime($data_escala_str . ' ' . $conflito['hora_inicio_prevista']);
                        $fim_existente_dt = new DateTime($data_escala_str . ' ' . $conflito['hora_fim_prevista']);
                        if ($fim_existente_dt <= $inicio_existente_dt) { $fim_existente_dt->modify('+1 day'); }

                        if ($inicio_req_dt < $fim_existente_dt && $fim_req_dt > $inicio_existente_dt) {
                            $validation_errors[] = "Conflito de veículo! Este veículo já está alocado em um horário conflitante nesta data.";
                            break; 
                        }
                    }
                }
            } catch (Exception $e) {
                $validation_errors[] = "Erro ao verificar conflito de veículo no servidor.";
                error_log("Erro ao verificar conflito de veículo: " . $e->getMessage());
            }
        }
    }
    
    // --- LÓGICA DE VALIDAÇÃO DE JORNADA (COMPLETA) ---
    if ($pdo && !$is_status_especial && !$ignorar_validacao_jornada_final && $motorista_id && $data_obj && $current_start_dt && $current_end_dt && empty($validation_errors)) {
        $status_especiais_sql_array = ['FOLGA', 'FALTA', 'FORADEESCALA', 'FÉRIAS', 'ATESTADO'];
        $placeholders_para_status = implode(',', array_fill(0, count($status_especiais_sql_array), '?'));
        
        $query_params_other = [$motorista_id, $data_escala_str];
        $sql_other_shifts = "SELECT id, hora_inicio_prevista, hora_fim_prevista, eh_extra, funcao_operacional_id FROM motorista_escalas WHERE motorista_id = ? AND data = ?";
        if ($escala_id) {
            $sql_other_shifts .= " AND id != ?";
            $query_params_other[] = $escala_id;
        }
        $sql_other_shifts .= " AND (UPPER(work_id) NOT IN (" . $placeholders_para_status . ") AND (funcao_operacional_id IS NULL OR (SELECT fo.ignorar_validacao_jornada FROM funcoes_operacionais fo WHERE fo.id = motorista_escalas.funcao_operacional_id) = 0))";

        $final_query_params_other = array_merge($query_params_other, $status_especiais_sql_array);
        $stmt_other_shifts = $pdo->prepare($sql_other_shifts);
        $stmt_other_shifts->execute($final_query_params_other);
        $other_scales_on_day = $stmt_other_shifts->fetchAll(PDO::FETCH_ASSOC);

        if ($other_scales_on_day !== false) {
            if (!defined('MAX_NORMAL_SECONDS_DAY')) define('MAX_NORMAL_SECONDS_DAY', 6 * 3600 + 40 * 60);
            if (!defined('MAX_EXTRA_SECONDS_DAY')) define('MAX_EXTRA_SECONDS_DAY', 2 * 3600);
            if (!defined('MAX_TOTAL_SECONDS_DAY')) define('MAX_TOTAL_SECONDS_DAY', (6 * 3600 + 40 * 60) + (2*3600) );
            if (!defined('MAX_INTERVAL_SECONDS')) define('MAX_INTERVAL_SECONDS', 4 * 3600);
            if (!defined('MIN_INTERJORNADA_SECONDS')) define('MIN_INTERJORNADA_SECONDS', 11 * 3600);

            $total_normal_seconds_day = 0; $total_extra_seconds_day = 0; $all_shifts_for_day = [];
            $duration_current_shift_seconds = $current_end_dt->getTimestamp() - $current_start_dt->getTimestamp();

            if ($eh_extra_val) { $total_extra_seconds_day += $duration_current_shift_seconds; } else { $total_normal_seconds_day += $duration_current_shift_seconds; }
            $all_shifts_for_day[] = ['start' => $current_start_dt, 'end' => $current_end_dt];

            foreach ($other_scales_on_day as $other_shift_item) {
                if (isset($other_shift_item['hora_inicio_prevista']) && isset($other_shift_item['hora_fim_prevista'])) {
                    try {
                        $other_start_dt_loop = new DateTime($data_escala_str . ' ' . $other_shift_item['hora_inicio_prevista']);
                        $other_end_dt_loop = new DateTime($data_escala_str . ' ' . $other_shift_item['hora_fim_prevista']);
                        if ($other_end_dt_loop <= $other_start_dt_loop) { $other_end_dt_loop->modify('+1 day'); }
                        if ($other_end_dt_loop > $other_start_dt_loop) {
                            $other_duration_seconds = $other_end_dt_loop->getTimestamp() - $other_start_dt_loop->getTimestamp();
                            $other_shift_is_extra = isset($other_shift_item['eh_extra']) && $other_shift_item['eh_extra'] == 1;
                            if ($other_shift_is_extra) { $total_extra_seconds_day += $other_duration_seconds; } else { $total_normal_seconds_day += $other_duration_seconds; }
                            $all_shifts_for_day[] = ['start' => $other_start_dt_loop, 'end' => $other_end_dt_loop];
                        }
                    } catch (Exception $e) { $validation_errors[] = "Erro ao processar horários de pegas existentes (validação)."; break; }
                }
            }
            if (empty($validation_errors)) {
                $total_normal_hours_calc = round($total_normal_seconds_day / 3600, 2);
                $total_extra_hours_calc = round($total_extra_seconds_day / 3600, 2);
                $overall_total_hours_calc = round(($total_normal_seconds_day + $total_extra_seconds_day) / 3600, 2);
                if ($total_normal_seconds_day > MAX_NORMAL_SECONDS_DAY) { $validation_errors[] = "Jornada Normal ({$total_normal_hours_calc}h) excede o limite de " . (MAX_NORMAL_SECONDS_DAY/3600) . "h."; }
                if ($total_extra_seconds_day > MAX_EXTRA_SECONDS_DAY) { $validation_errors[] = "Jornada Extra ({$total_extra_hours_calc}h) excede o limite de " . (MAX_EXTRA_SECONDS_DAY/3600) . "h."; }
                if (($total_normal_seconds_day + $total_extra_seconds_day) > MAX_TOTAL_SECONDS_DAY) { $validation_errors[] = "Carga horária total ({$overall_total_hours_calc}h) excede o limite de " . (MAX_TOTAL_SECONDS_DAY/3600) . "h."; }
            }
            if (count($all_shifts_for_day) > 1 && empty($validation_errors)) {
                usort($all_shifts_for_day, function($a, $b) { return $a['start']->getTimestamp() - $b['start']->getTimestamp(); });
                for ($i = 0; $i < count($all_shifts_for_day) - 1; $i++) {
                    $end_ts = $all_shifts_for_day[$i]['end']->getTimestamp();
                    $start_ts_next = $all_shifts_for_day[$i+1]['start']->getTimestamp();
                    $interval_sec = $start_ts_next - $end_ts;
                    if ($interval_sec > MAX_INTERVAL_SECONDS) { $validation_errors[] = "Intervalo entre pegas excede " . (MAX_INTERVAL_SECONDS/3600) . "h."; break; }
                    if ($interval_sec < 0) { $validation_errors[] = "Sobreposição de horários detectada entre pegas."; break; }
                }
            }
        }
    }
    // --- Fim Validação de Jornada ---

    if (!empty($validation_errors)) {
        $_SESSION['admin_form_error_escala_p'] = implode("<br>", $validation_errors);
        $_SESSION['form_data_escala_planejada'] = $_POST;
        header('Location: ' . $redirect_form_location);
        exit;
    }

    try {
        $pdo->beginTransaction();
        if ($escala_id) {
            $sql_op = "UPDATE motorista_escalas SET data = :data, motorista_id = :motorista_id, work_id = :work_id, tabela_escalas = :tabela_escalas, linha_origem_id = :linha_origem_id, funcao_operacional_id = :funcao_operacional_id, hora_inicio_prevista = :hora_inicio, local_inicio_turno_id = :local_inicio, hora_fim_prevista = :hora_fim, local_fim_turno_id = :local_fim, eh_extra = :eh_extra, veiculo_id = :veiculo_id WHERE id = :escala_id_bind";
            $stmt_op = $pdo->prepare($sql_op);
            $stmt_op->bindParam(':escala_id_bind', $escala_id, PDO::PARAM_INT);
        } else {
            $sql_op = "INSERT INTO motorista_escalas (data, motorista_id, work_id, tabela_escalas, linha_origem_id, funcao_operacional_id, hora_inicio_prevista, local_inicio_turno_id, hora_fim_prevista, local_fim_turno_id, eh_extra, veiculo_id) VALUES (:data, :motorista_id, :work_id, :tabela_escalas, :linha_origem_id, :funcao_operacional_id, :hora_inicio, :local_inicio, :hora_fim, :local_fim, :eh_extra, :veiculo_id)";
            $stmt_op = $pdo->prepare($sql_op);
        }
        
        $tabela_to_save = ($is_status_especial || $tipo_escala === 'funcao') ? null : (!empty($tabela_escalas_input) ? $tabela_escalas_input : null);
        $linha_to_save = ($is_status_especial || $tipo_escala === 'funcao') ? null : $linha_origem_id_val;
        $funcao_id_to_save = ($is_status_especial || $tipo_escala === 'linha') ? null : $funcao_operacional_id_val;
        $veiculo_to_save = ($is_status_especial || $tipo_escala !== 'linha') ? null : $veiculo_id_val;
        $local_inicio_to_save = $is_status_especial ? null : $local_inicio_id_val;
        $local_fim_to_save = $is_status_especial ? null : $local_fim_id_val;
        $eh_extra_to_save = $is_status_especial ? 0 : $eh_extra_val;
        $hora_inicio_final_db = ($is_status_especial || $ignorar_validacao_jornada_final) ? null : $hora_inicio_for_db;
        $hora_fim_final_db = ($is_status_especial || $ignorar_validacao_jornada_final) ? null : $hora_fim_for_db;

        if ($tipo_escala === 'funcao' && $ignorar_validacao_jornada_final && !$is_status_especial) {
            if (!empty($hora_inicio_str)) $hora_inicio_final_db = DateTime::createFromFormat('H:i', $hora_inicio_str)->format('H:i:s'); else $hora_inicio_final_db = null;
            if (!empty($hora_fim_str)) $hora_fim_final_db = DateTime::createFromFormat('H:i', $hora_fim_str)->format('H:i:s'); else $hora_fim_final_db = null;
        }

        // Binds
        $stmt_op->bindParam(':data', $data_escala_str, PDO::PARAM_STR);
        $stmt_op->bindParam(':motorista_id', $motorista_id, PDO::PARAM_INT);
        $stmt_op->bindParam(':work_id', $work_id_to_save, PDO::PARAM_STR);
        $stmt_op->bindParam(':tabela_escalas', $tabela_to_save, $tabela_to_save === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt_op->bindParam(':linha_origem_id', $linha_to_save, $linha_to_save === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt_op->bindParam(':funcao_operacional_id', $funcao_id_to_save, $funcao_id_to_save === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt_op->bindParam(':hora_inicio', $hora_inicio_final_db, $hora_inicio_final_db === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt_op->bindParam(':local_inicio', $local_inicio_to_save, $local_inicio_to_save === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt_op->bindParam(':hora_fim', $hora_fim_final_db, $hora_fim_final_db === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt_op->bindParam(':local_fim', $local_fim_to_save, $local_fim_to_save === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt_op->bindParam(':eh_extra', $eh_extra_to_save, PDO::PARAM_INT);
        $stmt_op->bindParam(':veiculo_id', $veiculo_to_save, $veiculo_to_save === null ? PDO::PARAM_NULL : PDO::PARAM_INT);

        if ($stmt_op->execute()) {
            $pdo->commit();
            $_SESSION['admin_success_message'] = "Entrada da escala planejada " . ($escala_id ? "atualizada" : "cadastrada") . " com sucesso!";
        } else {
            $pdo->rollBack();
            $errorInfoOp = $stmt_op->errorInfo();
            $error_message_user = "Erro ao salvar entrada da escala.";
            if (isset($errorInfoOp[1]) && $errorInfoOp[1] == 1062) {
                $error_message_user .= " Possível entrada duplicada.";
            }
            $_SESSION['admin_error_message'] = $error_message_user;
        }

    } catch (PDOException $e) {
        if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['admin_error_message'] = "Erro crítico de banco de dados ao salvar a escala.";
    }

    header('Location: ' . $redirect_list_location);
    exit;
} else {
    $_SESSION['admin_error_message'] = "Acesso inválido.";
    header('Location: escala_planejada_listar.php');
    exit;
}
?>