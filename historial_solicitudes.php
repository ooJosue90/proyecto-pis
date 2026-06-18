<?php
require_once 'conexion.php';
require_auth('Agricultor');

$id_usuario = $_SESSION['id_usuario'];
$solicitudes = db_fetch_all(
    $conn,
    "SELECT
        ps.id_producto_solicitud,
        ps.id_lote,
        ps.nombre,
        ps.cantidad_solicitada,
        ps.observaciones,
        ps.fecha,
        ps.estado,
        l.ubicacion AS lote_ubicacion,
        ia.unidad_medida
     FROM productos_solicitud ps
     LEFT JOIN lotes l ON l.id_lote = ps.id_lote
     LEFT JOIN insumos_agricolas ia ON ia.id_insumos = ps.id_insumos
     WHERE ps.id_agricultor = ?
     ORDER BY ps.fecha DESC, ps.id_producto_solicitud DESC",
    "s",
    [$id_usuario]
);

$historialStats = [
    'total' => count($solicitudes),
    'pendiente' => 0,
    'aprobado' => 0,
    'entregado' => 0,
    'cerrado' => 0,
];

foreach ($solicitudes as $solicitud) {
    $estado = strtolower(trim($solicitud['estado'] ?? ''));

    if (isset($historialStats[$estado])) {
        $historialStats[$estado]++;
        continue;
    }

    if (in_array($estado, ['rechazado', 'cancelado'], true)) {
        $historialStats['cerrado']++;
    }
}

function request_history_status_class(string $status): string
{
    $normalized = strtolower(trim($status));

    return match ($normalized) {
        'pendiente' => 'pending',
        'aprobado' => 'approved',
        'entregado' => 'delivered',
        'rechazado' => 'rejected',
        'cancelado' => 'cancelled',
        default => 'neutral',
    };
}

function request_history_quantity($quantity): string
{
    $value = (float) $quantity;

    return number_format($value, $value == floor($value) ? 0 : 2, ',', '.');
}
?>
<?php render_head('Historial de Solicitudes'); ?>
<body class="farmer-dashboard-page farmer-request-history-page">
<?php render_app_nav('fas fa-clock-rotate-left', current_user_name() . ' - Historial'); ?>

<main class="container farmer-dashboard mt-4">
    <?php render_flash_messages(); ?>

    <section class="farmer-page-heading farmer-dashboard-hero">
        <div class="farmer-hero-copy">
            <span class="farmer-kicker">Seguimiento de abastecimiento</span>
            <h1>Historial de solicitudes</h1>
            <p>Consulte el avance de cada insumo solicitado, desde su revisión hasta la entrega en el lote.</p>
        </div>
        <div class="farmer-hero-status">
            <span class="farmer-hero-status-icon"><i class="fas fa-clock-rotate-left"></i></span>
            <div>
                <small>Solicitudes registradas</small>
                <strong><i class="fas fa-circle"></i> <?php echo e($historialStats['total']); ?> en el historial</strong>
            </div>
        </div>
    </section>

    <section class="request-history-metrics" aria-label="Resumen de solicitudes">
        <article class="request-history-metric request-history-metric--total">
            <span class="request-history-metric__icon"><i class="fas fa-layer-group"></i></span>
            <div>
                <small>Total</small>
                <strong><?php echo e($historialStats['total']); ?></strong>
                <p>Registros acumulados</p>
            </div>
        </article>
        <article class="request-history-metric request-history-metric--pending">
            <span class="request-history-metric__icon"><i class="fas fa-clock"></i></span>
            <div>
                <small>Pendientes</small>
                <strong><?php echo e($historialStats['pendiente']); ?></strong>
                <p>Esperan revisión</p>
            </div>
        </article>
        <article class="request-history-metric request-history-metric--approved">
            <span class="request-history-metric__icon"><i class="fas fa-circle-check"></i></span>
            <div>
                <small>Aprobadas</small>
                <strong><?php echo e($historialStats['aprobado']); ?></strong>
                <p>Listas para bodega</p>
            </div>
        </article>
        <article class="request-history-metric request-history-metric--delivered">
            <span class="request-history-metric__icon"><i class="fas fa-box-open"></i></span>
            <div>
                <small>Entregadas</small>
                <strong><?php echo e($historialStats['entregado']); ?></strong>
                <p>Proceso completado</p>
            </div>
        </article>
    </section>

    <section class="request-history-card">
        <header class="request-history-card__header">
            <div>
                <span class="request-history-card__eyebrow">Registro operativo</span>
                <h2>Solicitudes realizadas</h2>
                <p>Busque por insumo, lote u observación y filtre los resultados por estado.</p>
            </div>
            <span class="request-history-count" data-history-count>
                <?php echo e($historialStats['total']); ?> registros
            </span>
        </header>

        <div class="request-history-toolbar">
            <label class="request-history-search">
                <i class="fas fa-magnifying-glass"></i>
                <input type="search" placeholder="Buscar insumo, lote u observación" data-history-search>
            </label>
            <label class="request-history-filter">
                <span>Estado</span>
                <select class="form-select" data-history-status>
                    <option value="">Todos los estados</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="aprobado">Aprobado</option>
                    <option value="entregado">Entregado</option>
                    <option value="rechazado">Rechazado</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </label>
        </div>

        <div class="request-history-table-wrap">
            <table class="request-history-table">
                <thead>
                    <tr>
                        <th>Solicitud</th>
                        <th>Insumo</th>
                        <th>Lote</th>
                        <th>Cantidad</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody data-history-body>
                    <?php foreach ($solicitudes as $solicitud): ?>
                        <?php
                        $status = trim($solicitud['estado'] ?? 'Sin estado');
                        $statusKey = strtolower($status);
                        $date = strtotime($solicitud['fecha'] ?? '');
                        $location = trim($solicitud['lote_ubicacion'] ?? '');
                        $notes = trim($solicitud['observaciones'] ?? '');
                        $searchText = strtolower(implode(' ', [
                            $solicitud['nombre'] ?? '',
                            $solicitud['id_lote'] ?? '',
                            $location,
                            $notes,
                            $status,
                        ]));
                        ?>
                        <tr data-history-row
                            data-status="<?php echo e($statusKey); ?>"
                            data-search="<?php echo e($searchText); ?>">
                            <td>
                                <span class="request-history-id">#<?php echo e($solicitud['id_producto_solicitud']); ?></span>
                            </td>
                            <td>
                                <div class="request-history-product">
                                    <span><i class="fas fa-flask"></i></span>
                                    <strong><?php echo e($solicitud['nombre']); ?></strong>
                                </div>
                            </td>
                            <td>
                                <div class="request-history-lot">
                                    <strong>Lote #<?php echo e($solicitud['id_lote'] ?: 'N/D'); ?></strong>
                                    <small><?php echo e($location !== '' ? $location : 'Ubicación no disponible'); ?></small>
                                </div>
                            </td>
                            <td>
                                <span class="request-history-quantity">
                                    <?php echo e(request_history_quantity($solicitud['cantidad_solicitada'])); ?>
                                    <small><?php echo e($solicitud['unidad_medida'] ?: 'unidades'); ?></small>
                                </span>
                            </td>
                            <td>
                                <time class="request-history-date" datetime="<?php echo e($solicitud['fecha']); ?>">
                                    <strong><?php echo e($date ? date('d/m/Y', $date) : 'Sin fecha'); ?></strong>
                                    <small><?php echo e($date ? date('H:i', $date) : ''); ?></small>
                                </time>
                            </td>
                            <td>
                                <span class="request-history-status request-history-status--<?php echo e(request_history_status_class($status)); ?>">
                                    <i></i>
                                    <?php echo e($status); ?>
                                </span>
                            </td>
                            <td>
                                <span class="request-history-notes" title="<?php echo e($notes !== '' ? $notes : 'Sin observaciones'); ?>">
                                    <?php echo e($notes !== '' ? $notes : 'Sin observaciones'); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="request-history-empty <?php echo $solicitudes ? 'd-none' : ''; ?>" data-history-empty>
                <span><i class="fas fa-clipboard-list"></i></span>
                <h3><?php echo $solicitudes ? 'No encontramos coincidencias' : 'Aún no hay solicitudes'; ?></h3>
                <p><?php echo $solicitudes ? 'Pruebe con otra búsqueda o cambie el filtro seleccionado.' : 'Cuando solicite insumos desde su panel, aparecerán aquí.'; ?></p>
                <a href="agricultor.php?tab=insumos" class="btn farmer-action-button farmer-action-button--compact">Solicitar insumos</a>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.querySelector('[data-history-search]');
    const status = document.querySelector('[data-history-status]');
    const rows = Array.from(document.querySelectorAll('[data-history-row]'));
    const count = document.querySelector('[data-history-count]');
    const empty = document.querySelector('[data-history-empty]');

    if (!search || !status || !count || !empty) {
        return;
    }

    function normalize(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function filterHistory() {
        const query = normalize(search.value);
        const selectedStatus = normalize(status.value);
        let visible = 0;

        rows.forEach(function (row) {
            const matchesSearch = !query || normalize(row.dataset.search).includes(query);
            const matchesStatus = !selectedStatus || normalize(row.dataset.status) === selectedStatus;
            const show = matchesSearch && matchesStatus;

            row.hidden = !show;
            if (show) {
                visible++;
            }
        });

        count.textContent = visible + (visible === 1 ? ' registro' : ' registros');
        empty.classList.toggle('d-none', visible !== 0);
    }

    search.addEventListener('input', filterHistory);
    status.addEventListener('change', filterHistory);
});
</script>
<?php render_ada_chat(); ?>
<?php render_scripts(); ?>
</body>
</html>
