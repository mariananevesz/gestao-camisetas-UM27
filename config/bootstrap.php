<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

define('APP_ROOT', dirname(__DIR__));
$GLOBALS['app_config'] = require APP_ROOT . '/config/config.php';
date_default_timezone_set($GLOBALS['app_config']['app']['timezone']);

require_once APP_ROOT . '/model/BancoDeDados.php';
require_once APP_ROOT . '/model/Congregacao.php';
require_once APP_ROOT . '/model/Camiseta.php';
require_once APP_ROOT . '/model/ItemPedido.php';
require_once APP_ROOT . '/model/Pedido.php';
require_once APP_ROOT . '/controller/Controlador.php';
require_once APP_ROOT . '/model/Sistema.php';

function app_config($chave = null)
{
    $config = $GLOBALS['app_config'];
    return $chave === null ? $config : ($config[$chave] ?? null);
}

function app_controller()
{
    static $controlador = null;
    if ($controlador === null) $controlador = new Controlador(app_config('database'));
    return $controlador;
}

function app_system()
{
    static $sistema = null;
    if ($sistema === null) $sistema = new Sistema(app_config('database'));
    return $sistema;
}

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function validar_csrf($token)
{
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function flash($tipo, $mensagem)
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function obter_flashes()
{
    $mensagens = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensagens;
}

function salvar_formulario_anterior($dados)
{
    $_SESSION['formulario_anterior'] = $dados;
}

function obter_formulario_anterior()
{
    $dados = $_SESSION['formulario_anterior'] ?? [];
    unset($_SESSION['formulario_anterior']);
    return $dados;
}

function redirecionar($destino)
{
    header('Location: ' . $destino);
    exit;
}

function app_url($caminho = '')
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $partes = explode('/', trim($script, '/'));
    if (count($partes) >= 2 && in_array($partes[count($partes) - 2], ['view', 'processamento'], true)) {
        array_pop($partes);
        array_pop($partes);
    } else {
        array_pop($partes);
    }
    $raiz = $partes ? '/' . implode('/', $partes) : '';
    return $raiz . '/' . ltrim($caminho, '/');
}

function app_redirect($caminho)
{
    redirecionar(app_url($caminho));
}

function app_current_user()
{
    if (empty($_SESSION['usuario']['id'])) return null;
    try {
        $usuario = app_system()->obterUsuario((int) $_SESSION['usuario']['id']);
        if (!$usuario || $usuario['status'] !== 'ativo') {
            unset($_SESSION['usuario']);
            return null;
        }
        unset($usuario['senha_hash']);
        $_SESSION['usuario'] = $usuario;
        return $usuario;
    } catch (Throwable $e) {
        unset($_SESSION['usuario']);
        return null;
    }
}

function app_login(array $usuario)
{
    session_regenerate_id(true);
    unset($usuario['senha_hash']);
    $_SESSION['usuario'] = $usuario;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function app_logout()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function app_require_login()
{
    if (!app_current_user()) {
        flash('erro', 'Entre com seu e-mail e senha para continuar.');
        app_redirect('view/login.php');
    }
}

function app_has_permission($permissao)
{
    $usuario = app_current_user();
    if (!$usuario) return false;
    if ($usuario['perfil'] === 'master') return true;
    $override = app_system()->obterPermissao((int) $usuario['id'], $permissao);
    if ($override !== null) return (bool) $override;
    $padroes = [
        'gestao' => [
            'criar_pedido' => true,
            'pedidos_ver_todos' => true,
            'financeiro' => true,
            'lotes' => true,
            'relatorios' => true,
            'criar_pedido_em_nome_de_outro' => true,
            'alterar_status_operacional' => true,
            'registrar_pagamentos' => true,
            'gerar_lotes' => true,
        ],
        'lider' => [
            'criar_pedido' => true,
            'meus_pedidos' => true,
        ],
    ];
    return !empty($padroes[$usuario['perfil']][$permissao]);
}

function app_require_permission($permissao)
{
    app_require_login();
    if (!app_has_permission($permissao)) {
        flash('erro', 'Seu perfil não tem permissão para acessar esta área.');
        app_redirect('view/gestaoPedidos.php');
    }
}

function app_is_management()
{
    $u = app_current_user();
    return $u && in_array($u['perfil'], ['master', 'gestao'], true);
}

function formatar_moeda($valor)
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function formatar_telefone_usuario($telefone)
{
    $digitos = preg_replace('/\D+/', '', (string) $telefone);
    if ((strlen($digitos) === 12 || strlen($digitos) === 13) && substr($digitos, 0, 2) === '55') $digitos = substr($digitos, 2);
    if (strlen($digitos) === 11) return '(' . substr($digitos, 0, 2) . ') ' . substr($digitos, 2, 5) . '-' . substr($digitos, 7);
    if (strlen($digitos) === 10) return '(' . substr($digitos, 0, 2) . ') ' . substr($digitos, 2, 4) . '-' . substr($digitos, 6);
    return $telefone ? (string) $telefone : '—';
}

function telefone_para_whatsapp($telefone)
{
    $digitos = preg_replace('/\D+/', '', (string) $telefone);
    if ((strlen($digitos) === 12 || strlen($digitos) === 13) && substr($digitos, 0, 2) === '55') return $digitos;
    if (strlen($digitos) === 10 || strlen($digitos) === 11) return '55' . $digitos;
    return '';
}

function formatar_data_hora($data)
{
    if (!$data) return '—';
    $instante = strtotime($data);
    return $instante ? date('d/m/Y', $instante) . ' às ' . date('H:i', $instante) : '—';
}

function rotulo_entrega($status)
{
    return $status === 'entregue' ? 'Entregue' : 'Aguardando entrega';
}

function rotulo_pagamento($status)
{
    if ($status === 'pago') return 'Pago';
    if ($status === 'parcial') return 'Parcial';
    return 'Pendente';
}

function rotulo_operacional($status)
{
    $rotulos = [
        'rascunho' => 'Rascunho',
        'enviado' => 'Enviado',
        'em_conferencia' => 'Em conferência',
        'conferido' => 'Pedido confirmado',
        'liberado_producao' => 'Liberado para produção',
        'lote_gerado' => 'Incluído em lote',
    ];
    return $rotulos[$status] ?? 'Indefinido';
}
