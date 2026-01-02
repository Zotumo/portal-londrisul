<?php
date_default_timezone_set('America/Sao_Paulo');
$data_atual = date('Y-m-d');
$horario_atual = date('H:i:s');

// Arrays de opções para os formulários
$linhas = [94, 95, 98, 200, 201, 202, 203, 204, 205, 207, 208, 209, 210, 211, 213, 214, 215, 216, 217, 218, 219, 220, 221, 222, 224, 225, 226, 227, 228, 229, 231, 232, 250, 260, 270, 271, 275, 280, 285, 290, 295, 296, 408, 600, 601, 602, 603, 800, 801, 802, 804, 806, 905, 906, 907, 913];
$carros = ['Sem Carro', 5056, 5057, 5058, 5059, 5060, 5061, 5062, 5063, 5064, 5065, 5100, 5101, 5102, 5103, 5104, 5105, 5106, 5107, 5108, 5109, 5110, 5111, 5112, 5113, 5114, 5115, 5116, 5117, 5118, 5119, 5120, 5121, 5122, 5123, 5124, 5125, 5126, 5127, 5128, 5129, 5130, 5131, 5132, 5133, 5134, 5135, 5136, 5137, 5138, 5139, 5140, 5141, 5142, 5143, 5144, 5145, 5146, 5147, 5149, 5150, 5151, 5152, 5153, 5154, 5155, 5156, 5157, 5158, 5159, 5160, 5161, 5162, 5163, 5164, 5165, 5166, 5167, 5168, 5169, 5170, 5171, 5172, 5173, 5174, 5175, 5176, 5177, 5178, 5179, 5180, 5300, 5301, 5302, 5303, 5304, 5305, 5306, 5307, 5308, 5309, 5310, 5311, 5312, 5313, 5314, 5315, 5316, 7012, 7013, 7014, 7015, 7016, 7017, 7023, 7024, 7025, 7026, 7027, 7028, 7029, 7030, 7031, 7032, 7033, 7034, 7035, 7036, 7037, 8000, 8001, 8002, 8003, 8004, 8006];
$ocorrencias = ['Acidente', 'Adiantamento', 'Atraso', 'Ausência/Falta', 'Mecânica', 'Não Cumpriu Parte do Itinerário', 'Não Logou', 'Não Saiu da Garagem', 'Não Utilizou Botton', 'Permanência', 'Saiu Atrasado da Garagem', 'Supressão', 'Supressão Bairro', 'Supressão/Atraso', 'Supressão/Mecânica', 'Supressão/Permanência', 'Supressão/Rendição', 'Supressão/Sem Carro Garagem', 'Borracharia', 'Troca'];
$incidentes = ['Aquecimento', 'Ar condicionado', 'Baldeação', 'Borracharia', 'Campainha', 'Carro Pedido', 'Carroceria', 'Catraca/Validador', 'Colisão', 'Combustível', 'Itinerário', 'Lavagem', 'Perda de Potência', 'Plataforma Cadeirante', 'Problema Elétrico', 'Problema Mecanico', 'Rendição', 'Socorro', 'Retorno na Linha'];
$terminais = ['Acapulco', 'Central', 'Gavetti', 'Irerê', 'Oeste', 'Shopping', 'Vivi'];
$monitores = ['André', 'Nicole', 'Borsato', 'Everton', 'Maison', 'Matos', 'Mayara', 'Aprendiz'];
$fiscais = ['Soltura', 'Helder', 'José Cardoso', 'José Roberto', 'Junior', 'Laércio', 'Maicon', 'Mateus', 'Matos', 'Odair', 'Reginaldo', 'Reserva', 'Ronaldo', 'Soltura'];

// --- Bloco de Datalists para reutilização ---
function gerar_datalist($id, $array_opcoes) {
    echo "<datalist id=\"$id\">";
    foreach ($array_opcoes as $opcao) {
        echo "<option value=\"$opcao\"></option>";
    }
    echo "</datalist>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Ocorrências</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .modal { display: none; }
        input:required { border-color: #f87171; } /* Destaca campos obrigatórios */
        #filtros-content { display: none; } /* Esconde os filtros inicialmente */
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <!-- Datalists reutilizáveis -->
    <?php gerar_datalist('lista-linhas', $linhas); ?>
    <?php gerar_datalist('lista-carros', $carros); ?>
    <?php gerar_datalist('lista-ocorrencias', $ocorrencias); ?>
    <?php gerar_datalist('lista-incidentes', $incidentes); ?>
    <?php gerar_datalist('lista-terminais', $terminais); ?>
    <?php gerar_datalist('lista-monitores', $monitores); ?>
    <?php gerar_datalist('lista-fiscais', $fiscais); ?>

    <main class="p-4 md:p-8">
        <header class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800">Registro de Ocorrências</h1>
            <p class="mt-2 text-lg text-gray-600">Formulário para inserção de novas ocorrências de monitoramento.</p>
        </header>
        
        <div id="mensagem-feedback" class="mb-4 max-w-8xl mx-auto"></div>

        <!-- Formulário de Inserção -->
        <div class="bg-white p-6 rounded-lg shadow-lg mb-8 max-w-8xl mx-auto">
            <h2 class="text-2xl font-semibold mb-6 border-b pb-4">Nova Ocorrência</h2>
            <form id="form-ocorrencia">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="space-y-4">
                        <div><label class="block text-sm font-medium text-gray-700">Data</label><input type="date" value="<?= $data_atual ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md bg-gray-100" readonly></div>
                        <div><label class="block text-sm font-medium text-gray-700">Horário</label><input type="time" value="<?= $horario_atual ?>" class="mt-1 block w-full p-2 border border-gray-300 rounded-md bg-gray-100" readonly></div>
                        <div><label for="workid" class="block text-sm font-medium text-gray-700">WorkID <span class="text-red-500">*</span></label><input type="text" id="workid" name="workid" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" maxlength="8" pattern="\d*" required></div>
                        <div><label for="motorista_atual" class="block text-sm font-medium text-gray-700">Motorista <span class="text-red-500">*</span></label><input type="text" id="motorista_atual" name="motorista_atual" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" maxlength="5" pattern="\d*" required></div>
                    </div>
                    <div class="space-y-4">
                        <div><label for="linha" class="block text-sm font-medium text-gray-700">Linha <span class="text-red-500">*</span></label><input type="text" id="linha" name="linha" list="lista-linhas" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" placeholder="Digite ou selecione" required></div>
                        <div><label for="carro_atual" class="block text-sm font-medium text-gray-700">Carro Atual</label><input type="text" id="carro_atual" name="carro_atual" list="lista-carros" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" placeholder="Digite ou selecione"></div>
                        <div><label for="ocorrencia" class="block text-sm font-medium text-gray-700">Ocorrência <span class="text-red-500">*</span></label><input type="text" id="ocorrencia" name="ocorrencia" list="lista-ocorrencias" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" placeholder="Digite ou selecione" required></div>
                        <div><label for="incidente" class="block text-sm font-medium text-gray-700">Incidente</label><input type="text" id="incidente" name="incidente" list="lista-incidentes" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" placeholder="Digite ou selecione"></div>
                    </div>
                    <div class="space-y-4">
                        <div><label for="horario_linha" class="block text-sm font-medium text-gray-700">Horário da Linha</label><input type="text" id="horario_linha" name="horario_linha" placeholder="HH:MM" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></div>
                        <div><label for="terminal" class="block text-sm font-medium text-gray-700">Terminal</label><input type="text" id="terminal" name="terminal" list="lista-terminais" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" placeholder="Digite ou selecione"></div>
                        <div><label for="carro_pos" class="block text-sm font-medium text-gray-700">Carro Pós</label><input type="text" id="carro_pos" name="carro_pos" list="lista-carros" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" placeholder="Digite ou selecione"></div>
                        <div class="pt-6"><div class="flex items-center"><input id="socorro" name="socorro" type="checkbox" class="h-5 w-5 text-blue-600 border-gray-300 rounded"><label for="socorro" class="ml-2 block text-sm text-gray-900">Necessitou de Socorro?</label></div></div>
                    </div>
                    <div class="space-y-4">
                        <div><label for="monitor" class="block text-sm font-medium text-gray-700">Monitor <span class="text-red-500">*</span></label><input type="text" id="monitor" name="monitor" list="lista-monitores" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" placeholder="Digite ou selecione" required></div>
                        <div><label for="fiscal" class="block text-sm font-medium text-gray-700">Fiscal <span class="text-red-500">*</span></label><input type="text" id="fiscal" name="fiscal" list="lista-fiscais" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" placeholder="Digite ou selecione" required></div>
                        <div class="md:col-span-2"><label for="observacao" class="block text-sm font-medium text-gray-700">Observação</label><textarea id="observacao" name="observacao" rows="3" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" maxlength="250"></textarea></div>
                    </div>
                </div>
                <div class="mt-6 text-right border-t pt-6">
                    <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-700 transition-colors duration-300">Salvar Ocorrência</button>
                </div>
            </form>
        </div>

        <!-- Tabela de Registros -->
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-8xl mx-auto w-full">
            <h2 class="text-2xl font-semibold mb-4">Ocorrências Registradas</h2>
            
            <!-- Cabeçalho do Accordion de Filtros -->
            <div id="filtros-toggle" class="flex justify-between items-center cursor-pointer p-4 bg-gray-50 rounded-md border hover:bg-gray-100 transition-colors">
                <h3 class="text-xl font-semibold text-gray-800">Filtros</h3>
                <svg id="filtro-chevron" class="w-6 h-6 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>

            <!-- Conteúdo do Accordion de Filtros (inicialmente oculto) -->
            <div id="filtros-content" class="space-y-4 mb-4 p-4 border-l border-r border-b rounded-b-md bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div><label for="data_inicio_filtro" class="block text-sm font-medium text-gray-700">De</label><input type="date" id="data_inicio_filtro" class="filtro-campo mt-1 block w-full p-2 border border-gray-300 rounded-md"></div>
                    <div><label for="data_fim_filtro" class="block text-sm font-medium text-gray-700">Até</label><input type="date" id="data_fim_filtro" class="filtro-campo mt-1 block w-full p-2 border border-gray-300 rounded-md"></div>
                    <div><label for="workid_filtro" class="block text-sm font-medium text-gray-700">WorkID</label><input type="text" id="workid_filtro" class="filtro-campo mt-1 block w-full p-2 border border-gray-300 rounded-md" maxlength="8" pattern="\d*"></div>
                    <div><label for="motorista_filtro" class="block text-sm font-medium text-gray-700">Motorista</label><input type="text" id="motorista_filtro" class="filtro-campo mt-1 block w-full p-2 border border-gray-300 rounded-md" maxlength="5" pattern="\d*"></div>
                    <div><label for="linha_filtro" class="block text-sm font-medium text-gray-700">Linha</label><input type="text" id="linha_filtro" list="lista-linhas" class="filtro-campo mt-1 block w-full p-2 border border-gray-300 rounded-md" placeholder="Digite ou selecione"></div>
                    <div><label for="carro_filtro" class="block text-sm font-medium text-gray-700">Carro Atual</label><input type="text" id="carro_filtro" list="lista-carros" class="filtro-campo mt-1 block w-full p-2 border border-gray-300 rounded-md" placeholder="Digite ou selecione"></div>
                    <div><label for="ocorrencia_filtro" class="block text-sm font-medium text-gray-700">Ocorrência</label><input type="text" id="ocorrencia_filtro" list="lista-ocorrencias" class="filtro-campo mt-1 block w-full p-2 border border-gray-300 rounded-md" placeholder="Digite ou selecione"></div>
                    <div><label for="incidente_filtro" class="block text-sm font-medium text-gray-700">Incidente</label><input type="text" id="incidente_filtro" list="lista-incidentes" class="filtro-campo mt-1 block w-full p-2 border border-gray-300 rounded-md" placeholder="Digite ou selecione"></div>
                    <div><label for="socorro_filtro" class="block text-sm font-medium text-gray-700">Socorro?</label><select id="socorro_filtro" class="filtro-campo w-full mt-1 p-2 border border-gray-300 rounded-md"><option value="">Todos</option><option value="1">Sim</option><option value="0">Não</option></select></div>
                    <div><label for="horario_linha_filtro" class="block text-sm font-medium text-gray-700">Horário da Linha</label><input type="text" id="horario_linha_filtro" placeholder="HH:MM" class="filtro-campo mt-1 block w-full p-2 border border-gray-300 rounded-md"></div>
                    <div><label for="terminal_filtro" class="block text-sm font-medium text-gray-700">Terminal</label><input type="text" id="terminal_filtro" list="lista-terminais" class="filtro-campo mt-1 block w-full p-2 border border-gray-300 rounded-md" placeholder="Digite ou selecione"></div>
                    <div><label for="carro_pos_filtro" class="block text-sm font-medium text-gray-700">Carro Pós</label><input type="text" id="carro_pos_filtro" list="lista-carros" class="filtro-campo mt-1 block w-full p-2 border border-gray-300 rounded-md" placeholder="Digite ou selecione"></div>
                    <div><label for="monitor_filtro" class="block text-sm font-medium text-gray-700">Monitor</label><input type="text" id="monitor_filtro" list="lista-monitores" class="filtro-campo mt-1 block w-full p-2 border border-gray-300 rounded-md" placeholder="Digite ou selecione"></div>
                </div>
                <div class="text-right pt-4 border-t mt-4">
                    <button id="limpar-filtros" class="bg-gray-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-gray-600">Limpar Filtros</button>
                </div>
            </div>

            <div class="overflow-x-auto mt-6">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                        <tr>
                            <th class="px-4 py-3">Data/Hora</th><th class="px-4 py-3">WorkID</th><th class="px-4 py-3">Mot.</th><th class="px-4 py-3">Linha</th><th class="px-4 py-3">Carro</th><th class="px-4 py-3">Ocorrência</th><th class="px-4 py-3">Incidente</th><th class="px-4 py-3">Socorro?</th><th class="px-4 py-3">Hor. Linha</th><th class="px-4 py-3">Terminal</th><th class="px-4 py-3">Carro Pós</th><th class="px-4 py-3">Monitor</th><th class="px-4 py-3">Observação</th><th class="px-4 py-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tabela-ocorrencias-corpo">
                        <!-- Linhas serão inseridas aqui via AJAX -->
                    </tbody>
                </table>
            </div>
            <div id="paginacao-controles" class="flex justify-center items-center mt-4">
                <!-- Botões serão inseridos aqui pelo JavaScript -->
            </div>
        </div>
    </main>

    <!-- Modal de Edição -->
    <div id="modal-editar" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-6xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-2xl font-semibold text-center mb-6 border-b pb-4">Editar Ocorrência</h3>
                <form id="form-editar-ocorrencia">
                    <input type="hidden" id="edit_id" name="edit_id">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 p-4">
                        <div class="space-y-4">
                            <div><label class="block text-sm font-medium text-gray-700">WorkID</label><input type="text" id="edit_workid" name="edit_workid" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" maxlength="8" pattern="\d*"></div>
                            <div><label class="block text-sm font-medium text-gray-700">Motorista</label><input type="text" id="edit_motorista_atual" name="edit_motorista_atual" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" maxlength="5" pattern="\d*"></div>
                        </div>
                        <div class="space-y-4">
                            <div><label class="block text-sm font-medium text-gray-700">Linha</label><input type="text" id="edit_linha" name="edit_linha" list="lista-linhas" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></div>
                            <div><label class="block text-sm font-medium text-gray-700">Carro Atual</label><input type="text" id="edit_carro_atual" name="edit_carro_atual" list="lista-carros" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></div>
                        </div>
                        <div class="space-y-4">
                            <div><label class="block text-sm font-medium text-gray-700">Ocorrência</label><input type="text" id="edit_ocorrencia" name="edit_ocorrencia" list="lista-ocorrencias" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></div>
                            <div><label class="block text-sm font-medium text-gray-700">Incidente</label><input type="text" id="edit_incidente" name="edit_incidente" list="lista-incidentes" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></div>
                        </div>
                        <div class="space-y-4">
                            <div><label class="block text-sm font-medium text-gray-700">Horário da Linha</label><input type="text" id="edit_horario_linha" name="edit_horario_linha" placeholder="HH:MM" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></div>
                            <div><label class="block text-sm font-medium text-gray-700">Terminal</label><input type="text" id="edit_terminal" name="edit_terminal" list="lista-terminais" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></div>
                        </div>
                        <div class="space-y-4">
                            <div><label class="block text-sm font-medium text-gray-700">Carro Pós</label><input type="text" id="edit_carro_pos" name="edit_carro_pos" list="lista-carros" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></div>
                            <div class="pt-6"><div class="flex items-center"><input id="edit_socorro" name="edit_socorro" type="checkbox" class="h-5 w-5 text-blue-600 border-gray-300 rounded"><label for="edit_socorro" class="ml-2 block text-sm text-gray-900">Socorro?</label></div></div>
                        </div>
                        <div class="space-y-4">
                            <div><label class="block text-sm font-medium text-gray-700">Monitor</label><input type="text" id="edit_monitor" name="edit_monitor" list="lista-monitores" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></div>
                            <div><label class="block text-sm font-medium text-gray-700">Fiscal</label><input type="text" id="edit_fiscal" name="edit_fiscal" list="lista-fiscais" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></div>
                        </div>
                        <div class="col-span-full"><label class="block text-sm font-medium text-gray-700">Observação</label><textarea id="edit_observacao" name="edit_observacao" rows="3" class="mt-1 block w-full p-2 border border-gray-300 rounded-md" maxlength="250"></textarea></div>
                    </div>
                    <div class="items-center px-4 py-3 mt-6 border-t">
                        <!-- CORREÇÃO: Alterado type="submit" para type="button" -->
                        <button id="salvar-edicao" type="button" class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md w-auto shadow-sm hover:bg-green-600">Salvar Alterações</button>
                        <button id="fechar-modal" type="button" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-auto ml-2 shadow-sm hover:bg-gray-600">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div id="modal-excluir" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-40 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Excluir Ocorrência</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">Tem certeza de que deseja excluir este registro? Esta ação não pode ser desfeita.</p>
                </div>
                <div class="items-center px-4 py-3">
                    <button id="confirmar-exclusao" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-auto shadow-sm hover:bg-red-600">Confirmar Exclusão</button>
                    <button id="cancelar-exclusao" class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-auto ml-2 shadow-sm hover:bg-gray-400">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Validação para campos numéricos
            $('#workid, #motorista_atual, #workid_filtro, #motorista_filtro, #edit_workid, #edit_motorista_atual').on('input', function() { this.value = this.value.replace(/[^0-9]/g, ''); });
            
            // Máscara para campos de horário
            $('#horario_linha, #horario_linha_filtro, #edit_horario_linha').on('input', function(e) {
                let input = $(this); let value = input.val().replace(/[^0-9]/g, '').substring(0, 4);
                if (value.length > 2) { let h = value.substring(0, 2); if (parseInt(h, 10) > 23) h = '23'; value = h + ':' + value.substring(2); }
                if (value.length > 3) { let m = value.substring(3); if (parseInt(m, 10) > 59) m = '59'; value = value.substring(0, 3) + m; }
                input.val(value);
            });

            function mostrarFeedback(mensagem, sucesso = true) {
                const cor = sucesso ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                $('#mensagem-feedback').html(`<div class="p-4 rounded-lg text-center ${cor}">${mensagem}</div>`).fadeIn();
                setTimeout(() => { $('#mensagem-feedback').fadeOut(); }, 4000);
            }

            let currentPage = 1;
            function carregarOcorrencias(page = 1) {
                currentPage = page;
                const filtros = { page: currentPage };
                $('.filtro-campo').each(function() { if ($(this).val()) { filtros[$(this).attr('id')] = $(this).val(); } });
                
                $('#tabela-ocorrencias-corpo').html('<tr><td colspan="14" class="text-center p-4">Carregando...</td></tr>');

                $.ajax({
                    url: 'buscar_ocorrencias.php', method: 'GET', data: filtros, dataType: 'json',
                    success: function(response) {
                        $('#tabela-ocorrencias-corpo').html(response.html);
                        $('#paginacao-controles').html(response.pagination);
                    },
                    error: function() { $('#tabela-ocorrencias-corpo').html('<tr><td colspan="14" class="text-center p-4 text-red-500">Erro ao carregar dados.</td></tr>'); }
                });
            }

            $('#form-ocorrencia').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'salvar_ocorrencia.php', method: 'POST', data: $(this).serialize(), dataType: 'json',
                    success: function(response) {
                        mostrarFeedback(response.message, response.success);
                        if (response.success) {
                            $('#form-ocorrencia')[0].reset();
                            carregarOcorrencias();
                        }
                    },
                    error: function() { mostrarFeedback('Erro de comunicação ao salvar.', false); }
                });
            });

            $(document).on('click', '.btn-editar', function() {
                const id = $(this).data('id');
                $.ajax({
                    url: 'buscar_ocorrencia_por_id.php', method: 'GET', data: { id: id }, dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            $('#edit_id').val(data.id);
                            $('#edit_workid').val(data.workid);
                            $('#edit_motorista_atual').val(data.motorista_atual);
                            $('#edit_linha').val(data.linha);
                            $('#edit_carro_atual').val(data.carro_atual);
                            $('#edit_ocorrencia').val(data.ocorrencia);
                            $('#edit_incidente').val(data.incidente);
                            $('#edit_horario_linha').val(data.horario_linha);
                            $('#edit_terminal').val(data.terminal);
                            $('#edit_carro_pos').val(data.carro_pos);
                            $('#edit_monitor').val(data.monitor);
                            $('#edit_fiscal').val(data.fiscal);
                            $('#edit_observacao').val(data.observacao);
                            $('#edit_socorro').prop('checked', data.socorro == 1);
                            $('#modal-editar').fadeIn();
                        } else {
                            mostrarFeedback(response.message, false);
                        }
                    },
                    error: function() { mostrarFeedback('Erro ao buscar dados para edição.', false); }
                });
            });

            // CORREÇÃO: Alterado de .on('submit') para .on('click') no botão específico
            $('#salvar-edicao').on('click', function() {
                $.ajax({
                    url: 'atualizar_ocorrencia.php',
                    method: 'POST',
                    data: $('#form-editar-ocorrencia').serialize(), // Serializa o formulário
                    dataType: 'json',
                    success: function(response) {
                        mostrarFeedback(response.message, response.success);
                        if (response.success) {
                            $('#modal-editar').fadeOut();
                            carregarOcorrencias(currentPage);
                        }
                    },
                    error: function() {
                        mostrarFeedback('Erro de comunicação ao atualizar.', false);
                    }
                });
            });

            $('#fechar-modal').on('click', function() { $('#modal-editar').fadeOut(); });

            let idParaExcluir = null;
            $(document).on('click', '.btn-excluir', function() {
                idParaExcluir = $(this).data('id');
                $('#modal-excluir').fadeIn();
            });

            $('#cancelar-exclusao').on('click', function() {
                $('#modal-excluir').fadeOut();
                idParaExcluir = null;
            });

            $('#confirmar-exclusao').on('click', function() {
                if (idParaExcluir) {
                    $.ajax({
                        url: 'excluir_ocorrencia.php', method: 'POST', data: { id: idParaExcluir }, dataType: 'json',
                        success: function(response) {
                            mostrarFeedback(response.message, response.success);
                            if (response.success) {
                                carregarOcorrencias(currentPage);
                            }
                        },
                        error: function() { mostrarFeedback('Erro de comunicação ao excluir.', false); }
                    });
                }
                $('#modal-excluir').fadeOut();
                idParaExcluir = null;
            });

            let debounceTimer;
            $('.filtro-campo').on('change keyup', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => carregarOcorrencias(1), 500);
            });

            $('#limpar-filtros').on('click', function() {
                $('.filtro-campo').val('').trigger('change');
            });
            
            // Listener para o toggle dos filtros (accordion)
            $('#filtros-toggle').on('click', function() {
                $('#filtros-content').slideToggle('fast'); // Anima o aparecimento/desaparecimento
                $('#filtro-chevron').toggleClass('rotate-180'); // Gira a seta
            });

            // Listener para os botões da paginação
            $('#paginacao-controles').on('click', '.btn-pagina', function() {
                const page = $(this).data('page');
                if (page && !$(this).is(':disabled')) {
                    carregarOcorrencias(page);
                }
            });

            carregarOcorrencias(1);
        });
    </script>
</body>
</html>
