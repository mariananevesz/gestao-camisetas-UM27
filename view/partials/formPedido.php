<?php
$modo = $modo ?? 'cadastrar';
$pedido = $pedido ?? null;
$dadosFormulario = $dadosFormulario ?? [];
$usuarioAtual = app_current_user();
$ehGestao = app_is_management();
$nomeComprador = $dadosFormulario['nome_comprador'] ?? ($pedido['nome_comprador'] ?? '');
$telefone = $dadosFormulario['telefone'] ?? ($pedido['telefone'] ?? '');
$congregacaoId = (int) ($dadosFormulario['congregacao_id'] ?? ($pedido['congregacao_id'] ?? ($usuarioAtual['congregacao_id'] ?? 0)));
$responsavelId = (int) ($dadosFormulario['responsavel_id'] ?? ($pedido['id_usuario_responsavel'] ?? $usuarioAtual['id']));
$setorBalcao = (string) ($dadosFormulario['setor_balcao'] ?? ($pedido['setor_balcao'] ?? ''));
$quantidadesAtuais = [];
if ($pedido && !empty($pedido['itens'])) foreach ($pedido['itens'] as $item) $quantidadesAtuais[$item['camiseta_id']] = (int) $item['quantidade'];
if (!empty($dadosFormulario['quantidades']) && is_array($dadosFormulario['quantidades'])) $quantidadesAtuais = array_map('intval', $dadosFormulario['quantidades']);
$contagemNomes = array_count_values(array_map(function ($c) { return $c['nome']; }, $congregacoes));
$rotuloCongregacao = function ($c) use ($contagemNomes) {
    $rotulo = $c['nome'];
    if (($c['tipo'] ?? '') === 'balcao') return 'Balcão — Venda de balcão';
    if (($contagemNomes[$c['nome']] ?? 0) > 1 && !empty($c['setor'])) $rotulo .= ' — Setor ' . $c['setor'];
    return $rotulo;
};
$congregacaoSelecionada = '';
foreach ($congregacoes as $congregacao) if ((int) $congregacao['id'] === $congregacaoId) { $congregacaoSelecionada = $rotuloCongregacao($congregacao); break; }
$camisetasPorModelo = [];
foreach ($camisetas as $camiseta) $camisetasPorModelo[$camiseta['modelo']][] = $camiseta;
$lideres = $lideres ?? [];
?>
<form class="pedido-form" action="<?= e(app_url('processamento/processamentoPedido.php')) ?>" method="POST" data-pedido-form>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="acao" value="<?= $modo === 'editar' ? 'editar' : 'criar' ?>">
    <?php if ($modo === 'editar'): ?><input type="hidden" name="pedido_id" value="<?= (int) $pedido['id'] ?>"><?php endif; ?>

    <section class="card bloco-formulario" aria-labelledby="titulo-comprador">
        <div class="section-heading"><div><h2 id="titulo-comprador">Dados do comprador</h2></div></div>
        <?php if ($ehGestao && app_has_permission('criar_pedido_em_nome_de_outro')): ?>
        <div class="campo-mod mod-section"><label for="responsavel_id">Pedido em nome de líder (opcional)</label>
            <select id="responsavel_id" name="responsavel_id"><option value="">Pedido registrado pela gestão</option>
            <?php foreach ($lideres as $lider): ?><option value="<?= (int) $lider['id'] ?>" data-congregacao-id="<?= (int) $lider['congregacao_id'] ?>" <?= $responsavelId === (int) $lider['id'] ? 'selected' : '' ?>><?= e($lider['nome_completo'] . ' — ' . $lider['congregacao_nome']) ?></option><?php endforeach; ?>
            </select><small>Ao escolher um líder, o pedido fica associado a ele e à congregação dele. A pessoa que lançou o pedido continua registrada separadamente.</small>
        </div>
        <?php elseif (!$ehGestao): ?><input type="hidden" name="responsavel_id" value="<?= (int) $usuarioAtual['id'] ?>"><?php endif; ?>
        <div class="form-grid comprador-grid">
            <div class="campo campo-amplo"><label for="nome_comprador">Nome completo do comprador</label><input id="nome_comprador" name="nome_comprador" type="text" maxlength="150" value="<?= e($nomeComprador) ?>" placeholder="Nome completo" autocomplete="name" required><span class="campo-erro" data-erro-campo="nome_comprador"></span></div>
            <?php if ($ehGestao): ?>
            <div class="campo combobox" data-combobox><label for="congregacao_busca">Congregação / origem</label><div class="combobox-input-wrap"><input id="congregacao_busca" type="search" value="<?= e($congregacaoSelecionada) ?>" placeholder="Digite para pesquisar" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="lista-congregacoes" aria-autocomplete="list" aria-haspopup="listbox" spellcheck="false" required><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m7 10 5 5 5-5"/></svg></div>
                <input type="hidden" id="congregacao_id" name="congregacao_id" value="<?= $congregacaoId ?: '' ?>">
                <div id="lista-congregacoes" class="combobox-lista" role="listbox" hidden><?php foreach ($congregacoes as $c): ?><button type="button" role="option" data-option-id="<?= (int) $c['id'] ?>" data-option-label="<?= e($rotuloCongregacao($c)) ?>"><?= e($rotuloCongregacao($c)) ?></button><?php endforeach; ?><p class="combobox-vazio" data-combobox-vazio hidden>Nenhuma congregação encontrada.</p></div><span class="campo-erro" data-erro-campo="congregacao_id"></span>
            </div>
            <?php else: ?>
            <div class="campo"><label>Congregação</label><input type="text" value="<?= e($usuarioAtual['congregacao_nome'] ?? $congregacaoSelecionada) ?>" readonly><input type="hidden" id="congregacao_id" name="congregacao_id" value="<?= (int) $usuarioAtual['congregacao_id'] ?>"></div>
            <?php endif; ?>
            <div class="campo"><label for="telefone">Telefone</label><input id="telefone" name="telefone" type="tel" maxlength="20" value="<?= e($telefone) ?>" placeholder="(18) 99999-9999" autocomplete="tel" required><span class="campo-erro" data-erro-campo="telefone"></span></div>
        </div>
        <?php if ($ehGestao): ?>
        <div class="form-grid-mod mod-section" data-balcao-fields <?= $congregacaoId === 1 ? '' : 'hidden' ?> style="margin-top:16px">
            <div class="campo-mod"><label for="setor_balcao">Setor do Balcão</label><select id="setor_balcao" name="setor_balcao"><option value="">Selecione o setor</option><?php foreach ($setores as $setor): ?><option value="<?= e($setor['setor']) ?>" <?= $setorBalcao === (string) $setor['setor'] ? 'selected' : '' ?>>Setor <?= e($setor['setor']) ?></option><?php endforeach; ?></select><small>O pedido será classificado como Balcão e não entrará no ranking das congregações.</small></div>
        </div>
        <?php endif; ?>
    </section>

    <section class="card bloco-formulario" aria-labelledby="titulo-camisetas"><div class="section-heading"><div><h2 id="titulo-camisetas">Camisetas</h2></div></div>
        <div class="modelos-grid"><?php foreach ($camisetasPorModelo as $modelo => $itensModelo): ?><section class="modelo-card"><h3><?= e($modelo) ?></h3><div class="camisetas-lista"><?php foreach ($itensModelo as $camiseta): $quantidade = (int) ($quantidadesAtuais[$camiseta['id']] ?? 0); ?><div class="camiseta-linha" data-camiseta-linha data-preco="<?= e($camiseta['valor']) ?>"><span class="tamanho-label"><?= e($camiseta['tamanho']) ?></span><span class="preco-label"><?= e(formatar_moeda($camiseta['valor'])) ?></span><div class="quantidade-controle" aria-label="Quantidade de <?= e($modelo . ' ' . $camiseta['tamanho']) ?>"><button type="button" class="quantidade-btn" data-quantidade-menos aria-label="Diminuir quantidade">−</button><input type="number" inputmode="numeric" min="0" max="999" readonly name="quantidades[<?= (int) $camiseta['id'] ?>]" value="<?= $quantidade ?>" aria-label="Quantidade"><button type="button" class="quantidade-btn" data-quantidade-mais aria-label="Aumentar quantidade">+</button></div></div><?php endforeach; ?></div></section><?php endforeach; ?></div><span class="campo-erro erro-itens" data-erro-campo="quantidades"></span>
    </section>

    <section class="card resumo-pedido" aria-labelledby="titulo-resumo"><div class="section-heading"><div><h2 id="titulo-resumo">Resumo do pedido</h2></div></div><div class="resumo-valores"><div><span>Total de camisetas</span><strong data-total-itens>0</strong></div><div><span>Valor total</span><strong data-total-valor>R$ 0,00</strong></div></div></section>

    <section class="card acao-salvar"><div class="section-heading compacta"><div><h2><?= $modo === 'editar' ? 'Salvar rascunho ou enviar' : 'Como deseja salvar?' ?></h2><p>Depois de enviar, os dados originais ficam bloqueados. Correções devem ser feitas em um novo pedido.</p></div></div>
      <div class="mod-actions"><button class="btn btn-secondary" type="submit" name="intencao" value="rascunho">Salvar como rascunho</button><button class="btn btn-primary btn-grande" type="submit" name="intencao" value="enviar"><?= $modo === 'editar' ? 'Salvar e enviar pedido' : 'Salvar e enviar pedido' ?></button></div>
    </section>
</form>
