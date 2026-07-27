<?php

declare(strict_types=1);

?>

<section class="admin-suppliers" data-abastecimiento-csrf="<?php echo e($csrfToken); ?>">
    <header class="admin-suppliers__header">
        <span class="admin-suppliers__header-icon"><i class="fas fa-truck-fast"></i></span>
        <div class="admin-suppliers__heading-copy">
            <span class="admin-section-eyebrow">Abastecimiento</span>
            <h4>Proveedores y pedidos</h4>
            <p>Gestiona aliados comerciales, compras pendientes y el flujo de recepción de insumos.</p>
        </div>
        <span class="admin-suppliers__pending-chip">
            <i class="fas fa-hourglass-half" aria-hidden="true"></i>
            <strong><?php echo (int) ($stats['pedidos_pendientes'] ?: 0); ?></strong>
            <span>pendientes</span>
        </span>
    </header>

    <div class="admin-suppliers__metrics" aria-label="Resumen de proveedores y pedidos">
        <article class="admin-suppliers__metric admin-suppliers__metric--providers">
            <span class="admin-suppliers__metric-icon"><i class="fas fa-building"></i></span>
            <div><span>Proveedores</span><strong><?php echo (int) ($stats['total_proveedores'] ?: 0); ?></strong><small>Aliados registrados</small></div>
        </article>
        <article class="admin-suppliers__metric admin-suppliers__metric--orders">
            <span class="admin-suppliers__metric-icon"><i class="fas fa-cart-shopping"></i></span>
            <div><span>Pedidos</span><strong><?php echo (int) ($stats['total_pedidos'] ?: 0); ?></strong><small>Órdenes generadas</small></div>
        </article>
        <article class="admin-suppliers__metric admin-suppliers__metric--pending">
            <span class="admin-suppliers__metric-icon"><i class="fas fa-clock"></i></span>
            <div><span>Pendientes</span><strong><?php echo (int) ($stats['pedidos_pendientes'] ?: 0); ?></strong><small>Esperan recepción</small></div>
        </article>
        <article class="admin-suppliers__metric admin-suppliers__metric--received">
            <span class="admin-suppliers__metric-icon"><i class="fas fa-circle-check"></i></span>
            <div><span>Recibidos</span><strong><?php echo (int) ($stats['pedidos_recibidos'] ?: 0); ?></strong><small>Compras completadas</small></div>
        </article>
    </div>

    <ul class="nav admin-suppliers__tabs" id="proveedorPedidoTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="proveedores-tab" data-bs-toggle="tab" data-bs-target="#proveedores-section" type="button">
                <i class="fas fa-truck"></i> Proveedores
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pedidos-tab" data-bs-toggle="tab" data-bs-target="#pedidos-section" type="button">
                <i class="fas fa-cart-shopping"></i> Pedidos
            </button>
        </li>
    </ul>

    <div class="tab-content admin-suppliers__content" id="proveedorPedidoTabsContent">
        <div class="tab-pane fade show active" id="proveedores-section">
            <section class="admin-suppliers__panel">
                <div class="admin-suppliers__panel-heading">
                    <div><span class="admin-section-eyebrow">Red comercial</span><h5>Proveedores registrados</h5></div>
                    <div class="admin-suppliers__panel-actions">
                        <span class="admin-suppliers__count"><?php echo count($proveedores); ?> resultados</span>
                        <button class="admin-suppliers__primary-action" type="button" data-bs-toggle="modal" data-bs-target="#modalCrearProveedor">
                            <i class="fas fa-plus"></i> Nuevo proveedor
                        </button>
                    </div>
                </div>

                <?php if ($proveedores !== []): ?>
                    <div class="admin-suppliers__notice">
                        <span><i class="fas fa-shield-halved"></i></span>
                        <p><strong>Eliminación protegida</strong> Los proveedores con pedidos asociados se conservan para mantener la trazabilidad.</p>
                    </div>
                    <div class="table-responsive admin-suppliers__table-wrap">
                        <table class="table align-middle admin-suppliers__table" data-app-table-owner="admin-suppliers-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Proveedor</th>
                                    <th>Teléfono</th>
                                    <th>Email</th>
                                    <th>Dirección</th>
                                    <th>Pedidos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($proveedores as $proveedor): ?>
                                    <?php
                                    $pedidos_count = (int) $proveedor['pedidos_count'];
                                    ?>
                                    <tr>
                                        <td><strong>#<?php echo (int) $proveedor['id_proveedor']; ?></strong></td>
                                        <td><span class="admin-supplier-name"><i class="fas fa-building"></i><?php echo htmlspecialchars($proveedor['Nombre']); ?></span></td>
                                        <td><?php echo htmlspecialchars($proveedor['telefono'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($proveedor['email'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($proveedor['direccion'] ?: 'N/A'); ?></td>
                                        <td>
                                            <span class="admin-supplier-orders <?php echo $pedidos_count > 0 ? 'admin-supplier-orders--active' : ''; ?>">
                                                <i class="fas <?php echo $pedidos_count > 0 ? 'fa-cart-shopping' : 'fa-circle'; ?>"></i>
                                                <?php echo $pedidos_count; ?> pedidos
                                            </span>
                                        </td>
                                        <td>
                                            <div class="admin-suppliers__actions">
                                                <button class="admin-suppliers__action admin-suppliers__action--edit"
                                                        type="button"
                                                        onclick="editarProveedor(<?php echo $proveedor['id_proveedor']; ?>, '<?php echo htmlspecialchars($proveedor['Nombre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($proveedor['telefono'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($proveedor['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($proveedor['direccion'], ENT_QUOTES); ?>')"
                                                        title="Editar proveedor">
                                                    <i class="fas fa-pen-to-square"></i>
                                                </button>
                                                <button class="admin-suppliers__action admin-suppliers__action--delete"
                                                        type="button"
                                                        data-delete-provider
                                                        data-provider-id="<?php echo (int) $proveedor['id_proveedor']; ?>"
                                                        data-provider-name="<?php echo htmlspecialchars($proveedor['Nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-provider-email="<?php echo htmlspecialchars($proveedor['email'] ?: 'Sin correo registrado', ENT_QUOTES, 'UTF-8'); ?>"
                                                        <?php echo $pedidos_count > 0 ? 'disabled title="No se puede eliminar: tiene pedidos asociados"' : 'title="Eliminar proveedor"'; ?>>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="admin-suppliers__empty">
                        <span><i class="fas fa-truck"></i></span>
                        <h5>No hay proveedores registrados</h5>
                        <p>Agrega proveedores para crear pedidos y mantener la red de abastecimiento organizada.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <div class="tab-pane fade" id="pedidos-section">
            <section class="admin-suppliers__panel">
                <div class="admin-suppliers__panel-heading">
                    <div><span class="admin-section-eyebrow">Órdenes de compra</span><h5>Pedidos a proveedores</h5></div>
                    <div class="admin-suppliers__panel-actions">
                        <span class="admin-suppliers__count"><?php echo count($pedidos); ?> resultados</span>
                        <button class="admin-suppliers__primary-action admin-suppliers__primary-action--success" type="button" data-bs-toggle="modal" data-bs-target="#modalCrearPedido">
                            <i class="fas fa-plus"></i> Nuevo pedido
                        </button>
                    </div>
                </div>

                <?php if ($pedidos !== []): ?>
                    <div class="table-responsive admin-suppliers__table-wrap">
                        <table class="table align-middle admin-suppliers__table" data-app-table-owner="admin-orders-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Proveedor</th>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Usuario</th>
                                    <th>Estado</th>
                                    <th>Observación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pedidos as $pedido): ?>
                                    <tr>
                                        <td><strong>#<?php echo (int) $pedido['id_pedidos']; ?></strong></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?></td>
                                        <td>
                                            <span class="admin-supplier-name admin-supplier-name--compact">
                                                <i class="fas fa-truck"></i><?php echo htmlspecialchars($pedido['proveedor_nombre'] ?: 'N/A'); ?>
                                            </span>
                                            <?php if ($pedido['proveedor_telefono']): ?>
                                                <small class="admin-supplier-muted"><?php echo htmlspecialchars($pedido['proveedor_telefono']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($pedido['nombre_producto']); ?></td>
                                        <td>
                                            <span class="admin-order-quantity">
                                                <?php echo htmlspecialchars($pedido['cantidad']); ?> <?php echo htmlspecialchars($pedido['unidad_medida']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($pedido['usuario_nombre'] ?: 'N/A'); ?></td>
                                        <td>
                                            <span class="admin-order-status admin-order-status--<?php echo strtolower($pedido['estado']); ?>">
                                                <i></i><?php echo htmlspecialchars($pedido['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($pedido['observaciones'] ?: 'Sin observación'); ?></td>
                                        <td>
                                            <?php if ($pedido['estado'] === 'Pendiente'): ?>
                                                <div class="admin-suppliers__actions admin-suppliers__actions--wide">
                                                    <button
                                                            type="button"
                                                            class="admin-suppliers__action admin-suppliers__action--edit"
                                                            data-edit-order
                                                            data-order-id="<?php echo (int) $pedido['id_pedidos']; ?>"
                                                            data-provider-id="<?php echo (int) $pedido['id_proveedor']; ?>"
                                                            data-user-id="<?php echo htmlspecialchars($pedido['id_usuario'], ENT_QUOTES); ?>"
                                                            data-item-id="<?php echo (int) $pedido['id_insumo']; ?>"
                                                            data-quantity="<?php echo htmlspecialchars($pedido['cantidad'], ENT_QUOTES); ?>"
                                                            data-observations="<?php echo htmlspecialchars($pedido['observaciones'] ?? '', ENT_QUOTES); ?>"
                                                            title="Editar pedido">
                                                        <i class="fas fa-pen"></i>
                                                    </button>
                                                    <button class="admin-suppliers__action admin-suppliers__action--delete"
                                                            type="button"
                                                            onclick="cancelarPedido(<?php echo (int) $pedido['id_pedidos']; ?>)"
                                                            title="Cancelar pedido">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="admin-supplier-muted">Sin acciones</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="admin-suppliers__empty">
                        <span><i class="fas fa-cart-shopping"></i></span>
                        <h5>No hay pedidos registrados</h5>
                        <p>Los pedidos creados para proveedores aparecerán aquí con su estado de recepción.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</section>

<!-- Modal Crear Proveedor -->
<div class="modal fade admin-premium-modal admin-purchase-modal" id="modalCrearProveedor" tabindex="-1" aria-labelledby="modalCrearProveedorTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formCrearProveedor">
                <input type="hidden" name="_token" value="<?php echo e($csrfToken); ?>">
                <div class="modal-header">
                    <span class="admin-premium-modal__icon admin-purchase-modal__icon">
                        <i class="fas fa-building-circle-check"></i>
                    </span>
                    <div class="admin-premium-modal__heading">
                        <span class="farmer-kicker">Red de abastecimiento</span>
                        <h5 class="modal-title" id="modalCrearProveedorTitle">Crear proveedor</h5>
                        <p>Registra un aliado comercial para tus próximos pedidos.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="admin-purchase-modal__notice">
                        <span><i class="fas fa-shield-halved"></i></span>
                        <div>
                            <strong>Información comercial verificada</strong>
                            <p>Los campos marcados son necesarios para identificar y contactar al proveedor.</p>
                        </div>
                    </div>
                    <div class="admin-purchase-modal__grid">
                        <div class="admin-purchase-field">
                            <label for="crear_nombre">Nombre comercial <b>*</b></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-building"></i>
                                <input type="text" class="form-control" id="crear_nombre" name="nombre" placeholder="Ej. Agroinsumos del Pacífico" autocomplete="organization" required>
                            </div>
                        </div>
                        <div class="admin-purchase-field">
                            <label for="crear_ruc">RUC / Cédula <b>*</b></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-id-card"></i>
                                <input type="text" class="form-control" id="crear_ruc" name="ruc_cedula" placeholder="Número de identificación" inputmode="numeric" required>
                            </div>
                        </div>
                        <div class="admin-purchase-field">
                            <label for="crear_telefono">Teléfono <b>*</b></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-phone"></i>
                                <input type="tel" class="form-control" id="crear_telefono" name="telefono" placeholder="Ej. 099 123 4567" autocomplete="tel" required>
                            </div>
                        </div>
                        <div class="admin-purchase-field">
                            <label for="crear_email">Correo electrónico <b>*</b></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-envelope"></i>
                                <input type="email" class="form-control" id="crear_email" name="email" placeholder="ventas@proveedor.com" autocomplete="email" required>
                            </div>
                        </div>
                        <div class="admin-purchase-field admin-purchase-field--wide">
                            <label for="crear_direccion">Dirección <small>Opcional</small></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-location-dot"></i>
                                <input type="text" class="form-control" id="crear_direccion" name="direccion" placeholder="Ciudad, sector y referencia" autocomplete="street-address">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <span class="admin-premium-modal__security">
                        <i class="fas fa-lock"></i> Datos protegidos
                    </span>
                    <button type="button" class="btn admin-purchase-modal__cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary admin-purchase-modal__submit">
                        <i class="fas fa-plus"></i> Guardar proveedor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Proveedor -->
<div class="modal fade admin-premium-modal admin-purchase-modal admin-supplier-edit-modal" id="modalEditarProveedor" tabindex="-1" aria-labelledby="modalEditarProveedorTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formEditarProveedor">
                <input type="hidden" name="_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" name="id_proveedor" id="edit_proveedor_id">
                <div class="modal-header">
                    <span class="admin-premium-modal__icon admin-purchase-modal__icon">
                        <i class="fas fa-building-pen"></i>
                    </span>
                    <div class="admin-premium-modal__heading">
                        <span class="farmer-kicker">Datos comerciales</span>
                        <h5 class="modal-title" id="modalEditarProveedorTitle">Editar proveedor</h5>
                        <p>Actualiza la información de contacto sin perder el historial de pedidos.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="admin-purchase-modal__notice admin-supplier-edit-modal__notice">
                        <span><i class="fas fa-rotate"></i></span>
                        <div>
                            <strong>Edición segura</strong>
                            <p>Los pedidos asociados conservarán su trazabilidad y permanecerán vinculados.</p>
                        </div>
                    </div>
                    <div class="admin-purchase-modal__grid">
                        <div class="admin-purchase-field admin-purchase-field--wide">
                            <label for="edit_proveedor_nombre">Nombre comercial <b>*</b></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-building"></i>
                                <input type="text" class="form-control" name="nombre" id="edit_proveedor_nombre" autocomplete="organization" required>
                            </div>
                        </div>
                        <div class="admin-purchase-field">
                            <label for="edit_proveedor_telefono">Teléfono <small>Opcional</small></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-phone"></i>
                                <input type="tel" class="form-control" name="telefono" id="edit_proveedor_telefono" placeholder="Ej. 099 123 4567" autocomplete="tel">
                            </div>
                        </div>
                        <div class="admin-purchase-field">
                            <label for="edit_proveedor_email">Correo electrónico <small>Opcional</small></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-envelope"></i>
                                <input type="email" class="form-control" name="email" id="edit_proveedor_email" placeholder="ventas@proveedor.com" autocomplete="email">
                            </div>
                        </div>
                        <div class="admin-purchase-field admin-purchase-field--wide">
                            <label for="edit_proveedor_direccion">Dirección <small>Opcional</small></label>
                            <div class="admin-purchase-field__control admin-purchase-field__control--textarea">
                                <i class="fas fa-location-dot"></i>
                                <textarea class="form-control" name="direccion" id="edit_proveedor_direccion" rows="2" maxlength="2000" placeholder="Ciudad, sector y referencia" autocomplete="street-address"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <span class="admin-premium-modal__security"><i class="fas fa-shield-halved"></i> Historial protegido</span>
                    <button type="button" class="btn admin-purchase-modal__cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary admin-purchase-modal__submit" data-skip-loading="1">
                        <i class="fas fa-floppy-disk"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Eliminar Proveedor -->
<div class="modal fade admin-premium-modal admin-supplier-delete-modal" id="modalEliminarProveedor" tabindex="-1" aria-labelledby="modalEliminarProveedorTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formEliminarProveedor">
                <input type="hidden" name="_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" name="id_proveedor" id="delete_proveedor_id">
                <div class="modal-header">
                    <span class="admin-supplier-delete-modal__icon"><i class="fas fa-trash-can"></i></span>
                    <div class="admin-premium-modal__heading">
                        <span class="farmer-kicker">Acción irreversible</span>
                        <h5 class="modal-title" id="modalEliminarProveedorTitle">Eliminar proveedor</h5>
                        <p>Confirma que deseas retirar este proveedor de la red comercial.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="admin-supplier-delete-modal__warning">
                        <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                        <p><strong>Esta acción no se puede deshacer.</strong> Solo se permite eliminar proveedores que no tengan pedidos asociados.</p>
                    </div>
                    <div class="admin-supplier-delete-modal__record" aria-label="Proveedor seleccionado">
                        <span><i class="fas fa-building"></i></span>
                        <div>
                            <small>Proveedor seleccionado</small>
                            <strong id="delete_proveedor_nombre">—</strong>
                            <p><b id="delete_proveedor_codigo">#—</b><span aria-hidden="true">·</span><span id="delete_proveedor_email">Sin correo registrado</span></p>
                        </div>
                    </div>
                    <p class="admin-supplier-delete-modal__error" data-delete-provider-error role="alert" hidden></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn admin-supplier-delete-modal__cancel" data-bs-dismiss="modal">Conservar proveedor</button>
                    <button type="submit" class="btn admin-supplier-delete-modal__confirm" data-skip-loading="1">
                        <i class="fas fa-trash-can"></i><span>Eliminar proveedor</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Pedido -->
<div class="modal fade admin-form-modal" id="modalEditarPedido" tabindex="-1" aria-labelledby="modalEditarPedidoTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarPedidoTitle"><i class="fas fa-pen-to-square"></i> Editar pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formEditarPedido">
                <input type="hidden" name="_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" name="id_pedido" id="edit_pedido_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Proveedor *</label>
                        <select class="form-control" name="id_proveedor" id="edit_pedido_proveedor" required>
                            <option value="">Seleccionar proveedor</option>
                            <?php foreach ($proveedores as $prov): ?>
                            <option value="<?php echo $prov['id_proveedor']; ?>">
                                <?php echo htmlspecialchars($prov['Nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Usuario Responsable *</label>
                        <select class="form-control" name="id_usuario" id="edit_pedido_usuario" required>
                            <option value="">Seleccionar usuario</option>
                            <?php foreach ($usuarios as $user): ?>
                            <option value="<?php echo $user['id_usuario']; ?>">
                                <?php echo htmlspecialchars($user['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Producto *</label>
                        <select class="form-control" name="id_insumo" id="edit_pedido_insumo" data-purchase-select data-select-icon="fa-seedling" data-option-icon="fa-box" data-select-label="Seleccionar producto" data-product-filter-select required>
                            <option value="">Seleccionar producto</option>
                            <?php foreach ($insumos as $insumo): ?>
                                <?php $categoriaInsumo = $insumo['tipo'] ?: 'Sin categoría'; ?>
                                <option
                                    value="<?php echo (int) $insumo['id_insumos']; ?>"
                                    data-search="<?php echo htmlspecialchars($categoriaInsumo . ' ' . $insumo['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($categoriaInsumo); ?> ·
                                    <?php echo htmlspecialchars($insumo['nombre']); ?>
                                    (<?php echo htmlspecialchars($insumo['unidad_medida'] ?: 'sin unidad'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cantidad *</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" name="cantidad" id="edit_pedido_cantidad" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observación</label>
                        <textarea class="form-control" name="observaciones" id="edit_pedido_observaciones" rows="3" maxlength="1000"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-floppy-disk"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Crear Pedido -->
<div class="modal fade admin-premium-modal admin-purchase-modal admin-purchase-modal--order" id="modalCrearPedido" tabindex="-1" aria-labelledby="modalCrearPedidoTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formCrearPedido">
                <input type="hidden" name="_token" value="<?php echo e($csrfToken); ?>">
                <div class="modal-header">
                    <span class="admin-premium-modal__icon admin-purchase-modal__icon">
                        <i class="fas fa-cart-flatbed"></i>
                    </span>
                    <div class="admin-premium-modal__heading">
                        <span class="farmer-kicker">Abastecimiento inteligente</span>
                        <h5 class="modal-title" id="modalCrearPedidoTitle">Nuevo pedido</h5>
                        <p>Organiza la compra y deja lista la recepción de insumos.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="admin-purchase-modal__notice">
                        <span><i class="fas fa-boxes-stacked"></i></span>
                        <div>
                            <strong>Pedido con trazabilidad</strong>
                            <p>Se registrará como pendiente hasta que bodega confirme la recepción.</p>
                        </div>
                    </div>
                    <div class="admin-purchase-modal__grid">
                        <div class="admin-purchase-field">
                            <label for="crear_pedido_proveedor">Proveedor <b>*</b></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-truck-field"></i>
                                <select class="form-select" id="crear_pedido_proveedor" name="id_proveedor" data-purchase-select data-select-icon="fa-truck-field" data-option-icon="fa-building" data-select-label="Seleccionar proveedor" required>
                                    <option value="">Seleccionar proveedor</option>
                            <?php foreach ($proveedores as $prov): ?>
                            <option value="<?php echo $prov['id_proveedor']; ?>">
                                <?php echo htmlspecialchars($prov['Nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="admin-purchase-field">
                            <label for="crear_pedido_usuario">Responsable <b>*</b></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-user-check"></i>
                                <select class="form-select" id="crear_pedido_usuario" name="id_usuario" data-purchase-select data-select-icon="fa-user-check" data-option-icon="fa-user-tie" data-select-label="Seleccionar responsable" required>
                                    <option value="">Seleccionar responsable</option>
                            <?php foreach ($usuarios as $user): ?>
                            <option value="<?php echo $user['id_usuario']; ?>">
                                <?php echo htmlspecialchars($user['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="admin-purchase-field">
                            <label for="crear_pedido_producto">Producto <b>*</b></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-seedling"></i>
                                <select class="form-select" id="crear_pedido_producto" name="id_insumo" data-purchase-select data-select-icon="fa-seedling" data-option-icon="fa-box" data-select-label="Seleccionar producto" data-product-filter-select required>
                            <option value="">Seleccionar producto</option>
                            <?php foreach ($insumos as $insumo): ?>
                                <?php $categoriaInsumo = $insumo['tipo'] ?: 'Sin categoría'; ?>
                                <option
                                    value="<?php echo (int) $insumo['id_insumos']; ?>"
                                    data-search="<?php echo htmlspecialchars($categoriaInsumo . ' ' . $insumo['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($categoriaInsumo); ?> ·
                                    <?php echo htmlspecialchars($insumo['nombre']); ?>
                                    (<?php echo htmlspecialchars($insumo['unidad_medida'] ?: 'sin unidad'); ?>,
                                    stock: <?php echo htmlspecialchars($insumo['cantidad']); ?>)
                                </option>
                            <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="admin-new-product-card admin-purchase-field--wide">
                            <label class="admin-new-product-toggle">
                                <input type="checkbox" id="crear_producto_nuevo" name="crear_producto_nuevo" value="1" data-new-product-toggle>
                                <span class="admin-new-product-toggle__icon"><i class="fas fa-circle-plus"></i></span>
                                <span class="admin-new-product-toggle__text">
                                    <strong>Pedir producto nuevo</strong>
                                    <small>Úsalo cuando el insumo aún no exista en el catálogo.</small>
                                </span>
                            </label>
                            <div class="admin-new-product-fields" data-new-product-fields hidden>
                                <div class="admin-purchase-field">
                                    <label for="nuevo_insumo_nombre">Nombre del producto <b>*</b></label>
                                    <div class="admin-purchase-field__control">
                                        <i class="fas fa-tag"></i>
                                        <input type="text" class="form-control" id="nuevo_insumo_nombre" name="nuevo_insumo_nombre" maxlength="200" placeholder="Ej. Bioestimulante especial" data-new-product-required disabled>
                                    </div>
                                </div>
                                <div class="admin-purchase-field">
                                    <label for="nuevo_insumo_tipo">Categoría <b>*</b></label>
                                    <div class="admin-purchase-field__control">
                                        <i class="fas fa-layer-group"></i>
                                        <select class="form-select" id="nuevo_insumo_tipo" name="nuevo_insumo_tipo" data-purchase-select data-select-icon="fa-layer-group" data-option-icon="fa-box-open" data-select-label="Seleccionar categoría" data-new-product-required disabled>
                                            <option value="">Seleccionar categoría</option>
                                            <?php foreach ($tiposInsumoPermitidos as $tipoPermitido): ?>
                                                <option value="<?php echo htmlspecialchars($tipoPermitido); ?>"><?php echo htmlspecialchars($tipoPermitido); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="admin-purchase-field">
                                    <label for="nuevo_insumo_unidad">Unidad <b>*</b></label>
                                    <div class="admin-purchase-field__control">
                                        <i class="fas fa-ruler-combined"></i>
                                        <input type="text" class="form-control" id="nuevo_insumo_unidad" name="nuevo_insumo_unidad" maxlength="50" placeholder="kg, litros, unidades..." data-new-product-required disabled>
                                    </div>
                                </div>
                                <div class="admin-purchase-field admin-purchase-field--wide">
                                    <label for="nuevo_insumo_observaciones">Nota del producto <small>Opcional</small></label>
                                    <div class="admin-purchase-field__control admin-purchase-field__control--textarea">
                                        <i class="fas fa-note-sticky"></i>
                                        <textarea class="form-control" id="nuevo_insumo_observaciones" name="nuevo_insumo_observaciones" rows="2" maxlength="1000" placeholder="Detalles para identificarlo al recibirlo" disabled></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="admin-purchase-field">
                            <label for="crear_pedido_cantidad">Cantidad <b>*</b></label>
                            <div class="admin-purchase-field__control">
                                <i class="fas fa-arrow-up-9-1"></i>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="crear_pedido_cantidad" name="cantidad" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="admin-purchase-field admin-purchase-field--wide">
                            <label for="crear_pedido_observaciones">Observación <small>Opcional</small></label>
                            <div class="admin-purchase-field__control admin-purchase-field__control--textarea">
                                <i class="fas fa-clipboard-list"></i>
                                <textarea class="form-control" id="crear_pedido_observaciones" name="observaciones" rows="3" maxlength="1000" placeholder="Detalle, fecha esperada o instrucciones para la recepción"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <span class="admin-premium-modal__security">
                        <i class="fas fa-clock-rotate-left"></i> Registro auditable
                    </span>
                    <button type="button" class="btn admin-purchase-modal__cancel" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success admin-purchase-modal__submit">
                        <i class="fas fa-paper-plane"></i> Crear pedido
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
