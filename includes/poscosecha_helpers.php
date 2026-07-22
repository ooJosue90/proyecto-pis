<?php

function poscosecha_estados(): array
{
    return ['Recepción', 'Lavado', 'Clasificación', 'Empaque', 'Almacenamiento', 'Finalizada'];
}

function poscosecha_destinos(): array
{
    return ['Exportación', 'Mercado nacional', 'Procesamiento'];
}

function poscosecha_estado_index(string $estado): int
{
    $index = array_search($estado, poscosecha_estados(), true);

    return $index === false ? -1 : (int) $index;
}

function poscosecha_next_estado(string $estado): ?string
{
    $estados = poscosecha_estados();
    $index = poscosecha_estado_index($estado);

    return $index >= 0 && isset($estados[$index + 1]) ? $estados[$index + 1] : null;
}

function poscosecha_previous_estado(string $estado): ?string
{
    $estados = poscosecha_estados();
    $index = poscosecha_estado_index($estado);

    return $index > 0 ? $estados[$index - 1] : null;
}

function poscosecha_estado_badge(string $estado): string
{
    return match ($estado) {
        'Recepción' => 'info',
        'Lavado' => 'primary',
        'Clasificación' => 'success',
        'Empaque' => 'warning text-dark',
        'Almacenamiento' => 'secondary',
        'Finalizada' => 'dark',
        default => 'light text-dark',
    };
}

function poscosecha_estado_icon(string $estado): string
{
    return match ($estado) {
        'Recepción' => 'fas fa-warehouse',
        'Lavado' => 'fas fa-droplet',
        'Clasificación' => 'fas fa-list-check',
        'Empaque' => 'fas fa-box-open',
        'Almacenamiento' => 'fas fa-boxes-stacked',
        'Finalizada' => 'fas fa-circle-check',
        default => 'fas fa-clock',
    };
}

function poscosecha_require_role(array $allowedRoles): void
{
    require_auth();

    if (!in_array((string) ($_SESSION['rol'] ?? ''), $allowedRoles, true)) {
        flash('error', 'No tienes permisos para acceder al módulo de poscosecha.');
        redirect(dashboard_for_role((string) ($_SESSION['rol'] ?? '')));
    }
}

function poscosecha_valid_date(?string $value): bool
{
    if ($value === null || $value === '') {
        return false;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

    return $date !== false && $date->format('Y-m-d') === $value;
}

function poscosecha_float(string $field): float
{
    $value = str_replace(',', '.', trim((string) ($_POST[$field] ?? '0')));

    return round((float) $value, 2);
}

function poscosecha_validate_amounts(array $data): ?string
{
    $numericFields = [
        'kg_recibidos',
        'kg_lavados',
        'kg_clasificados',
        'kg_primera',
        'kg_segunda',
        'kg_descarte',
        'kg_merma',
        'kg_exportacion',
        'kg_mercado_nacional',
        'kg_procesamiento',
    ];

    foreach ($numericFields as $field) {
        if ((float) $data[$field] < 0) {
            return 'Los kilogramos no pueden ser negativos.';
        }
    }

    if ((float) $data['kg_recibidos'] <= 0) {
        return 'Los kilogramos recibidos deben ser mayores que cero.';
    }

    if ((float) $data['kg_clasificados'] > (float) $data['kg_recibidos']) {
        return 'Los kilogramos clasificados no pueden superar los kilogramos recibidos.';
    }

    $calidadTotal = round(
        (float) $data['kg_primera']
        + (float) $data['kg_segunda']
        + (float) $data['kg_descarte']
        + (float) $data['kg_merma'],
        2
    );

    if ($calidadTotal > (float) $data['kg_recibidos']) {
        return 'Primera, segunda, descarte y merma no pueden superar los kilogramos recibidos.';
    }

    $destinoTotal = round(
        (float) $data['kg_exportacion']
        + (float) $data['kg_mercado_nacional']
        + (float) $data['kg_procesamiento'],
        2
    );

    if ($destinoTotal > (float) $data['kg_recibidos']) {
        return 'La suma por destino no puede superar los kilogramos recibidos.';
    }

    if ((float) $data['kg_merma'] > 0 && trim((string) $data['motivo_merma']) === '') {
        return 'Ingrese el motivo de la merma registrada.';
    }

    return null;
}

function poscosecha_notify_role(mysqli $conn, string $role, string $message): void
{
    db_execute(
        $conn,
        "INSERT INTO notificaciones (mensaje, rol_destino, leida, fecha) VALUES (?, ?, 0, NOW())",
        "ss",
        [$message, $role]
    );
}
