<?php
require_once __DIR__ . '/../config/bootstrap.php';
if (app_current_user())
  app_redirect('view/gestaoPedidos.php');
$tituloPagina = 'Entrar no sistema';
$paginaAtiva = '';
require __DIR__ . '/partials/header.php';
?>
<div class="auth-wrap">
  <section class="card auth-card">
    <span class="eyebrow">UMADEPRESP 2027</span>
    <h2>Entrar no sistema</h2>
    <p>Use seu e-mail e senha para acessar os pedidos.</p>
    <?php require __DIR__ . '/partials/flash.php'; ?>
    <form action="<?= e(app_url('processamento/processamentoAuth.php')) ?>" method="post" class="form-stack">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="acao" value="login">
      <div class="campo"><label for="email">E-mail</label><input id="email" name="email" type="email" maxlength="190"
          autocomplete="username" required></div>
      <div class="campo"><label for="senha">Senha</label><input id="senha" name="senha" type="password"
          autocomplete="current-password" required></div>
      <button class="btn btn-primary btn-grande" type="submit">Entrar</button>
    </form>
    <div class="auth-links"><a href="<?= e(app_url('view/precadastro.php')) ?>">Sou líder e quero me cadastrar</a><a
        href="<?= e(app_url('view/recuperarSenha.php')) ?>">Esqueci minha senha</a></div>
  </section>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>