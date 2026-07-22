<?php

function post_date_or_null(string $field): ?string
{
    $value = trim($_POST[$field] ?? '');

    return $value === '' ? null : $value;
}

function valid_date_or_null(?string $value): bool
{
    if ($value === null) {
        return true;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

    return $date !== false && $date->format('Y-m-d') === $value;
}

function valid_date_range(?string $start, ?string $end): bool
{
    return valid_date_or_null($start)
        && valid_date_or_null($end)
        && ($start === null || $end === null || $start <= $end);
}

function user_owns_cultivo(mysqli $conn, string $userId, int $cultivoId): bool
{
    return (int) db_value(
        $conn,
        "SELECT COUNT(*) FROM cultivos WHERE id_cultivo = ? AND id_usuario = ?",
        "is",
        [$cultivoId, $userId],
        0
    ) > 0;
}

function user_owns_lote(mysqli $conn, string $userId, int $loteId): bool
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

function crop_stage_label(int $stage): string
{
    return match ($stage) {
        1 => 'Siembra',
        2 => 'Desarrollo',
        3 => 'Cosecha',
        default => 'Sin etapa',
    };
}

function crop_status_label(string $status): string
{
    return match ($status) {
        'en_cosecha' => 'En cosecha',
        'finalizado' => 'Finalizado',
        'cancelado' => 'Cancelado',
        default => 'Activo',
    };
}

function crop_status_icon(string $status): string
{
    return match ($status) {
        'en_cosecha' => 'fas fa-wheat-awn',
        'finalizado' => 'fas fa-circle-check',
        'cancelado' => 'fas fa-circle-xmark',
        default => 'fas fa-seedling',
    };
}

function crop_status_symbol(string $status): string
{
    return match ($status) {
        'en_cosecha' => 'agriculture',
        'finalizado' => 'check_circle',
        'cancelado' => 'cancel',
        default => 'eco',
    };
}
