<?php declare(strict_types=1);$projectRoot=dirname(__DIR__,4);require_once $projectRoot.'/app/Shared/Views/layout.php';
?>
<?php render_head('Facturas de Compra', [
    'https://fonts.googleapis.com/css2?family=Raleway:wght@500;600;700;800;900&family=Roboto+Condensed:wght@400;500;600;700;800;900&display=swap',
    'assets/vendor/bootstrap/bootstrap.min.css',
    'assets/vendor/fontawesome/css/all.min.css',
    'css/admin.css?v=' . filemtime($projectRoot . '/css/admin.css'),
]); ?>
<body class="farmer-dashboard-page admin-dashboard-page farmer-admin-page warehouse-dashboard-page purchase-invoice-page">
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
            <a class="nav-item app-sidebar-link active" href="<?= e(\App\Core\Url::route('/facturas/recepcion')); ?>" title="Facturas">
                <i class="fas fa-receipt" aria-hidden="true"></i><span class="nav-label">Facturas</span>
            </a>
            <a class="nav-item app-sidebar-link" href="<?= e(\App\Core\Url::route('/reportes/solicitudes')); ?>" title="Solicitudes">
                <i class="fas fa-clipboard-check" aria-hidden="true"></i><span class="nav-label">Solicitudes</span>
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
                <div><h2>Saludos, <?php echo e(current_user_name()); ?></h2><p>Gestiona documentos y recepciones de bodega.</p></div>
            </div>
            <div class="admin-topbar-actions">
                <div class="admin-account-menu" data-admin-account-menu>
                    <button class="admin-account-button" type="button" aria-haspopup="menu" aria-expanded="false" data-admin-account-trigger>
                        <span class="admin-account-initials" aria-hidden="true"><?php echo e(app_user_initials()); ?></span>
                        <span>Cuenta</span><span class="material-symbols-outlined" aria-hidden="true">expand_more</span>
                    </button>
                    <div class="admin-account-dropdown" role="menu" aria-label="Opciones de cuenta">
                        <div class="admin-account-dropdown__profile"><strong>Bodeguero</strong><small><?php echo e(current_user_name()); ?></small></div>
                        <?php render_logout_control('dropdown'); ?>
                    </div>
                </div>
            </div>
        </header>

        <div class="container farmer-dashboard admin-dashboard mt-4 purchase-invoice-dashboard">
    <?php render_flash_messages(); ?>

    <section class="farmer-page-heading admin-page-heading">
        <div class="admin-greeting">
            <div class="admin-heading-copy">
                <h1>Facturas <span>de compra</span></h1>
                <p>Recepción de pedidos e ingreso de inventario</p>
            </div>
        </div>
    </section>

    <?php if (!$tablesReady): ?>
        <div class="alert alert-warning">
            <i class="fas fa-triangle-exclamation"></i>
            El módulo requiere ejecutar <strong>actualizar_flujo_pedidos_facturas.sql</strong> en phpMyAdmin.
        </div>
    <?php else: ?>
        <section class="admin-invoices warehouse-admin-documents">
            <header class="admin-invoice-header">
                <span class="admin-invoice-header__icon"><i class="fas fa-file-invoice-dollar"></i></span>
                <div class="admin-invoice-heading-copy">
                    <h4>Registrar factura de compra</h4>
                    <p>Confirma el pedido, registra el comprobante y actualiza las existencias.</p>
                </div>
                <span class="admin-invoice-count"><i class="fas fa-truck-fast"></i><?php echo count($pedidosPendientes); ?> pendientes</span>
            </header>

        <div class="admin-stat-card warehouse-admin-form-card">
            <div class="admin-invoice-section-heading">
                <span><i class="fas fa-file-circle-plus"></i></span>
                <div><h5>Datos de la factura</h5><p>Los campos marcados con * son obligatorios.</p></div>
            </div>
                <form method="POST" id="purchaseInvoiceForm">
                    <input type="hidden" name="_token" value="<?php echo e($csrfToken); ?>">
                    <div class="purchase-invoice-workspace">
                        <div class="purchase-invoice-form-column">
                            <section class="purchase-form-section">
                                <div class="purchase-form-section-heading">
                                    <span class="purchase-form-section-number">1</span>
                                    <div>
                                        <h5>Documento y pedido</h5>
                                        <p>Seleccione el pedido e ingrese los datos del comprobante.</p>
                                    </div>
                                </div>
                                <div class="purchase-invoice-main-fields">
                                    <div class="purchase-field purchase-field--order">
                                        <label class="form-label">Pedido relacionado *</label>
                                        <?php if ($pedidoSeleccionado): ?>
                                            <input type="hidden" name="id_pedido" value="<?php echo (int) $pedidoSeleccionado['id_pedidos']; ?>">
                                        <?php endif; ?>
                                        <select
                                            id="purchaseOrderSelect"
                                            class="form-select"
                                            <?php echo $pedidoSeleccionado ? 'disabled' : 'name="id_pedido"'; ?>
                                            required>
                                            <option value="">Seleccione un pedido pendiente</option>
                                            <?php foreach ($pedidosPendientes as $pedido): ?>
                                                <option
                                                    value="<?php echo (int) $pedido['id_pedidos']; ?>"
                                                    data-provider="<?php echo e($pedido['proveedor_nombre']); ?>"
                                                    data-product="<?php echo e($pedido['nombre_producto']); ?>"
                                                    data-quantity="<?php echo e($pedido['cantidad']); ?>"
                                                    data-unit="<?php echo e($pedido['unidad_medida']); ?>"
                                                    data-user="<?php echo e($pedido['usuario_responsable']); ?>"
                                                    data-item-id="<?php echo (int) ($pedido['id_insumo'] ?? 0); ?>"
                                                    <?php echo $pedidoSeleccionado && (int) $pedidoSeleccionado['id_pedidos'] === (int) $pedido['id_pedidos'] ? 'selected' : ''; ?>>
                                                    Pedido #<?php echo (int) $pedido['id_pedidos']; ?> -
                                                    <?php echo e($pedido['proveedor_nombre']); ?> -
                                                    <?php echo e($pedido['nombre_producto']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="purchase-field">
                                        <label class="form-label">Número de factura *</label>
                                        <input type="text" name="numero_factura" class="form-control" maxlength="60" placeholder="Ej. 001-001-000012345" required>
                                    </div>
                                    <div class="purchase-field">
                                        <label class="form-label">Fecha *</label>
                                        <input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                            </section>

                            <section class="purchase-form-section">
                                <div class="purchase-form-section-heading">
                                    <span class="purchase-form-section-number">2</span>
                                    <div>
                                        <h5>Recepción del producto</h5>
                                        <p>Confirme el producto y la cantidad que ingresará al inventario.</p>
                                    </div>
                                </div>

                                <div class="purchase-order-summary">
                        <div class="purchase-field">
                            <label class="form-label">Proveedor</label>
                            <input type="text" id="purchaseOrderProvider" class="form-control" readonly>
                        </div>
                        <div class="purchase-field">
                            <label class="form-label">Producto</label>
                            <input type="text" id="purchaseOrderProduct" class="form-control" readonly>
                        </div>
                        <div class="purchase-field">
                            <label class="form-label">Usuario responsable</label>
                            <input type="text" id="purchaseOrderUser" class="form-control" readonly>
                        </div>
                        <div class="purchase-field">
                            <label class="form-label">Cantidad pedida</label>
                            <input type="text" id="purchaseOrderQuantity" class="form-control" readonly>
                        </div>
                                </div>

                                <div class="purchase-inventory-link" id="purchaseInventoryItemField" hidden>
                        <div class="purchase-inventory-link-copy">
                            <span class="purchase-inventory-link-icon"><i class="fas fa-link"></i></span>
                            <div>
                                <strong>Relacionar con inventario</strong>
                                <p>Este pedido necesita asociarse con un producto existente o uno nuevo.</p>
                            </div>
                        </div>
                        <div class="purchase-field">
                            <label class="form-label">Producto que ingresará al inventario *</label>
                            <select
                                name="id_insumo"
                                id="purchaseInventoryItem"
                                class="form-select"
                                >
                                <option value="">Seleccione un producto</option>
                                <option value="-1">+ Crear un nuevo producto de inventario</option>
                                <?php foreach ($insumosDisponibles as $insumo): ?>
                                    <option
                                        value="<?php echo (int) $insumo['id_insumos']; ?>"
                                        data-product="<?php echo e($insumo['nombre']); ?>"
                                        data-unit="<?php echo e($insumo['unidad_medida']); ?>">
                                        <?php echo e($insumo['nombre']); ?>
                                        <?php if (!empty($insumo['tipo'])): ?>
                                            - <?php echo e($insumo['tipo']); ?>
                                        <?php endif; ?>
                                        (stock: <?php echo e($insumo['cantidad']); ?> <?php echo e($insumo['unidad_medida']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                                </div>

                                <div class="purchase-new-item" id="purchaseNewInventoryItemFields" hidden>
                        <div class="purchase-new-item-heading">
                            <span><i class="fas fa-box-open"></i></span>
                            <div>
                                <h6>Nuevo producto de inventario</h6>
                                <p>Se creará con stock inicial 0; la cantidad recibida se agregará al guardar.</p>
                            </div>
                        </div>
                        <div class="purchase-new-item-grid">
                            <div class="purchase-field purchase-field--wide">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="nuevo_insumo_nombre" id="newInventoryItemName" class="form-control" maxlength="200">
                            </div>
                            <div class="purchase-field purchase-field--wide">
                                <label class="form-label">Tipo *</label>
                                <select
                                    name="nuevo_insumo_tipo"
                                    id="newInventoryItemType"
                                    class="form-select"
                                    >
                                    <option value="">Seleccione una clasificación</option>
                                    <?php foreach ($tiposInsumoPermitidos as $tipoInsumo): ?>
                                        <option value="<?php echo e($tipoInsumo); ?>">
                                            <?php echo e($tipoInsumo); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="purchase-field">
                                <label class="form-label">Unidad *</label>
                                <input type="text" name="nuevo_insumo_unidad" id="newInventoryItemUnit" class="form-control" maxlength="50" placeholder="kg, L, unid">
                            </div>
                            <div class="purchase-field">
                                <label class="form-label">Observación</label>
                                <input type="text" name="nuevo_insumo_observaciones" class="form-control" maxlength="500">
                            </div>
                            <div class="purchase-field purchase-field--full">
                                <label class="form-label">Descripción</label>
                                <textarea name="nuevo_insumo_descripcion" class="form-control" rows="2" maxlength="1000"></textarea>
                            </div>
                        </div>
                                </div>

                                <div class="purchase-receipt-grid">
                        <div class="purchase-field">
                            <label class="form-label">Cantidad recibida *</label>
                            <input type="number" name="cantidad_recibida" id="purchaseReceivedQuantity" class="form-control" min="0.01" step="0.01" required>
                        </div>
                        <div class="purchase-field">
                            <label class="form-label">Unidad</label>
                            <input type="text" id="purchaseOrderUnit" class="form-control" readonly>
                        </div>
                        <div class="purchase-field">
                            <label class="form-label">Precio unitario *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="precio_unitario" id="purchaseUnitPrice" class="form-control" min="0" step="0.01" required>
                            </div>
                        </div>
                                </div>
                            </section>
                        </div>

                        <aside class="purchase-invoice-sidebar">
                            <div class="purchase-invoice-sidebar-title">
                                <span><i class="fas fa-receipt"></i></span>
                                <div>
                                    <small>Resumen</small>
                                    <h5>Factura de compra</h5>
                                </div>
                            </div>
                            <div class="purchase-invoice-total-card">
                                <span>Total calculado</span>
                                <strong data-purchase-total-mirror>$0.00</strong>
                                <small>Cantidad recibida × precio unitario</small>
                            </div>
                            <div class="purchase-field">
                                <label class="form-label">Observación</label>
                                <textarea name="observaciones" class="form-control" rows="5" maxlength="1000" placeholder="Información adicional de la recepción"></textarea>
                            </div>
                            <div class="purchase-invoice-checklist">
                                <span><i class="fas fa-check-circle"></i> Actualiza el stock</span>
                                <span><i class="fas fa-check-circle"></i> Registra el movimiento</span>
                                <span><i class="fas fa-check-circle"></i> Marca el pedido como recibido</span>
                            </div>
                            <button type="submit" class="btn purchase-invoice-submit warehouse-primary-action" <?php echo !$pedidosPendientes ? 'disabled' : ''; ?>>
                                <i class="fas fa-floppy-disk"></i>
                                <span>Registrar factura</span>
                            </button>
                            <a href="<?= e(\App\Core\Url::route('/dashboard/bodega')); ?>" class="purchase-invoice-cancel">
                                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                <span>Cancelar y volver</span>
                            </a>
                        </aside>
                    </div>
                </form>
            </div>

        <div class="admin-invoice-ledger admin-stat-card warehouse-admin-ledger">
            <div class="admin-invoice-ledger__heading">
                <h5><i class="fas fa-clock-rotate-left"></i> Facturas registradas recientemente</h5>
                <span class="admin-invoice-ledger__count"><?php echo count($facturasRecientes); ?> registros</span>
            </div>
                <div class="table-responsive purchase-history-wrap">
                    <table class="table table-hover align-middle admin-invoice-table purchase-history-table">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Proveedor</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($facturasRecientes): ?>
                                <?php foreach ($facturasRecientes as $factura): ?>
                                    <tr>
                                        <td><?php echo e($factura['numero_factura']); ?></td>
                                        <td><?php echo e($factura['proveedor_nombre']); ?></td>
                                        <td><?php echo e($factura['fecha']); ?></td>
                                        <td>$<?php echo number_format((float) $factura['total'], 2); ?></td>
                                        <td><span class="badge bg-<?php echo $factura['estado'] === 'Aprobada' ? 'success' : ($factura['estado'] === 'Registrada' ? 'warning' : 'danger'); ?>"><?php echo e($factura['estado']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="app-empty-state">No hay facturas registradas.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
        </div>
        </section>
    <?php endif; ?>
</div>
    </main>
</div>

<?php render_ada_chat(); ?>
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="js/admin-forms.js?v=<?php echo filemtime($projectRoot . '/js/admin-forms.js'); ?>"></script>
<?php if ($tablesReady): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.AdminFormMethods) {
        window.AdminFormMethods.enhanceAdminControls(document);
        window.AdminFormMethods.setupGenericAdminSelects(document);
    }

    const orderSelect = document.getElementById('purchaseOrderSelect');
    const provider = document.getElementById('purchaseOrderProvider');
    const product = document.getElementById('purchaseOrderProduct');
    const user = document.getElementById('purchaseOrderUser');
    const orderedQuantity = document.getElementById('purchaseOrderQuantity');
    const receivedQuantity = document.getElementById('purchaseReceivedQuantity');
    const unit = document.getElementById('purchaseOrderUnit');
    const unitPrice = document.getElementById('purchaseUnitPrice');
    const inventoryField = document.getElementById('purchaseInventoryItemField');
    const inventoryItem = document.getElementById('purchaseInventoryItem');
    const newItemFields = document.getElementById('purchaseNewInventoryItemFields');
    const newItemName = document.getElementById('newInventoryItemName');
    const newItemType = document.getElementById('newInventoryItemType');
    const newItemUnit = document.getElementById('newInventoryItemUnit');
    const totalMirror = document.querySelector('[data-purchase-total-mirror]');

    function updateTotal() {
        const total = (Number(receivedQuantity.value) || 0) * (Number(unitPrice.value) || 0);
        totalMirror.textContent = '$' + total.toFixed(2);
    }

    function updateOrder() {
        const option = orderSelect.options[orderSelect.selectedIndex];
        const hasOrder = option && option.value;
        provider.value = hasOrder ? option.dataset.provider || '' : '';
        product.value = hasOrder ? option.dataset.product || '' : '';
        user.value = hasOrder ? option.dataset.user || '' : '';
        unit.value = hasOrder ? option.dataset.unit || '' : '';
        orderedQuantity.value = hasOrder
            ? `${option.dataset.quantity || ''} ${option.dataset.unit || ''}`.trim()
            : '';
        receivedQuantity.value = hasOrder ? option.dataset.quantity || '' : '';
        const needsInventoryItem = Boolean(hasOrder && !Number(option.dataset.itemId || 0));
        inventoryField.hidden = !needsInventoryItem;
        inventoryItem.required = needsInventoryItem;
        if (!needsInventoryItem) {
            inventoryItem.value = '';
        }
        toggleNewInventoryItem();
        updateTotal();
    }

    function toggleNewInventoryItem() {
        const creatingNew = !inventoryField.hidden && inventoryItem.value === '-1';
        newItemFields.hidden = !creatingNew;
        newItemName.required = creatingNew;
        newItemType.required = creatingNew;
        newItemUnit.required = creatingNew;

        if (creatingNew) {
            newItemName.value = product.value;
            newItemUnit.value = unit.value;
        }
    }

    inventoryItem.addEventListener('change', function () {
        const selected = inventoryItem.options[inventoryItem.selectedIndex];
        toggleNewInventoryItem();
        if (selected && selected.value && selected.value !== '-1') {
            product.value = selected.dataset.product || product.value;
            unit.value = selected.dataset.unit || unit.value;
            orderedQuantity.value = `${orderSelect.options[orderSelect.selectedIndex].dataset.quantity || ''} ${unit.value}`.trim();
        }
    });

    newItemName.addEventListener('input', function () {
        if (inventoryItem.value === '-1') product.value = newItemName.value;
    });
    newItemUnit.addEventListener('input', function () {
        if (inventoryItem.value !== '-1') return;
        unit.value = newItemUnit.value;
        orderedQuantity.value = `${orderSelect.options[orderSelect.selectedIndex].dataset.quantity || ''} ${unit.value}`.trim();
    });

    orderSelect.addEventListener('change', updateOrder);
    receivedQuantity.addEventListener('input', updateTotal);
    unitPrice.addEventListener('input', updateTotal);
    updateOrder();
});
</script>
<?php endif; ?>
</body>
</html>
