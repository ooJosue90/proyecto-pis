<?php

function poscosecha_records(mysqli $conn, string $role, string $userId): array
{
    $where = '';
    $types = '';
    $params = [];

    if ($role === 'Agricultor') {
        $where = 'WHERE co.id_usuario = ?';
        $types = 's';
        $params = [$userId];
    }

    return db_fetch_all(
        $conn,
        "SELECT p.*,
                co.fecha_cosecha,
                co.cantidad_total_kg AS cosecha_total_kg,
                u.nombre AS agricultor_nombre,
                r.nombre AS responsable_nombre,
                l.ubicacion AS lote_ubicacion,
                l.area AS lote_area,
                c.tipo AS cultivo_tipo
         FROM poscosecha p
         INNER JOIN cosechas co ON p.id_cosecha = co.id_cosecha
         INNER JOIN usuarios u ON co.id_usuario = u.id_usuario
         INNER JOIN usuarios r ON p.id_responsable = r.id_usuario
         INNER JOIN lotes l ON p.id_lote = l.id_lote
         LEFT JOIN cultivos c ON l.id_cultivo = c.id_cultivo
         {$where}
         ORDER BY p.fecha_ingreso DESC, p.id_poscosecha DESC",
        $types,
        $params
    );
}

function poscosecha_available_cosechas(mysqli $conn): array
{
    return db_fetch_all(
        $conn,
        "SELECT co.id_cosecha, co.id_lote, co.id_usuario, co.fecha_cosecha,
                co.cantidad_total_kg, u.nombre AS agricultor_nombre,
                l.ubicacion AS lote_ubicacion, c.tipo AS cultivo_tipo
         FROM cosechas co
         INNER JOIN usuarios u ON co.id_usuario = u.id_usuario
         INNER JOIN lotes l ON co.id_lote = l.id_lote
         LEFT JOIN cultivos c ON l.id_cultivo = c.id_cultivo
         LEFT JOIN poscosecha p ON p.id_cosecha = co.id_cosecha
         WHERE co.estado = 'Recibida'
           AND p.id_poscosecha IS NULL
         ORDER BY co.fecha_recepcion DESC, co.id_cosecha DESC"
    );
}

function poscosecha_metrics(mysqli $conn): array
{
    $row = db_fetch_one(
        $conn,
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN estado = 'Recepción' THEN 1 ELSE 0 END) AS recepcion,
            SUM(CASE WHEN estado IN ('Lavado','Clasificación','Empaque','Almacenamiento') THEN 1 ELSE 0 END) AS en_curso,
            SUM(CASE WHEN estado = 'Finalizada' THEN 1 ELSE 0 END) AS finalizadas,
            SUM(CASE WHEN estado = 'Finalizada' AND listo_para_despacho = 1 THEN 1 ELSE 0 END) AS listas_despacho,
            COALESCE(SUM(CASE WHEN estado <> 'Finalizada' THEN kg_recibidos ELSE 0 END), 0) AS kg_en_poscosecha,
            COALESCE(SUM(kg_primera), 0) AS kg_primera,
            COALESCE(SUM(kg_segunda), 0) AS kg_segunda,
            COALESCE(SUM(kg_descarte), 0) AS kg_descarte,
            COALESCE(SUM(kg_merma), 0) AS kg_merma
         FROM poscosecha"
    ) ?? [];

    return [
        'total' => (int) ($row['total'] ?? 0),
        'recepcion' => (int) ($row['recepcion'] ?? 0),
        'en_curso' => (int) ($row['en_curso'] ?? 0),
        'finalizadas' => (int) ($row['finalizadas'] ?? 0),
        'listas_despacho' => (int) ($row['listas_despacho'] ?? 0),
        'kg_en_poscosecha' => (float) ($row['kg_en_poscosecha'] ?? 0),
        'kg_primera' => (float) ($row['kg_primera'] ?? 0),
        'kg_segunda' => (float) ($row['kg_segunda'] ?? 0),
        'kg_descarte' => (float) ($row['kg_descarte'] ?? 0),
        'kg_merma' => (float) ($row['kg_merma'] ?? 0),
    ];
}

function poscosecha_history(mysqli $conn, int $idPoscosecha): array
{
    return db_fetch_all(
        $conn,
        "SELECT h.id_etapa, h.id_poscosecha,
                h.etapa_anterior AS estado_anterior,
                h.etapa_nueva AS estado_nuevo,
                h.id_usuario,
                h.observacion AS observaciones,
                h.fecha_cambio,
                u.nombre AS usuario_nombre
         FROM poscosecha_etapas h
         INNER JOIN usuarios u ON h.id_usuario = u.id_usuario
         WHERE h.id_poscosecha = ?
         ORDER BY h.fecha_cambio DESC, h.id_etapa DESC",
        "i",
        [$idPoscosecha]
    );
}
