<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$allowedEndpoints = [
    'admin.php',
    'admin_usuarios.php',
    'admin_solicitudes.php',
    'admin_movimientos.php',
    'admin_facturas.php',
    'admin_reportes.php',
    'admin_cultivos.php',
    'admin_pedidos_proveedores.php',
];

$endpoint = basename($argv[1] ?? '');
if (!in_array($endpoint, $allowedEndpoints, true)) {
    fwrite(STDERR, "Endpoint administrativo no permitido.\n");
    exit(1);
}

require_once __DIR__ . '/../conexion.php';

$admin = db_fetch_one(
    $conn,
    "SELECT id_usuario, nombre, email, rol FROM usuarios WHERE rol = 'Administrador' LIMIT 1"
);
if (!$admin) {
    fwrite(STDERR, "No existe un administrador para ejecutar la prueba.\n");
    exit(1);
}

$_SESSION['id_usuario'] = $admin['id_usuario'];
$_SESSION['nombre'] = $admin['nombre'];
$_SESSION['email'] = $admin['email'];
$_SESSION['rol'] = $admin['rol'];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = [];
$_POST = [];

ob_start();
require __DIR__ . '/../' . $endpoint;
$html = ob_get_clean();

if (trim($html) === '') {
    fwrite(STDERR, "El endpoint no generó contenido: {$endpoint}\n");
    exit(1);
}

fwrite(STDOUT, $endpoint . ': ' . strlen($html) . " bytes\n");
