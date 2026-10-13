<?php

require_once __DIR__ . '/../config/bootstrap.php';
app_require_login();
require_once APP_ROOT . '/service/PdfService.php';
require_once APP_ROOT . '/service/pdf_helpers.php';

$pedidoId = (int) ($_GET['id'] ?? 0);
$pedido = app_controller()->buscarPedidoPorId($pedidoId);
$pedidoNovo = app_system()->obterPedido($pedidoId);
if (!$pedidoNovo || !app_system()->podeVerPedido($pedidoNovo, app_current_user())) {
    http_response_code(403);
    exit('Você não tem permissão para visualizar este pedido.');
}

if (!$pedido) {
    http_response_code(404);
    exit('Pedido não encontrado.');
}

$pdf = new PdfService();
$pdf->titulo(
    'Comprovante de pedido',
    'Gerado em ' . date('d/m/Y')
);
$pdf->secao($pedido['numero_pedido']);
adicionar_pedido_ao_pdf($pdf, $pedido, false);
$pdf->saida($pedido['numero_pedido'] . '.pdf');
