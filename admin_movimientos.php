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

// Obtener cosechas del módulo vigente
$cosechas = $conn->query("
    SELECT co.*,
           co.cantidad_total_kg as cantidad_cosechada,
           co.fecha_cosecha as fecha_cosecha,
           co.observaciones as observacion,
           u.nombre as usuario_nombre,
           l.ubicacion as lote_ubicacion,
           l.estado_cultivo,
           l.fecha_fin_cosecha_real,
           c.tipo as cultivo_tipo
    FROM cosechas co
    JOIN usuarios u ON co.id_usuario = u.id_usuario
    JOIN lotes l ON co.id_lote = l.id_lote
    LEFT JOIN cultivos c ON l.id_cultivo = c.id_cultivo
    ORDER BY co.fecha_cosecha DESC, co.id_cosecha DESC
");

// Estadísticas de movimientos
$stats_movimientos = $conn->query("
    SELECT 
        COUNT(*) as total_movimientos,
        SUM(CASE WHEN estado = 'Entrada' THEN 1 ELSE 0 END) as entradas,
        SUM(CASE WHEN estado = 'Salida' THEN 1 ELSE 0 END) as salidas
    FROM movimientos_insumos
")->fetch_assoc();

$total_cosechas = $conn->query("SELECT COUNT(*) as count FROM cosechas")->fetch_assoc()['count'];
?>

<section class="admin-movements">
    <header class="admin-movements__header">
        <div class="admin-movements__title">
            <span class="admin-movements__header-icon"><i class="fas fa-arrow-right-arrow-left"></i></span>
            <div>
                <span class="admin-section-eyebrow">Inventario y producción</span>
                <h4>Registro de movimientos</h4>
                <p>Consulta entradas, salidas de insumos y cosechas registradas en operación.</p>
            </div>
        </div>
    </header>

    <div class="row admin-movements__metrics">
        <div class="col-md-3 col-sm-6">
            <article class="admin-movements__metric">
                <span class="admin-movements__metric-icon admin-movements__metric-icon--total"><i class="fas fa-arrows-up-down-left-right"></i></span>
                <div>
                    <span>Total movimientos</span>
                    <strong><?php echo $stats_movimientos['total_movimientos'] ?: 0; ?></strong>
                </div>
            </article>
        </div>
        <div class="col-md-3 col-sm-6">
            <article class="admin-movements__metric">
                <span class="admin-movements__metric-icon admin-movements__metric-icon--in"><i class="fas fa-arrow-down"></i></span>
                <div>
                    <span>Entradas</span>
                    <strong><?php echo $stats_movimientos['entradas'] ?: 0; ?></strong>
                </div>
            </article>
        </div>
        <div class="col-md-3 col-sm-6">
            <article class="admin-movements__metric">
                <span class="admin-movements__metric-icon admin-movements__metric-icon--out"><i class="fas fa-arrow-up"></i></span>
                <div>
                    <span>Salidas</span>
                    <strong><?php echo $stats_movimientos['salidas'] ?: 0; ?></strong>
                </div>
            </article>
        </div>
        <div class="col-md-3 col-sm-6">
            <article class="admin-movements__metric">
                <span class="admin-movements__metric-icon admin-movements__metric-icon--harvest"><i class="fas fa-wheat-awn"></i></span>
                <div>
                    <span>Cosechas</span>
                    <strong><?php echo $total_cosechas; ?></strong>
                </div>
            </article>
        </div>
    </div>

    <section class="admin-movements__panel" aria-label="Registro de movimientos y cosechas">
        <div class="admin-movements__panel-heading">
            <span class="admin-movements__panel-icon"><i class="fas fa-list-check"></i></span>
            <div>
                <h5>Movimientos registrados</h5>
                <p>Filtra y revisa los movimientos operativos del inventario.</p>
            </div>
        </div>

        <ul class="nav nav-tabs admin-movements__tabs" id="movimientosTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="insumos-tab" data-bs-toggle="tab" data-bs-target="#movimientos-insumos" type="button">
                    <i class="fas fa-flask-vial"></i> Movimientos de Insumos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="productos-tab" data-bs-toggle="tab" data-bs-target="#productos-finales" type="button">
                    <i class="fas fa-wheat-awn"></i> Cosechas
                </button>
            </li>
        </ul>

        <div class="tab-content admin-movements__content" id="movimientosTabsContent">
            <div class="tab-pane fade show active" id="movimientos-insumos">
                <div class="mt-3">
                    <?php if ($movimientos_insumos && $movimientos_insumos->num_rows > 0): ?>
                    <div class="table-responsive admin-movements__table-wrap">
                        <table class="table admin-movements__table" data-app-table-owner="admin-movements-inputs-table">
                            <thead>
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
                                    <td><span class="admin-movements__id">#<?php echo $mov['id_movimiento']; ?></span></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($mov['fecha_movimiento'])); ?></td>
                                    <td><?php echo htmlspecialchars($mov['insumo_nombre']); ?></td>
                                    <td><span class="admin-movements__tag"><?php echo htmlspecialchars($mov['insumo_tipo']); ?></span></td>
                                    <td><?php echo htmlspecialchars($mov['usuario_nombre']); ?></td>
                                    <td>
                                        <span class="admin-movements__status <?php echo $mov['estado'] == 'Entrada' ? 'admin-movements__status--in' : 'admin-movements__status--out'; ?>">
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
                    <div class="admin-movements__empty">
                        <span><i class="fas fa-right-left"></i></span>
                        <h5>No hay movimientos de insumos registrados</h5>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="productos-finales">
                <div class="mt-3">
                    <?php if ($cosechas && $cosechas->num_rows > 0): ?>
                    <div class="table-responsive admin-movements__table-wrap">
                        <table class="table admin-movements__table" data-app-table-owner="admin-movements-harvests-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha Cosecha</th>
                                    <th>Cultivo</th>
                                    <th>Cantidad</th>
                                    <th>Primera</th>
                                    <th>Segunda</th>
                                    <th>Descarte</th>
                                    <th>Agricultor</th>
                                    <th>Lote</th>
                                    <th>Estado</th>
                                    <th>Observación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($prod = $cosechas->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="admin-movements__id">#<?php echo $prod['id_cosecha']; ?></span></td>
                                    <td><?php echo date('d/m/Y', strtotime($prod['fecha_cosecha'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($prod['cultivo_tipo'] ?: 'Mango Tommy Atkins'); ?></strong></td>
                                    <td><span class="admin-movements__quantity"><?php echo $prod['cantidad_cosechada']; ?> kg</span></td>
                                    <td><?php echo htmlspecialchars($prod['calidad_primera_kg']); ?> kg</td>
                                    <td><?php echo htmlspecialchars($prod['calidad_segunda_kg']); ?> kg</td>
                                    <td><?php echo htmlspecialchars($prod['descarte_kg']); ?> kg</td>
                                    <td><?php echo htmlspecialchars($prod['usuario_nombre']); ?></td>
                                    <td><span class="admin-movements__tag"><?php echo htmlspecialchars($prod['lote_ubicacion']); ?></span></td>
                                    <td>
                                        <?php
                                            $harvestStatusClass = $prod['estado'] === 'Validada'
                                                ? 'admin-movements__status--in'
                                                : ($prod['estado'] === 'Recibida'
                                                    ? 'admin-movements__status--received'
                                                    : ($prod['estado'] === 'Rechazada' ? 'admin-movements__status--rejected' : 'admin-movements__status--out'));
                                        ?>
                                        <span class="admin-movements__status <?php echo $harvestStatusClass; ?>">
                                            <?php echo htmlspecialchars($prod['estado']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($prod['observacion'] ?: 'N/A'); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="admin-movements__empty">
                        <span><i class="fas fa-wheat-awn"></i></span>
                        <h5>No hay cosechas registradas</h5>
                        <p>Las cosechas aparecerán aquí cuando los agricultores las registren en el nuevo módulo.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</section>
