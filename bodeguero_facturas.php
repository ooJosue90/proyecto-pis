<?php
require_once 'conexion.php';
require_auth('Bodeguero');

function purchase_tables_ready(mysqli $conn): bool
{
    foreach (['facturas_compra', 'factura_compra_detalle', 'movimientos_inventario'] as $table) {
        $escaped = $conn->real_escape_string($table);
        if ($conn->query("SHOW TABLES LIKE '{$escaped}'")->num_rows === 0) {
            return false;
        }
    }

    return true;
}

function valid_invoice_date(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

$tablesReady = purchase_tables_ready($conn);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!$tablesReady) {
        flash('error', 'Primero ejecute facturas_compra.sql en phpMyAdmin.');
        redirect('bodeguero_facturas.php');
    }

    $idProveedor = (int) ($_POST['id_proveedor'] ?? 0);
    $numeroFactura = trim((string) ($_POST['numero_factura'] ?? ''));
    $fecha = trim((string) ($_POST['fecha'] ?? ''));
    $observaciones = trim((string) ($_POST['observaciones'] ?? ''));
    $productos = is_array($_POST['productos'] ?? null) ? $_POST['productos'] : [];

    if ($idProveedor <= 0 || $numeroFactura === '' || !valid_invoice_date($fecha)) {
        flash('error', 'Complete correctamente proveedor, número de factura y fecha.');
        redirect('bodeguero_facturas.php');
    }

    if (empty($productos)) {
        flash('error', 'Agregue al menos un insumo a la factura.');
        redirect('bodeguero_facturas.php');
    }

    $conn->begin_transaction();

    try {
        $proveedor = db_fetch_one(
            $conn,
            'SELECT id_proveedor FROM proveedor WHERE id_proveedor = ?',
            'i',
            [$idProveedor]
        );

        if (!$proveedor) {
            throw new RuntimeException('El proveedor seleccionado no existe.');
        }

        $duplicada = db_value(
            $conn,
            'SELECT COUNT(*) FROM facturas_compra WHERE id_proveedor = ? AND numero_factura = ?',
            'is',
            [$idProveedor, $numeroFactura],
            0
        );

        if ((int) $duplicada > 0) {
            throw new RuntimeException('El número de factura ya está registrado para este proveedor.');
        }

        $lineas = [];
        $total = 0.0;
        $insumosIncluidos = [];

        foreach ($productos as $producto) {
            $idInsumo = (int) ($producto['id_insumo'] ?? 0);
            $cantidad = round((float) ($producto['cantidad'] ?? 0), 2);
            $precioUnitario = round((float) ($producto['precio_unitario'] ?? 0), 2);

            if ($idInsumo <= 0 || $cantidad <= 0 || $precioUnitario < 0) {
                throw new RuntimeException('Cada producto debe tener insumo, cantidad y precio válidos.');
            }

            if (isset($insumosIncluidos[$idInsumo])) {
                throw new RuntimeException('No repita el mismo insumo dentro de una factura.');
            }
            $insumosIncluidos[$idInsumo] = true;

            $insumo = db_fetch_one(
                $conn,
                'SELECT id_insumos, nombre, unidad_medida, cantidad
                 FROM insumos_agricolas
                 WHERE id_insumos = ?
                 FOR UPDATE',
                'i',
                [$idInsumo]
            );

            if (!$insumo) {
                throw new RuntimeException('Uno de los insumos seleccionados no existe.');
            }

            $subtotal = round($cantidad * $precioUnitario, 2);
            $total = round($total + $subtotal, 2);
            $lineas[] = [
                'insumo' => $insumo,
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotal,
            ];
        }

        if (!$lineas) {
            throw new RuntimeException('La factura no contiene productos válidos.');
        }

        db_execute(
            $conn,
            "INSERT INTO facturas_compra (
                id_proveedor, id_usuario, numero_factura, fecha, total, estado, observaciones
             ) VALUES (?, ?, ?, ?, ?, 'Registrada', ?)",
            'isssds',
            [$idProveedor, $_SESSION['id_usuario'], $numeroFactura, $fecha, $total, $observaciones]
        );
        $idFactura = (int) $conn->insert_id;

        foreach ($lineas as $linea) {
            $insumo = $linea['insumo'];
            $idInsumo = (int) $insumo['id_insumos'];
            $stockAnterior = (float) $insumo['cantidad'];
            $stockNuevo = round($stockAnterior + $linea['cantidad'], 2);

            db_execute(
                $conn,
                "INSERT INTO factura_compra_detalle (
                    id_factura_compra, id_insumo, nombre_insumo, unidad_medida,
                    cantidad, precio_unitario, subtotal
                 ) VALUES (?, ?, ?, ?, ?, ?, ?)",
                'iissddd',
                [
                    $idFactura,
                    $idInsumo,
                    $insumo['nombre'],
                    $insumo['unidad_medida'],
                    $linea['cantidad'],
                    $linea['precio_unitario'],
                    $linea['subtotal'],
                ]
            );
            $idDetalle = (int) $conn->insert_id;

            db_execute(
                $conn,
                'UPDATE insumos_agricolas SET cantidad = ? WHERE id_insumos = ?',
                'di',
                [$stockNuevo, $idInsumo]
            );

            $movimientoObservacion = "Entrada por factura {$numeroFactura}";
            db_execute(
                $conn,
                "INSERT INTO movimientos_inventario (
                    id_factura_compra, id_factura_compra_detalle, id_insumo, id_usuario,
                    tipo, cantidad, stock_anterior, stock_nuevo, observaciones
                 ) VALUES (?, ?, ?, ?, 'Entrada', ?, ?, ?, ?)",
                'iiisddds',
                [
                    $idFactura,
                    $idDetalle,
                    $idInsumo,
                    $_SESSION['id_usuario'],
                    $linea['cantidad'],
                    $stockAnterior,
                    $stockNuevo,
                    $movimientoObservacion,
                ]
            );

            // Mantiene visible la entrada en el reporte de movimientos existente.
            db_execute(
                $conn,
                "INSERT INTO movimientos_insumos (
                    id_insumo, id_usuario, tipo, estado, cantidad, observaciones, fecha_movimiento
                 ) VALUES (?, ?, 'Entrada', 'Entrada', ?, ?, NOW())",
                'isds',
                [$idInsumo, $_SESSION['id_usuario'], $linea['cantidad'], $movimientoObservacion]
            );
        }

        $conn->commit();
        flash('mensaje', "Factura {$numeroFactura} registrada. El inventario fue actualizado correctamente.");
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error registrando factura de compra: ' . $exception->getMessage());
        $message = (int) $exception->getCode() === 1062
            ? 'El número de factura ya está registrado para este proveedor.'
            : ($exception instanceof RuntimeException && !($exception instanceof mysqli_sql_exception)
                ? $exception->getMessage()
                : 'No se pudo registrar la factura de compra.');
        flash('error', $message);
    }

    redirect('bodeguero_facturas.php');
}

$proveedores = db_fetch_all($conn, 'SELECT id_proveedor, Nombre, ruc_cedula FROM proveedor ORDER BY Nombre');
$insumos = db_fetch_all(
    $conn,
    'SELECT id_insumos, nombre, unidad_medida, cantidad FROM insumos_agricolas ORDER BY nombre'
);
$facturasRecientes = $tablesReady
    ? db_fetch_all(
        $conn,
        "SELECT fc.*, p.Nombre AS proveedor_nombre
         FROM facturas_compra fc
         JOIN proveedor p ON fc.id_proveedor = p.id_proveedor
         WHERE fc.id_usuario = ?
         ORDER BY fc.fecha_registro DESC
         LIMIT 10",
        's',
        [$_SESSION['id_usuario']]
    )
    : [];
?>
<?php render_head('Facturas de Compra'); ?>
<body class="farmer-dashboard-page warehouse-dashboard-page purchase-invoice-page">
<?php render_app_nav('fas fa-file-invoice-dollar', 'Facturas - ' . current_user_name(), [
    ['href' => 'bodeguero.php', 'label' => 'Volver a bodega', 'icon' => 'fas fa-arrow-left', 'class' => 'btn btn-outline-light btn-sm'],
    ['href' => 'logout.php', 'label' => 'Salir', 'icon' => 'fas fa-sign-out-alt', 'class' => 'btn btn-outline-light btn-sm'],
]); ?>

<div class="container-fluid farmer-dashboard warehouse-dashboard purchase-invoice-dashboard mt-4">
    <?php render_flash_messages(); ?>

    <section class="farmer-page-heading warehouse-page-heading purchase-invoice-heading">
        <div>
            <span class="farmer-kicker">Recepción de compras</span>
            <h1>Registrar Factura de Compra</h1>
            <p>Registre el documento y los insumos recibidos en una sola operación.</p>
        </div>
        <span class="purchase-invoice-heading-icon"><i class="fas fa-file-invoice-dollar"></i></span>
    </section>

    <?php if (!$tablesReady): ?>
        <div class="alert alert-warning">
            <i class="fas fa-triangle-exclamation"></i>
            El módulo requiere ejecutar <strong>facturas_compra.sql</strong> en phpMyAdmin.
        </div>
    <?php else: ?>
        <div class="card mb-4 purchase-invoice-card">
            <div class="card-header purchase-invoice-card-header">
                <div>
                    <span class="purchase-invoice-step">Paso 1</span>
                    <h5><i class="fas fa-file-circle-plus"></i> Datos de la factura</h5>
                </div>
                <small>Los campos marcados con * son obligatorios</small>
            </div>
            <div class="card-body">
                <form method="POST" id="purchaseInvoiceForm">
                    <div class="row g-3 mb-4 purchase-invoice-main-fields">
                        <div class="col-md-5">
                            <label class="form-label">Proveedor *</label>
                            <select name="id_proveedor" class="form-select" required>
                                <option value="">Seleccione un proveedor</option>
                                <?php foreach ($proveedores as $proveedor): ?>
                                    <option value="<?php echo e($proveedor['id_proveedor']); ?>">
                                        <?php echo e($proveedor['Nombre']); ?> - <?php echo e($proveedor['ruc_cedula']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Número de factura *</label>
                            <input type="text" name="numero_factura" class="form-control" maxlength="60" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha *</label>
                            <input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="purchase-invoice-section-heading">
                        <div>
                            <span class="purchase-invoice-step">Paso 2</span>
                            <h5>Insumos recibidos</h5>
                        </div>
                        <button type="button" class="btn btn-outline-primary" id="addPurchaseItem">
                            <i class="fas fa-plus"></i> Agregar insumo
                        </button>
                    </div>

                    <div class="table-responsive purchase-items-wrap">
                        <table class="table align-middle purchase-items-table" id="purchaseItemsTable">
                            <thead>
                                <tr>
                                    <th>Insumo</th>
                                    <th>Cantidad</th>
                                    <th>Precio unitario</th>
                                    <th>Subtotal</th>
                                    <th><span class="visually-hidden">Eliminar</span></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot class="purchase-items-total">
                                <tr>
                                    <th colspan="3" class="text-end">Total</th>
                                    <th id="purchaseInvoiceTotal">$0.00</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="purchase-invoice-footer-grid">
                        <div>
                            <label class="form-label">Observación</label>
                            <textarea name="observaciones" class="form-control" rows="3" maxlength="1000" placeholder="Información adicional de la recepción"></textarea>
                        </div>
                        <div class="purchase-invoice-submit-panel">
                            <span>Total de la factura</span>
                            <strong data-purchase-total-mirror>$0.00</strong>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-floppy-disk"></i> Registrar factura e ingresar stock
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card purchase-invoice-history-card">
            <div class="card-header purchase-invoice-card-header">
                <div>
                    <span class="purchase-invoice-step">Historial</span>
                    <h5><i class="fas fa-clock-rotate-left"></i> Facturas registradas recientemente</h5>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive purchase-history-wrap">
                    <table class="table table-hover align-middle purchase-history-table">
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
        </div>
    <?php endif; ?>
</div>

<?php if ($tablesReady): ?>
<template id="purchaseItemTemplate">
    <tr>
        <td>
            <select class="form-select purchase-item-select" required>
                <option value="">Seleccione un insumo</option>
                <?php foreach ($insumos as $insumo): ?>
                    <option
                        value="<?php echo e($insumo['id_insumos']); ?>"
                        data-unit="<?php echo e($insumo['unidad_medida']); ?>">
                        <?php echo e($insumo['nombre']); ?> (stock: <?php echo e($insumo['cantidad']); ?> <?php echo e($insumo['unidad_medida']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="number" class="form-control purchase-item-quantity" min="0.01" step="0.01" placeholder="0.00" required></td>
        <td><div class="input-group"><span class="input-group-text">$</span><input type="number" class="form-control purchase-item-price" min="0" step="0.01" placeholder="0.00" required></div></td>
        <td><strong class="purchase-item-subtotal">$0.00</strong></td>
        <td><button type="button" class="btn btn-outline-danger btn-sm purchase-item-remove"><i class="fas fa-trash"></i></button></td>
    </tr>
</template>
<?php endif; ?>

<?php render_ada_chat(); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($tablesReady): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const body = document.querySelector('#purchaseItemsTable tbody');
    const template = document.getElementById('purchaseItemTemplate');
    const addButton = document.getElementById('addPurchaseItem');
    const totalElement = document.getElementById('purchaseInvoiceTotal');
    const totalMirror = document.querySelector('[data-purchase-total-mirror]');
    let itemIndex = 0;

    function updateTotal() {
        let total = 0;
        body.querySelectorAll('tr').forEach(row => {
            const quantity = Number(row.querySelector('.purchase-item-quantity').value) || 0;
            const price = Number(row.querySelector('.purchase-item-price').value) || 0;
            const subtotal = quantity * price;
            total += subtotal;
            row.querySelector('.purchase-item-subtotal').textContent = '$' + subtotal.toFixed(2);
        });
        totalElement.textContent = '$' + total.toFixed(2);
        totalMirror.textContent = '$' + total.toFixed(2);
    }

    function addItem() {
        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('tr');
        row.querySelector('.purchase-item-select').name = `productos[${itemIndex}][id_insumo]`;
        row.querySelector('.purchase-item-quantity').name = `productos[${itemIndex}][cantidad]`;
        row.querySelector('.purchase-item-price').name = `productos[${itemIndex}][precio_unitario]`;
        itemIndex++;

        row.querySelectorAll('input').forEach(input => input.addEventListener('input', updateTotal));
        row.querySelector('.purchase-item-remove').addEventListener('click', function () {
            row.remove();
            updateTotal();
        });
        body.appendChild(row);
    }

    addButton.addEventListener('click', addItem);
    addItem();
});
</script>
<?php endif; ?>
</body>
</html>
