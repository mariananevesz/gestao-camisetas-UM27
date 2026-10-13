<?php
require_once __DIR__ . '/../config/bootstrap.php';
app_require_permission('lotes');
require_once APP_ROOT . '/service/PdfService.php';
$lote = app_system()->obterLote((int) ($_GET['id'] ?? 0));
if (!$lote) {
    flash('erro', 'Lote não encontrado.');
    app_redirect('view/lotes.php');
}
$pdf = new PdfService();
$pdf->titulo('UMADEPRESP 2027 — Lote para fornecedor', $lote['codigo_lote']);
$pdf->chaveValor('Código do lote', $lote['codigo_lote']);
$pdf->chaveValor('Gerado em', formatar_data_hora($lote['data_geracao']));
$pdf->chaveValor('Status', str_replace('_', ' ', $lote['status']));
$pdf->espaco(10);
$pdf->secao('Consolidado por modelo e tamanho');
foreach ($lote['itens_consolidados'] as $item) {
    $pdf->texto($item['modelo'] . ' — ' . $item['tamanho'] . ': ' . (int) $item['quantidade'] . ' unidade(s)', 10, true);
}
$pdf->espaco(12);
$pdf->linha();
$pdf->texto('Documento de produção. Não contém nomes de compradores, telefones, líderes ou valores financeiros.', 8.5, false, [75, 85, 99]);
$pdf->saida('lote-' . $lote['codigo_lote'] . '.pdf');
