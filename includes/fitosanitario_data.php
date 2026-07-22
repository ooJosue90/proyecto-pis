<?php

function fitosanitario_lotes_for_farmer(mysqli $conn, string $userId): array
{
    return db_fetch_all(
        $conn,
        "SELECT l.id_lote, l.ubicacion, l.area, c.tipo AS cultivo
         FROM lotes l
         INNER JOIN cultivos c ON l.id_cultivo = c.id_cultivo
         WHERE c.id_usuario = ?
         ORDER BY l.id_lote DESC",
        "s",
        [$userId]
    );
}

function fitosanitario_all_lotes(mysqli $conn): array
{
    return db_fetch_all(
        $conn,
        "SELECT l.id_lote, l.ubicacion, l.area, c.tipo AS cultivo, u.nombre AS agricultor
         FROM lotes l
         LEFT JOIN cultivos c ON l.id_cultivo = c.id_cultivo
         LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
         ORDER BY l.id_lote DESC"
    );
}

function fitosanitario_inventory_products(mysqli $conn): array
{
    return db_fetch_all(
        $conn,
        "SELECT id_insumos, nombre, tipo, tipo_producto, ingrediente_activo,
                presentacion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario,
                dosis_recomendada, unidad_dosis, unidad_aplicacion,
                intervalo_seguridad, periodo_reingreso
         FROM insumos_agricolas
         WHERE uso_fitosanitario = 1
           AND tipo_producto IN ('Fungicidas', 'Insecticidas', 'Herbicidas', 'Coadyuvantes', 'Trampas')
         ORDER BY tipo_producto ASC, nombre ASC"
    );
}

function fitosanitario_records(mysqli $conn, string $role, string $userId): array
{
    $sql = "SELECT cf.*, l.ubicacion AS lote_ubicacion, l.area AS lote_area,
                   c.tipo AS cultivo_tipo, u.nombre AS usuario_responsable,
                   ia.cantidad AS stock_disponible, ia.unidad_medida AS stock_unidad,
                   ia.dosis_recomendada, ia.unidad_dosis, ia.unidad_aplicacion
            FROM control_fitosanitario cf
            INNER JOIN lotes l ON cf.id_lote = l.id_lote
            LEFT JOIN cultivos c ON l.id_cultivo = c.id_cultivo
            LEFT JOIN usuarios u ON cf.id_usuario = u.id_usuario
            LEFT JOIN insumos_agricolas ia ON cf.id_insumo = ia.id_insumos";

    if ($role === 'Agricultor') {
        $sql .= " WHERE c.id_usuario = ?";
        $types = "s";
        $params = [$userId];
    } else {
        $types = "";
        $params = [];
    }

    $sql .= " ORDER BY cf.fecha_registro DESC, cf.id_control DESC";

    return db_fetch_all($conn, $sql, $types, $params);
}

function fitosanitario_admin_stats(mysqli $conn): array
{
    $row = db_fetch_one(
        $conn,
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN estado = 'Pendiente' THEN 1 ELSE 0 END) AS pendientes,
            SUM(CASE WHEN estado = 'En tratamiento' THEN 1 ELSE 0 END) AS en_tratamiento,
            SUM(CASE WHEN estado = 'Controlado' THEN 1 ELSE 0 END) AS controlados,
            SUM(CASE WHEN severidad = 'Alta' THEN 1 ELSE 0 END) AS severidad_alta
         FROM control_fitosanitario"
    );

    return [
        'total' => (int) ($row['total'] ?? 0),
        'pendientes' => (int) ($row['pendientes'] ?? 0),
        'en_tratamiento' => (int) ($row['en_tratamiento'] ?? 0),
        'controlados' => (int) ($row['controlados'] ?? 0),
        'severidad_alta' => (int) ($row['severidad_alta'] ?? 0),
    ];
}

function fitosanitario_record(mysqli $conn, int $idControl): ?array
{
    return db_fetch_one(
        $conn,
        "SELECT cf.*, l.ubicacion AS lote_ubicacion, l.area AS lote_area,
                c.tipo AS cultivo_tipo, c.id_usuario AS id_agricultor,
                u.nombre AS usuario_responsable, u.email AS usuario_email,
                ia.cantidad AS stock_disponible, ia.unidad_medida AS stock_unidad,
                ia.dosis_recomendada, ia.unidad_dosis, ia.unidad_aplicacion
         FROM control_fitosanitario cf
         INNER JOIN lotes l ON cf.id_lote = l.id_lote
         LEFT JOIN cultivos c ON l.id_cultivo = c.id_cultivo
         LEFT JOIN usuarios u ON cf.id_usuario = u.id_usuario
         LEFT JOIN insumos_agricolas ia ON cf.id_insumo = ia.id_insumos
         WHERE cf.id_control = ?",
        "i",
        [$idControl]
    );
}

function fitosanitario_treatments(mysqli $conn, int $idControl): array
{
    return db_fetch_all(
        $conn,
        "SELECT cft.*, u.nombre AS usuario_responsable,
                ua.nombre AS usuario_aprobacion,
                ue.nombre AS usuario_entrega,
                ia.cantidad AS stock_disponible, ia.unidad_medida AS stock_unidad,
                ia.dosis_recomendada, ia.unidad_dosis, ia.unidad_aplicacion
         FROM control_fitosanitario_tratamientos cft
         LEFT JOIN usuarios u ON cft.id_usuario = u.id_usuario
         LEFT JOIN usuarios ua ON cft.id_usuario_aprobacion = ua.id_usuario
         LEFT JOIN usuarios ue ON cft.id_usuario_entrega = ue.id_usuario
         LEFT JOIN insumos_agricolas ia ON cft.id_insumo = ia.id_insumos
         WHERE cft.id_control = ?
         ORDER BY cft.fecha_aplicacion DESC, cft.fecha_registro DESC",
        "i",
        [$idControl]
    );
}
