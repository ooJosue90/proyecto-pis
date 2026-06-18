<?php

require_once 'conexion.php';
require_auth('Agricultor');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Los datos enviados no son válidos.']);
    exit();
}

$usuarioId = (string) $_SESSION['id_usuario'];
$idCultivo = (int) ($data['id_cultivo'] ?? 0);
$ubicacion = trim((string) ($data['ubicacion'] ?? ''));
$area = (float) ($data['area'] ?? 0);
$etapas = is_array($data['etapas'] ?? null) ? $data['etapas'] : [];
$plagas = is_array($data['plagas'] ?? null) ? $data['plagas'] : [];

$cultivoPertenece = (int) db_value(
    $conn,
    "SELECT COUNT(*) FROM cultivos WHERE id_cultivo = ? AND id_usuario = ?",
    "is",
    [$idCultivo, $usuarioId],
    0
) > 0;

if ($idCultivo <= 0 || $ubicacion === '' || $area <= 0 || !$cultivoPertenece) {
    http_response_code(422);
    echo json_encode(['error' => 'Revise el cultivo, la ubicación y el área del lote.']);
    exit();
}

$etapaActual = (int) ($data['etapa_actual'] ?? 0);

if (!in_array($etapaActual, [1, 2, 3], true)) {
    // Compatibilidad con clientes antiguos que envían nombres de etapas.
    $etapaActual = in_array('Cosecha', $etapas, true)
        ? 3
        : (in_array('Desarrollo', $etapas, true) || in_array('Riego', $etapas, true)
            ? 2
            : 1);
}

$etapaSiembra = $etapaActual >= 1 ? 1 : 0;
$etapaRiego = $etapaActual >= 2 ? 1 : 0;
$etapaCosecha = $etapaActual === 3 ? 1 : 0;
$estadoCultivo = $etapaActual === 3 ? 'en_cosecha' : 'activo';

$conn->begin_transaction();

try {
    db_execute(
        $conn,
        "INSERT INTO lotes (
            id_cultivo, ubicacion, area, etapa_actual, estado_cultivo,
            etapa_riego, etapa_siembra, etapa_cosecha, fecha_registro
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
        "isdisiii",
        [$idCultivo, $ubicacion, $area, $etapaActual, $estadoCultivo, $etapaRiego, $etapaSiembra, $etapaCosecha]
    );

    $loteId = $conn->insert_id;
    $plagasRegistradas = [];

    foreach ($plagas as $plaga) {
        $nombre = trim((string) (is_array($plaga) ? ($plaga['nombre'] ?? '') : $plaga));

        if ($nombre === '' || in_array($nombre, $plagasRegistradas, true)) {
            continue;
        }

        db_execute(
            $conn,
            "INSERT INTO plagas (id_lote, id_usuario, nombre) VALUES (?, ?, ?)",
            "iss",
            [$loteId, $usuarioId, $nombre]
        );
        $plagasRegistradas[] = $nombre;
    }

    $conn->commit();
    echo json_encode(['success' => true, 'id_lote' => $loteId], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    $conn->rollback();
    error_log('Error al guardar lote: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo guardar el lote.']);
}
