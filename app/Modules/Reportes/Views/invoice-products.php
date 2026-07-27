<?php declare(strict_types=1);$projectRoot=dirname(__DIR__,4);require_once $projectRoot.'/app/Shared/Views/layout.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Productos en Factura</title>
    <link rel="icon" type="image/x-icon" href="assets/mango.ico" />
</head>
<body class="print-page warehouse-report-page">
<main class="print-report">
    <header class="report-header">
        <div class="report-actions no-print">
            <a href="<?= e(\App\Core\Url::route('/dashboard/bodega')); ?>" class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left"></i> Regresar
            </a>
        </div>
        <span class="report-brand-mark"><i class="fas fa-box"></i></span>
        <p class="report-kicker">SEMBRIEXPORT</p>
        <h1 class="report-title">Reporte de productos en factura</h1>
        <p class="report-subtitle">Generado el <?php echo date('d/m/Y H:i:s'); ?> por <?php echo isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Usuario'; ?></p>
    </header>

    <section class="report-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th><i class="fas fa-tag"></i> Nombre</th>
                        <th><i class="fas fa-cogs"></i> Tipo</th>
                        <th><i class="fas fa-info-circle"></i> Descripción</th>
                        <th><i class="fas fa-balance-scale"></i> Unidad</th>
                        <th><i class="fas fa-hashtag"></i> Cantidad</th>
                        <th><i class="fas fa-dollar-sign"></i> Precio</th>
                        <th><i class="fas fa-calendar-plus"></i> Fecha Ingreso</th>
                        <th><i class="fas fa-calendar-times"></i> Fecha Vencimiento</th>
                        <th><i class="fas fa-check-circle"></i> Estado</th>
                        <th><i class="fas fa-calendar-alt"></i> Fecha Factura</th>
                        <th><i class="fas fa-user"></i> Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($productos_factura !== []): ?>
                        <?php foreach($productos_factura as $pf): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pf['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($pf['tipo']); ?></td>
                            <td><?php echo htmlspecialchars($pf['descripcion']); ?></td>
                            <td><?php echo htmlspecialchars($pf['unidad_medida']); ?></td>
                            <td><?php echo (int)$pf['cantidad']; ?></td>
                            <td>$<?php echo number_format($pf['precio'], 2); ?></td>
                            <td><?php echo htmlspecialchars($pf['fecha_ingreso']); ?></td>
                            <td><?php echo htmlspecialchars($pf['fecha_vencimiento']); ?></td>
                            <td><?php echo $pf['procesado'] ? '<span class="badge bg-success">Procesado</span>' : '<span class="badge bg-warning">Pendiente</span>'; ?></td>
                            <td><?php echo htmlspecialchars($pf['fecha_factura']); ?></td>
                            <td><?php echo htmlspecialchars($pf['usuario_nombre']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="11" class="app-empty-state">No hay productos registrados</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <footer class="report-footer">
        <div>
            <p><strong>SEMBRIEXPORT</strong> - Sistema de Gestión Agrícola</p>
            <p>Reporte generado automáticamente</p>
        </div>
        <button class="btn no-print warehouse-primary-action warehouse-primary-action--compact" onclick="window.print()">
            <i class="fas fa-print"></i>
            Imprimir
        </button>
    </footer>
</main>

<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<?php render_ada_chat(); ?>
</body>
</html>
