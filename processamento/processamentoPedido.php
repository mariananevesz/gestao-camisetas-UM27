<?php
require_once __DIR__ . '/../config/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') app_redirect('view/gestaoPedidos.php');
app_require_permission('criar_pedido');
if (!validar_csrf($_POST['csrf_token'] ?? null)) {
    flash('erro', 'Sua sessão expirou. Atualize a página e tente novamente.');
    app_redirect('view/gestaoPedidos.php');
}
$usuario = app_current_user();
$acao = (string) ($_POST['acao'] ?? '');
$intencao = in_array($_POST['intencao'] ?? 'rascunho', ['rascunho', 'enviar'], true) ? (string) ($_POST['intencao'] ?? 'rascunho') : 'rascunho';
try {
    if ($acao === 'criar') {
        $id = app_system()->criarPedido($_POST, $usuario, $intencao);
        $pedido = app_system()->obterPedido($id);
        flash('sucesso', $intencao === 'enviar' ? $pedido['numero_pedido'] . ' enviado para conferência.' : $pedido['numero_pedido'] . ' salvo como rascunho.');
        app_redirect('view/gestaoPedidos.php');
    }
    if ($acao === 'editar') {
        $id = (int) ($_POST['pedido_id'] ?? 0);
        $pedidoAtual = app_system()->obterPedido($id);
        if (!$pedidoAtual || $pedidoAtual['status_operacional'] !== 'rascunho' || !app_system()->podeVerPedido($pedidoAtual, $usuario)) {
            throw new DomainException('Você só pode editar seus próprios pedidos enquanto estiverem em rascunho.');
        }
        app_system()->atualizarRascunho($id, $_POST, $usuario, $intencao);
        flash('sucesso', $intencao === 'enviar' ? 'Pedido salvo e enviado para conferência. Os dados agora estão bloqueados.' : 'Rascunho atualizado.');
        app_redirect('view/gestaoPedidos.php');
    }
    throw new DomainException('Ação de pedido inválida.');
} catch (DomainException $e) {
    salvar_formulario_anterior($_POST);
    flash('erro', $e->getMessage());
} catch (Throwable $e) {
    salvar_formulario_anterior($_POST);
    flash('erro', 'Não foi possível salvar o pedido. Confira os dados e tente novamente.');
}
if ($acao === 'editar') app_redirect('view/editarPedido.php?id=' . (int) ($_POST['pedido_id'] ?? 0));
app_redirect('view/pedidos.php');
