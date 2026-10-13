<?php
require_once __DIR__ . '/../config/bootstrap.php';
app_require_permission('relatorios');
require_once APP_ROOT . '/service/PdfService.php';
$dados = app_system()->obterRelatorios();
$pdf = new PdfService();
$pdf->titulo('UMADEPRESP 2027 — Relatório administrativo', 'Resumo financeiro e ranking de congregações');
$pdf->secao('Resumo financeiro');
$pdf->chaveValor('Total vendido', formatar_moeda($dados['resumo']['total_vendido']));
$pdf->chaveValor('Total recebido', formatar_moeda($dados['resumo']['total_recebido']));
$pdf->chaveValor('Saldo em aberto', formatar_moeda($dados['resumo']['saldo_aberto']));
$pdf->espaco(8);
$pdf->secao('Balcão — fora do ranking');
$pdf->chaveValor('Pedidos de Balcão', (int) $dados['balcao']['pedidos']);
$pdf->chaveValor('Total vendido no Balcão', formatar_moeda($dados['balcao']['total_vendido']));
$pdf->espaco(8);
$pdf->secao('Ranking de congregações');
foreach (array_slice($dados['ranking'], 0, 20) as $i => $r) {
    $pdf->texto(($i + 1) . '. ' . $r['nome'] . ' — ' . (int) $r['pedidos'] . ' pedido(s), ' . (int) $r['camisetas'] . ' camiseta(s), ' . formatar_moeda($r['total_vendido']), 9.2, $i < 3);
}
$pdf->saida('relatorio-umadepresp-2027.pdf');
