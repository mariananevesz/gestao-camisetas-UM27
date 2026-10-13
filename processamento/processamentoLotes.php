<?php
require_once __DIR__ . '/../config/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') app_redirect('view/lotes.php');
app_require_permission('lotes');
if (!validar_csrf($_POST['csrf_token'] ?? null)) { flash('erro', 'Sua sessão expirou. Atualize a página e tente novamente.'); app_redirect('view/lotes.php'); }
try {
    $acao = (string) ($_POST['acao'] ?? '');
    if ($acao === 'criar') {
        if (!app_has_permission('gerar_lotes')) throw new DomainException('Você não tem permissão para gerar lotes.');
        $id = app_system()->criarLote($_POST['pedido_ids'] ?? [], app_current_user());
        flash('sucesso', 'Lote criado com sucesso. O código é ' . (app_system()->obterLote($id)['codigo_lote'] ?? ('#' . $id)) . '.');
    } elseif ($acao === 'atualizar') {
        app_system()->atualizarLote((int) ($_POST['lote_id'] ?? 0), (string) ($_POST['status'] ?? ''), (string) ($_POST['numero_pedido_externo'] ?? ''), app_current_user());
        flash('sucesso', 'Informações do lote atualizadas.');
    } else {
        throw new DomainException('Ação de lote inválida.');
    }
} catch (DomainException $e) {
    flash('erro', $e->getMessage());
} catch (Throwable $e) {
    flash('erro', 'Não foi possível concluir a operação do lote. Atualize a página e tente novamente.');
}
app_redirect('view/lotes.php');
