<?php
// admin/gerar_relatorio.php (v5 - Refinamento Contagem para "Todos os Cargos")
require_once 'auth_check.php';
require_once '../db_config.php';

// (Permissões como antes)
$niveis_permitidos_gerar_relatorios = ['CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_gerar_relatorios)) {
    if (isset($_POST['modo_exibicao']) && $_POST['modo_exibicao'] === 'html') {
        echo '<div class="alert alert-danger">Acesso negado para gerar este relatório.</div>';
    } else {
        // Para evitar que a sessão seja sobrescrita se a página de relatórios_index.php
        // já definiu um feedback, podemos verificar se já existe um.
        if (!isset($_SESSION['admin_feedback'])) {
            $_SESSION['admin_feedback'] = ['type' => 'error', 'message' => 'Acesso negado para gerar este relatório.'];
        }
        header('Location: relatorios_index.php');
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tipo_relatorio'])) {
    $tipo_relatorio = $_POST['tipo_relatorio'];
    $modo_exibicao = $_POST['modo_exibicao'] ?? 'download_csv';
    $formato_exportacao = $_POST['formato_exportacao'] ?? 'csv'; // Para quando o modo é download_*

    $filtro_cargo_rel = $_POST['filtro_cargo'] ?? '';
    $filtro_status_rel = $_POST['filtro_status'] ?? '';
    $filtro_data_contratacao_de_rel = $_POST['filtro_data_contratacao_de'] ?? '';
    $filtro_data_contratacao_ate_rel = $_POST['filtro_data_contratacao_ate'] ?? '';

    if (!$pdo) { 
        if ($modo_exibicao === 'html') { echo '<div class="alert alert-danger">Erro de conexão com o banco de dados.</div>'; exit; }
        else { die("Erro de conexão com o banco de dados."); }
    }

    $html_output = "";
    $nome_arquivo_base = "relatorio_";

    // =========================================================================
    // RELATÓRIO: CONTAGEM DE FUNCIONÁRIOS POR STATUS E CARGO
    // =========================================================================
    if ($tipo_relatorio === 'contagem_funcionarios_status_cargo') {
        $nome_arquivo_base = "rel_contagem_funcionarios_";
        try {
            $resultados_finais_contagem = []; // Array final para os dados
            $status_possiveis_contagem = ['ativo', 'inativo'];

            // 1. Determinar a lista de cargos para o relatório
            $cargos_base_para_relatorio = [];
            if (!empty($filtro_cargo_rel) && $filtro_cargo_rel !== 'NAO_MOTORISTA') {
                $cargos_base_para_relatorio = [$filtro_cargo_rel]; // Filtro para um cargo específico
            } else { // "Todos os Cargos" ou "NAO_MOTORISTA"
                $stmt_cargos_todos_db = $pdo->query("SELECT DISTINCT cargo FROM motoristas WHERE cargo IS NOT NULL AND cargo != '' ORDER BY cargo ASC");
                $todos_cargos_no_db = $stmt_cargos_todos_db->fetchAll(PDO::FETCH_COLUMN);
                
                if ($filtro_cargo_rel === 'NAO_MOTORISTA') {
                    foreach ($todos_cargos_no_db as $cargo_item_db) {
                        if (strtolower($cargo_item_db) !== 'motorista') { // Comparação case-insensitive
                            $cargos_base_para_relatorio[] = $cargo_item_db;
                        }
                    }
                } else { // "Todos os Cargos"
                    $cargos_base_para_relatorio = $todos_cargos_no_db;
                }
            }
            
            // 2. Inicializar $resultados_finais_contagem com 0 para todas as combinações relevantes
            foreach ($cargos_base_para_relatorio as $cargo_init) {
                foreach ($status_possiveis_contagem as $status_init) {
                    // Se um filtro de status foi aplicado, só inicializa para esse status
                    if (empty($filtro_status_rel) || $filtro_status_rel === $status_init) {
                        $resultados_finais_contagem[$cargo_init . '_' . $status_init] = [
                            'cargo' => $cargo_init,
                            'status' => $status_init,
                            'total' => 0
                        ];
                    }
                }
            }

            // 3. Query para buscar as contagens reais do banco, aplicando os filtros
            $sql_busca_contagens = "SELECT cargo, status, COUNT(*) as total FROM motoristas ";
            $where_contagens = [];
            $params_contagens = [];

            // Aplica filtro de cargo NA QUERY apenas se um cargo específico (NÃO 'NAO_MOTORISTA') foi selecionado
            if (!empty($filtro_cargo_rel) && $filtro_cargo_rel !== 'NAO_MOTORISTA') {
                $where_contagens[] = "cargo = :p_cargo";
                $params_contagens[':p_cargo'] = $filtro_cargo_rel;
            } 
            // Se for NAO_MOTORISTA, a filtragem de quais cargos mostrar já foi feita em $cargos_base_para_relatorio.
            // A query buscará as contagens para todos os cargos (exceto motorista) e o loop de atualização cuidará disso.
            // Ou, se você quiser que a query já exclua 'Motorista' quando NAO_MOTORISTA é selecionado:
            elseif ($filtro_cargo_rel === 'NAO_MOTORISTA') {
                 $where_contagens[] = "cargo != :p_cargo_motorista_exc";
                 $params_contagens[':p_cargo_motorista_exc'] = 'Motorista';
            }


            if (!empty($filtro_status_rel)) {
                $where_contagens[] = "status = :p_status";
                $params_contagens[':p_status'] = $filtro_status_rel;
            }

            if (!empty($where_contagens)) {
                $sql_busca_contagens .= " WHERE " . implode(" AND ", $where_contagens);
            }
            $sql_busca_contagens .= " GROUP BY cargo, status ORDER BY cargo, status";

            $stmt_contagens_reais = $pdo->prepare($sql_busca_contagens);
            $stmt_contagens_reais->execute($params_contagens);
            $dados_reais_db = $stmt_contagens_reais->fetchAll(PDO::FETCH_ASSOC);

            // 4. Atualizar a estrutura $resultados_finais_contagem com as contagens reais
            foreach ($dados_reais_db as $dado_real) {
                $chave = $dado_real['cargo'] . '_' . $dado_real['status'];
                // Atualiza apenas se a chave (cargo_status) existir na nossa estrutura pré-montada
                // Isso garante que só mostremos os cargos/status que definimos em $cargos_base_para_relatorio
                // e $status_possiveis_contagem (respeitando o filtro de status).
                if (isset($resultados_finais_contagem[$chave])) {
                    $resultados_finais_contagem[$chave]['total'] = $dado_real['total'];
                }
            }
            
            // A ordenação já foi feita na query e na inicialização. Se precisar reordenar o array final:
            // uasort($resultados_finais_contagem, function($a, $b) { /* ... sua lógica de ordenação ... */ });


            if ($modo_exibicao === 'html') {
                if (!empty($resultados_finais_contagem)) {
                    $html_output .= "<div class='table-responsive'><table class='table table-sm table-striped table-hover table-bordered'>";
                    $html_output .= "<thead class='thead-light'><tr><th>Cargo</th><th>Status</th><th>Total</th></tr></thead><tbody>";
                    foreach ($resultados_finais_contagem as $linha_final) {
                        $html_output .= "<tr><td>" . htmlspecialchars($linha_final['cargo']) . "</td><td>" . htmlspecialchars(ucfirst($linha_final['status'])) . "</td><td>" . htmlspecialchars($linha_final['total']) . "</td></tr>";
                    }
                    $html_output .= "</tbody></table></div>";
                } else {
                    // Esta mensagem deve aparecer se, após todos os filtros, nenhum cargo/status se qualifica
                    $html_output = "<p class='text-info'>Nenhum dado de contagem encontrado para os filtros selecionados (ou nenhum funcionário cadastrado com os cargos/status consultados).</p>";
                }
                echo $html_output;
                exit;
            } elseif (strpos($modo_exibicao, 'download_') === 0) {
                // ... (lógica de download CSV como antes, usando $resultados_finais_contagem)
                if ($formato_exportacao === 'csv') {
                    $filename = $nome_arquivo_base . date('Ymd_His') . ".csv";
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    $output = fopen('php://output', 'w');
                    fputcsv($output, ['Cargo', 'Status', 'Total']); // Cabeçalho
                    if (!empty($resultados_finais_contagem)) {
                        foreach ($resultados_finais_contagem as $linha_final) {
                            fputcsv($output, [$linha_final['cargo'], ucfirst($linha_final['status']), $linha_final['total']]);
                        }
                    } else {
                        fputcsv($output, ['Nenhum dado encontrado.', '', '']);
                    }
                    fclose($output);
                    exit;
                }
                // ... (lógica PDF)
            }
        } catch (PDOException $e) {
            error_log("Erro ao gerar relatório de contagem de funcionários: " . $e->getMessage());
            if ($modo_exibicao === 'html') { echo '<div class="alert alert-danger">Erro ao gerar relatório. Consulte o administrador.</div>'; exit; }
            else { die("Erro ao gerar relatório. Consulte o administrador do sistema."); }
        }
    } 
    // ... (código para 'lista_funcionarios_detalhada' e outros relatórios) ...
    // Mantenha a lógica para lista_funcionarios_detalhada como estava na resposta anterior,
    // pois ela já filtrava corretamente com base nos parâmetros.
    elseif ($tipo_relatorio === 'lista_funcionarios_detalhada') {
        // ... (código deste relatório como na resposta anterior) ...
        $nome_arquivo_base = "rel_lista_funcionarios_detalhada_";
        try {
            $sql_base = "SELECT nome, matricula, cargo, status, data_contratacao, tipo_veiculo, email, telefone 
                         FROM motoristas ";
            $where_parts = [];
            $params = [];
            
            if (!empty($filtro_cargo_rel)) {
                if ($filtro_cargo_rel === 'NAO_MOTORISTA') {
                    $where_parts[] = "cargo != :cargo_param";
                    $params[':cargo_param'] = 'Motorista';
                } else {
                    $where_parts[] = "cargo = :cargo_param";
                    $params[':cargo_param'] = $filtro_cargo_rel;
                }
            }
            if (!empty($filtro_status_rel)) {
                $where_parts[] = "status = :status_param";
                $params[':status_param'] = $filtro_status_rel;
            }
            if (!empty($filtro_data_contratacao_de_rel)) {
                 $where_parts[] = "data_contratacao >= :data_de";
                 $params[':data_de'] = $filtro_data_contratacao_de_rel;
            }
            if (!empty($filtro_data_contratacao_ate_rel)) {
                 $where_parts[] = "data_contratacao <= :data_ate";
                 $params[':data_ate'] = $filtro_data_contratacao_ate_rel;
            }
            
            $sql_query = $sql_base;
            if (!empty($where_parts)) {
                $sql_query .= " WHERE " . implode(" AND ", $where_parts);
            }
            $sql_query .= " ORDER BY nome ASC";
            
            $stmt = $pdo->prepare($sql_query);
            $stmt->execute($params);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($modo_exibicao === 'html') {
                if ($resultados) {
                    $html_output .= "<div class='table-responsive'><table class='table table-sm table-striped table-hover table-bordered'>";
                    $html_output .= "<thead class='thead-light'><tr><th>Nome</th><th>Matrícula</th><th>Cargo</th><th>Status</th><th>Dt. Contratação</th><th>Tipo Veículo</th><th>Email</th><th>Telefone</th></tr></thead><tbody>";
                    foreach ($resultados as $linha) {
                        $html_output .= "<tr>";
                        $html_output .= "<td>" . htmlspecialchars($linha['nome']) . "</td>";
                        $html_output .= "<td>" . htmlspecialchars($linha['matricula']) . "</td>";
                        $html_output .= "<td>" . htmlspecialchars($linha['cargo']) . "</td>";
                        $html_output .= "<td>" . htmlspecialchars(ucfirst($linha['status'])) . "</td>";
                        $html_output .= "<td>" . ($linha['data_contratacao'] ? date('d/m/Y', strtotime($linha['data_contratacao'])) : '-') . "</td>";
                        $html_output .= "<td>" . htmlspecialchars($linha['tipo_veiculo'] ?: '-') . "</td>";
                        $html_output .= "<td>" . htmlspecialchars($linha['email'] ?: '-') . "</td>";
                        $html_output .= "<td>" . htmlspecialchars($linha['telefone'] ?: '-') . "</td>";
                        $html_output .= "</tr>";
                    }
                    $html_output .= "</tbody></table></div>";
                } else {
                    $html_output = "<p class='text-info'>Nenhum funcionário encontrado para os filtros selecionados.</p>";
                }
                echo $html_output;
                exit;
            } elseif (strpos($modo_exibicao, 'download_') === 0) {
                 if ($formato_exportacao === 'csv') {
                    $filename = $nome_arquivo_base . date('Ymd_His') . ".csv";
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    $output = fopen('php://output', 'w');
                    fputcsv($output, ['Nome', 'Matrícula', 'Cargo', 'Status', 'Data Contratação', 'Tipo Veículo', 'Email', 'Telefone']);
                    if ($resultados) {
                        foreach ($resultados as $linha) {
                            fputcsv($output, [
                                $linha['nome'], $linha['matricula'], $linha['cargo'], ucfirst($linha['status']),
                                $linha['data_contratacao'] ? date('d/m/Y', strtotime($linha['data_contratacao'])) : '',
                                $linha['tipo_veiculo'], $linha['email'], $linha['telefone']
                            ]);
                        }
                    } else {  fputcsv($output, ['Nenhum funcionário encontrado.', '', '', '', '', '', '', '']); }
                    fclose($output);
                    exit;
                } // ... lógica PDF
            }
        } catch (PDOException $e) {
            // ... tratamento de erro
        }
    }
    // ... (outros else if para mais relatórios) ...
    else {
        if ($modo_exibicao === 'html') { echo '<div class="alert alert-warning">Tipo de relatório não implementado para visualização.</div>'; exit; }
        else { die("Tipo de relatório desconhecido ou não selecionado para download."); }
    }
} else {
    $_SESSION['admin_feedback'] = ['type' => 'error', 'message' => 'Requisição inválida para gerar relatório.'];
    header('Location: relatorios_index.php');
    exit;
}
?>