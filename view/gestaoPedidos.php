<?php
require_once __DIR__ . '/../config/bootstrap.php';
app_require_login();
$usuario = app_current_user();
$pedidos = app_system()->listarPedidos($usuario);
$ehGestao = app_is_management();
if ($usuario['perfil'] === 'gestao' && !app_has_permission('pedidos_ver_todos')) {
  $pedidos = array_values(array_filter($pedidos, function ($p) use ($usuario) {
    return (int) $p['id_usuario_criou'] === (int) $usuario['id'] || (int) $p['id_usuario_responsavel'] === (int) $usuario['id']; }));
}
$tituloPagina = $ehGestao ? 'Gestão de Pedidos' : 'Meus Pedidos';
$paginaAtiva = 'gestao';
require __DIR__ . '/partials/header.php';
function classeStatusOperacional($status)
{
  if ($status === 'liberado_producao' || $status === 'lote_gerado')
    return 'ok';
  if ($status === 'rascunho')
    return 'warn';
  if ($status === 'enviado' || $status === 'em_conferencia' || $status === 'conferido')
    return 'info';
  return '';
}
// Duas confirmações operacionais: confirmar pedido; liberar para produção.
$proximoStatus = ['enviado' => 'conferido', 'em_conferencia' => 'conferido', 'conferido' => 'liberado_producao'];
$rascunhos = array_values(array_filter($pedidos, function ($p) {
  return $p['status_operacional'] === 'rascunho'; }));
$finalizados = array_values(array_filter($pedidos, function ($p) {
  return $p['status_operacional'] !== 'rascunho' && $p['status_entrega'] === 'entregue' && $p['status_pagamento'] === 'pago'; }));
$abertos = array_values(array_filter($pedidos, function ($p) {
  return $p['status_operacional'] !== 'rascunho' && !($p['status_entrega'] === 'entregue' && $p['status_pagamento'] === 'pago'); }));
$gruposPedidos = [
  ['titulo' => 'Rascunhos', 'descricao' => 'Pedidos ainda não enviados. Podem ser editados ou excluídos pelo responsável autorizado.', 'pedidos' => $rascunhos],
  ['titulo' => 'Pedidos em aberto', 'descricao' => 'Pedidos que ainda aguardam alguma etapa, entrega ou pagamento.', 'pedidos' => $abertos],
  ['titulo' => 'Pedidos finalizados', 'descricao' => 'Pedidos entregues e totalmente pagos.', 'pedidos' => $finalizados],
];
?>
<div class="page-heading page-heading-com-acao">
  <div><span class="eyebrow"><?= $ehGestao ? 'Acompanhamento geral' : 'Área do líder' ?></span>
    <h2><?= $ehGestao ? 'Gestão de Pedidos' : 'Meus Pedidos' ?></h2>
    <p><?= count($pedidos) ?> pedido(s) listado(s). Pedidos enviados não podem ser editados nem excluídos.</p>
  </div>
  <?php if ($ehGestao && $pedidos): ?><a class="btn btn-secondary" href="<?= e(app_url('view/pdfPedidos.php')) ?>"
      target="_blank" rel="noopener">PDF de todos</a><?php endif; ?>
</div>
<?php require __DIR__ . '/partials/flash.php'; ?>
<?php if (!$pedidos): ?>
  <section class="card mod-empty">
    <h3>Nenhum pedido cadastrado</h3>
    <p>Os pedidos aparecerão aqui depois de serem criados.</p><?php if (app_has_permission('criar_pedido')): ?><a
        class="btn btn-primary" href="<?= e(app_url('view/pedidos.php')) ?>">Criar primeiro pedido</a><?php endif; ?>
  </section>
<?php else: ?>
  <?php foreach ($gruposPedidos as $grupo): ?>
    <section class="pedido-grupo mod-section">
      <div class="pedido-grupo-heading">
        <div>
          <h3><?= e($grupo['titulo']) ?></h3>
          <p><?= e($grupo['descricao']) ?></p>
        </div><span
          class="status-pill <?= $grupo['titulo'] === 'Pedidos finalizados' ? 'ok' : ($grupo['titulo'] === 'Pedidos em aberto' ? 'warn' : '') ?>"><?= count($grupo['pedidos']) ?></span>
      </div>
      <?php if (!$grupo['pedidos']): ?>
        <div class="card mod-empty pedido-grupo-vazio">Nenhum pedido nesta categoria.</div><?php else: ?>
        <div class="mod-order-list">
          <?php foreach ($grupo['pedidos'] as $pedido):
            $id = (int) $pedido['id'];
            $qtd = array_sum(array_map(function ($i) {
              return (int) $i['quantidade']; }, $pedido['itens']));
            $statusOp = $pedido['status_operacional'];
            $statusPag = $pedido['status_pagamento'];
            $statusEntrega = $pedido['status_entrega'];
            $temProximo = $ehGestao && isset($proximoStatus[$statusOp]);
            $rotuloBotaoStatus = in_array($statusOp, ['enviado', 'em_conferencia'], true) ? 'Confirmar pedido' : 'Confirmado para produção — OK';
            ?>
            <article class="card mod-order-card">
              <div class="mod-order-head">
                <div><span class="mod-muted"><?= e($pedido['numero_pedido'] ?: ('Pedido #' . $id)) ?> ·
                    <?= e(formatar_data_hora($pedido['criado_em'])) ?></span>
                  <h3><?= e($pedido['nome_comprador']) ?></h3>
                  <p><?= e($pedido['congregacao_nome']) ?><?php if ($pedido['congregacao_tipo'] === 'balcao'): ?> · Balcão /
                      Setor <?= e($pedido['setor_balcao'] ?: '—') ?><?php endif; ?></p>
                </div>
                <div class="mod-order-tags"><span
                    class="status-pill <?= e(classeStatusOperacional($statusOp)) ?>"><?= e(rotulo_operacional($statusOp)) ?></span><span
                    class="status-pill <?= $statusPag === 'pago' ? 'ok' : ($statusPag === 'parcial' ? 'info' : 'warn') ?>">Pagamento:
                    <?= e(rotulo_pagamento($statusPag)) ?></span><span
                    class="status-pill <?= $statusEntrega === 'entregue' ? 'ok' : 'warn' ?>"><?= e(rotulo_entrega($statusEntrega)) ?></span>
                </div>
              </div>
              <div class="mod-order-data">
                <div><span>Total</span><strong><?= e(formatar_moeda($pedido['valor_total'])) ?></strong></div>
                <div><span>Recebido</span><strong><?= e(formatar_moeda($pedido['total_recebido'])) ?></strong></div>
                <div><span>Saldo</span><strong><?= e(formatar_moeda($pedido['saldo_devedor'])) ?></strong></div>
                <div><span>Quantidade</span><strong><?= (int) $qtd ?> camiseta(s)</strong></div>
              </div>
              <div class="mod-items"><?php foreach ($pedido['itens'] as $item): ?><span
                    class="mod-item-chip"><?= e($item['modelo'] . ' · ' . $item['tamanho']) ?><strong><?= (int) $item['quantidade'] ?></strong></span><?php endforeach; ?>
              </div>
              <?php if ($ehGestao): ?>
                <p class="mod-muted">Lançado por: <?= e($pedido['criador_nome'] ?: 'Registro antigo') ?> · Responsável:
                  <?= e($pedido['responsavel_nome'] ?: 'Não atribuído') ?></p><?php endif; ?>
              <div class="mod-order-actions">
                <a class="btn btn-secondary btn-compacto" href="<?= e(app_url('view/pdfPedido.php?id=' . $id)) ?>"
                  target="_blank" rel="noopener">PDF do pedido</a>
                <?php if ($statusOp === 'rascunho'): ?>
                  <a class="btn btn-secondary btn-compacto" href="<?= e(app_url('view/editarPedido.php?id=' . $id)) ?>">Editar
                    rascunho</a>
                  <form action="<?= e(app_url('processamento/processamentoExcluirPedido.php')) ?>" method="post"
                    data-confirm="Excluir este rascunho permanentemente?" class="mod-inline-form"><input type="hidden"
                      name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="pedido_id"
                      value="<?= $id ?>"><button class="btn btn-secondary btn-compacto" type="submit">Excluir rascunho</button>
                  </form>
                <?php else: ?><span class="mod-muted">Dados bloqueados após envio.</span><?php endif; ?>
                <?php if ($temProximo && app_has_permission('alterar_status_operacional')): ?>
                  <form class="mod-inline-form" action="<?= e(app_url('processamento/processamentoStatus.php')) ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="acao"
                      value="operacional"><input type="hidden" name="pedido_id" value="<?= $id ?>"><input type="hidden"
                      name="status_operacional" value="<?= e($proximoStatus[$statusOp]) ?>"><button
                      class="btn btn-primary btn-compacto" type="submit"><?= e($rotuloBotaoStatus) ?></button></form>
                <?php endif; ?>
                <?php if ($ehGestao && $statusOp !== 'rascunho' && app_has_permission('alterar_status_operacional')): ?>
                  <?php if ($statusEntrega === 'aguardando'): ?>
                    <form class="mod-inline-form" action="<?= e(app_url('processamento/processamentoStatus.php')) ?>" method="post">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="acao"
                        value="entrega"><input type="hidden" name="pedido_id" value="<?= $id ?>"><input type="hidden"
                        name="status_entrega" value="entregue"><button class="btn btn-secondary btn-compacto" type="submit">Marcar
                        como entregue</button></form>
                  <?php else: ?><span class="status-pill ok">Entrega concluída</span>
                    <form class="mod-inline-form" action="<?= e(app_url('processamento/processamentoStatus.php')) ?>" method="post"
                      data-confirm="Corrigir o status da entrega para aguardando? "><input type="hidden" name="csrf_token"
                        value="<?= e(csrf_token()) ?>"><input type="hidden" name="acao" value="entrega"><input type="hidden"
                        name="pedido_id" value="<?= $id ?>"><input type="hidden" name="status_entrega" value="aguardando"><button
                        class="btn btn-secondary btn-compacto" type="submit">Corrigir entrega</button></form><?php endif; ?>
                <?php endif; ?>
                <?php if ($ehGestao && app_has_permission('financeiro') && $statusOp !== 'rascunho'): ?><a
                    class="btn btn-secondary btn-compacto" href="<?= e(app_url('view/financeiro.php#pedido-' . $id)) ?>">Registrar
                    pagamento</a><?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div><?php endif; ?>
    </section>
  <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>