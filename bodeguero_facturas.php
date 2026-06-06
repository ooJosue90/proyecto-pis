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

function purchase_flow_ready(mysqli $conn): bool
{
    $requiredColumns = [
        ['pedidos', 'id_insumo'],
        ['pedidos', 'estado'],
        ['pedidos', 'observaciones'],
        ['facturas_compra', 'id_pedido'],
    ];

    foreach ($requiredColumns as [$table, $column]) {
        $tableEscaped = $conn->real_escape_string($table);
        $columnEscaped = $conn->real_escape_string($column);
        if ($conn->query("SHOW COLUMNS FROM `{$tableEscaped}` LIKE '{$columnEscaped}'")->num_rows === 0) {
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

$tiposInsumoPermitidos = [
    'Fertilizantes',
    'Insecticidas',
    'Fungicidas',
    'Herbicidas',
    'Bioinsumos',
    'Correctores de suelo',
    'Herramientas agrícolas',
    'Sistemas de riego',
    'Materiales y suministros',
];

$tablesReady = purchase_tables_ready($conn) && purchase_flow_ready($conn);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!$tablesReady) {
        flash('error', 'Primero ejecute actualizar_flujo_pedidos_facturas.sql en phpMyAdmin.');
        redirect('bodeguero_facturas.php');
    }

    $idPedido = (int) ($_POST['id_pedido'] ?? 0);
    $numeroFactura = trim((string) ($_POST['numero_factura'] ?? ''));
    $fecha = trim((string) ($_POST['fecha'] ?? ''));
    $observaciones = trim((string) ($_POST['observaciones'] ?? ''));
    $cantidadRecibida = round((float) ($_POST['cantidad_recibida'] ?? 0), 2);
    $precioUnitario = round((float) ($_POST['precio_unitario'] ?? 0), 2);
    $idInsumoFormulario = (int) ($_POST['id_insumo'] ?? 0);
    $nuevoInsumoNombre = trim((string) ($_POST['nuevo_insumo_nombre'] ?? ''));
    $nuevoInsumoTipo = trim((string) ($_POST['nuevo_insumo_tipo'] ?? ''));
    $nuevoInsumoDescripcion = trim((string) ($_POST['nuevo_insumo_descripcion'] ?? ''));
    $nuevoInsumoUnidad = trim((string) ($_POST['nuevo_insumo_unidad'] ?? ''));
    $nuevoInsumoObservaciones = trim((string) ($_POST['nuevo_insumo_observaciones'] ?? ''));

    if ($idPedido <= 0 || $numeroFactura === '' || !valid_invoice_date($fecha)
        || $cantidadRecibida <= 0 || $precioUnitario < 0) {
        flash('error', 'Complete correctamente pedido, factura, fecha, cantidad y precio.');
        redirect('bodeguero_facturas.php' . ($idPedido > 0 ? '?pedido_id=' . $idPedido : ''));
    }

    $conn->begin_transaction();

    try {
        $pedido = db_fetch_one(
            $conn,
            "SELECT p.id_pedidos, p.id_proveedor, p.id_insumo, p.nombre_producto,
                    p.cantidad, p.unidad_medida, p.estado
             FROM pedidos p
             WHERE p.id_pedidos = ?
             FOR UPDATE",
            'i',
            [$idPedido]
        );

        if (!$pedido) {
            throw new RuntimeException('El pedido seleccionado no existe.');
        }
        if ($pedido['estado'] === 'Cancelado') {
            throw new RuntimeException('No se puede registrar un comprobante para un pedido cancelado.');
        }
        if ($pedido['estado'] !== 'Pendiente') {
            throw new RuntimeException('El pedido ya fue recibido o no está disponible.');
        }
        $idInsumoPedido = (int) ($pedido['id_insumo'] ?: $idInsumoFormulario);
        if (empty($pedido['id_insumo']) && $idInsumoFormulario === 0) {
            throw new RuntimeException('Seleccione el producto de inventario relacionado con el pedido.');
        }

        $duplicada = db_value(
            $conn,
            'SELECT COUNT(*) FROM facturas_compra WHERE id_pedido = ?',
            'i',
            [$idPedido],
            0
        );

        if ((int) $duplicada > 0) {
            throw new RuntimeException('Este pedido ya tiene un comprobante registrado.');
        }

        $numeroDuplicado = (int) db_value(
            $conn,
            'SELECT COUNT(*) FROM facturas_compra WHERE id_proveedor = ? AND numero_factura = ?',
            'is',
            [(int) $pedido['id_proveedor'], $numeroFactura],
            0
        );
        if ($numeroDuplicado > 0) {
            throw new RuntimeException('El número de factura ya está registrado para este proveedor.');
        }

        if (empty($pedido['id_insumo']) && $idInsumoFormulario === -1) {
            if ($nuevoInsumoNombre === '' || $nuevoInsumoTipo === '' || $nuevoInsumoUnidad === '') {
                throw new RuntimeException('Complete nombre, tipo y unidad del nuevo producto.');
            }

            if (!in_array($nuevoInsumoTipo, $tiposInsumoPermitidos, true)) {
                throw new RuntimeException('Seleccione una clasificación válida para el nuevo producto.');
            }

            if (mb_strlen($nuevoInsumoNombre) > 200
                || mb_strlen($nuevoInsumoTipo) > 100
                || mb_strlen($nuevoInsumoUnidad) > 50) {
                throw new RuntimeException('Los datos del nuevo producto superan la longitud permitida.');
            }

            $insumoExistente = (int) db_value(
                $conn,
                'SELECT COUNT(*) FROM insumos_agricolas WHERE LOWER(nombre) = LOWER(?)',
                's',
                [$nuevoInsumoNombre],
                0
            );
            if ($insumoExistente > 0) {
                throw new RuntimeException('Ya existe un producto con ese nombre. Selecciónelo en la lista.');
            }

            db_execute(
                $conn,
                "INSERT INTO insumos_agricolas (
                    id_usuario, nombre, tipo, descripcion, unidad_medida,
                    cantidad, observaciones
                 ) VALUES (?, ?, ?, ?, ?, 0, ?)",
                'ssssss',
                [
                    $_SESSION['id_usuario'],
                    $nuevoInsumoNombre,
                    $nuevoInsumoTipo,
                    $nuevoInsumoDescripcion,
                    $nuevoInsumoUnidad,
                    $nuevoInsumoObservaciones,
                ]
            );
            $idInsumoPedido = (int) $conn->insert_id;
        }

        if ($idInsumoPedido <= 0) {
            throw new RuntimeException('Seleccione un producto de inventario válido.');
        }

        $insumo = db_fetch_one(
            $conn,
            'SELECT id_insumos, nombre, unidad_medida, cantidad
             FROM insumos_agricolas
             WHERE id_insumos = ?
             FOR UPDATE',
            'i',
            [$idInsumoPedido]
        );
        if (!$insumo) {
            throw new RuntimeException('El producto relacionado ya no existe en el inventario.');
        }

        if (empty($pedido['id_insumo'])) {
            db_execute(
                $conn,
                'UPDATE pedidos
                 SET id_insumo = ?, nombre_producto = ?, unidad_medida = ?
                 WHERE id_pedidos = ? AND estado = \'Pendiente\'',
                'issi',
                [$idInsumoPedido, $insumo['nombre'], $insumo['unidad_medida'], $idPedido]
            );
            $pedido['nombre_producto'] = $insumo['nombre'];
            $pedido['unidad_medida'] = $insumo['unidad_medida'];
        }

        $total = round($cantidadRecibida * $precioUnitario, 2);
        db_execute(
            $conn,
            "INSERT INTO facturas_compra (
                id_pedido, id_proveedor, id_usuario, numero_factura, fecha,
                total, estado, observaciones
             ) VALUES (?, ?, ?, ?, ?, ?, 'Registrada', ?)",
            'iisssds',
            [
                $idPedido,
                (int) $pedido['id_proveedor'],
                $_SESSION['id_usuario'],
                $numeroFactura,
                $fecha,
                $total,
                $observaciones,
            ]
        );
        $idFactura = (int) $conn->insert_id;

        $idInsumo = (int) $insumo['id_insumos'];
        $stockAnterior = (float) $insumo['cantidad'];
        $stockNuevo = round($stockAnterior + $cantidadRecibida, 2);

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
                $pedido['unidad_medida'],
                $cantidadRecibida,
                $precioUnitario,
                $total,
            ]
        );
        $idDetalle = (int) $conn->insert_id;

        db_execute(
            $conn,
            'UPDATE insumos_agricolas SET cantidad = ? WHERE id_insumos = ?',
            'di',
            [$stockNuevo, $idInsumo]
        );

        $movimientoObservacion = "Entrada por factura {$numeroFactura}, pedido #{$idPedido}";
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
                $cantidadRecibida,
                $stockAnterior,
                $stockNuevo,
                $movimientoObservacion,
            ]
        );

        db_execute(
            $conn,
            "INSERT INTO movimientos_insumos (
                id_insumo, id_usuario, tipo, estado, cantidad, observaciones, fecha_movimiento
             ) VALUES (?, ?, 'Entrada', 'Entrada', ?, ?, NOW())",
            'isds',
            [$idInsumo, $_SESSION['id_usuario'], $cantidadRecibida, $movimientoObservacion]
        );

        $pedidoActualizado = db_execute(
            $conn,
            "UPDATE pedidos SET estado = 'Recibido'
             WHERE id_pedidos = ? AND estado = 'Pendiente'",
            'i',
            [$idPedido]
        );
        if ($pedidoActualizado !== 1) {
            throw new RuntimeException('El pedido cambió de estado durante la recepción.');
        }

        $conn->commit();
        flash('mensaje', "Factura {$numeroFactura} registrada. El inventario fue actualizado correctamente.");
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('Error registrando factura de compra: ' . $exception->getMessage());
        $message = (int) $exception->getCode() === 1062
            ? 'El pedido o el número de factura ya tiene un comprobante registrado.'
            : ($exception instanceof RuntimeException && !($exception instanceof mysqli_sql_exception)
                ? $exception->getMessage()
                : 'No se pudo registrar la factura de compra.');
        flash('error', $message);
    }

    redirect('bodeguero_facturas.php');
}

$pedidoSeleccionadoId = (int) ($_GET['pedido_id'] ?? 0);
$pedidosPendientes = $tablesReady
    ? db_fetch_all(
        $conn,
        "SELECT p.id_pedidos, p.id_proveedor, p.id_insumo, p.nombre_producto,
                p.cantidad, p.unidad_medida, p.observaciones, p.fecha,
                pr.Nombre AS proveedor_nombre, pr.ruc_cedula,
                u.nombre AS usuario_responsable
         FROM pedidos p
         JOIN proveedor pr ON p.id_proveedor = pr.id_proveedor
         JOIN usuarios u ON p.id_usuario = u.id_usuario
         LEFT JOIN facturas_compra fc ON fc.id_pedido = p.id_pedidos
         WHERE p.estado = 'Pendiente' AND fc.id_factura_compra IS NULL
         ORDER BY p.fecha ASC, p.id_pedidos ASC"
    )
    : [];
$pedidoSeleccionado = null;
foreach ($pedidosPendientes as $pedidoPendiente) {
    if ((int) $pedidoPendiente['id_pedidos'] === $pedidoSeleccionadoId) {
        $pedidoSeleccionado = $pedidoPendiente;
        break;
    }
}
$insumosDisponibles = $tablesReady
    ? db_fetch_all(
        $conn,
        'SELECT id_insumos, nombre, unidad_medida, cantidad
         FROM insumos_agricolas
         ORDER BY nombre'
    )
    : [];
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
            El módulo requiere ejecutar <strong>actualizar_flujo_pedidos_facturas.sql</strong> en phpMyAdmin.
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
                            <select name="id_insumo" id="purchaseInventoryItem" class="form-select">
                                <option value="">Seleccione un producto</option>
                                <option value="-1">+ Crear un nuevo producto de inventario</option>
                                <?php foreach ($insumosDisponibles as $insumo): ?>
                                    <option
                                        value="<?php echo (int) $insumo['id_insumos']; ?>"
                                        data-product="<?php echo e($insumo['nombre']); ?>"
                                        data-unit="<?php echo e($insumo['unidad_medida']); ?>">
                                        <?php echo e($insumo['nombre']); ?>
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
                                <select name="nuevo_insumo_tipo" id="newInventoryItemType" class="form-select">
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
                            <button type="submit" class="btn btn-success purchase-invoice-submit" <?php echo !$pedidosPendientes ? 'disabled' : ''; ?>>
                                <i class="fas fa-floppy-disk"></i>
                                <span>Registrar factura</span>
                            </button>
                            <a href="bodeguero.php" class="purchase-invoice-cancel">Cancelar y volver</a>
                        </aside>
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

<?php render_ada_chat(); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($tablesReady): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
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
