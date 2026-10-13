<?php
require_once __DIR__ . '/../config/bootstrap.php';
if (app_current_user()) app_redirect('view/gestaoPedidos.php');
$congregacoes = app_system()->listarCongregacoes(false);
$contagemNomes = array_count_values(array_map(function ($c) { return $c['nome']; }, $congregacoes));
$tituloPagina = 'Pré-cadastro de líder';
$paginaAtiva = '';
require __DIR__ . '/partials/header.php';
?>
<div class="auth-wrap">
  <section class="card auth-card auth-card-larga">
    <span class="eyebrow">Acesso de líderes</span><h2>Pré-cadastro</h2>
    <p>Após o envio, aguarde a aprovação da administradora Master. Quando seu cadastro for aprovado, seus dados de acesso serão enviados para o WhatsApp informado abaixo. Confira se o número está correto e tem DDD.</p>
    <?php require __DIR__ . '/partials/flash.php'; ?>
    <form action="<?= e(app_url('processamento/processamentoAuth.php')) ?>" method="post" class="form-stack">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="acao" value="precadastro">
      <div class="campo"><label for="nome_completo">Nome completo</label><input id="nome_completo" name="nome_completo" type="text" maxlength="180" autocomplete="name" required></div>
      <div class="campo"><label for="email">E-mail (será seu login)</label><input id="email" name="email" type="email" maxlength="190" autocomplete="email" required></div>
      <div class="campo"><label for="telefone">Telefone / WhatsApp com DDD</label><input id="telefone" name="telefone" type="tel" inputmode="tel" autocomplete="tel" placeholder="(11) 99999-9999" maxlength="25" required><small>Use um número com WhatsApp, incluindo o DDD.</small></div>
      <div class="campo"><label for="data_nascimento">Data de nascimento</label><input id="data_nascimento" name="data_nascimento" type="date" max="<?= e(date('Y-m-d')) ?>" autocomplete="bday" required></div>
      <div class="campo combobox" data-combobox><label for="congregacao_busca">Congregação</label><div class="combobox-input-wrap"><input id="congregacao_busca" type="search" placeholder="Digite para pesquisar sua congregação" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="lista-congregacoes" aria-autocomplete="list" aria-haspopup="listbox" spellcheck="false" required></div><input type="hidden" id="congregacao_id" name="congregacao_id" value=""><div id="lista-congregacoes" class="combobox-lista" role="listbox" hidden><?php foreach ($congregacoes as $c): ?><button type="button" role="option" data-option-id="<?= (int) $c['id'] ?>" data-option-label="<?= e($c['nome'] . ((($contagemNomes[$c['nome']] ?? 0) > 1 && !empty($c['setor'])) ? ' — Setor ' . $c['setor'] : '')) ?>"><?= e($c['nome'] . ((($contagemNomes[$c['nome']] ?? 0) > 1 && !empty($c['setor'])) ? ' — Setor ' . $c['setor'] : '')) ?></button><?php endforeach; ?><p class="combobox-vazio" data-combobox-vazio hidden>Nenhuma congregação encontrada.</p></div></div>
      <button class="btn btn-primary btn-grande" type="submit">Enviar pré-cadastro</button>
    </form>
    <div class="auth-links"><a href="<?= e(app_url('view/login.php')) ?>">Voltar para o login</a></div>
  </section>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
