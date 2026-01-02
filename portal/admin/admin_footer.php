<?php
// admin/admin_footer.php
// ATUALIZADO para garantir que o modal de zoom esteja presente
?>
            </main> </div> </div> <div class="modal fade" id="imageZoomModal" tabindex="-1" role="dialog" aria-labelledby="imageZoomModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="imageZoomModalLabel">Imagem Ampliada</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body text-center">
            <img src="" id="zoomedImage" class="img-fluid" alt="Imagem Ampliada">
          </div>
        </div>
      </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/pt-BR.js"></script> 

    <?php
    // Placeholder para injetar scripts JavaScript específicos da página que está incluindo este footer.
    // A página deve definir a variável $page_specific_js com o conteúdo do script.
    if (isset($page_specific_js) && !empty($page_specific_js)) {
        echo $page_specific_js; // O script já deve estar entre tags <script></script>
    }
    ?>

    <?php
    // Se você tiver um arquivo JavaScript global para todas as páginas do admin (admin_script.js),
    // inclua-o DEPOIS dos scripts específicos da página ou aqui no final.
    // O script de zoom pode estar aqui se for global para o admin.
    if (file_exists('admin_script.js')) {
        echo '<script src="admin_script.js?v=' . (file_exists('admin_script.js') ? filemtime('admin_script.js') : time()) . '"></script>';
    }
    ?>
</body>
</html>