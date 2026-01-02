document.addEventListener("DOMContentLoaded", function () {
    // Referências aos elementos
    const formBuscaLinha = document.getElementById("form-busca-linha");
    const formBuscaWorkID = document.getElementById("form-busca-workid");
    
    const containerResultados = document.getElementById("resultados-busca");
    const tituloResultado = document.getElementById("titulo-resultado-linha");
    const containerBotoes = document.getElementById("container-botoes-data");
    const containerTabela = document.getElementById("conteudo-tabela-horaria");
    const feedbackBusca = document.getElementById("feedback-busca");

    // 1. Listener para busca por LINHA
    if (formBuscaLinha) {
        formBuscaLinha.addEventListener("submit", function (e) {
            e.preventDefault();
            const termo = document.getElementById("numeroLinha").value.trim();
            if (termo) iniciarBusca(termo, 'Linha');
        });
    }

    // 2. Listener para busca por WORKID
    if (formBuscaWorkID) {
        formBuscaWorkID.addEventListener("submit", function (e) {
            e.preventDefault();
            const termo = document.getElementById("workid").value.trim();
            if (termo) iniciarBusca(termo, 'WorkID');
        });
    }

    // Função Principal de Busca
    function iniciarBusca(termo, tipo) {
        containerResultados.style.display = "block";
        tituloResultado.textContent = `${tipo}: ${termo}`;
        containerBotoes.innerHTML = "";
        containerTabela.innerHTML = "";
        feedbackBusca.style.display = "block";
        feedbackBusca.innerHTML = '<div class="spinner-border text-primary"></div> Verificando agenda...';

        containerResultados.scrollIntoView({ behavior: 'smooth' });

        fetch(`buscar_horario.php?acao=buscar_dias&termo=${termo}`)
            .then(r => r.json())
            .then(resp => {
                if (resp.erro) throw new Error(resp.msg);
                
                if (!resp.botoes || resp.botoes.length === 0) {
                    feedbackBusca.innerHTML = `<div class="alert alert-warning">Nenhuma escala encontrada para ${termo} nos próximos 7 dias.</div>`;
                    return;
                }

                feedbackBusca.style.display = "none";

                resp.botoes.forEach((btnInfo, index) => {
                    const btn = document.createElement("button");
                    btn.className = `btn btn-outline-primary btn-data-dia ${index === 0 ? 'active' : ''}`;
                    btn.textContent = btnInfo.label;
                    
                    btn.onclick = function() {
                        document.querySelectorAll('.btn-data-dia').forEach(b => b.classList.remove('active'));
                        this.classList.add('active');
                        carregarDetalhes(termo, btnInfo.data);
                    };

                    containerBotoes.appendChild(btn);
                });

                if (resp.botoes.length > 0) {
                    carregarDetalhes(termo, resp.botoes[0].data);
                }
            })
            .catch(err => {
                feedbackBusca.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
                console.error(err);
            });
    }

    // Função para carregar as Abas
    function carregarDetalhes(termo, data) {
        containerTabela.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-secondary"></div> Carregando Diários de Bordo...</div>';

        fetch(`buscar_horario.php?acao=buscar_detalhes&termo=${termo}&data=${data}`)
            .then(r => r.json())
            .then(resp => {
                if (resp.erro) throw new Error(resp.msg);
                
                let navTabs = '<ul class="nav nav-tabs" id="tabsWorkID" role="tablist">';
                let tabContent = '<div class="tab-content" id="tabsWorkIDContent">';

                resp.abas.forEach((aba, index) => {
                    const isActive = index === 0 ? 'active' : '';
                    const isShow = index === 0 ? 'show active' : '';

                    navTabs += `
                        <li class="nav-item">
                            <a class="nav-link ${isActive}" id="tab-${aba.id}" data-toggle="tab" href="#content-${aba.id}" role="tab" aria-controls="content-${aba.id}" aria-selected="${index === 0}">
                                <strong>${aba.titulo}</strong>
                            </a>
                        </li>
                    `;

                    tabContent += `
                        <div class="tab-pane fade ${isShow} p-3 border border-top-0 bg-white" id="content-${aba.id}" role="tabpanel" aria-labelledby="tab-${aba.id}">
                            ${aba.conteudo}
                        </div>
                    `;
                });

                navTabs += '</ul>';
                tabContent += '</div>';

                containerTabela.innerHTML = navTabs + tabContent;
            })
            .catch(err => {
                containerTabela.innerHTML = `<div class="alert alert-danger">Erro: ${err.message}</div>`;
            });
    }

    /* ==========================================
       FUNÇÃO DE ZOOM DE IMAGEM (AUTO-CORRETIVA)
       ========================================== */
    window.abrirZoom = function(src, title) {
        // Tenta achar o modal existente
        let modalElement = document.getElementById('imageZoomModal');
        let modalImg = document.getElementById('zoomModalImg'); // O ID correto é zoomModalImg (do seu footer)
        let modalTitle = document.getElementById('zoomModalTitle');

        // Se o footer.php falhou ou ID estiver errado, cria o modal na hora
        if (!modalElement || !modalImg) {
            console.log("Modal não encontrado no DOM. Criando dinamicamente...");
            
            // Remove qualquer lixo anterior se existir parcialmente
            if(modalElement) modalElement.remove();

            const modalHTML = `
                <div class="modal fade" id="imageZoomModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1055;">
                    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="zoomModalTitle">Visualização</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body text-center bg-light p-0">
                                <img id="zoomModalImg" src="" class="img-fluid" style="max-height: 85vh; width: auto;">
                            </div>
                        </div>
                    </div>
                </div>`;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            // Recaptura as referências criadas
            modalImg = document.getElementById('zoomModalImg');
            modalTitle = document.getElementById('zoomModalTitle');
        } else {
            // Se já existia (do footer), usa o ID zoomModalImg que vi no seu código
            // O seu footer usa id="zoomedImage" mas o script esperava "zoomModalImg"
            // Vamos corrigir a referência
            if(!modalImg) modalImg = document.getElementById('zoomedImage'); 
        }

        // Aplica os dados
        if (modalImg) modalImg.src = src;
        if (modalTitle) modalTitle.textContent = title || 'Imagem Ampliada';
        
        // Abre o modal usando jQuery
        $('#imageZoomModal').modal('show');
    };
});