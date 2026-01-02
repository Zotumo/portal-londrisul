<?php
// admin/ajax_linhas.php
require_once 'auth_check.php';
require_once '../db_config.php';

header('Content-Type: application/json');

$acao = $_POST['acao'] ?? '';
$permite_editar = in_array($admin_nivel_acesso_logado, ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador']);
$permite_status = in_array($admin_nivel_acesso_logado, ['Supervisores', 'Gerência', 'Administrador']);

try {

    if ($acao == 'listar_vias') {
        $linha = $_POST['linha'];
        $stmt = $pdo->prepare("SELECT codigo, descricao, iframe_mapa FROM relatorio.cadastros_vias WHERE linha = :linha ORDER BY codigo ASC");
        $stmt->execute([':linha' => $linha]);
        echo json_encode(['sucesso' => true, 'vias' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($acao == 'salvar_dados') {
        if (!$permite_editar) throw new Exception("Sem permissão.");
        
        $numero = $_POST['numero'];
        $nome = $_POST['nome'];
        $status = $_POST['status_linha'] ?? null;
        $tipos_veiculo = $_POST['tipos_veiculo'] ?? [];

        $pdo->beginTransaction();

        $sql = "UPDATE relatorio.cadastros_vias SET nome_linha = :nome";
        $params = [':nome' => $nome, ':num' => $numero];
        if ($status && $permite_status) {
            $sql .= ", status_linha = :status";
            $params[':status'] = $status;
        }
        $sql .= " WHERE linha = :num";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $stmtDel = $pdo->prepare("DELETE FROM relatorio.linhas_veiculos WHERE linha_numero = :num");
        $stmtDel->execute([':num' => $numero]);

        if (!empty($tipos_veiculo)) {
            $stmtIns = $pdo->prepare("INSERT INTO relatorio.linhas_veiculos (linha_numero, tipo_veiculo) VALUES (:num, :tipo)");
            foreach ($tipos_veiculo as $tipo) {
                $stmtIns->execute([':num' => $numero, ':tipo' => $tipo]);
            }
        }

        $pdo->commit();
        echo json_encode(['sucesso' => true, 'msg' => 'Dados atualizados!']);
        exit;
    }

    // ATUALIZADO: Salva Mapa E Descrição
    if ($acao == 'salvar_mapa') {
        if (!$permite_editar) throw new Exception("Sem permissão.");
        
        $sql = "UPDATE relatorio.cadastros_vias SET iframe_mapa = :iframe, descricao = :desc WHERE codigo = :codigo";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':iframe' => $_POST['iframe_mapa'],
            ':desc'   => $_POST['descricao'], // Nova descrição editada
            ':codigo' => $_POST['codigo_via']
        ]);
        echo json_encode(['sucesso' => true, 'msg' => 'Rota/Mapa atualizados!']);
        exit;
    }

    if ($acao == 'toggle_status') {
        if (!$permite_status) throw new Exception("Sem permissão.");
        $stmt = $pdo->prepare("UPDATE relatorio.cadastros_vias SET status_linha = :novo WHERE linha = :num");
        $stmt->execute([':novo' => $_POST['novo_status'], ':num' => $_POST['numero']]);
        echo json_encode(['sucesso' => true, 'msg' => 'Status alterado!']);
        exit;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'msg' => $e->getMessage()]);
}
?>