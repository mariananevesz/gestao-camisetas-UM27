<?php
require_once __DIR__ . '/../config/bootstrap.php';
app_require_permission('relatorios');
$dados = app_system()->obterRelatorios();
$top = array_slice($dados['ranking'], 0, 10);
$tituloPagina = 'Relatórios';
$paginaAtiva = 'relatorios';
require __DIR__ . '/partials/header.php';
?>
<div class="page-heading page-heading-com-acao">
    <div><span class="eyebrow">Indicadores administrativos</span>
        <h2>Relatórios</h2>
        <p>Vendas confirmadas, valores recebidos, saldo em aberto e desempenho por congregação.</p>
    </div><a class="btn btn-secondary" href="<?= e(app_url('view/pdfRelatorios.php')) ?>" target="_blank"
        rel="noopener">Gerar PDF</a>
</div>
<?php require __DIR__ . '/partials/flash.php'; ?>
<div class="mod-grid">
    <section class="card metric-card"><span>Total
            vendido</span><strong><?= e(formatar_moeda($dados['resumo']['total_vendido'])) ?></strong><small>Pedidos
            enviados; rascunhos excluídos</small></section>
    <section class="card metric-card"><span>Total
            recebido</span><strong><?= e(formatar_moeda($dados['resumo']['total_recebido'])) ?></strong><small>Pagamentos
            individuais registrados</small></section>
    <section class="card metric-card"><span>Saldo em
            aberto</span><strong><?= e(formatar_moeda($dados['resumo']['saldo_aberto'])) ?></strong><small>Diferença
            entre venda e recebimentos</small></section>
</div>
<section class="card mod-section">
    <div class="mod-card-heading">
        <h3>Vendas no Balcão</h3><span class="status-pill">Fora do ranking</span>
    </div>
    <div class="mod-order-data">
        <div><span>Pedidos</span><strong><?= (int) $dados['balcao']['pedidos'] ?></strong></div>
        <div><span>Total vendido</span><strong><?= e(formatar_moeda($dados['balcao']['total_vendido'])) ?></strong>
        </div>
    </div>
    <p class="mod-muted">Pedidos do Balcão são identificados pelo tipo de origem e não são atribuídos a congregações no
        ranking.</p>
</section>
<section class="card mod-section">
    <div class="mod-card-heading">
        <h3>Ranking de congregações</h3><span class="mod-muted">Ordenado por total vendido</span>
    </div><?php if (!$top): ?>
        <div class="mod-empty">Ainda não há vendas enviadas para o ranking.</div><?php else: ?>
        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Congregação</th>
                        <th>Setor</th>
                        <th>Pedidos</th>
                        <th>Camisetas</th>
                        <th>Total vendido</th>
                    </tr>
                </thead>
                <tbody><?php foreach ($top as $i => $linha): ?>
                        <tr>
                            <td><span class="ranking-numero"><?= $i + 1 ?></span></td>
                            <td><strong><?= e($linha['nome']) ?></strong></td>
                            <td><?= e($linha['setor'] ?: '—') ?></td>
                            <td><?= (int) $linha['pedidos'] ?></td>
                            <td><?= (int) $linha['camisetas'] ?></td>
                            <td><strong><?= e(formatar_moeda($linha['total_vendido'])) ?></strong></td>
                        </tr><?php endforeach; ?>
                </tbody>
            </table>
        </div><?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>