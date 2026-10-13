<?php
require_once __DIR__ . '/../config/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') app_redirect('view/gestaoPedidos.php');
app_require_login();
if (!validar_csrf($_POST['csrf_token'] ?? null)) {
    flash('erro', 'Sua sessão expirou. Atualize a página e tente novamente.');
    app_redirect('view/gestaoPedidos.php');
}
$usuario = app_current_user();
try {
    $acao = (string) ($_POST['acao'] ?? '');
    if ($acao === 'operacional') {
        if (!app_has_permission('alterar_status_operacional')) throw new DomainException('Você não tem permissão para alterar o status operacional.');
        $pedidoAcesso = app_system()->obterPedido((int) ($_POST['pedido_id'] ?? 0));
        if (!$pedidoAcesso || !app_system()->podeVerPedido($pedidoAcesso, $usuario)) throw new DomainException('Você não tem acesso a este pedido.');
        app_system()->mudarStatusOperacional((int) ($_POST['pedido_id'] ?? 0), (string) ($_POST['status_operacional'] ?? ''), $usuario);
        flash('sucesso', 'Etapa do pedido atualizada.');
    } elseif ($acao === 'entrega') {
        if (!app_has_permission('alterar_status_operacional')) throw new DomainException('Você não tem permissão para alterar a entrega.');
        $pedidoAcesso = app_system()->obterPedido((int) ($_POST['pedido_id'] ?? 0));
        if (!$pedidoAcesso || !app_system()->podeVerPedido($pedidoAcesso, $usuario)) throw new DomainException('Você não tem acesso a este pedido.');
        app_system()->mudarStatusEntrega((int) ($_POST['pedido_id'] ?? 0), (string) ($_POST['status_entrega'] ?? ''), $usuario);
        flash('sucesso', 'Status de entrega atualizado.');
    } else {
        throw new DomainException('Ação de status inválida.');
    }
} catch (DomainException $e) {
    flash('erro', $e->getMessage());
} catch (Throwable $e) {
    flash('erro', 'Não foi possível atualizar o status. Atualize a página e tente novamente.');
}
app_redirect('view/gestaoPedidos.php');
