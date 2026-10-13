<?php
require_once __DIR__ . '/../config/bootstrap.php';
app_require_login();
$usuario = app_current_user();
$tituloPagina = 'Minha conta';
$paginaAtiva = 'conta';
require __DIR__ . '/partials/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">Segurança</span><h2>Minha conta</h2><p>Altere sua senha quando quiser.</p></div></div>
<?php require __DIR__ . '/partials/flash.php'; ?>
<section class="card auth-card auth-card-larga">
  <dl class="dados-conta"><div><dt>Nome</dt><dd><?= e($usuario['nome_completo']) ?></dd></div><div><dt>E-mail</dt><dd><?= e($usuario['email']) ?></dd></div><div><dt>Perfil</dt><dd><?= e(ucfirst($usuario['perfil'])) ?></dd></div></dl>
  <form action="<?= e(app_url('processamento/processamentoAuth.php')) ?>" method="post" class="form-stack">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="acao" value="alterar_senha">
    <div class="campo"><label for="senha_atual">Senha atual</label><input id="senha_atual" name="senha_atual" type="password" autocomplete="current-password" required></div>
    <div class="campo"><label for="nova_senha">Nova senha (mínimo 8 caracteres)</label><input id="nova_senha" name="nova_senha" type="password" minlength="8" maxlength="200" autocomplete="new-password" required></div>
    <div class="campo"><label for="confirmar_senha">Confirme a nova senha</label><input id="confirmar_senha" name="confirmar_senha" type="password" minlength="8" maxlength="200" autocomplete="new-password" required></div>
    <button class="btn btn-primary" type="submit">Alterar senha</button>
  </form>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
