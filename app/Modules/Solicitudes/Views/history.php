<?php
declare(strict_types=1);
$projectRoot = dirname(__DIR__, 4);
require_once $projectRoot . '/app/Shared/Views/layout.php';

if (!function_exists('request_history_status_class')) {
    function request_history_status_class(string $status): string
    {
        return match (strtolower(trim($status))) {
            'pendiente' => 'pending', 'aprobado' => 'approved', 'entregado' => 'delivered',
            'rechazado' => 'rejected', 'cancelado' => 'cancelled', default => 'neutral',
        };
    }
}
if (!function_exists('request_history_quantity')) {
    function request_history_quantity($quantity): string
    {
        $value = (float) $quantity;
        return number_format($value, $value == floor($value) ? 0 : 2, ',', '.');
    }
}
?>
<?php render_head('Historial de Solicitudes', [
    'https://fonts.googleapis.com/css2?family=Raleway:wght@500;600;700;800;900&family=Roboto+Condensed:wght@400;500;600;700;800;900&display=swap',
    'assets/vendor/bootstrap/bootstrap.min.css',
    'assets/vendor/fontawesome/css/all.min.css',
    'css/admin.css?v=' . filemtime($projectRoot . '/css/admin.css'),
]); ?>
<body class="farmer-dashboard-page admin-dashboard-page farmer-admin-page farmer-request-history-page">
    <div class="admin-tablet-shell">
        <aside class="sidebar" id="mainSidebar" aria-label="Navegación principal">
            <div class="logo-container">
                <div class="admin-sidebar-logo">
                    <i class="fas fa-seedling" aria-hidden="true"></i>
                </div>
                <span class="nav-label admin-sidebar-brand">SembriExport</span>
            </div>

            <nav class="app-sidebar-nav admin-reference-nav">
                <a class="nav-item app-sidebar-link" href="<?= e(\App\Core\Url::route('/dashboard/agricultor')); ?>" title="Dashboard">
                    <span class="material-symbols-outlined" aria-hidden="true">dashboard</span>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a class="nav-item app-sidebar-link" href="<?= e(\App\Core\Url::route('/insumos/calculadora')); ?>" title="Calculadora">
                    <span class="material-symbols-outlined" aria-hidden="true">calculate</span>
                    <span class="nav-label">Calculadora</span>
                </a>
                <a class="nav-item app-sidebar-link active" href="<?= e(\App\Core\Url::route('/solicitudes/historial')); ?>" title="Historial">
                    <span class="material-symbols-outlined" aria-hidden="true">route</span>
                    <span class="nav-label">Historial</span>
                </a>
                <a class="nav-item app-sidebar-link" href="<?= e(\App\Core\Url::route('/plagas')); ?>" title="Fitosanitario">
                    <span class="material-symbols-outlined" aria-hidden="true">health_and_safety</span>
                    <span class="nav-label">Fitosanitario</span>
                </a>
            </nav>

            <div class="admin-sidebar-actions">
                <?php render_logout_control(); ?>
            </div>
        </aside>

        <main class="admin-inner-container">
            <header class="admin-reference-topbar">
                <div class="admin-topbar-user">
                    <span class="admin-topbar-avatar"><?php echo e(app_user_initials()); ?></span>
                    <div>
                        <h2>Saludos, <?php echo e(current_user_name()); ?></h2>
                        <p>Consulta el avance de tus solicitudes agrícolas y el estado de entrega.</p>
                    </div>
                </div>
                <div class="admin-topbar-actions">
                    <div class="admin-account-menu" data-admin-account-menu>
                        <button class="admin-account-button" type="button" aria-haspopup="menu" aria-expanded="false" data-admin-account-trigger>
                            <span class="admin-account-initials" aria-hidden="true"><?php echo e(app_user_initials()); ?></span>
                            <span>Cuenta</span>
                            <span class="material-symbols-outlined" aria-hidden="true">expand_more</span>
                        </button>
                        <div class="admin-account-dropdown" role="menu" aria-label="Opciones de cuenta">
                            <div class="admin-account-dropdown__profile" aria-hidden="true">
                                <strong>Agricultor</strong>
                                <small><?php echo e(current_user_name()); ?></small>
                            </div>
                            <?php render_logout_control('dropdown'); ?>
                        </div>
                    </div>
                </div>
            </header>

<div class="container farmer-dashboard admin-dashboard request-history-dashboard mt-4">
    <?php render_flash_messages(); ?>

    <section class="farmer-page-heading farmer-dashboard-hero">
        <div class="farmer-hero-copy">
            <span class="farmer-kicker">Seguimiento de abastecimiento</span>
            <h1>Historial de solicitudes</h1>
            <p>Consulte el avance de cada insumo solicitado, desde su revisión hasta la entrega en el lote.</p>
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
            </div>
            <span class="request-history-count" data-history-count>
                <?php echo e($historialStats['total']); ?> registros
            </span>
        </header>

        <div class="request-history-toolbar">
            <label class="request-history-search">
                <i class="fas fa-magnifying-glass"></i>
                <input type="search" placeholder="Buscar en la tabla" data-history-search>
            </label>
            <label class="request-history-filter">
                <span>Estado</span>
                <div class="ag-select request-history-status-select" data-history-status-select>
                    <input type="hidden" value="" data-history-status>
                    <button type="button" class="ag-select-button" data-history-status-button aria-haspopup="listbox" aria-expanded="false">
                        <i class="fas fa-filter request-history-status-select__icon" aria-hidden="true"></i>
                        <span data-history-status-label>Todos los estados</span>
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="ag-select-menu" data-history-status-menu role="listbox">
                        <button type="button" class="ag-select-option is-selected" data-value="" role="option" aria-selected="true">Todos los estados</button>
                        <button type="button" class="ag-select-option" data-value="pendiente" role="option" aria-selected="false">Pendiente</button>
                        <button type="button" class="ag-select-option" data-value="aprobado" role="option" aria-selected="false">Aprobado</button>
                        <button type="button" class="ag-select-option" data-value="entregado" role="option" aria-selected="false">Entregado</button>
                        <button type="button" class="ag-select-option" data-value="rechazado" role="option" aria-selected="false">Rechazado</button>
                        <button type="button" class="ag-select-option" data-value="cancelado" role="option" aria-selected="false">Cancelado</button>
                    </div>
                </div>
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
                <a href="<?= e(\App\Core\Url::route('/dashboard/agricultor', ['tab' => 'insumos'])); ?>" class="btn farmer-action-button farmer-action-button--compact">Solicitar insumos</a>
            </div>
        </div>
    </section>
</div>
        </main>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.querySelector('[data-history-search]');
    const status = document.querySelector('[data-history-status]');
    const statusSelect = document.querySelector('[data-history-status-select]');
    const statusButton = document.querySelector('[data-history-status-button]');
    const statusLabel = document.querySelector('[data-history-status-label]');
    const statusOptions = Array.from(document.querySelectorAll('[data-history-status-menu] .ag-select-option'));
    const rows = Array.from(document.querySelectorAll('[data-history-row]'));
    const count = document.querySelector('[data-history-count]');
    const empty = document.querySelector('[data-history-empty]');

    if (!search || !status || !statusSelect || !statusButton || !statusLabel || !count || !empty) {
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

    function closeStatusSelect() {
        statusSelect.classList.remove('is-open');
        statusButton.setAttribute('aria-expanded', 'false');
    }

    statusButton.addEventListener('click', function () {
        const willOpen = !statusSelect.classList.contains('is-open');
        statusSelect.classList.toggle('is-open', willOpen);
        statusButton.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });

    statusOptions.forEach(function (option) {
        option.addEventListener('click', function () {
            status.value = option.dataset.value || '';
            statusLabel.textContent = option.textContent.trim();
            statusOptions.forEach(function (item) {
                const selected = item === option;
                item.classList.toggle('is-selected', selected);
                item.setAttribute('aria-selected', selected ? 'true' : 'false');
            });
            closeStatusSelect();
            status.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    document.addEventListener('click', function (event) {
        if (!statusSelect.contains(event.target)) {
            closeStatusSelect();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeStatusSelect();
        }
    });
});
</script>
<?php render_ada_chat(); ?>
<?php render_scripts(); ?>
</body>
</html>
