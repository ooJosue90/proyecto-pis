<?php
require_once 'conexion.php';
require_auth('Administrador');

// Obtener movimientos de insumos con información completa
$movimientos_insumos = $conn->query("
    SELECT mi.*, 
           ia.nombre as insumo_nombre,
           ia.tipo as insumo_tipo,
           u.nombre as usuario_nombre,
           ps.nombre as producto_solicitud_nombre
    FROM movimientos_insumos mi
    JOIN insumos_agricolas ia ON mi.id_insumo = ia.id_insumos
    JOIN usuarios u ON mi.id_usuario = u.id_usuario
    LEFT JOIN productos_solicitud ps ON mi.id_producto_solicitud = ps.id_producto_solicitud
    ORDER BY mi.fecha_movimiento DESC
");

// Obtener productos finales (cosechas) con información completa
$productos_finales = $conn->query("
    SELECT pf.*,
           u.nombre as usuario_nombre,
           l.ubicacion as lote_ubicacion,
           c.tipo as cultivo_tipo
    FROM productos_finales pf
    JOIN usuarios u ON pf.id_usuario = u.id_usuario
    JOIN lotes l ON pf.id_lote = l.id_lote
    LEFT JOIN cultivos c ON l.id_cultivo = c.id_cultivo
    ORDER BY pf.fecha DESC
");

// Estadísticas de movimientos
$stats_movimientos = $conn->query("
    SELECT 
        COUNT(*) as total_movimientos,
        SUM(CASE WHEN estado = 'Entrada' THEN 1 ELSE 0 END) as entradas,
        SUM(CASE WHEN estado = 'Salida' THEN 1 ELSE 0 END) as salidas
    FROM movimientos_insumos
")->fetch_assoc();

$total_productos_finales = $conn->query("SELECT COUNT(*) as count FROM productos_finales")->fetch_assoc()['count'];
?>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-exchange-alt"></i> Registro de Movimientos y Productos</h4>
            </div>
            <div class="card-body">
                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-arrows-alt fa-2x mb-2"></i>
                                <h3><?php echo $stats_movimientos['total_movimientos'] ?: 0; ?></h3>
                                <p class="mb-0">Total Movimientos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-arrow-down fa-2x mb-2"></i>
                                <h3><?php echo $stats_movimientos['entradas'] ?: 0; ?></h3>
                                <p class="mb-0">Entradas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-arrow-up fa-2x mb-2"></i>
                                <h3><?php echo $stats_movimientos['salidas'] ?: 0; ?></h3>
                                <p class="mb-0">Salidas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-apple-alt fa-2x mb-2"></i>
                                <h3><?php echo $total_productos_finales; ?></h3>
                                <p class="mb-0">Productos Cosechados</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs" id="movimientosTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="insumos-tab" data-bs-toggle="tab" data-bs-target="#movimientos-insumos" type="button">
                            <i class="fas fa-flask"></i> Movimientos de Insumos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="productos-tab" data-bs-toggle="tab" data-bs-target="#productos-finales" type="button">
                            <i class="fas fa-apple-alt"></i> Productos Finales/Cosecha
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="movimientosTabsContent">
                    <!-- Tab de Movimientos de Insumos -->
                    <div class="tab-pane fade show active" id="movimientos-insumos">
                        <div class="mt-3">
                            <?php if ($movimientos_insumos && $movimientos_insumos->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Fecha</th>
                                            <th>Insumo</th>
                                            <th>Tipo</th>
                                            <th>Usuario</th>
                                            <th>Movimiento</th>
                                            <th>Cant. Solicitada</th>
                                            <th>Cant. Entregada</th>
                                            <th>Observaciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($mov = $movimientos_insumos->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $mov['id_movimiento']; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($mov['fecha_movimiento'])); ?></td>
                                            <td><?php echo htmlspecialchars($mov['insumo_nombre']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($mov['insumo_tipo']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($mov['usuario_nombre']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $mov['estado'] == 'Entrada' ? 'success' : 'warning'; ?>">
                                                    <i class="fas fa-arrow-<?php echo $mov['estado'] == 'Entrada' ? 'down' : 'up'; ?>"></i>
                                                    <?php echo $mov['estado']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $mov['cantidad_solicitada']; ?></td>
                                            <td><?php echo $mov['cantidad_entregada']; ?></td>
                                            <td><?php echo htmlspecialchars($mov['observaciones'] ?: 'N/A'); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-exchange-alt fa-3x mb-3"></i>
                                <h5>No hay movimientos de insumos registrados</h5>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tab de Productos Finales -->
                    <div class="tab-pane fade" id="productos-finales">
                        <div class="mt-3">
                            <?php if ($productos_finales && $productos_finales->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Fecha Cosecha</th>
                                            <th>Producto</th>
                                            <th>Cantidad</th>
                                            <th>Unidad</th>
                                            <th>Agricultor</th>
                                            <th>Lote</th>
                                            <th>Tipo Cultivo</th>
                                            <th>Observaciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($prod = $productos_finales->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $prod['id_producto_final']; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($prod['fecha'])); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($prod['nombre_producto']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary fs-6">
                                                    <?php echo $prod['cantidad']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($prod['unidad_medida']); ?></td>
                                            <td><?php echo htmlspecialchars($prod['usuario_nombre']); ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($prod['lote_ubicacion']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($prod['cultivo_tipo'] ?: 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($prod['observaciones'] == 'NULL' ? 'N/A' : $prod['observaciones']); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Resumen de producción -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h5><i class="fas fa-chart-pie"></i> Resumen de Producción</h5>
                                    <?php
                                    // Reset para obtener estadísticas
                                    $productos_finales->data_seek(0);
                                    $resumen_produccion = [];
                                    $total_cantidad = 0;
                                    
                                    while ($prod = $productos_finales->fetch_assoc()) {
                                        $producto = $prod['nombre_producto'];
                                        if (!isset($resumen_produccion[$producto])) {
                                            $resumen_produccion[$producto] = 0;
                                        }
                                        $resumen_produccion[$producto] += $prod['cantidad'];
                                        $total_cantidad += $prod['cantidad'];
                                    }
                                    ?>
                                    
                                    <div class="row">
                                        <?php foreach ($resumen_produccion as $producto => $cantidad): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card border-left-primary">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                                <?php echo htmlspecialchars($producto); ?>
                                                            </div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                                <?php echo $cantidad; ?> unidades
                                                            </div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-apple-alt fa-2x text-gray-300"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-apple-alt fa-3x mb-3"></i>
                                <h5>No hay productos finales registrados</h5>
                                <p>Los productos cosechados aparecerán aquí cuando los agricultores registren sus cosechas.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
