<?php
// footer.php (IDs corrigidos para o Zoom)
?>
    <div class="modal fade" id="loginModal" tabindex="-1" role="dialog" aria-labelledby="loginModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form action="processa_login.php" method="POST">
            <div class="modal-header" style="background-color: var(--cmtu-azul); color: white;">
              <h5 class="modal-title" id="loginModalLabel"><i class="fas fa-sign-in-alt"></i> Login do Motorista</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div id="login-error-message" class="alert alert-danger" style="display: none;" role="alert">
                <?php
                    if (!empty($login_error_message)) {
                        echo htmlspecialchars($login_error_message);
                    }
                ?>
              </div>
              <div class="form-group"> <label for="matriculaInput"><i class="fas fa-id-card"></i> Matrícula</label> <input type="text" class="form-control" id="matriculaInput" name="matricula" placeholder="Digite sua matrícula" required> </div>
              <div class="form-group"> <label for="senhaInput"><i class="fas fa-lock"></i> Senha</label> <input type="password" class="form-control" id="senhaInput" name="senha" placeholder="Digite sua senha" required> </div>
            </div>
            <div class="modal-footer"> <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button> <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Entrar</button> </div>
          </form>
        </div>
      </div>
    </div>

    <div class="modal fade" id="imageZoomModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1055;">
      <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="zoomModalTitle">Visualização</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
          </div>
          <div class="modal-body text-center bg-light p-0">
            <img src="" id="zoomModalImg" class="img-fluid" style="max-height: 85vh; width: auto;" alt="Imagem Ampliada">
          </div>
        </div>
      </div>
    </div>

    <footer class="bg-dark text-white text-center">
        <p>&copy; <?php echo date("Y"); ?> Portal do Motorista - Londrisul<br>
        Desenvolvido por Pedro Almeida</p>
    </footer>

    <script src="../portal/jquery-3.7.1.min.js"></script>
    <script src="../portal/popper.min.js"></script>
    <script src="../portal/bootstrap.min.js"></script> 
    
    <script src="script.js?v=4.0"></script>

    <script>
        // Código para exibir erro de login vindo da sessão PHP
         $(document).ready(function() {
            <?php if (!empty($login_error_message)): ?>
              $('#login-error-message').text("<?php echo addslashes($login_error_message); ?>").show();
              try {
                 $('#loginModal').modal('show');
              } catch(e) {
                 console.error("Erro modal login", e);
                 $('#login-error-message').show();
              }
            <?php endif; ?>

            $('#loginModal').on('hidden.bs.modal', function (e) {
              $('#login-error-message').hide().text('');
            });
         });
    </script>

</body>
</html>