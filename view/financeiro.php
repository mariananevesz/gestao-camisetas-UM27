<?php
require_once __DIR__ . '/../config/bootstrap.php';
app_require_permission('financeiro');
$usuario = app_current_user();
$pedidos = app_system()->listarPedidos($usuario, true);
if ($usuario['perfil'] === 'gestao' && !app_has_permission('pedidos_ver_todos'))
  $pedidos = array_values(array_filter($pedidos, function ($p) use ($usuario) {
    return (int) $p['id_usuario_criou'] === (int) $usuario['id'] || (int) $p['id_usuario_responsavel'] === (int) $usuario['id']; }));
$resumo = app_system()->obterResumoFinanceiro();
$tituloPagina = 'Financeiro';
$paginaAtiva = 'financeiro';
require __DIR__ . '/partials/header.php';
?>
<div class="page-heading">
  <div><span class="eyebrow">Recebimentos</span>
    <h2>Financeiro</h2>
    <p>Registre cada pagamento separadamente. O total recebido e o saldo são calculados automaticamente.</p>
  </div>
</div>
<?php require __DIR__ . '/partials/flash.php'; ?>
<div class="mod-grid">
  <section class="card metric-card"><span>Total
      vendido</span><strong><?= e(formatar_moeda($resumo['total_vendido'])) ?></strong><small>Pedidos enviados, sem
      rascunhos</small></section>
  <section class="card metric-card"><span>Total
      recebido</span><strong><?= e(formatar_moeda($resumo['total_recebido'])) ?></strong><small>Soma de todos os
      lançamentos</small></section>
  <section class="card metric-card"><span>Saldo em
      aberto</span><strong><?= e(formatar_moeda($resumo['saldo_aberto'])) ?></strong><small>Vendido menos
      recebido</small></section>
</div>
<?php if (!$pedidos): ?>
  <section class="card mod-empty">Nenhum pedido enviado para receber pagamentos.</section><?php else: ?>
  <div class="financeiro-order-list">
    <?php foreach ($pedidos as $pedido):
      $id = (int) $pedido['id'];
      $pagamentos = app_system()->listarPagamentos($id);
      $token = bin2hex(random_bytes(32)); ?>
      <article class="card mod-order-card financeiro-order-card" id="pedido-<?= $id ?>">
        <div class="mod-order-head">
          <div><span class="mod-muted"><?= e($pedido['numero_pedido']) ?> · <?= e($pedido['congregacao_nome']) ?></span>
            <h3><?= e($pedido['nome_comprador']) ?></h3>
            <p><?= e(rotulo_operacional($pedido['status_operacional'])) ?> ·
              <?= e(rotulo_entrega($pedido['status_entrega'])) ?></p>
          </div><span
            class="status-pill <?= $pedido['status_pagamento'] === 'pago' ? 'ok' : ($pedido['status_pagamento'] === 'parcial' ? 'info' : 'warn') ?>"><?= e(rotulo_pagamento($pedido['status_pagamento'])) ?></span>
        </div>
        <div class="mod-order-data">
          <div><span>Valor do pedido</span><strong><?= e(formatar_moeda($pedido['valor_total'])) ?></strong></div>
          <div><span>Já recebido</span><strong><?= e(formatar_moeda($pedido['total_recebido'])) ?></strong></div>
          <div><span>Saldo restante</span><strong><?= e(formatar_moeda($pedido['saldo_devedor'])) ?></strong></div>
          <div><span>Pagamentos lançados</span><strong><?= count($pagamentos) ?></strong></div>
        </div>
        <?php if ($pagamentos): ?>
          <details>
            <summary>Ver histórico de pagamentos</summary>
            <div class="mod-table-wrap">
              <table class="mod-table">
                <thead>
                  <tr>
                    <th>Data</th>
                    <th>Valor</th>
                    <th>Forma</th>
                    <th>Registrado por</th>
                    <th>Observação</th>
                  </tr>
                </thead>
                <tbody><?php foreach ($pagamentos as $pg): ?>
                    <tr>
                      <td><?= e(formatar_data_hora($pg['data_pagamento'])) ?></td>
                      <td><?= e(formatar_moeda($pg['valor'])) ?></td>
                      <td><?= e($pg['metodo'] ?: 'Não informado') ?></td>
                      <td><?= e($pg['registrado_por_nome'] ?: 'Usuário removido') ?></td>
                      <td><?= e($pg['observacao'] ?: '—') ?></td>
                    </tr><?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </details><?php endif; ?>
        <?php if ($pedido['saldo_devedor'] > 0): ?>
          <form action="<?= e(app_url('processamento/processamentoFinanceiro.php')) ?>" method="post"
            class="form-stack mod-section" style="margin-top:18px"><input type="hidden" name="csrf_token"
              value="<?= e(csrf_token()) ?>"><input type="hidden" name="acao" value="registrar"><input type="hidden"
              name="pedido_id" value="<?= $id ?>"><input type="hidden" name="token_operacao" value="<?= e($token) ?>">
            <div class="form-grid-mod">
              <div class="campo-mod"><label for="valor-<?= $id ?>">Valor recebido (máximo
                  <?= e(number_format((float) $pedido['saldo_devedor'], 2, '.', '')) ?>)</label><input id="valor-<?= $id ?>"
                  name="valor" type="number" min="0.01"
                  max="<?= e(number_format((float) $pedido['saldo_devedor'], 2, '.', '')) ?>" step="0.01" required></div>
              <div class="campo-mod"><label for="metodo-<?= $id ?>">Forma de pagamento</label><select id="metodo-<?= $id ?>"
                  name="metodo">
                  <option value="">Não informado</option>
                  <option value="pix">PIX</option>
                  <option value="cartao">Cartão</option>
                  <option value="dinheiro">Dinheiro</option>
                  <option value="outro">Outro</option>
                </select></div>
              <div class="campo-mod" style="grid-column:1/-1"><label for="obs-<?= $id ?>">Observação (opcional)</label><input
                  id="obs-<?= $id ?>" name="observacao" maxlength="255" type="text" placeholder="Ex.: entrada, parcela 2...">
              </div>
            </div>
            <div class="mod-actions"><button class="btn btn-primary" type="submit">Registrar pagamento</button></div>
          </form><?php else: ?>
          <p class="mod-note">Pedido totalmente pago. O histórico permanece disponível para consulta.</p><?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div><?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>