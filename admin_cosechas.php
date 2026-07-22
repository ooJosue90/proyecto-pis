<?php
require_once 'conexion.php';
require_once __DIR__ . '/includes/cosecha_helpers.php';
require_once __DIR__ . '/includes/cosecha_data.php';

require_auth('Administrador');

$cosechas = cosecha_records($conn, 'Administrador', (string) $_SESSION['id_usuario']);
$metrics = cosecha_metrics($conn);
?>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-wheat-awn"></i> Gestión de Cosechas</h4>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <h3><?php echo $metrics['cosechas_pendientes']; ?></h3>
                                <p class="mb-0">Pendientes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-scale-balanced fa-2x mb-2"></i>
                                <h3><?php echo number_format($metrics['kg_validados'], 2); ?></h3>
                                <p class="mb-0">Kg validados</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-award fa-2x mb-2"></i>
                                <h3><?php echo number_format($metrics['kg_primera'], 2); ?></h3>
                                <p class="mb-0">Kg primera</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-secondary text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-layer-group fa-2x mb-2"></i>
                                <h3><?php echo number_format($metrics['kg_segunda'] + $metrics['kg_descarte'], 2); ?></h3>
                                <p class="mb-0">Kg segunda + descarte</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Agricultor</th>
                                <th>Lote</th>
                                <th>Total</th>
                                <th>Primera</th>
                                <th>Segunda</th>
                                <th>Descarte</th>
                                <th>Estado</th>
                                <th>Observaciones</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$cosechas): ?>
                                <tr><td colspan="11" class="app-empty-state">No hay cosechas registradas.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($cosechas as $cosecha): ?>
                                <tr>
                                    <td><?php echo (int) $cosecha['id_cosecha']; ?></td>
                                    <td><?php echo e(date('d/m/Y', strtotime($cosecha['fecha_cosecha']))); ?></td>
                                    <td><?php echo e($cosecha['agricultor_nombre']); ?></td>
                                    <td><?php echo e($cosecha['lote_ubicacion']); ?><br><small><?php echo e($cosecha['cultivo_tipo'] ?: 'Sin cultivo'); ?></small></td>
                                    <td><strong><?php echo number_format((float) $cosecha['cantidad_total_kg'], 2); ?> kg</strong></td>
                                    <td><?php echo number_format((float) $cosecha['calidad_primera_kg'], 2); ?> kg</td>
                                    <td><?php echo number_format((float) $cosecha['calidad_segunda_kg'], 2); ?> kg</td>
                                    <td><?php echo number_format((float) $cosecha['descarte_kg'], 2); ?> kg</td>
                                    <td>
                                        <span class="badge bg-<?php echo e(cosecha_estado_badge($cosecha['estado'])); ?>">
                                            <i class="<?php echo e(cosecha_estado_icon($cosecha['estado'])); ?>"></i>
                                            <?php echo e($cosecha['estado']); ?>
                                        </span>
                                        <?php if ($cosecha['validador_nombre']): ?>
                                            <small class="d-block text-muted">Por <?php echo e($cosecha['validador_nombre']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo e($cosecha['observaciones'] ?: 'Sin observaciones'); ?>
                                        <?php if ($cosecha['observaciones_admin']): ?>
                                            <small class="d-block text-muted">Admin: <?php echo e($cosecha['observaciones_admin']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($cosecha['estado'] === 'Registrada'): ?>
                                            <div class="d-flex flex-column gap-2">
                                                <form method="POST" action="cosecha_acciones.php" class="d-flex gap-2">
                                                    <input type="hidden" name="accion" value="validar_cosecha">
                                                    <input type="hidden" name="id_cosecha" value="<?php echo (int) $cosecha['id_cosecha']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success" data-confirm-message="¿Validar esta cosecha?">
                                                        <i class="fas fa-check"></i> Validar
                                                    </button>
                                                </form>
                                                <form method="POST" action="cosecha_acciones.php" class="d-flex gap-2">
                                                    <input type="hidden" name="accion" value="rechazar_cosecha">
                                                    <input type="hidden" name="id_cosecha" value="<?php echo (int) $cosecha['id_cosecha']; ?>">
                                                    <input type="text" name="observaciones_admin" class="form-control form-control-sm" placeholder="Motivo" required>
                                                    <button type="submit" class="btn btn-sm btn-danger" data-confirm-message="¿Rechazar esta cosecha?">
                                                        <i class="fas fa-xmark"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Sin acciones</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
