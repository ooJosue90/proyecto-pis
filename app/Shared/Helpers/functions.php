<?php

declare(strict_types=1);

function app_config(?string $key = null, $default = null)
{
    static $config = null;

    if ($config === null) {
        $projectRoot = dirname(__DIR__, 3);
        $config = [
            'app' => require $projectRoot . '/config/app.php',
            'database' => require $projectRoot . '/config/database.php',
            'permissions' => require $projectRoot . '/config/permissions.php',
        ];
    }

    if ($key === null) {
        return $config;
    }

    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header("Location: {$path}");
    exit();
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION[$key] = $message;
        return null;
    }

    if (!isset($_SESSION[$key])) {
        return null;
    }

    $value = $_SESSION[$key];
    unset($_SESSION[$key]);

    return $value;
}

function current_user_name(): string
{
    return $_SESSION['nombre'] ?? 'Usuario';
}

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (function_exists('start_secure_session')) {
            start_secure_session();
        } else {
            session_start();
        }
    }
    if (!isset($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}
