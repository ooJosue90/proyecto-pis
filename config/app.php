<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

return [
    'name' => env_value('APP_NAME', 'SembriExport'),
    'environment' => env_value('APP_ENV', 'production'),
    'debug' => filter_var(env_value('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'timezone' => env_value('APP_TIMEZONE', 'America/Guayaquil'),
];
