<?php declare(strict_types=1);
?>

<div class="admin-invoice-detail">
<section class="admin-invoice-detail__summary">
    <div>
        <span>Número</span>
        <strong><?php echo e($factura['numero_factura']); ?></strong>
    </div>
    <div>
        <span>Total</span>
        <strong>$<?php echo number_format((float) $factura['total'], 2); ?></strong>
    </div>
    <div>
        <span>Estado</span>
        <strong><span class="admin-invoice-status admin-invoice-status--<?php echo e($estadoClase); ?>"><i></i><?php echo e($factura['estado']); ?></span></strong>
    </div>
</section>

<section class="admin-invoice-detail__meta">
    <div class="admin-invoice-detail__meta-group">
        <span class="admin-invoice-detail__meta-icon"><i class="fas fa-file-invoice"></i></span>
        <div class="admin-invoice-detail__meta-list">
            <p><span>Pedido</span><strong><?php echo $factura['id_pedido'] ? '#' . (int) $factura['id_pedido'] : 'Sin pedido'; ?></strong></p>
            <p><span>Fecha</span><strong><?php echo date('d/m/Y', strtotime($factura['fecha'])); ?></strong></p>
            <p><span>Registrada por</span><strong><?php echo e($factura['bodeguero_nombre']); ?></strong></p>
            <p><span>Revisada por</span><strong><?php echo e($factura['revisor_nombre'] ?: 'Pendiente'); ?></strong></p>
        </div>
    </div>
    <div class="admin-invoice-detail__meta-group">
        <span class="admin-invoice-detail__meta-icon"><i class="fas fa-truck"></i></span>
        <div class="admin-invoice-detail__meta-list">
            <p><span>Proveedor</span><strong><?php echo e($factura['proveedor_nombre']); ?></strong></p>
            <p><span>RUC/Cédula</span><strong><?php echo e($factura['ruc_cedula']); ?></strong></p>
            <p><span>Teléfono</span><strong><?php echo e($factura['telefono'] ?: 'N/A'); ?></strong></p>
            <p><span>Email</span><strong><?php echo e($factura['proveedor_email'] ?: 'N/A'); ?></strong></p>
        </div>
    </div>
    <p class="admin-invoice-detail__note"><span>Observación</span><strong><?php echo e($factura['observaciones'] ?: 'Ninguna'); ?></strong></p>
</section>

<section class="admin-invoice-detail__items">
    <div class="admin-invoice-detail__items-heading">
        <div>
            <span class="admin-invoice-detail__items-icon"><i class="fas fa-boxes-stacked"></i></span>
            <div><small>Inventario</small><h3>Insumos recibidos</h3></div>
        </div>
        <span class="admin-invoice-detail__count"><?php echo count($detalles); ?> registros</span>
    </div>
<div class="table-responsive admin-invoice-detail__table-wrap">
    <table class="table align-middle admin-invoice-detail__table">
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
        <tfoot>
            <tr>
                <th colspan="5" class="text-end">TOTAL</th>
                <th>$<?php echo number_format((float) $factura['total'], 2); ?></th>
            </tr>
        </tfoot>
    </table>
</div>
</section>
</div>
