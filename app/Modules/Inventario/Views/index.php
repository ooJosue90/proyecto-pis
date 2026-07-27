<?php

declare(strict_types=1);

use App\Core\Url;
use App\Shared\Domain\CultivationStage;

$projectRoot = dirname(__DIR__, 4);
require_once $projectRoot . '/app/Shared/Views/layout.php';
$role = (string) ($user['rol'] ?? 'Bodeguero');
$isAdmin = $role === 'Administrador';
?>
<?php render_head('Inventario de productos', [
    'https://fonts.googleapis.com/css2?family=Raleway:wght@500;600;700;800;900&family=Roboto+Condensed:wght@400;500;600;700;800;900&display=swap',
    'assets/vendor/bootstrap/bootstrap.min.css',
    'assets/vendor/fontawesome/css/all.min.css',
    'css/admin.css?v=' . filemtime($projectRoot . '/css/admin.css'),
]); ?>
<body class="farmer-dashboard-page admin-dashboard-page farmer-admin-page warehouse-dashboard-page inventory-page">
<div class="admin-tablet-shell">
    <aside class="sidebar" id="mainSidebar" aria-label="Navegación principal">
        <div class="logo-container">
            <div class="admin-sidebar-logo"><i class="fas fa-seedling" aria-hidden="true"></i></div>
            <span class="nav-label admin-sidebar-brand">SembriExport</span>
        </div>
        <nav class="app-sidebar-nav admin-reference-nav">
            <?php if ($isAdmin): ?>
                <a class="nav-item app-sidebar-link" href="<?= e(Url::route('/dashboard/admin')); ?>" title="Dashboard">
                    <i class="fas fa-gauge-high" aria-hidden="true"></i><span class="nav-label">Dashboard</span>
                </a>
                <a class="nav-item app-sidebar-link" href="<?= e(Url::route('/dashboard/admin') . '#usuarios'); ?>" title="Usuarios">
                    <i class="fas fa-users-gear" aria-hidden="true"></i><span class="nav-label">Usuarios</span>
                </a>
                <a class="nav-item app-sidebar-link" href="<?= e(Url::route('/dashboard/admin') . '#solicitudes'); ?>" title="Solicitudes">
                    <i class="fas fa-clipboard-check" aria-hidden="true"></i><span class="nav-label">Solicitudes</span>
                </a>
                <a class="nav-item app-sidebar-link" href="<?= e(Url::route('/dashboard/admin') . '#movimientos'); ?>" title="Movimientos">
                    <i class="fas fa-arrow-right-arrow-left" aria-hidden="true"></i><span class="nav-label">Movimientos</span>
                </a>
                <a class="nav-item app-sidebar-link active" href="<?= e(Url::route('/inventario')); ?>" title="Inventario">
                    <i class="fas fa-boxes-stacked" aria-hidden="true"></i><span class="nav-label">Inventario</span>
                </a>
                <a class="nav-item app-sidebar-link" href="<?= e(Url::route('/dashboard/admin') . '#facturas'); ?>" title="Facturas">
                    <i class="fas fa-receipt" aria-hidden="true"></i><span class="nav-label">Facturas</span>
                </a>
                <a class="nav-item app-sidebar-link" href="<?= e(Url::route('/dashboard/admin') . '#cultivos'); ?>" title="Cultivos">
                    <i class="fas fa-seedling" aria-hidden="true"></i><span class="nav-label">Cultivos</span>
                </a>
                <a class="nav-item app-sidebar-link" href="<?= e(Url::route('/dashboard/admin') . '#pedidos-proveedores'); ?>" title="Proveedores">
                    <i class="fas fa-truck-fast" aria-hidden="true"></i><span class="nav-label">Proveedores</span>
                </a>
                <a class="nav-item app-sidebar-link" href="<?= e(Url::route('/dashboard/admin') . '#reportes'); ?>" title="Reportes">
                    <i class="fas fa-chart-simple" aria-hidden="true"></i><span class="nav-label">Reportes</span>
                </a>
            <?php else: ?>
                <a class="nav-item app-sidebar-link" href="<?= e(Url::route('/dashboard/bodega')); ?>" title="Bodega">
                    <i class="fas fa-warehouse" aria-hidden="true"></i><span class="nav-label">Bodega</span>
                </a>
                <a class="nav-item app-sidebar-link active" href="<?= e(Url::route('/inventario')); ?>" title="Inventario">
                    <i class="fas fa-boxes-stacked" aria-hidden="true"></i><span class="nav-label">Inventario</span>
                </a>
                <a class="nav-item app-sidebar-link" href="<?= e(Url::route('/facturas/recepcion')); ?>" title="Facturas">
                    <i class="fas fa-receipt" aria-hidden="true"></i><span class="nav-label">Facturas</span>
                </a>
                <a class="nav-item app-sidebar-link" href="<?= e(Url::route('/reportes/solicitudes')); ?>" title="Solicitudes">
                    <i class="fas fa-clipboard-check" aria-hidden="true"></i><span class="nav-label">Solicitudes</span>
                </a>
            <?php endif; ?>
        </nav>
        <div class="admin-sidebar-actions"><?php render_logout_control(); ?></div>
    </aside>
    <div class="admin-mobile-overlay" data-admin-mobile-close></div>

    <main class="admin-inner-container">
        <header class="admin-reference-topbar">
            <button type="button" class="admin-mobile-toggle" data-admin-mobile-toggle aria-label="Abrir menú"><i class="fas fa-bars" aria-hidden="true"></i></button>
            <div class="admin-topbar-user">
                <span class="admin-topbar-avatar"><?= e(app_user_initials()); ?></span>
                <div>
                    <h2>Saludos, <?= e(current_user_name()); ?></h2>
                    <p><?= e($isAdmin ? 'Supervisa el inventario general y sus niveles de stock.' : 'Consulta productos y existencias de la bodega.'); ?></p>
                </div>
            </div>
            <div class="admin-topbar-actions">
                <div class="admin-account-menu" data-admin-account-menu>
                    <button class="admin-account-button" type="button" aria-haspopup="menu" aria-expanded="false" data-admin-account-trigger>
                        <span class="admin-account-initials" aria-hidden="true"><?= e(app_user_initials()); ?></span>
                        <span>Cuenta</span><span class="material-symbols-outlined" aria-hidden="true">expand_more</span>
                    </button>
                    <div class="admin-account-dropdown" role="menu" aria-label="Opciones de cuenta">
                        <div class="admin-account-dropdown__profile"><strong><?= e($role); ?></strong><small><?= e(current_user_name()); ?></small></div>
                        <?php render_logout_control('dropdown'); ?>
                    </div>
                </div>
            </div>
        </header>

        <div class="container farmer-dashboard admin-dashboard inventory-dashboard mt-4">
            <?php if ($success): ?>
                <div class="app-notification-stack" data-app-notification-stack aria-live="polite">
                    <div class="app-notification app-notification--success" data-app-notification data-duration="8000" role="status">
                        <span class="app-notification__icon"><i class="fas fa-check"></i></span>
                        <span class="app-notification__content"><strong class="app-notification__title">Inventario actualizado</strong><span class="app-notification__message"><?= e($success); ?></span></span>
                        <button class="app-notification__close" type="button" data-app-notification-close aria-label="Cerrar"><i class="fas fa-xmark"></i></button>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="app-notification-stack" data-app-notification-stack aria-live="assertive">
                    <div class="app-notification app-notification--danger" data-app-notification data-duration="10000" role="alert">
                        <span class="app-notification__icon"><i class="fas fa-exclamation"></i></span>
                        <span class="app-notification__content"><strong class="app-notification__title">Revise los datos</strong><span class="app-notification__message"><?= e($error); ?></span></span>
                        <button class="app-notification__close" type="button" data-app-notification-close aria-label="Cerrar"><i class="fas fa-xmark"></i></button>
                    </div>
                </div>
            <?php endif; ?>

            <section class="farmer-page-heading admin-page-heading">
                <div class="admin-heading-copy">
                    <span class="farmer-kicker"><?= e($isAdmin ? 'Control administrativo' : 'Bodega agrícola'); ?></span>
                    <h1>Inventario <span>de productos</span></h1>
                    <p><?= e($isAdmin ? 'Vista general de todos los productos y existencias del sistema.' : 'Consulta el stock consolidado de los productos recibidos.'); ?></p>
                </div>
                <span class="inventory-total-badge"><span class="material-symbols-outlined" aria-hidden="true">inventory_2</span><?= count($items); ?> productos</span>
            </section>

            <section class="card warehouse-module-card inventory-list-card" aria-labelledby="inventory-list-title">
                <header class="warehouse-module-header">
                    <span class="warehouse-module-icon warehouse-module-icon--inventory"><span class="material-symbols-outlined" aria-hidden="true">inventory</span></span>
                    <div><span class="warehouse-module-kicker">Existencias actuales</span><h2 id="inventory-list-title">Productos registrados</h2></div>
                    <span class="warehouse-module-count"><?= count($items); ?> en catálogo</span>
                </header>
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle warehouse-inventory-table"
                           data-app-table-owner="inventory-products-table"
                           data-app-table-extra-filter="Tipo">
                        <thead><tr><th>Producto</th><th>Tipo</th><th>Descripción</th><th>Unidad</th><th>Stock acumulado</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php if ($items !== []): ?>
                            <?php foreach ($items as $item): ?>
                                <?php
                                    $stock = (float) $item['cantidad'];
                                    $level = $stock <= 0 ? 'critical' : ($stock <= 5 ? 'low' : 'available');
                                    $label = $stock <= 0 ? 'Agotado' : ($stock <= 5 ? 'Stock bajo' : 'Disponible');
                                ?>
                                <tr>
                                    <td><strong class="warehouse-inventory-product"><?= e($item['nombre']); ?></strong><small>#<?= (int) $item['id_insumos']; ?></small></td>
                                    <td><span class="warehouse-stage-badge"><?= e(CultivationStage::normalizeName((string) $item['tipo'])); ?></span></td>
                                    <td><?= e($item['descripcion'] ?: 'Sin descripción'); ?></td>
                                    <td><?= e($item['unidad_medida']); ?></td>
                                    <td><strong><?= e($item['cantidad']); ?></strong></td>
                                    <td><span class="warehouse-stock-status warehouse-stock-status--<?= e($level); ?>"><?= e($label); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="app-empty-state">Todavía no hay productos registrados. Registre la recepción de una factura para incorporarlos.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</div>
<?php render_ada_chat(); ?>
<?php render_scripts(); ?>
</body>
</html>
