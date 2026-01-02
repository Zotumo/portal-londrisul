<?php
// admin/ajax_locais.php
require_once 'auth_check.php';
require_once '../db_config.php';

header('Content-Type: application/json');

$acao = $_POST['acao'] ?? '';
$niveis = ['CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis)) { echo json_encode(['sucesso'=>false, 'msg'=>'Negado']); exit; }

try {

    // SALVAR (Modal)
    if ($acao == 'salvar_local') {
        $codigo = $_POST['codigo'];
        $nome = $_POST['nome'];
        $desc = $_POST['descricao'] ?? null;
        $coords = $_POST['coordenadas'] ?? null;
        $status = $_POST['status'] ?? 'ativo';
        
        // --- NOVO CAMPO: MOSTRAR PONTO ---
        // Se o checkbox foi marcado, vem '1'. Se não, não vem nada (0).
        $mostrar = isset($_POST['mostrar_ponto']) ? 1 : 0;

        $nome_img = null;
        if (isset($_FILES['imagem_arquivo']) && $_FILES['imagem_arquivo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['imagem_arquivo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif'])) throw new Exception("Imagem inválida.");
            $novoNome = "LOCAL_" . preg_replace('/[^A-Z0-9]/', '', $codigo) . "." . $ext;
            if(move_uploaded_file($_FILES['imagem_arquivo']['tmp_name'], '../img/pontos/' . $novoNome)) {
                $nome_img = $novoNome;
            }
        }

        // SQL ATUALIZADO COM mostrar_ponto
        $sql = "UPDATE relatorio.cadastros_locais SET 
                name = :nome, 
                descricao = :desc, 
                coordenadas = :coord, 
                status = :st, 
                mostrar_ponto = :mostrar 
                WHERE company_code = :cod";
        
        $params = [
            ':nome' => $nome, 
            ':desc' => $desc, 
            ':coord' => $coords, 
            ':st' => $status, 
            ':mostrar' => $mostrar,
            ':cod' => $codigo
        ];
        
        if ($nome_img) { 
            $sql = str_replace("WHERE", ", imagem_path = :img WHERE", $sql);
            $params[':img'] = $nome_img; 
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['sucesso' => true, 'msg' => 'Salvo com sucesso!']);
        exit;
    }

    // TOGGLE STATUS
    if ($acao == 'toggle_status') {
        $stmt = $pdo->prepare("UPDATE relatorio.cadastros_locais SET status = :st WHERE company_code = :cod");
        $stmt->execute([':st'=>$_POST['novo_status'], ':cod'=>$_POST['codigo']]);
        echo json_encode(['sucesso' => true]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'msg' => $e->getMessage()]);
}
?>