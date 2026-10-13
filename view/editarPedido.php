<?php
require_once __DIR__ . '/../config/bootstrap.php';
app_require_permission('criar_pedido');
$usuario = app_current_user();
$pedidoId = (int) ($_GET['id'] ?? 0);
$pedido = app_system()->obterPedido($pedidoId);
if (!$pedido || $pedido['status_operacional'] !== 'rascunho' || !app_system()->podeVerPedido($pedido, $usuario)) {
    flash('erro', 'Este pedido não existe, não pertence a você ou já foi enviado. Pedidos enviados não podem ser editados.');
    app_redirect('view/gestaoPedidos.php');
}
$ehGestao = app_is_management();
$congregacoes = app_system()->listarCongregacoes($ehGestao);
if (!$ehGestao) $congregacoes = array_values(array_filter($congregacoes, function ($c) use ($usuario) { return (int) $c['id'] === (int) $usuario['congregacao_id']; }));
$camisetas = app_system()->listarCamisetas();
$setores = app_system()->listarSetores();
$lideres = $ehGestao && app_has_permission('criar_pedido_em_nome_de_outro') ? app_system()->listarLideresAtivos() : [];
$dadosFormulario = obter_formulario_anterior();
$modo = 'editar';
$tituloPagina = 'Editar rascunho';
$paginaAtiva = 'gestao';
require __DIR__ . '/partials/header.php';
?>
<div class="page-heading page-heading-com-acao"><div><span class="eyebrow">Pedido em rascunho</span><h2>Editar <?= e($pedido['numero_pedido']) ?></h2><p>Depois de enviado para conferência, este pedido ficará bloqueado.</p></div><a class="btn btn-secondary" href="<?= e(app_url('view/gestaoPedidos.php')) ?>">Voltar</a></div>
<?php require __DIR__ . '/partials/flash.php'; ?>
<?php require __DIR__ . '/partials/formPedido.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
