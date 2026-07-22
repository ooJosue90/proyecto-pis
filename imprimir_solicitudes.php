<?php
require_once __DIR__ . '/conexion.php';
require_auth('Bodeguero');

$solicitudes = db_fetch_all($conn, "
    SELECT ps.*, u.nombre AS agricultor_nombre,
           COALESCE(l.ubicacion, 'Sin lote asignado') AS lote_ubicacion,
           COALESCE(ia.unidad_medida, '') AS unidad_medida
    FROM productos_solicitud ps
    JOIN usuarios u ON ps.id_agricultor = u.id_usuario
    LEFT JOIN lotes l ON ps.id_lote = l.id_lote
    LEFT JOIN insumos_agricolas ia ON ps.id_insumos = ia.id_insumos
    WHERE ps.estado IN ('Entregado', 'Rechazado', 'Cancelado')
    ORDER BY ps.fecha DESC
");

$solicitudMetricas = ['total' => count($solicitudes), 'Entregado' => 0, 'Rechazado' => 0, 'Cancelado' => 0];
foreach ($solicitudes as $solicitud) {
    $estado = $solicitud['estado'] ?? '';
    if (isset($solicitudMetricas[$estado])) {
        $solicitudMetricas[$estado]++;
    }
}

function warehouse_request_tone(string $estado): string
{
    return match ($estado) {
        'Entregado' => 'success',
        'Rechazado' => 'danger',
        default => 'muted',
    };
}
?>
<?php render_head('Solicitudes procesadas', [
    'https://fonts.googleapis.com/css2?family=Raleway:wght@500;600;700;800;900&family=Roboto+Condensed:wght@400;500;600;700;800;900&display=swap',
    'assets/vendor/bootstrap/bootstrap.min.css',
    'assets/vendor/fontawesome/css/all.min.css',
    'css/admin.css?v=' . filemtime(__DIR__ . '/css/admin.css'),
]); ?>
<body class="farmer-dashboard-page admin-dashboard-page farmer-admin-page warehouse-dashboard-page warehouse-document-page warehouse-requests-page">
<div class="admin-tablet-shell">
    <aside class="sidebar" id="mainSidebar" aria-label="Navegación principal">
        <div class="logo-container">
            <div class="admin-sidebar-logo"><span class="material-symbols-outlined" aria-hidden="true">agriculture</span></div>
            <span class="nav-label admin-sidebar-brand">SembriExport</span>
        </div>
        <nav class="app-sidebar-nav admin-reference-nav">
            <a class="nav-item app-sidebar-link" href="bodeguero.php" title="Bodega">
                <span class="material-symbols-outlined" aria-hidden="true">warehouse</span><span class="nav-label">Bodega</span>
            </a>
            <a class="nav-item app-sidebar-link" href="bodeguero_facturas.php" title="Facturas">
                <span class="material-symbols-outlined" aria-hidden="true">receipt_long</span><span class="nav-label">Facturas</span>
            </a>
            <a class="nav-item app-sidebar-link active" href="imprimir_solicitudes.php" title="Solicitudes">
                <span class="material-symbols-outlined" aria-hidden="true">assignment</span><span class="nav-label">Solicitudes</span>
            </a>
        </nav>
        <div class="admin-sidebar-actions">
            <a class="nav-item" href="logout.php" title="Cerrar sesión">
                <span class="material-symbols-outlined" aria-hidden="true">logout</span><span class="nav-label">Log out</span>
            </a>
        </div>
    </aside>

    <main class="admin-inner-container">
        <header class="admin-reference-topbar no-print">
            <div class="admin-topbar-user">
                <span class="admin-topbar-avatar"><?php echo e(app_user_initials()); ?></span>
                <div><h2>Solicitudes procesadas</h2><p>Consulta la trazabilidad de entregas y cierres realizados por bodega.</p></div>
            </div>
            <div class="admin-topbar-actions">
                <a href="bodeguero.php" class="warehouse-topbar-action warehouse-document-back">
                    <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span><span>Volver a bodega</span>
                </a>
                <button type="button" class="warehouse-topbar-action" onclick="window.print()">
                    <span class="material-symbols-outlined" aria-hidden="true">print</span><span>Imprimir reporte</span>
                </button>
                <div class="admin-account-menu" data-admin-account-menu>
                    <button class="admin-account-button" type="button" aria-haspopup="menu" aria-expanded="false" data-admin-account-trigger>
                        <span class="admin-account-initials"><?php echo e(app_user_initials()); ?></span><span>Cuenta</span>
                        <span class="material-symbols-outlined" aria-hidden="true">expand_more</span>
                    </button>
                    <div class="admin-account-dropdown" role="menu" aria-label="Opciones de cuenta">
                        <div class="admin-account-dropdown__profile"><strong>Bodeguero</strong><small><?php echo e(current_user_name()); ?></small></div>
                        <a href="logout.php" role="menuitem"><span class="material-symbols-outlined">logout</span><span>Cerrar sesión</span></a>
                    </div>
                </div>
            </div>
        </header>

        <div class="warehouse-document-content">
            <?php render_flash_messages(); ?>

            <section class="warehouse-document-hero">
                <div>
                    <span class="farmer-kicker">Historial de operación</span>
                    <h1>Solicitudes procesadas</h1>
                    <p>Revisa productos entregados, solicitudes rechazadas y cancelaciones con su responsable y lote.</p>
                </div>
                <span class="warehouse-document-hero__icon"><span class="material-symbols-outlined">assignment_turned_in</span></span>
            </section>

            <section class="warehouse-document-metrics" aria-label="Resumen de solicitudes">
                <article><span class="material-symbols-outlined">inventory_2</span><div><small>Total procesadas</small><strong><?php echo $solicitudMetricas['total']; ?></strong></div></article>
                <article class="is-success"><span class="material-symbols-outlined">task_alt</span><div><small>Entregadas</small><strong><?php echo $solicitudMetricas['Entregado']; ?></strong></div></article>
                <article class="is-danger"><span class="material-symbols-outlined">block</span><div><small>Rechazadas</small><strong><?php echo $solicitudMetricas['Rechazado']; ?></strong></div></article>
                <article class="is-muted"><span class="material-symbols-outlined">cancel</span><div><small>Canceladas</small><strong><?php echo $solicitudMetricas['Cancelado']; ?></strong></div></article>
            </section>

            <section class="warehouse-document-card">
                <header class="warehouse-document-card__header">
                    <div><span class="material-symbols-outlined">history</span><div><small>Trazabilidad</small><h2>Historial de solicitudes</h2></div></div>
                    <span class="warehouse-document-count"><?php echo count($solicitudes); ?> registros</span>
                </header>

                <div class="warehouse-document-toolbar no-print">
                    <label class="warehouse-document-search">
                        <span class="material-symbols-outlined">search</span>
                        <input type="search" placeholder="Buscar agricultor, producto o lote" data-request-search>
                    </label>
                    <label class="warehouse-document-filter">
                        <span class="material-symbols-outlined">filter_alt</span>
                        <select data-request-status>
                            <option value="">Todos los estados</option>
                            <option value="Entregado">Entregados</option>
                            <option value="Rechazado">Rechazados</option>
                            <option value="Cancelado">Cancelados</option>
                        </select>
                    </label>
                </div>

                <div class="table-responsive warehouse-document-table-wrap">
                    <table class="table warehouse-document-table" data-request-table>
                        <thead><tr><th>Solicitud</th><th>Agricultor</th><th>Producto</th><th>Cantidad</th><th>Lote</th><th>Estado</th><th>Fecha</th></tr></thead>
                        <tbody>
                        <?php if ($solicitudes): ?>
                            <?php foreach ($solicitudes as $solicitud): ?>
                                <?php $tone = warehouse_request_tone((string) $solicitud['estado']); ?>
                                <tr data-request-row data-status="<?php echo e($solicitud['estado']); ?>" data-search="<?php echo e(strtolower($solicitud['agricultor_nombre'] . ' ' . $solicitud['nombre'] . ' ' . $solicitud['lote_ubicacion'])); ?>">
                                    <td><span class="warehouse-document-id">#<?php echo (int) $solicitud['id_producto_solicitud']; ?></span></td>
                                    <td><strong><?php echo e($solicitud['agricultor_nombre']); ?></strong></td>
                                    <td><span class="warehouse-document-product"><span class="material-symbols-outlined">eco</span><?php echo e($solicitud['nombre']); ?></span></td>
                                    <td><strong><?php echo e($solicitud['cantidad_solicitada']); ?></strong> <small><?php echo e($solicitud['unidad_medida']); ?></small></td>
                                    <td><?php echo e($solicitud['lote_ubicacion']); ?></td>
                                    <td><span class="warehouse-document-status is-<?php echo $tone; ?>"><?php echo e($solicitud['estado']); ?></span></td>
                                    <td><time datetime="<?php echo e($solicitud['fecha']); ?>"><?php echo e(date('d/m/Y H:i', strtotime($solicitud['fecha']))); ?></time></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="warehouse-document-empty"><span class="material-symbols-outlined">inbox</span><strong>No hay solicitudes procesadas</strong><small>Las entregas y cierres aparecerán aquí.</small></td></tr>
                        <?php endif; ?>
                        <tr data-request-no-results hidden><td colspan="7" class="warehouse-document-empty"><span class="material-symbols-outlined">search_off</span><strong>Sin coincidencias</strong><small>Ajusta la búsqueda o el estado seleccionado.</small></td></tr>
                        </tbody>
                    </table>
                </div>

                <footer class="warehouse-document-card__footer">
                    <span>Generado el <?php echo date('d/m/Y H:i'); ?> por <?php echo e(current_user_name()); ?></span>
                    <button type="button" class="warehouse-document-print no-print" onclick="window.print()"><span class="material-symbols-outlined">print</span>Imprimir</button>
                </footer>
            </section>
        </div>
    </main>
</div>

<?php render_ada_chat(); ?>
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
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
<?php $conn->close(); ?>
