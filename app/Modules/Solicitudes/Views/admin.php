<?php
declare(strict_types=1);
?>

<section class="admin-requests">
    <header class="admin-requests__header">
        <div class="admin-requests__title">
            <span class="admin-requests__header-icon"><i class="fas fa-clipboard-check"></i></span>
            <div>
                <span class="admin-section-eyebrow">Abastecimiento</span>
                <h4>Gestión de solicitudes</h4>
                <p>Revisa, aprueba o rechaza los pedidos de productos enviados por agricultores.</p>
            </div>
        </div>
    </header>

    <div class="row admin-requests__metrics">
        <div class="col-md-3 col-sm-6">
            <article class="admin-requests__metric">
                <span class="admin-requests__metric-icon admin-requests__metric-icon--total"><i class="fas fa-list-check"></i></span>
                <div>
                    <span>Total solicitudes</span>
                    <strong><?php echo $stats_solicitudes['total'] ?: 0; ?></strong>
                </div>
            </article>
        </div>
        <div class="col-md-3 col-sm-6">
            <article class="admin-requests__metric">
                <span class="admin-requests__metric-icon admin-requests__metric-icon--pending"><i class="fas fa-clock"></i></span>
                <div>
                    <span>Pendientes</span>
                    <strong><?php echo $stats_solicitudes['pendientes'] ?: 0; ?></strong>
                </div>
            </article>
        </div>
        <div class="col-md-3 col-sm-6">
            <article class="admin-requests__metric">
                <span class="admin-requests__metric-icon admin-requests__metric-icon--done"><i class="fas fa-circle-check"></i></span>
                <div>
                    <span>Entregadas</span>
                    <strong><?php echo $stats_solicitudes['entregadas'] ?: 0; ?></strong>
                </div>
            </article>
        </div>
        <div class="col-md-3 col-sm-6">
            <article class="admin-requests__metric">
                <span class="admin-requests__metric-icon admin-requests__metric-icon--rejected"><i class="fas fa-ban"></i></span>
                <div>
                    <span>Rechazadas</span>
                    <strong><?php echo $stats_solicitudes['rechazadas'] ?: 0; ?></strong>
                </div>
            </article>
        </div>
    </div>

    <section class="admin-requests__panel" aria-label="Lista de solicitudes">
        <div class="admin-requests__panel-heading">
            <span class="admin-requests__panel-icon"><i class="fas fa-clipboard-list"></i></span>
            <div>
                <h5>Solicitudes registradas</h5>
                <p><?php echo ($stats_solicitudes['pendientes'] ?: 0) > 0 ? 'Hay solicitudes pendientes por revisar.' : 'No hay solicitudes pendientes.'; ?></p>
            </div>
        </div>

        <?php if ($solicitudes !== []): ?>
                <div class="table-responsive admin-requests__table-wrap">
                    <table class="table admin-requests__table" data-app-table-owner="admin-requests-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Agricultor</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($solicitudes as $solicitud): ?>
                            <tr>
                                <td><span class="admin-requests__id">#<?php echo $solicitud['id_producto_solicitud']; ?></span></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($solicitud['fecha'])); ?></td>
                                <td>
                                    <div class="admin-requests__person">
                                        <strong><?php echo htmlspecialchars($solicitud['agricultor_nombre']); ?></strong>
                                        <small><?php echo htmlspecialchars($solicitud['agricultor_email']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($solicitud['nombre']); ?></td>
                                <td><span class="admin-requests__quantity"><?php echo $solicitud['cantidad_solicitada']; ?></span></td>
                                <td>
                                    <?php
                                        $estado = $solicitud['estado'];
                                        $estado_class = $estado === 'Pendiente'
                                            ? 'admin-requests__status--pending'
                                            : ($estado === 'Aprobado'
                                                ? 'admin-requests__status--approved'
                                                : ($estado === 'Entregado'
                                                    ? 'admin-requests__status--done'
                                                    : ($estado === 'Cancelado' ? 'admin-requests__status--neutral' : 'admin-requests__status--rejected')));
                                    ?>
                                    <span class="admin-requests__status <?php echo $estado_class; ?>">
                                        <?php echo $solicitud['estado']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="admin-requests__actions">
                                    <?php if ($solicitud['estado'] == 'Pendiente'): ?>
                                    <button
                                        type="button"
                                        class="admin-requests__action admin-requests__action--approve"
                                        data-admin-request-action="aprobar"
                                        data-request-id="<?php echo $solicitud['id_producto_solicitud']; ?>"
                                        data-farmer="<?php echo htmlspecialchars($solicitud['agricultor_nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-product="<?php echo htmlspecialchars($solicitud['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-quantity="<?php echo htmlspecialchars($solicitud['cantidad_solicitada'], ENT_QUOTES, 'UTF-8'); ?>"
                                        aria-label="Aprobar solicitud <?php echo $solicitud['id_producto_solicitud']; ?>">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="admin-requests__action admin-requests__action--reject"
                                        data-admin-request-action="rechazar"
                                        data-request-id="<?php echo $solicitud['id_producto_solicitud']; ?>"
                                        data-farmer="<?php echo htmlspecialchars($solicitud['agricultor_nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-product="<?php echo htmlspecialchars($solicitud['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-quantity="<?php echo htmlspecialchars($solicitud['cantidad_solicitada'], ENT_QUOTES, 'UTF-8'); ?>"
                                        aria-label="Rechazar solicitud <?php echo $solicitud['id_producto_solicitud']; ?>">
                                        <i class="fas fa-xmark"></i>
                                    </button>
                                    <?php else: ?>
                                    <span class="admin-requests__processed">Procesada</span>
                                    <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="admin-requests__empty">
                    <span><i class="fas fa-clipboard-list"></i></span>
                    <h5>No hay solicitudes registradas</h5>
                    <p>Las solicitudes de los agricultores aparecerán aquí.</p>
                </div>
                <?php endif; ?>
    </section>
</section>

<div class="modal fade warehouse-confirm-modal admin-premium-modal admin-request-modal" id="adminRequestConfirmModal" tabindex="-1" aria-labelledby="adminRequestConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="adminRequestConfirmForm" data-review-url="<?php echo e(\App\Core\Url::route('/solicitudes/revisar')); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" id="adminRequestConfirmId">
                <input type="hidden" id="adminRequestConfirmAction">

                <div class="modal-header">
                    <span class="warehouse-modal-icon admin-request-modal__icon" data-admin-request-modal-icon>
                        <i class="fas fa-check"></i>
                    </span>
                    <div class="admin-premium-modal__heading">
                        <span class="farmer-kicker">Confirmación administrativa</span>
                        <h2 class="modal-title" id="adminRequestConfirmTitle">Aprobar solicitud</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <p class="admin-request-modal__message" data-admin-request-modal-message>Revise la información antes de continuar.</p>
                    <div class="warehouse-modal-summary admin-request-modal__summary">
                        <div>
                            <span>Agricultor</span>
                            <strong data-admin-request-modal-farmer></strong>
                        </div>
                        <div>
                            <span>Producto</span>
                            <strong data-admin-request-modal-product></strong>
                        </div>
                        <div>
                            <span>Cantidad</span>
                            <strong data-admin-request-modal-quantity></strong>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn warehouse-modal-back admin-request-modal__back" data-bs-dismiss="modal">Volver</button>
                    <button type="submit" class="btn warehouse-modal-confirm admin-request-modal__confirm" data-admin-request-modal-confirm data-skip-loading="1">
                        <i class="fas fa-check"></i>
                        <span>Confirmar aprobación</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
