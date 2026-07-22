<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

return [
    'host' => env_required('DB_HOST'),
    'username' => env_required('DB_USER'),
    'password' => env_value('DB_PASSWORD', ''),
    'name' => env_required('DB_NAME'),
    'port' => (int) env_value('DB_PORT', '3306'),
    'charset' => env_value('DB_CHARSET', 'utf8mb4'),
];
