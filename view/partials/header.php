<?php
$tituloPagina = $tituloPagina ?? 'Sistema';
$paginaAtiva = $paginaAtiva ?? '';
$usuarioLogado = app_current_user();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#111827">
    <title><?= e($tituloPagina) ?> | UMADEPRESP 2027</title>
    <link rel="stylesheet" href="<?= e(app_url('css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(app_url('css/modules.css')) ?>">
</head>
<body>
<div class="app-shell <?= $usuarioLogado ? '' : 'app-shell-publica' ?>">
    <?php if ($usuarioLogado): ?>
    <aside class="sidebar" aria-label="Menu principal">
        <div class="sidebar-brand"><strong>UMADEPRESP</strong><span>Gestão de camisetas 2027</span></div>
        <nav class="sidebar-nav">
            <?php if (app_has_permission('criar_pedido')): ?>
            <a class="nav-link <?= $paginaAtiva === 'pedidos' ? 'ativo' : '' ?>" href="<?= e(app_url('view/pedidos.php')) ?>"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg><span>Novo pedido</span></a>
            <?php endif; ?>
            <a class="nav-link <?= $paginaAtiva === 'gestao' ? 'ativo' : '' ?>" href="<?= e(app_url('view/gestaoPedidos.php')) ?>"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h10"/></svg><span><?= $usuarioLogado['perfil'] === 'lider' ? 'Meus pedidos' : 'Gestão de pedidos' ?></span></a>
            <?php if (app_has_permission('financeiro')): ?><a class="nav-link <?= $paginaAtiva === 'financeiro' ? 'ativo' : '' ?>" href="<?= e(app_url('view/financeiro.php')) ?>"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 6h18v12H3zM3 10h18M7 15h4"/></svg><span>Financeiro</span></a><?php endif; ?>
            <?php if (app_has_permission('lotes')): ?><a class="nav-link <?= $paginaAtiva === 'lotes' ? 'ativo' : '' ?>" href="<?= e(app_url('view/lotes.php')) ?>"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5ZM3 12l9 5 9-5M3 16l9 5 9-5"/></svg><span>Lotes fornecedor</span></a><?php endif; ?>
            <?php if (app_has_permission('relatorios')): ?><a class="nav-link <?= $paginaAtiva === 'relatorios' ? 'ativo' : '' ?>" href="<?= e(app_url('view/relatorios.php')) ?>"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 20V10M12 20V4M19 20v-7"/></svg><span>Relatórios</span></a><?php endif; ?>
            <?php if (app_has_permission('usuarios_gerenciar') || app_has_permission('aprovar_cadastros') || app_has_permission('criar_gestores')): ?><a class="nav-link <?= $paginaAtiva === 'usuarios' ? 'ativo' : '' ?>" href="<?= e(app_url('view/usuarios.php')) ?>"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM20 8v6M17 11h6"/></svg><span>Usuários</span></a><?php endif; ?>
            <a class="nav-link <?= $paginaAtiva === 'conta' ? 'ativo' : '' ?>" href="<?= e(app_url('view/minhaConta.php')) ?>"><svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg><span>Minha conta</span></a>
        </nav>
        <div class="sidebar-bottom"><span><?= e($usuarioLogado['nome_completo']) ?></span><small><?= e(ucfirst($usuarioLogado['perfil'])) ?></small><form action="<?= e(app_url('processamento/processamentoAuth.php')) ?>" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="acao" value="logout"><button class="btn btn-secondary btn-compacto" type="submit">Sair</button></form></div>
    </aside>
    <?php endif; ?>
    <div class="app-main <?= $usuarioLogado ? '' : 'app-main-publica' ?>">
        <header class="topbar">
            <h1><?= e($usuarioLogado ? 'Central de pedidos' : 'UMADEPRESP 2027') ?></h1>
            <?php if ($usuarioLogado): ?><div class="topbar-user"><span><?= e($usuarioLogado['nome_completo']) ?></span><small><?= e(ucfirst($usuarioLogado['perfil'])) ?></small></div><?php endif; ?>
        </header>
        <main class="content">
