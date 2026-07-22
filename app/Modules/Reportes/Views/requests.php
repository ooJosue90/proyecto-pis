<?php declare(strict_types=1);$projectRoot=dirname(__DIR__,4);require_once $projectRoot.'/app/Shared/Views/layout.php';
if (!function_exists('warehouse_request_tone')) {
    function warehouse_request_tone(string $estado): string
    {
        return match ($estado) {
            'Entregado' => 'in',
            'Rechazado' => 'rejected',
            default => 'out',
        };
    }
}
?>
<?php render_head('Solicitudes procesadas', [
    'https://fonts.googleapis.com/css2?family=Raleway:wght@500;600;700;800;900&family=Roboto+Condensed:wght@400;500;600;700;800;900&display=swap',
    'assets/vendor/bootstrap/bootstrap.min.css',
    'assets/vendor/fontawesome/css/all.min.css',
    'css/admin.css?v=' . filemtime($projectRoot . '/css/admin.css'),
]); ?>
<body class="farmer-dashboard-page admin-dashboard-page farmer-admin-page warehouse-dashboard-page warehouse-requests-page">
<div class="admin-tablet-shell">
    <aside class="sidebar" id="mainSidebar" aria-label="Navegación principal">
        <div class="logo-container">
            <div class="admin-sidebar-logo"><i class="fas fa-seedling" aria-hidden="true"></i></div>
            <span class="nav-label admin-sidebar-brand">SembriExport</span>
        </div>
        <nav class="app-sidebar-nav admin-reference-nav">
            <a class="nav-item app-sidebar-link" href="<?= e(\App\Core\Url::route('/dashboard/bodega')); ?>" title="Bodega">
                <i class="fas fa-warehouse" aria-hidden="true"></i><span class="nav-label">Bodega</span>
            </a>
            <a class="nav-item app-sidebar-link" href="<?= e(\App\Core\Url::route('/facturas/recepcion')); ?>" title="Facturas">
                <i class="fas fa-receipt" aria-hidden="true"></i><span class="nav-label">Facturas</span>
            </a>
            <a class="nav-item app-sidebar-link active" href="<?= e(\App\Core\Url::route('/reportes/solicitudes')); ?>" title="Solicitudes">
                <i class="fas fa-clipboard-check" aria-hidden="true"></i><span class="nav-label">Solicitudes</span>
            </a>
        </nav>
        <div class="admin-sidebar-actions">
            <?php render_logout_control(); ?>
        </div>
    </aside>

    <main class="admin-inner-container">
        <header class="admin-reference-topbar no-print">
            <div class="admin-topbar-user">
                <span class="admin-topbar-avatar"><?php echo e(app_user_initials()); ?></span>
                <div><h2>Saludos, <?php echo e(current_user_name()); ?></h2><p>Gestiona documentos y trazabilidad de bodega.</p></div>
            </div>
            <div class="admin-topbar-actions">
                <div class="admin-account-menu" data-admin-account-menu>
                    <button class="admin-account-button" type="button" aria-haspopup="menu" aria-expanded="false" data-admin-account-trigger>
                        <span class="admin-account-initials"><?php echo e(app_user_initials()); ?></span><span>Cuenta</span>
                        <span class="material-symbols-outlined" aria-hidden="true">expand_more</span>
                    </button>
                    <div class="admin-account-dropdown" role="menu" aria-label="Opciones de cuenta">
                        <div class="admin-account-dropdown__profile"><strong>Bodeguero</strong><small><?php echo e(current_user_name()); ?></small></div>
                        <?php render_logout_control('dropdown'); ?>
                    </div>
                </div>
            </div>
        </header>

        <div class="container farmer-dashboard admin-dashboard mt-4">
            <?php render_flash_messages(); ?>

            <section class="farmer-page-heading admin-page-heading">
                <div class="admin-greeting">
                    <div class="admin-heading-copy">
                        <h1>Solicitudes <span>procesadas</span></h1>
                        <p>Historial de entregas, rechazos y cancelaciones</p>
                    </div>
                </div>
            </section>

            <section class="admin-invoices warehouse-admin-documents">
                <header class="admin-invoice-header">
                    <span class="admin-invoice-header__icon"><i class="fas fa-clipboard-check"></i></span>
                    <div class="admin-invoice-heading-copy"><h4>Historial de solicitudes</h4><p>Consulta la trazabilidad de los movimientos atendidos por bodega.</p></div>
                    <button type="button" class="admin-invoice-count no-print warehouse-admin-print" onclick="window.print()"><i class="fas fa-print"></i>Imprimir</button>
                </header>

                <div class="admin-invoice-stats" aria-label="Resumen de solicitudes">
                    <article class="admin-invoice-stat"><span class="admin-invoice-stat__icon"><i class="fas fa-box-archive"></i></span><div><span>Total procesadas</span><strong><?php echo $solicitudMetricas['total']; ?></strong><small>Registros históricos</small></div></article>
                    <article class="admin-invoice-stat admin-invoice-stat--approved"><span class="admin-invoice-stat__icon"><i class="fas fa-circle-check"></i></span><div><span>Entregadas</span><strong><?php echo $solicitudMetricas['Entregado']; ?></strong><small>Salidas completadas</small></div></article>
                    <article class="admin-invoice-stat admin-invoice-stat--pending"><span class="admin-invoice-stat__icon"><i class="fas fa-ban"></i></span><div><span>Rechazadas</span><strong><?php echo $solicitudMetricas['Rechazado']; ?></strong><small>No autorizadas</small></div></article>
                    <article class="admin-invoice-stat admin-invoice-stat--amount"><span class="admin-invoice-stat__icon"><i class="fas fa-circle-xmark"></i></span><div><span>Canceladas</span><strong><?php echo $solicitudMetricas['Cancelado']; ?></strong><small>Cerradas sin entrega</small></div></article>
                </div>

            <section class="admin-invoice-ledger admin-stat-card warehouse-admin-ledger">
                <div class="admin-invoice-ledger__heading">
                    <h5><i class="fas fa-clock-rotate-left"></i> Solicitudes registradas</h5>
                    <span class="admin-invoice-ledger__count"><?php echo count($solicitudes); ?> registros</span>
                </div>

                <div class="admin-invoice-filters warehouse-request-filters no-print">
                    <label class="admin-invoice-filter">
                        <span class="form-label">Buscar</span>
                        <input class="form-control" type="search" placeholder="Agricultor, producto o lote" data-request-search>
                    </label>
                    <label class="admin-invoice-filter">
                        <span class="form-label">Estado</span>
                        <select class="form-select" data-request-status data-filter-control aria-label="Filtrar solicitudes por estado">
                            <option value="">Todos los estados</option>
                            <option value="Entregado">Entregados</option>
                            <option value="Rechazado">Rechazados</option>
                            <option value="Cancelado">Cancelados</option>
                        </select>
                    </label>
                </div>

                <div class="table-responsive admin-invoice-table-wrap">
                    <table class="table admin-invoice-table" data-request-table>
                        <thead><tr><th>Solicitud</th><th>Agricultor</th><th>Producto</th><th>Cantidad</th><th>Lote</th><th>Estado</th><th>Fecha</th></tr></thead>
                        <tbody>
                        <?php if ($solicitudes): ?>
                            <?php foreach ($solicitudes as $solicitud): ?>
                                <?php $tone = warehouse_request_tone((string) $solicitud['estado']); ?>
                                <tr data-request-row data-status="<?php echo e($solicitud['estado']); ?>" data-search="<?php echo e(strtolower($solicitud['agricultor_nombre'] . ' ' . $solicitud['nombre'] . ' ' . $solicitud['lote_ubicacion'])); ?>">
                                    <td><strong>#<?php echo (int) $solicitud['id_producto_solicitud']; ?></strong></td>
                                    <td><strong><?php echo e($solicitud['agricultor_nombre']); ?></strong></td>
                                    <td><strong><?php echo e($solicitud['nombre']); ?></strong></td>
                                    <td><strong><?php echo e($solicitud['cantidad_solicitada']); ?></strong> <small><?php echo e($solicitud['unidad_medida']); ?></small></td>
                                    <td><?php echo e($solicitud['lote_ubicacion']); ?></td>
                                    <td><span class="admin-movements__status admin-movements__status--<?php echo e($tone); ?>"><?php echo e($solicitud['estado']); ?></span></td>
                                    <td><time datetime="<?php echo e($solicitud['fecha']); ?>"><?php echo e(date('d/m/Y H:i', strtotime($solicitud['fecha']))); ?></time></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="app-empty-state">No hay solicitudes procesadas.</td></tr>
                        <?php endif; ?>
                        <tr data-request-no-results hidden><td colspan="7" class="app-empty-state">No se encontraron coincidencias.</td></tr>
                        </tbody>
                    </table>
                </div>

                <footer class="warehouse-admin-footer">Generado el <?php echo date('d/m/Y H:i'); ?> por <?php echo e(current_user_name()); ?></footer>
            </section>
            </section>
        </div>
    </main>
</div>

<?php render_ada_chat(); ?>
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="js/admin-forms.js?v=<?php echo filemtime($projectRoot . '/js/admin-forms.js'); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.AdminFormMethods?.setupGenericAdminSelects(document);
    const search = document.querySelector('[data-request-search]');
    const status = document.querySelector('[data-request-status]');
    const rows = Array.from(document.querySelectorAll('[data-request-row]'));
    const noResults = document.querySelector('[data-request-no-results]');

    function normalize(value) {
        return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    }

    function filterRows() {
        const query = normalize(search?.value);
        const selectedStatus = status?.value || '';
        let visible = 0;
        rows.forEach(function (row) {
            const matchesSearch = !query || normalize(row.dataset.search).includes(query);
            const matchesStatus = !selectedStatus || row.dataset.status === selectedStatus;
            row.hidden = !(matchesSearch && matchesStatus);
            if (!row.hidden) visible++;
        });
        if (noResults) noResults.hidden = visible > 0 || rows.length === 0;
    }

    search?.addEventListener('input', filterRows);
    status?.addEventListener('change', filterRows);
});
</script>
</body>
</html>
