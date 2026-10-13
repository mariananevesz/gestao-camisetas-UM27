<?php
require_once __DIR__ . '/../config/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') app_redirect('view/gestaoPedidos.php');
app_require_permission('criar_pedido');
if (!validar_csrf($_POST['csrf_token'] ?? null)) {
    flash('erro', 'Sua sessão expirou. Atualize a página e tente novamente.');
    app_redirect('view/gestaoPedidos.php');
}
try {
    app_system()->excluirRascunho((int) ($_POST['pedido_id'] ?? 0), app_current_user());
    flash('sucesso', 'Rascunho excluído.');
} catch (DomainException $e) {
    flash('erro', $e->getMessage());
} catch (Throwable $e) {
    flash('erro', 'Não foi possível excluir o rascunho.');
}
app_redirect('view/gestaoPedidos.php');
