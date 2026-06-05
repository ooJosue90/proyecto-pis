<?php

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';

start_secure_session();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $database = app_config('database');

    $conn = new mysqli(
        $database['host'],
        $database['username'],
        $database['password'],
        $database['name'],
        $database['port']
    );
    $conn->set_charset($database['charset']);
} catch (Throwable $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('No se pudo cargar la configuración o conectar con la base de datos.');
}
