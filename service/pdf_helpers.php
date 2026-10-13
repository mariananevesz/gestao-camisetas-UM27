<?php

function adicionar_pedido_ao_pdf(PdfService $pdf, array $pedido, $incluirTitulo = true)
{
    if ($incluirTitulo) {
        $pdf->secao($pedido['numero_pedido'] . ' — ' . $pedido['nome_comprador']);
    }

    $pdf->chaveValor('Congregação', $pedido['congregacao_nome']);
    $pdf->chaveValor('Telefone', $pedido['telefone']);
    $pdf->chaveValor('Data do pedido', formatar_data_hora($pedido['criado_em']));
    $pdf->chaveValor('Entrega', rotulo_entrega($pedido['status_entrega']));
    $pdf->chaveValor('Pagamento', rotulo_pagamento($pedido['status_pagamento']));
    $pdf->espaco(4);
    $pdf->texto('Itens do pedido', 9.5, true, [17, 24, 39]);

    foreach ($pedido['itens'] as $item) {
        $descricao = sprintf(
            '• %s – %s | %d x %s = %s',
            $item['modelo'],
            $item['tamanho'],
            $item['quantidade'],
            formatar_moeda($item['valor_unitario']),
            formatar_moeda($item['subtotal'])
        );
        $pdf->texto($descricao, 8.8, false, [55, 65, 81], 10);
    }

    $pdf->espaco(3);
    $pdf->texto('Valor total: ' . formatar_moeda($pedido['valor_total']), 11, true, [37, 99, 235]);
    $pdf->espaco(8);
    $pdf->linha();
    $pdf->espaco(12);
}
