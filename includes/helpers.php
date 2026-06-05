<?php

function app_config(?string $key = null, $default = null)
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/../config/app.php';
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
