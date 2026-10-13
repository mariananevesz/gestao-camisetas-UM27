<?php
require_once __DIR__ . '/../config/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') app_redirect('view/usuarios.php');
app_require_login();
if (!validar_csrf($_POST['csrf_token'] ?? null)) { flash('erro', 'Sua sessão expirou. Atualize a página e tente novamente.'); app_redirect('view/usuarios.php'); }
$acao = (string) ($_POST['acao'] ?? '');
$ator = app_current_user();
try {
    if ($acao === 'alterar_status') {
        $canManage = app_has_permission('usuarios_gerenciar');
        $canApprove = app_has_permission('aprovar_cadastros');
        if (!$canApprove && !$canManage) throw new DomainException('Você não tem permissão para aprovar ou alterar usuários.');
        $status = (string) ($_POST['status'] ?? '');
        $target = app_system()->obterUsuario((int) ($_POST['usuario_id'] ?? 0));
        if (!$target) throw new DomainException('Usuário não encontrado.');
        if ($ator['perfil'] !== 'master' && !$canManage && !($canApprove && $target['perfil'] === 'lider' && $target['status'] === 'aguardando_aprovacao' && in_array($status, ['ativo', 'inativo'], true))) {
            throw new DomainException('Sua permissão permite apenas aprovar ou rejeitar pré-cadastros de líderes.');
        }
        app_system()->alterarStatusUsuario((int) ($_POST['usuario_id'] ?? 0), $status, (int) $ator['id']);
        flash('sucesso', 'Status do usuário atualizado. Se o líder foi aprovado, prepare a mensagem do WhatsApp na lista.');
    } elseif ($acao === 'criar_gestor') {
        if (!app_has_permission('criar_gestores')) throw new DomainException('Somente usuários autorizados pela Master podem criar contas de gestão.');
        $novo = app_system()->criarGestor($_POST, (int) $ator['id']);
        flash('sucesso', 'Usuário de gestão criado. Use o botão Preparar WhatsApp na lista de usuários para enviar os dados de acesso.');
    } elseif ($acao === 'permissoes') {
        if ($ator['perfil'] !== 'master') throw new DomainException('Somente a administradora Master pode definir permissões.');
        $id = (int) ($_POST['usuario_id'] ?? 0);
        $permissoes = $_POST['permissoes'] ?? [];
        $permitidas = ['criar_pedido', 'pedidos_ver_todos', 'financeiro', 'lotes', 'relatorios', 'usuarios_gerenciar', 'aprovar_cadastros', 'criar_gestores', 'criar_pedido_em_nome_de_outro', 'alterar_status_operacional', 'registrar_pagamentos', 'gerar_lotes'];
        foreach ($permitidas as $perm) app_system()->definirPermissao($id, $perm, isset($permissoes[$perm]) && (string) $permissoes[$perm] === '1', (int) $ator['id']);
        flash('sucesso', 'Permissões atualizadas.');
    } else {
        throw new DomainException('Ação administrativa inválida.');
    }
} catch (DomainException $e) {
    flash('erro', $e->getMessage());
} catch (Throwable $e) {
    flash('erro', 'Não foi possível concluir a operação administrativa.');
}
app_redirect('view/usuarios.php');
