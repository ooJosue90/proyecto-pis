<?php

require_once __DIR__ . '/farmer_helpers.php';

function fitosanitario_allowed_roles(): array
{
    return ['Administrador', 'Agricultor', 'Bodeguero'];
}

function fitosanitario_require_access(): void
{
    require_auth();

    if (!in_array($_SESSION['rol'] ?? '', fitosanitario_allowed_roles(), true)) {
        flash('error', 'No tienes permisos para acceder al módulo fitosanitario.');
        redirect(dashboard_for_role((string) ($_SESSION['rol'] ?? '')));
    }
}

function fitosanitario_is_valid_tipo(string $tipo): bool
{
    return in_array($tipo, ['Plaga', 'Enfermedad', 'Hongo', 'Otro'], true);
}

function fitosanitario_is_valid_severidad(string $severidad): bool
{
    return in_array($severidad, ['Baja', 'Media', 'Alta'], true);
}

function fitosanitario_is_valid_estado(string $estado): bool
{
    return in_array($estado, ['Pendiente', 'En tratamiento', 'Controlado'], true);
}

function fitosanitario_status_tone(string $estado): string
{
    return match ($estado) {
        'Controlado' => 'success',
        'En tratamiento' => 'info',
        default => 'warning',
    };
}

function fitosanitario_severity_tone(string $severidad): string
{
    return match ($severidad) {
        'Alta' => 'danger',
        'Media' => 'warning',
        default => 'success',
    };
}

function fitosanitario_record_owner_id(mysqli $conn, int $idControl): ?string
{
    return db_value(
        $conn,
        "SELECT id_usuario FROM control_fitosanitario WHERE id_control = ?",
        "i",
        [$idControl],
        null
    );
}

function fitosanitario_record_belongs_to_farmer(mysqli $conn, string $userId, int $idControl): bool
{
    return (int) db_value(
        $conn,
        "SELECT COUNT(*)
         FROM control_fitosanitario cf
         INNER JOIN lotes l ON cf.id_lote = l.id_lote
         INNER JOIN cultivos c ON l.id_cultivo = c.id_cultivo
         WHERE cf.id_control = ? AND c.id_usuario = ?",
        "is",
        [$idControl, $userId],
        0
    ) > 0;
}

function fitosanitario_can_view(mysqli $conn, string $role, string $userId, int $idControl): bool
{
    if ($role === 'Administrador' || $role === 'Bodeguero') {
        return (int) db_value(
            $conn,
            "SELECT COUNT(*) FROM control_fitosanitario WHERE id_control = ?",
            "i",
            [$idControl],
            0
        ) > 0;
    }

    return $role === 'Agricultor'
        && fitosanitario_record_belongs_to_farmer($conn, $userId, $idControl);
}

function fitosanitario_can_edit(mysqli $conn, string $role, string $userId, int $idControl): bool
{
    $record = db_fetch_one(
        $conn,
        "SELECT estado, id_usuario FROM control_fitosanitario WHERE id_control = ?",
        "i",
        [$idControl]
    );

    if (!$record) {
        return false;
    }

    if ($role === 'Administrador') {
        return true;
    }

    return $role === 'Agricultor'
        && $record['estado'] !== 'Controlado'
        && fitosanitario_record_belongs_to_farmer($conn, $userId, $idControl);
}

function fitosanitario_can_add_treatment(mysqli $conn, string $role, string $userId, int $idControl): bool
{
    if ($role === 'Administrador') {
        return fitosanitario_can_view($conn, $role, $userId, $idControl);
    }

    return fitosanitario_can_edit($conn, $role, $userId, $idControl);
}

function fitosanitario_notify_high_severity(mysqli $conn, int $idLote, string $problem, string $fechaDeteccion): void
{
    $lote = db_fetch_one(
        $conn,
        "SELECT l.ubicacion, c.tipo AS cultivo
         FROM lotes l
         LEFT JOIN cultivos c ON l.id_cultivo = c.id_cultivo
         WHERE l.id_lote = ?",
        "i",
        [$idLote]
    );

    $loteLabel = 'Lote #' . $idLote;
    if ($lote) {
        $loteLabel .= ' - ' . $lote['ubicacion'];
        if (!empty($lote['cultivo'])) {
            $loteLabel .= ' (' . $lote['cultivo'] . ')';
        }
    }

    $message = sprintf(
        'Alerta fitosanitaria alta: %s detectado en %s el %s.',
        $problem,
        $loteLabel,
        $fechaDeteccion
    );

    db_execute(
        $conn,
        "INSERT INTO notificaciones (mensaje, leida, fecha) VALUES (?, 0, NOW())",
        "s",
        [$message]
    );
}

function fitosanitario_inventory_product(mysqli $conn, int $idInsumo): ?array
{
    if ($idInsumo <= 0) {
        return null;
    }

    return db_fetch_one(
        $conn,
        "SELECT id_insumos, nombre, tipo, tipo_producto, ingrediente_activo,
                presentacion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario,
                dosis_recomendada, unidad_dosis, unidad_aplicacion,
                intervalo_seguridad, periodo_reingreso
         FROM insumos_agricolas
         WHERE id_insumos = ?
           AND uso_fitosanitario = 1
           AND tipo_producto IN ('Fungicidas', 'Insecticidas', 'Herbicidas', 'Coadyuvantes', 'Trampas')",
        "i",
        [$idInsumo]
    );
}
