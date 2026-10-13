<?php
require_once __DIR__ . '/../config/bootstrap.php';
app_require_permission('criar_pedido');
$usuario = app_current_user();
$ehGestao = app_is_management();
$congregacoes = app_system()->listarCongregacoes($ehGestao);
if (!$ehGestao) $congregacoes = array_values(array_filter($congregacoes, function ($c) use ($usuario) { return (int) $c['id'] === (int) $usuario['congregacao_id']; }));
$camisetas = app_system()->listarCamisetas();
$setores = app_system()->listarSetores();
$lideres = $ehGestao && app_has_permission('criar_pedido_em_nome_de_outro') ? app_system()->listarLideresAtivos() : [];
$dadosFormulario = obter_formulario_anterior();
$modo = 'cadastrar';
$tituloPagina = 'Novo pedido';
$paginaAtiva = 'pedidos';
require __DIR__ . '/partials/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">Cadastro de pedidos</span><h2>Novo pedido</h2><p>Salve como rascunho ou envie para iniciar a conferência.</p></div></div>
<?php require __DIR__ . '/partials/flash.php'; ?>
<?php require __DIR__ . '/partials/formPedido.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
