<?php
require_once 'conexion.php';
require_auth('Administrador');
require_once __DIR__ . '/includes/fitosanitario_helpers.php';
require_once __DIR__ . '/includes/fitosanitario_data.php';

$stats = fitosanitario_admin_stats($conn);
$registros = fitosanitario_records($conn, 'Administrador', (string) $_SESSION['id_usuario']);
$lotes = fitosanitario_all_lotes($conn);
$productosFitosanitarios = fitosanitario_inventory_products($conn);
?>

<div class="row mt-4 phytosanitary-admin">
    <div class="col-12">
        <div class="card phytosanitary-panel">
            <div class="card-header phytosanitary-admin__header">
                <span class="phytosanitary-admin__icon"><i class="fas fa-shield-virus"></i></span>
                <div>
                    <span class="farmer-kicker">Sanidad agrícola</span>
                    <h4 class="mb-0">Control Fitosanitario</h4>
                </div>
            </div>
            <div class="card-body">
                <section class="admin-metrics-grid phytosanitary-metrics" aria-label="Estadísticas fitosanitarias">
                    <article class="admin-metric-card">
                        <div class="admin-metric-top">
                            <span class="admin-metric-icon"><i class="fas fa-list-check"></i></span>
                            <span class="admin-metric-tag">Registros</span>
                        </div>
                        <strong class="admin-metric-value"><?php echo $stats['total']; ?></strong>
                        <p>Total de incidencias</p>
                    </article>
                    <article class="admin-metric-card">
                        <div class="admin-metric-top">
                            <span class="admin-metric-icon"><i class="fas fa-clock"></i></span>
                            <span class="admin-metric-tag">Pendientes</span>
                        </div>
                        <strong class="admin-metric-value"><?php echo $stats['pendientes']; ?></strong>
                        <p>Requieren atención</p>
                    </article>
                    <article class="admin-metric-card">
                        <div class="admin-metric-top">
                            <span class="admin-metric-icon"><i class="fas fa-triangle-exclamation"></i></span>
                            <span class="admin-metric-tag">Alta severidad</span>
                        </div>
                        <strong class="admin-metric-value"><?php echo $stats['severidad_alta']; ?></strong>
                        <p>Casos críticos</p>
                    </article>
                    <article class="admin-metric-card">
                        <div class="admin-metric-top">
                            <span class="admin-metric-icon"><i class="fas fa-circle-check"></i></span>
                            <span class="admin-metric-tag">Controlados</span>
                        </div>
                        <strong class="admin-metric-value"><?php echo $stats['controlados']; ?></strong>
                        <p>Tratamientos cerrados</p>
                    </article>
                </section>

                <div class="table-responsive mt-4">
                    <table class="table table-striped table-hover align-middle phytosanitary-table" data-app-table>
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Lote</th>
                                <th>Problema</th>
                                <th>Severidad</th>
                                <th>Producto</th>
                                <th>Responsable</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$registros): ?>
                                <tr>
                                    <td colspan="9" class="app-empty-state">No hay registros fitosanitarios.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($registros as $registro): ?>
                                <tr>
                                    <td><?php echo (int) $registro['id_control']; ?></td>
                                    <td>
                                        <strong>Lote #<?php echo (int) $registro['id_lote']; ?></strong>
                                        <small class="d-block text-muted"><?php echo e($registro['lote_ubicacion']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo e($registro['nombre_problema']); ?></strong>
                                        <small class="d-block text-muted"><?php echo e($registro['tipo']); ?></small>
                                    </td>
                                    <td>
                                        <span class="app-table-status-capsule app-table-status-capsule--<?php echo e(fitosanitario_severity_tone($registro['severidad'])); ?>">
                                            <?php echo e($registro['severidad']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo e($registro['producto_aplicado'] ?: 'Sin producto'); ?></td>
                                    <td><?php echo e($registro['usuario_responsable'] ?: 'Sin usuario'); ?></td>
                                    <td>
                                        <span class="app-table-status-capsule app-table-status-capsule--<?php echo e(fitosanitario_status_tone($registro['estado'])); ?>">
                                            <?php echo e($registro['estado']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($registro['fecha_deteccion'])); ?></td>
                                    <td>
                                        <div class="phytosanitary-actions">
                                            <button type="button" class="btn btn-sm btn-outline-info" data-fito-detail="<?php echo (int) $registro['id_control']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                data-fito-edit
                                                data-id="<?php echo (int) $registro['id_control']; ?>"
                                                data-lote="<?php echo (int) $registro['id_lote']; ?>"
                                                data-id-insumo="<?php echo e($registro['id_insumo']); ?>"
                                                data-area="<?php echo e($registro['lote_area']); ?>"
                                                data-tipo="<?php echo e($registro['tipo']); ?>"
                                                data-problema="<?php echo e($registro['nombre_problema']); ?>"
                                                data-severidad="<?php echo e($registro['severidad']); ?>"
                                                data-descripcion="<?php echo e($registro['descripcion']); ?>"
                                                data-producto="<?php echo e($registro['producto_aplicado']); ?>"
                                                data-dosis="<?php echo e($registro['dosis']); ?>"
                                                data-fecha-deteccion="<?php echo e($registro['fecha_deteccion']); ?>"
                                                data-fecha-aplicacion="<?php echo e($registro['fecha_aplicacion']); ?>"
                                                data-observaciones="<?php echo e($registro['observaciones']); ?>">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-success" data-fito-treatment="<?php echo (int) $registro['id_control']; ?>">
                                                <i class="fas fa-notes-medical"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                data-fito-status
                                                data-id="<?php echo (int) $registro['id_control']; ?>"
                                                data-estado="<?php echo e($registro['estado']); ?>">
                                                <i class="fas fa-rotate"></i>
                                            </button>
                                        </div>
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

<div class="modal fade phytosanitary-modal" id="fitoEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form data-fito-ajax-form>
                <input type="hidden" name="accion" value="editar_registro">
                <input type="hidden" name="id_control" data-fito-edit-id>
                <div class="modal-header">
                    <span class="admin-premium-modal__icon"><i class="fas fa-pen"></i></span>
                    <div>
                        <span class="farmer-kicker">Edición administrativa</span>
                        <h2 class="modal-title">Editar registro fitosanitario</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="farmer-form-grid record-field-grid phytosanitary-form__grid">
                        <label class="record-field-card">
                            <span>Lote</span>
                            <select name="id_lote" class="form-select" data-fito-edit-lote required>
                                <?php foreach ($lotes as $lote): ?>
                                    <option value="<?php echo (int) $lote['id_lote']; ?>" data-area="<?php echo e($lote['area']); ?>">
                                        Lote #<?php echo (int) $lote['id_lote']; ?> - <?php echo e($lote['ubicacion']); ?> / <?php echo e($lote['agricultor'] ?: 'Sin agricultor'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="record-field-card">
                            <span>Tipo</span>
                            <select name="tipo" class="form-select" data-fito-edit-tipo required>
                                <option value="Plaga">Plaga</option>
                                <option value="Enfermedad">Enfermedad</option>
                                <option value="Hongo">Hongo</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </label>
                        <label class="record-field-card">
                            <span>Nombre del problema</span>
                            <input type="text" name="nombre_problema" class="form-control" data-fito-edit-problema maxlength="200" required>
                        </label>
                        <label class="record-field-card">
                            <span>Severidad</span>
                            <select name="severidad" class="form-select" data-fito-edit-severidad required>
                                <option value="Baja">Baja</option>
                                <option value="Media">Media</option>
                                <option value="Alta">Alta</option>
                            </select>
                        </label>
                        <label class="record-field-card">
                            <span>Fecha detección</span>
                            <input type="date" name="fecha_deteccion" class="form-control" data-fito-edit-fecha-deteccion required>
                        </label>
                        <label class="record-field-card">
                            <span>Fecha aplicación</span>
                            <input type="date" name="fecha_aplicacion" class="form-control" data-fito-edit-fecha-aplicacion>
                        </label>
                        <label class="record-field-card">
                            <span>Producto aplicado</span>
                            <select name="id_insumo" class="form-select" data-fito-edit-producto data-fito-product-select>
                                <option value="">Sin producto aplicado</option>
                                <?php foreach ($productosFitosanitarios as $producto): ?>
                                    <option
                                        value="<?php echo (int) $producto['id_insumos']; ?>"
                                        data-stock="<?php echo e($producto['cantidad']); ?>"
                                        data-unit="<?php echo e($producto['unidad_medida']); ?>"
                                        data-dose="<?php echo e($producto['dosis_recomendada']); ?>"
                                        data-dose-unit="<?php echo e($producto['unidad_dosis']); ?>"
                                        data-application-unit="<?php echo e($producto['unidad_aplicacion']); ?>">
                                        <?php echo e($producto['nombre']); ?> · <?php echo e($producto['ingrediente_activo'] ?: $producto['tipo_producto']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="phytosanitary-stock-hint" data-fito-stock-hint>Seleccione un producto para ver stock disponible.</small>
                            <div class="phytosanitary-dose-hint" data-fito-dose-hint></div>
                        </label>
                        <label class="record-field-card phytosanitary-field--wide">
                            <span>Descripción</span>
                            <textarea name="descripcion" class="form-control" data-fito-edit-descripcion rows="3" required></textarea>
                        </label>
                        <label class="record-field-card phytosanitary-field--wide">
                            <span>Observaciones</span>
                            <textarea name="observaciones" class="form-control" data-fito-edit-observaciones rows="3"></textarea>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn warehouse-modal-back" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn warehouse-modal-confirm" data-skip-loading="1">
                        <i class="fas fa-check"></i>
                        <span>Guardar cambios</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade phytosanitary-modal" id="fitoTreatmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form data-fito-ajax-form>
                <input type="hidden" name="accion" value="agregar_tratamiento">
                <input type="hidden" name="id_control" data-fito-treatment-id>
                <input type="hidden" data-fito-treatment-area>
                <div class="modal-header">
                    <span class="admin-premium-modal__icon"><i class="fas fa-notes-medical"></i></span>
                    <div>
                        <span class="farmer-kicker">Tratamiento</span>
                        <h2 class="modal-title">Agregar tratamiento</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body phytosanitary-treatment-grid">
                    <label class="record-field-card">
                        <span>Producto aplicado</span>
                        <select name="id_insumo" class="form-select" data-fito-product-select required>
                            <option value="">Seleccione producto</option>
                            <?php foreach ($productosFitosanitarios as $producto): ?>
                                <option
                                    value="<?php echo (int) $producto['id_insumos']; ?>"
                                    data-stock="<?php echo e($producto['cantidad']); ?>"
                                    data-unit="<?php echo e($producto['unidad_medida']); ?>"
                                    data-dose="<?php echo e($producto['dosis_recomendada']); ?>"
                                    data-dose-unit="<?php echo e($producto['unidad_dosis']); ?>"
                                    data-application-unit="<?php echo e($producto['unidad_aplicacion']); ?>">
                                    <?php echo e($producto['nombre']); ?> · <?php echo e($producto['ingrediente_activo'] ?: $producto['tipo_producto']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="phytosanitary-stock-hint" data-fito-stock-hint>Seleccione un producto para ver stock disponible.</small>
                        <div class="phytosanitary-dose-hint" data-fito-dose-hint></div>
                    </label>
                    <section class="phytosanitary-dose-panel phytosanitary-field--wide" data-fito-dose-panel>
                        <div class="phytosanitary-dose-card">
                            <span>Dosis recomendada</span>
                            <strong data-fito-recommended-display>Seleccione producto</strong>
                            <small>Solo lectura</small>
                        </div>
                        <label class="phytosanitary-dose-card">
                            <span>Dosis aplicada</span>
                            <input type="number" name="dosis_aplicada" class="form-control" min="0.01" step="0.01" data-fito-applied-dose required>
                            <small>Unidad: <b data-fito-dose-unit>--</b></small>
                        </label>
                        <div class="phytosanitary-dose-card">
                            <span>Cantidad sugerida</span>
                            <strong data-fito-suggested-display>--</strong>
                            <small>Solo lectura</small>
                        </div>
                        <label class="phytosanitary-dose-card">
                            <span>Cantidad para entrega</span>
                            <input type="number" name="cantidad_aplicada" class="form-control" min="0.01" step="0.01" data-fito-applied-quantity required>
                            <small>Editable</small>
                        </label>
                        <div class="phytosanitary-dose-warning phytosanitary-dose-card--wide" data-fito-adjustment-warning hidden>
                            ⚠️ La dosis aplicada es diferente de la dosis recomendada. Al guardar el tratamiento deberá justificar el motivo del cambio.
                        </div>
                        <label class="phytosanitary-dose-card phytosanitary-dose-card--wide" data-fito-adjustment-wrap hidden>
                            <span>Motivo del ajuste</span>
                            <textarea name="motivo_ajuste" class="form-control" rows="3" data-fito-adjustment-reason></textarea>
                        </label>
                    </section>
                    <label class="record-field-card">
                        <span>Fecha aplicación</span>
                        <input type="date" name="fecha_aplicacion" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </label>
                    <label class="record-field-card">
                        <span>Estado resultante</span>
                        <select name="estado_resultante" class="form-select" required>
                            <option value="En tratamiento">En tratamiento</option>
                            <option value="Controlado">Controlado</option>
                            <option value="Pendiente">Pendiente</option>
                        </select>
                    </label>
                    <label class="record-field-card phytosanitary-field--wide">
                        <span>Observaciones</span>
                        <textarea name="observaciones" class="form-control" rows="3"></textarea>
                    </label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn warehouse-modal-back" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn warehouse-modal-confirm" data-skip-loading="1">
                        <i class="fas fa-check"></i>
                        <span>Registrar tratamiento</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade phytosanitary-modal" id="fitoStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form data-fito-ajax-form>
                <input type="hidden" name="accion" value="cambiar_estado">
                <input type="hidden" name="id_control" data-fito-status-id>
                <div class="modal-header">
                    <span class="admin-premium-modal__icon"><i class="fas fa-rotate"></i></span>
                    <div>
                        <span class="farmer-kicker">Seguimiento</span>
                        <h2 class="modal-title">Cambiar estado</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <label class="record-field-card">
                        <span>Nuevo estado</span>
                        <select name="estado" class="form-select" data-fito-status-estado required>
                            <option value="Pendiente">Pendiente</option>
                            <option value="En tratamiento">En tratamiento</option>
                            <option value="Controlado">Controlado</option>
                        </select>
                    </label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn warehouse-modal-back" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn warehouse-modal-confirm" data-skip-loading="1">
                        <i class="fas fa-check"></i>
                        <span>Actualizar estado</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade phytosanitary-modal" id="fitoDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <span class="admin-premium-modal__icon"><i class="fas fa-shield-virus"></i></span>
                <div>
                    <span class="farmer-kicker">Detalle fitosanitario</span>
                    <h2 class="modal-title">Historial del registro</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" data-fito-detail-content>
                <div class="text-center mt-4"><i class="fas fa-spinner fa-spin"></i><p>Cargando...</p></div>
            </div>
        </div>
    </div>
</div>
