 <?php
 // =================================================================
 //  Configuração de Metas de KM e Funções Auxiliares (v1.6)
 //  - CORRIGIDO: Avisos 'Deprecated' de conversão float para int
 //    adicionando (int)round() nas funções de formatação de tempo.
 // =================================================================
 
 // --- METAS DE KM PROGRAMADO (Exemplo para 2025) ---
 $metas_km_programado_anual = [
     '2025' => 820790.00,
	 '2026' => 820790.00
 ];
 
 // --- CORES PARA GRÁFICO DE COMUNICAÇÃO ---
 $cores_grafico_comunicacao = [
     'Logado em Linha'   => '#16a34a', // Verde
     'Em Permanência'    => '#f59e0b', // Ambar
     'Recolhendo'        => '#ef4444', // Vermelho
     'Saindo da Garagem' => '#3b82f6', // Azul
     'Sem Login'         => '#6b7280', // Cinza
     'Outro'             => '#9ca3af'  // Cinza claro (fallback)
 ];
 
 // --- ARRAY MANUAL DE MESES (para corrigir erro IntlDateFormatter) ---
 $meses_pt_br = [
     1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
     7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
 ];
 
 /**
  * Calcula o número de dias úteis, sábados e domingos num determinado mês e ano.
  */
 function calcular_dias_uteis_sab_dom($ano, $mes) {
     if ($mes < 1 || $mes > 12) return null;
     $uteis = 0; $sabados = 0; $domingos = 0;
     $total_dias = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
     for ($dia = 1; $dia <= $total_dias; $dia++) {
         $data_iso = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
         $timestamp = strtotime($data_iso);
         if ($timestamp === false) continue;
         $dia_semana = date('N', $timestamp); // 1 (Seg) a 7 (Dom)
         if ($dia_semana == 7) $domingos++;
         elseif ($dia_semana == 6) $sabados++;
         else $uteis++;
     }
     return ['uteis' => $uteis, 'sabados' => $sabados, 'domingos' => $domingos];
 }
 
 /**
  * Formata segundos em HH:MM:SS.
  */
 function formatar_segundos_hhmmss($totalSegundos) {
     if (!is_numeric($totalSegundos) || $totalSegundos <= 0) return "00:00:00";
     // CORREÇÃO: Converte para int arredondado
     $totalSegundos = (int)round($totalSegundos); 
     $horas = floor($totalSegundos / 3600);
     $minutos = floor(($totalSegundos % 3600) / 60);
     $segundos = $totalSegundos % 60;
     return sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos);
 }
 
 /**
  * Formata um intervalo de tempo em segundos para um formato extenso (anos, meses, dias, HH:MM:SS).
  */
 function formatar_intervalo_tempo_extenso($totalSegundos) {
     if (!is_numeric($totalSegundos) || $totalSegundos < 0) return "0 segundos";
     // CORREÇÃO: Converte para int arredondado
     $totalSegundos = (int)round($totalSegundos);
     if ($totalSegundos == 0) return "0 segundos";
 
     $anos = floor($totalSegundos / (365 * 24 * 3600));
     $totalSegundos %= (365 * 24 * 3600);
     $meses = floor($totalSegundos / (30 * 24 * 3600)); // Aproximação
     $totalSegundos %= (30 * 24 * 3600);
     $dias = floor($totalSegundos / (24 * 3600));
     $totalSegundos %= (24 * 3600);
     $horas = floor($totalSegundos / 3600);
     $totalSegundos %= 3600;
     $minutos = floor($totalSegundos / 60);
     $segundos = $totalSegundos % 60;
 
     $partes = [];
     if ($anos > 0) $partes[] = $anos . ($anos > 1 ? " anos" : " ano");
     if ($meses > 0) $partes[] = $meses . ($meses > 1 ? " meses" : " mês");
     if ($dias > 0) $partes[] = $dias . ($dias > 1 ? " dias" : " dia");
     if ($horas > 0) $partes[] = $horas . ($horas > 1 ? " horas" : " hora");
     if ($minutos > 0) $partes[] = $minutos . ($minutos > 1 ? " minutos" : " minuto");
     if ($segundos > 0) $partes[] = $segundos . ($segundos > 1 ? " segundos" : " segundo");
 
     if (count($partes) == 0) return "0 segundos";
     if (count($partes) == 1) return $partes[0];
     $ultimaParte = array_pop($partes);
     return implode(', ', $partes) . ' e ' . $ultimaParte;
 }
 
 /**
  * Processa o campo 'motorista_matricula_raw' da tabela de comunicação.
  */
 function processar_motorista_comunicacao($raw_string) {
     $nome_formatado = 'N/A';
     $matricula_formatada = null;
     $raw_string = trim($raw_string ?? '');
 
     if ($raw_string === 'No Badge Provided' || empty($raw_string)) {
         $nome_formatado = 'Sem Login';
     } else {
         $parts = array_map('trim', explode(',', $raw_string));
         if (count($parts) >= 3 && is_numeric(end($parts))) {
             $matricula_num = (int)array_pop($parts);
             $matricula_formatada = (string)$matricula_num;
             if (count($parts) >= 2) {
                 $sobrenome = array_shift($parts);
                 $nome_partes = $parts;
                 $nome_formatado = trim(implode(' ', $nome_partes) . ' ' . $sobrenome);
             } else { $nome_formatado = trim(implode(' ', $parts)); }
         } elseif (count($parts) === 2) {
              $nome_formatado = $parts[1] . ' ' . $parts[0];
         } else { $nome_formatado = $raw_string; }
     }
     return ['nome' => $nome_formatado, 'matricula' => $matricula_formatada];
 }
 
 /**
  * Cria um link para o Google Maps a partir de latitude e longitude.
  */
 function criar_link_maps($latitude, $longitude) {
      if ($latitude === null || $longitude === null || trim($latitude) === '' || trim($longitude) === '') return '';
     $lat_float = filter_var(str_replace(',', '.', $latitude), FILTER_VALIDATE_FLOAT);
     $lon_float = filter_var(str_replace(',', '.', $longitude), FILTER_VALIDATE_FLOAT);
     if ($lat_float !== false && $lon_float !== false &&
         $lat_float >= -90 && $lat_float <= 90 &&
         $lon_float >= -180 && $lon_float <= 180 &&
         !($lat_float == 0 && $lon_float == 0))
     {
         return "http://www.google.com/maps/search/?api=1&query=" . urlencode($lat_float . ',' . $lon_float);
     }
     return '';
 }
 
 /**
  * Processa o campo 'funcionario_raw' da tabela Life.
  */
 function processar_funcionario_life($raw_string) {
      $nome_formatado = 'N/A';
      $matricula_formatada = null;
      $raw_string = trim($raw_string ?? '');
 
      if(empty($raw_string) || $raw_string === 'Não Informado') {
         $nome_formatado = 'Não Informado';
      } elseif (preg_match('/^(.*?)(?:-\s*)?\((\w+)\)\s*$/', $raw_string, $matches)) {
          $nome_formatado = trim($matches[1]);
          $matricula_formatada = trim($matches[2]);
      } else { $nome_formatado = $raw_string; }
      return ['nome' => $nome_formatado, 'matricula' => $matricula_formatada];
 }
 
 /**
  * Formata a string da linha de comunicação para exibição E categorização.
  *
  * @param string|null $linha_raw A string original da linha.
  * @return string A linha formatada ("Recolhendo para X") ou categoria ("Sem Login") ou a linha original limpa.
  */
 function formatar_linha_comunicacao($linha_raw) {
     $linha = trim($linha_raw ?? '');
     if (empty($linha) || $linha === 'Not Provided') {
         return 'Sem Login';
     }
 
     // Remove prefixos numéricos e espaços (ex: "       221 - Limoeiro")
     $linha_limpa = trim(preg_replace('/^\s*\d+\s*-\s*/', '', $linha));
 
     // Padrões de extração (mais específicos)
     // Tenta capturar o local após o ponto, hífen ou espaço
     $pull_out_pattern = '/Pull-out from BT-G(?:ARCIA)?[\.\-\s]?(.*)/i';
     $pull_in_pattern = '/Pull-in to BT-G(?:ARCIA)?[\.\-\s]?(.*)/i';
     $deadhead_pattern = '/DeadHead from BT-G?-?(.*)/i';
 
     if (preg_match($pull_out_pattern, $linha_limpa, $matches)) {
         $local = trim($matches[1]);
         return 'Saindo da Garagem' . ($local ? " ($local)" : '');
     } elseif (preg_match($pull_in_pattern, $linha_limpa, $matches)) {
         $local = trim($matches[1]);
         return 'Recolhendo para' . ($local ? " ($local)" : '');
     } elseif (preg_match($deadhead_pattern, $linha_limpa, $matches)) {
         $local = trim($matches[1]);
         return 'Em Permanência' . ($local ? " ($local)" : '');
     }
 
     // Se não for especial, retorna a linha limpa
     return $linha_limpa;
 }
 
 ?>
