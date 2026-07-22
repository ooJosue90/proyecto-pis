<?php declare(strict_types=1);
?>

<section class="admin-invoices" data-facturas-csrf="<?php echo e($csrfToken); ?>">
        <?php if (!$tablesReady): ?>
            <div class="alert alert-warning">
                <i class="fas fa-triangle-exclamation"></i>
                El módulo requiere ejecutar <strong>facturas_compra.sql</strong> en phpMyAdmin.
            </div>
        <?php else: ?>
            <div class="admin-invoice-card">
                <header class="admin-invoice-header">
                    <span class="admin-invoice-header__icon"><i class="fas fa-receipt"></i></span>
                    <div class="admin-invoice-heading-copy">
                        <span class="admin-section-eyebrow">Control financiero</span>
                        <h4>Facturas de compra</h4>
                        <p>Revisa comprobantes, montos y estados de las compras registradas.</p>
                    </div>
                    <span class="admin-invoice-count">
                        <i class="fas fa-clock" aria-hidden="true"></i>
                        <strong><?php echo (int) $stats['registradas']; ?></strong>
                        <span>por revisar</span>
                    </span>
                </header>
                    <div class="admin-invoice-stats" aria-label="Resumen financiero">
                        <article class="admin-invoice-stat admin-invoice-stat--documents">
                            <span class="admin-invoice-stat__icon"><i class="fas fa-file-invoice"></i></span>
                            <div><span>Documentos</span><strong><?php echo (int) $stats['total_facturas']; ?></strong><small>Facturas registradas</small></div>
                        </article>
                        <article class="admin-invoice-stat admin-invoice-stat--amount">
                            <span class="admin-invoice-stat__icon"><i class="fas fa-coins"></i></span>
                            <div><span>Volumen total</span><strong>$<?php echo number_format((float) $stats['total_monto'], 2); ?></strong><small>Monto registrado</small></div>
                        </article>
                        <article class="admin-invoice-stat admin-invoice-stat--approved">
                            <span class="admin-invoice-stat__icon"><i class="fas fa-circle-check"></i></span>
                            <div><span>Aprobado</span><strong>$<?php echo number_format((float) $stats['total_aprobado'], 2); ?></strong><small>Monto validado</small></div>
                        </article>
                        <article class="admin-invoice-stat admin-invoice-stat--pending">
                            <span class="admin-invoice-stat__icon"><i class="fas fa-hourglass-half"></i></span>
                            <div><span>Pendientes</span><strong><?php echo (int) $stats['registradas']; ?></strong><small>Requieren revisión</small></div>
                        </article>
                    </div>

                    <section class="admin-invoice-filter-panel" aria-labelledby="adminInvoiceFiltersTitle">
                        <div class="admin-invoice-section-heading">
                            <span><i class="fas fa-sliders"></i></span>
                            <div><h5 id="adminInvoiceFiltersTitle">Filtrar facturas</h5><p>Combina proveedor, estado o rango de fechas.</p></div>
                        </div>
                    <form class="admin-invoice-filters" id="purchaseInvoiceFilters">
                        <div class="admin-invoice-filter admin-invoice-filter--provider">
                            <label class="form-label">Proveedor</label>
                            <select name="id_proveedor" class="form-select" data-filter-control>
                                <option value="">Todos</option>
                                <?php foreach ($proveedores as $proveedor): ?>
                                    <option value="<?php echo e($proveedor['id_proveedor']); ?>" <?php echo $filtroProveedor === (int) $proveedor['id_proveedor'] ? 'selected' : ''; ?>>
                                        <?php echo e($proveedor['Nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="admin-invoice-filter">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select" data-filter-control>
                                <option value="">Todos</option>
                                <?php foreach ($estadosValidos as $estado): ?>
                                    <option value="<?php echo e($estado); ?>" <?php echo $filtroEstado === $estado ? 'selected' : ''; ?>><?php echo e($estado); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="admin-invoice-filter">
                            <label class="form-label">Desde</label>
                            <input type="date" name="fecha_desde" class="form-control" value="<?php echo e($fechaDesde); ?>">
                        </div>
                        <div class="admin-invoice-filter">
                            <label class="form-label">Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control" value="<?php echo e($fechaHasta); ?>">
                        </div>
                        <div class="admin-invoice-filter-actions">
                            <button type="button" class="btn btn-outline-secondary" data-clear-invoice-filters><i class="fas fa-rotate-left"></i> Limpiar</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Aplicar filtros</button>
                        </div>
                    </form>
                    </section>

                    <section class="admin-invoice-ledger" aria-labelledby="adminInvoiceLedgerTitle">
                        <div class="admin-invoice-ledger__heading">
                            <div><span class="admin-section-eyebrow">Registro contable</span><h5 id="adminInvoiceLedgerTitle">Historial de facturas</h5></div>
                            <span class="admin-invoice-ledger__count"><?php echo count($facturas); ?> resultados</span>
                        </div>
                    <div class="table-responsive admin-invoice-table-wrap">
                        <table class="table align-middle admin-invoice-table" data-app-table-owner="admin-invoices-table">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Número</th>
                                    <th>Fecha</th>
                                    <th>Proveedor</th>
                                    <th>Bodeguero</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($facturas): ?>
                                    <?php foreach ($facturas as $factura): ?>
                                        <?php
                                        $badge = $factura['estado'] === 'Aprobada'
                                            ? 'success'
                                            : ($factura['estado'] === 'Registrada'
                                                ? 'warning'
                                                : ($factura['estado'] === 'Anulada' ? 'secondary' : 'danger'));
                                        ?>
                                        <tr>
                                            <td><span class="admin-invoice-order"><?php echo $factura['id_pedido'] ? '#' . (int) $factura['id_pedido'] : 'Sin pedido'; ?></span></td>
                                            <td><strong class="admin-invoice-number"><?php echo e($factura['numero_factura']); ?></strong></td>
                                            <td><time class="admin-invoice-date" datetime="<?php echo e($factura['fecha']); ?>"><?php echo date('d/m/Y', strtotime($factura['fecha'])); ?></time></td>
                                            <td><span class="admin-invoice-provider"><?php echo e($factura['proveedor_nombre']); ?></span></td>
                                            <td><span class="admin-invoice-owner"><?php echo e($factura['bodeguero_nombre']); ?></span></td>
                                            <td><strong class="admin-invoice-total">$<?php echo number_format((float) $factura['total'], 2); ?></strong></td>
                                            <td><span class="admin-invoice-status admin-invoice-status--<?php echo strtolower(e($factura['estado'])); ?>"><i></i><?php echo e($factura['estado']); ?></span></td>
                                            <td class="admin-invoice-actions">
                                                <div class="admin-invoice-actions__group">
                                                <button class="admin-invoice-action admin-invoice-action--view" onclick="verDetallesFactura(<?php echo (int) $factura['id_factura_compra']; ?>)" aria-label="Ver factura <?php echo e($factura['numero_factura']); ?>">
                                                    <i class="fas fa-eye"></i><span>Ver</span>
                                                </button>
                                                <?php if ($factura['estado'] === 'Registrada'): ?>
                                                    <button
                                                        type="button"
                                                        class="admin-invoice-action admin-invoice-action--approve"
                                                        data-admin-invoice-action="aprobar_factura"
                                                        data-invoice-id="<?php echo (int) $factura['id_factura_compra']; ?>"
                                                        data-invoice-number="<?php echo e($factura['numero_factura']); ?>"
                                                        data-invoice-provider="<?php echo e($factura['proveedor_nombre']); ?>"
                                                        data-invoice-total="$<?php echo number_format((float) $factura['total'], 2); ?>">
                                                        <i class="fas fa-check"></i><span>Aprobar</span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="admin-invoice-action admin-invoice-action--reject"
                                                        data-admin-invoice-action="rechazar_factura"
                                                        data-invoice-id="<?php echo (int) $factura['id_factura_compra']; ?>"
                                                        data-invoice-number="<?php echo e($factura['numero_factura']); ?>"
                                                        data-invoice-provider="<?php echo e($factura['proveedor_nombre']); ?>"
                                                        data-invoice-total="$<?php echo number_format((float) $factura['total'], 2); ?>">
                                                        <i class="fas fa-xmark"></i><span>Rechazar</span>
                                                    </button>
                                                <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="app-empty-state">No hay facturas que coincidan con los filtros.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    </section>
            </div>
        <?php endif; ?>
</section>

<div class="modal fade admin-premium-modal admin-invoice-review-modal" id="adminInvoiceConfirmModal" tabindex="-1" aria-labelledby="adminInvoiceConfirmTitle" aria-describedby="adminInvoiceConfirmMessage" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="adminInvoiceConfirmForm">
                <input type="hidden" name="_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" id="adminInvoiceConfirmId">
                <input type="hidden" id="adminInvoiceConfirmAction">

                <div class="modal-header">
                    <span class="admin-invoice-review__icon" data-admin-invoice-modal-icon>
                        <i class="fas fa-file-circle-check"></i>
                    </span>
                    <div class="admin-premium-modal__heading">
                        <span class="farmer-kicker" data-admin-invoice-modal-eyebrow>Revisión administrativa</span>
                        <h2 class="modal-title" id="adminInvoiceConfirmTitle">Aprobar factura</h2>
                        <p>Verifique los datos antes de registrar la decisión.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div class="admin-invoice-review__message">
                        <i class="fas fa-circle-info" data-admin-invoice-message-icon aria-hidden="true"></i>
                        <p id="adminInvoiceConfirmMessage" data-admin-invoice-modal-message>Revise la factura antes de continuar.</p>
                    </div>
                    <div class="admin-invoice-review__summary">
                        <div class="admin-invoice-review__number">
                            <span>Número</span>
                            <strong data-admin-invoice-modal-number></strong>
                        </div>
                        <div class="admin-invoice-review__provider">
                            <span>Proveedor</span>
                            <strong data-admin-invoice-modal-provider></strong>
                        </div>
                        <div class="admin-invoice-review__total">
                            <span>Total</span>
                            <strong data-admin-invoice-modal-total></strong>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn admin-invoice-review__back" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn admin-invoice-review__confirm" data-admin-invoice-modal-confirm data-skip-loading="1">
                        <i class="fas fa-check"></i>
                        <span>Confirmar aprobación</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade admin-premium-modal admin-invoice-detail-modal" id="modalDetallesFactura" tabindex="-1" aria-labelledby="modalDetallesFacturaTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="admin-premium-modal__icon"><i class="fas fa-file-invoice-dollar"></i></span>
                <div class="admin-premium-modal__heading">
                    <span class="farmer-kicker">Control financiero</span>
                    <h2 class="modal-title" id="modalDetallesFacturaTitle">Detalle de factura</h2>
                    <p>Resumen contable y recepción de insumos.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="detallesFacturaContent"></div>
            <div class="modal-footer">
                <span class="admin-premium-modal__security"><i class="fas fa-shield-halved"></i> Registro protegido</span>
                <button type="button" class="btn admin-premium-modal__close" data-bs-dismiss="modal">
                    <i class="fas fa-xmark"></i> Cerrar detalle
                </button>
            </div>
        </div>
    </div>
</div>
