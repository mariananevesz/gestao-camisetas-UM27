
<?php
require_once __DIR__ . '/../config/bootstrap.php';
app_require_permission('financeiro');

$usuario = app_current_user();
$pedidos = app_system()->listarPedidos($usuario, true);

if ($usuario['perfil'] === 'gestao' && !app_has_permission('pedidos_ver_todos')) {
    $pedidos = array_values(array_filter($pedidos, function ($pedido) use ($usuario) {
        return (int) $pedido['id_usuario_criou'] === (int) $usuario['id']
            || (int) $pedido['id_usuario_responsavel'] === (int) $usuario['id'];
    }));
}

$resumo = app_system()->obterResumoFinanceiro();

// Organiza os pedidos nos três status de pagamento.
$pedidosPorStatus = [
    'pago' => [],
    'parcial' => [],
    'pendente' => [],
];

foreach ($pedidos as $pedido) {
    $statusPagamento = $pedido['status_pagamento'] ?? 'pendente';

    if (!isset($pedidosPorStatus[$statusPagamento])) {
        $statusPagamento = 'pendente';
    }

    $pedidosPorStatus[$statusPagamento][] = $pedido;
}

$gruposFinanceiros = [
    [
        'chave' => 'pago',
        'titulo' => 'Pago',
        'descricao' => 'Pedidos totalmente quitados. Abra um cartão para consultar o histórico.',
        'pedidos' => $pedidosPorStatus['pago'],
    ],
    [
        'chave' => 'parcial',
        'titulo' => 'Parcial',
        'descricao' => 'Pedidos que já receberam pagamentos, mas ainda têm saldo em aberto.',
        'pedidos' => $pedidosPorStatus['parcial'],
    ],
    [
        'chave' => 'pendente',
        'titulo' => 'Pendente',
        'descricao' => 'Pedidos que ainda não têm pagamentos registrados.',
        'pedidos' => $pedidosPorStatus['pendente'],
    ],
];

$tituloPagina = 'Financeiro';
$paginaAtiva = 'financeiro';

require __DIR__ . '/partials/header.php';
?>

<div class="page-heading">
    <div>
        <span class="eyebrow">Recebimentos</span>
        <h2>Financeiro</h2>
        <p>Consulte os valores e registre os pagamentos de cada pedido.</p>
    </div>
</div>

<?php require __DIR__ . '/partials/flash.php'; ?>

<div class="mod-grid">
    <section class="card metric-card">
        <span>Total vendido</span>
        <strong><?= e(formatar_moeda($resumo['total_vendido'])) ?></strong>
        <small>Pedidos enviados, sem rascunhos</small>
    </section>

    <section class="card metric-card">
        <span>Total recebido</span>
        <strong><?= e(formatar_moeda($resumo['total_recebido'])) ?></strong>
        <small>Soma de todos os lançamentos</small>
    </section>

    <section class="card metric-card">
        <span>Saldo em aberto</span>
        <strong><?= e(formatar_moeda($resumo['saldo_aberto'])) ?></strong>
        <small>Vendido menos recebido</small>
    </section>
</div>

<?php if (!$pedidos): ?>

    <section class="card mod-empty">
        <h3>Nenhum pedido enviado para receber pagamentos</h3>
        <p>Os pedidos aparecerão aqui quando forem enviados.</p>
    </section>

<?php else: ?>

    <section
        class="pedido-busca"
        role="search"
        aria-label="Buscar pedidos no financeiro"
    >
        <label for="busca-pedidos">Buscar pedido</label>

        <input
            id="busca-pedidos"
            type="search"
            placeholder="Número, comprador, congregação, responsável ou status..."
            autocomplete="off"
            aria-describedby="pedido-busca-feedback"
        >

        <p
            id="pedido-busca-feedback"
            class="mod-muted"
            aria-live="polite"
        >
            Pesquise por número, comprador, congregação, responsável ou status do pagamento.
        </p>
    </section>

    <div
        id="pedido-busca-sem-resultados"
        class="card mod-empty pedido-busca-sem-resultados"
        hidden
    >
        <h3>Nenhum pedido encontrado</h3>
        <p>Tente pesquisar por outro nome, número de pedido, congregação ou status.</p>
    </div>

    <?php foreach ($gruposFinanceiros as $grupo): ?>

        <?php
        // Pendente começa aberto. Os outros grupos começam recolhidos.
        $grupoAbertoInicialmente = $grupo['chave'] === 'pendente';

        if ($grupo['chave'] === 'pago') {
            $classeGrupo = 'ok';
        } elseif ($grupo['chave'] === 'parcial') {
            $classeGrupo = 'info';
        } else {
            $classeGrupo = 'warn';
        }
        ?>

        <details
            class="pedido-grupo mod-section pedido-grupo-recolhivel financeiro-grupo"
            data-order-section
            data-group-key="<?= e($grupo['chave']) ?>"
            data-default-open="<?= $grupoAbertoInicialmente ? 'true' : 'false' ?>"
            data-total-count="<?= count($grupo['pedidos']) ?>"
            <?= $grupoAbertoInicialmente ? 'open' : '' ?>
        >
            <summary class="pedido-grupo-heading">

                <div class="pedido-grupo-heading-text">
                    <h3><?= e($grupo['titulo']) ?></h3>
                    <p><?= e($grupo['descricao']) ?></p>
                </div>

                <div class="pedido-grupo-heading-meta">
                    <span
                        class="status-pill <?= e($classeGrupo) ?>"
                        data-group-count
                    >
                        <?= count($grupo['pedidos']) ?>
                    </span>

                    <span
                        class="pedido-grupo-chevron"
                        aria-hidden="true"
                    >⌄</span>
                </div>

            </summary>

            <div class="pedido-grupo-conteudo">

                <?php if (!$grupo['pedidos']): ?>

                    <div
                        class="card mod-empty pedido-grupo-vazio"
                        data-group-empty
                    >
                        Nenhum pedido neste status.
                    </div>

                <?php else: ?>

                    <div class="mod-order-list">

                        <?php foreach ($grupo['pedidos'] as $pedido): ?>

                            <?php
                            $id = (int) $pedido['id'];

                            $pagamentos = app_system()->listarPagamentos($id);
                            $token = bin2hex(random_bytes(32));

                            $statusPagamento = $pedido['status_pagamento'] ?? 'pendente';
                            $statusOperacional = $pedido['status_operacional'] ?? '';
                            $statusEntrega = $pedido['status_entrega'] ?? 'aguardando';

                            if ($statusPagamento === 'pago') {
                                $classePagamento = 'ok';
                            } elseif ($statusPagamento === 'parcial') {
                                $classePagamento = 'info';
                            } else {
                                $classePagamento = 'warn';
                            }

                            // Inclui informações dos pagamentos na busca geral.
                            $pagamentosBusca = implode(' ', array_map(
                                function ($pagamento) {
                                    return implode(' ', array_filter([
                                        $pagamento['metodo'] ?? '',
                                        $pagamento['observacao'] ?? '',
                                        $pagamento['registrado_por_nome'] ?? '',
                                    ], function ($valor) {
                                        return $valor !== null && $valor !== '';
                                    }));
                                },
                                $pagamentos
                            ));

                            $textoBusca = implode(' ', array_filter([
                                $grupo['titulo'],
                                $pedido['numero_pedido'] ?? '',
                                'Pedido ' . $id,
                                $pedido['nome_comprador'] ?? '',
                                $pedido['congregacao_nome'] ?? '',
                                $pedido['setor_balcao'] ?? '',
                                $pedido['criador_nome'] ?? '',
                                $pedido['responsavel_nome'] ?? '',
                                rotulo_pagamento($statusPagamento),
                                rotulo_operacional($statusOperacional),
                                rotulo_entrega($statusEntrega),
                                formatar_moeda($pedido['valor_total']),
                                formatar_moeda($pedido['total_recebido']),
                                formatar_moeda($pedido['saldo_devedor']),
                                $pagamentosBusca,
                            ], function ($valor) {
                                return $valor !== null && $valor !== '';
                            }));
                            ?>

                            <details
                                class="card mod-order-card mod-order-card-finalizado financeiro-order-card"
                                id="pedido-<?= $id ?>"
                                data-order-card
                                data-search="<?= e($textoBusca) ?>"
                            >
                                <summary class="mod-order-resumo-finalizado">

                                    <div class="mod-order-head">

                                        <div class="mod-order-identificacao">
                                            <span class="mod-muted">
                                                <?= e($pedido['numero_pedido'] ?: ('Pedido #' . $id)) ?>
                                                · <?= e(formatar_data_hora($pedido['criado_em'])) ?>
                                            </span>

                                            <h3><?= e($pedido['nome_comprador']) ?></h3>

                                            <p>
                                                <?= e($pedido['congregacao_nome']) ?>

                                                <?php if (($pedido['congregacao_tipo'] ?? '') === 'balcao'): ?>
                                                    · Balcão / Setor
                                                    <?= e($pedido['setor_balcao'] ?: '—') ?>
                                                <?php endif; ?>

                                                · <?= e(rotulo_operacional($statusOperacional)) ?>
                                            </p>
                                        </div>

                                        <div class="mod-order-tags">
                                            <span class="status-pill <?= e($classePagamento) ?>">
                                                Pagamento: <?= e(rotulo_pagamento($statusPagamento)) ?>
                                            </span>

                                            <span class="status-pill <?= $statusEntrega === 'entregue' ? 'ok' : 'warn' ?>">
                                                <?= e(rotulo_entrega($statusEntrega)) ?>
                                            </span>
                                        </div>

                                    </div>

                                    <div class="mod-order-data">

                                        <div>
                                            <span>Total da compra</span>
                                            <strong>
                                                <?= e(formatar_moeda($pedido['valor_total'])) ?>
                                            </strong>
                                        </div>

                                        <div>
                                            <span>Já recebido</span>
                                            <strong>
                                                <?= e(formatar_moeda($pedido['total_recebido'])) ?>
                                            </strong>
                                        </div>

                                        <div>
                                            <span>Falta pagar</span>
                                            <strong>
                                                <?= e(formatar_moeda($pedido['saldo_devedor'])) ?>
                                            </strong>
                                        </div>

                                        <div>
                                            <span>Pagamentos lançados</span>
                                            <strong><?= count($pagamentos) ?></strong>
                                        </div>

                                    </div>

                                    <div class="mod-order-final-toggle">
                                        <span class="mod-order-final-toggle-text">
                                            Ver detalhes e pagamentos
                                        </span>

                                        <span
                                            class="mod-order-final-chevron"
                                            aria-hidden="true"
                                        >⌄</span>
                                    </div>

                                </summary>

                                <div class="mod-order-expanded">

                                    <?php if ($pedido['criador_nome'] || $pedido['responsavel_nome']): ?>
                                        <p class="mod-muted">
                                            Lançado por:
                                            <?= e($pedido['criador_nome'] ?: 'Registro antigo') ?>

                                            · Responsável:
                                            <?= e($pedido['responsavel_nome'] ?: 'Não atribuído') ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if ($pagamentos): ?>

                                        <details class="financeiro-historico-pagamentos">
                                            <summary>
                                                Ver histórico de pagamentos
                                                (<?= count($pagamentos) ?>)
                                            </summary>

                                            <div class="mod-table-wrap">
                                                <table class="mod-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Data</th>
                                                            <th>Valor</th>
                                                            <th>Forma</th>
                                                            <th>Registrado por</th>
                                                            <th>Observação</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        <?php foreach ($pagamentos as $pagamento): ?>
                                                            <tr>
                                                                <td>
                                                                    <?= e(formatar_data_hora($pagamento['data_pagamento'])) ?>
                                                                </td>

                                                                <td>
                                                                    <?= e(formatar_moeda($pagamento['valor'])) ?>
                                                                </td>

                                                                <td>
                                                                    <?= e($pagamento['metodo'] ?: 'Não informado') ?>
                                                                </td>

                                                                <td>
                                                                    <?= e($pagamento['registrado_por_nome'] ?: 'Usuário removido') ?>
                                                                </td>

                                                                <td>
                                                                    <?= e($pagamento['observacao'] ?: '—') ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </details>

                                    <?php else: ?>

                                        <p class="mod-muted">
                                            Nenhum pagamento registrado até o momento.
                                        </p>

                                    <?php endif; ?>

                                    <?php if ((float) $pedido['saldo_devedor'] > 0): ?>

                                        <form
                                            action="<?= e(app_url('processamento/processamentoFinanceiro.php')) ?>"
                                            method="post"
                                            class="form-stack mod-section"
                                            style="margin-top:18px"
                                        >
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e(csrf_token()) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="acao"
                                                value="registrar"
                                            >

                                            <input
                                                type="hidden"
                                                name="pedido_id"
                                                value="<?= $id ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="token_operacao"
                                                value="<?= e($token) ?>"
                                            >

                                            <div class="form-grid-mod">

                                                <div class="campo-mod">
                                                    <label for="valor-<?= $id ?>">
                                                        Valor recebido (máximo
                                                        <?= e(number_format((float) $pedido['saldo_devedor'], 2, '.', '')) ?>)
                                                    </label>

                                                    <input
                                                        id="valor-<?= $id ?>"
                                                        name="valor"
                                                        type="number"
                                                        min="0.01"
                                                        max="<?= e(number_format((float) $pedido['saldo_devedor'], 2, '.', '')) ?>"
                                                        step="0.01"
                                                        required
                                                    >
                                                </div>

                                                <div class="campo-mod">
                                                    <label for="metodo-<?= $id ?>">
                                                        Forma de pagamento
                                                    </label>

                                                    <select
                                                        id="metodo-<?= $id ?>"
                                                        name="metodo"
                                                    >
                                                        <option value="">Não informado</option>
                                                        <option value="pix">PIX</option>
                                                        <option value="cartao">Cartão</option>
                                                        <option value="dinheiro">Dinheiro</option>
                                                        <option value="outro">Outro</option>
                                                    </select>
                                                </div>

                                                <div
                                                    class="campo-mod"
                                                    style="grid-column:1/-1"
                                                >
                                                    <label for="obs-<?= $id ?>">
                                                        Observação (opcional)
                                                    </label>

                                                    <input
                                                        id="obs-<?= $id ?>"
                                                        name="observacao"
                                                        maxlength="255"
                                                        type="text"
                                                        placeholder="Ex.: entrada, parcela 2..."
                                                    >
                                                </div>

                                            </div>

                                            <div class="mod-actions">
                                                <button
                                                    class="btn btn-primary"
                                                    type="submit"
                                                >
                                                    Registrar pagamento
                                                </button>
                                            </div>
                                        </form>

                                    <?php else: ?>

                                        <p class="mod-note">
                                            Pedido totalmente pago. O histórico permanece disponível para consulta.
                                        </p>

                                    <?php endif; ?>

                                </div>
                            </details>

                        <?php endforeach; ?>

                    </div>
                <?php endif; ?>

            </div>
        </details>

    <?php endforeach; ?>

<?php endif; ?>

<script>
(function () {
    'use strict';

    // Após registrar um pagamento, abre o grupo e o cartão do pedido.
    document.addEventListener('DOMContentLoaded', function () {
        var hash = window.location.hash;

        if (!hash || hash.indexOf('#pedido-') !== 0) {
            return;
        }

        var alvo = document.getElementById(hash.substring(1));

        if (!alvo) {
            return;
        }

        var elemento = alvo;

        while (elemento) {
            if (
                elemento.tagName &&
                elemento.tagName.toLowerCase() === 'details'
            ) {
                elemento.open = true;
            }

            elemento = elemento.parentElement;
        }

        alvo.scrollIntoView({ block: 'start' });
    });
})();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
