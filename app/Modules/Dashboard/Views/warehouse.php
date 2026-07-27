<?php
declare(strict_types=1);

use App\Shared\Domain\CultivationStage;

$projectRoot=dirname(__DIR__,4);require_once $projectRoot.'/app/Shared/Views/layout.php';
?>
<?php render_head('Panel Bodeguero', [
    'https://fonts.googleapis.com/css2?family=Raleway:wght@500;600;700;800;900&family=Roboto+Condensed:wght@400;500;600;700;800;900&display=swap',
    'assets/vendor/bootstrap/bootstrap.min.css',
    'assets/vendor/fontawesome/css/all.min.css',
    'css/admin.css?v=' . filemtime($projectRoot . '/css/admin.css'),
]); ?>
<body class="farmer-dashboard-page admin-dashboard-page farmer-admin-page warehouse-dashboard-page">
    <div class="admin-tablet-shell">
        <aside class="sidebar" id="mainSidebar" aria-label="Navegación principal">
            <div class="logo-container">
                <div class="admin-sidebar-logo">
                    <i class="fas fa-seedling" aria-hidden="true"></i>
                </div>
                <span class="nav-label admin-sidebar-brand">SembriExport</span>
            </div>

            <nav class="app-sidebar-nav admin-reference-nav">
                <a class="nav-item app-sidebar-link active" href="<?= e(\App\Core\Url::route('/dashboard/bodega')); ?>" title="Bodega">
                    <span class="material-symbols-outlined" aria-hidden="true">warehouse</span>
                    <span class="nav-label">Bodega</span>
                </a>
                <a class="nav-item app-sidebar-link" href="<?= e(\App\Core\Url::route('/inventario')); ?>" title="Inventario">
                    <span class="material-symbols-outlined" aria-hidden="true">inventory_2</span>
                    <span class="nav-label">Inventario</span>
                </a>
                <a class="nav-item app-sidebar-link" href="<?= e(\App\Core\Url::route('/facturas/recepcion')); ?>" title="Facturas">
                    <span class="material-symbols-outlined" aria-hidden="true">receipt_long</span>
                    <span class="nav-label">Facturas</span>
                </a>
                <a class="nav-item app-sidebar-link" href="<?= e(\App\Core\Url::route('/reportes/solicitudes')); ?>" title="Solicitudes">
                    <span class="material-symbols-outlined" aria-hidden="true">assignment</span>
                    <span class="nav-label">Solicitudes</span>
                </a>
            </nav>

            <div class="admin-sidebar-actions">
                <?php render_logout_control(); ?>
            </div>
        </aside>
        <div class="admin-mobile-overlay" data-admin-mobile-close></div>

        <main class="admin-inner-container">
            <header class="admin-reference-topbar">
                <button type="button" class="admin-mobile-toggle" data-admin-mobile-toggle aria-label="Abrir menú"><i class="fas fa-bars" aria-hidden="true"></i></button>
                <div class="admin-topbar-user">
                    <span class="admin-topbar-avatar"><?php echo e(app_user_initials()); ?></span>
                    <div>
                        <h2>Saludos, <?php echo e(current_user_name()); ?></h2>
                        <p>Supervisa inventario, compras y solicitudes aprobadas desde bodega.</p>
                    </div>
                </div>
                <div class="admin-topbar-actions">
                    <a href="<?= e(\App\Core\Url::route('/facturas/recepcion')); ?>" class="warehouse-topbar-action">
                        <span class="material-symbols-outlined" aria-hidden="true">receipt_long</span>
                        <span>Registrar factura</span>
                    </a>
                    <div class="admin-account-menu" data-admin-account-menu>
                        <button class="admin-account-button" type="button" aria-haspopup="menu" aria-expanded="false" data-admin-account-trigger>
                            <span class="admin-account-initials" aria-hidden="true"><?php echo e(app_user_initials()); ?></span>
                            <span>Cuenta</span>
                            <span class="material-symbols-outlined" aria-hidden="true">expand_more</span>
                        </button>
                        <div class="admin-account-dropdown" role="menu" aria-label="Opciones de cuenta">
                            <div class="admin-account-dropdown__profile" aria-hidden="true">
                                <strong>Bodeguero</strong>
                                <small><?php echo e(current_user_name()); ?></small>
                            </div>
                            <?php render_logout_control('dropdown'); ?>
                        </div>
                    </div>
                </div>
            </header>

            <div class="container farmer-dashboard warehouse-dashboard admin-dashboard mt-4">

<?php render_flash_messages(); ?>
<?php render_contextual_messages($contextual_messages ?? []); ?>

<section class="farmer-page-heading warehouse-page-heading">
    <div class="warehouse-heading-copy">
        <span class="farmer-kicker">Bodega agrícola</span>
        <h1>Control de Inventario</h1>
        <p>Supervisa existencias, recepciones y entregas desde un centro operativo unificado.</p>
    </div>
    <div class="warehouse-hero-status">
        <span class="warehouse-hero-status-icon"><span class="material-symbols-outlined" aria-hidden="true">warehouse</span></span>
        <div>
            <small>Estado de bodega</small>
            <strong><span class="material-symbols-outlined warehouse-status-dot" aria-hidden="true">circle</span> Operación activa</strong>
        </div>
    </div>
</section>

<!-- Dashboard Stats -->
<section class="warehouse-overview" aria-labelledby="warehouse-overview-title">
    <div class="warehouse-section-heading">
        <div>
            <span>Vista general</span>
            <h2 id="warehouse-overview-title">Indicadores de bodega</h2>
        </div>
        <small><span class="material-symbols-outlined warehouse-status-dot" aria-hidden="true">circle</span> Información actualizada</small>
    </div>

    <div class="warehouse-metrics-grid">
        <article class="warehouse-metric-card warehouse-metric-card--inventory">
            <div class="warehouse-metric-top">
                <span class="warehouse-metric-icon"><span class="material-symbols-outlined" aria-hidden="true">science</span></span>
                <span class="warehouse-metric-tag">Inventario</span>
            </div>
            <strong><?php echo $total_insumos; ?></strong>
            <p>Insumos registrados</p>
            <span class="warehouse-metric-detail">Catálogo disponible en bodega</span>
        </article>

        <article class="warehouse-metric-card warehouse-metric-card--invoices">
            <div class="warehouse-metric-top">
                <span class="warehouse-metric-icon"><span class="material-symbols-outlined" aria-hidden="true">receipt_long</span></span>
                <span class="warehouse-metric-tag">Compras</span>
            </div>
            <strong><?php echo $total_facturas_compra; ?></strong>
            <p>Facturas de compra</p>
            <span class="warehouse-metric-detail">Recepciones documentadas</span>
        </article>

        <article class="warehouse-metric-card warehouse-metric-card--orders">
            <div class="warehouse-metric-top">
                <span class="warehouse-metric-icon"><span class="material-symbols-outlined" aria-hidden="true">shopping_cart</span></span>
                <span class="warehouse-metric-tag">Pendientes</span>
            </div>
            <strong><?php echo $total_pedidos_pendientes; ?></strong>
            <p>Pedidos por comprobar</p>
            <span class="warehouse-metric-detail">Requieren comprobante</span>
        </article>

        <article class="warehouse-metric-card warehouse-metric-card--approved">
            <div class="warehouse-metric-top">
                <span class="warehouse-metric-icon"><span class="material-symbols-outlined" aria-hidden="true">assignment_turned_in</span></span>
                <span class="warehouse-metric-tag">Despacho</span>
            </div>
            <strong><?php echo $total_solicitudes_aprobadas; ?></strong>
            <p>Solicitudes aprobadas</p>
            <span class="warehouse-metric-detail">Listas para procesar</span>
        </article>
    </div>
</section>

<div class="card mb-4 warehouse-module-card" id="warehouse-pending-orders">
    <div class="card-header warehouse-module-header">
        <span class="warehouse-module-icon warehouse-module-icon--orders" aria-hidden="true">
            <span class="material-symbols-outlined" aria-hidden="true">local_shipping</span>
        </span>
        <div>
            <span class="warehouse-module-kicker">Recepción de compras</span>
            <h5 class="mb-0">Pedidos pendientes por comprobar</h5>
        </div>
        <span class="warehouse-module-count"><?php echo $total_pedidos_pendientes; ?> pendientes</span>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Proveedor</th>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Unidad</th>
                    <th>Fecha del pedido</th>
                    <th>Usuario responsable</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pedidos_pendientes): ?>
                    <?php foreach ($pedidos_pendientes as $pedido): ?>
                        <tr>
                            <td><?php echo (int) $pedido['id_pedidos']; ?></td>
                            <td><?php echo e($pedido['proveedor_nombre']); ?></td>
                            <td><?php echo e($pedido['nombre_producto']); ?></td>
                            <td><?php echo e($pedido['cantidad']); ?></td>
                            <td><?php echo e($pedido['unidad_medida']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?></td>
                            <td><?php echo e($pedido['usuario_responsable']); ?></td>
                            <td><span class="badge bg-warning text-dark"><?php echo e($pedido['estado']); ?></span></td>
                            <td>
                                <a
                                    class="btn btn-sm warehouse-receipt-button warehouse-primary-action warehouse-primary-action--compact"
                                    href="<?= e(\App\Core\Url::route('/facturas/recepcion', ['pedido_id' => (int) $pedido['id_pedidos']])); ?>">
                                    <span class="material-symbols-outlined" aria-hidden="true">note_add</span> Registrar comprobante
                                </a>
                                <?php if (empty($pedido['id_insumo'])): ?>
                                    <small class="d-block text-danger mt-1">Debe relacionar el producto al registrar.</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="app-empty-state">No hay pedidos pendientes por comprobar.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Tabla Solicitudes Aprobadas -->
<div class="card mt-4 warehouse-module-card" id="warehouse-approved-requests">
    <div class="card-header warehouse-module-header">
        <span class="warehouse-module-icon warehouse-module-icon--approved"><span class="material-symbols-outlined" aria-hidden="true">inventory_2</span></span>
        <div>
            <span class="warehouse-module-kicker">Preparación de entregas</span>
            <h5 class="mb-0">Solicitudes aprobadas para bodega</h5>
        </div>
        <span class="warehouse-module-count"><?php echo $total_solicitudes_aprobadas; ?> por procesar</span>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-hover warehouse-requests-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Agricultor</th>
                    <th>Lote</th>
                    <th>Etapa</th>
                    <th>Producto</th>
                    <th>Cantidad Solicitada</th>
                    <th>Stock Disponible</th>
                    <th>Fecha</th>
                    <th class="warehouse-actions-column">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($solicitudes)): ?>
                    <?php foreach ($solicitudes as $sol): ?>
                    <tr>
                        <td><?php echo e($sol['id_producto_solicitud']); ?></td>
                        <td><?php echo e($sol['agricultor_nombre']); ?></td>
                        <td><?php echo e($sol['lote_ubicacion']); ?></td>
                        <td><?php echo e(CultivationStage::normalizeName((string) $sol['etapa_lote'])); ?></td>
                        <td><?php echo e($sol['nombre']); ?></td>
                        <td><?php echo e($sol['cantidad_solicitada']); ?> <?php echo e($sol['unidad_medida']); ?></td>
                        <td><?php echo e($sol['stock_disponible'] ?? 'No disponible'); ?> <?php echo e($sol['unidad_medida']); ?></td>
                        <td><?php echo e($sol['fecha']); ?></td>
                        <td class="warehouse-actions-cell">
                            <div class="warehouse-action-group">
                                <button
                                    type="button"
                                    class="btn btn-sm warehouse-action-button warehouse-primary-action warehouse-primary-action--compact"
                                    data-warehouse-action="entregar"
                                    data-request-id="<?php echo e($sol['id_producto_solicitud']); ?>"
                                    data-product="<?php echo e($sol['nombre']); ?>"
                                    data-quantity="<?php echo e($sol['cantidad_solicitada']); ?> <?php echo e($sol['unidad_medida']); ?>">
                                    <span class="material-symbols-outlined" aria-hidden="true">check</span>
                                    Entregar
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-danger btn-sm warehouse-action-button"
                                    data-warehouse-action="cancelar"
                                    data-request-id="<?php echo e($sol['id_producto_solicitud']); ?>"
                                    data-product="<?php echo e($sol['nombre']); ?>"
                                    data-quantity="<?php echo e($sol['cantidad_solicitada']); ?> <?php echo e($sol['unidad_medida']); ?>">
                                    <span class="material-symbols-outlined" aria-hidden="true">close</span>
                                    Cancelar
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="app-empty-state">No hay solicitudes aprobadas para procesar.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Tabla Solicitudes Procesadas -->
<div class="card mt-4 warehouse-module-card">
    <div class="card-header warehouse-module-header">
        <span class="warehouse-module-icon warehouse-module-icon--history"><span class="material-symbols-outlined" aria-hidden="true">history</span></span>
        <div>
            <span class="warehouse-module-kicker">Historial operativo</span>
            <h5 class="mb-0">Solicitudes procesadas</h5>
        </div>
        <a href="<?= e(\App\Core\Url::route('/reportes/solicitudes')); ?>" class="btn btn-light btn-sm warehouse-print-button" data-warehouse-print-report><span class="material-symbols-outlined" aria-hidden="true">print</span> Imprimir</a>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Agricultor</th>
                    <th>Producto</th>
                    <th>Cantidad Solicitada</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($solicitudes_procesadas)): ?>
                    <?php foreach ($solicitudes_procesadas as $sol): ?>
                    <tr>
                        <td><?php echo e($sol['id_producto_solicitud']); ?></td>
                        <td><?php echo e($sol['agricultor_nombre']); ?></td>
                        <td><?php echo e($sol['nombre']); ?></td>
                        <td><?php echo e($sol['cantidad_solicitada']); ?></td>
                        <td><span class="badge bg-<?php
                            echo $sol['estado'] == 'Pendiente' ? 'warning' :
                                ($sol['estado'] == 'Aprobado' ? 'primary' :
                                ($sol['estado'] == 'Entregado' ? 'success' : 'danger'));
                        ?>"><?php echo e($sol['estado']); ?></span></td>
                        <td><?php echo e($sol['fecha']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="app-empty-state">No hay solicitudes procesadas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</main>
</div>

<div class="modal fade warehouse-confirm-modal" id="warehouseConfirmModal" tabindex="-1" aria-labelledby="warehouseConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?php echo e(\App\Core\Url::route('/solicitudes/procesar')); ?>" id="warehouseConfirmForm">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id_producto_solicitud" id="warehouseConfirmRequestId">
                <input type="hidden" name="accion" id="warehouseConfirmAction">

                <div class="modal-header">
                    <span class="warehouse-modal-icon" data-warehouse-modal-icon>
                        <span class="material-symbols-outlined" aria-hidden="true">check</span>
                    </span>
                    <div>
                        <span class="farmer-kicker">Confirmación de bodega</span>
                        <h2 class="modal-title" id="warehouseConfirmTitle">Confirmar entrega</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <p data-warehouse-modal-message>Revise la información antes de continuar.</p>
                    <div class="warehouse-modal-summary">
                        <div>
                            <span>Producto</span>
                            <strong data-warehouse-modal-product></strong>
                        </div>
                        <div>
                            <span>Cantidad</span>
                            <strong data-warehouse-modal-quantity></strong>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn warehouse-modal-back" data-bs-dismiss="modal">Volver</button>
                    <button type="submit" class="btn warehouse-modal-confirm warehouse-primary-action warehouse-primary-action--compact" data-warehouse-modal-confirm>
                        <span class="material-symbols-outlined" aria-hidden="true">check</span>
                        <span>Confirmar entrega</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php render_ada_chat(); ?>
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const printReportButton = document.querySelector('[data-warehouse-print-report]');

    printReportButton?.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        document.querySelector('[data-warehouse-print-frame]')?.remove();

        const printFrame = document.createElement('iframe');
        printFrame.className = 'warehouse-print-frame';
        printFrame.dataset.warehousePrintFrame = 'true';
        printFrame.setAttribute('aria-hidden', 'true');
        printFrame.title = 'Reporte de solicitudes para impresión';

        printFrame.addEventListener('load', function () {
            const printWindow = printFrame.contentWindow;
            if (!printWindow) {
                window.location.href = printReportButton.href;
                return;
            }

            printWindow.addEventListener('afterprint', function () {
                printFrame.remove();
            }, { once: true });
            printWindow.focus();
            printWindow.print();
        }, { once: true });

        printFrame.src = printReportButton.href;
        document.body.appendChild(printFrame);
    });

    const modalElement = document.getElementById('warehouseConfirmModal');

    if (!modalElement || !window.bootstrap?.Modal) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const requestIdInput = document.getElementById('warehouseConfirmRequestId');
    const actionInput = document.getElementById('warehouseConfirmAction');
    const title = document.getElementById('warehouseConfirmTitle');
    const icon = modalElement.querySelector('[data-warehouse-modal-icon]');
    const message = modalElement.querySelector('[data-warehouse-modal-message]');
    const product = modalElement.querySelector('[data-warehouse-modal-product]');
    const quantity = modalElement.querySelector('[data-warehouse-modal-quantity]');
    const confirmButton = modalElement.querySelector('[data-warehouse-modal-confirm]');
    const confirmLabel = confirmButton.querySelector('span');
    const confirmIcon = confirmButton.querySelector('.material-symbols-outlined');

    document.querySelectorAll('[data-warehouse-action]').forEach((button) => {
        button.addEventListener('click', function() {
            const action = button.dataset.warehouseAction;
            const isDelivery = action === 'entregar';

            requestIdInput.value = button.dataset.requestId || '';
            actionInput.value = action;
            product.textContent = button.dataset.product || 'Sin producto';
            quantity.textContent = button.dataset.quantity || 'Sin cantidad';

            title.textContent = isDelivery ? 'Confirmar entrega' : 'Cancelar solicitud';
            message.textContent = isDelivery
                ? 'Al confirmar, la cantidad será descontada del inventario y registrada como salida.'
                : 'La solicitud quedará cancelada y no se modificará el inventario.';
            confirmLabel.textContent = isDelivery ? 'Confirmar entrega' : 'Confirmar cancelación';
            confirmIcon.textContent = isDelivery ? 'check' : 'close';
            icon.innerHTML = isDelivery
                ? '<span class="material-symbols-outlined" aria-hidden="true">inventory_2</span>'
                : '<span class="material-symbols-outlined" aria-hidden="true">block</span>';
            modalElement.classList.toggle('is-cancel', !isDelivery);

            modal.show();
        });
    });
});
</script>
</body>
</html>
