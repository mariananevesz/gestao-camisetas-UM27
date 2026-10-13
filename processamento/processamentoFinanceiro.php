<?php
require_once __DIR__ . '/../config/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') app_redirect('view/financeiro.php');
app_require_permission('financeiro');
if (!app_has_permission('registrar_pagamentos')) { flash('erro', 'Seu perfil não tem permissão para registrar pagamentos.'); app_redirect('view/financeiro.php'); }
if (!validar_csrf($_POST['csrf_token'] ?? null)) { flash('erro', 'Sua sessão expirou. Atualize a página e tente novamente.'); app_redirect('view/financeiro.php'); }
try {
    if (($_POST['acao'] ?? '') !== 'registrar') throw new DomainException('Ação financeira inválida.');
    $pedidoAcesso = app_system()->obterPedido((int) ($_POST['pedido_id'] ?? 0));
    if (!$pedidoAcesso || !app_system()->podeVerPedido($pedidoAcesso, app_current_user())) throw new DomainException('Você não tem acesso financeiro a este pedido.');
    app_system()->registrarPagamento((int) ($_POST['pedido_id'] ?? 0), $_POST['valor'] ?? '', (string) ($_POST['metodo'] ?? ''), (string) ($_POST['observacao'] ?? ''), (string) ($_POST['token_operacao'] ?? ''), app_current_user());
    flash('sucesso', 'Pagamento registrado no histórico. O saldo foi recalculado.');
} catch (DomainException $e) {
    flash('erro', $e->getMessage());
} catch (Throwable $e) {
    flash('erro', 'Não foi possível registrar o pagamento. Atualize a página antes de tentar novamente.');
}
app_redirect('view/financeiro.php#pedido-' . (int) ($_POST['pedido_id'] ?? 0));
