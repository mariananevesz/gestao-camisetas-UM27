
<?php
require_once __DIR__ . '/../config/bootstrap.php';
app_require_login();

$usuario = app_current_user();
$pedidos = app_system()->listarPedidos($usuario);
$ehGestao = app_is_management();

if ($usuario['perfil'] === 'gestao' && !app_has_permission('pedidos_ver_todos')) {
    $pedidos = array_values(array_filter($pedidos, function ($pedido) use ($usuario) {
        return (int) $pedido['id_usuario_criou'] === (int) $usuario['id']
            || (int) $pedido['id_usuario_responsavel'] === (int) $usuario['id'];
    }));
}

$tituloPagina = $ehGestao ? 'Gestão de Pedidos' : 'Meus Pedidos';
$paginaAtiva = 'gestao';

function classeStatusOperacional($status)
{
    if ($status === 'liberado_producao' || $status === 'lote_gerado') {
        return 'ok';
    }

    if ($status === 'rascunho') {
        return 'warn';
    }

    if ($status === 'enviado' || $status === 'em_conferencia' || $status === 'conferido') {
        return 'info';
    }

    return '';
}

// Mantém o fluxo operacional atual.
$proximoStatus = [
    'enviado' => 'conferido',
    'em_conferencia' => 'conferido',
    'conferido' => 'liberado_producao',
];

// 1. RASCUNHOS
$rascunhos = array_values(array_filter($pedidos, function ($pedido) {
    return $pedido['status_operacional'] === 'rascunho';
}));

// 2. FINALIZADOS
// Só são finalizados os pedidos entregues e totalmente pagos.
$finalizados = array_values(array_filter($pedidos, function ($pedido) {
    return $pedido['status_operacional'] !== 'rascunho'
        && $pedido['status_entrega'] === 'entregue'
        && $pedido['status_pagamento'] === 'pago';
}));

// 3. AGUARDANDO ENTREGA
// O pedido precisa estar liberado para produção (ou incluído em lote),
// totalmente pago e ainda não marcado como entregue.
$aguardandoEntrega = array_values(array_filter($pedidos, function ($pedido) {
    return $pedido['status_operacional'] !== 'rascunho'
        && in_array(
            $pedido['status_operacional'],
            ['liberado_producao', 'lote_gerado'],
            true
        )
        && $pedido['status_pagamento'] === 'pago'
        && $pedido['status_entrega'] !== 'entregue';
}));

// Prepara os IDs dos pedidos que já pertencem a outros grupos.
$idsAguardandoEntrega = array_fill_keys(array_map(function ($pedido) {
    return (int) $pedido['id'];
}, $aguardandoEntrega), true);

$idsFinalizados = array_fill_keys(array_map(function ($pedido) {
    return (int) $pedido['id'];
}, $finalizados), true);

// 4. PEDIDOS EM ABERTO
// Inclui pedidos ainda não liberados para produção,
// pedidos com pagamento pendente ou parcial e pedidos entregues
// cujo pagamento ainda não foi quitado.
$abertos = array_values(array_filter(
    $pedidos,
    function ($pedido) use ($idsAguardandoEntrega, $idsFinalizados) {
        if ($pedido['status_operacional'] === 'rascunho') {
            return false;
        }

        $id = (int) $pedido['id'];

        return !isset($idsAguardandoEntrega[$id])
            && !isset($idsFinalizados[$id]);
    }
));

// Monta os menus na ordem em que aparecerão na tela.
$gruposPedidos = [];

// Rascunhos só aparecem se existir algum.
if (count($rascunhos) > 0) {
    $gruposPedidos[] = [
        'chave' => 'rascunhos',
        'titulo' => 'Rascunhos',
        'descricao' => 'Pedidos ainda não enviados.',
        'pedidos' => $rascunhos,
    ];
}

// Pedidos em aberto.
$gruposPedidos[] = [
    'chave' => 'abertos',
    'titulo' => 'Pedidos em aberto',
    'descricao' => 'Pedidos que ainda não foram liberados para produção ou que não estão totalmente pagos.',
    'pedidos' => $abertos,
];

// Aguardando entrega só aparece quando há pedidos nessa situação.
if (count($aguardandoEntrega) > 0) {
    $gruposPedidos[] = [
        'chave' => 'aguardando-entrega',
        'titulo' => 'Aguardando entrega',
        'descricao' => 'Pedidos liberados para produção e totalmente pagos, aguardando a entrega.',
        'pedidos' => $aguardandoEntrega,
    ];
}

// Finalizados.
$gruposPedidos[] = [
    'chave' => 'finalizados',
    'titulo' => 'Pedidos finalizados',
    'descricao' => 'Pedidos entregues e totalmente pagos. Clique em um pedido para consultar os detalhes.',
    'pedidos' => $finalizados,
];

require __DIR__ . '/partials/header.php';
?>

<div class="page-heading page-heading-com-acao">
    <div>
        <span class="eyebrow">
            <?= $ehGestao ? 'Acompanhamento geral' : 'Área do líder' ?>
        </span>

        <h2><?= $ehGestao ? 'Gestão de Pedidos' : 'Meus Pedidos' ?></h2>

        <p>
            <?= count($pedidos) ?> pedido(s) listado(s).
            Pedidos enviados não podem ser editados nem excluídos.
        </p>
    </div>

    <?php if ($ehGestao && $pedidos): ?>
        <a
            class="btn btn-secondary"
            href="<?= e(app_url('view/pdfPedidos.php')) ?>"
            target="_blank"
            rel="noopener"
        >PDF de todos</a>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/flash.php'; ?>

<?php if (!$pedidos): ?>

    <section class="card mod-empty">
        <h3>Nenhum pedido cadastrado</h3>
        <p>Os pedidos aparecerão aqui depois de serem criados.</p>

        <?php if (app_has_permission('criar_pedido')): ?>
            <a
                class="btn btn-primary"
                href="<?= e(app_url('view/pedidos.php')) ?>"
            >Criar primeiro pedido</a>
        <?php endif; ?>
    </section>

<?php else: ?>

    <section class="pedido-busca" role="search" aria-label="Buscar pedidos">
        <label for="busca-pedidos">Buscar pedido</label>

        <input
            id="busca-pedidos"
            type="search"
            placeholder="Número, comprador, líder ou congregação..."
            autocomplete="off"
            aria-describedby="pedido-busca-feedback"
        >

        <p
            id="pedido-busca-feedback"
            class="mod-muted"
            aria-live="polite"
        >Pesquise por número, comprador, líder ou congregação.</p>
    </section>

    <div
        id="pedido-busca-sem-resultados"
        class="card mod-empty pedido-busca-sem-resultados"
        hidden
    >
        <h3>Nenhum pedido encontrado</h3>
        <p>Tente pesquisar por outro nome, número de pedido ou congregação.</p>
    </div>

    <?php foreach ($gruposPedidos as $grupo): ?>

        <?php
        // Apenas "Pedidos em aberto" começa expandido.
        $grupoAbertoInicialmente = $grupo['chave'] === 'abertos';

        if ($grupo['chave'] === 'finalizados') {
            $classeGrupo = 'ok';
        } elseif ($grupo['chave'] === 'abertos') {
            $classeGrupo = 'warn';
        } elseif ($grupo['chave'] === 'aguardando-entrega') {
            $classeGrupo = 'info';
        } else {
            $classeGrupo = '';
        }
        ?>

        <details
            class="pedido-grupo mod-section pedido-grupo-recolhivel"
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
                    ><?= count($grupo['pedidos']) ?></span>

                    <span class="pedido-grupo-chevron" aria-hidden="true">⌄</span>
                </div>

            </summary>

            <div class="pedido-grupo-conteudo">

                <?php if (!$grupo['pedidos']): ?>

                    <div
                        class="card mod-empty pedido-grupo-vazio"
                        data-group-empty
                    >Nenhum pedido nesta categoria.</div>

                <?php else: ?>

                    <div class="mod-order-list">

                        <?php foreach ($grupo['pedidos'] as $pedido): ?>

                            <?php
                            $id = (int) $pedido['id'];

                            $qtd = array_sum(array_map(function ($item) {
                                return (int) $item['quantidade'];
                            }, $pedido['itens']));

                            $statusOp = $pedido['status_operacional'];
                            $statusPag = $pedido['status_pagamento'];
                            $statusEntrega = $pedido['status_entrega'];

                            $ehPedidoFinalizado = $grupo['chave'] === 'finalizados';

                            $temProximo = $ehGestao && isset($proximoStatus[$statusOp]);

                            $rotuloBotaoStatus = in_array(
                                $statusOp,
                                ['enviado', 'em_conferencia'],
                                true
                            )
                                ? 'Confirmar pedido'
                                : 'Confirmado para produção — OK';

                            // Inclui o nome do novo grupo na busca geral.
                            $textoBusca = implode(' ', array_filter([
                                $grupo['titulo'],
                                $pedido['numero_pedido'] ?? '',
                                'Pedido ' . $id,
                                $pedido['nome_comprador'] ?? '',
                                $pedido['congregacao_nome'] ?? '',
                                $pedido['setor_balcao'] ?? '',
                                $pedido['criador_nome'] ?? '',
                                $pedido['responsavel_nome'] ?? '',
                                rotulo_operacional($statusOp),
                                rotulo_pagamento($statusPag),
                                rotulo_entrega($statusEntrega),
                            ], function ($valor) {
                                return $valor !== null && $valor !== '';
                            }));
                            ?>

                            <?php if ($ehPedidoFinalizado): ?>

                                <details
                                    class="card mod-order-card mod-order-card-finalizado"
                                    data-order-card
                                    data-search="<?= e($textoBusca) ?>"
                                >
                                    <summary class="mod-order-resumo-finalizado">

                                        <div class="mod-order-head">

                                            <div class="mod-order-identificacao">
                                                <span class="mod-muted">
                                                    <?= e($pedido['numero_pedido'] ?: ('Pedido #' . $id)) ?>
                                                    ·
                                                    <?= e(formatar_data_hora($pedido['criado_em'])) ?>
                                                </span>

                                                <h3><?= e($pedido['nome_comprador']) ?></h3>

                                                <p>
                                                    <?= e($pedido['congregacao_nome']) ?>

                                                    <?php if ($pedido['congregacao_tipo'] === 'balcao'): ?>
                                                        · Balcão / Setor
                                                        <?= e($pedido['setor_balcao'] ?: '—') ?>
                                                    <?php endif; ?>
                                                </p>
                                            </div>

                                            <div class="mod-order-tags">
                                                <span class="status-pill <?= e(classeStatusOperacional($statusOp)) ?>">
                                                    <?= e(rotulo_operacional($statusOp)) ?>
                                                </span>

                                                <span class="status-pill ok">
                                                    Pagamento: <?= e(rotulo_pagamento($statusPag)) ?>
                                                </span>

                                                <span class="status-pill ok">
                                                    <?= e(rotulo_entrega($statusEntrega)) ?>
                                                </span>
                                            </div>

                                        </div>

                                        <div class="mod-order-final-toggle">
                                            <span class="mod-order-final-toggle-text">Ver detalhes</span>
                                            <span class="mod-order-final-chevron" aria-hidden="true">⌄</span>
                                        </div>

                                    </summary>

                                    <div class="mod-order-expanded">

                            <?php else: ?>

                                <article
                                    class="card mod-order-card"
                                    data-order-card
                                    data-search="<?= e($textoBusca) ?>"
                                >
                                    <div class="mod-order-head">

                                        <div class="mod-order-identificacao">
                                            <span class="mod-muted">
                                                <?= e($pedido['numero_pedido'] ?: ('Pedido #' . $id)) ?>
                                                ·
                                                <?= e(formatar_data_hora($pedido['criado_em'])) ?>
                                            </span>

                                            <h3><?= e($pedido['nome_comprador']) ?></h3>

                                            <p>
                                                <?= e($pedido['congregacao_nome']) ?>

                                                <?php if ($pedido['congregacao_tipo'] === 'balcao'): ?>
                                                    · Balcão / Setor
                                                    <?= e($pedido['setor_balcao'] ?: '—') ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>

                                        <div class="mod-order-tags">
                                            <span class="status-pill <?= e(classeStatusOperacional($statusOp)) ?>">
                                                <?= e(rotulo_operacional($statusOp)) ?>
                                            </span>

                                            <span class="status-pill <?= $statusPag === 'pago' ? 'ok' : ($statusPag === 'parcial' ? 'info' : 'warn') ?>">
                                                Pagamento: <?= e(rotulo_pagamento($statusPag)) ?>
                                            </span>

                                            <span class="status-pill <?= $statusEntrega === 'entregue' ? 'ok' : 'warn' ?>">
                                                <?= e(rotulo_entrega($statusEntrega)) ?>
                                            </span>
                                        </div>

                                    </div>

                            <?php endif; ?>

                                    <div class="mod-order-data">
                                        <div>
                                            <span>Total</span>
                                            <strong><?= e(formatar_moeda($pedido['valor_total'])) ?></strong>
                                        </div>

                                        <div>
                                            <span>Recebido</span>
                                            <strong><?= e(formatar_moeda($pedido['total_recebido'])) ?></strong>
                                        </div>

                                        <div>
                                            <span>Saldo</span>
                                            <strong><?= e(formatar_moeda($pedido['saldo_devedor'])) ?></strong>
                                        </div>

                                        <div>
                                            <span>Quantidade</span>
                                            <strong><?= (int) $qtd ?> camiseta(s)</strong>
                                        </div>
                                    </div>

                                    <div class="mod-items">
                                        <?php foreach ($pedido['itens'] as $item): ?>
                                            <span class="mod-item-chip">
                                                <?= e($item['modelo'] . ' · ' . $item['tamanho']) ?>
                                                <strong><?= (int) $item['quantidade'] ?></strong>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if ($ehGestao): ?>
                                        <p class="mod-muted">
                                            Lançado por: <?= e($pedido['criador_nome'] ?: 'Registro antigo') ?>
                                            · Responsável: <?= e($pedido['responsavel_nome'] ?: 'Não atribuído') ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="mod-order-actions">

                                        <a
                                            class="btn btn-secondary btn-compacto"
                                            href="<?= e(app_url('view/pdfPedido.php?id=' . $id)) ?>"
                                            target="_blank"
                                            rel="noopener"
                                        >PDF do pedido</a>

                                        <?php if ($statusOp === 'rascunho'): ?>

                                            <a
                                                class="btn btn-secondary btn-compacto"
                                                href="<?= e(app_url('view/editarPedido.php?id=' . $id)) ?>"
                                            >Editar rascunho</a>

                                            <form
                                                action="<?= e(app_url('processamento/processamentoExcluirPedido.php')) ?>"
                                                method="post"
                                                data-confirm="Excluir este rascunho permanentemente?"
                                                class="mod-inline-form"
                                            >
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="pedido_id" value="<?= $id ?>">

                                                <button class="btn btn-secondary btn-compacto" type="submit">
                                                    Excluir rascunho
                                                </button>
                                            </form>

                                        <?php else: ?>

                                            <span class="mod-muted">Dados bloqueados após envio.</span>

                                        <?php endif; ?>

                                        <?php if ($temProximo && app_has_permission('alterar_status_operacional')): ?>

                                            <form
                                                class="mod-inline-form"
                                                action="<?= e(app_url('processamento/processamentoStatus.php')) ?>"
                                                method="post"
                                            >
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="acao" value="operacional">
                                                <input type="hidden" name="pedido_id" value="<?= $id ?>">
                                                <input
                                                    type="hidden"
                                                    name="status_operacional"
                                                    value="<?= e($proximoStatus[$statusOp]) ?>"
                                                >

                                                <button class="btn btn-primary btn-compacto" type="submit">
                                                    <?= e($rotuloBotaoStatus) ?>
                                                </button>
                                            </form>

                                        <?php endif; ?>

                                        <?php if (
                                            $ehGestao
                                            && $statusOp !== 'rascunho'
                                            && app_has_permission('alterar_status_operacional')
                                        ): ?>

                                            <?php if ($statusEntrega === 'aguardando'): ?>

                                                <form
                                                    class="mod-inline-form"
                                                    action="<?= e(app_url('processamento/processamentoStatus.php')) ?>"
                                                    method="post"
                                                >
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="acao" value="entrega">
                                                    <input type="hidden" name="pedido_id" value="<?= $id ?>">
                                                    <input type="hidden" name="status_entrega" value="entregue">

                                                    <button class="btn btn-secondary btn-compacto" type="submit">
                                                        Marcar como entregue
                                                    </button>
                                                </form>

                                            <?php else: ?>

                                                <span class="status-pill ok">Entrega concluída</span>

                                                <form
                                                    class="mod-inline-form"
                                                    action="<?= e(app_url('processamento/processamentoStatus.php')) ?>"
                                                    method="post"
                                                    data-confirm="Corrigir o status da entrega para aguardando?"
                                                >
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="acao" value="entrega">
                                                    <input type="hidden" name="pedido_id" value="<?= $id ?>">
                                                    <input type="hidden" name="status_entrega" value="aguardando">

                                                    <button class="btn btn-secondary btn-compacto" type="submit">
                                                        Corrigir entrega
                                                    </button>
                                                </form>

                                            <?php endif; ?>

                                        <?php endif; ?>

                                        <?php if (
                                            $ehGestao
                                            && app_has_permission('financeiro')
                                            && $statusOp !== 'rascunho'
                                        ): ?>

                                            <a
                                                class="btn btn-secondary btn-compacto"
                                                href="<?= e(app_url('view/financeiro.php#pedido-' . $id)) ?>"
                                            >Registrar pagamento</a>

                                        <?php endif; ?>

                                    </div>

                            <?php if ($ehPedidoFinalizado): ?>

                                    </div>
                                </details>

                            <?php else: ?>

                                </article>

                            <?php endif; ?>

                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </details>

    <?php endforeach; ?>

<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
