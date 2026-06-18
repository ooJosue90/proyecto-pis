<?php
require_once __DIR__ . '/includes/auth.php';
require_auth('Bodeguero');

require_once __DIR__ . '/conexion.php';

// Traer solicitudes procesadas
$solicitudes_procesadas = $conn->query("
    SELECT ps.*, u.nombre AS agricultor_nombre
    FROM productos_solicitud ps
    JOIN usuarios u ON ps.id_agricultor = u.id_usuario
    WHERE ps.estado IN ('Entregado', 'Rechazado', 'Cancelado')
    ORDER BY ps.fecha DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Solicitudes Procesadas</title>
    <link rel="icon" type="image/x-icon" href="assets/mango.ico" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" rel="stylesheet">
    <link href="css/dashboard.css?v=<?php echo filemtime(__DIR__ . '/css/dashboard.css'); ?>" rel="stylesheet">
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
        <div class="warehouse-report-brand">
            <span class="report-brand-mark"><i class="fas fa-check"></i></span>
            <div>
                <strong>SEMBRIEXPORT</strong>
                <small>Gestión agrícola</small>
            </div>
        </div>
        <h1 class="report-title">Reporte de solicitudes procesadas</h1>
        <p class="report-subtitle">Generado el <?php echo date('d/m/Y H:i:s'); ?> por <?php echo htmlspecialchars($_SESSION['nombre']); ?></p>
    </header>

    <section class="report-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> ID</th>
                        <th><i class="fas fa-user"></i> Agricultor</th>
                        <th><i class="fas fa-seedling"></i> Producto</th>
                        <th><i class="fas fa-hashtag"></i> Cantidad solicitada</th>
                        <th><i class="fas fa-info-circle"></i> Estado</th>
                        <th><i class="fas fa-calendar-alt"></i> Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($solicitudes_procesadas && $solicitudes_procesadas->num_rows > 0): ?>
                        <?php while($sol = $solicitudes_procesadas->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sol['id_producto_solicitud']); ?></td>
                            <td><?php echo htmlspecialchars($sol['agricultor_nombre']); ?></td>
                            <td><?php echo htmlspecialchars($sol['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($sol['cantidad_solicitada']); ?></td>
                            <td>
                                <span class="badge <?php echo $sol['estado'] === 'Entregado' ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo htmlspecialchars($sol['estado']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($sol['fecha']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="app-empty-state">No hay solicitudes procesadas</td></tr>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<?php render_ada_chat(); ?>
</body>
</html>
<?php $conn->close(); ?>
