<?php

require_once __DIR__ . '/helpers.php';

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $sessionPath = __DIR__ . '/../storage/sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0775, true);
    }

    if (is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }

    session_start();
}

function require_auth(?string $role = null): void
{
    start_secure_session();

    if (!isset($_SESSION['id_usuario'], $_SESSION['rol'])) {
        redirect('login.html');
    }

    if ($role !== null && $_SESSION['rol'] !== $role) {
        flash('error', 'No tienes permisos para realizar esta acción.');
        redirect(dashboard_for_role((string) $_SESSION['rol']));
    }
}

function login_user(array $user): void
{
    start_secure_session();
    session_regenerate_id(true);

    $_SESSION['id_usuario'] = $user['id_usuario'];
    $_SESSION['nombre'] = $user['nombre'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['rol'] = $user['rol'];
}

function dashboard_for_role(string $role): string
{
    $routes = [
        'Administrador' => 'admin.php',
        'Agricultor' => 'agricultor.php',
        'Bodeguero' => 'bodeguero.php',
    ];

    return $routes[$role] ?? 'index.html';
}

function logout_user(): void
{
    start_secure_session();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
