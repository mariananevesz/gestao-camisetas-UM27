<?php
require_once __DIR__ . '/../config/bootstrap.php';
app_require_login();
if (!app_has_permission('usuarios_gerenciar') && !app_has_permission('aprovar_cadastros') && !app_has_permission('criar_gestores')) {
    flash('erro', 'Você não tem permissão para gerenciar usuários.');
    app_redirect('view/gestaoPedidos.php');
}
$usuarioAtual = app_current_user();
$canManageUsers = app_has_permission('usuarios_gerenciar');
$canApproveUsers = app_has_permission('aprovar_cadastros');
$usuarios = app_system()->listarUsuarios();
if ($usuarioAtual['perfil'] !== 'master' && !$canManageUsers) {
    $usuarios = $canApproveUsers ? array_values(array_filter($usuarios, function ($u) { return $u['perfil'] === 'lider' && ($u['status'] === 'aguardando_aprovacao' || ($u['status'] === 'ativo' && (int) $u['senha_inicial_ativa'] === 1)); })) : [];
}
$permissoesGestao = [
    'criar_pedido' => 'Criar pedidos',
    'pedidos_ver_todos' => 'Ver todos os pedidos',
    'financeiro' => 'Acessar financeiro',
    'registrar_pagamentos' => 'Registrar pagamentos',
    'lotes' => 'Acessar lotes do fornecedor',
    'gerar_lotes' => 'Gerar lotes do fornecedor',
    'relatorios' => 'Acessar relatórios',
    'criar_pedido_em_nome_de_outro' => 'Criar pedido em nome de líder',
    'alterar_status_operacional' => 'Avançar etapas e atualizar entrega',
    'usuarios_gerenciar' => 'Acessar gestão de usuários',
    'aprovar_cadastros' => 'Aprovar cadastros de líderes',
    'criar_gestores' => 'Criar usuários de gestão',
];
$padroesGestao = ['criar_pedido', 'pedidos_ver_todos', 'financeiro', 'registrar_pagamentos', 'lotes', 'gerar_lotes', 'relatorios', 'criar_pedido_em_nome_de_outro', 'alterar_status_operacional'];
$tituloPagina = 'Usuários e permissões';
$paginaAtiva = 'usuarios';
require __DIR__ . '/partials/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">Acessos ao sistema</span><h2>Usuários</h2><p>Pré-cadastros, aprovação de líderes, contas de gestão e permissões.</p></div></div>
<?php require __DIR__ . '/partials/flash.php'; ?>
<?php if (app_has_permission('criar_gestores')): ?>
<section class="card mod-section"><div class="mod-card-heading"><h3>Criar usuário de gestão</h3></div><p class="mod-muted">Informe um número de WhatsApp com DDD. Depois de criar a conta, você poderá abrir a conversa e preparar a mensagem de acesso na lista de usuários.</p><form action="<?= e(app_url('processamento/processamentoAdmin.php')) ?>" method="post" class="form-stack"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="acao" value="criar_gestor"><div class="form-grid-mod"><div class="campo-mod"><label for="nome_completo">Nome completo</label><input id="nome_completo" name="nome_completo" maxlength="180" autocomplete="name" required></div><div class="campo-mod"><label for="email">E-mail (login)</label><input id="email" name="email" type="email" maxlength="190" autocomplete="email" required></div><div class="campo-mod"><label for="telefone">Telefone / WhatsApp com DDD</label><input id="telefone" name="telefone" type="tel" inputmode="tel" autocomplete="tel" placeholder="(11) 99999-9999" maxlength="25" required></div><div class="campo-mod"><label for="data_nascimento">Data de nascimento</label><input id="data_nascimento" name="data_nascimento" type="date" max="<?= e(date('Y-m-d')) ?>" autocomplete="bday" required></div></div><div class="mod-actions"><button class="btn btn-primary" type="submit">Criar usuário de gestão</button></div></form></section>
<?php endif; ?>
<?php if ($canManageUsers || $canApproveUsers || $usuarioAtual['perfil'] === 'master'): ?><section class="card mod-section"><div class="mod-card-heading"><h3>Lista de usuários</h3><span class="status-pill info"><?= count($usuarios) ?> usuário(s)</span></div>
<?php if (!$usuarios): ?><div class="mod-empty">Ainda não há usuários cadastrados.</div><?php else: ?><div class="mod-table-wrap"><table class="mod-table"><thead><tr><th>Usuário / contato</th><th>Perfil</th><th>Congregação</th><th>Status</th><th>Ações / mensagem</th></tr></thead><tbody>
<?php foreach ($usuarios as $u): $isMaster = $u['perfil'] === 'master'; $statusClass = $u['status'] === 'ativo' ? 'ok' : ($u['status'] === 'aguardando_aprovacao' ? 'warn' : 'danger'); $telefoneWa = telefone_para_whatsapp($u['telefone'] ?? ''); ?>
<tr><td><strong><?= e($u['nome_completo']) ?></strong><br><span class="mod-muted"><?= e($u['email']) ?></span><br><span class="mod-muted">WhatsApp: <?= e(formatar_telefone_usuario($u['telefone'] ?? '')) ?></span><br><span class="mod-muted">Nasc.: <?= e(date('d/m/Y', strtotime($u['data_nascimento']))) ?></span></td><td><?= e(ucfirst($u['perfil'])) ?></td><td><?= e($u['congregacao_nome'] ?: '—') ?></td><td><span class="status-pill <?= $statusClass ?>"><?= e(str_replace('_', ' ', $u['status'])) ?></span></td><td>
<?php if (!$isMaster && ($canManageUsers || ($canApproveUsers && $u['perfil'] === 'lider' && $u['status'] === 'aguardando_aprovacao'))): ?>
<form action="<?= e(app_url('processamento/processamentoAdmin.php')) ?>" method="post" class="mod-inline-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="acao" value="alterar_status"><input type="hidden" name="usuario_id" value="<?= (int) $u['id'] ?>"><select name="status" aria-label="Novo status"><option value="ativo" <?= $u['status'] === 'ativo' ? 'selected' : '' ?>>Ativo / aprovar</option><option value="inativo" <?= $u['status'] === 'inativo' ? 'selected' : '' ?>>Inativo / rejeitar</option><?php if ($canManageUsers || $usuarioAtual['perfil'] === 'master'): ?><option value="bloqueado" <?= $u['status'] === 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option><?php endif; ?></select><button class="btn btn-secondary btn-compacto" type="submit">Salvar</button></form>
<?php endif; ?>
<?php
$podePrepararMensagem = ($u['perfil'] === 'lider' && $u['status'] === 'ativo' && (int) $u['senha_inicial_ativa'] === 1 && ($usuarioAtual['perfil'] === 'master' || $canApproveUsers))
    || ($u['perfil'] === 'gestao' && $u['status'] === 'ativo' && (int) $u['senha_inicial_ativa'] === 1 && app_has_permission('criar_gestores'));
if ($podePrepararMensagem && $telefoneWa !== ''):
    $senhaInicialMensagem = date('dmY', strtotime($u['data_nascimento']));
    $mensagemAcesso = $u['perfil'] === 'gestao' ? 'Seu acesso de gestão ao sistema UMADEPRESP 2027 foi criado.' : 'Seu pré-cadastro no sistema UMADEPRESP 2027 foi aprovado.';
    $mensagem = 'Olá, ' . $u['nome_completo'] . '! ' . $mensagemAcesso . ' Login: ' . $u['email'] . '. Senha inicial: ' . $senhaInicialMensagem . '. Guarde seus dados com cuidado.';
?>
<div class="mod-actions"><a class="btn btn-secondary btn-compacto" target="_blank" rel="noopener" href="https://wa.me/<?= e($telefoneWa) ?>?text=<?= e(rawurlencode($mensagem)) ?>">Preparar WhatsApp</a></div>
<?php elseif ($podePrepararMensagem): ?><p class="mod-muted">Sem telefone válido para abrir o WhatsApp.</p><?php endif; ?>
</td></tr>
<?php if ($usuarioAtual['perfil'] === 'master' && $u['perfil'] === 'gestao'): $salvas = app_system()->listarPermissoesUsuario((int) $u['id']); ?>
<tr><td colspan="5"><details><summary>Permissões de <?= e($u['nome_completo']) ?></summary><form action="<?= e(app_url('processamento/processamentoAdmin.php')) ?>" method="post" class="form-stack" style="margin-top:14px"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="acao" value="permissoes"><input type="hidden" name="usuario_id" value="<?= (int) $u['id'] ?>"><div class="mod-checkbox-list">
<?php foreach ($permissoesGestao as $perm => $rotulo): $marcado = array_key_exists($perm, $salvas) ? $salvas[$perm] : in_array($perm, $padroesGestao, true); ?>
<label class="mod-check"><input type="hidden" name="permissoes[<?= e($perm) ?>]" value="0"><input type="checkbox" name="permissoes[<?= e($perm) ?>]" value="1" <?= $marcado ? 'checked' : '' ?>><span><?= e($rotulo) ?></span></label>
<?php endforeach; ?></div><div class="mod-actions"><button class="btn btn-primary" type="submit">Salvar permissões</button></div></form></details></td></tr><?php endif; ?>
<?php endforeach; ?></tbody></table></div><?php endif; ?></section><?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
