<?php
// =================================================================
//  Parceiro de Programação - Central de Controle e Importação
//  VERSÃO 18.0: Adicionado Módulo de Importação de Rotas (KML)
// =================================================================

// --- 1. CONFIGURAÇÕES E INICIALIZAÇÃO ---
ini_set('upload_max_filesize', '512M');
ini_set('post_max_size', '600M');
ini_set('max_file_uploads', '100');
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(1800); // 30 minutos
setlocale(LC_ALL, 'pt_BR.utf8');
mb_internal_encoding('UTF-8');

if (file_exists('config_km.php')) { require_once 'config_km.php'; }
if (file_exists('config_bilhetagem.php')) { require_once 'config_bilhetagem.php'; }

$upload_message = '';
$message_type = '';
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "relatorio";

// --- FUNÇÕES AUXILIARES (Mantidas) ---
function converter_linha_para_utf8($linha_array) {
    return array_map(function($celula) {
        if (!is_string($celula)) return $celula;
        $encoding = mb_detect_encoding($celula, 'UTF-8, ISO-8859-1, Windows-1252', true);
        if ($encoding && $encoding !== 'UTF-8') {
            return mb_convert_encoding($celula, 'UTF-8', $encoding);
        }
        if ($encoding === 'UTF-8' && !mb_check_encoding($celula, 'UTF-8')) {
             return mb_convert_encoding($celula, 'UTF-8', 'UTF-8');
         }
        return $celula;
    }, $linha_array);
}

function extrair_data_filename($filename) {
    if (preg_match('/(\d{4}-\d{2}-\d{2})/', $filename, $matches)) {
        return $matches[1];
    } elseif (preg_match('/(\d{2}-\d{2}-\d{4})/', $filename, $matches)) {
        $date_obj = DateTime::createFromFormat('d-m-Y', $matches[1]);
        if ($date_obj) {
            return $date_obj->format('Y-m-d');
        }
    }
    return null;
}

function limpar_numero_ptbr($valor) {
   if (empty($valor)) return 0;
   $valor = str_replace('.', '', $valor);
   $valor = str_replace(',', '.', $valor);
   return (float)$valor;
}

function converter_data_hora_br($data_hora_str) {
    if (empty($data_hora_str)) return null;
    $data_hora_str = trim($data_hora_str, '" ');
    $formats_to_try = [
        'd-m-Y H:i:s', 'd-m-Y H:i', 'd/m/Y H:i:s', 'd/m/Y H:i', 'Y-m-d H:i:s', 'Y-m-d H:i'
    ];
    foreach ($formats_to_try as $format) {
        $date_obj = DateTime::createFromFormat($format, $data_hora_str);
        $check_format = (strpos($format, ':s') === false && strlen($format) > 5) ? substr($format, 0, -3) : $format;
        $original_check = (strpos($format, ':s') === false && strlen($data_hora_str) > 5) ? substr($data_hora_str, 0, -3) : $data_hora_str;
        if ($date_obj && $date_obj->format($check_format) === $original_check) {
            return $date_obj->format('Y-m-d H:i:s');
        }
    }
    try {
        $timestamp = strtotime($data_hora_str);
        if ($timestamp !== false) { return date('Y-m-d H:i:s', $timestamp); }
    } catch (Exception $e) {}
    return null;
}

function limpar_prefixo_veiculo($veiculo_str) {
    if ($veiculo_str === null) return null;
    $veiculo_str = trim($veiculo_str);
    if (preg_match('/^(\d+)/', $veiculo_str, $matches)) {
        return $matches[1];
    }
    return null;
}

function formatar_data_noxxon_para_iso($data_str) {
   if (empty($data_str)) return null;
   $data_str = trim($data_str);
   $meses_map = [
       'jan.' => '01', 'fev.' => '02', 'mar.' => '03', 'abr.' => '04',
       'mai.' => '05', 'jun.' => '06', 'jul.' => '07', 'ago.' => '08',
       'set.' => '09', 'out.' => '10', 'nov.' => '11', 'dez.' => '12'
   ];
   $data_str_limpa = str_replace(' de ', ' ', $data_str);
   $data_str_lower = mb_strtolower($data_str_limpa, 'UTF-8');
   if (preg_match('/^(\d{1,2})\s+(.*?)\s+(\d{4})$/', $data_str_lower, $matches)) {
       $dia = $matches[1]; $mes_texto = $matches[2]; $ano = $matches[3];
       $mes_num = $meses_map[$mes_texto] ?? null;
       if ($mes_num) {
           $dia_formatado = sprintf('%02d', $dia);
           return "$ano-$mes_num-$dia_formatado";
       }
   }
   try {
       $timestamp = strtotime($data_str);
       if ($timestamp !== false) { return date('Y-m-d', $timestamp); }
   } catch (Exception $e) {}
   return null;
}

function converter_tempo_para_segundos($tempo) {
     if (empty($tempo) || !is_string($tempo)) return 0;
     $tempo = trim($tempo);
     $parts = explode(':', $tempo);
     if (count($parts) === 3) {
         return ((int)$parts[0] * 3600) + ((int)$parts[1] * 60) + (int)$parts[2];
     } elseif (count($parts) === 2) {
          return ((int)$parts[0] * 3600) + ((int)$parts[1] * 60);
     }
     return 0;
}

function processar_operador($operator_raw, &$nome, &$matricula) {
    $nome = null; $matricula = null;
    if ($operator_raw === null) return;
    $operator_raw = trim($operator_raw);
    if ($operator_raw === 'No Badge Provided' || empty($operator_raw)) {
        $nome = 'No Badge Provided'; return;
    }
    $parts = array_map('trim', explode(',', $operator_raw));
    if (count($parts) >= 3 && is_numeric(end($parts))) {
        $matricula = (int)array_pop($parts);
        if(count($parts) >= 2){
            $sobrenome = array_shift($parts);
            $nome = trim(implode(' ', $parts) . ' ' . $sobrenome);
        } else { $nome = trim(implode(' ', $parts)); }
        return;
    }
    if (count($parts) === 2 && !is_numeric(end($parts))) {
        $matricula = null; $nome = $parts[1] . ' ' . $parts[0]; return;
    }
    if (preg_match('/^(.*?)(?:-\s*)?\((\w+)\)\s*$/', $operator_raw, $matches)) {
        $nome = trim($matches[1]);
        $matricula = is_numeric($matches[2]) ? (int)$matches[2] : $matches[2]; 
        return;
    }
    $nome = $operator_raw; $matricula = null;
}

// --- NOVA FUNÇÃO: CONVERTER UTM PARA LAT/LON (ZONA 22S - LONDRINA) ---
function converterUtmParaLatLon($utmEasting, $utmNorthing, $zona = 22) {
    // 1. Limpeza (Remove pontos de milhar, ex: 483.611.99 -> 483611.99)
    $eastingStr = preg_replace('/[^0-9]/', '', $utmEasting);
    $northingStr = preg_replace('/[^0-9]/', '', $utmNorthing);

    // Ajusta casas decimais (X=6 inteiros, Y=7 inteiros)
    $easting = (strlen($eastingStr) > 6) ? floatval(substr($eastingStr, 0, 6) . '.' . substr($eastingStr, 6)) : floatval($eastingStr);
    $northing = (strlen($northingStr) > 7) ? floatval(substr($northingStr, 0, 7) . '.' . substr($northingStr, 7)) : floatval($northingStr);

    if ($easting == 0 || $northing == 0) return null;

    // Constantes WGS84
    $a = 6378137; $eccSquared = 0.00669438; $k0 = 0.9996;
    $e1 = (1 - sqrt(1 - $eccSquared)) / (1 + sqrt(1 - $eccSquared));
    $x = $easting - 500000.0; $y = $northing - 10000000.0; // Sul

    $m = $y / $k0;
    $mu = $m / ($a * (1 - $eccSquared/4 - 3*$eccSquared*$eccSquared/64 - 5*pow($eccSquared,3)/256));
    
    $phi1Rad = $mu + (3*$e1/2 - 27*pow($e1,3)/32)*sin(2*$mu) + (21*$e1*$e1/16 - 55*pow($e1,4)/32)*sin(4*$mu) + (151*pow($e1,3)/96)*sin(6*$mu);
    $n = $a / sqrt(1 - $eccSquared * sin($phi1Rad) * sin($phi1Rad));
    $t = tan($phi1Rad) * tan($phi1Rad);
    $c = ($eccSquared / (1 - $eccSquared)) * cos($phi1Rad) * cos($phi1Rad);
    $r = $a * (1 - $eccSquared) / pow(1 - $eccSquared * sin($phi1Rad) * sin($phi1Rad), 1.5);
    $d = $x / ($n * $k0);

    $lat = $phi1Rad - ($n*tan($phi1Rad)/$r)*($d*$d/2 - (5+3*$t+10*$c-4*$c*$c-9*$eccSquared)*pow($d,4)/24 + (61+90*$t+298*$c+45*$t*$t-252*$eccSquared-3*$c*$c)*pow($d,6)/720);
    $lat = rad2deg($lat);
    $lon = ($d - (1+2*$t+$c)*pow($d,3)/6 + (5-2*$c+28*$t-3*$c*$c+8*$eccSquared+24*$t*$t)*pow($d,5)/120) / cos($phi1Rad);
    $lon = rad2deg($lon) + (($zona - 1) * 6 - 180 + 3);

    return number_format($lat, 6, '.', '') . ", " . number_format($lon, 6, '.', '');
}

function formatar_data_br_para_iso($data_str) {
     if (empty($data_str)) return null;
     $data_str = trim($data_str, ' "');
     try {
         $date_obj = DateTime::createFromFormat('d/m/Y', $data_str);
         if ($date_obj && $date_obj->format('d/m/Y') === $data_str) return $date_obj->format('Y-m-d');
         
         $date_obj = DateTime::createFromFormat('d-m-Y', $data_str);
         if ($date_obj && $date_obj->format('d-m-Y') === $data_str) return $date_obj->format('Y-m-d');
         
         $date_obj = DateTime::createFromFormat('m/d/Y', $data_str);
         if ($date_obj && $date_obj->format('m/d/Y') === $data_str) return $date_obj->format('Y-m-d');

         $timestamp = strtotime($data_str);
         if ($timestamp !== false) return date('Y-m-d', $timestamp);
     } catch (Exception $e) {}
     return null;
}

function formatar_data_americana_para_iso($data_str) {
    if (empty($data_str)) return null;
    $data_str = trim($data_str, ' "');
    try {
        $date_obj = DateTime::createFromFormat('m/d/Y', $data_str);
        if ($date_obj && $date_obj->format('m/d/Y') === $data_str) return $date_obj->format('Y-m-d');
        
        $date_obj = DateTime::createFromFormat('m-d-Y', $data_str);
        if ($date_obj && $date_obj->format('m-d-Y') === $data_str) return $date_obj->format('Y-m-d');
        
        return formatar_data_br_para_iso($data_str);
    } catch (Exception $e) {}
    return null;
}

function combinar_data_hora($data_iso, $hora_str) {
     if (empty($data_iso) || empty($hora_str) || $hora_str === null) return null;
     $hora_str = trim($hora_str);
     if (strtolower($hora_str) === 'nan' || $hora_str === '') return null;
     if (preg_match('/^(\d{1,2}:\d{2})/', $hora_str, $matches)) {
         $hora_limpa = $matches[1];
         if (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $hora_limpa)) {
             try {
                 $datetime_obj = new DateTime($data_iso . ' ' . $hora_limpa . ':00');
                 return $datetime_obj->format('Y-m-d H:i:s');
             } catch (Exception $e) { return null; }
         }
     }
     return null;
}


// --- 2. LÓGICA DE PROCESSAMENTO DO UPLOAD ---

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --- BLOCO 1: IMPORTAÇÃO DE KML (NOVO) ---
    if (isset($_FILES['kmlfile'])) {
        if ($_FILES['kmlfile']['error'] === UPLOAD_ERR_OK) {
            try {
                $xml = simplexml_load_file($_FILES['kmlfile']['tmp_name']);
                if ($xml === false) throw new Exception("Não foi possível ler o arquivo KML.");

                // Registra namespace para encontrar as tags
                $xml->registerXPathNamespace('kml', 'http://www.opengis.net/kml/2.2');

                // Conexão PDO específica para o KML (mais fácil lidar com parâmetros nomeados)
                $dsn_kml = "mysql:host=$servidor;dbname=$banco;charset=utf8mb4";
                $pdo_kml = new PDO($dsn_kml, $usuario, $senha, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

                $count_kml = 0;
                $stmt = $pdo_kml->prepare("INSERT INTO rotas_geometria (codigo_variante, linha, sentido, cor_hex, pontos_json) VALUES (:cod, :lin, :sen, :cor, :pts) ON DUPLICATE KEY UPDATE pontos_json = :pts, cor_hex = :cor, linha = :lin, sentido = :sen");

                foreach ($xml->xpath('//kml:Placemark') as $placemark) {
                    $codice = ''; $linha = ''; $codverso = 0;
                    
                    // Extrai metadados do SchemaData
                    foreach ($placemark->ExtendedData->SchemaData->SimpleData as $data) {
                        $attrs = $data->attributes();
                        $name = (string)$attrs['name'];
                        $val = (string)$data;
                        if ($name === 'CODICE') $codice = $val;
                        if ($name === 'LINE(UNIQU') $linha = $val;
                        if ($name === 'CODVERSO') $codverso = (int)$val;
                    }

                    // Extrai Cor (KML é AABBGGRR -> converter para #RRGGBB)
                    $kmlColor = (string)$placemark->Style->LineStyle->color;
                    $htmlColor = '#0000FF'; // Padrão
                    if (strlen($kmlColor) == 8) {
                        $blue = substr($kmlColor, 2, 2);
                        $green = substr($kmlColor, 4, 2);
                        $red = substr($kmlColor, 6, 2);
                        $htmlColor = "#" . $red . $green . $blue;
                    }

                    // Extrai Coordenadas
                    $coordsRaw = (string)$placemark->LineString->coordinates;
                    $pontos = explode(' ', trim($coordsRaw));
                    $pontosJsonArray = [];
                    foreach ($pontos as $p) {
                        if (empty($p)) continue;
                        $parts = explode(',', $p);
                        if (count($parts) >= 2) {
                            // Leaflet usa [lat, lon]
                            $pontosJsonArray[] = [(float)$parts[1], (float)$parts[0]];
                        }
                    }
                    
                    if ($codice && !empty($pontosJsonArray)) {
                        $jsonFinal = json_encode($pontosJsonArray);
                        $stmt->execute([
                            ':cod' => $codice, ':lin' => $linha, ':sen' => $codverso,
                            ':cor' => $htmlColor, ':pts' => $jsonFinal
                        ]);
                        $count_kml++;
                    }
                }
                
                $upload_message = "KML Processado! $count_kml rotas importadas/atualizadas.";
                $message_type = 'success';

            } catch (Exception $e) {
                $upload_message = "Erro na importação KML: " . $e->getMessage();
                $message_type = 'error';
            }
        } else {
            $upload_message = "Erro no upload do arquivo KML.";
            $message_type = 'error';
        }
    }
    // --- BLOCO 2: IMPORTAÇÃO DE CSV (EXISTENTE) ---
    elseif (isset($_FILES['csvfiles'])) {
 
        $form_type = $_POST['form_type'] ?? '';
        $import_mode = $_POST['import_mode'] ?? 'truncate';
        $data_inicio_vigencia = $_POST['data_inicio_vigencia'] ?? null;
        $data_fim_vigencia = $_POST['data_fim_vigencia'] ?? null;
 
        $files = $_FILES['csvfiles'];
        $total_linhas_importadas = 0;
        $total_arquivos_processados = 0;
        $nomes_arquivos_processados = [];
        $erros_ocorridos = [];
 
        $config = null;
 
        // --- Definição das configurações ---
        switch ($form_type) {
            
            case 'todos_horarios':
                $config = [
                    'table' => 'relatorios_todos_horarios',
                    'num_expected_cols' => 28,
                    'columns_db' => [
                        'data_viagem', 
                        'SERVICELEVEL', 'CALENDAR', 'OPERATINGDAY', 'PIECETYPE',
                        'STARTINGDATE', 'ENDINGDATE', 'LINE', 'LINEBASIN',
                        'LINECOMPANY', 'PATTERN', 'DIRECTION', 'TRIPCODE',
                        'TRIPCOMPANYCODE', 'ACTIVITYNUMBER', 'LENGTH',
                        'ARRIVALTIME', 'DEPARTURETIME', 'NODE', 'NODEBASIN',
                        'NODECOMPANY', 'PASSAGEORDER'
                    ],
                    'types_db' => 'sssssssssssssssdsssssi', 
                    'process_todos_horarios' => true,
                    'date_from_filename' => true,
                    'skip_lines' => 1,
                    'delimiter' => ';'
                ];
                break;

            case 'km_precisao':
                $config = [
                    'table' => 'relatorios_viagens_precisao',
                    'num_expected_cols' => 17,
                    'columns_db' => [
                        'data_viagem', 'duty_companycode', 'start_time', 'start_node', 'end_time', 
                        'end_node', 'description', 'length_km', 'vehicle_block', 'line_code', 
                        'pattern', 'direction', 'is_produtivo', 'is_ocioso'
                    ],
                    'types_db' => 'sssssssdssssii',
                    'process_km_precisa' => true,
                    'date_from_filename' => true,
                    'skip_lines' => 1,
                    'delimiter' => ','
                ];
                break;
            
            case 'bilhetagem':
                $config = [
                    'table' => 'relatorios_bilhetagem',
                    'num_expected_cols' => 16,
                    'columns_db' => [
                        'linha', 'data_viagem', 'viagens', 'frota', 'bonus', 'comum', 'contactless', 
                        'emv', 'escolar_100', 'escolar_duplo', 'escolar', 'funcionario', 'gratuitos', 
                        'integracao', 'pagantes', 'vale_transporte', 'total_passageiros'
                    ],
                    'types_db' => 'ssiiddddddddddddd', 
                    'process_bilhetagem' => true, 
                    'delimiter' => ';',
                    'skip_lines' => 0
                ];
                break;

            case 'relatorio_servicos': 
                if (empty($data_inicio_vigencia) || empty($data_fim_vigencia)) {
                    $erros_ocorridos[] = "Erro: Datas de vigência obrigatórias."; break;
                }
                if (strtotime($data_fim_vigencia) < strtotime($data_inicio_vigencia)) {
                    $erros_ocorridos[] = "Erro: Data Fim < Data Início."; break;
                }
                $config = [
                    'table' => 'relatorios_servicos',
                    'num_expected_cols' => 17,
                    'columns_db' => [
                        'VERSION', 'PROJECT_NAME', 'PROJUNIT_NAME', 'DUTY_COMPANYCODE', 'DUTY_ID', 
                        'PIECETYPE_NAME', 'REFERREDVB_COMPANYCODE', 'PRETIMESEC', 'POSTTIMESEC', 
                        'STARTNODE_COMPANYCODE', 'STARTNODE_BASIN', 'STARTNODE_COMPANY', 
                        'ENDNODE_COMPANYCODE', 'ENDNODE_BASIN', 'ENDNODE_COMPANY', 
                        'START_TIME', 'END_TIME', 'data_inicio_vigencia', 'data_fim_vigencia'
                    ],
                    'types_db' => 'sssisssiississsssss',
                    'process_servicos' => true,
                    'skip_lines' => 1,
                    'delimiter' => ';'
                ];
                break;
 
            case 'relatorio_viagens':
                $config = [
                    'table' => 'relatorios_viagens',
                    'num_expected_cols' => 11,
                    'columns_db' => [
                        'data_viagem', 'BLOCK_NUMBER', 'TRIP_CC', 'START_TIME', 'END_TIME',
                        'START_PLACE', 'END_PLACE', 'ROUTE_ID', 'ROUTE_VARIANT', 'DIRECTION_NUM', 'DISTANCE', 'TRIP_ID'
                    ],
                    'types_db' => 'ssissssssisi',
                    'process_viagens' => true,
                    'date_from_filename' => true,
                    'skip_lines' => 1,
                    'delimiter' => ','
                ];
                break;
 
            case 'relatorio_viagens_ajuste':
                $config = [
                    'table' => 'relatorios_viagens_ajuste',
                    'num_expected_cols' => 11,
                    'columns_db' => [
                        'data_viagem', 'BLOCK_NUMBER', 'TRIP_CC', 'START_TIME', 'END_TIME',
                        'START_PLACE', 'END_PLACE', 'ROUTE_ID', 'ROUTE_VARIANT', 'DIRECTION_NUM', 'DISTANCE', 'TRIP_ID'
                    ],
                    'types_db' => 'ssissssssisi',
                    'process_viagens' => true,
                    'date_from_filename' => true,
                    'skip_lines' => 1,
                    'delimiter' => ','
                ];
                break;
 
            case '0241_geral':
                $config = [
                    'table' => 'registros_gerais',
                    'num_expected_cols' => 15,
                    'columns_db' => [
                        'depot', 'block', 'route', 'direction', 'variation', 'operator', 'vehicle',
                        'trip', 'nome_parada', 'chegada_programada', 'chegada_real',
                        'desvio_chegada', 'partida_programada', 'partida_real', 'desvio_partida'
                    ],
                    'types_db' => 'ssssissssssssss',
                    'process_0241' => true
                ];
                break;
 
            case 'timepoint_geral':
                $config = [
                    'table' => 'registros_timepoint_geral',
                    'num_expected_cols' => 13,
                    'columns_db' => [
                        'data_evento', 'vehicle', 'bloco', 'workid', 'operator', 'matricula',
                        'route', 'ponto_controle', 'sentido_via', 'direcao', 'horario_real', 'horario_programado', 'is_last_tp'
                    ],
                    'types_db' => 'sssssissssssi',
                    'process_timepoint_v10' => true
                ];
                break;
 
            case 'relatorio_frota':
                 $config = [
                    'table' => 'relatorios_frota',
                    'num_expected_cols' => 23,
                    'columns_db' => [
                        'depot', 'vehicle', 'operator', 'matricula', 'data_viagem', 'route',
                        'tempo_exec_p_seg', 'tempo_exec_r_seg', 'distancia_p_km', 'distancia_r_km',
                        'tempo_produtivo_p_seg', 'tempo_produtivo_r_seg', 'distancia_produtiva_p_km', 'distancia_produtiva_r_km',
                        'tempo_recolha_p_seg', 'tempo_recolha_r_seg', 'distancia_recolha_p_km', 'distancia_recolha_r_km',
                        'tempo_ocioso_p_seg', 'tempo_ocioso_r_seg', 'distancia_ociosa_p_km', 'distancia_ociosa_r_km',
                        'tempo_ocioso_total_r_seg', 'distancia_ociosa_total_r_km'
                    ],
                     'types_db' => 'ssssssiissiisssiissiissi',
                    'process_frota_data' => true
                ];
                break;
 
            case 'relatorio_divisao':
                 $config = [
                    'table' => 'relatorios_divisao',
                    'num_expected_cols' => 13,
                    'columns_db' => [
                        'division', 'vehicle', 'data_viagem',
                        'manual_duracao_seg', 'manual_distancia_km', 'off_duracao_seg', 'off_distancia_km',
                        'scheduled_duracao_seg', 'scheduled_distancia_km', 'unknown_duracao_seg', 'unknown_distancia_km',
                        'total_duracao_seg', 'total_distancia_km'
                    ],
                    'types_db' => 'sssisisisisis',
                    'process_divisao_data' => true,
                    'skip_lines' => 2
                ];
                break;
 
            case 'icv_ipv':
                 $config = [
                    'table' => 'relatorios_icv_ipv',
                     'num_expected_cols' => 10,
                    'columns_db' => [
                        'data_relatorio', 'viagens_programadas', 'viagens_realizadas', 'viagens_atrasadas',
                        'viagens_adiantadas', 'viagens_suprimidas', 'icv_percent', 'icv_actual_percent',
                        'ipv_percent', 'ipv_trip_percent'
                    ],
                    'types_db' => 'siiiiiisss',
                    'summary_file' => true,
                    'date_key_column_index' => 0
                ];
                break;
 
            case 'on_time':
                 $config = [
                    'table' => 'relatorios_on_time',
                    'num_expected_cols' => 5,
                    'columns_db' => [
                        'data_relatorio', 'no_horario_percent', 'adiantado_percent', 'atrasado_percent',
                        'timepoints_processados'
                    ],
                    'types_db' => 'ssssi',
                    'summary_file' => true,
                    'date_key_column_index' => 0
                ];
                break;
 
            case 'km_roleta_diario':
                    $config = [
                        'table' => 'relatorios_km_roleta_diario',
                        'num_expected_cols' => 4,
                        'columns_db' => ['vehicle', 'data_leitura', 'km_inicial', 'km_final', 'total'],
                        'types_db' => 'ssssi',
                        'process_roleta_diario' => true,
                        'date_from_filename' => true,
                        'delimiter' => ';'
                    ];
                    break;

            case 'relatorio_noxxon':
                $config = [
                    'table' => 'relatorios_km_noxxon_diario',
                    'num_expected_cols' => 6,
                    'columns_db' => ['vehicle', 'data_leitura', 'km_inicial', 'km_final', 'km_percorrido'],
                    'types_db' => 'sssss',
                    'process_noxxon_diario' => true,
                    'date_from_data_column' => true,
                    'skip_lines' => 1,
                    'delimiter' => ','
                ];
                break;
 
            case 'odometro_life':
                $config = [
                    'table' => 'registros_odometro_life',
                    'num_expected_cols' => 13,
                    'columns_db' => [
                        'data_leitura', 'vehicle', 'funcionario_raw', 'evento', 'odometro_km',
                        'odometro_fator_correcao', 'fator_correcao', 'consumo', 'localizacao',
                        'botton', 'pcid', 'unidade_empresarial', 'data_gravacao'
                    ],
                    'types_db' => 'sssssssssssss',
                    'process_odometro_life' => true
                ];
                break;
 
            case 'comunicacao':
                $config = [
                    'table' => 'registros_comunicacao',
                    'num_expected_cols' => 11,
                    'columns_db' => [
                         'data_csv', 'vehicle', 'motorista_matricula_raw', 'bloco', 'linha', 'sentido',
                         'data_evento_inicio', 'data_evento_fim', 'duracao_offline_seg', 'latitude', 'longitude'
                    ],
                    'types_db' => 'ssssssssiss',
                    'process_comunicacao' => true
                ];
                break;
        case 'cadastro_vias':
            $config = [
                'table' => 'cadastros_vias',
                'num_expected_cols' => 4,
                'columns_db' => ['codigo', 'instrucoes', 'linha', 'descricao'],
                'types_db' => 'ssss',
                'delimiter' => ';',
                'skip_lines' => 1
            ];
            break;

        case 'cadastro_locais':
            $config = [
                'table' => 'cadastros_locais',
                // Agora o CSV tem 4 colunas: CODE; X; Y; NAME
                'num_expected_cols' => 4, 
                // No banco vamos gravar nestas 3 colunas (X e Y viram 'coordenadas')
                'columns_db' => ['company_code', 'coordenadas', 'name'],
                'types_db' => 'sss',
                'delimiter' => ';',
                'skip_lines' => 1,
                'process_locais_utm' => true // Flag para ativar a nossa lógica nova
            ];
            break;
        }
 
        if (empty($erros_ocorridos) && $config) {
            try {
                $conexao = new mysqli($servidor, $usuario, $senha, $banco);
                if ($conexao->connect_error) { throw new Exception("Falha na conexão: " . $conexao->connect_error); }
                $conexao->set_charset("utf8mb4");
 
                $tabela_ja_limpa = false;
                $delimiter = $config['delimiter'] ?? ',';
 
                foreach ($files['tmp_name'] as $key => $tmp_name) {
                    if ($files['error'][$key] !== UPLOAD_ERR_OK) { continue; }
                    $nome_arquivo_atual = htmlspecialchars($files['name'][$key]);
                    $handle = fopen($tmp_name, "r");
                    if ($handle === false) { $erros_ocorridos[] = "Erro ao abrir $nome_arquivo_atual."; continue; }
 
                    if ($import_mode === 'truncate' && !$tabela_ja_limpa) {
                        // Lógicas específicas de limpeza
                        if (isset($config['process_servicos'])) {
                            $sql = "DELETE FROM {$config['table']} WHERE data_inicio_vigencia = '{$conexao->real_escape_string($data_inicio_vigencia)}' AND data_fim_vigencia = '{$conexao->real_escape_string($data_fim_vigencia)}'";
                            if (!$conexao->query($sql)) throw new Exception("Erro ao limpar vigência: " . $conexao->error);
                        } elseif (isset($config['process_km_precisa']) || isset($config['process_viagens']) || isset($config['process_roleta_diario']) || isset($config['process_todos_horarios'])) {
                            $datas = [];
                            foreach ($files['name'] as $n) { if($d = extrair_data_filename($n)) $datas[] = "'".$conexao->real_escape_string($d)."'"; }
                            $coluna_data = isset($config['process_roleta_diario']) ? 'data_leitura' : 'data_viagem';
                            if($datas && !$conexao->query("DELETE FROM {$config['table']} WHERE $coluna_data IN (".implode(',',array_unique($datas)).")")) throw new Exception("Erro ao limpar datas: " . $conexao->error);
                        } elseif (isset($config['process_noxxon_diario'])) {
                            if (!$conexao->query("TRUNCATE TABLE {$config['table']}")) throw new Exception("Erro truncate Noxxon: " . $conexao->error);
                        } elseif (!isset($config['summary_file'])) { 
                             if (!$conexao->query("TRUNCATE TABLE {$config['table']}")) throw new Exception("Erro truncate: " . $conexao->error);
                        } elseif (isset($config['summary_file'])) {
                             if (!$conexao->query("TRUNCATE TABLE {$config['table']}")) throw new Exception("Erro truncate summary: " . $conexao->error);
                        }
                        $tabela_ja_limpa = true;
                    }
 
                    $data_do_ficheiro = null;
                    if (isset($config['date_from_filename']) && $config['date_from_filename']) {
                        $data_do_ficheiro = extrair_data_filename($nome_arquivo_atual);
                        if ($data_do_ficheiro === null) { $erros_ocorridos[] = "Data não encontrada no nome do arquivo $nome_arquivo_atual."; fclose($handle); continue; }
                    }
 
                    $conexao->begin_transaction();
                    try {
                        $batch_size = 500;
                        $params_batch = [];
                        $row_count = 0;
                        $linha_atual_csv = 0;
                        $linha_atual_nome = null; 
 
                        // Pular linhas
                        $linhas_pular = $config['skip_lines'] ?? 1;
                        for ($i = 0; $i < $linhas_pular; $i++) { $linha_atual_csv++; fgetcsv($handle, 2000, $delimiter); }
 
                        if (isset($config['summary_file']) && $config['summary_file']) {
                             $sql_template = ($import_mode === 'append') 
                                 ? "INSERT INTO {$config['table']} (" . implode(',', $config['columns_db']) . ") VALUES (?" . str_repeat(',?', count($config['columns_db']) - 1) . ") ON DUPLICATE KEY UPDATE " 
                                 : "INSERT INTO {$config['table']} (" . implode(',', $config['columns_db']) . ") VALUES (?" . str_repeat(',?', count($config['columns_db']) - 1) . ")";
                             if ($import_mode === 'append') {
                                  $upd = []; $dk = $config['columns_db'][$config['date_key_column_index']];
                                  foreach ($config['columns_db'] as $c) if($c!==$dk) $upd[]="`$c`=VALUES(`$c`)";
                                  $sql_template .= implode(',', $upd);
                             }
                             $stmt = $conexao->prepare($sql_template);
                             while (($data_raw = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                                 $data = converter_linha_para_utf8($data_raw);
                                 if (count($data) < $config['num_expected_cols']) continue;
                                 $params = []; $types = $config['types_db'];
                                 foreach ($config['columns_db'] as $idx => $col) {
                                     $val = trim($data[$idx]??'');
                                     if ($idx == $config['date_key_column_index']) $params[] = formatar_data_br_para_iso($val);
                                     elseif ($types[$idx] == 'i') $params[] = (int)filter_var($val, FILTER_SANITIZE_NUMBER_INT);
                                     else $params[] = ($val!=='' ? $val : null);
                                 }
                                 $stmt->bind_param($types, ...$params);
                                 $stmt->execute();
                                 $total_linhas_importadas++;
                             }
                             $stmt->close();
                        } else {
                             // BATCH LOGIC
                             $placeholders_row = '(' . rtrim(str_repeat('?,', count($config['columns_db'])), ',') . ')';
                             $types_row = $config['types_db'];
 
                             while (($data_raw = fgetcsv($handle, 2000, $delimiter)) !== FALSE) {
                                 $linha_atual_csv++;
                                 if (empty(array_filter($data_raw, fn($v) => $v !== null && $v !== ''))) continue;
                                 $data = converter_linha_para_utf8($data_raw);
                                 
                                 if (!isset($config['process_bilhetagem']) && count($data) < $config['num_expected_cols']) continue; 
 
                                $row_params = [];
                                $valid_row = true;
 
                                try {

                        if (isset($config['process_todos_horarios'])) {
                            
                            // Função para limpar HH:MM:SS para HH:MM:00
                            $clean_time = function($t) {
                                $t = trim($t ?? '');
                                // Se vier vazio, retorna nulo para não gravar 00:00:00 incorretamente
                                if (empty($t)) return null;
                                // Pega HH:MM e força :00 no final
                                if(preg_match('/^\d{1,2}:\d{2}/', $t, $m)) return $m[0] . ':00';
                                return null;
                            };

                            $row_params = [
                                $data_do_ficheiro,           
                                trim($data[0]??''),         // SERVICELEVEL
                                trim($data[1]??''),         // CALENDAR
                                trim($data[2]??''),         // OPERATINGDAY
                                trim($data[3]??''),         // PIECETYPE
                                formatar_data_br_para_iso($data[4]), // STARTINGDATE
                                formatar_data_br_para_iso($data[5]), // ENDINGDATE
                                trim($data[6]??''),         // LINE
                                trim($data[7]??''),         // LINEBASIN
                                trim($data[8]??''),         // LINECOMPANY
                                trim($data[9]??''),         // PATTERN
                                trim($data[10]??''),        // DIRECTION
                                trim($data[11]??''),        // TRIPCODE
                                trim($data[12]??''),        // TRIPCOMPANYCODE
                                trim($data[13]??''),        // ACTIVITYNUMBER
                                (float)str_replace(',','.', $data[14]??0), // LENGTH (Coluna 14)
                                
                                // --- CORREÇÃO DOS ÍNDICES ABAIXO ---
                                // Pulamos as colunas 15 a 21 do CSV que não usamos
                                $clean_time($data[22]),     // ARRIVALTIME (Coluna 22)
                                $clean_time($data[23]),     // DEPARTURETIME (Coluna 23)
                                trim($data[24]??''),        // NODE (Coluna 24)
                                trim($data[25]??''),        // NODEBASIN (Coluna 25)
                                trim($data[26]??''),        // NODECOMPANY (Coluna 26)
                                (int)($data[27]??0)         // PASSAGEORDER (Coluna 27)
                            ];
                        }
                        elseif (isset($config['process_km_precisa'])) {
                            $desc = trim($data[10] ?? '');
                            $km_metros = (float)str_replace(',', '.', $data[11] ?? 0);
                            $km_final = $km_metros / 1;
                            $is_prod = ($desc === 'Revenue') ? 1 : 0;
                            $is_ocio = in_array($desc, ['Pre_Duty', 'Pre_DutyPart', 'Fuori_linea', 'Post_Duty', 'Post_DutyPart', 'DeadHead']) ? 1 : 0;
                            $row_params = [$data_do_ficheiro, trim($data[5]??''), trim($data[6]??''), trim($data[7]??''), trim($data[8]??''), trim($data[9]??''), $desc, $km_final, trim($data[12]??''), trim($data[13]??''), trim($data[14]??''), trim($data[15]??''), $is_prod, $is_ocio];
                        }
                                  elseif (isset($config['process_servicos'])) {
                                       $row_params = [
                                            trim($data[0]), trim($data[1]), trim($data[2]), (int)($data[3] ?? 0),
                                            trim($data[4]), trim($data[5]), trim($data[6]), (int)($data[7] ?? 0),
                                            (int)($data[8] ?? 0), trim($data[9]), trim($data[10]), trim($data[11]),
                                            trim($data[12]), trim($data[13]), trim($data[14]), trim($data[15]),
                                            trim($data[16]), $data_inicio_vigencia, $data_fim_vigencia
                                       ];
                                  }
                                  elseif (isset($config['process_bilhetagem'])) {
                                       if (strpos($data[0] ?? '', 'Linha:') !== false) {
                                            $linha_parts = explode(':', $data[0]);
                                            if (isset($linha_parts[1])) $linha_atual_nome = trim($linha_parts[1]);
                                            $valid_row = false; 
                                       } else {
                                            $data_iso = formatar_data_br_para_iso($data[0] ?? '');
                                            if ($data_iso && $linha_atual_nome) {
                                                 $row_params = [
                                                      $linha_atual_nome, $data_iso,
                                                      (int)($data[1] ?? 0), (int)($data[2] ?? 0),
                                                      limpar_numero_ptbr($data[3]??''), limpar_numero_ptbr($data[4]??''),
                                                      limpar_numero_ptbr($data[5]??''), limpar_numero_ptbr($data[6]??''),
                                                      limpar_numero_ptbr($data[7]??''), limpar_numero_ptbr($data[8]??''),
                                                      limpar_numero_ptbr($data[9]??''), limpar_numero_ptbr($data[10]??''),
                                                      limpar_numero_ptbr($data[11]??''), limpar_numero_ptbr($data[12]??''),
                                                      limpar_numero_ptbr($data[13]??''), limpar_numero_ptbr($data[14]??''),
                                                      limpar_numero_ptbr($data[15]??'')
                                                 ];
                                            } else { $valid_row = false; }
                                       }
                                  }
                                  elseif (isset($config['process_viagens'])) {
                                       $block = trim($data[0] ?? ''); if(!$block) $valid_row=false;
                                       $dist = str_replace(',', '.', trim($data[9] ?? '0'));
                                       if ($valid_row) $row_params = [
                                            $data_do_ficheiro, $block, (int)($data[1]??0), trim($data[2]), trim($data[3]),
                                            trim($data[4]), trim($data[5]), trim($data[6]), trim($data[7]), (int)($data[8]??0),
                                            (!is_numeric($dist)?0.0:$dist), (int)($data[10]??0)
                                       ];
                                  }
                                  elseif (isset($config['process_noxxon_diario'])) {
                                       $veh = limpar_prefixo_veiculo($data[0]); $dt = formatar_data_noxxon_para_iso($data[1]);
                                       if(!$veh || !$dt) $valid_row=false;
                                       else $row_params = [$veh, $dt, ($data[3]!=='-'?$data[3]:null), ($data[4]!=='-'?$data[4]:null), ($data[5]!=='-'?$data[5]:null)];
                                  }
                                  elseif (isset($config['process_roleta_diario'])) {
                                       $veh = limpar_prefixo_veiculo($data[0]); 
                                       $tot = filter_var(str_replace('.','',$data[3]??''), FILTER_SANITIZE_NUMBER_INT);
                                       if(!$veh || $veh=="CARRO") $valid_row=false;
                                       else $row_params = [
                                            $veh, $data_do_ficheiro, 
                                            str_replace(',','.',str_replace('.','',$data[1]??'')),
                                            str_replace(',','.',str_replace('.','',$data[2]??'')),
                                            ($tot!==''?(int)$tot:null)
                                       ];
                                  }
                                  elseif (isset($config['process_odometro_life'])) {
                                       $dt = converter_data_hora_br($data[0]); $veh = limpar_prefixo_veiculo($data[1]);
                                       if(!$dt || !$veh || $veh=="Veiculo") $valid_row=false;
                                       else $row_params = [
                                            $dt, $veh, trim($data[2]??''), trim($data[3]??''), trim($data[4]??''), trim($data[5]??''),
                                            ($data[6]!=='-'?trim($data[6]):null), trim($data[7]??''), trim($data[8]??''), trim($data[9]??''),
                                            trim($data[10]??''), trim($data[11]??''), converter_data_hora_br($data[12])
                                       ];
                                  }
                                  elseif (isset($config['process_comunicacao'])) {
                                       $dt = formatar_data_br_para_iso($data[0]); $veh = limpar_prefixo_veiculo($data[1]);
                                       if(!$dt || !$veh) $valid_row=false;
                                       else $row_params = [
                                            $dt, $veh, trim($data[2]??''), trim($data[3]??''), trim($data[4]??''), trim($data[5]??''),
                                            converter_data_hora_br($data[6]), converter_data_hora_br($data[7]),
                                            converter_tempo_para_segundos($data[8]), trim($data[9]??''), trim($data[10]??'')
                                       ];
                                  }
                                  elseif (isset($config['process_timepoint_v10'])) {
                                       $dt = formatar_data_br_para_iso($data[0]); 
                                       $veh = (strtolower(trim($data[1]))=='nan'?'':limpar_prefixo_veiculo($data[1]));
                                       processar_operador($data[4], $nm, $mat);
                                       $prog = combinar_data_hora($dt, $data[11]);
                                       if(!$prog) $valid_row=false;
                                       else $row_params = [
                                            $dt, $veh, trim($data[2]), trim($data[3]), $nm, $mat, trim($data[5]), trim($data[6]),
                                            trim($data[8]), trim($data[9]), combinar_data_hora($dt, $data[10]), $prog, (int)($data[12]??0)
                                       ];
                                  }
                                  elseif (isset($config['process_frota_data'])) {
                                       $dep = trim($data[0]); if(!$dep || strtolower($dep)=='total') $valid_row=false;
                                       $veh = limpar_prefixo_veiculo($data[1]); processar_operador($data[2], $nm, $mat);
                                       // CORREÇÃO AQUI: Usa a nova função para formato americano (MM/DD/AAAA)
                                       $dt = formatar_data_americana_para_iso($data[3]);
                                       
                                       if(!$dt) $valid_row=false;
                                       else $row_params = [
                                            $dep, $veh, $nm, $mat, $dt, trim($data[4]), 
                                            converter_tempo_para_segundos($data[5]), converter_tempo_para_segundos($data[6]),
                                            trim($data[7]), trim($data[8]), converter_tempo_para_segundos($data[9]), 
                                            converter_tempo_para_segundos($data[10]), trim($data[11]), trim($data[12]),
                                            converter_tempo_para_segundos($data[13]), converter_tempo_para_segundos($data[14]),
                                            trim($data[15]), trim($data[16]), converter_tempo_para_segundos($data[17]),
                                            converter_tempo_para_segundos($data[18]), trim($data[19]), trim($data[20]),
                                            converter_tempo_para_segundos($data[21]), trim($data[22])
                                       ];
                                  }
                                  elseif (isset($config['process_divisao_data'])) {
                                       $veh = limpar_prefixo_veiculo($data[1]); $dt = formatar_data_br_para_iso($data[2]);
                                       if(!$veh || !$dt) $valid_row=false;
                                       else {
                                            $row_params = [trim($data[0]), $veh, $dt];
                                            for($x=3; $x<=12; $x++) $row_params[] = ($x%2!=0 ? converter_tempo_para_segundos($data[$x]) : str_replace(',','',$data[$x]));
                                       }
                                  }
								  elseif (isset($config['process_locais_utm'])) {
                                    // CSV: [0]Code, [1]CoordX, [2]CoordY, [3]Name
                                    $code = trim($data[0] ?? '');
                                    $utmX = trim($data[1] ?? '');
                                    $utmY = trim($data[2] ?? '');
                                    $name = trim($data[3] ?? '');

                                    if (!$code) {
                                        $valid_row = false;
                                    } else {
                                        // Converte as duas colunas UTM numa única string "Lat, Lon"
                                        $latLon = converterUtmParaLatLon($utmX, $utmY);
                                        
                                        // Mapeia para as colunas do banco definidas no config: 
                                        // 1. company_code, 2. coordenadas, 3. name
                                        $row_params = [$code, $latLon, $name];
                                    }
                                }
                                  else { // Genérico
                                       foreach($config['columns_db'] as $idx => $col) {
                                            $val = trim($data[$idx]??'');
                                            if($idx==4) $row_params[] = (int)filter_var($val, FILTER_SANITIZE_NUMBER_INT);
                                            else $row_params[] = ($val!==''?$val:null);
                                       }
                                  }
                                 } catch (Exception $e) { $erros_ocorridos[] = "Erro linha: ".$e->getMessage(); $valid_row=false; }
 
                                 if (!$valid_row) continue;
                                 if (count($row_params) != strlen($types_row)) { $erros_ocorridos[]="Erro contagem params"; continue; }
 
                                 $params_batch = array_merge($params_batch, $row_params);
                                 $row_count++;
 
                                 if ($row_count >= $batch_size) {
                                     $sql = "INSERT INTO {$config['table']} (" . implode(',', $config['columns_db']) . ") VALUES " . implode(',', array_fill(0, $row_count, $placeholders_row));
                                     $stmt = $conexao->prepare($sql);
                                     $stmt->bind_param(str_repeat($types_row, $row_count), ...$params_batch);
                                     if (!$stmt->execute()) throw new Exception("Erro Batch: " . $stmt->error);
                                     $total_linhas_importadas += $row_count;
                                     $stmt->close();
                                     $params_batch = []; $row_count = 0;
                                 }
                             }
                        }
 
                        if ($row_count > 0 && !isset($config['summary_file'])) {
                             $sql = "INSERT INTO {$config['table']} (" . implode(',', $config['columns_db']) . ") VALUES " . implode(',', array_fill(0, $row_count, $placeholders_row));
                             $stmt = $conexao->prepare($sql);
                             $stmt->bind_param(str_repeat($types_row, $row_count), ...$params_batch);
                             if (!$stmt->execute()) {
                                  if ($conexao->errno != 1062) throw new Exception("Erro Batch Final: " . $stmt->error);
                             } else { $total_linhas_importadas += $row_count; }
                             $stmt->close();
                        }
 
                        $conexao->commit();
                        $total_arquivos_processados++;
                        $nomes_arquivos_processados[] = $nome_arquivo_atual;
 
                    } catch (Exception $e) {
                        $conexao->rollback();
                        $erros_ocorridos[] = "Erro arquivo $nome_arquivo_atual: " . $e->getMessage();
                    } finally { if (is_resource($handle)) fclose($handle); }
                }
                $conexao->close();
 
                if (empty($erros_ocorridos)) {
                    $upload_message = "Importação concluída! Processados $total_arquivos_processados arquivos e $total_linhas_importadas registros.";
                    $message_type = 'success';
                } else {
                    $upload_message = "Erros:<br>" . implode('<br>', $erros_ocorridos);
                    $message_type = 'error';
                }
 
            } catch (Exception $e) { 
                $upload_message = "Erro inicial: " . $e->getMessage(); $message_type = 'error'; 
                if(isset($conexao)) $conexao->close(); 
            }
        } else {
            $upload_message = empty($erros_ocorridos) ? "Tipo de formulário inválido." : implode('<br>', $erros_ocorridos);
            $message_type = 'error';
        }
    } elseif (!isset($_FILES['csvfiles'])) {
        if(empty($upload_message)) { $upload_message = "Erro: Nenhum ficheiro enviado."; $message_type = 'error'; }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Central de Controle e Importação</title>
    <script src="tailwindcss-3.4.17.js"></script>
    <link href="css2.css" rel="stylesheet">
    <style>
        /* CONFIGURAÇÃO DO FUNDO FIXO */
        body { 
            font-family: 'Inter', sans-serif; 
            /* Imagem de fundo fixa e preenchendo a tela */
            background-image: url('fundo-body.png');
            background-attachment: fixed; /* Fixa a imagem ao rolar */
            background-size: auto;       /* Cobre toda a área */
            background-position: center;  /* Centraliza a imagem */
            background-repeat: no-repeat;
        }

        /* CONFIGURAÇÃO DO CONTAINER PRINCIPAL PARA LEITURA */
        /* Adicionamos um fundo branco semitransparente para o texto não sumir em cima da imagem */
        .main-container {
            background-color: rgba(255, 255, 255, 0.92); /* Branco com 92% de opacidade */
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        /* Estilos dos Cards (Mantidos) */
        .card { transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); }
        
        /* Estilos dos Botões de Upload (Mantidos) */
        input[type="file"]::file-selector-button { margin-right: 0.5rem; padding: 0.5rem 1rem; border-radius: 9999px; border-width: 0px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: background-color 0.2s ease-in-out; }
        #csv_0241::file-selector-button { background-color: #ebf8ff; color: #2b6cb0; } #csv_0241::file-selector-button:hover { background-color: #bee3f8; }
        #csv_timepoint::file-selector-button { background-color: #faf5ff; color: #805ad5; } #csv_timepoint::file-selector-button:hover { background-color: #e9d8fd; }
        #csv_frota::file-selector-button { background-color: #f0fff4; color: #2f855a; } #csv_frota::file-selector-button:hover { background-color: #c6f6d5; }
        #csv_divisao::file-selector-button { background-color: #fff5f7; color: #d53f8c; } #csv_divisao::file-selector-button:hover { background-color: #fed7e2; }
        #csv_km_roleta::file-selector-button { background-color: #fffbeb; color: #b45309; } #csv_km_roleta::file-selector-button:hover { background-color: #fef3c7; }
        #csv_km_noxxon::file-selector-button { background-color: #ecfeff; color: #0891b2; } #csv_km_noxxon::file-selector-button:hover { background-color: #a5f3fc; }
        #csv_odometro_life::file-selector-button { background-color: #eef2ff; color: #4f46e5; } #csv_odometro_life::file-selector-button:hover { background-color: #c7d2fe; }
        #csv_comunicacao::file-selector-button { background-color: #fee2e2; color: #dc2626; } #csv_comunicacao::file-selector-button:hover { background-color: #fecaca; }
        #csv_icv_ipv::file-selector-button { background-color: #f3f4f6; color: #4b5563; } #csv_icv_ipv::file-selector-button:hover { background-color: #e5e7eb; }
        #csv_on_time::file-selector-button { background-color: #f3f4f6; color: #4b5563; } #csv_on_time::file-selector-button:hover { background-color: #e5e7eb; }
        #csv_servicos::file-selector-button { background-color: #ecfeff; color: #0e7490; } #csv_servicos::file-selector-button:hover { background-color: #cffafe; }
        #csv_viagens::file-selector-button { background-color: #f0fdf4; color: #15803d; } #csv_viagens::file-selector-button:hover { background-color: #dcfce7; }
        #km_precisao::file-selector-button { background-color: #fef2f2; color: #dc2626; } #km_precisao::file-selector-button:hover { background-color: #fee2e2; }
        #csv_viagens_ajuste::file-selector-button { background-color: #f0fdf4; color: #15803d; } #csv_viagens_ajuste::file-selector-button:hover { background-color: #dcfce7; }
        #csv_bilhetagem::file-selector-button { background-color: #fff7ed; color: #c2410c; } #csv_bilhetagem::file-selector-button:hover { background-color: #ffedd5; }
        #csv_todos_horarios::file-selector-button { background-color: #f3e8ff; color: #7e22ce; } #csv_todos_horarios::file-selector-button:hover { background-color: #e9d5ff; }
    </style>
</head>
<body class="p-4 md:p-8 min-h-screen">
    
    <main class="max-w-7xl mx-auto main-container p-6 md:p-10">
        
        <header class="flex flex-col md:flex-row items-center justify-between mb-10 gap-6 border-b pb-8 border-gray-200">
            
            <div class="w-full md:w-1/4 flex justify-center md:justify-start">
                <img src="logo-londrisul.png" alt="Londrisul" class="h-16 md:h-20 object-contain">
            </div>

            <div class="w-full md:w-2/4 text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-800 tracking-tight">Central de Relatórios</h1>
                <p class="mt-2 text-lg text-gray-600 font-medium">Importe relatórios e acesse os dashboards de análise.</p>
            </div>

            <div class="w-full md:w-1/4 flex justify-center md:justify-end">
                <img src="logo-ciop.png" alt="CIOP" class="h-12 md:h-16 object-contain">
            </div>

        </header>

        <?php if ($upload_message): ?>
            <div class="mb-6 p-4 rounded-lg text-left <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>">
                <?php echo nl2br($upload_message); ?>
            </div>
        <?php endif; ?>

        <section class="mb-12">
             <h2 class="text-3xl font-semibold text-gray-700 mb-6 border-b pb-3 flex items-center gap-2">
                Dashboards de Análise
             </h2>
             <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                 <a href="ocorrencia/index.php" target="_blank" class="card block bg-gradient-to-br from-amber-400 to-orange-500 p-6 rounded-xl shadow-lg text-white hover:shadow-xl transition-shadow duration-300">
                     <h3 class="text-2xl font-bold mb-2">Ocorrências <p>&nbsp</p></h3>
                     <p class="text-sm opacity-90 mb-4">Acesse o painel para registrar, editar e consultar as ocorrências da operação diária.</p>
                     <span class="font-semibold inline-block border-b-2 border-transparent hover:border-white transition-colors">Acessar Painel &rarr;</span>
                 </a>
                 <a href="relatorio_bilhetagem.php" target="_blank" class="card block bg-gradient-to-br from-orange-400 to-red-500 p-6 rounded-xl shadow-lg text-white hover:shadow-xl transition-shadow duration-300">
                     <h3 class="text-2xl font-bold mb-2">Bilhetagem <p>&nbsp</p></h3>
                     <p class="text-sm opacity-90 mb-4">Análise de demanda de passageiros, tipos de cartão e tendências.</p>
                     <span class="font-semibold inline-block border-b-2 border-transparent hover:border-white transition-colors">Acessar Painel &rarr;</span>
                 </a>
                 <a href="relatorio_diario_bordo.php" target="_blank" class="card block bg-gradient-to-br from-indigo-500 to-purple-600 p-6 rounded-xl shadow-lg text-white hover:shadow-xl">
                     <h3 class="text-2xl font-bold mb-2">Diário de Bordo <p>&nbsp</p></h3>
                     <p class="text-sm opacity-90 mb-4">Geração de tabelas/diário de bordo por linha ou WorkID.</p>
                     <span class="font-semibold border-b-2 border-transparent hover:border-white">Acessar Painel &rarr;</span>
                 </a>
                 <a href="relatorio_km_detalhado_diario.php" target="_blank" class="card block bg-gradient-to-br from-red-400 to-blue-500 p-6 rounded-xl shadow-lg text-white hover:shadow-xl transition-shadow duration-300">
                     <h3 class="text-2xl font-bold mb-2">Relatório KM Consolidado</h3>
                     <p class="text-sm opacity-90 mb-4">Visão diária detalhada e cards consolidados do período (Roleta, MTRAN, Clever, Life).</p>
                     <span class="font-semibold inline-block border-b-2 border-transparent hover:border-white transition-colors">Acessar Painel &rarr;</span>
                 </a>
                 <a href="relatorio_0241_geral.php" target="_blank" class="card block bg-gradient-to-br from-sky-400 to-blue-600 p-6 rounded-xl shadow-lg text-white hover:shadow-xl transition-shadow duration-300">
                      <h3 class="text-2xl font-bold mb-2">Relatório 0241 <p>&nbsp</p></h3>
                      <p class="text-sm opacity-90 mb-4">Análise de desempenho (ICV/IPV) de chegadas e partidas nos terminais.</p>
                      <span class="font-semibold inline-block border-b-2 border-transparent hover:border-white transition-colors">Ver Relatório &rarr;</span>
                  </a>
                  <a href="relatorio_timepoint_geral.php" target="_blank" class="card block bg-gradient-to-br from-violet-400 to-purple-600 p-6 rounded-xl shadow-lg text-white hover:shadow-xl transition-shadow duration-300">
                      <h3 class="text-2xl font-bold mb-2">Relatório 108 <p>&nbsp</p></h3>
                      <p class="text-sm opacity-90 mb-4">Pontualidade de chegadas/partidas nos pontos de controle (Timepoint).</p>
                      <span class="font-semibold inline-block border-b-2 border-transparent hover:border-white transition-colors">Ver Relatório &rarr;</span>
                  </a>
                  <a href="dashboard_frota.php" target="_blank" class="card block bg-gradient-to-br from-teal-400 to-emerald-600 p-6 rounded-xl shadow-lg text-white hover:shadow-xl transition-shadow duration-300">
                      <h3 class="text-2xl font-bold mb-2">Relatório 004 <p>&nbsp</p></h3>
                      <p class="text-sm opacity-90 mb-4">Dashboard de análise de desempenho da frota (distância e horas).</p>
                      <span class="font-semibold inline-block border-b-2 border-transparent hover:border-white transition-colors">Ver Dashboard &rarr;</span>
                  </a>
                  <a href="dashboard_divisao.php" target="_blank" class="card block bg-gradient-to-br from-rose-400 to-pink-600 p-6 rounded-xl shadow-lg text-white hover:shadow-xl transition-shadow duration-300">
                      <h3 class="text-2xl font-bold mb-2">Relatório 142 <p>&nbsp</p></h3>
                      <p class="text-sm opacity-90 mb-4">Dashboard de trabalho da frota por garagem/divisão (Clever Reports).</p>
                      <span class="font-semibold inline-block border-b-2 border-transparent hover:border-white transition-colors">Ver Dashboard &rarr;</span>
                  </a>
                   <a href="relatorio_perda_comunicacao.php" target="_blank" class="card block bg-gradient-to-br from-red-500 to-pink-700 p-6 rounded-xl shadow-lg text-white hover:shadow-xl transition-shadow duration-300">
                      <h3 class="text-2xl font-bold mb-2">Perda de Comunicação</h3>
                      <p class="text-sm opacity-90 mb-4">Análise exclusiva de falhas de sinal GPS e sistemas offline.</p>
                      <span class="font-semibold inline-block border-b-2 border-transparent hover:border-white transition-colors">Ver Relatório &rarr;</span>
                  </a>
                 <a href="relatorio_tabelas.php" target="_blank" class="card block bg-gradient-to-br from-cyan-400 to-blue-600 p-6 rounded-xl shadow-lg text-white hover:shadow-xl transition-shadow duration-300">
                     <h3 class="text-2xl font-bold mb-2">Relatório de Tabelas <p>&nbsp</p></h3>
                     <p class="text-sm opacity-90 mb-4">Análise de KM, Horas e Serviços por Faixa Horária e Linha (Tabelas).</p>
                     <span class="font-semibold inline-block border-b-2 border-transparent hover:border-white transition-colors">Ver Relatório &rarr;</span>
                 </a>
                 <a href="relatorio_projecao_km.php" target="_blank" class="card block bg-gradient-to-br from-indigo-400 to-red-600 p-6 rounded-xl shadow-lg text-white hover:shadow-xl transition-shadow duration-300">
                     <h3 class="text-2xl font-bold mb-2">Relatório Projeção <p>&nbsp</p></h3>
                     <p class="text-sm opacity-90 mb-4">Projeta KM futuro baseado em médias históricas de diversas fontes.</p>
                     <span class="font-semibold inline-block border-b-2 border-transparent hover:border-white transition-colors">Ver Relatório &rarr;</span>
                 </a>
             </div>
         </section>
 
         <section>
             <h2 class="text-3xl font-semibold text-gray-700 mb-6 border-b pb-3 flex items-center gap-2">
                Módulos de Importação de CSV / KML
             </h2>
             <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                
                <div class="card bg-white p-6 rounded-xl shadow-lg border-4 border-lime-500">
                    <h2 class="text-2xl font-bold mb-3 text-lime-700">Rotas / Mapas (KML)</h2>
                    <p class="text-sm text-gray-600 mb-6">Importe o arquivo .kml extraído do sistema de planejamento para atualizar os traçados.</p>
                    <form action="index.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="form_type" value="importar_kml">
                        <div class="mb-4">
                            <label for="kmlfile" class="sr-only">Escolher arquivo KML</label>
                            <input type="file" name="kmlfile" id="kmlfile" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-lime-50 file:text-lime-700 hover:file:bg-lime-100" accept=".kml" required>
                        </div>
                        <button type="submit" class="w-full bg-lime-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-lime-700 transition-colors">Importar Mapa KML</button>
                    </form>
                </div>

                <div class="card bg-white p-6 rounded-xl shadow-lg border-4 border-purple-500">
                     <h2 class="text-2xl font-bold mb-3 text-purple-700">Todos os Horários (Detalhado)</h2>
                     <p class="text-sm text-gray-600 mb-6">Importe o CSV "Todos os Horários.csv" (ex: 2025-01-06...). A data deve estar no nome.</p>
                     <form action="index.php" method="post" enctype="multipart/form-data">
                         <input type="hidden" name="form_type" value="todos_horarios">
                         <div class="mb-4"><label for="csv_todos_horarios" class="sr-only">Escolher arquivo</label><input type="file" name="csvfiles[]" id="csv_todos_horarios" class="block w-full text-sm text-gray-500" multiple required></div>
                         <fieldset class="mb-4 text-sm"><legend class="font-medium text-gray-700 mb-2">Modo de Importação:</legend><div class="flex gap-4"><div class="flex items-center"><input id="todos_truncate" name="import_mode" type="radio" value="truncate" class="h-4 w-4 text-purple-600 focus:ring-purple-500" checked><label for="todos_truncate" class="ml-2 text-gray-700">Limpar Dias do(s) Fich.</label></div><div class="flex items-center"><input id="todos_append" name="import_mode" type="radio" value="append" class="h-4 w-4 text-purple-600 focus:ring-purple-500"><label for="todos_append" class="ml-2 text-gray-700">Adicionar Dados</label></div></div></fieldset>
                         <p class="text-xs text-gray-500 mb-4">Nota: 'Limpar Dias' apaga apenas os dias presentes nos nomes dos ficheiros selecionados.</p>
                         <button type="submit" class="w-full bg-purple-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-purple-700 transition-colors">Importar Horários Detalhados</button>
                     </form>
                 </div>

                 <div class="card bg-white p-6 rounded-xl shadow-lg border-4 border-red-500">
                     <h2 class="text-2xl font-bold mb-3 text-red-700">Relatório Serviços (MTRAN)</h2>
                     <p class="text-sm text-gray-600 mb-6">Importe os CSVs diários (ex: 2026-01-06.csv). A data deve estar no nome.</p>
                     <form action="index.php" method="post" enctype="multipart/form-data">
                         <input type="hidden" name="form_type" value="km_precisao">
                         <div class="mb-4"><label for="km_precisao" class="sr-only">Escolher arquivo</label><input type="file" name="csvfiles[]" id="km_precisao" class="block w-full text-sm text-gray-500" multiple required></div>
                         <fieldset class="mb-4 text-sm"><legend class="font-medium text-gray-700 mb-2">Modo de Importação:</legend><div class="flex gap-4"><div class="flex items-center"><input id="km_precisao_radio" name="import_mode" type="radio" value="truncate" class="h-4 w-4 text-red-600 focus:ring-red-500" checked><label for="km_precisao_radio" class="ml-2 text-gray-700">Limpar Dias do(s) Fich.</label></div><div class="flex items-center"><input id="viagens_append" name="import_mode" type="radio" value="append" class="h-4 w-4 text-red-600 focus:ring-red-500"><label for="viagens_append" class="ml-2 text-gray-700">Adicionar Dados</label></div></div></fieldset>
                         <p class="text-xs text-gray-500 mb-4">Nota: 'Limpar Dias' apaga apenas os dias presentes nos nomes dos ficheiros selecionados.</p>
                         <button type="submit" class="w-full bg-red-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-red-700 transition-colors">Importar Serviços</button>
                     </form>
                 </div>
                 
                 <div class="card bg-white p-6 rounded-xl shadow-lg border-4 border-indigo-500">
                     <h2 class="text-2xl font-bold mb-3 text-indigo-700">Cadastros (Diário Bordo)</h2>
                     <p class="text-sm text-gray-600 mb-4">Importe Vias.csv e Locais.csv para o Diário de Bordo.</p>
                     
                     <form action="index.php" method="post" enctype="multipart/form-data" class="mb-4 pb-4 border-b">
                         <input type="hidden" name="form_type" value="cadastro_vias">
                         <label class="block text-sm font-bold text-gray-700 mb-1">Vias.csv</label>
                         <input type="file" name="csvfiles[]" class="block w-full text-sm text-gray-500 mb-2" required>
                         <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-2 rounded hover:bg-indigo-700">Importar Vias</button>
                     </form>

                     <form action="index.php" method="post" enctype="multipart/form-data">
                         <input type="hidden" name="form_type" value="cadastro_locais">
                         <label class="block text-sm font-bold text-gray-700 mb-1">Locais.csv</label>
                         <input type="file" name="csvfiles[]" class="block w-full text-sm text-gray-500 mb-2" required>
                         <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-2 rounded hover:bg-indigo-700">Importar Locais</button>
                     </form>
                 </div>
                 
                 <div class="card bg-white p-6 rounded-xl shadow-lg border-4 border-cyan-500">
                     <h2 class="text-2xl font-bold mb-3 text-cyan-700">Relatório Serviços (WorkIDs)</h2>
                     <p class="text-sm text-gray-600 mb-6">Importe o CSV "Turni Guida_*.csv" (planejamento mestre).</p>
                     <form action="index.php" method="post" enctype="multipart/form-data">
                         <input type="hidden" name="form_type" value="relatorio_servicos">
                         
                         <div class="grid grid-cols-2 gap-4 mb-4">
                             <div>
                                 <label for="data_inicio_vigencia_servicos" class="block text-sm font-medium text-gray-700">Início Vigência</label>
                                 <input type="date" name="data_inicio_vigencia" id="data_inicio_vigencia_servicos" class="mt-1 w-full p-2 border border-gray-300 rounded-md" required>
                             </div>
                             <div>
                                 <label for="data_fim_vigencia_servicos" class="block text-sm font-medium text-gray-700">Fim Vigência</label>
                                 <input type="date" name="data_fim_vigencia" id="data_fim_vigencia_servicos" class="mt-1 w-full p-2 border border-gray-300 rounded-md" required>
                             </div>
                         </div>
                         
                         <div class="mb-4">
                             <label for="csv_servicos" class="sr-only">Escolher arquivo</label>
                             <input type="file" name="csvfiles[]" id="csv_servicos" class="block w-full text-sm text-gray-500" multiple required>
                         </div>
                         <fieldset class="mb-4 text-sm">
                             <legend class="font-medium text-gray-700 mb-2">Modo de Importação:</legend>
                             <div class="flex gap-4">
                                 <div class="flex items-center">
                                     <input id="servicos_truncate" name="import_mode" type="radio" value="truncate" class="h-4 w-4 text-cyan-600 focus:ring-cyan-500" checked>
                                     <label for="servicos_truncate" class="ml-2 text-gray-700">Limpar Vigência e Importar</label>
                                 </div>
                                 <div class="flex items-center">
                                     <input id="servicos_append" name="import_mode" type="radio" value="append" class="h-4 w-4 text-cyan-600 focus:ring-cyan-500">
                                     <label for="servicos_append" class="ml-2 text-gray-700">Adicionar Dados</label>
                                 </div>
                             </div>
                         </fieldset>
                         <button type="submit" class="w-full bg-cyan-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-cyan-700 transition-colors">Importar Serviços</button>
                     </form>
                 </div>

                 <div class="card bg-white p-6 rounded-xl shadow-lg border-4 border-teal-500">
                     <h2 class="text-2xl font-bold mb-3 text-teal-700">Relatório Viagens (MTRAN)</h2>
                     <p class="text-sm text-gray-600 mb-6">Importe os CSVs diários (ex: 2025-11-03.csv). A data deve estar no nome.</p>
                     <form action="index.php" method="post" enctype="multipart/form-data">
                         <input type="hidden" name="form_type" value="relatorio_viagens">
                         <div class="mb-4"><label for="csv_viagens" class="sr-only">Escolher arquivo</label><input type="file" name="csvfiles[]" id="csv_viagens" class="block w-full text-sm text-gray-500" multiple required></div>
                         <fieldset class="mb-4 text-sm"><legend class="font-medium text-gray-700 mb-2">Modo de Importação:</legend><div class="flex gap-4"><div class="flex items-center"><input id="viagens_truncate" name="import_mode" type="radio" value="truncate" class="h-4 w-4 text-teal-600 focus:ring-teal-500" checked><label for="viagens_truncate" class="ml-2 text-gray-700">Limpar Dias do(s) Fich.</label></div><div class="flex items-center"><input id="viagens_append" name="import_mode" type="radio" value="append" class="h-4 w-4 text-teal-600 focus:ring-teal-500"><label for="viagens_append" class="ml-2 text-gray-700">Adicionar Dados</label></div></div></fieldset>
                         <p class="text-xs text-gray-500 mb-4">Nota: 'Limpar Dias' apaga apenas os dias presentes nos nomes dos ficheiros selecionados.</p>
                         <button type="submit" class="w-full bg-teal-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-teal-700 transition-colors">Importar Viagens</button>
                     </form>
                 </div>

                <div class="card bg-white p-6 rounded-xl shadow-lg border-4 border-teal-500">
                     <h2 class="text-2xl font-bold mb-3 text-teal-700">Relatório Viagens (Ajuste)</h2>
                     <p class="text-sm text-gray-600 mb-6">Importe os CSVs de ajuste (ex: 2025-11-03.csv). A data deve estar no nome.</p>
                     <form action="index.php" method="post" enctype="multipart/form-data">
                         <input type="hidden" name="form_type" value="relatorio_viagens_ajuste">
                         <div class="mb-4"><label for="csv_viagens_ajuste" class="sr-only">Escolher arquivo</label><input type="file" name="csvfiles[]" id="csv_viagens_ajuste" class="block w-full text-sm text-gray-500" multiple required></div>
                         <fieldset class="mb-4 text-sm"><legend class="font-medium text-gray-700 mb-2">Modo de Importação:</legend><div class="flex gap-4"><div class="flex items-center"><input id="viagens_ajuste_truncate" name="import_mode" type="radio" value="truncate" class="h-4 w-4 text-teal-600 focus:ring-teal-500" checked><label for="viagens_ajuste_truncate" class="ml-2 text-gray-700">Limpar Dias do(s) Fich.</label></div><div class="flex items-center"><input id="viagens_append_ajuste" name="import_mode" type="radio" value="append" class="h-4 w-4 text-teal-600 focus:ring-teal-500"><label for="viagens_append_ajuste" class="ml-2 text-gray-700">Adicionar Dados</label></div></div></fieldset>
                         <p class="text-xs text-gray-500 mb-4">Nota: 'Limpar Dias' apaga apenas os dias presentes nos nomes dos ficheiros selecionados.</p>
                         <button type="submit" class="w-full bg-teal-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-teal-700 transition-colors">Importar Viagens</button>
                     </form>
                 </div>

                 <div class="card bg-white p-6 rounded-xl shadow-lg">
                     <h2 class="text-2xl font-bold mb-3 text-blue-700">Relatório 0241 Geral</h2>
                     <p class="text-sm text-gray-600 mb-6">Importe os dados de chegadas e partidas nos terminais e pontos finais.</p>
                     <form action="index.php" method="post" enctype="multipart/form-data">
                         <input type="hidden" name="form_type" value="0241_geral">
                         <div class="mb-4"><label for="csv_0241" class="sr-only">Escolher arquivo</label><input type="file" name="csvfiles[]" id="csv_0241" class="block w-full text-sm text-gray-500" multiple required></div>
                         <fieldset class="mb-4 text-sm"><legend class="font-medium text-gray-700 mb-2">Modo de Importação:</legend><div class="flex gap-4"><div class="flex items-center"><input id="g_truncate" name="import_mode" type="radio" value="truncate" class="h-4 w-4 text-blue-600 focus:ring-blue-500" checked><label for="g_truncate" class="ml-2 text-gray-700">Limpar e Importar</label></div><div class="flex items-center"><input id="g_append" name="import_mode" type="radio" value="append" class="h-4 w-4 text-blue-600 focus:ring-blue-500"><label for="g_append" class="ml-2 text-gray-700">Adicionar Dados</label></div></div></fieldset>
                         <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors">Importar 0241</button>
                     </form>
                 </div>

                 <div class="card bg-white p-6 rounded-xl shadow-lg">
                     <h2 class="text-2xl font-bold mb-3 text-purple-700">Relatório 108 Geral</h2>
                     <p class="text-sm text-gray-600 mb-6">Importe os dados de pontualidade de chegadas e partidas nos pontos de controle.</p>
                     <form action="index.php" method="post" enctype="multipart/form-data">
                         <input type="hidden" name="form_type" value="timepoint_geral">
                         <div class="mb-4"><label for="csv_timepoint" class="sr-only">Escolher arquivo</label><input type="file" name="csvfiles[]" id="csv_timepoint" class="block w-full text-sm text-gray-500" multiple required></div>
                         <fieldset class="mb-4 text-sm"><legend class="font-medium text-gray-700 mb-2">Modo de Importação:</legend><div class="flex gap-4"><div class="flex items-center"><input id="t_truncate" name="import_mode" type="radio" value="truncate" class="h-4 w-4 text-purple-600 focus:ring-purple-500" checked><label for="t_truncate" class="ml-2 text-gray-700">Limpar e Importar</label></div><div class="flex items-center"><input id="t_append" name="import_mode" type="radio" value="append" class="h-4 w-4 text-purple-600 focus:ring-purple-500"><label for="t_append" class="ml-2 text-gray-700">Adicionar Dados</label></div></div></fieldset>
                         <button type="submit" class="w-full bg-purple-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-purple-700 transition-colors">Importar Timepoint</button>
                     </form>
                 </div>

                 <div class="card bg-white p-6 rounded-xl shadow-lg">
                     <h2 class="text-2xl font-bold mb-3 text-emerald-700">Relatório 004 de Frota</h2>
                     <p class="text-sm text-gray-600 mb-6">Importe os dados de desempenho da frota (distância e horas).</p>
                     <form action="index.php" method="post" enctype="multipart/form-data">
                         <input type="hidden" name="form_type" value="relatorio_frota">
                         <div class="mb-4"><label for="csv_frota" class="sr-only">Escolher arquivo</label><input type="file" name="csvfiles[]" id="csv_frota" class="block w-full text-sm text-gray-500" multiple required></div>
                         <fieldset class="mb-4 text-sm"><legend class="font-medium text-gray-700 mb-2">Modo de Importação:</legend><div class="flex gap-4"><div class="flex items-center"><input id="f_truncate" name="import_mode" type="radio" value="truncate" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500" checked><label for="f_truncate" class="ml-2 text-gray-700">Limpar e Importar</label></div><div class="flex items-center"><input id="f_append" name="import_mode" type="radio" value="append" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500"><label for="f_append" class="ml-2 text-gray-700">Adicionar Dados</label></div></div></fieldset>
                         <button type="submit" class="w-full bg-emerald-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-emerald-700 transition-colors">Importar Frota</button>
                     </form>
                 </div>

                 <div class="card bg-white p-6 rounded-xl shadow-lg">
                     <h2 class="text-2xl font-bold mb-3 text-pink-700">Relatório 142 (KM Clever)</h2>
                     <p class="text-sm text-gray-600 mb-6">Importe os dados de trabalho da frota por divisão (KMs - Clever Reports).</p>
                     <form action="index.php" method="post" enctype="multipart/form-data">
                         <input type="hidden" name="form_type" value="relatorio_divisao">
                         <div class="mb-4"><label for="csv_divisao" class="sr-only">Escolher arquivo</label><input type="file" name="csvfiles[]" id="csv_divisao" class="block w-full text-sm text-gray-500" multiple required></div>
                          <fieldset class="mb-4 text-sm"><legend class="font-medium text-gray-700 mb-2">Modo de Importação:</legend><div class="flex gap-4"><div class="flex items-center"><input id="d_truncate" name="import_mode" type="radio" value="truncate" class="h-4 w-4 text-pink-600 focus:ring-pink-500" checked><label for="d_truncate" class="ml-2 text-gray-700">Limpar e Importar</label></div><div class="flex items-center"><input id="d_append" name="import_mode" type="radio" value="append" class="h-4 w-4 text-pink-600 focus:ring-pink-500"><label for="d_append" class="ml-2 text-gray-700">Adicionar Dados</label></div></div></fieldset>
                         <button type="submit" class="w-full bg-pink-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-pink-700 transition-colors">Importar KM Clever</button>
                     </form>
                 </div>

                 <div class="card bg-white p-6 rounded-xl shadow-lg border-4 border-orange-500">
                     <h2 class="text-2xl font-bold mb-3 text-orange-700">Bilhetagem (CSV)</h2>
                     <p class="text-sm text-gray-600 mb-6">Importe o relatório CSV de bilhetagem (por linha).</p>
                     <form action="index.php" method="post" enctype="multipart/form-data">
                         <input type="hidden" name="form_type" value="bilhetagem">
                         <div class="mb-4">
                             <label for="csv_bilhetagem" class="sr-only">Escolher arquivo</label>
                             <input type="file" name="csvfiles[]" id="csv_bilhetagem" class="block w-full text-sm text-gray-500" multiple required>
                         </div>
                         <fieldset class="mb-4 text-sm">
                             <legend class="font-medium text-gray-700 mb-2">Modo:</legend>
                             <div class="flex gap-4">
                                 <div class="flex items-center">
                                     <input id="bilhetagem_truncate" name="import_mode" type="radio" value="truncate" class="h-4 w-4 text-orange-600 focus:ring-orange-500" checked>
                                     <label for="bilhetagem_truncate" class="ml-2 text-gray-700">Limpar e Importar</label>
                                 </div>
                                 <div class="flex items-center">
                                     <input id="bilhetagem_append" name="import_mode" type="radio" value="append" class="h-4 w-4 text-orange-600 focus:ring-orange-500">
                                     <label for="bilhetagem_append" class="ml-2 text-gray-700">Adicionar</label>
                                 </div>
                             </div>
                         </fieldset>
                         <button type="submit" class="w-full bg-orange-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-orange-700 transition-colors">Importar Bilhetagem</button>
                     </form>
                 </div>

                 <div class="card bg-white p-6 rounded-xl shadow-lg border-4 border-yellow-500">
                     <h2 class="text-2xl font-bold mb-3 text-yellow-700">KM Diário (Roleta)</h2>
                     <p class="text-sm text-gray-600 mb-6">Importe o CSV diário da roleta. A data deve estar no nome (ex: ...2025-10-01.csv).</p>
                     <form action="index.php" method="post" enctype="multipart/form-data">
                         <input type="hidden" name="form_type" value="km_roleta_diario">
                         <div class="mb-4"><label for="csv_km_roleta" class="sr-only">Escolher arquivo</label><input type="file" name="csvfiles[]" id="csv_km_roleta" class="block w-full text-sm text-gray-500" multiple required></div>
                         <fieldset class="mb-4 text-sm"><legend class="font-medium text-gray-700 mb-2">Modo de Importação:</legend><div class="flex gap-4"><div class="flex items-center"><input id="km_r_truncate" name="import_mode" type="radio" value="truncate" class="h-4 w-4 text-yellow-600 focus:ring-yellow-500" checked><label for="km_r_truncate" class="ml-2 text-gray-700">Limpar Dias do(s) Fich.</label></div><div class="flex items-center"><input id="km_r_append" name="import_mode" type="radio" value="append" class="h-4 w-4 text-yellow-600 focus:ring-yellow-500"><label for="km_r_append" class="ml-2 text-gray-700">Adicionar Dados</label></div></div></fieldset>
                         <p class="text-xs text-gray-500 mb-4">Nota: 'Limpar Dias' apaga apenas os dias presentes nos nomes dos ficheiros selecionados.</p>
                         <button type="submit" class="w-full bg-yellow-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-yellow-700 transition-colors">Importar Roleta</button>
                     </form>
                 </div>

                 <div class="card bg-white p-6 rounded-xl shadow-lg border-4 border-cyan-500">
                     <h2 class="text-2xl font-bold mb-3 text-cyan-700">KM Telemetria (Noxxon)</h2>
                     <p class="text-sm text-gray-600 mb-6">Importe o CSV diário da telemetria (ex: ...Noxxon.csv). A data está na coluna.</p>
                     <form action="index.php" method="post" enctype="multipart/form-data">
                         <input type="hidden" name="form_type" value="relatorio_noxxon">
                         <div class="mb-4"><label for="csv_km_noxxon" class="sr-only">Escolher arquivo</label><input type="file" name="csvfiles[]" id="csv_km_noxxon" class="block w-full text-sm text-gray-500" multiple required></div>
                         <fieldset class="mb-4 text-sm"><legend class="font-medium text-gray-700 mb-2">Modo de Importação:</legend><div class="flex gap-4"><div class="flex items-center"><input id="km_n_truncate" name="import_mode" type="radio" value="truncate" class="h-4 w-4 text-cyan-600 focus:ring-cyan-500" checked><label for="km_n_truncate" class="ml-2 text-gray-700">Limpar Dias do(s) Fich.</label></div><div class="flex items-center"><input id="km_n_append" name="import_mode" type="radio" value="append" class="h-4 w-4 text-cyan-600 focus:ring-cyan-500"><label for="km_n_append" class="ml-2 text-gray-700">Adicionar Dados</label></div></div></fieldset>
                         <p class="text-xs text-gray-500 mb-4">Nota: 'Limpar Dias' apaga apenas os dias presentes nas colunas de data dos ficheiros selecionados.</p>
                         <button type="submit" class="w-full bg-cyan-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-cyan-700 transition-colors">Importar Noxxon</button>
                     </form>
                 </div>

                 <div class="card bg-white p-6 rounded-xl shadow-lg border-4 border-indigo-500">
                     <h2 class="text-2xl font-bold mb-3 text-indigo-700">KM Odômetro (Life)</h2>
                     <p class="text-sm text-gray-600 mb-6">Importe o relatório de odômetro do rastreador (porta CAN).</p>
                     <form action="index.php" method="post" enctype="multipart/form-data">
                         <input type="hidden" name="form_type" value="odometro_life">
                         <div class="mb-4"><label for="csv_odometro_life" class="sr-only">Escolher arquivo</label><input type="file" name="csvfiles[]" id="csv_odometro_life" class="block w-full text-sm text-gray-500" multiple required></div>
                         <fieldset class="mb-4 text-sm"><legend class="font-medium text-gray-700 mb-2">Modo de Importação:</legend><div class="flex gap-4"><div class="flex items-center"><input id="life_truncate" name="import_mode" type="radio" value="truncate" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500" checked><label for="life_truncate" class="ml-2 text-gray-700">Limpar e Importar</label></div><div class="flex items-center"><input id="life_append" name="import_mode" type="radio" value="append" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500"><label for="life_append" class="ml-2 text-gray-700">Adicionar Dados</label></div></div></fieldset>
                         <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-indigo-700 transition-colors">Importar Life</button>
                     </form>
                 </div>

                 <div class="card bg-white p-6 rounded-xl shadow-lg border-4 border-red-500">
                     <h2 class="text-2xl font-bold mb-3 text-red-700">Perda de Comunicação</h2>
                     <p class="text-sm text-gray-600 mb-6">Importe o relatório de perda de sinal (Clever Reports).</p>
                     <form action="index.php" method="post" enctype="multipart/form-data">
                         <input type="hidden" name="form_type" value="comunicacao">
                         <div class="mb-4"><label for="csv_comunicacao" class="sr-only">Escolher arquivo</label><input type="file" name="csvfiles[]" id="csv_comunicacao" class="block w-full text-sm text-gray-500" multiple required></div>
                         <fieldset class="mb-4 text-sm"><legend class="font-medium text-gray-700 mb-2">Modo de Importação:</legend><div class="flex gap-4"><div class="flex items-center"><input id="com_truncate" name="import_mode" type="radio" value="truncate" class="h-4 w-4 text-red-600 focus:ring-red-500" checked><label for="com_truncate" class="ml-2 text-gray-700">Limpar e Importar</label></div><div class="flex items-center"><input id="com_append" name="import_mode" type="radio" value="append" class="h-4 w-4 text-red-600 focus:ring-red-500"><label for="com_append" class="ml-2 text-gray-700">Adicionar Dados</label></div></div></fieldset>
                         <button type="submit" class="w-full bg-red-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-red-700 transition-colors">Importar Comunicação</button>
                     </form>
                 </div>
                 
                 <div class="card bg-gray-100 p-6 rounded-xl shadow-inner">
                     <h2 class="text-2xl font-bold mb-3 text-gray-700">Resumo ICV / IPV</h2>
                     <p class="text-sm text-gray-600 mb-6">Importe o resumo diário de cumprimento e pontualidade das viagens.</p>
                     <form action="index.php" method="post" enctype="multipart/form-data">
                         <input type="hidden" name="form_type" value="icv_ipv">
                         <div class="mb-4"><label for="csv_icv_ipv" class="sr-only">Escolher arquivo</label><input type="file" name="csvfiles[]" id="csv_icv_ipv" class="block w-full text-sm text-gray-500" multiple required></div>
                         <fieldset class="mb-4 text-sm"><legend class="font-medium text-gray-700 mb-2">Modo de Importação:</legend><div class="flex gap-4"><div class="flex items-center"><input id="icv_truncate" name="import_mode" type="radio" value="truncate" class="h-4 w-4 text-gray-600 focus:ring-gray-500" checked><label for="icv_truncate" class="ml-2 text-gray-700">Limpar e Importar</label></div><div class="flex items-center"><input id="icv_append" name="import_mode" type="radio" value="append" class="h-4 w-4 text-gray-600 focus:ring-gray-500"><label for="icv_append" class="ml-2 text-gray-700">Adicionar/Atualizar Dias</label></div></div></fieldset>
                         <p class="text-xs text-gray-500 mb-4">'Adicionar/Atualizar' insere novos dias ou substitui os existentes.</p>
                         <button type="submit" class="w-full bg-gray-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-gray-700 transition-colors">Importar Resumo ICV</button>
                     </form>
                 </div>

                 <div class="card bg-gray-100 p-6 rounded-xl shadow-inner">
                     <h2 class="text-2xl font-bold mb-3 text-gray-700">Resumo 103 On-Time</h2>
                     <p class="text-sm text-gray-600 mb-6">Importe o resumo diário de pontualidade dos timepoints.</p>
                     <form action="index.php" method="post" enctype="multipart/form-data">
                         <input type="hidden" name="form_type" value="on_time">
                         <div class="mb-4"><label for="csv_on_time" class="sr-only">Escolher arquivo</label><input type="file" name="csvfiles[]" id="csv_on_time" class="block w-full text-sm text-gray-500" multiple required></div>
                         <fieldset class="mb-4 text-sm"><legend class="font-medium text-gray-700 mb-2">Modo de Importação:</legend><div class="flex gap-4"><div class="flex items-center"><input id="ontime_truncate" name="import_mode" type="radio" value="truncate" class="h-4 w-4 text-gray-600 focus:ring-gray-500" checked><label for="ontime_truncate" class="ml-2 text-gray-700">Limpar e Importar</label></div><div class="flex items-center"><input id="ontime_append" name="import_mode" type="radio" value="append" class="h-4 w-4 text-gray-600 focus:ring-gray-500"><label for="ontime_append" class="ml-2 text-gray-700">Adicionar/Atualizar Dias</label></div></div></fieldset>
                          <p class="text-xs text-gray-500 mb-4">'Adicionar/Atualizar' insere novos dias ou substitui os existentes.</p>
                         <button type="submit" class="w-full bg-gray-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-gray-700 transition-colors">Importar Resumo OnTime</button>
                     </form>
                 </div>

             </div>
         </section>
      </main>
 </body>
 </html>