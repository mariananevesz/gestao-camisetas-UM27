<?php
require_once __DIR__ . '/../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') app_redirect('view/login.php');
if (!validar_csrf($_POST['csrf_token'] ?? null)) {
    flash('erro', 'Sua sessão expirou. Atualize a página e tente novamente.');
    app_redirect('view/login.php');
}

$acao = (string) ($_POST['acao'] ?? '');
$agora = time();
$chaveTentativas = 'tentativas_' . ($acao === 'login' ? 'login' : ($acao === 'recuperar' ? 'recuperar' : 'cadastro'));
$_SESSION[$chaveTentativas] = array_values(array_filter($_SESSION[$chaveTentativas] ?? [], function ($t) use ($agora) { return $t > $agora - 900; }));
$acoesPublicas = ['login', 'recuperar', 'precadastro'];
$emailBruto = trim((string) ($_POST['email'] ?? ''));
$emailLimite = function_exists('mb_strtolower') ? mb_strtolower($emailBruto, 'UTF-8') : strtolower($emailBruto);
$chaveLimite = hash('sha256', $acao . '|' . $emailLimite . '|' . (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
if (in_array($acao, $acoesPublicas, true) && (!app_system()->acessoPermitido($chaveLimite) || count($_SESSION[$chaveTentativas]) >= 8)) {
    flash('erro', 'Muitas tentativas em pouco tempo. Aguarde 15 minutos e tente novamente.');
    app_redirect($acao === 'precadastro' ? 'view/precadastro.php' : ($acao === 'recuperar' ? 'view/recuperarSenha.php' : 'view/login.php'));
}

try {
    if ($acao === 'login') {
        $usuario = app_system()->autenticar($_POST['email'] ?? '', $_POST['senha'] ?? '');
        if (!$usuario) {
            $_SESSION[$chaveTentativas][] = $agora;
            throw new DomainException('E-mail ou senha inválidos, ou conta ainda não aprovada/ativa.');
        }
        unset($_SESSION[$chaveTentativas]);
        app_system()->limparLimiteAcesso($chaveLimite);
        app_login($usuario);
        app_redirect('view/gestaoPedidos.php');
    }

    if ($acao === 'logout') {
        app_require_login();
        app_logout();
        session_start();
        flash('sucesso', 'Você saiu do sistema.');
        app_redirect('view/login.php');
    }

    if ($acao === 'precadastro') {
        app_system()->cadastrarPreCadastro($_POST);
        unset($_SESSION[$chaveTentativas]);
        app_system()->limparLimiteAcesso($chaveLimite);
        flash('sucesso', 'Pré-cadastro enviado. Aguarde a aprovação da administradora Master; seus dados de acesso serão enviados ao WhatsApp informado.');
        app_redirect('view/login.php');
    }

    if ($acao === 'recuperar') {
        $nova = (string) ($_POST['nova_senha'] ?? '');
        $confirmar = (string) ($_POST['confirmar_senha'] ?? '');
        if (!hash_equals($nova, $confirmar)) throw new DomainException('A confirmação da nova senha não confere.');
        app_system()->recuperarSenha($_POST['email'] ?? '', $_POST['data_nascimento'] ?? '', $nova);
        unset($_SESSION[$chaveTentativas]);
        app_system()->limparLimiteAcesso($chaveLimite);
        flash('sucesso', 'Senha redefinida. Agora você pode entrar com o e-mail e a nova senha.');
        app_redirect('view/login.php');
    }

    if ($acao === 'alterar_senha') {
        app_require_login();
        $usuario = app_current_user();
        $nova = (string) ($_POST['nova_senha'] ?? '');
        $confirmar = (string) ($_POST['confirmar_senha'] ?? '');
        if (!hash_equals($nova, $confirmar)) throw new DomainException('A confirmação da nova senha não confere.');
        app_system()->alterarSenhaUsuario((int) $usuario['id'], (string) ($_POST['senha_atual'] ?? ''), $nova);
        flash('sucesso', 'Senha alterada com sucesso.');
        app_redirect('view/minhaConta.php');
    }

    throw new DomainException('Ação inválida.');
} catch (DomainException $e) {
    if (in_array($acao, $acoesPublicas, true)) app_system()->registrarFalhaAcesso($chaveLimite);
    flash('erro', $e->getMessage());
} catch (Throwable $e) {
    if (in_array($acao, $acoesPublicas, true)) { try { app_system()->registrarFalhaAcesso($chaveLimite); } catch (Throwable $ignored) {} }
    flash('erro', 'Não foi possível concluir a operação. Confira os dados e tente novamente.');
}

$destino = $acao === 'precadastro' ? 'view/precadastro.php' : ($acao === 'recuperar' ? 'view/recuperarSenha.php' : ($acao === 'alterar_senha' ? 'view/minhaConta.php' : 'view/login.php'));
app_redirect($destino);
