<?php
require_once __DIR__ . '/includes/auth.php';
require_auth('Bodeguero');

require_once __DIR__ . '/conexion.php';

// Traer productos en factura
$productos_factura = $conn->query("
    SELECT pf.nombre, pf.tipo, pf.descripcion, pf.unidad_medida, pf.cantidad, pf.precio, pf.fecha_ingreso, pf.fecha_vencimiento, pf.procesado, f.fecha AS fecha_factura, u.nombre AS usuario_nombre
    FROM productos_factura pf
    INNER JOIN factura f ON pf.id_factura = f.id_factura
    INNER JOIN usuarios u ON f.id_usuario = u.id_usuario
    ORDER BY f.fecha DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Productos en Factura</title>
    <link rel="icon" type="image/x-icon" href="assets/mango.ico" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/dashboard.css?v=20260605-select-font" rel="stylesheet">
    <link href="asistente/asistente_virtual.css" rel="stylesheet">
</head>
<body class="print-page warehouse-report-page">
<main class="print-report">
    <header class="report-header">
        <div class="report-actions no-print">
            <a href="bodeguero.php" class="btn btn-outline-light btn-sm">
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
                    <?php if($productos_factura && $productos_factura->num_rows > 0): ?>
                        <?php while($pf = $productos_factura->fetch_assoc()): ?>
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
                        <?php endwhile; ?>
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
        <button class="btn btn-primary no-print" onclick="window.print()">
            <i class="fas fa-print"></i>
            Imprimir
        </button>
    </footer>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<?php render_ada_chat(); ?>
</body>
</html>
<?php $conn->close(); ?>
