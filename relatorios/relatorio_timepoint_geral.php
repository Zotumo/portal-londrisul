 <?php
 // =================================================================
 //  Parceiro de Programação - Relatório Timepoint Geral (v11.2)
 //  - Corrigido problema de cores no gráfico de status (Chart.js)
 //  - Adicionada seção de comparação de Índices Totais (Calculado vs. CAD)
 //  - Limita detalhes por motorista a 50 registos (performance)
 //  - Adiciona seleção de tolerância (-1/+3 vs -2/+6)
 //  - Implementa status 'Não Login' e 'Sem Dados'
 //  - Adiciona seção de Desempenho por Sentido
 //  - Adiciona colunas Bloco, WorkID, Sentido/Via, Direção
 //  - Adiciona seções de listagem para Não Login e Sem Dados
 //  - Atualiza chamadas para formatarDesvioExcedente e getCorDesvio
 // =================================================================
 
 require 'config_timepoint.php'; // Usa a v2.0 com funções atualizadas
 
 // --- LÓGICA DE FILTROS ---
 $visao_selecionada = $_GET['visao'] ?? 'geral';
 $aba_selecionada = $_GET['aba'] ?? 'geral';
 $linha_selecionada = $_GET['linha'] ?? 'todas';
 $tolerancia_selecionada = $_GET['tolerancia'] ?? 'padrao'; // 'padrao' ou 'alternativa'
 
 // Define os limites de tempo com base na seleção
 if ($tolerancia_selecionada === 'alternativa') {
     $limite_adiantado_str = '-00:01:00'; // -1 minuto
     $limite_atraso_str = '00:03:00';    // +3 minutos
     $tolerancia_atraso_segundos = 3 * 60;
     $tolerancia_adiantado_segundos = 1 * 60;
     $titulo_tolerancia = "-1 / +3 min";
 } else {
     $limite_adiantado_str = '-00:02:00'; // -2 minutos (Padrão)
     $limite_atraso_str = '00:06:00';    // +6 minutos (Padrão)
     $tolerancia_atraso_segundos = 6 * 60;
     $tolerancia_adiantado_segundos = 2 * 60;
     $titulo_tolerancia = "-2 / +6 min (Padrão)";
 }
 $limite_supressao_str = '00:15:00'; // +15 minutos (Constante)
 
 $data_ontem = date('Y-m-d', strtotime('-1 day'));
 $intervalo_datas_db_query = "SELECT MIN(DATE(horario_programado)) as min_date, MAX(DATE(horario_programado)) as max_date FROM registros_timepoint_geral";
 $intervalo_datas_db_result = $conexao->query($intervalo_datas_db_query);
 $intervalo_datas_db = $intervalo_datas_db_result ? $intervalo_datas_db_result->fetch_assoc() : ['min_date' => null, 'max_date' => null];
 
 
 $data_inicio = $_GET['data_inicio'] ?? ($intervalo_datas_db['min_date'] ?? $data_ontem);
 $data_fim = $_GET['data_fim'] ?? ($intervalo_datas_db['max_date'] ?? $data_ontem);
 
 
 $where_conditions = [];
 
 if ($visao_selecionada === 'chegadas') {
     $where_conditions[] = "is_last_tp = 1";
     $titulo_visao = "Chegadas";
     $titulo_visao_coluna = "Chegadas";
 } elseif ($visao_selecionada === 'partidas') {
     $where_conditions[] = "is_last_tp = 0";
     $titulo_visao = "Partidas";
     $titulo_visao_coluna = "Partidas";
 } else {
     $titulo_visao = "Geral (Chegadas e Partidas)";
     $titulo_visao_coluna = "Registos";
 }
 
 $titulo_periodo = "Geral";
 switch ($aba_selecionada) {
     case 'uteis': $where_conditions[] = "WEEKDAY(horario_programado) BETWEEN 0 AND 4"; $titulo_periodo = "Dias Úteis"; break;
     case 'sabados': $where_conditions[] = "WEEKDAY(horario_programado) = 5"; $titulo_periodo = "Sábados"; break;
     case 'domingos': $where_conditions[] = "WEEKDAY(horario_programado) = 6"; $titulo_periodo = "Domingos"; break;
 }
 
 $titulo_linha = "Todas as Linhas";
 if ($linha_selecionada !== 'todas' && !empty($linha_selecionada)) {
     $where_conditions[] = "route = '" . $conexao->real_escape_string($linha_selecionada) . "'";
     $titulo_linha = "Linha " . htmlspecialchars($linha_selecionada);
 }
 
 if (!empty($data_inicio) && !empty($data_fim)) {
     // Validação básica das datas para evitar SQL injection e erros
     $data_inicio_obj = DateTime::createFromFormat('Y-m-d', $data_inicio);
     $data_fim_obj = DateTime::createFromFormat('Y-m-d', $data_fim);
     if ($data_inicio_obj && $data_fim_obj && $data_inicio_obj <= $data_fim_obj) {
         $where_conditions[] = "DATE(horario_programado) BETWEEN '" . $conexao->real_escape_string($data_inicio) . "' AND '" . $conexao->real_escape_string($data_fim) . "'";
     } else {
         // Se as datas forem inválidas, usar um intervalo padrão ou mostrar erro
         // Por agora, vamos usar a data de ontem como fallback seguro
         $data_inicio = $data_ontem;
         $data_fim = $data_ontem;
         $where_conditions[] = "DATE(horario_programado) = '" . $conexao->real_escape_string($data_ontem) . "'";
         // Adicionar uma mensagem de erro seria ideal aqui
     }
 } elseif (!empty($data_inicio)) {
      $data_inicio_obj = DateTime::createFromFormat('Y-m-d', $data_inicio);
      if($data_inicio_obj){
          $data_fim = $data_inicio; // Se só tem data início, filtra por esse dia
          $where_conditions[] = "DATE(horario_programado) = '" . $conexao->real_escape_string($data_inicio) . "'";
      } else {
           $data_inicio = $data_ontem;
           $data_fim = $data_ontem;
           $where_conditions[] = "DATE(horario_programado) = '" . $conexao->real_escape_string($data_ontem) . "'";
      }
 } else {
     // Fallback se nenhuma data for fornecida
     $data_inicio = $data_ontem;
     $data_fim = $data_ontem;
     $where_conditions[] = "DATE(horario_programado) = '" . $conexao->real_escape_string($data_ontem) . "'";
 }
 
 
 $where_clause = "";
 if (!empty($where_conditions)) {
     $where_clause = "WHERE " . implode(' AND ', $where_conditions);
 }
 
 
 // --- LÓGICA DE CÁLCULO E QUERIES ---
 
 // SQL Base com a NOVA lógica de status_calculado e tolerâncias dinâmicas
 $base_classificacao_sql = "
     FROM (
         SELECT 
             *,
             -- Calcula o desvio apenas se horario_real não for nulo
             CASE 
                 WHEN horario_real IS NOT NULL THEN TIMEDIFF(horario_real, horario_programado) 
                 ELSE NULL 
             END as desvio,
             
             -- Nova Lógica de Status Calculado
             (CASE 
                 WHEN operator = 'No Badge Provided' THEN 'Não Login' -- Prioridade 1
                 WHEN horario_real IS NULL THEN 'Sem Dados'           -- Prioridade 2
                 WHEN TIMEDIFF(horario_real, horario_programado) > '{$limite_supressao_str}' THEN 'Supressão' 
                 WHEN TIMEDIFF(horario_real, horario_programado) >= '{$limite_atraso_str}' THEN 'Atrasado' 
                 WHEN TIMEDIFF(horario_real, horario_programado) <= '{$limite_adiantado_str}' THEN 'Adiantado' 
                 ELSE 'No Horário' -- Restante dos casos com horario_real
             END) AS status_calculado,
 
             -- Faixa Horária (sem alteração)
             (CASE 
                 WHEN HOUR(horario_programado) BETWEEN 6 AND 8 THEN 'Pico Manhã (06:00-08:59)' 
                 WHEN HOUR(horario_programado) BETWEEN 9 AND 10 THEN 'Entrepico Manhã (09:00-10:59)' 
                 WHEN HOUR(horario_programado) BETWEEN 11 AND 13 THEN 'Horário Escolar (11:00-13:59)' 
                 WHEN HOUR(horario_programado) BETWEEN 14 AND 15 THEN 'Início Tarde (14:00-15:59)' 
                 WHEN HOUR(horario_programado) BETWEEN 16 AND 19 THEN 'Pico Tarde (16:00-19:00)' 
                 WHEN HOUR(horario_programado) >= 19 THEN 'Fim Noite (19:01-23:59)' 
                 ELSE 'Madrugada' 
             END) AS faixa_horaria
         FROM registros_timepoint_geral
         {$where_clause}
     ) AS dados_classificados
 ";
 
 // --- QUERIES PARA ANÁLISE DIÁRIA E TOTAIS DO CAD (mantidas como antes) ---
 $where_datas_resumo = "WHERE data_relatorio BETWEEN '{$conexao->real_escape_string($data_inicio)}' AND '{$conexao->real_escape_string($data_fim)}'";
 $sql_sistema_icv = "SELECT * FROM relatorios_icv_ipv {$where_datas_resumo}";
 $result_sistema_icv = $conexao->query($sql_sistema_icv);
 $dados_sistema_icv = [];
 $totais_cad_icv = ['weighted_sum' => 0, 'total_trips' => 0];
 if ($result_sistema_icv) {
     while($row = $result_sistema_icv->fetch_assoc()) {
         $dados_sistema_icv[$row['data_relatorio']] = $row;
         // Usa ?? 0 para evitar erros se as colunas não existirem ou forem NULL
         $icv_percent = $row['icv_actual_percent'] ?? 0;
         $viagens = $row['viagens_programadas'] ?? 0;
         if (is_numeric($icv_percent) && is_numeric($viagens) && $viagens > 0) {
              $totais_cad_icv['weighted_sum'] += $icv_percent * $viagens;
              $totais_cad_icv['total_trips'] += $viagens;
         }
     }
 }
 
 $sql_sistema_ontime = "SELECT * FROM relatorios_on_time {$where_datas_resumo}";
 $result_sistema_ontime = $conexao->query($sql_sistema_ontime);
 $dados_sistema_ontime = [];
 $totais_cad_ontime = ['weighted_sum' => 0, 'total_points' => 0];
 if($result_sistema_ontime){
     while($row = $result_sistema_ontime->fetch_assoc()) {
         $dados_sistema_ontime[$row['data_relatorio']] = $row;
         $ontime_percent = $row['no_horario_percent'] ?? 0;
         $points = $row['timepoints_processados'] ?? 0;
         if (is_numeric($ontime_percent) && is_numeric($points) && $points > 0) {
             $totais_cad_ontime['weighted_sum'] += $ontime_percent * $points;
             $totais_cad_ontime['total_points'] += $points;
         }
     }
 }
 
 $sql_calculado_diario = "
     SELECT 
         DATE(horario_programado) as data,
         COUNT(*) as total_registros,
         COUNT(CASE WHEN status_calculado NOT IN ('Supressão', 'Sem Dados', 'Não Login') THEN 1 END) as viagens_cumpridas, -- Ajustado para incluir Sem Dados/Não Login
         COUNT(CASE WHEN status_calculado = 'No Horário' THEN 1 END) as total_no_horario
     " . $base_classificacao_sql . "
     GROUP BY data
     ORDER BY data ASC;
 ";
 $result_calculado_diario = $conexao->query($sql_calculado_diario);
 $dados_calculados_diario = [];
 if($result_calculado_diario){
     while($row = $result_calculado_diario->fetch_assoc()) {
         $row['icv_percent'] = ($row['total_registros'] > 0) ? round(($row['viagens_cumpridas'] / $row['total_registros']) * 100, 2) : 0;
         // IPV agora considera apenas cumpridas (exclui Supressão, Sem Dados, Não Login)
         $row['ipv_percent'] = ($row['viagens_cumpridas'] > 0) ? round(($row['total_no_horario'] / $row['viagens_cumpridas']) * 100, 2) : 0;
         $dados_calculados_diario[$row['data']] = $row;
     }
 }
 
 // Calcula médias totais CAD
 $icv_total_cad = ($totais_cad_icv['total_trips'] > 0) ? round($totais_cad_icv['weighted_sum'] / $totais_cad_icv['total_trips'], 1) : 0; // Arredonda para 1 casa decimal
 $ipv_total_cad = ($totais_cad_ontime['total_points'] > 0) ? round($totais_cad_ontime['weighted_sum'] / $totais_cad_ontime['total_points'], 1) : 0; // Arredonda para 1 casa decimal
 
 // --- FIM DAS QUERIES DE COMPARAÇÃO ---
 
 
 // Índices de Desempenho (ICV e IPV) - CALCULADO com nova lógica
 $sql_indices = "SELECT 
     COUNT(*) as total_registros, 
     COUNT(CASE WHEN status_calculado NOT IN ('Supressão', 'Sem Dados', 'Não Login') THEN 1 END) as viagens_cumpridas, 
     COUNT(CASE WHEN status_calculado = 'No Horário' THEN 1 END) as viagens_no_horario 
 " . $base_classificacao_sql;
 $result_indices = $conexao->query($sql_indices);
 $stats_indices = $result_indices ? $result_indices->fetch_assoc() : ['total_registros' => 0, 'viagens_cumpridas' => 0, 'viagens_no_horario' => 0];
 
 $icv_calculado = ($stats_indices['total_registros'] > 0) ? round(($stats_indices['viagens_cumpridas'] / $stats_indices['total_registros']) * 100, 1) : 0;
 $ipv_calculado = ($stats_indices['viagens_cumpridas'] > 0) ? round(($stats_indices['viagens_no_horario'] / $stats_indices['viagens_cumpridas']) * 100, 1) : 0;
 
 // Distribuição de Status (Incluindo Não Login e Sem Dados)
 $sql_dist_status = "SELECT status_calculado, COUNT(*) as total " . $base_classificacao_sql . " GROUP BY status_calculado;";
 $result_dist_status = $conexao->query($sql_dist_status);
 // Garante que todas as categorias existem, mesmo que com 0
 $stats_distribuicao = ['No Horário' => 0, 'Atrasado' => 0, 'Adiantado' => 0, 'Supressão' => 0, 'Sem Dados' => 0, 'Não Login' => 0];
 if($result_dist_status){
     while($item = $result_dist_status->fetch_assoc()) {
         if(isset($stats_distribuicao[$item['status_calculado']])) {
             $stats_distribuicao[$item['status_calculado']] = (int)$item['total']; // Garante que é inteiro
         }
     }
 }
 $total_registros_geral = array_sum($stats_distribuicao); // Total para percentagens
 
 
 // Desempenho por Linhas
 $stats_por_linha = [];
 if ($linha_selecionada === 'todas') {
     $sql_linhas = "
         SELECT 
             route, 
             COUNT(*) as total_viagens, 
             COUNT(CASE WHEN status_calculado = 'No Horário' THEN 1 END) as total_no_horario, 
             COUNT(CASE WHEN status_calculado = 'Atrasado' THEN 1 END) as total_atrasado, 
             SEC_TO_TIME(AVG(CASE WHEN status_calculado = 'Atrasado' THEN TIME_TO_SEC(desvio) END)) as media_atraso,
             COUNT(CASE WHEN status_calculado = 'Adiantado' THEN 1 END) as total_adiantado,
             SEC_TO_TIME(AVG(CASE WHEN status_calculado = 'Adiantado' THEN TIME_TO_SEC(desvio) END)) as media_adiantado,
             COUNT(CASE WHEN status_calculado = 'Supressão' THEN 1 END) as total_supressao,
             COUNT(CASE WHEN status_calculado = 'Sem Dados' THEN 1 END) as total_sem_dados,
             COUNT(CASE WHEN status_calculado = 'Não Login' THEN 1 END) as total_nao_login
         " . $base_classificacao_sql . " 
         GROUP BY route ORDER BY route ASC;";
     $result_linhas = $conexao->query($sql_linhas);
     $stats_por_linha = $result_linhas ? $result_linhas->fetch_all(MYSQLI_ASSOC) : [];
 }
 
 // Desempenho por Faixa Horária
 $sql_faixa_horaria = "
     SELECT 
         faixa_horaria, 
         COUNT(*) as total_viagens, 
         COUNT(CASE WHEN status_calculado = 'No Horário' THEN 1 END) as total_no_horario, 
         COUNT(CASE WHEN status_calculado = 'Atrasado' THEN 1 END) as total_atrasado, 
         SEC_TO_TIME(AVG(CASE WHEN status_calculado = 'Atrasado' THEN TIME_TO_SEC(desvio) END)) as media_atraso,
         COUNT(CASE WHEN status_calculado = 'Adiantado' THEN 1 END) as total_adiantado,
         SEC_TO_TIME(AVG(CASE WHEN status_calculado = 'Adiantado' THEN TIME_TO_SEC(desvio) END)) as media_adiantado,
         COUNT(CASE WHEN status_calculado = 'Supressão' THEN 1 END) as total_supressao,
         COUNT(CASE WHEN status_calculado = 'Sem Dados' THEN 1 END) as total_sem_dados,
         COUNT(CASE WHEN status_calculado = 'Não Login' THEN 1 END) as total_nao_login
     " . $base_classificacao_sql . " 
     WHERE faixa_horaria != 'Madrugada' 
     GROUP BY faixa_horaria 
     ORDER BY FIELD(faixa_horaria, 'Pico Manhã (06:00-08:59)', 'Entrepico Manhã (09:00-10:59)', 'Horário Escolar (11:00-13:59)', 'Início Tarde (14:00-15:59)', 'Pico Tarde (16:00-19:00)', 'Fim Noite (19:01-23:59)');";
 $result_faixa = $conexao->query($sql_faixa_horaria);
 $stats_faixa_horaria = $result_faixa ? $result_faixa->fetch_all(MYSQLI_ASSOC) : [];
 
 // *** NOVO: Desempenho por Sentido ***
 $sql_sentido = "
     SELECT 
         direcao, 
         COUNT(*) as total_viagens, 
         COUNT(CASE WHEN status_calculado = 'No Horário' THEN 1 END) as total_no_horario, 
         COUNT(CASE WHEN status_calculado = 'Atrasado' THEN 1 END) as total_atrasado, 
         SEC_TO_TIME(AVG(CASE WHEN status_calculado = 'Atrasado' THEN TIME_TO_SEC(desvio) END)) as media_atraso,
         COUNT(CASE WHEN status_calculado = 'Adiantado' THEN 1 END) as total_adiantado,
         SEC_TO_TIME(AVG(CASE WHEN status_calculado = 'Adiantado' THEN TIME_TO_SEC(desvio) END)) as media_adiantado,
         COUNT(CASE WHEN status_calculado = 'Supressão' THEN 1 END) as total_supressao,
         COUNT(CASE WHEN status_calculado = 'Sem Dados' THEN 1 END) as total_sem_dados,
         COUNT(CASE WHEN status_calculado = 'Não Login' THEN 1 END) as total_nao_login
     " . $base_classificacao_sql . " 
     WHERE direcao IN ('IDA', 'VOLTA') -- Filtra apenas IDA e VOLTA
     GROUP BY direcao 
     ORDER BY direcao ASC;";
 $result_sentido = $conexao->query($sql_sentido);
 $stats_sentido = $result_sentido ? $result_sentido->fetch_all(MYSQLI_ASSOC) : [];
 
 
 // Painel de Desempenho por Motorista (Excluindo 'Não Login')
 $sql_motoristas_resumo = "
     SELECT 
         operator, 
         matricula,
         COUNT(*) as total_viagens, 
         COUNT(CASE WHEN status_calculado = 'No Horário' THEN 1 END) as total_no_horario, 
         COUNT(CASE WHEN status_calculado = 'Atrasado' THEN 1 END) as total_atrasado, 
         SEC_TO_TIME(AVG(CASE WHEN status_calculado = 'Atrasado' THEN TIME_TO_SEC(desvio) END)) as media_atraso,
         COUNT(CASE WHEN status_calculado = 'Adiantado' THEN 1 END) as total_adiantado,
         SEC_TO_TIME(AVG(CASE WHEN status_calculado = 'Adiantado' THEN TIME_TO_SEC(desvio) END)) as media_adiantado,
         COUNT(CASE WHEN status_calculado = 'Supressão' THEN 1 END) as total_supressao,
         COUNT(CASE WHEN status_calculado = 'Sem Dados' THEN 1 END) as total_sem_dados -- Adicionado Sem Dados aqui também
     " . $base_classificacao_sql . " 
     WHERE status_calculado != 'Não Login' -- Exclui Não Login
     GROUP BY operator, matricula 
     ORDER BY total_viagens DESC;";
 $result_moto_resumo = $conexao->query($sql_motoristas_resumo);
 $stats_motoristas_resumo = $result_moto_resumo ? $result_moto_resumo->fetch_all(MYSQLI_ASSOC) : [];
 
 $sql_motoristas_analise = "
     SELECT 
         operator, 
         matricula,
         -- Médias e DP apenas de registros com desvio válido
         SEC_TO_TIME(AVG(CASE WHEN status_calculado NOT IN ('Sem Dados', 'Não Login') THEN TIME_TO_SEC(desvio) END)) as desvio_medio, 
         SEC_TO_TIME(STDDEV_SAMP(CASE WHEN status_calculado NOT IN ('Sem Dados', 'Não Login') THEN TIME_TO_SEC(desvio) END)) as consistencia, 
         -- Pior atraso/adiantamento considera apenas os status relevantes
         MAX(CASE WHEN status_calculado IN ('Atrasado', 'Supressão') THEN desvio END) as pior_atraso, 
         MIN(CASE WHEN status_calculado = 'Adiantado' THEN desvio END) as pior_adiantamento 
     " . $base_classificacao_sql . " 
     WHERE status_calculado != 'Não Login' -- Exclui Não Login
     GROUP BY operator, matricula 
     ORDER BY COUNT(*) DESC;"; // Ordena pela contagem original (antes do WHERE) para manter consistência
 
 $result_moto_analise = $conexao->query($sql_motoristas_analise);
 $stats_motoristas_analise_raw = $result_moto_analise ? $result_moto_analise->fetch_all(MYSQLI_ASSOC) : [];
 
 // Cria um array associativo para fácil acesso na tabela
 $stats_motoristas_analise = [];
 foreach($stats_motoristas_analise_raw as $row) {
     $stats_motoristas_analise[$row['operator']] = $row;
 }
 
 
 // --- PREPARAÇÃO DOS DADOS DE DETALHES POR MOTORISTA (COM LIMITE) ---
 $max_details_per_driver = 50; // Limite de detalhes por motorista
 
 // Detalhes de Atrasos por Motorista (com novas colunas)
 $sql_detalhes_atrasos = "
     SELECT operator, matricula, ponto_controle, route, bloco, workid, sentido_via, direcao, horario_programado, horario_real, desvio 
     " . $base_classificacao_sql . " 
     WHERE status_calculado IN ('Atrasado', 'Supressão') 
     ORDER BY operator, desvio DESC;"; // ORDER BY importante para pegar os piores
 $result_detalhes_atrasos = $conexao->query($sql_detalhes_atrasos); // Não usar fetch_all aqui
 
 $detalhes_atrasos_por_motorista = [];
 if ($result_detalhes_atrasos) {
     while ($registo = $result_detalhes_atrasos->fetch_assoc()) {
         $operator = $registo['operator'];
         if (!isset($detalhes_atrasos_por_motorista[$operator])) {
             $detalhes_atrasos_por_motorista[$operator] = [];
         }
         // Adiciona apenas se o limite não foi atingido
         if (count($detalhes_atrasos_por_motorista[$operator]) < $max_details_per_driver) {
             $detalhes_atrasos_por_motorista[$operator][] = $registo;
         }
     }
     $result_detalhes_atrasos->free(); // Libera memória
 }
 
 // Detalhes de Adiantamentos por Motorista (com novas colunas)
 $sql_detalhes_adiantamentos = "
     SELECT operator, matricula, ponto_controle, route, bloco, workid, sentido_via, direcao, horario_programado, horario_real, desvio 
     " . $base_classificacao_sql . " 
     WHERE status_calculado = 'Adiantado' 
     ORDER BY operator, desvio ASC;"; // ORDER BY importante para pegar os piores
 $result_detalhes_adiantamentos = $conexao->query($sql_detalhes_adiantamentos); // Não usar fetch_all
 
 $detalhes_adiantamentos_por_motorista = [];
 if ($result_detalhes_adiantamentos) {
     while ($registo = $result_detalhes_adiantamentos->fetch_assoc()) {
         $operator = $registo['operator'];
         if (!isset($detalhes_adiantamentos_por_motorista[$operator])) {
             $detalhes_adiantamentos_por_motorista[$operator] = [];
         }
         // Adiciona apenas se o limite não foi atingido
         if (count($detalhes_adiantamentos_por_motorista[$operator]) < $max_details_per_driver) {
             $detalhes_adiantamentos_por_motorista[$operator][] = $registo;
         }
     }
     $result_detalhes_adiantamentos->free(); // Libera memória
 }
 // --- FIM DA PREPARAÇÃO DOS DADOS DE DETALHES ---
 
 
 // Análise Detalhada por Motorista e Veículo (Excluindo Não Login)
 $sql_motoristas_carros = "
     SELECT 
         operator, 
         matricula,
         vehicle, 
         COUNT(*) as total_viagens, 
         COUNT(CASE WHEN status_calculado = 'No Horário' THEN 1 END) as total_no_horario, 
         COUNT(CASE WHEN status_calculado = 'Atrasado' THEN 1 END) as total_atrasado, 
         SEC_TO_TIME(AVG(CASE WHEN status_calculado = 'Atrasado' THEN TIME_TO_SEC(desvio) END)) as media_atraso,
         COUNT(CASE WHEN status_calculado = 'Adiantado' THEN 1 END) as total_adiantado,
         SEC_TO_TIME(AVG(CASE WHEN status_calculado = 'Adiantado' THEN TIME_TO_SEC(desvio) END)) as media_adiantado,
         COUNT(CASE WHEN status_calculado = 'Supressão' THEN 1 END) as total_supressao,
         COUNT(CASE WHEN status_calculado = 'Sem Dados' THEN 1 END) as total_sem_dados 
     " . $base_classificacao_sql . " 
     WHERE status_calculado != 'Não Login' AND vehicle IS NOT NULL AND vehicle != '' 
     GROUP BY operator, matricula, vehicle 
     ORDER BY operator, total_viagens DESC;";
 $result_motoristas_carros = $conexao->query($sql_motoristas_carros);
 $motoristas_veiculos_detalhado = [];
 if($result_motoristas_carros){
     while($registo = $result_motoristas_carros->fetch_assoc()) { 
         $motoristas_veiculos_detalhado[$registo['operator']][] = $registo; 
     }
 }
 
 
 // Pontos com Mais Atrasos / Adiantamentos (sem alterações, pois já usam status_calculado)
 $sql_top_pontos_atraso = "SELECT ponto_controle, route, COUNT(*) as quantidade " . $base_classificacao_sql . " WHERE status_calculado = 'Atrasado' GROUP BY ponto_controle, route ORDER BY quantidade DESC LIMIT 50;";
 $result_top_atraso = $conexao->query($sql_top_pontos_atraso);
 $top_pontos_atraso = $result_top_atraso ? $result_top_atraso->fetch_all(MYSQLI_ASSOC) : [];
 
 $sql_top_pontos_adiantado = "SELECT ponto_controle, route, COUNT(*) as quantidade " . $base_classificacao_sql . " WHERE status_calculado = 'Adiantado' GROUP BY ponto_controle, route ORDER BY quantidade DESC LIMIT 50;";
 $result_top_adiant = $conexao->query($sql_top_pontos_adiantado);
 $top_pontos_adiantado = $result_top_adiant ? $result_top_adiant->fetch_all(MYSQLI_ASSOC) : [];
 
 // Maiores Atrasos Registrados (com novas colunas)
 $sql_maiores_atrasos = "
     SELECT ponto_controle, route, bloco, workid, sentido_via, direcao, horario_programado, horario_real, desvio 
     " . $base_classificacao_sql . " 
     WHERE status_calculado IN ('Atrasado', 'Supressão') 
     ORDER BY desvio DESC LIMIT 50;";
 $result_maior_atraso = $conexao->query($sql_maiores_atrasos);
 $top_maiores_atrasos = $result_maior_atraso ? $result_maior_atraso->fetch_all(MYSQLI_ASSOC) : [];
 
 // Maiores Adiantamentos Registrados (com novas colunas)
 $sql_maiores_adiantamentos = "
     SELECT ponto_controle, route, bloco, workid, sentido_via, direcao, horario_programado, horario_real, desvio 
     " . $base_classificacao_sql . " 
     WHERE status_calculado = 'Adiantado' 
     ORDER BY desvio ASC LIMIT 50;";
 $result_maior_adiant = $conexao->query($sql_maiores_adiantamentos);
 $top_maiores_adiantamentos = $result_maior_adiant ? $result_maior_adiant->fetch_all(MYSQLI_ASSOC) : [];
 
 // *** NOVO: Lista de Registros 'Não Login' ***
 $sql_nao_login = "
     SELECT route, bloco, workid, ponto_controle, sentido_via, direcao, horario_programado 
     " . $base_classificacao_sql . " 
     WHERE status_calculado = 'Não Login' 
     ORDER BY horario_programado ASC;";
 $result_nao_login = $conexao->query($sql_nao_login);
 $lista_nao_login = $result_nao_login ? $result_nao_login->fetch_all(MYSQLI_ASSOC) : [];
 
 // *** NOVO: Lista de Registros 'Sem Dados' ***
 $sql_sem_dados = "
     SELECT route, bloco, workid, operator, matricula, ponto_controle, sentido_via, direcao, horario_programado 
     " . $base_classificacao_sql . " 
     WHERE status_calculado = 'Sem Dados' 
     ORDER BY horario_programado ASC;";
 $result_sem_dados = $conexao->query($sql_sem_dados);
 $lista_sem_dados = $result_sem_dados ? $result_sem_dados->fetch_all(MYSQLI_ASSOC) : [];
 
 
 // Busca as linhas disponíveis para o filtro
 $result_linhas_disp = $conexao->query("SELECT DISTINCT route FROM registros_timepoint_geral ORDER BY route ASC");
 $linhas_disponiveis = $result_linhas_disp ? $result_linhas_disp->fetch_all(MYSQLI_ASSOC) : [];
 
 // *** NOVO: Mapeamento de Cores para o Gráfico ***
 $chart_colors = [
     'No Horário' => '#22c55e', // Verde
     'Atrasado'   => '#f59e0b', // Ambar
     'Adiantado'  => '#ef4444', // Vermelho
     'Supressão'  => '#8b5cf6', // Violeta
     'Sem Dados'  => '#6b7280', // Cinza
     'Não Login'  => '#a16207'  // Amarelo Escuro
 ];
 $chart_labels = array_keys($stats_distribuicao);
 $chart_data = array_values($stats_distribuicao);
 $chart_background_colors = array_map(function($label) use ($chart_colors) {
     return $chart_colors[$label] ?? '#cccccc'; // Fallback cinza
 }, $chart_labels);
 
 
 ?>
 <!DOCTYPE html>
 <html lang="pt-br">
 <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>Relatório Geral de Timepoint v11.2</title> <!-- Versão incrementada -->
     <script src="tailwindcss-3.4.17.js"></script>
     <script src="chart.js"></script>
     <style>
         .text-no-horario { color: #22c55e; } /* Verde */
         .text-atrasado { color: #f59e0b; } /* Ambar */
         .text-adiantado { color: #ef4444; } /* Vermelho */
         .text-supressao { color: #8b5cf6; } /* Violeta */
         .text-sem-dados { color: #6b7280; } /* Cinza */
         .text-nao-login { color: #a16207; } /* Amarelo Escuro */
         
         .bg-no-horario-dot { background-color: #22c55e; }
         .bg-atrasado-dot { background-color: #f59e0b; }
         .bg-adiantado-dot { background-color: #ef4444; }
         .bg-supressao-dot { background-color: #8b5cf6; }
         .bg-sem-dados-dot { background-color: #6b7280; }
         .bg-nao-login-dot { background-color: #a16207; }
 
         .table-scroll-container { max-height: 400px; overflow-y: auto; }
        thead th { position: sticky; top: 0; background-color: #f3f4f6; z-index: 10; }
         details > summary { list-style: none; cursor: pointer; }
         details > summary::-webkit-details-marker { display: none; }
         /* Ajuste fino para espaçamento e bordas */
         th, td { padding: 0.5rem 0.75rem; border-bottom: 1px solid #e5e7eb; white-space: nowrap; /* Evita quebra de linha */ vertical-align: middle; }
         tbody tr:hover { background-color: #f9fafb; }
         /* Ajuste para tabelas dentro de details terem scroll horizontal se necessário */
         details .table-scroll-container table { min-width: 900px; /* Ajuste conforme necessário */ } 
     </style>
 </head>
 <body class="bg-gray-100 font-sans">
 
     <main class="mx-auto p-4 md:p-6">
         <header class="mb-6">
             <h1 class="text-3xl font-bold text-gray-800">Relatório Geral de Timepoint (v11.2)</h1>
             <p class="text-gray-600">Análise de pontualidade por ponto de controle com novas regras e tolerâncias.</p>
         </header>
 
         <!-- Formulário de Filtros -->
         <div class="bg-white p-4 rounded-lg shadow-md mb-6">
             <form action="relatorio_timepoint_geral.php" method="GET" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-7 gap-4 items-end">
                 <div>
                     <label for="visao" class="block text-sm font-medium text-gray-700 mb-1">Visão</label>
                     <select name="visao" id="visao" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                         <option value="geral" <?= $visao_selecionada === 'geral' ? 'selected' : '' ?>>Geral</option>
                         <option value="chegadas" <?= $visao_selecionada === 'chegadas' ? 'selected' : '' ?>>Chegadas</option>
                         <option value="partidas" <?= $visao_selecionada === 'partidas' ? 'selected' : '' ?>>Partidas</option>
                     </select>
                 </div>
                 <div>
                     <label for="aba" class="block text-sm font-medium text-gray-700 mb-1">Período</label>
                     <select name="aba" id="aba" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                         <option value="geral" <?= $aba_selecionada === 'geral' ? 'selected' : '' ?>>Geral</option>
                         <option value="uteis" <?= $aba_selecionada === 'uteis' ? 'selected' : '' ?>>Dias Úteis</option>
                         <option value="sabados" <?= $aba_selecionada === 'sabados' ? 'selected' : '' ?>>Sábados</option>
                         <option value="domingos" <?= $aba_selecionada === 'domingos' ? 'selected' : '' ?>>Domingos</option>
                     </select>
                 </div>
                 <div>
                     <label for="linha" class="block text-sm font-medium text-gray-700 mb-1">Linha</label>
                     <select name="linha" id="linha" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                         <option value="todas">Todas as Linhas</option>
                         <?php foreach ($linhas_disponiveis as $linha): ?>
                             <option value="<?= htmlspecialchars($linha['route']) ?>" <?= $linha_selecionada === $linha['route'] ? 'selected' : '' ?>>
                                 <?= htmlspecialchars($linha['route']) ?>
                             </option>
                         <?php endforeach; ?>
                     </select>
                 </div>
                  <div>
                     <label for="tolerancia" class="block text-sm font-medium text-gray-700 mb-1">Tolerância</label>
                     <select name="tolerancia" id="tolerancia" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                         <option value="padrao" <?= $tolerancia_selecionada === 'padrao' ? 'selected' : '' ?>>-2 / +6 min (Padrão)</option>
                         <option value="alternativa" <?= $tolerancia_selecionada === 'alternativa' ? 'selected' : '' ?>>-1 / +3 min</option>
                     </select>
                 </div>
                 <div>
                     <label for="data_inicio" class="block text-sm font-medium text-gray-700 mb-1">Data Início</label>
                     <input type="date" name="data_inicio" id="data_inicio" value="<?= htmlspecialchars($data_inicio) ?>" min="<?= htmlspecialchars($intervalo_datas_db['min_date'] ?? '') ?>" max="<?= htmlspecialchars($intervalo_datas_db['max_date'] ?? '') ?>" class="w-full p-2 border border-gray-300 rounded-md shadow-sm text-sm">
                 </div>
                 <div>
                     <label for="data_fim" class="block text-sm font-medium text-gray-700 mb-1">Data Fim</label>
                     <input type="date" name="data_fim" id="data_fim" value="<?= htmlspecialchars($data_fim) ?>" min="<?= htmlspecialchars($intervalo_datas_db['min_date'] ?? '') ?>" max="<?= htmlspecialchars($intervalo_datas_db['max_date'] ?? '') ?>" class="w-full p-2 border border-gray-300 rounded-md shadow-sm text-sm">
                 </div>
                 <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-md hover:bg-blue-700 transition-colors duration-300 h-10 text-sm">
                     Filtrar
                 </button>
             </form>
         </div>
 
         <!-- Área de Conteúdo do Relatório -->
         <div class="bg-white p-6 rounded-lg shadow-md">
             <h2 class="text-xl font-semibold text-gray-700 mb-2">
                 Exibindo Relatório de <?= $titulo_visao ?>
             </h2>
             <p class="text-sm text-gray-600 mb-4">
                 <strong>Período:</strong> <?= $titulo_periodo ?> | 
                 <strong>Linha:</strong> <?= $titulo_linha ?> |
                 <strong>Tolerância:</strong> <?= $titulo_tolerancia ?> |
                 <strong>Datas:</strong> <?= (new DateTime($data_inicio))->format('d/m/Y') ?> a <?= (new DateTime($data_fim))->format('d/m/Y') ?>
             </p>
             
             <div class="space-y-6 border-t pt-4">
 
                 <!-- *** NOVA SEÇÃO: Índices Totais (Calculado vs CAD) *** -->
                 <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
                     <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                         <h3 class="font-bold text-lg mb-2 text-center text-gray-700">Índices Totais (Calculado)</h3>
                         <div class="flex justify-around items-center mt-3">
                             <div class="text-center">
                                 <p class="text-sm text-blue-600 font-semibold">ICV</p>
                                 <p class="text-3xl font-bold text-blue-600"><?= number_format($icv_calculado, 1) ?>%</p>
                             </div>
                             <div class="text-center">
                                 <p class="text-sm text-green-600 font-semibold">IPV</p>
                                 <p class="text-3xl font-bold text-green-600"><?= number_format($ipv_calculado, 1) ?>%</p>
                             </div>
                         </div>
                         <p class="text-xs text-gray-500 mt-2 text-center">Calculado com base nos dados importados e filtros aplicados.</p>
                     </div>
                     <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                          <h3 class="font-bold text-lg mb-2 text-center text-gray-700">Índices Totais (CAD)</h3>
                          <div class="flex justify-around items-center mt-3">
                             <div class="text-center">
                                 <p class="text-sm text-blue-600 font-semibold">ICV</p>
                                 <p class="text-3xl font-bold text-blue-600"><?= number_format($icv_total_cad, 1) ?>%</p>
                             </div>
                             <div class="text-center">
                                 <p class="text-sm text-green-600 font-semibold">IPV</p>
                                 <p class="text-3xl font-bold text-green-600"><?= number_format($ipv_total_cad, 1) ?>%</p>
                             </div>
                         </div>
                          <p class="text-xs text-gray-500 mt-2 text-center">Média ponderada dos resumos diários importados (ICV/IPV e On-Time).</p>
                     </div>
                 </section>
 
                 <!-- Distribuição de Status -->
                 <section class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                     <h3 class="font-bold text-lg mb-3">Distribuição de Status</h3>
                     <div class="flex flex-col md:flex-row items-center">
                         <div class="w-full md:w-1/3 h-64 md:h-auto"><canvas id="statusChart"></canvas></div>
                         <div class="w-full md:w-2/3 md:pl-6 mt-4 md:mt-0 space-y-1">
                             <?php 
                                 $cores_texto = [
                                     'No Horário' => 'text-no-horario', 'Atrasado' => 'text-atrasado', 'Adiantado' => 'text-adiantado', 
                                     'Supressão' => 'text-supressao', 'Sem Dados' => 'text-sem-dados', 'Não Login' => 'text-nao-login'
                                 ];
                                 $cores_bg = [
                                     'No Horário' => 'bg-no-horario-dot', 'Atrasado' => 'bg-atrasado-dot', 'Adiantado' => 'bg-adiantado-dot', 
                                     'Supressão' => 'bg-supressao-dot', 'Sem Dados' => 'bg-sem-dados-dot', 'Não Login' => 'bg-nao-login-dot'
                                 ]; 
                             ?>
                             <?php foreach ($stats_distribuicao as $status => $total): ?>
                             <div class="flex justify-between items-center py-1 text-sm">
                                 <span class="flex items-center">
                                     <span class="w-3 h-3 rounded-full <?= $cores_bg[$status] ?? 'bg-gray-400' ?> mr-2 inline-block flex-shrink-0"></span>
                                     <span class="font-medium <?= $cores_texto[$status] ?? '' ?> truncate pr-2"><?= htmlspecialchars($status) ?></span>
                                 </span>
                                 <span class="font-semibold <?= $cores_texto[$status] ?? '' ?> ml-2 flex-shrink-0">
                                     <?= number_format($total) ?> 
                                     <span class="text-gray-500 font-normal text-xs">/ <?= $total_registros_geral > 0 ? round($total / $total_registros_geral * 100, 1) : 0 ?>%</span>
                                 </span>
                             </div>
                             <?php endforeach; ?>
                             <div class="border-t pt-2 mt-2 flex justify-between items-center text-sm font-bold">
                                 <span>Total Geral</span>
                                 <span class="text-black"><?= number_format($total_registros_geral) ?></span>
                             </div>
                         </div>
                     </div>
                 </section>
 
                 <!-- ANÁLISE DIÁRIA DE INDICADORES (Mantida) -->
                 <section class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                     <h3 class="font-bold text-lg mb-3">Análise Diária de Indicadores (CAD vs. Calculado)</h3>
                     <div class="table-scroll-container">
                         <table class="w-full text-sm">
                             <thead class="bg-gray-200">
                                 <tr>
                                     <th>Data</th>
                                     <th class="text-center text-blue-600">ICV CAD (%)</th>
                                     <th class="text-center text-blue-600">ICV Calc. (%)</th>
                                     <th class="text-center text-green-600">IPV CAD (%)</th>
                                     <th class="text-center text-green-600">IPV Calc. (%)</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 <?php
                                 $period = new DatePeriod(new DateTime($data_inicio), new DateInterval('P1D'), (new DateTime($data_fim))->modify('+1 day'));
                                 $has_daily_data = false;
                                 foreach ($period as $date):
                                     $has_daily_data = true;
                                     $current_date_str = $date->format('Y-m-d');
                                 ?>
                                 <tr>
                                     <td class="font-semibold"><?= $date->format('d/m/Y') ?></td>
                                     <td class="text-center font-bold text-blue-600"><?= isset($dados_sistema_icv[$current_date_str]['icv_actual_percent']) ? number_format($dados_sistema_icv[$current_date_str]['icv_actual_percent'], 1) : '-' ?></td>
                                     <td class="text-center font-bold text-blue-600"><?= isset($dados_calculados_diario[$current_date_str]['icv_percent']) ? number_format($dados_calculados_diario[$current_date_str]['icv_percent'], 1) : '-' ?></td>
                                     <td class="text-center font-bold text-green-600"><?= isset($dados_sistema_ontime[$current_date_str]['no_horario_percent']) ? number_format($dados_sistema_ontime[$current_date_str]['no_horario_percent'], 1) : '-' ?></td>
                                     <td class="text-center font-bold text-green-600"><?= isset($dados_calculados_diario[$current_date_str]['ipv_percent']) ? number_format($dados_calculados_diario[$current_date_str]['ipv_percent'], 1) : '-' ?></td>
                                 </tr>
                                 <?php endforeach; ?>
                                 <?php if (!$has_daily_data): ?>
                                     <tr><td colspan="5" class="p-4 text-center text-gray-500">Nenhum dado diário encontrado para o período selecionado.</td></tr>
                                 <?php endif; ?>
                             </tbody>
                              <?php if ($has_daily_data): ?>
                              <tfoot class="bg-gray-200 font-bold">
                                 <tr>
                                     <td>Total/Média</td>
                                     <td class="text-center text-blue-600"><?= number_format($icv_total_cad, 1) ?>%</td>
                                     <td class="text-center text-blue-600"><?= number_format($icv_calculado, 1) ?>%</td>
                                     <td class="text-center text-green-600"><?= number_format($ipv_total_cad, 1) ?>%</td>
                                     <td class="text-center text-green-600"><?= number_format($ipv_calculado, 1) ?>%</td>
                                 </tr>
                             </tfoot>
                              <?php endif; ?>
                         </table>
                     </div>
                 </section>
 
                 <!-- Desempenho por Linhas -->
                 <?php if ($linha_selecionada === 'todas'): ?>
                 <section class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                     <h3 class="font-bold text-lg mb-3">Desempenho por Linhas</h3>
                      <div class="table-scroll-container">
                         <table class="w-full text-sm">
                             <thead class="bg-gray-200">
                                 <tr>
                                     <th>Linha</th>
                                     <th class="text-center text-no-horario">Nº / % No Horário</th>
                                     <th class="text-center text-atrasado">Nº / % Atrasado</th>
                                     <th class="text-center text-atrasado">Média Atraso</th>
                                     <th class="text-center text-adiantado">Nº / % Adiantado</th>
                                     <th class="text-center text-adiantado">Média Adiantado</th>
                                     <th class="text-center text-supressao">Nº / % Supressão</th>
                                     <th class="text-center text-sem-dados">Nº / % Sem Dados</th>
                                     <th class="text-center text-nao-login">Nº / % Não Login</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 <?php foreach ($stats_por_linha as $linha_data): $divisor = $linha_data['total_viagens'] > 0 ? $linha_data['total_viagens'] : 1; ?>
                                 <tr>
                                     <td class="font-mono"><?= htmlspecialchars($linha_data['route']); ?></td>
                                     <td class="text-center"><?= $linha_data['total_no_horario']; ?> <span class="text-xs text-gray-500">/ <?= round(($linha_data['total_no_horario'] / $divisor) * 100, 1); ?>%</span></td>
                                     <td class="text-center"><?= $linha_data['total_atrasado']; ?> <span class="text-xs text-gray-500">/ <?= round(($linha_data['total_atrasado'] / $divisor) * 100, 1); ?>%</span></td>
                                     <td class="text-center font-mono text-atrasado"><?= formatarTempoSQL($linha_data['media_atraso']) ?></td>
                                     <td class="text-center"><?= $linha_data['total_adiantado']; ?> <span class="text-xs text-gray-500">/ <?= round(($linha_data['total_adiantado'] / $divisor) * 100, 1); ?>%</span></td>
                                     <td class="text-center font-mono text-adiantado"><?= formatarTempoSQL($linha_data['media_adiantado']) ?></td>
                                     <td class="text-center"><?= $linha_data['total_supressao']; ?> <span class="text-xs text-gray-500">/ <?= round(($linha_data['total_supressao'] / $divisor) * 100, 1); ?>%</span></td>
                                     <td class="text-center"><?= $linha_data['total_sem_dados']; ?> <span class="text-xs text-gray-500">/ <?= round(($linha_data['total_sem_dados'] / $divisor) * 100, 1); ?>%</span></td>
                                     <td class="text-center"><?= $linha_data['total_nao_login']; ?> <span class="text-xs text-gray-500">/ <?= round(($linha_data['total_nao_login'] / $divisor) * 100, 1); ?>%</span></td>
                                 </tr>
                                 <?php endforeach; ?>
                                 <?php if (empty($stats_por_linha)): ?>
                                     <tr><td colspan="9" class="p-4 text-center text-gray-500">Nenhuma linha encontrada para os filtros selecionados.</td></tr>
                                 <?php endif; ?>
                             </tbody>
                         </table>
                     </div>
                 </section>
                 <?php endif; ?>
 
                 <!-- Desempenho por Faixa Horária -->
                 <section class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                     <h3 class="font-bold text-lg mb-3">Desempenho por Faixa Horária</h3>
                     <div class="table-scroll-container">
                         <table class="w-full text-sm">
                             <thead class="bg-gray-200">
                                 <tr>
                                     <th>Faixa Horária</th>
                                     <th class="text-center text-no-horario">Nº / % No Horário</th>
                                     <th class="text-center text-atrasado">Nº / % Atrasado</th>
                                     <th class="text-center text-atrasado">Média Atraso</th>
                                     <th class="text-center text-adiantado">Nº / % Adiantado</th>
                                     <th class="text-center text-adiantado">Média Adiantado</th>
                                     <th class="text-center text-supressao">Nº / % Supressão</th>
                                      <th class="text-center text-sem-dados">Nº / % Sem Dados</th>
                                     <th class="text-center text-nao-login">Nº / % Não Login</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 <?php foreach ($stats_faixa_horaria as $faixa): $divisor = $faixa['total_viagens'] > 0 ? $faixa['total_viagens'] : 1; ?>
                                 <tr>
                                     <td><?= htmlspecialchars($faixa['faixa_horaria']) ?></td>
                                     <td class="text-center"><?= $faixa['total_no_horario']; ?> <span class="text-xs text-gray-500">/ <?= round(($faixa['total_no_horario'] / $divisor) * 100, 1); ?>%</span></td>
                                     <td class="text-center"><?= $faixa['total_atrasado']; ?> <span class="text-xs text-gray-500">/ <?= round(($faixa['total_atrasado'] / $divisor) * 100, 1); ?>%</span></td>
                                     <td class="text-center font-mono text-atrasado"><?= formatarTempoSQL($faixa['media_atraso']) ?></td>
                                     <td class="text-center"><?= $faixa['total_adiantado']; ?> <span class="text-xs text-gray-500">/ <?= round(($faixa['total_adiantado'] / $divisor) * 100, 1); ?>%</span></td>
                                     <td class="text-center font-mono text-adiantado"><?= formatarTempoSQL($faixa['media_adiantado']) ?></td>
                                     <td class="text-center"><?= $faixa['total_supressao']; ?> <span class="text-xs text-gray-500">/ <?= round(($faixa['total_supressao'] / $divisor) * 100, 1); ?>%</span></td>
                                     <td class="text-center"><?= $faixa['total_sem_dados']; ?> <span class="text-xs text-gray-500">/ <?= round(($faixa['total_sem_dados'] / $divisor) * 100, 1); ?>%</span></td>
                                     <td class="text-center"><?= $faixa['total_nao_login']; ?> <span class="text-xs text-gray-500">/ <?= round(($faixa['total_nao_login'] / $divisor) * 100, 1); ?>%</span></td>
                                 </tr>
                                 <?php endforeach; ?>
                                  <?php if (empty($stats_faixa_horaria)): ?>
                                     <tr><td colspan="9" class="p-4 text-center text-gray-500">Nenhum dado encontrado para as faixas horárias.</td></tr>
                                 <?php endif; ?>
                             </tbody>
                         </table>
                     </div>
                 </section>
 
                 <!-- *** NOVO: Desempenho por Sentido *** -->
                 <section class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                     <h3 class="font-bold text-lg mb-3">Desempenho por Sentido</h3>
                     <div class="table-scroll-container">
                         <table class="w-full text-sm">
                             <thead class="bg-gray-200">
                                 <tr>
                                     <th>Sentido</th>
                                     <th class="text-center text-no-horario">Nº / % No Horário</th>
                                     <th class="text-center text-atrasado">Nº / % Atrasado</th>
                                     <th class="text-center text-atrasado">Média Atraso</th>
                                     <th class="text-center text-adiantado">Nº / % Adiantado</th>
                                     <th class="text-center text-adiantado">Média Adiantado</th>
                                     <th class="text-center text-supressao">Nº / % Supressão</th>
                                     <th class="text-center text-sem-dados">Nº / % Sem Dados</th>
                                     <th class="text-center text-nao-login">Nº / % Não Login</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 <?php foreach ($stats_sentido as $sentido): $divisor = $sentido['total_viagens'] > 0 ? $sentido['total_viagens'] : 1; ?>
                                 <tr>
                                     <td class="font-semibold"><?= htmlspecialchars($sentido['direcao']) ?></td>
                                     <td class="text-center"><?= $sentido['total_no_horario']; ?> <span class="text-xs text-gray-500">/ <?= round(($sentido['total_no_horario'] / $divisor) * 100, 1); ?>%</span></td>
                                     <td class="text-center"><?= $sentido['total_atrasado']; ?> <span class="text-xs text-gray-500">/ <?= round(($sentido['total_atrasado'] / $divisor) * 100, 1); ?>%</span></td>
                                     <td class="text-center font-mono text-atrasado"><?= formatarTempoSQL($sentido['media_atraso']) ?></td>
                                     <td class="text-center"><?= $sentido['total_adiantado']; ?> <span class="text-xs text-gray-500">/ <?= round(($sentido['total_adiantado'] / $divisor) * 100, 1); ?>%</span></td>
                                     <td class="text-center font-mono text-adiantado"><?= formatarTempoSQL($sentido['media_adiantado']) ?></td>
                                     <td class="text-center"><?= $sentido['total_supressao']; ?> <span class="text-xs text-gray-500">/ <?= round(($sentido['total_supressao'] / $divisor) * 100, 1); ?>%</span></td>
                                     <td class="text-center"><?= $sentido['total_sem_dados']; ?> <span class="text-xs text-gray-500">/ <?= round(($sentido['total_sem_dados'] / $divisor) * 100, 1); ?>%</span></td>
                                     <td class="text-center"><?= $sentido['total_nao_login']; ?> <span class="text-xs text-gray-500">/ <?= round(($sentido['total_nao_login'] / $divisor) * 100, 1); ?>%</span></td>
                                 </tr>
                                 <?php endforeach; ?>
                                  <?php if (empty($stats_sentido)): ?>
                                     <tr><td colspan="9" class="p-4 text-center text-gray-500">Nenhum dado encontrado para IDA/VOLTA.</td></tr>
                                 <?php endif; ?>
                             </tbody>
                         </table>
                     </div>
                 </section>
 
                 <!-- Painel de Desempenho por Motorista (Excluindo Não Login) -->
                 <section class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                     <h3 class="font-bold text-lg mb-3">Painel de Desempenho por Motorista (Exclui 'Não Login')</h3>
                     <div class="space-y-6">
                         <div>
                             <h4 class="font-semibold text-md mb-2">1. Resumo Quantitativo</h4>
                             <div class="table-scroll-container">
                                 <table class="w-full text-sm">
                                     <thead class="bg-gray-200">
                                         <tr>
                                             <th>Motorista</th>
                                             <th>Matrícula</th>
                                             <th class="text-center">Registos</th>
                                             <th class="text-center text-no-horario">Nº / % No Horário</th>
                                             <th class="text-center text-atrasado">Nº / % Atrasado</th>
                                             <th class="text-center text-atrasado">Média Atraso</th>
                                             <th class="text-center text-adiantado">Nº / % Adiantado</th>
                                             <th class="text-center text-adiantado">Média Adiantado</th>
                                             <th class="text-center text-supressao">Nº / % Supressão</th>
                                             <th class="text-center text-sem-dados">Nº / % Sem Dados</th>
                                         </tr>
                                     </thead>
                                     <tbody>
                                         <?php foreach ($stats_motoristas_resumo as $motorista): $divisor = $motorista['total_viagens'] > 0 ? $motorista['total_viagens'] : 1; ?>
                                         <tr>
                                             <td><?= htmlspecialchars($motorista['operator']) ?></td>
                                             <td class="text-center"><?= htmlspecialchars($motorista['matricula']) ?></td>
                                             <td class="text-center"><?= $motorista['total_viagens'] ?></td>
                                             <td class="text-center"><?= $motorista['total_no_horario']; ?> <span class="text-xs text-gray-500">/ <?= round(($motorista['total_no_horario'] / $divisor) * 100, 1); ?>%</span></td>
                                             <td class="text-center"><?= $motorista['total_atrasado']; ?> <span class="text-xs text-gray-500">/ <?= round(($motorista['total_atrasado'] / $divisor) * 100, 1); ?>%</span></td>
                                             <td class="text-center font-mono text-atrasado"><?= formatarTempoSQL($motorista['media_atraso']) ?></td>
                                             <td class="text-center"><?= $motorista['total_adiantado']; ?> <span class="text-xs text-gray-500">/ <?= round(($motorista['total_adiantado'] / $divisor) * 100, 1); ?>%</span></td>
                                             <td class="text-center font-mono text-adiantado"><?= formatarTempoSQL($motorista['media_adiantado']) ?></td>
                                             <td class="text-center"><?= $motorista['total_supressao']; ?> <span class="text-xs text-gray-500">/ <?= round(($motorista['total_supressao'] / $divisor) * 100, 1); ?>%</span></td>
                                             <td class="text-center"><?= $motorista['total_sem_dados']; ?> <span class="text-xs text-gray-500">/ <?= round(($motorista['total_sem_dados'] / $divisor) * 100, 1); ?>%</span></td>
                                         </tr>
                                         <?php endforeach; ?>
                                         <?php if (empty($stats_motoristas_resumo)): ?>
                                             <tr><td colspan="10" class="p-4 text-center text-gray-500">Nenhum motorista encontrado (excluindo 'Não Login').</td></tr>
                                         <?php endif; ?>
                                     </tbody>
                                 </table>
                             </div>
                         </div>
                         <div>
                             <h4 class="font-semibold text-md mb-2">2. Análise de Desvio e Consistência</h4>
                             <div class="table-scroll-container">
                                 <table class="w-full text-sm">
                                     <thead class="bg-gray-200">
                                         <tr>
                                             <th>Motorista</th>
                                             <th>Matrícula</th>
                                             <th class="text-center">Desvio Médio Geral</th>
                                             <th class="text-center">Consistência (DP)</th>
                                             <th class="text-center text-atrasado">Pior Atraso</th>
                                             <th class="text-center text-adiantado">Pior Adiantamento</th>
                                         </tr>
                                     </thead>
                                     <tbody>
                                         <?php foreach ($stats_motoristas_resumo as $motorista_resumo): // Loop pelo resumo para garantir a ordem
                                             $motorista_nome = $motorista_resumo['operator'];
                                             $analise = $stats_motoristas_analise[$motorista_nome] ?? null; 
                                             if ($analise): ?>
                                                 <tr>
                                                     <td><?= htmlspecialchars($analise['operator']) ?></td>
                                                     <td class="text-center"><?= htmlspecialchars($analise['matricula']) ?></td>
                                                     <td class="text-center font-mono <?= getCorDesvio($analise['desvio_medio'] ?? null, $tolerancia_atraso_segundos, $tolerancia_adiantado_segundos) ?>"><?= formatarTempoSQL($analise['desvio_medio'] ?? null) ?></td>
                                                     <td class="text-center font-mono"><?= formatarTempoSQL($analise['consistencia'] ?? null) ?></td>
                                                     <td class="text-center font-mono text-atrasado"><?= formatarTempoSQL($analise['pior_atraso'] ?? null) ?></td>
                                                     <td class="text-center font-mono text-adiantado"><?= formatarTempoSQL($analise['pior_adiantamento'] ?? null) ?></td>
                                                 </tr>
                                             <?php endif; ?>
                                         <?php endforeach; ?>
                                          <?php if (empty($stats_motoristas_analise)): ?>
                                             <tr><td colspan="6" class="p-4 text-center text-gray-500">Nenhum dado de análise encontrado.</td></tr>
                                         <?php endif; ?>
                                     </tbody>
                                 </table>
                             </div>
                         </div>
                         
                         <div>
                             <h4 class="font-semibold text-md mb-2">3. Detalhes de Atrasos/Supressões por Motorista</h4>
                             <div class="space-y-2 table-scroll-container">
                                <?php $count_atrasos = 0; ?>
                                <?php foreach ($stats_motoristas_resumo as $motorista_resumo): ?>
                                    <?php 
                                    $motorista_nome = $motorista_resumo['operator'];
                                    if (isset($detalhes_atrasos_por_motorista[$motorista_nome])): 
                                        $count_atrasos++;
                                        $registros = $detalhes_atrasos_por_motorista[$motorista_nome];
                                        $matricula_det = $registros[0]['matricula'] ?? $motorista_resumo['matricula']; // Pega a matrícula do primeiro registro ou do resumo
                                    ?>
                                    <details class="bg-white rounded border border-gray-200">
                                        <summary class="p-2 font-semibold text-gray-700 hover:bg-gray-50 flex justify-between items-center">
                                            <span><?= htmlspecialchars($motorista_nome) ?> (<?= htmlspecialchars($matricula_det) ?>)</span>
                                            <span class="text-sm text-atrasado font-bold"><?= count($registros) ?> Atrasos/Supressões</span>
                                        </summary>
                                        <div class="p-2 border-t">
                                            <div class="table-scroll-container" style="max-height: 250px;">
                                                <table class="w-full text-xs">
                                                    <thead class="bg-gray-200"><tr>
                                                        <th class="p-1 text-left">Ponto</th>
                                                        <th class="p-1 text-left">Linha</th>
                                                        <th class="p-1 text-center">Bloco</th>
                                                        <th class="p-1 text-center">WorkID</th>
                                                        <th class="p-1 text-left">Via</th>
                                                        <th class="p-1 text-center">Sentido</th>
                                                        <th class="p-1 text-center">Programado</th>
                                                        <th class="p-1 text-center">Realizado</th>
                                                        <th class="p-1 text-center">Desvio</th>
                                                        <th class="p-1 text-center">Excesso</th>
                                                    </tr></thead>
                                                    <tbody>
                                                    <?php foreach ($registros as $reg): ?>
                                                        <tr>
                                                            <td class="p-1 border-b"><?= htmlspecialchars($reg['ponto_controle']) ?></td>
                                                            <td class="p-1 border-b"><?= htmlspecialchars($reg['route']) ?></td>
                                                            <td class="p-1 border-b text-center"><?= htmlspecialchars($reg['bloco']) ?></td>
                                                            <td class="p-1 border-b text-center"><?= htmlspecialchars($reg['workid']) ?></td>
                                                            <td class="p-1 border-b"><?= htmlspecialchars($reg['sentido_via']) ?></td>
                                                            <td class="p-1 border-b text-center"><?= htmlspecialchars($reg['direcao']) ?></td>
                                                            <td class="p-1 border-b text-center"><?= $reg['horario_programado'] ? (new DateTime($reg['horario_programado']))->format('H:i') : '-' ?></td>
                                                            <td class="p-1 border-b text-center"><?= $reg['horario_real'] ? (new DateTime($reg['horario_real']))->format('H:i') : '-' ?></td>
                                                            <td class="p-1 border-b text-center font-mono"><?= formatarTempoSQL($reg['desvio']) ?></td>
                                                            <td class="p-1 border-b text-center font-bold text-atrasado"><?= formatarDesvioExcedente($reg['desvio'], $tolerancia_atraso_segundos, $tolerancia_adiantado_segundos) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </details>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if ($count_atrasos === 0): ?>
                                     <p class="p-4 text-center text-gray-500">Nenhum atraso ou supressão encontrada para os motoristas listados.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                             <h4 class="font-semibold text-md mb-2">4. Detalhes de Adiantamentos por Motorista</h4>
                             <div class="space-y-2 table-scroll-container">
                                <?php $count_adiant = 0; ?>
                                <?php foreach ($stats_motoristas_resumo as $motorista_resumo): ?>
                                     <?php 
                                    $motorista_nome = $motorista_resumo['operator'];
                                    if (isset($detalhes_adiantamentos_por_motorista[$motorista_nome])): 
                                        $count_adiant++;
                                        $registros = $detalhes_adiantamentos_por_motorista[$motorista_nome];
                                        $matricula_det = $registros[0]['matricula'] ?? $motorista_resumo['matricula'];
                                    ?>
                                    <details class="bg-white rounded border border-gray-200">
                                        <summary class="p-2 font-semibold text-gray-700 hover:bg-gray-50 flex justify-between items-center">
                                            <span><?= htmlspecialchars($motorista_nome) ?> (<?= htmlspecialchars($matricula_det) ?>)</span>
                                            <span class="text-sm text-adiantado font-bold"><?= count($registros) ?> Adiantamentos</span>
                                        </summary>
                                        <div class="p-2 border-t">
                                            <div class="table-scroll-container" style="max-height: 250px;">
                                                <table class="w-full text-xs">
                                                     <thead class="bg-gray-200"><tr>
                                                        <th class="p-1 text-left">Ponto</th>
                                                        <th class="p-1 text-left">Linha</th>
                                                        <th class="p-1 text-center">Bloco</th>
                                                        <th class="p-1 text-center">WorkID</th>
                                                        <th class="p-1 text-left">Via</th>
                                                        <th class="p-1 text-center">Sentido</th>
                                                        <th class="p-1 text-center">Programado</th>
                                                        <th class="p-1 text-center">Realizado</th>
                                                        <th class="p-1 text-center">Desvio</th>
                                                        <th class="p-1 text-center">Excesso</th>
                                                    </tr></thead>
                                                    <tbody>
                                                    <?php foreach ($registros as $reg): ?>
                                                         <tr>
                                                            <td class="p-1 border-b"><?= htmlspecialchars($reg['ponto_controle']) ?></td>
                                                            <td class="p-1 border-b"><?= htmlspecialchars($reg['route']) ?></td>
                                                            <td class="p-1 border-b text-center"><?= htmlspecialchars($reg['bloco']) ?></td>
                                                            <td class="p-1 border-b text-center"><?= htmlspecialchars($reg['workid']) ?></td>
                                                            <td class="p-1 border-b"><?= htmlspecialchars($reg['sentido_via']) ?></td>
                                                            <td class="p-1 border-b text-center"><?= htmlspecialchars($reg['direcao']) ?></td>
                                                            <td class="p-1 border-b text-center"><?= $reg['horario_programado'] ? (new DateTime($reg['horario_programado']))->format('H:i') : '-' ?></td>
                                                            <td class="p-1 border-b text-center"><?= $reg['horario_real'] ? (new DateTime($reg['horario_real']))->format('H:i') : '-' ?></td>
                                                            <td class="p-1 border-b text-center font-mono"><?= formatarTempoSQL($reg['desvio']) ?></td>
                                                            <td class="p-1 border-b text-center font-bold text-adiantado"><?= formatarDesvioExcedente($reg['desvio'], $tolerancia_atraso_segundos, $tolerancia_adiantado_segundos) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </details>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if ($count_adiant === 0): ?>
                                     <p class="p-4 text-center text-gray-500">Nenhum adiantamento encontrado para os motoristas listados.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>
                 
                 <!-- Análise Detalhada por Motorista e Veículo -->
                 <section class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                     <h3 class="font-bold text-lg mb-3">Análise Detalhada por Motorista e Veículo</h3>
                     <div class="table-scroll-container">
                        <table class="w-full text-sm">
                             <thead class="bg-gray-200">
                                 <tr>
                                     <th>Motorista</th>
                                     <th>Veículo</th>
                                     <th class="text-center"><?= htmlspecialchars($titulo_visao_coluna) ?></th>
                                     <th class="text-center text-no-horario">Nº / % No Horário</th>
                                     <th class="text-center text-atrasado">Nº / % Atrasado</th>
                                     <th class="text-center text-adiantado">Nº / % Adiantado</th>
                                     <th class="text-center text-supressao">Nº / % Supressão</th>
                                     <th class="text-center text-sem-dados">Nº / % Sem Dados</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 <?php $has_vehicle_data = false; ?>
                                 <?php foreach ($motoristas_veiculos_detalhado as $operador => $veiculos): ?>
                                     <?php foreach ($veiculos as $i => $registo): 
                                         $has_vehicle_data = true;
                                         $divisor = $registo['total_viagens'] > 0 ? $registo['total_viagens'] : 1; ?>
                                     <tr>
                                         <?php if ($i === 0): ?>
                                             <td rowspan="<?= count($veiculos) ?>"><?= htmlspecialchars($operador) ?> (<?= htmlspecialchars($registo['matricula']) ?>)</td>
                                         <?php endif; ?>
                                         <td class="text-center"><?= htmlspecialchars($registo['vehicle']) ?></td>
                                         <td class="text-center"><?= $registo['total_viagens'] ?></td>
                                         <td class="text-center"><?= $registo['total_no_horario']; ?> <span class="text-xs text-gray-500">/ <?= round(($registo['total_no_horario'] / $divisor) * 100, 1); ?>%</span></td>
                                         <td class="text-center"><?= $registo['total_atrasado']; ?> <span class="text-xs text-gray-500">/ <?= round(($registo['total_atrasado'] / $divisor) * 100, 1); ?>%</span></td>
                                         <td class="text-center"><?= $registo['total_adiantado']; ?> <span class="text-xs text-gray-500">/ <?= round(($registo['total_adiantado'] / $divisor) * 100, 1); ?>%</span></td>
                                         <td class="text-center"><?= $registo['total_supressao']; ?> <span class="text-xs text-gray-500">/ <?= round(($registo['total_supressao'] / $divisor) * 100, 1); ?>%</span></td>
                                         <td class="text-center"><?= $registo['total_sem_dados']; ?> <span class="text-xs text-gray-500">/ <?= round(($registo['total_sem_dados'] / $divisor) * 100, 1); ?>%</span></td>
                                     </tr>
                                     <?php endforeach; ?>
                                 <?php endforeach; ?>
                                  <?php if (!$has_vehicle_data): ?>
                                     <tr><td colspan="8" class="p-4 text-center text-gray-500">Nenhum dado encontrado para esta combinação.</td></tr>
                                 <?php endif; ?>
                             </tbody>
                         </table>
                     </div>
                 </section>
 
                 <!-- Pontos com Mais Atrasos / Adiantamentos -->
                <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-bold text-lg mb-3 text-atrasado">Pontos com Mais Atrasos</h3>
                        <div class="table-scroll-container" style="max-height: 300px;">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-200"><tr><th class="p-2 text-left">Ponto</th><th class="p-2 text-left">Linha</th><th class="p-2 text-center">Qtd</th></tr></thead>
                                <tbody><?php foreach ($top_pontos_atraso as $ponto): ?><tr><td class="p-2 border-b"><?= htmlspecialchars($ponto['ponto_controle']) ?></td><td class="p-2 border-b"><?= htmlspecialchars($ponto['route']) ?></td><td class="p-2 border-b text-center font-semibold text-atrasado"><?= $ponto['quantidade'] ?></td></tr><?php endforeach; ?></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-bold text-lg mb-3 text-adiantado">Pontos com Mais Adiantamentos</h3>
                        <div class="table-scroll-container" style="max-height: 300px;">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-200"><tr><th class="p-2 text-left">Ponto</th><th class="p-2 text-left">Linha</th><th class="p-2 text-center">Qtd</th></tr></thead>
                                <tbody><?php foreach ($top_pontos_adiantado as $ponto): ?><tr><td class="p-2 border-b"><?= htmlspecialchars($ponto['ponto_controle']) ?></td><td class="p-2 border-b"><?= htmlspecialchars($ponto['route']) ?></td><td class="p-2 border-b text-center font-semibold text-adiantado"><?= $ponto['quantidade'] ?></td></tr><?php endforeach; ?></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Maiores Atrasos / Adiantamentos Registrados com novas colunas -->
                 <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-bold text-lg mb-3 text-atrasado">Maiores Atrasos Registrados</h3>
                        <div class="table-scroll-container" style="max-height: 300px;">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-200"><tr>
                                    <th class="p-1 text-left">Ponto</th><th class="p-1 text-left">Linha</th>
                                    <th class="p-1 text-center">Bloco</th><th class="p-1 text-center">WorkID</th>
                                    <th class="p-1 text-left">Via</th><th class="p-1 text-center">Sentido</th>
                                    <th class="p-1 text-center">Programado</th><th class="p-1 text-center">Real</th>
                                    <th class="p-1 text-center">Desvio</th><th class="p-1 text-center">Excesso</th>
                                </tr></thead>
                                <tbody><?php foreach ($top_maiores_atrasos as $reg): ?><tr>
                                    <td class="p-1 border-b"><?= htmlspecialchars($reg['ponto_controle']) ?></td>
                                    <td class="p-1 border-b"><?= htmlspecialchars($reg['route']) ?></td>
                                    <td class="p-1 border-b text-center"><?= htmlspecialchars($reg['bloco']) ?></td>
                                    <td class="p-1 border-b text-center"><?= htmlspecialchars($reg['workid']) ?></td>
                                    <td class="p-1 border-b"><?= htmlspecialchars($reg['sentido_via']) ?></td>
                                    <td class="p-1 border-b text-center"><?= htmlspecialchars($reg['direcao']) ?></td>
                                    <td class="p-1 border-b text-center"><?= $reg['horario_programado'] ? (new DateTime($reg['horario_programado']))->format('H:i') : '-' ?></td>
                                    <td class="p-1 border-b text-center"><?= $reg['horario_real'] ? (new DateTime($reg['horario_real']))->format('H:i') : '-' ?></td>
                                    <td class="p-1 border-b text-center font-mono"><?= formatarTempoSQL($reg['desvio']) ?></td>
                                    <td class="p-1 border-b text-center font-bold text-atrasado"><?= formatarDesvioExcedente($reg['desvio'], $tolerancia_atraso_segundos, $tolerancia_adiantado_segundos) ?></td>
                                </tr><?php endforeach; ?></tbody>
                            </table>
                        </div>
                    </div>
                     <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-bold text-lg mb-3 text-adiantado">Maiores Adiantamentos Registrados</h3>
                        <div class="table-scroll-container" style="max-height: 300px;">
                            <table class="w-full text-sm">
                                 <thead class="bg-gray-200"><tr>
                                    <th class="p-1 text-left">Ponto</th><th class="p-1 text-left">Linha</th>
                                    <th class="p-1 text-center">Bloco</th><th class="p-1 text-center">WorkID</th>
                                    <th class="p-1 text-left">Via</th><th class="p-1 text-center">Sentido</th>
                                    <th class="p-1 text-center">Programado</th><th class="p-1 text-center">Real</th>
                                    <th class="p-1 text-center">Desvio</th><th class="p-1 text-center">Excesso</th>
                                </tr></thead>
                                <tbody><?php foreach ($top_maiores_adiantamentos as $reg): ?><tr>
                                    <td class="p-1 border-b"><?= htmlspecialchars($reg['ponto_controle']) ?></td>
                                    <td class="p-1 border-b"><?= htmlspecialchars($reg['route']) ?></td>
                                    <td class="p-1 border-b text-center"><?= htmlspecialchars($reg['bloco']) ?></td>
                                    <td class="p-1 border-b text-center"><?= htmlspecialchars($reg['workid']) ?></td>
                                    <td class="p-1 border-b"><?= htmlspecialchars($reg['sentido_via']) ?></td>
                                    <td class="p-1 border-b text-center"><?= htmlspecialchars($reg['direcao']) ?></td>
                                    <td class="p-1 border-b text-center"><?= $reg['horario_programado'] ? (new DateTime($reg['horario_programado']))->format('H:i') : '-' ?></td>
                                    <td class="p-1 border-b text-center"><?= $reg['horario_real'] ? (new DateTime($reg['horario_real']))->format('H:i') : '-' ?></td>
                                    <td class="p-1 border-b text-center font-mono"><?= formatarTempoSQL($reg['desvio']) ?></td>
                                    <td class="p-1 border-b text-center font-bold text-adiantado"><?= formatarDesvioExcedente($reg['desvio'], $tolerancia_atraso_segundos, $tolerancia_adiantado_segundos) ?></td>
                                </tr><?php endforeach; ?></tbody>
                            </table>
                        </div>
                    </div>
                </div>
 
                  <!-- *** NOVO: Listagem Não Login *** -->
                 <section class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                     <h3 class="font-bold text-lg mb-3 text-nao-login">Listagem de Registros 'Não Login'</h3>
                     <div class="table-scroll-container">
                         <table class="w-full text-sm whitespace-nowrap">
                             <thead class="bg-gray-200">
                                 <tr>
                                     <th class="p-1 text-left">Linha</th>
                                     <th class="p-1 text-center">Bloco</th>
                                     <th class="p-1 text-center">WorkID</th>
                                     <th class="p-1 text-left">Ponto de Controle</th>
                                     <th class="p-1 text-left">Via</th>
                                     <th class="text-center">Sentido</th>
                                     <th class="text-center">Programado</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 <?php foreach ($lista_nao_login as $reg): ?>
                                 <tr>
                                     <td class="p-1 border-b"><?= htmlspecialchars($reg['route']) ?></td>
                                     <td class="p-1 border-b text-center"><?= htmlspecialchars($reg['bloco']) ?></td>
                                     <td class="p-1 border-b text-center"><?= htmlspecialchars($reg['workid']) ?></td>
                                     <td class="p-1 border-b"><?= htmlspecialchars($reg['ponto_controle']) ?></td>
                                     <td class="p-1 border-b"><?= htmlspecialchars($reg['sentido_via']) ?></td>
                                     <td class="p-1 border-b text-center"><?= htmlspecialchars($reg['direcao']) ?></td>
                                     <td class="p-1 border-b text-center"><?= $reg['horario_programado'] ? (new DateTime($reg['horario_programado']))->format('d/m H:i') : '-' ?></td>
                                 </tr>
                                 <?php endforeach; ?>
                                 <?php if (empty($lista_nao_login)): ?>
                                     <tr><td colspan="7" class="p-4 text-center text-gray-500">Nenhum registro 'Não Login' encontrado para os filtros selecionados.</td></tr>
                                 <?php endif; ?>
                             </tbody>
                         </table>
                     </div>
                 </section>
 
                 <!-- *** NOVO: Listagem Sem Dados *** -->
                 <section class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                     <h3 class="font-bold text-lg mb-3 text-sem-dados">Listagem de Registros 'Sem Dados'</h3>
                      <div class="table-scroll-container">
                         <table class="w-full text-sm whitespace-nowrap">
                             <thead class="bg-gray-200">
                                 <tr>
                                     <th class="p-1 text-left">Linha</th>
                                     <th class="p-1 text-center">Bloco</th>
                                     <th class="p-1 text-center">WorkID</th>
                                     <th class="p-1 text-left">Motorista</th>
                                     <th class="p-1 text-left">Matrícula</th>
                                     <th class="p-1 text-left">Ponto de Controle</th>
                                     <th class="p-1 text-left">Via</th>
                                     <th class="p-1 text-center">Sentido</th>
                                     <th class="p-1 text-center">Programado</th>
                                 </tr>
                             </thead>
                            <tbody>
                                 <?php foreach ($lista_sem_dados as $reg): ?>
                                 <tr>
                                     <td class="p-1 border-b"><?= htmlspecialchars($reg['route']) ?></td>
                                     <td class="p-1 border-b text-center"><?= htmlspecialchars($reg['bloco']) ?></td>
                                     <td class="p-1 border-b text-center"><?= htmlspecialchars($reg['workid']) ?></td>
                                     <td class="p-1 border-b"><?= htmlspecialchars($reg['operator']) ?></td>
                                     <td class="p-1 border-b text-center"><?= htmlspecialchars($reg['matricula']) ?></td>
                                     <td class="p-1 border-b"><?= htmlspecialchars($reg['ponto_controle']) ?></td>
                                     <td class="p-1 border-b"><?= htmlspecialchars($reg['sentido_via']) ?></td>
                                     <td class="p-1 border-b text-center"><?= htmlspecialchars($reg['direcao']) ?></td>
                                     <td class="p-1 border-b text-center"><?= $reg['horario_programado'] ? (new DateTime($reg['horario_programado']))->format('d/m H:i') : '-' ?></td>
                                 </tr>
                                 <?php endforeach; ?>
                                 <?php if (empty($lista_sem_dados)): ?>
                                     <tr><td colspan="9" class="p-4 text-center text-gray-500">Nenhum registro 'Sem Dados' encontrado para os filtros selecionados.</td></tr>
                                 <?php endif; ?>
                             </tbody>
                         </table>
                     </div>
                 </section>
 
             </div> <!-- Fim do space-y-8 principal -->
         </div> <!-- Fim do bg-white principal -->
     </main>
 
     <script>
         document.addEventListener('DOMContentLoaded', () => {
             const ctx = document.getElementById('statusChart');
             // *** CORREÇÃO AQUI: Passa os dados e cores do PHP para o JavaScript ***
             const chartLabels = <?= json_encode($chart_labels) ?>;
             const chartData = <?= json_encode($chart_data) ?>;
             const chartBackgroundColors = <?= json_encode($chart_background_colors) ?>;
 
             if (ctx && chartData.length > 0) { // Verifica se há dados antes de criar o gráfico
                 new Chart(ctx, {
                     type: 'doughnut',
                     data: {
                         labels: chartLabels,
                         datasets: [{
                             data: chartData,
                             backgroundColor: chartBackgroundColors, // Usa as cores corrigidas
                             borderColor: '#fff',
                             borderWidth: 2
                         }]
                     },
                     options: {
                         responsive: true,
                         maintainAspectRatio: false,
                         plugins: { 
                             legend: { display: false }, // Legenda já está na lista ao lado
                             tooltip: {
                                 callbacks: {
                                     label: function(context) {
                                         let label = context.label || '';
                                         if (label) {
                                             label += ': ';
                                         }
                                         if (context.parsed !== null) {
                                             // Calcula a percentagem
                                             const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                             const value = context.parsed;
                                             const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                             label += `${value} (${percentage}%)`;
                                         }
                                         return label;
                                     }
                                 }
                             }
                         },
                         cutout: '60%'
                     }
                 });
             } else if (ctx) {
                 // Opcional: Mostrar mensagem se não houver dados para o gráfico
                 const context = ctx.getContext('2d');
                 context.textAlign = 'center';
                 context.textBaseline = 'middle';
                 context.fillText('Sem dados para exibir o gráfico.', ctx.width / 2, ctx.height / 2);
             }
         });
     </script>
 
 </body>
 </html>
 

