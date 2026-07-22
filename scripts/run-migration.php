<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$migrationName = $argv[1] ?? '';
$migrationPath = realpath(__DIR__ . '/../migrations/' . basename($migrationName));
$migrationsDirectory = realpath(__DIR__ . '/../migrations');

if (!$migrationPath || !$migrationsDirectory || !str_starts_with($migrationPath, $migrationsDirectory)) {
    fwrite(STDERR, "Migración no encontrada.\n");
    exit(1);
}

require_once __DIR__ . '/../conexion.php';

$sql = file_get_contents($migrationPath);
if ($sql === false || trim($sql) === '') {
    fwrite(STDERR, "La migración está vacía.\n");
    exit(1);
}

try {
    $conn->multi_query($sql);
    do {
        $result = $conn->store_result();
        if ($result instanceof mysqli_result) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());

    fwrite(STDOUT, "Migración aplicada: " . basename($migrationPath) . PHP_EOL);
} catch (Throwable $exception) {
    fwrite(STDERR, "No se pudo aplicar la migración: " . $exception->getMessage() . PHP_EOL);
    exit(1);
}
