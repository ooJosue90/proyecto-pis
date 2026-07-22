<?php

function cosecha_lotes_for_farmer(mysqli $conn, string $userId): array
{
    return db_fetch_all(
        $conn,
        "SELECT l.id_lote, l.ubicacion, l.area, l.estado_cultivo, c.tipo AS cultivo
         FROM lotes l
         INNER JOIN cultivos c ON l.id_cultivo = c.id_cultivo
         LEFT JOIN cosechas co
            ON co.id_lote = l.id_lote
           AND co.estado IN ('Registrada', 'Validada', 'Recibida')
         WHERE c.id_usuario = ?
           AND l.estado_cultivo = 'en_cosecha'
           AND co.id_cosecha IS NULL
         ORDER BY l.id_lote DESC",
        "s",
        [$userId]
    );
}

function cosecha_records(mysqli $conn, string $role, string $userId): array
{
    $where = '';
    $types = '';
    $params = [];

    if ($role === 'Agricultor') {
        $where = 'WHERE co.id_usuario = ?';
        $types = 's';
        $params = [$userId];
    } elseif ($role === 'Bodeguero') {
        $where = "WHERE co.estado IN ('Validada', 'Recibida')";
    }

    return db_fetch_all(
        $conn,
        "SELECT co.*,
                u.nombre AS agricultor_nombre,
                uv.nombre AS validador_nombre,
                ur.nombre AS receptor_nombre,
                l.ubicacion AS lote_ubicacion,
                l.area AS lote_area,
                c.tipo AS cultivo_tipo
         FROM cosechas co
         INNER JOIN usuarios u ON co.id_usuario = u.id_usuario
         LEFT JOIN usuarios uv ON co.id_usuario_valida = uv.id_usuario
         LEFT JOIN usuarios ur ON co.id_usuario_recibe = ur.id_usuario
         INNER JOIN lotes l ON co.id_lote = l.id_lote
         LEFT JOIN cultivos c ON l.id_cultivo = c.id_cultivo
         {$where}
         ORDER BY co.fecha_cosecha DESC, co.id_cosecha DESC",
        $types,
        $params
    );
}

function cosecha_metrics(mysqli $conn): array
{
    $row = db_fetch_one(
        $conn,
        "SELECT
            COUNT(*) AS total_cosechas,
            SUM(CASE WHEN estado = 'Registrada' THEN 1 ELSE 0 END) AS pendientes,
            COALESCE(SUM(CASE WHEN estado IN ('Validada', 'Recibida') THEN cantidad_total_kg ELSE 0 END), 0) AS kg_validados,
            COALESCE(SUM(CASE WHEN estado IN ('Validada', 'Recibida') THEN calidad_primera_kg ELSE 0 END), 0) AS kg_primera,
            COALESCE(SUM(CASE WHEN estado IN ('Validada', 'Recibida') THEN calidad_segunda_kg ELSE 0 END), 0) AS kg_segunda,
            COALESCE(SUM(CASE WHEN estado IN ('Validada', 'Recibida') THEN descarte_kg ELSE 0 END), 0) AS kg_descarte
         FROM cosechas"
    ) ?? [];

    return [
        'total_cosechas' => (int) ($row['total_cosechas'] ?? 0),
        'cosechas_pendientes' => (int) ($row['pendientes'] ?? 0),
        'kg_validados' => (float) ($row['kg_validados'] ?? 0),
        'kg_primera' => (float) ($row['kg_primera'] ?? 0),
        'kg_segunda' => (float) ($row['kg_segunda'] ?? 0),
        'kg_descarte' => (float) ($row['kg_descarte'] ?? 0),
    ];
}

function cosecha_pending_admin_records(mysqli $conn): array
{
    return db_fetch_all(
        $conn,
        "SELECT co.id_cosecha, co.fecha_cosecha, co.cantidad_total_kg,
                u.nombre AS agricultor_nombre, l.ubicacion AS lote_ubicacion, c.tipo AS cultivo_tipo
         FROM cosechas co
         INNER JOIN usuarios u ON co.id_usuario = u.id_usuario
         INNER JOIN lotes l ON co.id_lote = l.id_lote
         LEFT JOIN cultivos c ON l.id_cultivo = c.id_cultivo
         WHERE co.estado = 'Registrada'
         ORDER BY co.fecha_registro DESC
         LIMIT 8"
    );
}
