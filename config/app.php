<?php

require_once __DIR__ . '/../config_database.php';

return [
    'app' => [
        'name' => env_required('APP_NAME'),
        'environment' => env_required('APP_ENV'),
    ],
    'database' => config_database(),
];
