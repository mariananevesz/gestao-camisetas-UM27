<?php
require_once __DIR__ . '/../config/bootstrap.php';
$tituloPagina = 'Recuperar senha';
$paginaAtiva = '';
require __DIR__ . '/partials/header.php';
?>
<div class="auth-wrap"><section class="card auth-card auth-card-larga">
  <span class="eyebrow">Recuperação de acesso</span><h2>Redefinir senha</h2>
  <p>Informe o e-mail e a data de nascimento cadastrados e escolha uma nova senha. Não é enviado e-mail de recuperação.</p>
  <?php require __DIR__ . '/partials/flash.php'; ?>
  <form action="<?= e(app_url('processamento/processamentoAuth.php')) ?>" method="post" class="form-stack">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="acao" value="recuperar">
    <div class="campo"><label for="email">E-mail</label><input id="email" name="email" type="email" autocomplete="email" maxlength="190" required></div>
    <div class="campo"><label for="data_nascimento">Data de nascimento</label><input id="data_nascimento" name="data_nascimento" type="date" required></div>
    <div class="campo"><label for="nova_senha">Nova senha</label><input id="nova_senha" name="nova_senha" type="password" minlength="8" maxlength="200" autocomplete="new-password" required></div>
    <div class="campo"><label for="confirmar_senha">Confirme a nova senha</label><input id="confirmar_senha" name="confirmar_senha" type="password" minlength="8" maxlength="200" autocomplete="new-password" required></div>
    <button class="btn btn-primary btn-grande" type="submit">Redefinir senha</button>
  </form>
  <div class="auth-links"><a href="<?= e(app_url('view/login.php')) ?>">Voltar para o login</a></div>
</section></div>
<?php require __DIR__ . '/partials/footer.php'; ?>
