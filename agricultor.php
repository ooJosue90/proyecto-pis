<?php
require_once 'conexion.php';
require_auth('Agricultor');

$id_usuario = $_SESSION['id_usuario'];

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

// Finalizar cosecha y registrar la producción real
if (($_POST['accion'] ?? '') === 'finalizar_cosecha') {
    $id_lote = (int) ($_POST['id_lote'] ?? 0);
    $cantidad_cosechada = (float) ($_POST['cantidad_cosechada'] ?? 0);
    $unidad_cosecha = trim($_POST['unidad_cosecha'] ?? '');
    $fecha_cosecha = post_date_or_null('fecha_cosecha');
    $observacion = trim($_POST['observacion'] ?? '');

    if ($id_lote <= 0 || !user_owns_lote($conn, $id_usuario, $id_lote)) {
        flash('error', 'Seleccione un lote válido.');
        redirect('agricultor.php');
    }

    if ($cantidad_cosechada <= 0) {
        flash('error', 'La cantidad cosechada debe ser mayor que cero.');
        redirect('agricultor.php');
    }

    if ($unidad_cosecha === '' || $fecha_cosecha === null || !valid_date_or_null($fecha_cosecha)) {
        flash('error', 'Complete la unidad y la fecha real de cosecha.');
        redirect('agricultor.php');
    }

    $conn->begin_transaction();

    try {
        $lote = db_fetch_one(
            $conn,
            "SELECT l.id_lote, l.estado_cultivo, c.tipo
             FROM lotes l
             INNER JOIN cultivos c ON l.id_cultivo = c.id_cultivo
             WHERE l.id_lote = ? AND c.id_usuario = ?
             FOR UPDATE",
            "is",
            [$id_lote, $id_usuario]
        );

        if (!$lote || $lote['estado_cultivo'] !== 'en_cosecha') {
            throw new RuntimeException('El lote ya no está disponible para finalizar su cosecha.');
        }

        db_execute(
            $conn,
            "INSERT INTO productos_finales (
                id_usuario, id_lote, nombre_producto, cantidad,
                unidad_medida, observaciones, fecha
            ) VALUES (?, ?, ?, ?, ?, ?, ?)",
            "sisdsss",
            [
                $id_usuario,
                $id_lote,
                $lote['tipo'],
                $cantidad_cosechada,
                $unidad_cosecha,
                $observacion === '' ? null : $observacion,
                $fecha_cosecha . ' 00:00:00',
            ]
        );

        db_execute(
            $conn,
            "UPDATE lotes
             SET estado_cultivo = 'finalizado',
                 fecha_fin_cosecha_real = ?
             WHERE id_lote = ? AND estado_cultivo = 'en_cosecha'",
            "si",
            [$fecha_cosecha, $id_lote]
        );

        $conn->commit();
        flash('mensaje', 'Cosecha finalizada y producción registrada correctamente.');
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error al finalizar cosecha: ' . $exception->getMessage());
        flash('error', $exception instanceof RuntimeException
            ? $exception->getMessage()
            : 'No se pudo finalizar la cosecha.');
    }

    redirect('agricultor.php');
}

// Registrar cultivo
if (($_POST['accion'] ?? '') === 'registrar_cultivo' || isset($_POST['registrar_cultivo'])) {
    $tipo = trim($_POST['tipo'] ?? '');
    $fecha_siembra = post_date_or_null('fecha_siembra');

    if ($tipo === '' || $fecha_siembra === null || !valid_date_or_null($fecha_siembra)) {
        flash('error', 'Complete los datos del cultivo.');
        redirect('agricultor.php');
    }

    try {
        db_execute(
            $conn,
            "INSERT INTO cultivos (id_usuario, tipo, fecha_siembra) VALUES (?, ?, ?)",
            "sss",
            [$id_usuario, $tipo, $fecha_siembra]
        );
        flash('mensaje', 'Cultivo registrado correctamente. Ya puede asociarlo a un lote.');
    } catch (Throwable $exception) {
        error_log('Error al registrar cultivo: ' . $exception->getMessage());
        flash('error', 'No se pudo registrar el cultivo.');
    }

    redirect('agricultor.php?tab=lote');
}

// Registrar lote
if (($_POST['accion'] ?? '') === 'registrar_lote' || isset($_POST['registrar_lote'])) {
    $id_cultivo = (int) ($_POST['id_cultivo'] ?? 0);
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $area = (float) ($_POST['area'] ?? 0);
    $etapa_siembra = isset($_POST['etapa_siembra']) ? 1 : 0;
    $etapa_riego = isset($_POST['etapa_riego']) ? 1 : 0;
    $etapa_cosecha = isset($_POST['etapa_cosecha']) ? 1 : 0;
    $etapa_actual = $etapa_cosecha === 1
        ? 3
        : ($etapa_riego === 1 ? 2 : ($etapa_siembra === 1 ? 1 : 0));
    $estado_cultivo = $etapa_actual === 3 ? 'en_cosecha' : 'activo';
    $fecha_inicio_riego = post_date_or_null('fecha_inicio_riego');
    $fecha_fin_riego = post_date_or_null('fecha_fin_riego');
    $fecha_inicio_siembra = post_date_or_null('fecha_inicio_siembra');
    $fecha_fin_siembra = post_date_or_null('fecha_fin_siembra');
    $fecha_inicio_cosecha = post_date_or_null('fecha_inicio_cosecha');
    $fecha_fin_cosecha = post_date_or_null('fecha_fin_cosecha');
    if ($id_cultivo <= 0) {
        flash('error', 'Seleccione el cultivo que desea asociar al lote.');
        redirect('agricultor.php?tab=lote');
    }

    if (!user_owns_cultivo($conn, $id_usuario, $id_cultivo)) {
        flash('error', 'El cultivo seleccionado no pertenece a su cuenta.');
        redirect('agricultor.php?tab=lote');
    }

    if ($ubicacion === '') {
        flash('error', 'Ingrese la ubicación del lote.');
        redirect('agricultor.php?tab=lote');
    }

    if ($area <= 0) {
        flash('error', 'Ingrese un área válida mayor que cero.');
        redirect('agricultor.php?tab=lote');
    }

    if (!valid_date_range($fecha_inicio_riego, $fecha_fin_riego)) {
        flash('error', 'Revise las fechas de riego: la fecha final no puede ser anterior a la inicial.');
        redirect('agricultor.php?tab=lote');
    }

    if (!valid_date_range($fecha_inicio_siembra, $fecha_fin_siembra)) {
        flash('error', 'Revise las fechas de siembra: la fecha final no puede ser anterior a la inicial.');
        redirect('agricultor.php?tab=lote');
    }

    if (!valid_date_range($fecha_inicio_cosecha, $fecha_fin_cosecha)) {
        flash('error', 'Revise las fechas de cosecha: la fecha final no puede ser anterior a la inicial.');
        redirect('agricultor.php?tab=lote');
    }

    try {
        db_execute(
            $conn,
            "INSERT INTO lotes (
                id_cultivo, ubicacion, area, etapa_actual, estado_cultivo,
                etapa_riego, etapa_siembra, etapa_cosecha,
                fecha_inicio_riego, fecha_fin_riego, fecha_inicio_siembra, fecha_fin_siembra,
                fecha_inicio_cosecha, fecha_fin_cosecha, fecha_registro
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            "isdisiiissssss",
            [
                $id_cultivo,
                $ubicacion,
                $area,
                $etapa_actual,
                $estado_cultivo,
                $etapa_riego,
                $etapa_siembra,
                $etapa_cosecha,
                $fecha_inicio_riego,
                $fecha_fin_riego,
                $fecha_inicio_siembra,
                $fecha_fin_siembra,
                $fecha_inicio_cosecha,
                $fecha_fin_cosecha,
            ]
        );
        flash('mensaje', 'Lote registrado correctamente.');
    } catch (Throwable $exception) {
        error_log('Error al registrar lote: ' . $exception->getMessage());
        flash('error', 'No se pudo registrar el lote.');
    }

    redirect('agricultor.php?tab=lote');
}

// Registrar plagas
if (($_POST['accion'] ?? '') === 'registrar_plaga' || isset($_POST['registrar_plaga'])) {
    $id_lote = (int) ($_POST['id_lote'] ?? 0);
    $plagasInput = is_array($_POST['plagas'] ?? null) ? $_POST['plagas'] : [];
    $plagas = array_values(array_unique(array_filter(array_map('trim', $plagasInput))));

    if ($id_lote <= 0 || empty($plagas) || !user_owns_lote($conn, $id_usuario, $id_lote)) {
        flash('error', 'Seleccione un lote válido y al menos una plaga.');
        redirect('agricultor.php');
    }

    $conn->begin_transaction();

    try {
        foreach ($plagas as $plaga) {
            db_execute(
                $conn,
                "INSERT INTO plagas (id_lote, id_usuario, nombre) VALUES (?, ?, ?)",
                "iss",
                [$id_lote, $id_usuario, $plaga]
            );
        }
        $conn->commit();
        flash('mensaje', 'Plagas registradas correctamente.');
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error al registrar plagas: ' . $exception->getMessage());
        flash('error', 'No se pudieron registrar las plagas.');
    }

    redirect('agricultor.php');
}

// Registrar solicitud manual desde el dashboard
if (($_POST['accion'] ?? '') === 'solicitar_insumos_manual' || isset($_POST['solicitar_insumos_manual'])) {
    $id_lote = (int) ($_POST['id_lote'] ?? 0);
    $hectareas = (float) ($_POST['hectareas'] ?? 0);
    $observaciones = trim($_POST['observaciones'] ?? '');
    $productos = is_array($_POST['productos'] ?? null) ? $_POST['productos'] : [];

    if ($id_lote <= 0 || !user_owns_lote($conn, $id_usuario, $id_lote)) {
        flash('error', 'Seleccione un lote válido.');
        redirect('agricultor.php?tab=insumos');
    }

    $area_lote = (float) db_value(
        $conn,
        "SELECT area FROM lotes WHERE id_lote = ?",
        "i",
        [$id_lote],
        0
    );

    if ($hectareas <= 0 || $area_lote <= 0 || $hectareas > $area_lote) {
        flash('error', 'Las hectáreas deben ser mayores que cero y no superar el área del lote.');
        redirect('agricultor.php?tab=insumos');
    }

    if (empty($productos)) {
        flash('error', 'Agregue al menos un insumo a la solicitud.');
        redirect('agricultor.php?tab=insumos');
    }

    $conn->begin_transaction();

    try {
        $registrados = 0;

        foreach ($productos as $producto) {
            $id_insumo = (int) ($producto['id_insumo'] ?? 0);
            $cantidad_por_hectarea = (float) ($producto['cantidad'] ?? 0);

            if ($id_insumo <= 0 || $cantidad_por_hectarea <= 0) {
                continue;
            }

            $insumo = db_fetch_one(
                $conn,
                "SELECT id_insumos, nombre, tipo FROM insumos_agricolas WHERE id_insumos = ?",
                "i",
                [$id_insumo]
            );

            if (!$insumo) {
                continue;
            }

            $cantidad_total = $cantidad_por_hectarea * $hectareas;
            db_execute(
                $conn,
                "INSERT INTO productos_solicitud (
                    id_agricultor, id_lote, id_insumos, etapa, nombre, cantidad_solicitada, observaciones, fecha, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'Pendiente')",
                "siissds",
                [
                    $id_usuario,
                    $id_lote,
                    $id_insumo,
                    $insumo['tipo'],
                    $insumo['nombre'],
                    $cantidad_total,
                    $observaciones,
                ]
            );
            $registrados++;
        }

        if ($registrados === 0) {
            throw new RuntimeException('No se registró ningún insumo válido.');
        }

        $conn->commit();
        flash('mensaje', "Solicitud enviada correctamente con $registrados insumos.");
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error al registrar solicitud desde dashboard: ' . $exception->getMessage());
        flash('error', 'No se pudo registrar la solicitud de insumos.');
    }

    redirect('agricultor.php?tab=insumos');
}

// Registrar solicitud automática basada en insumos calculados
if (isset($_POST['solicitar_insumos_automatico'])) {
    $id_lote = (int) ($_POST['id_lote'] ?? 0);
    $observaciones = trim($_POST['observaciones'] ?? '');

    // Obtener insumos calculados para el lote
    $insumos_json = $_POST['insumos_json'] ?? '[]';
    $insumos = json_decode($insumos_json, true);

    if ($id_lote <= 0 || !is_array($insumos) || empty($insumos) || !user_owns_lote($conn, $id_usuario, $id_lote)) {
        flash('error', 'No se pudo registrar la solicitud automática.');
        redirect('agricultor.php');
    }

    $conn->begin_transaction();

    try {
        $registrados = 0;
        foreach ($insumos as $insumo) {
            $nombre = trim($insumo['nombre'] ?? '');
            $cantidad = (float) ($insumo['cantidad_total'] ?? 0);

            if ($nombre === '' || $cantidad <= 0) {
                continue;
            }

            $insumoDb = db_fetch_one(
                $conn,
                "SELECT id_insumos, tipo FROM insumos_agricolas WHERE nombre = ? LIMIT 1",
                "s",
                [$nombre]
            );

            if (!$insumoDb) {
                continue;
            }

            db_execute(
                $conn,
                "INSERT INTO productos_solicitud (
                    id_agricultor, id_lote, id_insumos, etapa, nombre, cantidad_solicitada, observaciones, fecha, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'Pendiente')",
                "siissds",
                [$id_usuario, $id_lote, (int) $insumoDb['id_insumos'], $insumoDb['tipo'], $nombre, $cantidad, $observaciones]
            );
            $registrados++;
        }

        if ($registrados === 0) {
            throw new RuntimeException('No se encontró ningún insumo válido.');
        }

        $conn->commit();
        flash('mensaje', "Solicitud automática registrada correctamente con $registrados insumos.");
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error al registrar solicitud automática: ' . $exception->getMessage());
        flash('error', 'No se pudo registrar la solicitud automática.');
    }

    redirect('agricultor.php');
}

// Datos para dashboard
$cultivos = db_fetch_all($conn, "SELECT * FROM cultivos WHERE id_usuario = ? ORDER BY fecha_siembra DESC", "s", [$id_usuario]);
$lotes = db_fetch_all($conn, "
    SELECT l.*, c.tipo AS tipo_cultivo, GROUP_CONCAT(p.nombre SEPARATOR ', ') AS plagas
    FROM lotes l
    LEFT JOIN cultivos c ON l.id_cultivo=c.id_cultivo
    LEFT JOIN plagas p ON l.id_lote=p.id_lote
    WHERE c.id_usuario = ?
    GROUP BY l.id_lote
    ORDER BY l.id_lote DESC
", "s", [$id_usuario]);

// Obtener insumos disponibles para solicitudes
$insumos = db_fetch_all($conn, "SELECT id_insumos, nombre, cantidad, unidad_medida FROM insumos_agricolas ORDER BY nombre");

// Estadísticas
$total_lotes = count($lotes);
$etapas = ['Siembra'=>0,'Desarrollo'=>0,'Cosecha'=>0];
foreach ($lotes as $l) {
    if($l['etapa_actual']==1) $etapas['Siembra']++;
    elseif($l['etapa_actual']==2) $etapas['Desarrollo']++;
    elseif($l['etapa_actual']==3) $etapas['Cosecha']++;
}
?>
<?php render_head('Dashboard Agricultor', [], ['https://cdn.jsdelivr.net/npm/chart.js']); ?>
<body class="farmer-dashboard-page">
<?php render_app_nav('fas fa-seedling', current_user_name(), [
    ['href' => 'logout.php', 'label' => 'Salir', 'icon' => 'fas fa-sign-out-alt', 'class' => 'btn btn-outline-light btn-sm'],
]); ?>
<div class="container farmer-dashboard mt-4">
    <?php render_flash_messages(); ?>

    <section class="farmer-page-heading farmer-dashboard-hero">
        <div class="farmer-hero-copy">
            <span class="farmer-kicker">Panel agrícola</span>
            <h1>Resumen de Actividad</h1>
            <p>Gestiona tus cultivos, lotes y solicitudes desde un solo lugar.</p>
        </div>
        <div class="farmer-hero-status">
            <span class="farmer-hero-status-icon"><i class="fas fa-seedling"></i></span>
            <div>
                <small>Estado de operación</small>
                <strong><i class="fas fa-circle"></i> Jornada activa</strong>
            </div>
        </div>
    </section>

    <section class="farmer-stats-grid" aria-label="Resumen por etapas">
        <article class="farmer-stat-card farmer-stat-card--total">
            <div class="farmer-stat-top">
                <span class="farmer-stat-icon"><i class="fas fa-map-location-dot"></i></span>
                <span class="farmer-stat-status">Registrados</span>
            </div>
            <strong><?php echo $total_lotes; ?></strong>
            <p>Total de lotes</p>
            <span class="farmer-stat-detail"><i class="fas fa-layer-group"></i> Superficie bajo gestión</span>
        </article>

        <article class="farmer-stat-card farmer-stat-card--riego">
            <div class="farmer-stat-top">
                <span class="farmer-stat-icon"><i class="fas fa-droplet"></i></span>
                <span class="farmer-stat-status">Activo</span>
            </div>
            <strong><?php echo $etapas['Desarrollo']; ?></strong>
            <p>Lotes en desarrollo</p>
            <span class="farmer-stat-detail"><i class="fas fa-water"></i> Crecimiento y gestión hídrica</span>
        </article>

        <article class="farmer-stat-card farmer-stat-card--siembra">
            <div class="farmer-stat-top">
                <span class="farmer-stat-icon"><i class="fas fa-leaf"></i></span>
                <span class="farmer-stat-status">En progreso</span>
            </div>
            <strong><?php echo $etapas['Siembra']; ?></strong>
            <p>Lotes en siembra</p>
            <span class="farmer-stat-detail"><i class="fas fa-seedling"></i> Desarrollo inicial</span>
        </article>

        <article class="farmer-stat-card farmer-stat-card--cosecha">
            <div class="farmer-stat-top">
                <span class="farmer-stat-icon"><i class="fas fa-box"></i></span>
                <span class="farmer-stat-status">Planificado</span>
            </div>
            <strong><?php echo $etapas['Cosecha']; ?></strong>
            <p>Lotes en cosecha</p>
            <span class="farmer-stat-detail"><i class="fas fa-calendar-check"></i> Etapa productiva</span>
        </article>
    </section>

    <div class="farmer-content-grid">
        <main class="farmer-main-panel">
            <ul class="nav nav-tabs farmer-tabs" id="agricultorTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="cultivo-tab" data-bs-toggle="tab" data-bs-target="#cultivo" type="button" role="tab" aria-controls="cultivo" aria-selected="true"><i class="fas fa-seedling"></i> Registrar Cultivo</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="lote-tab" data-bs-toggle="tab" data-bs-target="#lote" type="button" role="tab" aria-controls="lote" aria-selected="false"><i class="fas fa-map-location-dot"></i> Registrar Lote</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="plaga-tab" data-bs-toggle="tab" data-bs-target="#plaga" type="button" role="tab" aria-controls="plaga" aria-selected="false"><i class="fas fa-bug"></i> Registrar Plagas</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="insumos-tab" data-bs-toggle="tab" data-bs-target="#insumos" type="button" role="tab" aria-controls="insumos" aria-selected="false"><i class="fas fa-box-open"></i> Solicitar Insumos</button>
                </li>
            </ul>

            <div class="tab-content farmer-tab-content" id="agricultorTabContent">
                <div class="tab-pane fade show active" id="cultivo" role="tabpanel" aria-labelledby="cultivo-tab">
                    <form method="POST" class="farmer-form farmer-record-form">
                        <input type="hidden" name="accion" value="registrar_cultivo">
                        <div class="record-hero record-hero--crop">
                            <div>
                                <span class="farmer-kicker">Registro agrícola</span>
                                <h2>Registrar Cultivo</h2>
                                <p>Ingrese el tipo de cultivo y la fecha de siembra para iniciar el seguimiento productivo.</p>
                            </div>
                            <span class="record-hero-icon" aria-hidden="true"><i class="fas fa-seedling"></i></span>
                        </div>

                        <div class="farmer-form-grid record-field-grid">
                            <label class="record-field-card">
                                <span>Tipo de cultivo</span>
                                <input type="text" name="tipo" class="form-control" placeholder="Ej. Mango, banano..." required>
                            </label>
                            <label class="record-field-card">
                                <span>Fecha de siembra</span>
                                <input type="date" name="fecha_siembra" class="form-control" required>
                            </label>
                        </div>
                        <button type="submit" name="registrar_cultivo" class="btn w-100 farmer-submit farmer-action-button farmer-action-button--primary">
                            <span>Registrar cultivo</span>
                        </button>
                    </form>
                </div>

                <div class="tab-pane fade" id="lote" role="tabpanel" aria-labelledby="lote-tab">
                    <form method="POST" class="farmer-form farmer-record-form">
                        <input type="hidden" name="accion" value="registrar_lote">
                        <div class="record-hero record-hero--lot">
                            <div>
                                <span class="farmer-kicker">Gestión de lotes</span>
                                <h2>Registrar Lote</h2>
                                <p>Asocie el lote a un cultivo, defina su ubicación y configure las etapas de trabajo.</p>
                            </div>
                            <span class="record-hero-icon" aria-hidden="true"><i class="fas fa-map-location-dot"></i></span>
                        </div>

                        <div class="farmer-form-grid record-field-grid lot-primary-grid">
                            <label class="record-field-card lot-crop-field">
                                <span>Cultivo</span>
                                <div class="ag-select" data-ag-select>
                                    <input type="hidden" name="id_cultivo" data-ag-select-value>
                                    <button type="button" class="ag-select-button" data-ag-select-button aria-haspopup="listbox" aria-expanded="false">
                                        <i class="fas fa-seedling"></i>
                                        <span data-ag-select-label>Selecciona cultivo para el lote</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                    <div class="ag-select-menu" data-ag-select-menu role="listbox">
                                        <?php if (!empty($cultivos)): ?>
                                            <?php foreach($cultivos as $c): ?>
                                                <button type="button" class="ag-select-option" data-value="<?php echo e($c['id_cultivo']); ?>" role="option">
                                                    <i class="fas fa-leaf"></i>
                                                    <span><?php echo e($c['tipo']); ?> - <?php echo e($c['fecha_siembra']); ?></span>
                                                </button>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="ag-select-empty">Primero registre un cultivo.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </label>
                            <label class="record-field-card">
                                <span>Ubicación</span>
                                <input type="text" name="ubicacion" class="form-control" placeholder="Ubicación del lote" required>
                            </label>
                            <label class="record-field-card">
                                <span>Área</span>
                                <input type="number" step="0.01" min="0.01" name="area" class="form-control" placeholder="Área ha" required>
                            </label>
                        </div>

                        <section class="lot-stage-planner" aria-labelledby="lot-stage-title">
                            <header class="lot-stage-planner__header">
                                <div>
                                    <span class="farmer-kicker">Cronograma productivo</span>
                                    <h3 id="lot-stage-title">Etapas y fechas del lote</h3>
                                    <p>Seleccione la etapa actual y defina los periodos estimados de trabajo.</p>
                                </div>
                                <span class="lot-stage-planner__badge"><i class="fas fa-calendar-days"></i> Planificación</span>
                            </header>

                            <div class="lot-stage-grid">
                                <article class="lot-stage-card lot-stage-card--riego">
                                    <label class="lot-stage-toggle">
                                        <input type="checkbox" name="etapa_riego" value="1" class="form-check-input">
                                        <span class="lot-stage-icon"><i class="fas fa-droplet"></i></span>
                                        <span class="lot-stage-copy">
                                            <strong>Desarrollo</strong>
                                            <small>Crecimiento y gestión hídrica</small>
                                        </span>
                                        <span class="lot-stage-check"><i class="fas fa-check"></i></span>
                                    </label>
                                    <div class="lot-stage-dates">
                                        <label>
                                            <span>Fecha de inicio</span>
                                            <input type="date" name="fecha_inicio_riego" class="form-control">
                                        </label>
                                        <label>
                                            <span>Fecha de finalización</span>
                                            <input type="date" name="fecha_fin_riego" class="form-control">
                                        </label>
                                    </div>
                                </article>

                                <article class="lot-stage-card lot-stage-card--siembra">
                                    <label class="lot-stage-toggle">
                                        <input type="checkbox" name="etapa_siembra" value="1" class="form-check-input">
                                        <span class="lot-stage-icon"><i class="fas fa-seedling"></i></span>
                                        <span class="lot-stage-copy">
                                            <strong>Siembra</strong>
                                            <small>Implantación y desarrollo inicial</small>
                                        </span>
                                        <span class="lot-stage-check"><i class="fas fa-check"></i></span>
                                    </label>
                                    <div class="lot-stage-dates">
                                        <label>
                                            <span>Fecha de inicio</span>
                                            <input type="date" name="fecha_inicio_siembra" class="form-control">
                                        </label>
                                        <label>
                                            <span>Fecha de finalización</span>
                                            <input type="date" name="fecha_fin_siembra" class="form-control">
                                        </label>
                                    </div>
                                </article>

                                <article class="lot-stage-card lot-stage-card--cosecha">
                                    <label class="lot-stage-toggle">
                                        <input type="checkbox" name="etapa_cosecha" value="1" class="form-check-input">
                                        <span class="lot-stage-icon"><i class="fas fa-wheat-awn"></i></span>
                                        <span class="lot-stage-copy">
                                            <strong>Cosecha</strong>
                                            <small>Recolección en curso, todavía no finalizada</small>
                                        </span>
                                        <span class="lot-stage-check"><i class="fas fa-check"></i></span>
                                    </label>
                                    <div class="lot-stage-dates">
                                        <label>
                                            <span>Fecha de inicio</span>
                                            <input type="date" name="fecha_inicio_cosecha" class="form-control">
                                        </label>
                                        <label>
                                            <span>Fecha de finalización</span>
                                            <input type="date" name="fecha_fin_cosecha" class="form-control">
                                        </label>
                                    </div>
                                </article>
                            </div>
                        </section>
                        <button type="submit" name="registrar_lote" class="btn w-100 farmer-submit farmer-action-button farmer-action-button--primary">
                            <span>Registrar lote</span>
                        </button>
                    </form>
                </div>

                <div class="tab-pane fade" id="plaga" role="tabpanel" aria-labelledby="plaga-tab">
                    <form method="POST" class="farmer-form pest-monitor-form" data-pest-form>
                        <input type="hidden" name="accion" value="registrar_plaga">
                        <div class="pest-hero">
                            <div>
                                <span class="farmer-kicker">Monitoreo fitosanitario</span>
                                <h2>Registrar Plagas</h2>
                                <p>Seleccione el lote inspeccionado y marque las plagas detectadas durante el recorrido.</p>
                            </div>
                            <span class="pest-hero-icon" aria-hidden="true">🐛</span>
                        </div>

                        <label class="pest-lot-selector">
                            <span>Lote de inspección</span>
                            <div class="ag-select ag-select--inspection" data-ag-select>
                                <input type="hidden" name="id_lote" data-ag-select-value>
                                <button type="button" class="ag-select-button" data-ag-select-button aria-haspopup="listbox" aria-expanded="false">
                                    <i class="fas fa-location-dot"></i>
                                    <span data-ag-select-label>Selecciona un lote para inspección</span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="ag-select-menu" data-ag-select-menu role="listbox">
                                    <?php foreach($lotes as $l): ?>
                                        <button type="button" class="ag-select-option" data-value="<?php echo e($l['id_lote']); ?>" role="option">
                                            <i class="fas fa-location-dot"></i>
                                            <span>Lote #<?php echo e($l['id_lote']); ?> - <?php echo e($l['ubicacion']); ?> (<?php echo e($l['tipo_cultivo']); ?>)</span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </label>

                        <section class="pest-picker" aria-labelledby="pest-picker-title">
                            <div class="farmer-section-heading pest-section-heading">
                                <div>
                                    <h2 id="pest-picker-title">Plagas detectadas</h2>
                                    <p>Use las etiquetas para registrar hallazgos y priorizar el nivel de riesgo.</p>
                                </div>
                                <span>Inspección activa</span>
                            </div>

                            <div class="pest-card-grid">
                                <label class="pest-card" data-severity="alto" tabindex="0">
                                    <input type="checkbox" name="plagas[]" value="Mosca de la fruta">
                                    <span class="pest-icon" aria-hidden="true">🐛</span>
                                    <span class="pest-name">Mosca de la fruta</span>
                                    <span class="pest-severity pest-severity--alto">Alto</span>
                                </label>
                                <label class="pest-card" data-severity="medio" tabindex="0">
                                    <input type="checkbox" name="plagas[]" value="Trips">
                                    <span class="pest-icon" aria-hidden="true">🦗</span>
                                    <span class="pest-name">Trips</span>
                                    <span class="pest-severity pest-severity--medio">Medio</span>
                                </label>
                                <label class="pest-card" data-severity="medio" tabindex="0">
                                    <input type="checkbox" name="plagas[]" value="Ácaros">
                                    <span class="pest-icon" aria-hidden="true">🕷️</span>
                                    <span class="pest-name">Ácaros</span>
                                    <span class="pest-severity pest-severity--medio">Medio</span>
                                </label>
                                <label class="pest-card" data-severity="bajo" tabindex="0">
                                    <input type="checkbox" name="plagas[]" value="Pulgones">
                                    <span class="pest-icon" aria-hidden="true">🐞</span>
                                    <span class="pest-name">Pulgones</span>
                                    <span class="pest-severity pest-severity--bajo">Bajo</span>
                                </label>
                                <label class="pest-card" data-severity="alto" tabindex="0">
                                    <input type="checkbox" name="plagas[]" value="Mosca blanca">
                                    <span class="pest-icon" aria-hidden="true">🦟</span>
                                    <span class="pest-name">Mosca blanca</span>
                                    <span class="pest-severity pest-severity--alto">Alto</span>
                                </label>
                                <label class="pest-card" data-severity="medio" tabindex="0">
                                    <input type="checkbox" name="plagas[]" value="Orugas defoliadoras">
                                    <span class="pest-icon" aria-hidden="true">🐛</span>
                                    <span class="pest-name">Orugas defoliadoras</span>
                                    <span class="pest-severity pest-severity--medio">Medio</span>
                                </label>
                                <label class="pest-card" data-severity="alto" tabindex="0">
                                    <input type="checkbox" name="plagas[]" value="Nematodos fitoparásitos">
                                    <span class="pest-icon" aria-hidden="true">🪱</span>
                                    <span class="pest-name">Nematodos fitoparásitos</span>
                                    <span class="pest-severity pest-severity--alto">Alto</span>
                                </label>
                            </div>
                        </section>

                        <section class="pest-summary-card" aria-label="Resumen de monitoreo de plagas">
                            <div class="pest-summary-heading">
                                <span aria-hidden="true">🐛</span>
                                <h2>Monitoreo de Plagas</h2>
                            </div>
                            <div class="pest-summary-grid">
                                <div>
                                    <span>Plagas registradas</span>
                                    <strong data-pest-total>0</strong>
                                </div>
                                <div>
                                    <span>Riesgo alto</span>
                                    <strong data-pest-high>0</strong>
                                </div>
                                <div>
                                    <span>Riesgo medio</span>
                                    <strong data-pest-medium>0</strong>
                                </div>
                                <div>
                                    <span>Riesgo bajo</span>
                                    <strong data-pest-low>0</strong>
                                </div>
                            </div>
                            <p class="pest-summary-date">
                                Última actualización:
                                <strong data-pest-updated><?php echo date('d/m/Y H:i'); ?></strong>
                            </p>
                        </section>

                        <button type="submit" name="registrar_plaga" class="btn w-100 farmer-submit farmer-action-button farmer-action-button--primary pest-submit">
                            <span>Registrar monitoreo</span>
                        </button>
                    </form>
                </div>

                <div class="tab-pane fade" id="insumos" role="tabpanel" aria-labelledby="insumos-tab">
                    <section class="supply-dashboard" aria-label="Solicitar insumos agrícolas">
                        <div class="record-hero supply-hero">
                            <div>
                                <span class="farmer-kicker">Abastecimiento agrícola</span>
                                <h2>Solicitar Insumos</h2>
                                <p>Planifique fertilizantes, materiales y productos por lote para mantener el flujo operativo del cultivo.</p>
                            </div>
                            <span class="record-hero-icon supply-hero-icon" aria-hidden="true"><i class="fas fa-clipboard-list"></i></span>
                        </div>

                        <div class="supply-workspace">
                            <form method="POST" action="agricultor.php" class="farmer-form supply-request-form" data-supply-form>
                                <input type="hidden" name="accion" value="solicitar_insumos_manual">
                                <div class="farmer-form-grid record-field-grid">
                                    <label class="record-field-card">
                                        <span>Lote para abastecimiento</span>
                                        <div class="ag-select" data-ag-select>
                                            <input type="hidden" name="id_lote" data-ag-select-value>
                                            <button type="button" class="ag-select-button" data-ag-select-button aria-haspopup="listbox" aria-expanded="false">
                                                <i class="fas fa-location-dot"></i>
                                                <span data-ag-select-label>Selecciona lote para solicitar insumos</span>
                                                <i class="fas fa-chevron-down"></i>
                                            </button>
                                            <div class="ag-select-menu" data-ag-select-menu role="listbox">
                                                <?php foreach($lotes as $l): ?>
                                                    <button type="button" class="ag-select-option" data-value="<?php echo e($l['id_lote']); ?>" role="option">
                                                        <i class="fas fa-location-dot"></i>
                                                        <span>Lote #<?php echo e($l['id_lote']); ?> - <?php echo e($l['ubicacion']); ?> (<?php echo e($l['area']); ?> ha)</span>
                                                    </button>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </label>

                                    <label class="record-field-card">
                                        <span>Cantidad de hectáreas</span>
                                        <input type="number" step="0.01" min="0.01" name="hectareas" class="form-control" placeholder="Ej. 2.5" required>
                                    </label>
                                </div>

                                <section class="supply-products-panel">
                                    <div class="farmer-section-heading">
                                        <div>
                                            <h2>Insumos solicitados</h2>
                                            <p>Agregue cada insumo y la cantidad requerida por hectárea.</p>
                                        </div>
                                        <button type="button" class="btn farmer-add-button farmer-action-button farmer-action-button--compact" data-add-supply-product data-app-no-ripple>
                                            <i class="fas fa-plus"></i>
                                            <span>Agregar insumo</span>
                                        </button>
                                    </div>
                                    <div class="farmer-products-list" data-supply-products></div>
                                </section>

                                <label class="record-field-card">
                                    <span>Observaciones</span>
                                    <textarea name="observaciones" class="form-control" placeholder="Notas para bodega o administración"></textarea>
                                </label>

                                <button type="submit" name="solicitar_insumos_manual" class="btn w-100 farmer-submit farmer-action-button farmer-action-button--primary supply-submit">
                                    <span>Enviar solicitud de insumos</span>
                                </button>
                            </form>

                            <aside class="supply-side-summary">
                                <article>
                                    <span>Lotes disponibles</span>
                                    <strong><?php echo $total_lotes; ?></strong>
                                </article>
                                <article>
                                    <span>Insumos disponibles</span>
                                    <strong><?php echo count($insumos); ?></strong>
                                </article>
                                <a href="calcular_insumos.php">
                                    <i class="fas fa-calculator"></i>
                                    Calcular cantidades
                                </a>
                                <a href="historial_solicitudes.php">
                                    <i class="fas fa-clock-rotate-left"></i>
                                    Ver historial
                                </a>
                            </aside>
                        </div>
                    </section>
                </div>
            </div>

            <section class="farmer-lotes-card">
                <div class="farmer-section-heading">
                    <h2>Lotes Registrados</h2>
                    <span><?php echo $total_lotes; ?> registros</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cultivo</th>
                                <th>Ubicación</th>
                                <th>Área</th>
                                <th>Etapa</th>
                                <th>Estado</th>
                                <th>Plagas</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lotes)): ?>
                                <tr>
                                    <td colspan="8" class="farmer-empty-row">
                                        <i class="fas fa-circle-info"></i>
                                        No hay lotes registrados recientemente
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($lotes as $l): ?>
                                <tr>
                                    <td><?php echo e($l['id_lote']); ?></td>
                                    <td><?php echo e($l['tipo_cultivo']); ?></td>
                                    <td><?php echo e($l['ubicacion']); ?></td>
                                    <td><?php echo e($l['area']); ?></td>
                                    <td><?php echo e(crop_stage_label((int) $l['etapa_actual'])); ?></td>
                                    <td>
                                        <span class="crop-status crop-status--<?php echo e(str_replace('_', '-', $l['estado_cultivo'])); ?>">
                                            <i class="<?php echo e(crop_status_icon($l['estado_cultivo'])); ?>" aria-hidden="true"></i>
                                            <?php echo e(crop_status_label($l['estado_cultivo'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo e($l['plagas'] ?: '-'); ?></td>
                                    <td>
                                        <?php if ($l['estado_cultivo'] === 'en_cosecha'): ?>
                                            <button
                                                type="button"
                                                class="harvest-finish-button farmer-action-button farmer-action-button--compact"
                                                data-bs-toggle="modal"
                                                data-bs-target="#finalizarCosechaModal"
                                                data-harvest-lot-id="<?php echo (int) $l['id_lote']; ?>"
                                                data-harvest-lot-name="<?php echo e($l['ubicacion'] . ' - ' . $l['tipo_cultivo']); ?>">
                                                <i class="fas fa-check-circle"></i> Finalizar cosecha
                                            </button>
                                        <?php elseif ($l['estado_cultivo'] === 'finalizado'): ?>
                                            <span class="harvest-closed-state"><i class="fas fa-lock"></i> Cerrado</span>
                                        <?php else: ?>
                                            <span class="harvest-no-action">Sin acciones</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <aside class="farmer-side-panel">
            <section class="farmer-field-card" aria-label="Estado general de lotes">
                <div class="farmer-map-frame">
                    <iframe
                        title="Mapa satelital del cultivo"
                        src="https://maps.google.com/maps?q=-2.170998,-79.922359&t=k&z=15&output=embed"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen>
                    </iframe>
                </div>
                <div class="farmer-field-footer">
                    <div class="farmer-field-heading">
                        <h2>Estado General</h2>
                    </div>
                    <div class="farmer-chart-wrap">
                        <canvas id="etapaChart" class="farmer-chart"></canvas>
                    </div>
                    <div class="farmer-stage-legend">
                        <span><i class="farmer-dot farmer-dot--siembra"></i>Siembra</span>
                        <span><i class="farmer-dot farmer-dot--riego"></i>Desarrollo</span>
                        <span><i class="farmer-dot farmer-dot--cosecha"></i>Cosecha</span>
                    </div>
                </div>
            </section>

            <section class="farmer-weather-card">
                <div>
                    <span>Clima Actual</span>
                    <strong>24°C</strong>
                    <p>Cielos Despejados</p>
                </div>
                <i class="fas fa-sun"></i>
            </section>
        </aside>
    </div>
</div>

<div class="modal fade harvest-premium-modal" id="finalizarCosechaModal" tabindex="-1" aria-labelledby="finalizarCosechaTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="agricultor.php">
                <input type="hidden" name="accion" value="finalizar_cosecha">
                <input type="hidden" name="id_lote" data-harvest-lot-input>
                <div class="modal-header">
                    <span class="harvest-modal-icon" aria-hidden="true">
                        <i class="fas fa-wheat-awn"></i>
                    </span>
                    <div class="harvest-modal-heading">
                        <span class="farmer-kicker">Cierre productivo</span>
                        <h2 class="modal-title" id="finalizarCosechaTitle">Finalizar cosecha</h2>
                        <p>Registra el resultado real y cierra el ciclo productivo del lote.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="harvest-lot-summary">
                        <span class="harvest-lot-summary__icon"><i class="fas fa-location-dot"></i></span>
                        <div>
                            <small>Lote seleccionado</small>
                            <strong data-harvest-lot-label></strong>
                        </div>
                        <span class="harvest-lot-summary__status"><i class="fas fa-wheat-awn"></i> En cosecha</span>
                    </div>

                    <div class="harvest-form-grid">
                        <label class="harvest-field">
                            <span><i class="fas fa-scale-balanced"></i> Cantidad cosechada</span>
                            <input type="number" name="cantidad_cosechada" min="0.01" step="0.01" class="form-control" placeholder="Ej. 1250" required>
                        </label>
                        <label class="harvest-field">
                            <span><i class="fas fa-boxes-stacked"></i> Unidad de medida</span>
                            <select name="unidad_cosecha" class="form-select" required>
                                <option value="">Seleccione</option>
                                <option value="kg">Kilogramos (kg)</option>
                                <option value="toneladas">Toneladas</option>
                                <option value="cajas">Cajas</option>
                                <option value="unidades">Unidades</option>
                            </select>
                        </label>
                        <label class="harvest-field harvest-field--wide">
                            <span><i class="fas fa-calendar-check"></i> Fecha real de cosecha</span>
                            <input type="date" name="fecha_cosecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </label>
                        <label class="harvest-field harvest-field--wide">
                            <span><i class="fas fa-note-sticky"></i> Observación <small>Opcional</small></span>
                            <textarea name="observacion" class="form-control" rows="3" placeholder="Calidad, clasificación, pérdidas u otra novedad de la cosecha"></textarea>
                        </label>
                    </div>

                    <div class="harvest-close-notice">
                        <span><i class="fas fa-lock"></i></span>
                        <div>
                            <strong>Cierre definitivo del ciclo</strong>
                            <p>Al confirmar, el lote pasará a Finalizado y la producción quedará registrada.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <span class="harvest-modal-security"><i class="fas fa-shield-halved"></i> Registro protegido por validación</span>
                    <button type="button" class="harvest-modal-cancel" data-bs-dismiss="modal">Volver</button>
                    <button type="submit" class="harvest-modal-submit farmer-action-button farmer-action-button--compact">
                        <i class="fas fa-circle-check"></i> Guardar y finalizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/calcular_insumos.js" defer></script>
<script>
const supplyInsumosOptions = <?php echo json_encode(array_map(static function ($insumo) {
    return [
        'id' => $insumo['id_insumos'],
        'label' => $insumo['nombre'] . ' (' . $insumo['cantidad'] . ' ' . $insumo['unidad_medida'] . ')',
    ];
}, $insumos), JSON_UNESCAPED_UNICODE); ?>;

function escapeSupplyHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function buildSupplyInsumoSelect(index) {
    const options = supplyInsumosOptions.map((insumo) => `
        <button type="button" class="ag-select-option" data-value="${escapeSupplyHtml(insumo.id)}" role="option">
            <i class="fas fa-box-open" aria-hidden="true"></i>
            <span>${escapeSupplyHtml(insumo.label)}</span>
        </button>
    `).join('');

    return `
        <div class="ag-select ag-select--supply" data-ag-select>
            <input type="hidden" name="productos[${index}][id_insumo]" data-ag-select-value>
            <button type="button" class="ag-select-button" data-ag-select-button aria-haspopup="listbox" aria-expanded="false">
                <i class="fas fa-flask-vial" aria-hidden="true"></i>
                <span data-ag-select-label>Selecciona insumo</span>
                <i class="fas fa-chevron-down" aria-hidden="true"></i>
            </button>
            <div class="ag-select-menu" data-ag-select-menu role="listbox" aria-label="Seleccionar insumo">
                ${options}
            </div>
        </div>
    `;
}

const ctx = document.getElementById('etapaChart').getContext('2d');
const etapaChart = new Chart(ctx,{
    type:'doughnut',
    data:{
        labels:['Siembra','Desarrollo','Cosecha'],
        datasets:[{
            data:[<?php echo $etapas['Siembra'].','.$etapas['Desarrollo'].','.$etapas['Cosecha']; ?>],
            backgroundColor:['#08752b','#145ee8','#ffb43b'],
            borderColor: document.documentElement.dataset.theme === 'light'
                ? '#ffffff'
                : (document.documentElement.dataset.theme === 'night' ? '#080d0a' : '#172033'),
            borderWidth:3,
            hoverOffset:5
        }]
    },
    options:{
        responsive: true,
        maintainAspectRatio: false,
        cutout: '66%',
        plugins:{
            legend:{display:false}
        },
        layout:{
            padding:4
        }
    }
});

window.addEventListener('app:themechange', function(event) {
    etapaChart.data.datasets[0].borderColor = event.detail.theme === 'light'
        ? '#ffffff'
        : (event.detail.theme === 'night' ? '#080d0a' : '#172033');
    etapaChart.update('none');
});

document.addEventListener('DOMContentLoaded', function() {
    const harvestModal = document.getElementById('finalizarCosechaModal');

    harvestModal?.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        harvestModal.querySelector('[data-harvest-lot-input]').value = button?.dataset.harvestLotId || '';
        harvestModal.querySelector('[data-harvest-lot-label]').textContent = button?.dataset.harvestLotName || 'este lote';
    });

    const getSelects = () => Array.from(document.querySelectorAll('[data-ag-select]'));

    function closeSelect(select) {
        select.classList.remove('is-open');
        select.querySelector('[data-ag-select-button]')?.setAttribute('aria-expanded', 'false');
    }

    function closeAll(except = null) {
        getSelects().forEach((select) => {
            if (select !== except) {
                closeSelect(select);
            }
        });
    }

    function initializeSelect(select) {
        if (!select || select.dataset.agSelectBound === '1') {
            return;
        }
        select.dataset.agSelectBound = '1';

        const button = select.querySelector('[data-ag-select-button]');
        const value = select.querySelector('[data-ag-select-value]');
        const label = select.querySelector('[data-ag-select-label]');
        const options = Array.from(select.querySelectorAll('.ag-select-option'));

        button.addEventListener('click', function() {
            const willOpen = !select.classList.contains('is-open');
            closeAll(select);
            select.classList.toggle('is-open', willOpen);
            button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });

        options.forEach((option) => {
            option.addEventListener('click', function() {
                value.value = option.dataset.value || '';
                label.textContent = option.textContent.trim();
                select.classList.remove('is-invalid');
                options.forEach((item) => item.classList.toggle('is-selected', item === option));
                closeSelect(select);
            });
        });
    }

    getSelects().forEach(initializeSelect);
    document.addEventListener('ag-select:mount', function(event) {
        initializeSelect(event.detail?.select);
    });

    document.addEventListener('click', function(event) {
        if (!event.target.closest('[data-ag-select]')) {
            closeAll();
        }
    });

    document.addEventListener('submit', function(event) {
        const formSelects = Array.from(event.target.querySelectorAll('[data-ag-select]'));
        const invalidSelect = formSelects.find((select) => {
            const value = select.querySelector('[data-ag-select-value]');
            return !value || value.value === '';
        });

        if (!invalidSelect) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        invalidSelect.classList.add('is-invalid', 'is-open');
        invalidSelect.querySelector('[data-ag-select-button]')?.focus();
    }, true);

    const requestedTab = new URLSearchParams(window.location.search).get('tab');
    const requestedTrigger = requestedTab
        ? document.querySelector(`[data-bs-target="#${CSS.escape(requestedTab)}"]`)
        : null;

    if (requestedTrigger && window.bootstrap?.Tab) {
        window.bootstrap.Tab.getOrCreateInstance(requestedTrigger).show();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const supplyForm = document.querySelector('[data-supply-form]');

    if (!supplyForm) {
        return;
    }

    const container = supplyForm.querySelector('[data-supply-products]');
    const addButton = supplyForm.querySelector('[data-add-supply-product]');
    let supplyProductIndex = 0;

    function addSupplyProduct() {
        const row = document.createElement('div');
        row.className = 'producto-item farmer-product-row supply-product-row';
        row.innerHTML = `
            <div class="farmer-product-grid">
                <label>
                    <span>Insumo</span>
                    ${buildSupplyInsumoSelect(supplyProductIndex)}
                </label>
                <label>
                    <span>Cantidad por hectárea</span>
                    <input type="number" step="0.01" min="0.01" name="productos[${supplyProductIndex}][cantidad]" class="form-control" placeholder="Ej. 10" required>
                </label>
                <button type="button" class="btn btn-danger btn-sm remove-producto" aria-label="Eliminar insumo"><i class="fas fa-trash"></i></button>
            </div>
        `;

        container.appendChild(row);
        document.dispatchEvent(new CustomEvent('ag-select:mount', {
            detail: { select: row.querySelector('[data-ag-select]') }
        }));
        supplyProductIndex++;

        row.querySelector('.remove-producto').addEventListener('click', function() {
            if (container.children.length === 1) {
                const select = row.querySelector('[data-ag-select]');
                select.querySelector('[data-ag-select-value]').value = '';
                select.querySelector('[data-ag-select-label]').textContent = 'Selecciona insumo';
                select.querySelectorAll('.ag-select-option').forEach((option) => {
                    option.classList.remove('is-selected');
                });
                select.classList.remove('is-invalid', 'is-open');
                row.querySelector('input[type="number"]').value = '';
                return;
            }

            row.remove();
        });
    }

    addButton.addEventListener('click', addSupplyProduct);
    addSupplyProduct();
});

document.addEventListener('DOMContentLoaded', function() {
    const pestForm = document.querySelector('[data-pest-form]');

    if (!pestForm) {
        return;
    }

    const cards = Array.from(pestForm.querySelectorAll('.pest-card'));
    const total = pestForm.querySelector('[data-pest-total]');
    const high = pestForm.querySelector('[data-pest-high]');
    const medium = pestForm.querySelector('[data-pest-medium]');
    const low = pestForm.querySelector('[data-pest-low]');
    const updated = pestForm.querySelector('[data-pest-updated]');

    function formatNow() {
        return new Intl.DateTimeFormat('es-EC', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date());
    }

    function updatePestSummary() {
        const selected = cards.filter((card) => card.querySelector('input').checked);
        const counts = { alto: 0, medio: 0, bajo: 0 };

        selected.forEach((card) => {
            const severity = card.dataset.severity;
            if (Object.prototype.hasOwnProperty.call(counts, severity)) {
                counts[severity]++;
            }
        });

        total.textContent = selected.length;
        high.textContent = counts.alto;
        medium.textContent = counts.medio;
        low.textContent = counts.bajo;
        updated.textContent = formatNow();
    }

    cards.forEach((card) => {
        const checkbox = card.querySelector('input');

        checkbox.addEventListener('change', updatePestSummary);
        card.addEventListener('keydown', function(event) {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    pestForm.addEventListener('submit', function(event) {
        const hasSelectedPest = cards.some((card) => card.querySelector('input').checked);

        if (!hasSelectedPest) {
            event.preventDefault();
            event.stopImmediatePropagation();
            cards[0]?.focus();
            alert('Seleccione al menos una plaga detectada.');
        }
    }, true);
});

// Script para calcular insumos y actualizar el formulario
document.addEventListener('DOMContentLoaded', function() {
    const loteSelect = document.getElementById('id_lote');
    const hectareasInput = document.getElementById('hectareas');
    const insumosContainer = document.getElementById('insumosCalculados');
    const insumosJsonInput = document.getElementById('insumos_json');

    if (!loteSelect || !hectareasInput || !insumosContainer || !insumosJsonInput) {
        return;
    }

    function actualizarInsumos() {
        const loteId = loteSelect.value;
        const hectareas = parseFloat(hectareasInput.value);
        if (!loteId || isNaN(hectareas) || hectareas <= 0) {
            insumosContainer.innerHTML = '<p>Seleccione un lote y cantidad de hectáreas para calcular insumos.</p>';
            insumosJsonInput.value = '';
            return;
        }

        insumosContainer.innerHTML = '<p>Cargando insumos...</p>';

        fetch(`calcular_insumos.php?id_lote=${loteId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    insumosContainer.innerHTML = `<p class="text-danger">${data.error}</p>`;
                    insumosJsonInput.value = '';
                    return;
                }

                let html = `<h5>Área del lote: ${data.area} ha</h5>`;
                html += '<table class="table table-bordered"><thead><tr><th>Etapa</th><th>Insumo</th><th>Cantidad Total</th><th>Unidad</th></tr></thead><tbody>';

                const insumosCalculados = [];

                data.insumos.forEach(insumo => {
                    const cantidadTotal = insumo.cantidad_total * hectareas;
                    insumosCalculados.push({
                        nombre: insumo.nombre,
                        cantidad_total: cantidadTotal
                    });
                    html += `<tr>
                        <td>${insumo.etapa}</td>
                        <td>${insumo.nombre}</td>
                        <td>${cantidadTotal.toFixed(2)}</td>
                        <td>${insumo.unidad}</td>
                    </tr>`;
                });

                html += '</tbody></table>';
                insumosContainer.innerHTML = html;
                insumosJsonInput.value = JSON.stringify(insumosCalculados);
            })
            .catch(err => {
                insumosContainer.innerHTML = `<p class="text-danger">Error al cargar insumos: ${err}</p>`;
                insumosJsonInput.value = '';
            });
    }

    loteSelect.addEventListener('change', actualizarInsumos);
    hectareasInput.addEventListener('input', actualizarInsumos);
});
</script>
<?php render_ada_chat(); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
