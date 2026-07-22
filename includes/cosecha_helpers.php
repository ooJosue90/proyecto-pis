<?php

function cosecha_estado_badge(string $estado): string
{
    return match ($estado) {
        'Validada' => 'success',
        'Rechazada' => 'danger',
        'Recibida' => 'primary',
        default => 'warning text-dark',
    };
}

function cosecha_estado_icon(string $estado): string
{
    return match ($estado) {
        'Validada' => 'fas fa-circle-check',
        'Rechazada' => 'fas fa-circle-xmark',
        'Recibida' => 'fas fa-warehouse',
        default => 'fas fa-clock',
    };
}

function cosecha_valid_date(?string $value): bool
{
    if ($value === null || $value === '') {
        return false;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

    return $date !== false && $date->format('Y-m-d') === $value;
}

function cosecha_float(string $field): float
{
    $value = str_replace(',', '.', trim((string) ($_POST[$field] ?? '0')));

    return round((float) $value, 2);
}

function cosecha_validate_amounts(float $total, float $primera, float $segunda, float $descarte): ?string
{
    if ($total <= 0) {
        return 'La cantidad total cosechada debe ser mayor que cero.';
    }

    if ($primera < 0 || $segunda < 0 || $descarte < 0) {
        return 'Las cantidades por calidad no pueden ser negativas.';
    }

    if (round($primera + $segunda + $descarte, 2) > $total) {
        return 'La suma de primera, segunda y descarte no puede superar la cantidad total.';
    }

    return null;
}

function cosecha_user_owns_lote(mysqli $conn, string $userId, int $loteId): bool
{
    return (int) db_value(
        $conn,
        "SELECT COUNT(*)
         FROM lotes l
         INNER JOIN cultivos c ON l.id_cultivo = c.id_cultivo
         WHERE l.id_lote = ? AND c.id_usuario = ?",
        "is",
        [$loteId, $userId],
        0
    ) > 0;
}

function cosecha_notify_role(mysqli $conn, string $role, string $message): void
{
    db_execute(
        $conn,
        "INSERT INTO notificaciones (mensaje, rol_destino, leida, fecha) VALUES (?, ?, 0, NOW())",
        "ss",
        [$message, $role]
    );
}

function cosecha_require_role(array $allowedRoles): void
{
    require_auth();

    if (!in_array((string) ($_SESSION['rol'] ?? ''), $allowedRoles, true)) {
        flash('error', 'No tienes permisos para acceder al módulo de cosecha.');
        redirect(dashboard_for_role((string) ($_SESSION['rol'] ?? '')));
    }
}
