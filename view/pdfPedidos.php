<?php

require_once __DIR__ . '/../config/bootstrap.php';
app_require_permission('pedidos_ver_todos');
require_once APP_ROOT . '/service/PdfService.php';
require_once APP_ROOT . '/service/pdf_helpers.php';

$pedidos = app_controller()->listarTodosPedidos();

/*
 * Organiza os pedidos do mais antigo para o mais recente:
 * pedido 1, pedido 2, pedido 3...
 */
usort($pedidos, function ($pedidoA, $pedidoB) {
    return (int) $pedidoA['id'] <=> (int) $pedidoB['id'];
});

$pdf = new PdfService();

$pdf->titulo(
    'Relação completa de pedidos',
    'Gerado em ' . date('d/m/Y')
);

$pdf->texto('Total de pedidos: ' . count($pedidos), 10, true);
$pdf->espaco(8);

if (empty($pedidos)) {
    $pdf->texto('Nenhum pedido cadastrado até o momento.');
} else {
    foreach ($pedidos as $pedido) {
        adicionar_pedido_ao_pdf($pdf, $pedido, true);
    }
}

$pdf->saida('Relatorio de Pedidos.pdf');