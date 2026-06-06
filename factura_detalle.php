<?php
require_once 'conexion.php';
require_auth('Administrador');

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    echo '<div class="alert alert-danger">Factura inválida.</div>';
    exit;
}

$factura = db_fetch_one(
    $conn,
    "SELECT fc.*, p.Nombre AS proveedor_nombre, p.ruc_cedula, p.telefono,
            p.email AS proveedor_email, u.nombre AS bodeguero_nombre,
            ur.nombre AS revisor_nombre, pe.nombre_producto AS pedido_producto,
            pe.cantidad AS pedido_cantidad, pe.unidad_medida AS pedido_unidad
     FROM facturas_compra fc
     JOIN proveedor p ON fc.id_proveedor = p.id_proveedor
     JOIN usuarios u ON fc.id_usuario = u.id_usuario
     LEFT JOIN usuarios ur ON fc.id_usuario_revision = ur.id_usuario
     LEFT JOIN pedidos pe ON fc.id_pedido = pe.id_pedidos
     WHERE fc.id_factura_compra = ?",
    'i',
    [$id]
);

if (!$factura) {
    echo '<div class="alert alert-danger">Factura no encontrada.</div>';
    exit;
}

$detalles = db_fetch_all(
    $conn,
    "SELECT d.*, ia.tipo
     FROM factura_compra_detalle d
     JOIN insumos_agricolas ia ON d.id_insumo = ia.id_insumos
     WHERE d.id_factura_compra = ?
     ORDER BY d.id_factura_compra_detalle",
    'i',
    [$id]
);

$badge = $factura['estado'] === 'Aprobada'
    ? 'success'
    : ($factura['estado'] === 'Registrada'
        ? 'warning'
        : ($factura['estado'] === 'Anulada' ? 'secondary' : 'danger'));
?>

<div class="row g-4">
    <div class="col-md-6">
        <h6><i class="fas fa-file-invoice"></i> Factura</h6>
        <table class="table table-sm">
            <tr><th>Número:</th><td><?php echo e($factura['numero_factura']); ?></td></tr>
            <tr><th>Pedido:</th><td><?php echo $factura['id_pedido'] ? '#' . (int) $factura['id_pedido'] : 'Sin pedido'; ?></td></tr>
            <tr><th>Fecha:</th><td><?php echo date('d/m/Y', strtotime($factura['fecha'])); ?></td></tr>
            <tr><th>Registrada por:</th><td><?php echo e($factura['bodeguero_nombre']); ?></td></tr>
            <tr><th>Total:</th><td><strong>$<?php echo number_format((float) $factura['total'], 2); ?></strong></td></tr>
            <tr><th>Estado:</th><td><span class="badge bg-<?php echo $badge; ?>"><?php echo e($factura['estado']); ?></span></td></tr>
            <tr><th>Revisada por:</th><td><?php echo e($factura['revisor_nombre'] ?: 'Pendiente'); ?></td></tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6><i class="fas fa-truck"></i> Proveedor</h6>
        <table class="table table-sm">
            <tr><th>Nombre:</th><td><?php echo e($factura['proveedor_nombre']); ?></td></tr>
            <tr><th>RUC/Cédula:</th><td><?php echo e($factura['ruc_cedula']); ?></td></tr>
            <tr><th>Teléfono:</th><td><?php echo e($factura['telefono'] ?: 'N/A'); ?></td></tr>
            <tr><th>Email:</th><td><?php echo e($factura['proveedor_email'] ?: 'N/A'); ?></td></tr>
            <tr><th>Observación:</th><td><?php echo e($factura['observaciones'] ?: 'Ninguna'); ?></td></tr>
        </table>
    </div>
</div>

<hr>

<h6><i class="fas fa-boxes-stacked"></i> Insumos recibidos</h6>
<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Insumo</th>
                <th>Tipo</th>
                <th>Cantidad</th>
                <th>Unidad</th>
                <th>Precio unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles as $detalle): ?>
                <tr>
                    <td><?php echo e($detalle['nombre_insumo']); ?></td>
                    <td><?php echo e($detalle['tipo'] ?: 'N/A'); ?></td>
                    <td><?php echo e($detalle['cantidad']); ?></td>
                    <td><?php echo e($detalle['unidad_medida']); ?></td>
                    <td>$<?php echo number_format((float) $detalle['precio_unitario'], 2); ?></td>
                    <td><strong>$<?php echo number_format((float) $detalle['subtotal'], 2); ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot class="table-dark">
            <tr>
                <th colspan="5" class="text-end">TOTAL</th>
                <th>$<?php echo number_format((float) $factura['total'], 2); ?></th>
            </tr>
        </tfoot>
    </table>
</div>
