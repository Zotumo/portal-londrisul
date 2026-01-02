<?php
// admin/mensagem_visualizar.php

require_once 'auth_check.php'; // Autenticação e permissões

$niveis_permitidos_ver_mensagens = ['Operacional', 'CIOP Monitoramento', 'Instrutores', 'CIOP Planejamento', 'Supervisores', 'Gerência', 'Administrador'];
if (!in_array($admin_nivel_acesso_logado, $niveis_permitidos_ver_mensagens)) {
    $_SESSION['admin_error_message'] = "Você não tem permissão para visualizar detalhes de mensagens.";
    header('Location: mensagens_listar.php');
    exit;
}

require_once '../db_config.php'; // Conexão com o banco

$mensagem_id_interno = null; // Usaremos este para buscar, mas não necessariamente para exibir
$mensagem_detalhes = null;
$erro_ao_buscar = false;

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $mensagem_id_interno = (int)$_GET['id']; // Guarda o ID para a query
} else {
    $_SESSION['admin_error_message'] = "ID da mensagem inválido ou não fornecido.";
    header('Location: mensagens_listar.php');
    exit;
}

if ($pdo && $mensagem_id_interno) {
    try {
        $sql = "SELECT msg.id, msg.assunto, msg.mensagem, msg.remetente, msg.data_envio, msg.data_leitura,
                       mot.nome as nome_motorista, mot.matricula as matricula_motorista
                FROM mensagens_motorista AS msg
                JOIN motoristas AS mot ON msg.motorista_id = mot.id
                WHERE msg.id = :mensagem_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':mensagem_id', $mensagem_id_interno, PDO::PARAM_INT);
        $stmt->execute();
        $mensagem_detalhes = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$mensagem_detalhes) {
            $_SESSION['admin_error_message'] = "Mensagem com ID {$mensagem_id_interno} não encontrada.";
            header('Location: mensagens_listar.php');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Erro ao buscar detalhes da mensagem ID {$mensagem_id_interno}: " . $e->getMessage());
        $erro_ao_buscar = true;
    }
} else {
    $_SESSION['admin_error_message'] = "Falha na conexão com o banco ou ID da mensagem inválido.";
    header('Location: mensagens_listar.php');
    exit;
}

// Título da página sem o ID explícito, a menos que queira para debug ou referência interna
$page_title = 'Detalhes da Mensagem';


require_once 'admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo $page_title; // Título da página agora é mais genérico ?></h1>
    <a href="mensagens_listar.php?pagina=<?php echo isset($_GET['pagina']) ? htmlspecialchars($_GET['pagina']) : '1'; ?>&busca_motorista=<?php echo isset($_GET['busca_motorista']) ? urlencode($_GET['busca_motorista']) : ''; ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Voltar para Lista de Mensagens
    </a>
</div>

<?php if ($erro_ao_buscar): ?>
    <div class="alert alert-danger">
        Ocorreu um erro ao tentar carregar os detalhes da mensagem. Por favor, tente novamente ou contate o suporte.
    </div>
<?php elseif ($mensagem_detalhes): ?>
    <div class="card">
        <div class="card-header">
            <strong>Assunto:</strong> <?php echo htmlspecialchars($mensagem_detalhes['assunto'] ?: '(Sem assunto)'); ?>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Destinatário:</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($mensagem_detalhes['nome_motorista']); ?> (Matrícula: <?php echo htmlspecialchars($mensagem_detalhes['matricula_motorista']); ?>)</dd>

                <dt class="col-sm-3">Remetente:</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($mensagem_detalhes['remetente']); ?></dd>

                <dt class="col-sm-3">Data de Envio:</dt>
                <dd class="col-sm-9"><?php echo date('d/m/Y H:i:s', strtotime($mensagem_detalhes['data_envio'])); ?></dd>

                <dt class="col-sm-3">Status da Leitura:</dt>
                <dd class="col-sm-9">
                    <?php if ($mensagem_detalhes['data_leitura']): ?>
                        <span class="text-success"><i class="fas fa-check-circle"></i> Lida em <?php echo date('d/m/Y H:i:s', strtotime($mensagem_detalhes['data_leitura'])); ?></span>
                    <?php else: ?>
                        <span class="text-warning"><i class="fas fa-envelope"></i> Não Lida</span>
                    <?php endif; ?>
                </dd>
            </dl>
            <hr>
            <h5 class="card-title mt-4">Conteúdo da Mensagem:</h5>
            <div class="mensagem-conteudo p-3 bg-light border rounded" style="white-space: pre-wrap; word-wrap: break-word;">
                <?php echo nl2br(htmlspecialchars($mensagem_detalhes['mensagem'])); ?>
            </div>
        </div>
        <div class="card-footer text-muted">
            Página visualizada em: <?php echo date('d/m/Y H:i:s'); ?>
            <?php // Se quiser manter o ID da mensagem de forma discreta para referência do admin, pode colocar aqui:
                  // echo " (Ref. Interna Msg ID: " . htmlspecialchars($mensagem_detalhes['id']) . ")";
            ?>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning">Nenhuma informação de mensagem para exibir.</div>
<?php endif; ?>

<?php
require_once 'admin_footer.php';
?>