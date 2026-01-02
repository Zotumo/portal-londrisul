<?php
// parts/listar_mensagens.php (v3 - Com Collapse e clique para ler/marcar)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once dirname(__DIR__) . '/db_config.php';

if (!isset($_SESSION['user_id']) || $pdo === null) {
    echo "<div class='alert alert-warning'>Acesso não autorizado ou falha na conexão.</div>";
    exit;
}

$motorista_id_logado = $_SESSION['user_id'];
$mensagens = [];
$erro_busca_msg = false;

try {
    $sql = "SELECT id, remetente, assunto, mensagem, data_envio, data_leitura
            FROM mensagens_motorista WHERE motorista_id = :motorista_id
            ORDER BY data_envio DESC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':motorista_id', $motorista_id_logado, PDO::PARAM_INT);
    $stmt->execute();
    $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { error_log("Erro msg: ".$e->getMessage()); $erro_busca_msg = true; }

// Exibe as mensagens
if ($erro_busca_msg) { echo "<div class='alert alert-danger'>Erro ao carregar mensagens.</div>"; }
elseif (empty($mensagens)) { echo "<p class='text-info p-3'>Você não possui mensagem.</p>"; }
else {
    // Usaremos um container simples, e cada mensagem será um 'card' ou similar
    echo '<div id="mensagens-accordion">'; // Container para o collapse funcionar melhor

    foreach ($mensagens as $msg) {
        $lida = ($msg['data_leitura'] !== null);
        $id_html_base = "msg-" . $msg['id'];
        $id_html_collapse = "collapse-" . $id_html_base;

        // Estilo e classes para o cabeçalho/trigger
        $classe_trigger = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center '; // Classes base do trigger
        $classe_trigger .= $lida ? '' : 'list-group-item-primary mensagem-nao-lida'; // Destaque e classe p/ JS se não lida
        $peso_fonte = $lida ? 'font-weight-normal' : 'font-weight-bold';

        $data_envio_fmt = date('d/m/Y H:i', strtotime($msg['data_envio']));
        $data_leitura_fmt = $lida ? date('d/m/Y H:i', strtotime($msg['data_leitura'])) : null;

        // Cabeçalho Clicável (Trigger do Collapse)
        echo '<div class="'. trim($classe_trigger) . '" ';
        echo ' data-toggle="collapse" data-target="#' . $id_html_collapse . '" '; // Atributos do Bootstrap Collapse
        echo ' aria-expanded="false" aria-controls="' . $id_html_collapse . '" ';
        echo ' data-msg-id="' . $msg['id'] . '" style="cursor: pointer;">'; // ID para o JS saber qual mensagem
        echo '   <div class="msg-summary ' . $peso_fonte . '">'; // Container para remetente/assunto
        echo '      <i class="fas fa-user-tie mr-1"></i> ' . htmlspecialchars($msg['remetente']);
        if (!empty($msg['assunto'])) {
             echo '<br><small class="text-muted"> Assunto: ' . htmlspecialchars($msg['assunto']) . '</small>';
        }
        echo '   </div>';
        echo '   <small class="text-muted msg-status" title="Enviado em: '.$data_envio_fmt.'">'; // Status (Nova ou Lida)
        if($lida) { echo '<i class="fas fa-check-circle text-success"></i> Lida'; }
        else { echo '<strong class="text-primary"><i class="fas fa-envelope"></i> Nova</strong>'; }
        echo '<br>' . $data_envio_fmt; // Data de envio sempre visível
        echo '</small>';
        echo '</div>'; // Fim do Cabeçalho/Trigger

        // Conteúdo Recolhível (Collapse)
        echo '<div class="collapse border border-top-0 p-3 mb-2" id="' . $id_html_collapse . '" data-parent="#mensagens-accordion">'; // mb-2 para espaço
        echo '  <p class="mb-1">' . nl2br(htmlspecialchars($msg['mensagem'])) . '</p>'; // Mensagem completa
        echo '  <hr>';
        echo '  <small class="text-muted msg-status-detail">';
        if($lida) { echo 'Lida em: ' . $data_leitura_fmt; }
        else { echo 'Mensagem ainda não lida.'; }
        echo ' </small>';
        echo '</div>'; // Fim do Collapse
    }
    echo '</div>'; // Fim #mensagens-accordion
}
?>