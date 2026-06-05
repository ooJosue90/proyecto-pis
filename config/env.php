<?php

use Dotenv\Dotenv;

function env_load(): void
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $root = dirname(__DIR__);
    $autoload = $root . '/vendor/autoload.php';

    if (!file_exists($autoload)) {
        throw new RuntimeException(
            'No se encontró vendor/autoload.php. Instala dependencias con Composer antes de iniciar el proyecto.'
        );
    }

    require_once $autoload;

    if (!class_exists(Dotenv::class)) {
        throw new RuntimeException('La librería vlucas/phpdotenv no está instalada correctamente.');
    }

    Dotenv::createImmutable($root)->safeLoad();
    $loaded = true;
}

function env_value(string $key, ?string $default = null): ?string
{
    env_load();

    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

function env_required(string $key): string
{
    $value = env_value($key);

    if ($value === null) {
        throw new RuntimeException("La variable de entorno {$key} no está configurada.");
    }

    return $value;
}
