<?php
// =================================================================
//  Parceiro de Programação - Gerenciador de Metas e Feriados (v2.0)
//  - TAB 1: Cadastrar Metas Mensais (Planilha Tarifária).
//  - TAB 2: Cadastrar Feriados (Com tipo de operação: Útil/Sáb/Dom).
// =================================================================

ini_set('display_errors', 1); error_reporting(E_ALL);
require_once 'config_km.php';

$conexao = new mysqli("localhost", "root", "", "relatorio");
if ($conexao->connect_error) { die("Falha na conexão: " . $conexao->connect_error); }
$conexao->set_charset("utf8mb4");

$msg = '';
$tipo_msg = '';

// --- PROCESSAR AÇÕES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // 1. SALVAR METAS
    if ($acao === 'salvar_metas') {
        $ano_selecionado = (int)$_POST['ano'];
        $metas = $_POST['meta'] ?? [];
        $stmt = $conexao->prepare("INSERT INTO metas_planilha_tarifaria (ano, mes, km_meta) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE km_meta = VALUES(km_meta)");
        foreach ($metas as $mes => $valor_raw) {
            $valor = (float)str_replace(['.', ','], ['', '.'], $valor_raw);
            $mes = (int)$mes;
            $stmt->bind_param('iid', $ano_selecionado, $mes, $valor);
            $stmt->execute();
        }
        $stmt->close();
        $msg = "Metas de $ano_selecionado salvas!"; $tipo_msg = "success";
    }

    // 2. ADICIONAR FERIADO
    elseif ($acao === 'add_feriado') {
        $data = $_POST['data_feriado'];
        $desc = trim($_POST['descricao']);
        $tipo = $_POST['tipo_operacao'];
        
        // Graças à UNIQUE KEY na data_feriado, isso atualiza se já existir
        $stmt = $conexao->prepare("INSERT INTO feriados (data_feriado, descricao, tipo_operacao) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE descricao=VALUES(descricao), tipo_operacao=VALUES(tipo_operacao)");
        $stmt->bind_param('sss', $data, $desc, $tipo);
        if ($stmt->execute()) {
            $msg = "Feriado salvo com sucesso!"; $tipo_msg = "success";
        } else {
            $msg = "Erro ao salvar feriado: " . $stmt->error; $tipo_msg = "error";
        }
        $stmt->close();
    }

    // 3. EXCLUIR FERIADO
    elseif ($acao === 'del_feriado') {
        $id = (int)$_POST['id_feriado'];
        $conexao->query("DELETE FROM feriados WHERE id = $id");
        $msg = "Feriado removido."; $tipo_msg = "success";
    }

    // 4. CARREGAR PADRÃO LONDRINA
    elseif ($acao === 'load_padrao') {
        // Exemplo: Feriados de 2025/2026 para Londrina
        $padrao = [
            ['2025-01-01', 'Confraternização Universal', 'domingos'],
            ['2025-02-28', 'Carnaval (Facultativo)', 'sabados'], 
            ['2025-03-04', 'Carnaval', 'domingos'], 
            ['2025-04-18', 'Sexta-feira Santa', 'domingos'],
            ['2025-04-21', 'Tiradentes', 'domingos'],
            ['2025-05-01', 'Dia do Trabalho', 'domingos'],
            ['2025-06-19', 'Corpus Christi', 'domingos'], 
            ['2025-06-27', 'Sagrado Coração de Jesus (Padroeiro)', 'domingos'], // Londrina
            ['2025-09-07', 'Independência do Brasil', 'domingos'],
            ['2025-10-12', 'Nossa Sra. Aparecida', 'domingos'],
            ['2025-11-02', 'Finados', 'domingos'],
            ['2025-11-15', 'Proclamação da República', 'domingos'],
            ['2025-12-10', 'Aniversário de Londrina', 'domingos'],
            ['2025-12-25', 'Natal', 'domingos'],
            // 2026 (Parcial)
            ['2026-01-01', 'Confraternização Universal', 'domingos'],
            ['2026-12-10', 'Aniversário de Londrina', 'domingos'],
        ];
        
        $stmt = $conexao->prepare("INSERT IGNORE INTO feriados (data_feriado, descricao, tipo_operacao) VALUES (?, ?, ?)");
        foreach($padrao as $f) {
            $stmt->bind_param('sss', $f[0], $f[1], $f[2]);
            $stmt->execute();
        }
        $stmt->close();
        $msg = "Feriados padrão de Londrina carregados."; $tipo_msg = "success";
    }
}

// --- DADOS PARA EXIBIÇÃO ---
$ano_filtro_meta = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
$anos_disponiveis = [date('Y'), date('Y') + 1, date('Y') + 2];

// Metas
$metas_db = [];
$res = $conexao->query("SELECT mes, km_meta FROM metas_planilha_tarifaria WHERE ano = $ano_filtro_meta");
if ($res) while ($r = $res->fetch_assoc()) $metas_db[$r['mes']] = $r['km_meta'];

// Feriados (Lista Completa)
$feriados_db = [];
$res_f = $conexao->query("SELECT * FROM feriados ORDER BY data_feriado DESC");
if ($res_f) $feriados_db = $res_f->fetch_all(MYSQLI_ASSOC);

$meses_nome = [1=>'Janeiro', 2=>'Fevereiro', 3=>'Março', 4=>'Abril', 5=>'Maio', 6=>'Junho', 7=>'Julho', 8=>'Agosto', 9=>'Setembro', 10=>'Outubro', 11=>'Novembro', 12=>'Dezembro'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações de Projeção</title>
    <script src="tailwindcss-3.4.17.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
        .tab-btn { cursor: pointer; padding: 10px 20px; font-weight: 600; border-bottom: 2px solid transparent; color: #64748b; }
        .tab-btn.active { border-color: #2563eb; color: #2563eb; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body class="p-6">
    <div class="max-w-5xl mx-auto">
        <header class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Configurações de Projeção</h1>
                <p class="text-sm text-slate-500">Defina metas e calendário operacional.</p>
            </div>
            <a href="relatorio_projecao_km.php" class="text-blue-600 hover:underline font-semibold">← Voltar</a>
        </header>

        <?php if ($msg): ?>
            <div class="mb-4 p-4 rounded <?= $tipo_msg === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-sm">
            <div class="flex border-b border-slate-200 px-4">
                <div class="tab-btn active" id="btn-metas" onclick="switchTab('metas')">Metas (Planilha)</div>
                <div class="tab-btn" id="btn-feriados" onclick="switchTab('feriados')">Feriados & Exceções</div>
            </div>

            <!-- TAB 1: METAS -->
            <div id="tab-metas" class="tab-content active p-6">
                <form method="GET" class="mb-6 flex items-center gap-4">
                    <label class="font-bold text-slate-700">Ano:</label>
                    <select name="ano" onchange="this.form.submit()" class="border rounded p-2 font-semibold">
                        <?php foreach ($anos_disponiveis as $a): ?>
                            <option value="<?= $a ?>" <?= $a == $ano_filtro_meta ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <form method="POST">
                    <input type="hidden" name="acao" value="salvar_metas">
                    <input type="hidden" name="ano" value="<?= $ano_filtro_meta ?>">
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <div class="bg-slate-50 p-3 rounded border">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1"><?= $meses_nome[$m] ?></label>
                            <input type="text" name="meta[<?= $m ?>]" 
                                   value="<?= number_format($metas_db[$m] ?? 0, 2, ',', '.') ?>" 
                                   class="w-full border rounded p-2 text-right mask-decimal" placeholder="0,00">
                        </div>
                        <?php endfor; ?>
                    </div>
                    <div class="mt-6 text-right">
                        <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-6 rounded hover:bg-blue-700">Salvar Metas</button>
                    </div>
                </form>
            </div>

            <!-- TAB 2: FERIADOS -->
            <div id="tab-feriados" class="tab-content p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Form Cadastro -->
                    <div class="lg:col-span-1 bg-slate-50 p-4 rounded border h-fit">
                        <h3 class="font-bold text-slate-700 mb-4">Adicionar Feriado / Exceção</h3>
                        <form method="POST">
                            <input type="hidden" name="acao" value="add_feriado">
                            <div class="mb-3">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Data</label>
                                <input type="date" name="data_feriado" required class="w-full border rounded p-2">
                            </div>
                            <div class="mb-3">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Descrição</label>
                                <input type="text" name="descricao" placeholder="Ex: Natal" required class="w-full border rounded p-2">
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Tipo de Operação</label>
                                <select name="tipo_operacao" class="w-full border rounded p-2">
                                    <option value="domingos">Operar como Domingo</option>
                                    <option value="sabados">Operar como Sábado</option>
                                    <option value="uteis">Operar como Dia Útil</option>
                                </select>
                            </div>
                            <button type="submit" class="w-full bg-green-600 text-white font-bold py-2 rounded hover:bg-green-700">Salvar</button>
                        </form>
                        
                        <hr class="my-6 border-slate-200">
                        
                        <form method="POST" onsubmit="return confirm('Isso adicionará feriados padrão de Londrina (2025/2026). Continuar?');">
                            <input type="hidden" name="acao" value="load_padrao">
                            <button type="submit" class="w-full bg-slate-200 text-slate-700 font-semibold py-2 rounded hover:bg-slate-300 text-sm">
                                Carregar Padrão Londrina
                            </button>
                        </form>
                    </div>

                    <!-- Lista -->
                    <div class="lg:col-span-2">
                        <h3 class="font-bold text-slate-700 mb-4">Feriados Cadastrados</h3>
                        <div class="overflow-auto max-h-[500px] border rounded">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-slate-100 uppercase text-xs text-slate-500">
                                    <tr>
                                        <th class="p-3">Data</th>
                                        <th class="p-3">Descrição</th>
                                        <th class="p-3">Operação</th>
                                        <th class="p-3 text-center">Ação</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if(empty($feriados_db)): ?>
                                        <tr><td colspan="4" class="p-4 text-center text-slate-500">Nenhum feriado cadastrado.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($feriados_db as $f): 
                                            $classe_tipo = '';
                                            if ($f['tipo_operacao'] == 'domingos') $classe_tipo = 'bg-red-100 text-red-700';
                                            elseif ($f['tipo_operacao'] == 'sabados') $classe_tipo = 'bg-yellow-100 text-yellow-700';
                                            else $classe_tipo = 'bg-green-100 text-green-700';
                                        ?>
                                        <tr>
                                            <td class="p-3 font-mono"><?= date('d/m/Y', strtotime($f['data_feriado'])) ?></td>
                                            <td class="p-3 font-medium"><?= htmlspecialchars($f['descricao']) ?></td>
                                            <td class="p-3">
                                                <span class="px-2 py-1 rounded text-xs font-bold uppercase <?= $classe_tipo ?>">
                                                    <?= $f['tipo_operacao'] ?>
                                                </span>
                                            </td>
                                            <td class="p-3 text-center">
                                                <form method="POST" onsubmit="return confirm('Excluir este feriado?');">
                                                    <input type="hidden" name="acao" value="del_feriado">
                                                    <input type="hidden" name="id_feriado" value="<?= $f['id'] ?>">
                                                    <button type="submit" class="text-red-500 hover:text-red-700 font-bold px-2">×</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById('tab-' + tabId).classList.add('active');
            document.getElementById('btn-' + tabId).classList.add('active');
        }

        document.querySelectorAll('.mask-decimal').forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                value = (value / 100).toFixed(2) + '';
                value = value.replace('.', ',');
                value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
                e.target.value = value;
            });
        });
    </script>
</body>
</html>
<?php $conexao->close(); ?>