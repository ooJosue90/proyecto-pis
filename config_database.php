<?php

require_once __DIR__ . '/config/env.php';

function config_database(): array
{
    env_load();

    foreach (['DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME', 'DB_PORT', 'DB_CHARSET'] as $key) {
        if (!array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER) && getenv($key) === false) {
            throw new RuntimeException("La variable de entorno {$key} no está configurada.");
        }
    }

    return [
        'host' => env_required('DB_HOST'),
        'username' => env_required('DB_USER'),
        'password' => (string) ($_ENV['DB_PASSWORD'] ?? $_SERVER['DB_PASSWORD'] ?? getenv('DB_PASSWORD')),
        'name' => env_required('DB_NAME'),
        'port' => (int) env_required('DB_PORT'),
        'charset' => env_required('DB_CHARSET'),
    ];
}
