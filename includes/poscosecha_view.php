<?php

function render_poscosecha_stepper(string $currentEstado, string $size = 'normal'): void
{
    $currentIndex = poscosecha_estado_index($currentEstado);
    $class = $size === 'compact' ? 'posharvest-stepper posharvest-stepper--compact' : 'posharvest-stepper';
    ?>
    <div class="<?php echo e($class); ?>" aria-label="Progreso de poscosecha">
        <?php foreach (poscosecha_estados() as $index => $estado): ?>
            <?php
            $stateClass = $index < $currentIndex
                ? 'is-complete'
                : ($index === $currentIndex ? 'is-current' : 'is-pending');
            ?>
            <span class="posharvest-step <?php echo e($stateClass); ?>" data-posharvest-stage="<?php echo e($estado); ?>">
                <i class="<?php echo e($index < $currentIndex ? 'fas fa-check' : poscosecha_estado_icon($estado)); ?>"></i>
                <span><?php echo e($estado); ?></span>
            </span>
        <?php endforeach; ?>
    </div>
    <?php
}

function poscosecha_stage_button_label(?string $estado): string
{
    return match ($estado) {
        'Lavado' => 'Registrar lavado',
        'Clasificación' => 'Registrar clasificación',
        'Empaque' => 'Definir destino',
        'Almacenamiento' => 'Registrar almacenamiento',
        'Finalizada' => 'Finalizar poscosecha',
        default => 'Sin avance',
    };
}

function poscosecha_main_destination(array $record): string
{
    $destinos = [
        'Exportación' => (float) $record['kg_exportacion'],
        'Mercado nacional' => (float) $record['kg_mercado_nacional'],
        'Procesamiento' => (float) $record['kg_procesamiento'],
    ];
    arsort($destinos);
    $principal = key($destinos);

    return current($destinos) > 0 ? (string) $principal : 'Pendiente';
}

function render_poscosecha_panel(mysqli $conn, string $role, string $userId, bool $embedded = false): void
{
    $records = poscosecha_records($conn, $role, $userId);
    $metrics = poscosecha_metrics($conn);
    $availableCosechas = $role === 'Agricultor' ? [] : poscosecha_available_cosechas($conn);
    $canManage = in_array($role, ['Administrador', 'Bodeguero'], true);

    $mermaAlta = 0;
    $descarteAlto = 0;
    foreach ($records as $record) {
        $recibidos = (float) $record['kg_recibidos'];
        if ($recibidos > 0 && ((float) $record['kg_merma'] / $recibidos) > 0.10) {
            $mermaAlta++;
        }
        if ($recibidos > 0 && ((float) $record['kg_descarte'] / $recibidos) > 0.10) {
            $descarteAlto++;
        }
    }
    ?>
    <section class="<?php echo $embedded ? 'row mt-4' : ''; ?> posharvest-module">
        <div class="<?php echo $embedded ? 'col-12' : ''; ?>">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-3">
                    <span class="admin-premium-modal__icon"><i class="fas fa-boxes-packing"></i></span>
                    <div>
                        <span class="farmer-kicker">Flujo productivo</span>
                        <h4 class="mb-0">Poscosecha</h4>
                    </div>
                </div>
                <div class="card-body posharvest-layout">
                    <section class="posharvest-section" aria-labelledby="posharvest-summary-title">
                        <div class="posharvest-section-heading">
                            <div>
                                <span class="farmer-kicker">Resumen operativo</span>
                                <h5 id="posharvest-summary-title">Indicadores de poscosecha</h5>
                            </div>
                        </div>

                        <div class="posharvest-alert-grid" aria-label="Alertas de poscosecha">
                            <?php if ($canManage && count($availableCosechas) > 0): ?>
                                <div class="posharvest-alert">
                                    <i class="fas fa-triangle-exclamation"></i>
                                    <span><?php echo count($availableCosechas); ?> cosecha(s) recibida(s) sin iniciar poscosecha.</span>
                                </div>
                            <?php endif; ?>
                            <?php if ($mermaAlta > 0): ?>
                                <div class="posharvest-alert posharvest-alert--warning">
                                    <i class="fas fa-arrow-trend-down"></i>
                                    <span><?php echo $mermaAlta; ?> proceso(s) con merma superior al 10%.</span>
                                </div>
                            <?php endif; ?>
                            <?php if ($descarteAlto > 0): ?>
                                <div class="posharvest-alert posharvest-alert--warning">
                                    <i class="fas fa-recycle"></i>
                                    <span><?php echo $descarteAlto; ?> proceso(s) con descarte superior al 10%.</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="posharvest-metrics-grid" aria-label="Indicadores de poscosecha">
                            <article class="posharvest-metric-card">
                                <div class="posharvest-metric-top">
                                    <span class="posharvest-metric-icon"><i class="fas fa-warehouse"></i></span>
                                    <span class="posharvest-metric-tag">Recepción</span>
                                </div>
                                <strong class="posharvest-metric-value"><?php echo $metrics['recepcion']; ?></strong>
                                <p>Procesos en recepción</p>
                            </article>
                            <article class="posharvest-metric-card">
                                <div class="posharvest-metric-top">
                                    <span class="posharvest-metric-icon"><i class="fas fa-spinner"></i></span>
                                    <span class="posharvest-metric-tag">En curso</span>
                                </div>
                                <strong class="posharvest-metric-value"><?php echo $metrics['en_curso']; ?></strong>
                                <p>Lavado, clasificación, empaque o almacenamiento</p>
                            </article>
                            <article class="posharvest-metric-card">
                                <div class="posharvest-metric-top">
                                    <span class="posharvest-metric-icon"><i class="fas fa-circle-check"></i></span>
                                    <span class="posharvest-metric-tag">Finalizadas</span>
                                </div>
                                <strong class="posharvest-metric-value"><?php echo $metrics['finalizadas']; ?></strong>
                                <p><?php echo $metrics['listas_despacho']; ?> listas para despacho</p>
                            </article>
                            <article class="posharvest-metric-card">
                                <div class="posharvest-metric-top">
                                    <span class="posharvest-metric-icon"><i class="fas fa-scale-balanced"></i></span>
                                    <span class="posharvest-metric-tag">Kg activos</span>
                                </div>
                                <strong class="posharvest-metric-value"><?php echo number_format($metrics['kg_en_poscosecha'], 2); ?></strong>
                                <p>Kg en poscosecha</p>
                            </article>
                        </div>

                        <div class="posharvest-quality-grid">
                            <article class="posharvest-quality-card">
                                <span class="posharvest-quality-icon"><i class="fas fa-award"></i></span>
                                <div><span>Kg primera</span><strong><?php echo number_format($metrics['kg_primera'], 2); ?></strong></div>
                                <span class="posharvest-quality-state">Calidad</span>
                            </article>
                            <article class="posharvest-quality-card">
                                <span class="posharvest-quality-icon"><i class="fas fa-layer-group"></i></span>
                                <div><span>Kg segunda</span><strong><?php echo number_format($metrics['kg_segunda'], 2); ?></strong></div>
                                <span class="posharvest-quality-state">Kg</span>
                            </article>
                            <article class="posharvest-quality-card">
                                <span class="posharvest-quality-icon"><i class="fas fa-recycle"></i></span>
                                <div><span>Kg descarte</span><strong><?php echo number_format($metrics['kg_descarte'], 2); ?></strong></div>
                                <span class="posharvest-quality-state">Control</span>
                            </article>
                            <article class="posharvest-quality-card">
                                <span class="posharvest-quality-icon"><i class="fas fa-arrow-trend-down"></i></span>
                                <div><span>Kg merma</span><strong><?php echo number_format($metrics['kg_merma'], 2); ?></strong></div>
                                <span class="posharvest-quality-state">Merma</span>
                            </article>
                        </div>
                    </section>

                    <?php if ($canManage): ?>
                        <section class="posharvest-section" aria-labelledby="posharvest-start-title">
                            <div class="posharvest-section-heading">
                                <div>
                                    <span class="farmer-kicker">Recepción</span>
                                    <h5 id="posharvest-start-title">Iniciar poscosecha</h5>
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-success"
                                    data-bs-toggle="modal"
                                    data-bs-target="#poscosechaRecepcionModal">
                                    <i class="fas fa-plus"></i> Registrar recepción
                                </button>
                            </div>
                            <div class="posharvest-start-card">
                                <span class="posharvest-start-card__icon"><i class="fas fa-warehouse"></i></span>
                                <div>
                                    <strong><?php echo count($availableCosechas); ?> cosechas recibidas disponibles</strong>
                                    <p>Seleccione la cosecha, confirme los kilos recibidos y deje la observación inicial.</p>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>

                    <section class="posharvest-section" aria-labelledby="posharvest-tracking-title">
                        <div class="posharvest-section-heading">
                            <div>
                                <span class="farmer-kicker">Seguimiento</span>
                                <h5 id="posharvest-tracking-title">Procesos registrados</h5>
                            </div>
                            <div class="posharvest-filter">
                                <span><?php echo count($records); ?> registros</span>
                                <select class="form-select form-select-sm" data-poscosecha-filter aria-label="Filtrar poscosecha">
                                    <option value="todos">Todos</option>
                                    <option value="ready">Finalizadas listas para despacho</option>
                                    <option value="not_ready">No listas</option>
                                    <option value="pending_destination">Pendientes de destino</option>
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle posharvest-table" data-app-table data-app-table-owner="posharvest-table">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Proceso</th>
                                        <th>Resumen</th>
                                        <th>Destino</th>
                                        <th>Progreso</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!$records): ?>
                                        <tr data-poscosecha-empty><td colspan="6" class="app-empty-state">No hay procesos de poscosecha registrados.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($records as $record): ?>
                                        <?php
                                        $kgRecibidos = (float) $record['kg_recibidos'];
                                        $kgAprovechables = (float) $record['kg_primera'] + (float) $record['kg_segunda'];
                                        $kgDestino = (float) $record['kg_exportacion'] + (float) $record['kg_mercado_nacional'] + (float) $record['kg_procesamiento'];
                                        $rendimiento = $kgRecibidos > 0 ? ($kgAprovechables / $kgRecibidos) * 100 : 0;
                                        $destinoPrincipal = poscosecha_main_destination($record);
                                        $filterStatus = ((string) $record['estado'] === 'Finalizada' && (int) $record['listo_para_despacho'] === 1)
                                            ? 'ready'
                                            : ($kgDestino <= 0 ? 'pending_destination' : 'not_ready');
                                        $nextEstado = poscosecha_next_estado($record['estado']);
                                        $previousEstado = poscosecha_previous_estado($record['estado']);
                                        $history = poscosecha_history($conn, (int) $record['id_poscosecha']);
                                        ?>
                                        <tr data-poscosecha-row data-filter-status="<?php echo e($filterStatus); ?>">
                                            <td>
                                                <strong>Poscosecha #<?php echo (int) $record['id_poscosecha']; ?></strong>
                                                <small class="d-block text-muted">Cosecha #<?php echo (int) $record['id_cosecha']; ?> · <?php echo date('d/m/Y', strtotime($record['fecha_cosecha'])); ?></small>
                                                <small class="d-block text-muted"><?php echo e($record['lote_ubicacion']); ?> · <?php echo e($record['cultivo_tipo'] ?: 'Sin cultivo'); ?></small>
                                            </td>
                                            <td>
                                                <div class="posharvest-process-summary">
                                                    <span><strong><?php echo number_format($kgRecibidos, 2); ?></strong><small>Kg recibidos</small></span>
                                                    <span><strong><?php echo number_format($kgAprovechables, 2); ?></strong><small>Kg aprovechables</small></span>
                                                    <span><strong><?php echo number_format((float) $record['kg_merma'], 2); ?></strong><small>Kg merma</small></span>
                                                    <span><strong><?php echo number_format($rendimiento, 1); ?>%</strong><small>Rendimiento</small></span>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo e($destinoPrincipal); ?></strong>
                                                <small class="d-block text-muted">Exp: <?php echo number_format((float) $record['kg_exportacion'], 2); ?> · Nac: <?php echo number_format((float) $record['kg_mercado_nacional'], 2); ?> · Proc: <?php echo number_format((float) $record['kg_procesamiento'], 2); ?></small>
                                                <small class="d-block text-muted">Responsable: <?php echo e($record['responsable_nombre']); ?></small>
                                            </td>
                                            <td>
                                                <?php render_poscosecha_stepper($record['estado'], 'compact'); ?>
                                                <details class="posharvest-history mt-2">
                                                    <summary>Historial</summary>
                                                    <?php if (!$history): ?>
                                                        <small class="text-muted">Sin historial registrado.</small>
                                                    <?php endif; ?>
                                                    <div class="posharvest-timeline">
                                                        <?php foreach ($history as $item): ?>
                                                            <div class="posharvest-timeline-item">
                                                                <span class="posharvest-timeline-dot"></span>
                                                                <div>
                                                                    <strong><?php echo e($item['estado_anterior'] ?: 'Inicio'); ?> → <?php echo e($item['estado_nuevo']); ?></strong>
                                                                    <small><?php echo e(date('d/m/Y H:i', strtotime($item['fecha_cambio']))); ?> · <?php echo e($item['usuario_nombre']); ?></small>
                                                                    <?php if ($item['observaciones']): ?>
                                                                        <p><?php echo e($item['observaciones']); ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </details>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo e(poscosecha_estado_badge($record['estado'])); ?>">
                                                    <i class="<?php echo e(poscosecha_estado_icon($record['estado'])); ?>"></i>
                                                    <?php echo e($record['estado']); ?>
                                                </span>
                                                <span class="badge bg-<?php echo (int) $record['listo_para_despacho'] === 1 ? 'success' : 'secondary'; ?>">
                                                    <?php echo (int) $record['listo_para_despacho'] === 1 ? 'Listo' : 'No listo'; ?>
                                                </span>
                                            </td>
                                            <td class="posharvest-actions-cell">
                                                <?php if ($canManage): ?>
                                                    <div class="posharvest-actions">
                                                        <?php if ($nextEstado): ?>
                                                            <button
                                                                type="button"
                                                                class="btn btn-sm btn-success"
                                                                data-poscosecha-stage-open
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#poscosechaEtapaModal"
                                                                data-id="<?php echo (int) $record['id_poscosecha']; ?>"
                                                                data-current="<?php echo e($record['estado']); ?>"
                                                                data-next="<?php echo e($nextEstado); ?>"
                                                                data-kg-recibidos="<?php echo e($record['kg_recibidos']); ?>"
                                                                data-kg-lavados="<?php echo e($record['kg_lavados']); ?>"
                                                                data-kg-clasificados="<?php echo e($record['kg_clasificados']); ?>"
                                                                data-kg-primera="<?php echo e($record['kg_primera']); ?>"
                                                                data-kg-segunda="<?php echo e($record['kg_segunda']); ?>"
                                                                data-kg-descarte="<?php echo e($record['kg_descarte']); ?>"
                                                                data-kg-merma="<?php echo e($record['kg_merma']); ?>"
                                                                data-kg-exportacion="<?php echo e($record['kg_exportacion']); ?>"
                                                                data-kg-mercado-nacional="<?php echo e($record['kg_mercado_nacional']); ?>"
                                                                data-kg-procesamiento="<?php echo e($record['kg_procesamiento']); ?>">
                                                                <i class="fas fa-arrow-right"></i> <?php echo e(poscosecha_stage_button_label($nextEstado)); ?>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($role === 'Administrador' && $previousEstado): ?>
                                                            <form method="POST" action="poscosecha_acciones.php" class="posharvest-stage-form">
                                                                <input type="hidden" name="accion" value="avanzar_etapa">
                                                                <input type="hidden" name="id_poscosecha" value="<?php echo (int) $record['id_poscosecha']; ?>">
                                                                <input type="hidden" name="etapa_nueva" value="<?php echo e($previousEstado); ?>">
                                                                <input type="text" name="observacion_etapa" class="form-control form-control-sm mb-1" placeholder="Motivo de corrección">
                                                                <button type="submit" class="btn btn-sm btn-outline-warning" data-confirm-message="¿Corregir etapa a <?php echo e($previousEstado); ?>?">
                                                                    <i class="fas fa-arrow-left"></i> Corregir a <?php echo e($previousEstado); ?>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Consulta</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </section>

    <?php if ($canManage): ?>
        <div class="modal fade" id="poscosechaRecepcionModal" tabindex="-1" aria-labelledby="poscosechaRecepcionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form method="POST" action="poscosecha_acciones.php" class="modal-content posharvest-modal" data-poscosecha-reception-form>
                    <input type="hidden" name="accion" value="crear_poscosecha">
                    <input type="hidden" name="destino_previsto" value="Exportación">
                    <div class="modal-header">
                        <div>
                            <span class="farmer-kicker">Recepción</span>
                            <h5 class="modal-title" id="poscosechaRecepcionModalLabel">Registrar recepción de poscosecha</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Cosecha asociada</label>
                                <select name="id_cosecha" class="form-select" required data-poscosecha-reception-cosecha>
                                    <option value="">Seleccione una cosecha recibida</option>
                                    <?php foreach ($availableCosechas as $cosecha): ?>
                                        <option value="<?php echo (int) $cosecha['id_cosecha']; ?>" data-kg="<?php echo e($cosecha['cantidad_total_kg']); ?>">
                                            #<?php echo (int) $cosecha['id_cosecha']; ?> · <?php echo e($cosecha['cultivo_tipo'] ?: 'Cosecha'); ?> · <?php echo e($cosecha['lote_ubicacion']); ?> · <?php echo number_format((float) $cosecha['cantidad_total_kg'], 2); ?> kg
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Solo aparecen cosechas recibidas sin poscosecha registrada.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha de ingreso</label>
                                <input type="date" name="fecha_ingreso" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kg recibidos</label>
                                <input type="number" name="kg_recibidos" class="form-control" min="0.01" step="0.01" required data-poscosecha-reception-kg>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observación</label>
                                <textarea name="observaciones" class="form-control" rows="3" placeholder="Estado de llegada, novedades o responsable de recepción"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar recepción</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="poscosechaEtapaModal" tabindex="-1" aria-labelledby="poscosechaEtapaModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form method="POST" action="poscosecha_acciones.php" class="modal-content posharvest-modal" data-poscosecha-stage-form>
                    <input type="hidden" name="accion" value="avanzar_etapa">
                    <input type="hidden" name="id_poscosecha" data-stage-field="id_poscosecha">
                    <input type="hidden" name="etapa_nueva" data-stage-field="etapa_nueva">
                    <div class="modal-header">
                        <div>
                            <span class="farmer-kicker" data-stage-current>Poscosecha</span>
                            <h5 class="modal-title" id="poscosechaEtapaModalLabel" data-stage-title>Registrar avance</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="posharvest-live-summary">
                            <span><small>Total recibido</small><strong data-live-recibido>0.00 kg</strong></span>
                            <span><small>Total clasificado</small><strong data-live-clasificado>0.00 kg</strong></span>
                            <span><small>Total distribuido</small><strong data-live-distribuido>0.00 kg</strong></span>
                            <span><small>Diferencia pendiente</small><strong data-live-pendiente>0.00 kg</strong></span>
                        </div>

                        <div class="posharvest-modal-step" data-stage-section="Lavado">
                            <div class="posharvest-form-step__heading">
                                <span class="posharvest-form-step__icon"><i class="fas fa-droplet"></i></span>
                                <div><strong>Lavado</strong><small>Confirme los kilos lavados antes de clasificar.</small></div>
                            </div>
                            <label class="form-label">Kg lavados</label>
                            <input type="number" name="kg_lavados" class="form-control" min="0" step="0.01" data-stage-input="kg_lavados">
                        </div>

                        <div class="posharvest-modal-step" data-stage-section="Clasificación">
                            <div class="posharvest-form-step__heading">
                                <span class="posharvest-form-step__icon"><i class="fas fa-list-check"></i></span>
                                <div><strong>Clasificación</strong><small>Distribuya el producto lavado por calidad y control.</small></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Kg primera</label>
                                    <input type="number" name="kg_primera" class="form-control" min="0" step="0.01" data-stage-input="kg_primera">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Kg segunda</label>
                                    <input type="number" name="kg_segunda" class="form-control" min="0" step="0.01" data-stage-input="kg_segunda">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Kg descarte</label>
                                    <input type="number" name="kg_descarte" class="form-control" min="0" step="0.01" data-stage-input="kg_descarte">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Kg merma</label>
                                    <input type="number" name="kg_merma" class="form-control" min="0" step="0.01" data-stage-input="kg_merma">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Motivo de merma</label>
                                    <textarea name="motivo_merma" class="form-control" rows="2" data-stage-input="motivo_merma"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="posharvest-modal-step" data-stage-section="Empaque">
                            <div class="posharvest-form-step__heading">
                                <span class="posharvest-form-step__icon"><i class="fas fa-box-open"></i></span>
                                <div><strong>Empaque y destino</strong><small>Defina la salida prevista del producto aprovechable.</small></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Kg exportación</label>
                                    <input type="number" name="kg_exportacion" class="form-control" min="0" step="0.01" data-stage-input="kg_exportacion">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Kg mercado nacional</label>
                                    <input type="number" name="kg_mercado_nacional" class="form-control" min="0" step="0.01" data-stage-input="kg_mercado_nacional">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Kg procesamiento</label>
                                    <input type="number" name="kg_procesamiento" class="form-control" min="0" step="0.01" data-stage-input="kg_procesamiento">
                                </div>
                            </div>
                        </div>

                        <div class="posharvest-modal-step" data-stage-section="Almacenamiento">
                            <div class="posharvest-form-step__heading">
                                <span class="posharvest-form-step__icon"><i class="fas fa-boxes-stacked"></i></span>
                                <div><strong>Almacenamiento</strong><small>Confirme que el producto quedó almacenado según destino.</small></div>
                            </div>
                            <div class="posharvest-confirm-box">
                                <i class="fas fa-circle-check"></i>
                                <span>El sistema usará la distribución registrada en Empaque para continuar.</span>
                            </div>
                        </div>

                        <div class="posharvest-modal-step" data-stage-section="Finalizada">
                            <div class="posharvest-form-step__heading">
                                <span class="posharvest-form-step__icon"><i class="fas fa-circle-check"></i></span>
                                <div><strong>Finalizar poscosecha</strong><small>Al finalizar quedará disponible para consulta futura de Despacho.</small></div>
                            </div>
                            <div class="posharvest-confirm-box">
                                <i class="fas fa-truck-ramp-box"></i>
                                <span>Marcar como lista para despacho.</span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Observación del cambio</label>
                            <textarea name="observacion_etapa" class="form-control" rows="3" data-stage-input="observacion_etapa" placeholder="Detalle del avance, novedades o responsable operativo"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success" data-stage-submit><i class="fas fa-arrow-right"></i> Avanzar</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    <?php
}
