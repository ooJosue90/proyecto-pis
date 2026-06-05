<?php
require_once 'conexion.php';
require_auth('Bodeguero');

function warehouse_table_exists(mysqli $conn, string $table): bool
{
    $escaped = $conn->real_escape_string($table);
    return $conn->query("SHOW TABLES LIKE '{$escaped}'")->num_rows > 0;
}

// Estadísticas
$total_insumos = (int) db_value($conn, "SELECT COUNT(*) FROM insumos_agricolas", '', [], 0);
$total_facturas_compra = warehouse_table_exists($conn, 'facturas_compra')
    ? (int) db_value($conn, "SELECT COUNT(*) FROM facturas_compra", '', [], 0)
    : 0;
$total_solicitudes_aprobadas = (int) db_value(
    $conn,
    "SELECT COUNT(*) FROM productos_solicitud WHERE estado = 'Aprobado'",
    '',
    [],
    0
);

// Datos del dashboard
$insumos = db_fetch_all($conn, "
    SELECT ia.*
    FROM insumos_agricolas ia
    ORDER BY ia.nombre ASC
");
$solicitudes = db_fetch_all($conn, "
    SELECT ps.*, u.nombre AS agricultor_nombre,
           COALESCE(l.ubicacion, 'Sin lote asignado') AS lote_ubicacion,
           COALESCE(ps.etapa, 'Sin etapa') AS etapa_lote,
           ia.cantidad AS stock_disponible,
           ia.unidad_medida
    FROM productos_solicitud ps
    JOIN usuarios u ON ps.id_agricultor = u.id_usuario
    LEFT JOIN lotes l ON ps.id_lote = l.id_lote
    LEFT JOIN insumos_agricolas ia ON ps.id_insumos = ia.id_insumos
    WHERE ps.estado = 'Aprobado'
    ORDER BY ps.fecha DESC
");

// Solicitudes que ya no requieren una acción de bodega
$solicitudes_procesadas = db_fetch_all($conn, "
    SELECT ps.*, u.nombre AS agricultor_nombre
    FROM productos_solicitud ps
    JOIN usuarios u ON ps.id_agricultor = u.id_usuario
    WHERE ps.estado IN ('Entregado', 'Rechazado', 'Cancelado')
    ORDER BY ps.fecha DESC
");
?>
<?php render_head('Panel Bodeguero'); ?>
<body class="farmer-dashboard-page warehouse-dashboard-page">
<?php render_app_nav('fas fa-warehouse', 'Bodeguero - ' . current_user_name(), [
    ['href' => 'bodeguero_facturas.php', 'label' => 'Registrar Factura', 'icon' => 'fas fa-file-invoice-dollar', 'class' => 'btn btn-success btn-sm'],
    ['href' => 'logout.php', 'label' => 'Salir', 'icon' => 'fas fa-sign-out-alt', 'class' => 'btn btn-outline-light btn-sm'],
]); ?>

<div class="container farmer-dashboard warehouse-dashboard mt-4">

<?php render_flash_messages(); ?>

<section class="farmer-page-heading warehouse-page-heading">
    <div>
        <span class="farmer-kicker">Bodega agrícola</span>
        <h1>Control de Inventario</h1>
    </div>
    <div class="warehouse-heading-actions">
        <span><i class="fas fa-flask"></i> <?php echo $total_insumos; ?> insumos</span>
        <span><i class="fas fa-check-circle"></i> <?php echo $total_solicitudes_aprobadas; ?> aprobadas</span>
    </div>
</section>

<!-- Dashboard Stats -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card text-white bg-primary">
            <div class="card-body text-center">
                <i class="fas fa-flask fa-2x mb-2"></i>
                <h4><?php echo $total_insumos; ?></h4>
                <p class="mb-0">Total Insumos</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card text-white bg-success">
            <div class="card-body text-center">
                <i class="fas fa-file-invoice-dollar fa-2x mb-2"></i>
                <h4><?php echo $total_facturas_compra; ?></h4>
                <p class="mb-0">Facturas de Compra</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card text-white bg-warning">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <h4><?php echo $total_solicitudes_aprobadas; ?></h4>
                <p class="mb-0">Solicitudes Aprobadas</p>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h5><i class="fas fa-truck-ramp-box"></i> Recepción de insumos</h5>
            <p class="mb-0">Registre la factura cuando el proveedor entregue los productos. El stock se actualizará dentro de la misma operación.</p>
        </div>
        <a href="bodeguero_facturas.php" class="btn btn-primary">
            <i class="fas fa-file-circle-plus"></i> Nueva factura
        </a>
    </div>
</div>

<!-- Tabla Solicitudes Aprobadas -->
<div class="card mt-4">
    <div class="card-header bg-warning text-white">Solicitudes Aprobadas para Bodega</div>
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
                        <td><?php echo e($sol['etapa_lote']); ?></td>
                        <td><?php echo e($sol['nombre']); ?></td>
                        <td><?php echo e($sol['cantidad_solicitada']); ?> <?php echo e($sol['unidad_medida']); ?></td>
                        <td><?php echo e($sol['stock_disponible'] ?? 'No disponible'); ?> <?php echo e($sol['unidad_medida']); ?></td>
                        <td><?php echo e($sol['fecha']); ?></td>
                        <td class="warehouse-actions-cell">
                            <div class="warehouse-action-group">
                                <button
                                    type="button"
                                    class="btn btn-success btn-sm warehouse-action-button"
                                    data-warehouse-action="entregar"
                                    data-request-id="<?php echo e($sol['id_producto_solicitud']); ?>"
                                    data-product="<?php echo e($sol['nombre']); ?>"
                                    data-quantity="<?php echo e($sol['cantidad_solicitada']); ?> <?php echo e($sol['unidad_medida']); ?>">
                                    <i class="fas fa-check"></i>
                                    Entregar
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-danger btn-sm warehouse-action-button"
                                    data-warehouse-action="cancelar"
                                    data-request-id="<?php echo e($sol['id_producto_solicitud']); ?>"
                                    data-product="<?php echo e($sol['nombre']); ?>"
                                    data-quantity="<?php echo e($sol['cantidad_solicitada']); ?> <?php echo e($sol['unidad_medida']); ?>">
                                    <i class="fas fa-xmark"></i>
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
<div class="card mt-4">
    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-check-circle"></i> Solicitudes Procesadas</h5>
        <a href="imprimir_solicitudes.php" target="_blank" class="btn btn-light btn-sm" onclick="event.stopPropagation();"><i class="fas fa-print"></i> Imprimir</a>
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

<div class="modal fade warehouse-confirm-modal" id="warehouseConfirmModal" tabindex="-1" aria-labelledby="warehouseConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="procesar_solicitud.php" id="warehouseConfirmForm">
                <input type="hidden" name="id_producto_solicitud" id="warehouseConfirmRequestId">
                <input type="hidden" name="accion" id="warehouseConfirmAction">

                <div class="modal-header">
                    <span class="warehouse-modal-icon" data-warehouse-modal-icon>
                        <i class="fas fa-check"></i>
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
                    <button type="submit" class="btn warehouse-modal-confirm" data-warehouse-modal-confirm>
                        <i class="fas fa-check"></i>
                        <span>Confirmar entrega</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php render_ada_chat(); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
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
    const confirmIcon = confirmButton.querySelector('i');

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
            confirmIcon.className = isDelivery ? 'fas fa-check' : 'fas fa-xmark';
            icon.innerHTML = isDelivery ? '<i class="fas fa-box-open"></i>' : '<i class="fas fa-ban"></i>';
            modalElement.classList.toggle('is-cancel', !isDelivery);

            modal.show();
        });
    });
});
</script>
</body>
</html>
