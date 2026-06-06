/* admin.js - Unificado y namespaced
   Sobrescribe/replace el viejo js/admin.js con este archivo.
   Mantiene compatibilidad global (aliases) para las llamadas onclick en los PHP.
*/
(function (window, document) {
    'use strict';

    const alert = function (message) {
        const text = String(message || '');
        const type = /error|inválid|denegad|no se pudo|conexión|servidor/i.test(text)
            ? 'danger'
            : 'success';

        if (typeof window.appNotify === 'function') {
            window.appNotify(text, type, { persist: true });
            return;
        }

        window.alert(text);
    };

    const Admin = {
        contentCache: {},

        cleanupModalState: function () {
            const visibleModal = document.querySelector('.modal.show');
            if (visibleModal) return;

            document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        },

        closeModal: function (modalElement) {
            return new Promise(resolve => {
                if (!modalElement || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                    this.cleanupModalState();
                    resolve();
                    return;
                }

                const modal = bootstrap.Modal.getInstance(modalElement)
                    || bootstrap.Modal.getOrCreateInstance(modalElement);
                let finished = false;

                const finish = () => {
                    if (finished) return;
                    finished = true;
                    modalElement.removeEventListener('hidden.bs.modal', finish);
                    this.cleanupModalState();
                    resolve();
                };

                modalElement.addEventListener('hidden.bs.modal', finish, { once: true });
                modal.hide();
                window.setTimeout(finish, 450);
            });
        },

        refreshPedidosProveedores: function (modalElement) {
            return this.closeModal(modalElement)
                .then(() => this.loadPedidosProveedores())
                .finally(() => this.cleanupModalState());
        },

        // Carga genérica de HTML en un contenedor y devuelve una Promise
        loadContent: function (file, targetId, options = {}) {
            const useCache = options.useCache !== false;
            const target = document.getElementById(targetId);
            if (!target) return Promise.reject(new Error('Target element not found: ' + targetId));

            // Cache simple (5 minutos)
            if (useCache && this.contentCache[file] && (Date.now() - this.contentCache[file].timestamp < 300000)) {
                target.innerHTML = this.contentCache[file].content;
                // Asegurar setup de formularios
                this.setupDynamicForms(target);
                return Promise.resolve(this.contentCache[file].content);
            }

            target.innerHTML = '<div class="text-center mt-5"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Cargando...</p></div>';

            return fetch(file)
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    return response.text();
                })
                .then(html => {
                    target.innerHTML = html;
                    this.contentCache[file] = { content: html, timestamp: Date.now() };
                    // Después de inyectar el HTML configuramos listeners / formularios en ese contenedor
                    this.setupDynamicForms(target);
                    return html;
                })
                .catch(error => {
                    console.error(`Error cargando ${file}:`, error);
                    target.innerHTML = `
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle"></i> Error al cargar contenido</h5>
                            <p>No se pudo cargar ${file}</p>
                            <p><strong>Error:</strong> ${error.message}</p>
                            <button class="btn btn-outline-danger btn-sm" onclick="Admin.loadContent('${file}', '${targetId}')">
                                <i class="fas fa-redo"></i> Reintentar
                            </button>
                        </div>
                    `;
                    throw error;
                });
        },

        // Versión con cache pública (no necesario si usas loadContent directamente)
        loadContentWithCache: function (file, targetId) {
            return this.loadContent(file, targetId, { useCache: true });
        },

        // Conecta listeners/handlers para formularios y botones dinámicos dentro de un contenedor
        setupDynamicForms: function (container) {
            // container puede ser elemento o id string; si null => document
            let root = container;
            if (!root) root = document;
            if (typeof container === 'string') root = document.getElementById(container) || document;

            this.setupRequestConfirmation(root);
            this.setupInvoiceConfirmation(root);

            const invoiceFilters = root.querySelector('#purchaseInvoiceFilters');
            if (invoiceFilters && !invoiceFilters.dataset.hasListener) {
                invoiceFilters.dataset.hasListener = '1';
                invoiceFilters.addEventListener('submit', function (event) {
                    event.preventDefault();
                    const query = new URLSearchParams(new FormData(this)).toString();
                    Admin.loadContent(`admin_facturas.php?${query}`, 'facturas-content', { useCache: false });
                });
            }

            const clearInvoiceFilters = root.querySelector('[data-clear-invoice-filters]');
            if (clearInvoiceFilters && !clearInvoiceFilters.dataset.hasListener) {
                clearInvoiceFilters.dataset.hasListener = '1';
                clearInvoiceFilters.addEventListener('click', function () {
                    Admin.loadFacturas();
                });
            }

            // Manejo de formulario crear usuario (si existe)
            const formCrear = root.querySelector('#formCrearUsuario');
            if (formCrear && !formCrear.dataset.hasListener) {
                formCrear.dataset.hasListener = '1';
                formCrear.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn ? submitBtn.innerHTML : '';
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
                    }
                    const fd = new FormData(this);
                    fd.append('action', 'crear');

                    fetch('admin_usuarios.php', { method: 'POST', body: fd })
                        .then(r => r.text())
                        .then(text => {
                            try {
                                const data = JSON.parse(text);
                                if (data.success) {
                                    alert('Usuario creado exitosamente');
                                    this.reset();
                                    if (typeof bootstrap !== 'undefined') {
                                        const modalEl = document.getElementById('modalCrearUsuario');
                                        if (modalEl) {
                                            const m = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                                            m.hide();
                                        }
                                    }
                                    Admin.loadUsuarios();
                                } else {
                                    alert('Error: ' + (data.message || 'Error desconocido'));
                                }
                            } catch (err) {
                                console.error('Respuesta inválida crear usuario:', text, err);
                                alert('Respuesta inválida del servidor');
                            }
                        })
                        .catch(err => {
                            console.error('Error crear usuario:', err);
                            alert('Error de conexión');
                        })
                        .finally(() => {
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalText;
                            }
                        });
                });
            }

            // Manejo del formulario editar usuario
            const formEditar = root.querySelector('#formEditarUsuario');
            if (formEditar && !formEditar.dataset.hasListener) {
                formEditar.dataset.hasListener = '1';
                formEditar.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const fd = new FormData(this);
                    // No agregamos action aquí, asumimos que admin_usuarios.php lo detecta
                    fetch('admin_usuarios.php', { method: 'POST', body: fd })
                        .then(r => r.text())
                        .then(text => {
                            try {
                                const data = JSON.parse(text);
                                if (data.success) {
                                    alert('Usuario actualizado exitosamente');
                                    if (typeof bootstrap !== 'undefined') {
                                        const modalEl = document.getElementById('modalEditarUsuario');
                                        if (modalEl) {
                                            const m = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                                            m.hide();
                                        }
                                    }
                                    Admin.loadUsuarios();
                                } else {
                                    alert('Error: ' + (data.message || 'Error desconocido'));
                                }
                            } catch (err) {
                                console.error('Respuesta inválida editar usuario:', text, err);
                                alert('Respuesta inválida del servidor');
                            }
                        })
                        .catch(err => {
                            console.error('Error editar usuario:', err);
                            alert('Error de conexión');
                        });
                });
            }

            // Delegación para botones dinámicos que no tienen JS - ejemplo: ver detalles, etc. 
            // si se desea se puede añadir listeners para formularios de cultivos, lotes, facturas según convenga.
            // --- INICIO: Handlers para Proveedores y Pedidos ---
            // Crear proveedor
            const crearProv = root.querySelector('#formCrearProveedor');
            if (crearProv && !crearProv.dataset.hasListener) {
            crearProv.dataset.hasListener = '1';
            crearProv.addEventListener('submit', function (e) {
                e.preventDefault();
                const fd = new FormData(this);
                fd.append('action','crear_proveedor');
                fetch('admin_pedidos_proveedores.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Proveedor creado exitosamente');
                        Admin.refreshPedidosProveedores(
                            document.getElementById('modalCrearProveedor')
                        );
                    }
                    else alert('Error: ' + (data.message || 'Error desconocido'));
                }).catch(err => { console.error('Crear proveedor:', err); alert('Error de conexión'); });
            });
            }

            // Editar proveedor
            const editProv = root.querySelector('#formEditarProveedor');
            if (editProv && !editProv.dataset.hasListener) {
            editProv.dataset.hasListener = '1';
            editProv.addEventListener('submit', function (e) {
                e.preventDefault();
                const fd = new FormData(this);
                fd.append('action','editar_proveedor');
                fetch('admin_pedidos_proveedores.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Proveedor actualizado');
                        Admin.refreshPedidosProveedores(
                            document.getElementById('modalEditarProveedor')
                        );
                    }
                    else alert('Error: ' + (data.message || 'Error desconocido'));
                }).catch(err => { console.error('Editar proveedor:', err); alert('Error de conexión'); });
            });
            }

            // Crear pedido
            const crearPedido = root.querySelector('#formCrearPedido');
            if (crearPedido && !crearPedido.dataset.hasListener) {
            crearPedido.dataset.hasListener = '1';
            crearPedido.addEventListener('submit', function (e) {
                e.preventDefault();
                const fd = new FormData(this);
                fd.append('action','crear_pedido');
                fetch('admin_pedidos_proveedores.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Pedido creado exitosamente');
                        Admin.refreshPedidosProveedores(
                            document.getElementById('modalCrearPedido')
                        );
                    }
                    else alert('Error: ' + (data.message || 'Error desconocido'));
                }).catch(err => { console.error('Crear pedido:', err); alert('Error de conexión'); });
            });
            }

            const editarPedido = root.querySelector('#formEditarPedido');
            if (editarPedido && !editarPedido.dataset.hasListener) {
            editarPedido.dataset.hasListener = '1';
            editarPedido.addEventListener('submit', function (e) {
                e.preventDefault();
                const fd = new FormData(this);
                fd.append('action', 'editar_pedido');
                fetch('admin_pedidos_proveedores.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Pedido actualizado exitosamente');
                        Admin.refreshPedidosProveedores(
                            document.getElementById('modalEditarPedido')
                        );
                    }
                    else alert('Error: ' + (data.message || 'No se pudo actualizar el pedido'));
                }).catch(err => { console.error('Editar pedido:', err); alert('Error de conexión'); });
            });
            }

            root.querySelectorAll('[data-edit-order]').forEach((button) => {
                if (button.dataset.hasListener) return;
                button.dataset.hasListener = '1';
                button.addEventListener('click', function () {
                    const fields = {
                        edit_pedido_id: button.dataset.orderId,
                        edit_pedido_proveedor: button.dataset.providerId,
                        edit_pedido_usuario: button.dataset.userId,
                        edit_pedido_insumo: button.dataset.itemId,
                        edit_pedido_cantidad: button.dataset.quantity,
                        edit_pedido_observaciones: button.dataset.observations,
                    };

                    Object.entries(fields).forEach(([id, value]) => {
                        const field = document.getElementById(id);
                        if (field) field.value = value || '';
                    });

                    const modalElement = document.getElementById('modalEditarPedido');
                    if (modalElement && typeof bootstrap !== 'undefined') {
                        bootstrap.Modal.getOrCreateInstance(modalElement).show();
                    }
                });
            });

            // Exponer funciones globales que tu HTML usa con onclick(...)
            window.editarProveedor = function (id, nombre, telefono, email, direccion) {
            const idEl = document.getElementById('edit_proveedor_id');
            if (idEl) idEl.value = id;
            const n = document.getElementById('edit_proveedor_nombre'); if (n) n.value = nombre || '';
            const t = document.getElementById('edit_proveedor_telefono'); if (t) t.value = telefono || '';
            const e = document.getElementById('edit_proveedor_email'); if (e) e.value = email || '';
            const d = document.getElementById('edit_proveedor_direccion'); if (d) d.value = direccion || '';
            const modalEl = document.getElementById('modalEditarProveedor');
            if (modalEl && typeof bootstrap !== 'undefined') new bootstrap.Modal(modalEl).show();
            };

            window.eliminarProveedor = function (id) {
            if (!confirm('¿Seguro de eliminar este proveedor?')) return;
            const fd = new FormData(); fd.append('action','eliminar_proveedor'); fd.append('id_proveedor', id);
            fetch('admin_pedidos_proveedores.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                if (data.success) {
                    alert(data.message || 'Proveedor eliminado');
                    Admin.loadPedidosProveedores();
                }
                else alert('Error: ' + (data.message || 'Error desconocido'));
                }).catch(err => { console.error('Eliminar proveedor:', err); alert('Error de conexión'); });
            };

            window.cancelarPedido = function (id) {
            if (!confirm('¿Seguro de cancelar este pedido? Esta acción impedirá registrar su comprobante.')) return;
            const fd = new FormData(); fd.append('action','cancelar_pedido'); fd.append('id_pedido', id);
            fetch('admin_pedidos_proveedores.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                if (data.success) {
                    alert(data.message || 'Pedido cancelado');
                    Admin.loadPedidosProveedores();
                }
                else alert('Error: ' + (data.message || 'Error desconocido'));
                }).catch(err => { console.error('Cancelar pedido:', err); alert('Error de conexión'); });
            };
            // --- FIN: Handlers para Proveedores y Pedidos ---
        },

        // Pestañas: carga el contenido en la zona correspondiente
        loadUsuarios: function () { return this.loadContent('admin_usuarios.php', 'usuarios-content', { useCache: false }); },
        loadSolicitudes: function () { return this.loadContent('admin_solicitudes.php', 'solicitudes-content', { useCache: false }); },
        loadMovimientos: function () { return this.loadContent('admin_movimientos.php', 'movimientos-content', { useCache: false }); },
        loadFacturas: function () { return this.loadContent('admin_facturas.php', 'facturas-content', { useCache: false }); },
        loadReportes: function () { return this.loadContent('admin_reportes.php', 'reportes-content', { useCache: false }); },
        loadCultivos: function () { return this.loadContent('admin_cultivos.php', 'cultivos-content', { useCache: false }); },
        loadPedidosProveedores: function () { return this.loadContent('admin_pedidos_proveedores.php', 'pedidos-proveedores-content', { useCache: false }); },

        setupRequestConfirmation: function (root) {
            const modalElement = root.querySelector('#adminRequestConfirmModal');
            const form = root.querySelector('#adminRequestConfirmForm');

            if (!modalElement || !form || typeof bootstrap === 'undefined') return;

            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            const requestIdInput = form.querySelector('#adminRequestConfirmId');
            const actionInput = form.querySelector('#adminRequestConfirmAction');
            const title = modalElement.querySelector('#adminRequestConfirmTitle');
            const icon = modalElement.querySelector('[data-admin-request-modal-icon]');
            const message = modalElement.querySelector('[data-admin-request-modal-message]');
            const farmer = modalElement.querySelector('[data-admin-request-modal-farmer]');
            const product = modalElement.querySelector('[data-admin-request-modal-product]');
            const quantity = modalElement.querySelector('[data-admin-request-modal-quantity]');
            const confirmButton = modalElement.querySelector('[data-admin-request-modal-confirm]');
            const confirmLabel = confirmButton.querySelector('span');
            const confirmIcon = confirmButton.querySelector('i');

            root.querySelectorAll('[data-admin-request-action]').forEach(button => {
                if (button.dataset.adminRequestListener === '1') return;
                button.dataset.adminRequestListener = '1';

                button.addEventListener('click', function () {
                    const isApproval = button.dataset.adminRequestAction === 'aprobar';

                    requestIdInput.value = button.dataset.requestId || '';
                    actionInput.value = isApproval ? 'aprobar_solicitud' : 'rechazar_solicitud';
                    farmer.textContent = button.dataset.farmer || 'Sin agricultor';
                    product.textContent = button.dataset.product || 'Sin producto';
                    quantity.textContent = button.dataset.quantity || 'Sin cantidad';

                    title.textContent = isApproval ? 'Aprobar solicitud' : 'Rechazar solicitud';
                    message.textContent = isApproval
                        ? 'La solicitud quedará aprobada y estará disponible para que bodega la procese.'
                        : 'La solicitud quedará rechazada y no podrá ser procesada por bodega.';
                    confirmLabel.textContent = isApproval ? 'Confirmar aprobación' : 'Confirmar rechazo';
                    confirmIcon.className = isApproval ? 'fas fa-check' : 'fas fa-xmark';
                    icon.innerHTML = isApproval ? '<i class="fas fa-clipboard-check"></i>' : '<i class="fas fa-ban"></i>';
                    modalElement.classList.toggle('is-cancel', !isApproval);

                    modal.show();
                });
            });

            if (form.dataset.adminRequestListener === '1') return;
            form.dataset.adminRequestListener = '1';

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const action = actionInput.value;
                const requestId = requestIdInput.value;
                const originalContent = confirmButton.innerHTML;
                const fd = new FormData();
                fd.append('action', action);
                fd.append('id_solicitud', requestId);

                confirmButton.disabled = true;
                confirmButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span> Procesando...</span>';

                fetch('admin_solicitudes.php', { method: 'POST', body: fd })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            alert(data.message || 'No se pudo procesar la solicitud.');
                            return;
                        }

                        alert(data.message || 'Solicitud procesada correctamente.');
                        modalElement.addEventListener('hidden.bs.modal', function () {
                            Admin.loadSolicitudes();
                        }, { once: true });
                        modal.hide();
                    })
                    .catch(error => {
                        console.error('Error procesando solicitud:', error);
                        alert('Error de conexión');
                    })
                    .finally(() => {
                        confirmButton.disabled = false;
                        confirmButton.innerHTML = originalContent;
                    });
            });
        },

        setupInvoiceConfirmation: function (root) {
            const modalElement = root.querySelector('#adminInvoiceConfirmModal');
            const form = root.querySelector('#adminInvoiceConfirmForm');

            if (!modalElement || !form || typeof bootstrap === 'undefined') return;

            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            const invoiceIdInput = form.querySelector('#adminInvoiceConfirmId');
            const actionInput = form.querySelector('#adminInvoiceConfirmAction');
            const title = modalElement.querySelector('#adminInvoiceConfirmTitle');
            const icon = modalElement.querySelector('[data-admin-invoice-modal-icon]');
            const message = modalElement.querySelector('[data-admin-invoice-modal-message]');
            const number = modalElement.querySelector('[data-admin-invoice-modal-number]');
            const provider = modalElement.querySelector('[data-admin-invoice-modal-provider]');
            const total = modalElement.querySelector('[data-admin-invoice-modal-total]');
            const confirmButton = modalElement.querySelector('[data-admin-invoice-modal-confirm]');
            const confirmLabel = confirmButton.querySelector('span');
            const confirmIcon = confirmButton.querySelector('i');

            root.querySelectorAll('[data-admin-invoice-action]').forEach(button => {
                if (button.dataset.adminInvoiceListener === '1') return;
                button.dataset.adminInvoiceListener = '1';

                button.addEventListener('click', function () {
                    const isApproval = button.dataset.adminInvoiceAction === 'aprobar_factura';

                    invoiceIdInput.value = button.dataset.invoiceId || '';
                    actionInput.value = button.dataset.adminInvoiceAction || '';
                    number.textContent = button.dataset.invoiceNumber || 'Sin número';
                    provider.textContent = button.dataset.invoiceProvider || 'Sin proveedor';
                    total.textContent = button.dataset.invoiceTotal || '$0.00';

                    title.textContent = isApproval ? 'Aprobar factura' : 'Rechazar factura';
                    message.textContent = isApproval
                        ? 'La factura quedará aprobada administrativamente.'
                        : 'La factura quedará rechazada. El stock físico recibido no será revertido.';
                    confirmLabel.textContent = isApproval ? 'Confirmar aprobación' : 'Confirmar rechazo';
                    confirmIcon.className = isApproval ? 'fas fa-check' : 'fas fa-xmark';
                    icon.innerHTML = isApproval
                        ? '<i class="fas fa-file-circle-check"></i>'
                        : '<i class="fas fa-file-circle-xmark"></i>';
                    modalElement.classList.toggle('is-cancel', !isApproval);

                    modal.show();
                });
            });

            if (form.dataset.adminInvoiceListener === '1') return;
            form.dataset.adminInvoiceListener = '1';

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const fd = new FormData();
                fd.append('action', actionInput.value);
                fd.append('id_factura_compra', invoiceIdInput.value);
                const originalContent = confirmButton.innerHTML;

                confirmButton.disabled = true;
                confirmButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span> Procesando...</span>';

                fetch('admin_facturas.php', { method: 'POST', body: fd })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            alert(data.message || 'No se pudo revisar la factura.');
                            return;
                        }

                        alert(data.message || 'Factura revisada correctamente.');
                        modalElement.addEventListener('hidden.bs.modal', function () {
                            Admin.loadFacturas();
                        }, { once: true });
                        modal.hide();
                    })
                    .catch(error => {
                        console.error('Error revisando factura:', error);
                        alert('Error de conexión');
                    })
                    .finally(() => {
                        confirmButton.disabled = false;
                        confirmButton.innerHTML = originalContent;
                    });
            });
        },

        // Operaciones que los PHP esperan: aprobar/rechazar solicitud
        aprobarSolicitud: function (id) {
            const button = document.querySelector(
                `[data-admin-request-action="aprobar"][data-request-id="${id}"]`
            );
            if (button) button.click();
        },

        rechazarSolicitud: function (id) {
            const button = document.querySelector(
                `[data-admin-request-action="rechazar"][data-request-id="${id}"]`
            );
            if (button) button.click();
        },

        // Mostrar detalles (cultivo, lote, factura) en modal
        verDetallesCultivo: function (id) {
            fetch(`cultivo_detalle.php?id=${id}`)
                .then(r => r.text())
                .then(html => {
                    const container = document.getElementById('detallesCultivoContent');
                    if (container) container.innerHTML = html;
                    if (typeof bootstrap !== 'undefined') {
                        const modalEl = document.getElementById('modalDetallesCultivo');
                        if (modalEl) new bootstrap.Modal(modalEl).show();
                    }
                }).catch(err => { console.error('Error cultivo detalle:', err); alert('Error al cargar los detalles del cultivo'); });
        },

        eliminarCultivo: function (id) {
            if (!confirm('¿Está seguro de eliminar este cultivo? ADVERTENCIA: Si tiene lotes asociados, se eliminarán también.')) return;
            const fd = new FormData();
            fd.append('action', 'eliminar_cultivo');
            fd.append('id_cultivo', id);
            fetch('admin_cultivos.php', { method: 'POST', body: fd })
                .then(r => r.text())
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            alert('Cultivo eliminado exitosamente');
                            Admin.loadCultivos();
                        } else {
                            alert('Error: ' + (data.message || 'Error desconocido'));
                        }
                    } catch (err) {
                        console.error('Eliminar cultivo - respuesta inválida:', text, err);
                        alert('Error en el servidor');
                    }
                })
                .catch(err => {
                    console.error('Error eliminar cultivo:', err);
                    alert('Error de conexión');
                });
        },

        verDetalleLote: function (id) {
            fetch(`lote_detalle.php?id=${id}`)
                .then(r => r.text())
                .then(html => {
                    const container = document.getElementById('detalleLoteContent');
                    if (container) container.innerHTML = html;
                    if (typeof bootstrap !== 'undefined') {
                        const modalEl = document.getElementById('modalDetalleLote');
                        if (modalEl) new bootstrap.Modal(modalEl).show();
                    }
                }).catch(err => { console.error('Error lote detalle:', err); alert('Error al cargar los detalles del lote'); });
        },

        eliminarLote: function (id) {
            if (!confirm('¿Está seguro de eliminar este lote?')) return;
            const fd = new FormData();
            fd.append('action', 'eliminar_lote');
            fd.append('id_lote', id);
            fetch('admin_cultivos.php', { method: 'POST', body: fd })
                .then(r => r.text())
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            alert('Lote eliminado exitosamente');
                            Admin.loadCultivos();
                        } else {
                            alert('Error: ' + (data.message || 'Error desconocido'));
                        }
                    } catch (err) {
                        console.error('Eliminar lote - respuesta inválida:', text, err);
                        alert('Error en el servidor');
                    }
                })
                .catch(err => {
                    console.error('Error eliminar lote:', err);
                    alert('Error de conexión');
                });
        },

        verDetallesFactura: function (id) {
            fetch(`factura_detalle.php?id=${id}`)
                .then(r => r.text())
                .then(html => {
                    const container = document.getElementById('detallesFacturaContent');
                    if (container) container.innerHTML = html;
                    if (typeof bootstrap !== 'undefined') {
                        const modalEl = document.getElementById('modalDetallesFactura');
                        if (modalEl) new bootstrap.Modal(modalEl).show();
                    }
                }).catch(err => { console.error('Error ver detalles factura:', err); alert('Error al cargar los detalles'); });
        },

        // Init: engancha listeners de pestañas (evita duplicar listeners)
        init: function () {
            document.querySelectorAll('#adminTabsContent > .tab-pane').forEach(pane => {
                if (pane.dataset.adminListener === '1') return;
                pane.dataset.adminListener = '1';
                pane.addEventListener('shown.bs.tab', function (e) {
                    if (e.target !== this) return;

                    const targetId = '#' + this.id;
                    switch (targetId) {
                        case '#usuarios':
                            Admin.loadUsuarios().then(() => {
                                Admin.setupDynamicForms('usuarios-content');
                            });
                            break;
                        case '#solicitudes': Admin.loadSolicitudes(); break;
                        case '#movimientos': Admin.loadMovimientos(); break;
                        case '#facturas': Admin.loadFacturas(); break;
                        case '#reportes': Admin.loadReportes(); break;
                        case '#cultivos': Admin.loadCultivos(); break;
                        case '#pedidos-proveedores': Admin.loadPedidosProveedores(); break;
                        default: break;
                    }
                });
            });

            // La navegación entre pestañas (data-app-tab) la maneja exclusivamente app-ui.js
            /*
            document.querySelectorAll('[data-app-tab]').forEach(trigger => {
                if (trigger.dataset.adminNavListener === '1') return;
                trigger.dataset.adminNavListener = '1';
                trigger.addEventListener('click', function () {
                    const target = this.getAttribute('data-app-tab');
                    const tabButton = document.querySelector(`[data-bs-target="${target}"]`);
                    if (tabButton && typeof bootstrap !== 'undefined') {
                        bootstrap.Tab.getOrCreateInstance(tabButton).show();
                    }
                    document.querySelectorAll('.app-sidebar-link').forEach(link => link.classList.remove('active'));
                    this.classList.add('active');
                    document.body.classList.remove('app-mobile-nav-open');
                });
            });
            */

            // El toggle del sidebar y el overlay móvil los maneja exclusivamente app-ui.js para evitar conflictos de clicks dobles.
            /*
            const sidebarToggle = document.querySelector('[data-app-sidebar-toggle]');
            if (sidebarToggle && sidebarToggle.dataset.adminSidebarListener !== '1') {
                sidebarToggle.dataset.adminSidebarListener = '1';
                sidebarToggle.addEventListener('click', function () {
                    if (window.matchMedia('(max-width: 991px)').matches) {
                        document.body.classList.toggle('app-mobile-nav-open');
                    } else {
                        document.body.classList.toggle('app-sidebar-collapsed');
                    }
                });
            }
            */

            document.querySelectorAll('[data-app-mobile-close]').forEach(closeTarget => {
                if (closeTarget.dataset.adminCloseListener === '1') return;
                closeTarget.dataset.adminCloseListener = '1';
                closeTarget.addEventListener('click', () => document.body.classList.remove('app-mobile-nav-open'));
            });

            console.log('Admin module inicializado');
        }
    };

    // Exponer Admin globalmente y mantener aliases globales que los PHP esperan
    window.Admin = Admin;
    // Aliases compatibles (no tocar los PHP)
    window.loadUsuarios = Admin.loadUsuarios.bind(Admin);
    window.loadSolicitudes = Admin.loadSolicitudes.bind(Admin);
    window.loadMovimientos = Admin.loadMovimientos.bind(Admin);
    window.loadFacturas = Admin.loadFacturas.bind(Admin);
    window.loadReportes = Admin.loadReportes.bind(Admin);
    window.loadCultivos = Admin.loadCultivos.bind(Admin);
    window.loadPedidosProveedores = Admin.loadPedidosProveedores.bind(Admin);


    window.editarUsuario = function (id, nombre, email, rol) {
        // Llenado de modal de editar usuario: buscar campos por id y mostrar modal
        setTimeout(() => { // leve espera para asegurar que el modal exista si fue cargado dinámicamente
            const editId = document.getElementById('edit_id');
            const editIdDisplay = document.getElementById('edit_id_display');
            const editNombre = document.getElementById('edit_nombre');
            const editEmail = document.getElementById('edit_email');
            const editRol = document.getElementById('edit_rol');
            const editContrasena = document.getElementById('edit_contrasena');
            if (editId) editId.value = id;
            if (editIdDisplay) editIdDisplay.value = id;
            if (editNombre) editNombre.value = nombre || '';
            if (editEmail) editEmail.value = email || '';
            if (editRol) editRol.value = rol || '';
            if (editContrasena) editContrasena.value = '';
            const modal = document.getElementById('modalEditarUsuario');
            if (modal && typeof bootstrap !== 'undefined') {
                new bootstrap.Modal(modal).show();
            }
        }, 50);
    };

    window.eliminarUsuario = function (id) {
        if (!confirm('¿Desea eliminar este usuario?')) return;
        const fd = new FormData();
        fd.append('action', 'eliminar');
        fd.append('id_usuario', id);
        fetch('admin_usuarios.php', { method: 'POST', body: fd })
            .then(r => r.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        alert('Usuario eliminado exitosamente');
                        Admin.loadUsuarios();
                    } else {
                        alert('Error: ' + (data.message || 'Error desconocido'));
                    }
                } catch (err) {
                    console.error('Eliminar usuario - respuesta inválida:', text, err);
                    alert('Error en el servidor');
                }
            })
            .catch(err => {
                console.error('Error eliminar usuario:', err);
                alert('Error de conexión');
            });
    };

    // Aliases para solicitudes/facturas/cultivos usados por los PHP
    window.aprobarSolicitud = Admin.aprobarSolicitud.bind(Admin);
    window.rechazarSolicitud = Admin.rechazarSolicitud.bind(Admin);
    window.revisarFacturaCompra = function (id, action) {
        const button = document.querySelector(
            `[data-admin-invoice-action="${action}"][data-invoice-id="${id}"]`
        );
        if (button) button.click();
    };
    window.verDetallesCultivo = Admin.verDetallesCultivo.bind(Admin);
    window.eliminarCultivo = Admin.eliminarCultivo.bind(Admin);
    window.verDetalleLote = Admin.verDetalleLote.bind(Admin);
    window.eliminarLote = Admin.eliminarLote.bind(Admin);
    window.verDetallesFactura = Admin.verDetallesFactura.bind(Admin);

    // Inicializamos cuando DOM esté listo
    document.addEventListener('DOMContentLoaded', function () {
        Admin.init();
    });
    
})(window, document);
