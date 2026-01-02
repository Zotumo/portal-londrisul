$(document).ready(function() {
    $('#destinatario_id_select2').select2({
        theme: 'bootstrap4', // Se você tiver o tema Bootstrap 4 para Select2, senão pode remover ou usar 'bootstrap'
        language: "pt-BR", // Usa a tradução incluída
        width: '100%',     // Faz o select ocupar a largura total do container
        allowClear: 'true',  // Permite limpar a seleção (X)
        ajax: {
            url: 'buscar_motoristas_ajax.php', // Endpoint PHP que buscará os motoristas
            dataType: 'json',
            delay: 250, // Milissegundos de espera após o usuário parar de digitar
            data: function (params) {
                return {
                    q: params.term, // Termo de busca
                    page: params.page || 1 // Para paginação dos resultados AJAX (se implementado no backend)
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results: data.items, // 'items' deve ser uma array de objetos {id: X, text: 'Nome (Matricula)'}
                    pagination: {
                        more: (params.page * 10) < data.total_count // Exemplo: 10 resultados por página AJAX
                    }
                };
            },
            cache: true // O navegador pode cachear as requisições AJAX
        },
        minimumInputLength: 2, // Começa a buscar após o usuário digitar N caracteres
        escapeMarkup: function (markup) { return markup; }, // Permite HTML nos resultados, se necessário (cuidado com XSS)
        templateResult: formatRepo, // Função para formatar a exibição dos resultados na lista dropdown
        templateSelection: formatRepoSelection // Função para formatar o item selecionado
    });

    function formatRepo (repo) {
        if (repo.loading) {
            return repo.text; // "Buscando..."
        }
        // repo.text já deve vir formatado do backend como "Nome (Matrícula)"
        // Se quiser adicionar mais detalhes, pode fazer aqui.
        var markup = "<div class='select2-result-repository clearfix'>" +
                     "<div class='select2-result-repository__title'>" + repo.text + "</div>";
        if (repo.matricula) { // Se o backend enviar 'matricula' separadamente
             // markup += "<div class='select2-result-repository__matricula'><small>Matrícula: " + repo.matricula + "</small></div>";
        }
        markup += "</div>";
        return markup;
    }

    function formatRepoSelection (repo) {
        return repo.text || repo.id; // Mostra o texto (Nome (Matrícula)) ou o ID se o texto não estiver disponível
    }

    // Lidar com a opção "TODOS OS MOTORISTAS"
    // Se o Select2 estiver cobrindo a opção "TODOS", precisamos de uma maneira de selecioná-la
    // ou tratá-la separadamente. Uma forma é não incluir "TODOS" nos resultados AJAX
    // e manter como uma opção fixa no HTML ou adicionar um checkbox "Enviar para todos".
    // Por enquanto, a opção "TODOS" está no HTML e o Select2 deve pegá-la.
    // Se você selecionar "TODOS", o valor será "TODOS".
    // Se você buscar e selecionar um motorista, o valor será o ID do motorista.

    // Lógica para manter a opção "TODOS OS MOTORISTAS" sempre visível ou facilmente selecionável
    // pode ser um pouco complexa com a busca AJAX padrão do Select2.
    // Uma alternativa é ter um checkbox separado: [ ] Enviar para todos os motoristas.
    // Se marcado, o select de motorista individual é desabilitado/ignorado.
    // Se não marcado, o select de motorista individual é usado e obrigatório.

    // Verificação para a opção "TODOS"
    // Se o valor inicial for "TODOS", o Select2 deve mostrá-lo.
    // Se o usuário buscar, a opção "TODOS" não virá do AJAX.
    // O Select2 permite adicionar opções "tags" ou opções que não vêm do AJAX.
    // Mas para o caso de "TODOS", manter como uma <option> fixa no HTML como fizemos
    // deve funcionar, desde que o usuário não comece a digitar (o que iniciaria a busca AJAX).
    // Se ele limpar a busca, a opção "TODOS" deve reaparecer.
});