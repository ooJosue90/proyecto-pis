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
        formModulePromise: null,

        ensureFormModule: function () {
            if (window.AdminFormMethods) {
                Object.assign(this, window.AdminFormMethods);
                return Promise.resolve();
            }

            if (this.formModulePromise) return this.formModulePromise;

            this.formModulePromise = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = `js/admin-forms.js?v=${Date.now()}`;
                script.onload = () => {
                    Object.assign(this, window.AdminFormMethods || {});
                    resolve();
                };
                script.onerror = () => reject(new Error('No se pudo cargar el módulo de formularios.'));
                document.head.appendChild(script);
            });

            return this.formModulePromise;
        },

        cleanupModalState: function () {
            const visibleModal = document.querySelector('.modal.show');
            if (visibleModal) return;

            document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
        },

        mountDynamicModal: function (modalElement, ownerId) {
            if (!modalElement) return null;

            if (ownerId) modalElement.dataset.adminModalOwner = ownerId;
            if (modalElement.parentElement !== document.body) {
                document.body.appendChild(modalElement);
            }

            return modalElement;
        },

        cleanupOwnedModals: function (ownerId) {
            if (!ownerId) return;

            document.querySelectorAll('.modal[data-admin-modal-owner]').forEach(modalElement => {
                if (modalElement.dataset.adminModalOwner !== ownerId || modalElement.classList.contains('show')) {
                    return;
                }

                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    bootstrap.Modal.getInstance(modalElement)?.dispose();
                }
                document.querySelectorAll(`[data-purchase-modal-id="${modalElement.id}"]`)
                    .forEach(menu => menu.remove());
                modalElement.remove();
            });

            this.cleanupModalState();
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

            this.cleanupOwnedModals(targetId);

            // Cache simple (5 minutos)
            if (useCache && this.contentCache[file] && (Date.now() - this.contentCache[file].timestamp < 300000)) {
                target.innerHTML = this.contentCache[file].content;
                return this.ensureFormModule().then(() => {
                    this.setupDynamicForms(target);
                    return this.contentCache[file].content;
                });
            }

            target.innerHTML = '<div class="text-center mt-5"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Cargando...</p></div>';

            const requestUrl = useCache ? file : `${file}${file.includes('?') ? '&' : '?'}_=${Date.now()}`;

            return fetch(requestUrl, { cache: useCache ? 'default' : 'no-store' })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    return response.text();
                })
                .then(html => {
                    target.innerHTML = html;
                    this.contentCache[file] = { content: html, timestamp: Date.now() };
                    return this.ensureFormModule().then(() => {
                        this.setupDynamicForms(target);
                        return html;
                    });
                })
                .catch(error => {
                    console.error(`Error cargando ${file}:`, error);
                    target.innerHTML = `
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-triangle-exclamation"></i> Error al cargar contenido</h5>
                            <p>No se pudo cargar ${file}</p>
                            <p><strong>Error:</strong> ${error.message}</p>
                            <button class="btn btn-outline-danger btn-sm" onclick="Admin.loadContent('${file}', '${targetId}')">
                                <i class="fas fa-rotate-right"></i> Reintentar
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

        // Pestañas: carga el contenido en la zona correspondiente
        loadUsuarios: function () { return this.loadContent('usuarios', 'usuarios-content', { useCache: false }); },
        loadSolicitudes: function () { return this.loadContent('solicitudes/admin', 'solicitudes-content', { useCache: false }); },
        loadMovimientos: function () { return this.loadContent('movimientos', 'movimientos-content', { useCache: false }); },
        loadFacturas: function () { return this.loadContent('facturas', 'facturas-content', { useCache: false }); },
        loadReportes: function () { return this.loadContent('reportes', 'reportes-content', { useCache: false }); },
        loadCultivos: function () { return this.loadContent('admin/agricultura', 'cultivos-content', { useCache: false }); },
        loadPedidosProveedores: function () { return this.loadContent('abastecimiento', 'pedidos-proveedores-content', { useCache: false }); },

        setupRequestConfirmation: function (root) {
            const modalElement = this.mountDynamicModal(
                root.querySelector('#adminRequestConfirmModal'),
                root.id
            );
            const form = root.querySelector('#adminRequestConfirmForm');

            const modalForm = form || modalElement?.querySelector('#adminRequestConfirmForm');
            if (!modalElement || !modalForm || typeof bootstrap === 'undefined') return;

            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            const requestIdInput = modalForm.querySelector('#adminRequestConfirmId');
            const actionInput = modalForm.querySelector('#adminRequestConfirmAction');
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

            if (modalForm.dataset.adminRequestListener === '1') return;
            modalForm.dataset.adminRequestListener = '1';

            modalForm.addEventListener('submit', function (event) {
                event.preventDefault();

                const action = actionInput.value;
                const requestId = requestIdInput.value;
                const originalContent = confirmButton.innerHTML;
                const fd = new FormData();
                fd.append('action', action);
                fd.append('id_solicitud', requestId);
                fd.append('_token', modalForm.querySelector('[name="_token"]')?.value || '');

                confirmButton.disabled = true;
                confirmButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span> Procesando...</span>';

                fetch(modalForm.dataset.reviewUrl || 'solicitudes/revisar', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            alert(data.message || 'No se pudo procesar la solicitud.');
                            return;
                        }

                        alert(data.message || 'Solicitud procesada correctamente.');
                        return Admin.closeModal(modalElement)
                            .then(() => Admin.loadSolicitudes());
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
            const modalElement = this.mountDynamicModal(
                root.querySelector('#adminInvoiceConfirmModal'),
                root.id
            );
            const form = root.querySelector('#adminInvoiceConfirmForm');

            const modalForm = form || modalElement?.querySelector('#adminInvoiceConfirmForm');
            if (!modalElement || !modalForm || typeof bootstrap === 'undefined') return;

            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            const invoiceIdInput = modalForm.querySelector('#adminInvoiceConfirmId');
            const actionInput = modalForm.querySelector('#adminInvoiceConfirmAction');
            const title = modalElement.querySelector('#adminInvoiceConfirmTitle');
            const icon = modalElement.querySelector('[data-admin-invoice-modal-icon]');
            const message = modalElement.querySelector('[data-admin-invoice-modal-message]');
            const messageIcon = modalElement.querySelector('[data-admin-invoice-message-icon]');
            const eyebrow = modalElement.querySelector('[data-admin-invoice-modal-eyebrow]');
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
                    if (eyebrow) eyebrow.textContent = isApproval ? 'Aprobación financiera' : 'Revisión con observaciones';
                    message.textContent = isApproval
                        ? 'La factura quedará aprobada y registrada como validada administrativamente.'
                        : 'La factura quedará rechazada. Esta decisión no revierte el stock físico que ya fue recibido.';
                    if (messageIcon) {
                        messageIcon.className = isApproval ? 'fas fa-circle-check' : 'fas fa-triangle-exclamation';
                    }
                    confirmLabel.textContent = isApproval ? 'Confirmar aprobación' : 'Confirmar rechazo';
                    confirmIcon.className = isApproval ? 'fas fa-check' : 'fas fa-xmark';
                    icon.innerHTML = isApproval
                        ? '<i class="fas fa-file-circle-check"></i>'
                        : '<i class="fas fa-file-circle-xmark"></i>';
                    modalElement.classList.toggle('is-cancel', !isApproval);

                    modal.show();
                });
            });

            if (modalForm.dataset.adminInvoiceListener === '1') return;
            modalForm.dataset.adminInvoiceListener = '1';

            modalForm.addEventListener('submit', function (event) {
                event.preventDefault();

                const fd = new FormData();
                fd.append('action', actionInput.value);
                fd.append('id_factura_compra', invoiceIdInput.value);
                fd.append('_token', root.querySelector('[data-facturas-csrf]')?.dataset.facturasCsrf || '');
                const originalContent = confirmButton.innerHTML;

                confirmButton.disabled = true;
                confirmButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span> Procesando...</span>';

                fetch('facturas/revisar', { method: 'POST', body: fd })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            alert(data.message || 'No se pudo revisar la factura.');
                            return;
                        }

                        alert(data.message || 'Factura revisada correctamente.');
                        return Admin.closeModal(modalElement)
                            .then(() => Admin.loadFacturas());
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

        setupCropDeletion: function (root) {
            const modalElement = this.mountDynamicModal(
                root.querySelector('#adminCropDeleteModal'),
                root.id
            );
            if (!modalElement || typeof bootstrap === 'undefined') return;

            const form = modalElement.querySelector('#adminCropDeleteForm');
            const typeInput = modalElement.querySelector('#adminCropDeleteType');
            const idInput = modalElement.querySelector('#adminCropDeleteId');
            const title = modalElement.querySelector('#adminCropDeleteTitle');
            const question = modalElement.querySelector('[data-admin-delete-question]');
            const label = modalElement.querySelector('[data-admin-delete-label]');
            const name = modalElement.querySelector('[data-admin-delete-name]');
            const confirmButton = modalElement.querySelector('[data-admin-delete-confirm]');
            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

            root.querySelectorAll('[data-admin-crop-delete]').forEach(button => {
                if (button.dataset.adminDeleteListener === '1') return;
                button.dataset.adminDeleteListener = '1';

                button.addEventListener('click', function () {
                    if (button.disabled) return;

                    const type = button.dataset.adminCropDelete;
                    const isCrop = type === 'cultivo';

                    typeInput.value = type;
                    idInput.value = button.dataset.recordId || '';
                    title.textContent = isCrop ? 'Eliminar cultivo' : 'Eliminar lote';
                    question.textContent = isCrop
                        ? '¿Confirma que desea eliminar este cultivo?'
                        : '¿Confirma que desea eliminar este lote?';
                    label.textContent = isCrop ? 'Cultivo seleccionado' : 'Lote seleccionado';
                    name.textContent = button.dataset.recordName || `Registro #${idInput.value}`;
                    modal.show();
                });
            });

            if (!form || form.dataset.adminDeleteListener === '1') return;
            form.dataset.adminDeleteListener = '1';

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const type = typeInput.value;
                const isCrop = type === 'cultivo';
                const originalContent = confirmButton.innerHTML;
                const fd = new FormData();
                fd.append('action', isCrop ? 'eliminar_cultivo' : 'eliminar_lote');
                fd.append(isCrop ? 'id_cultivo' : 'id_lote', idInput.value);

                confirmButton.disabled = true;
                confirmButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span> Eliminando...</span>';

                fd.append('_token', document.querySelector('[data-admin-agriculture-csrf]')?.dataset.adminAgricultureCsrf || '');
                fetch('admin/agricultura', { method: 'POST', body: fd })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            alert(data.message || 'No se pudo eliminar el registro.');
                            return;
                        }

                        alert(data.message || 'Registro eliminado exitosamente.');
                        return Admin.closeModal(modalElement)
                            .then(() => Admin.loadCultivos());
                    })
                    .catch(error => {
                        console.error('Error eliminando registro agrícola:', error);
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
            fetch(`admin/agricultura/cultivos/${encodeURIComponent(id)}`)
                .then(r => r.text())
                .then(html => {
                    const container = document.getElementById('detallesCultivoContent');
                    if (container) container.innerHTML = html;
                    if (typeof bootstrap !== 'undefined') {
                        const modalEl = document.getElementById('modalDetallesCultivo');
                        if (modalEl) {
                            this.mountDynamicModal(modalEl, 'cultivos-content');
                            bootstrap.Modal.getOrCreateInstance(modalEl).show();
                        }
                    }
                }).catch(err => { console.error('Error cultivo detalle:', err); alert('Error al cargar los detalles del cultivo'); });
        },

        eliminarCultivo: function (id) {
            document.querySelector(
                `[data-admin-crop-delete="cultivo"][data-record-id="${id}"]`
            )?.click();
        },

        verDetalleLote: function (id) {
            fetch(`admin/agricultura/lotes/${encodeURIComponent(id)}`)
                .then(r => r.text())
                .then(html => {
                    const container = document.getElementById('detalleLoteContent');
                    if (container) container.innerHTML = html;
                    if (typeof bootstrap !== 'undefined') {
                        const modalEl = document.getElementById('modalDetalleLote');
                        if (modalEl) {
                            this.mountDynamicModal(modalEl, 'cultivos-content');
                            bootstrap.Modal.getOrCreateInstance(modalEl).show();
                        }
                    }
                }).catch(err => { console.error('Error lote detalle:', err); alert('Error al cargar los detalles del lote'); });
        },

        eliminarLote: function (id) {
            document.querySelector(
                `[data-admin-crop-delete="lote"][data-record-id="${id}"]`
            )?.click();
        },

        verDetallesFactura: function (id) {
            fetch(`facturas/${encodeURIComponent(id)}`)
                .then(r => r.text())
                .then(html => {
                    const container = document.getElementById('detallesFacturaContent');
                    if (container) container.innerHTML = html;
                    if (typeof bootstrap !== 'undefined') {
                        const modalEl = document.getElementById('modalDetallesFactura');
                        if (modalEl) {
                            this.mountDynamicModal(modalEl, 'facturas-content');
                            bootstrap.Modal.getOrCreateInstance(modalEl).show();
                        }
                    }
                }).catch(err => { console.error('Error ver detalles factura:', err); alert('Error al cargar los detalles'); });
        },

        setupAccountMenu: function () {
            const menu = document.querySelector('[data-admin-account-menu]');
            const trigger = menu?.querySelector('[data-admin-account-trigger]');
            if (!menu || !trigger || menu.dataset.adminAccountListener === '1') return;

            menu.dataset.adminAccountListener = '1';

            const setOpen = (open) => {
                menu.classList.toggle('is-open', open);
                trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
            };

            trigger.addEventListener('click', (event) => {
                event.stopPropagation();
                setOpen(!menu.classList.contains('is-open'));
            });

            document.addEventListener('click', (event) => {
                if (!menu.contains(event.target)) setOpen(false);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') setOpen(false);
            });
        },

        setupLotHistoryLauncher: function () {
            if (document.body.dataset.adminLotHistoryDelegated === '1') return;
            document.body.dataset.adminLotHistoryDelegated = '1';

            const clearHistoryResult = () => {
                content.querySelectorAll(':scope > .app-table-tools, :scope > .app-table-pagination').forEach(element => {
                    element.remove();
                });
                document.querySelectorAll('.app-table-filter__menu[data-app-table-owner="historialLoteContent"]').forEach(menu => {
                    menu.remove();
                });
                content.replaceChildren();
            };

            document.addEventListener('click', async (event) => {
                const button = event.target.closest('[data-admin-lot-history]');
                if (!button) return;

                event.preventDefault();
                event.stopPropagation();

                const nativeSelect = document.getElementById('selectorLote');
                const content = document.getElementById('historialLoteContent');
                if (!nativeSelect || !content || button.disabled) return;

                const loteId = nativeSelect.value;
                const icon = button.querySelector('i');
                const label = button.querySelector('span');
                const customSelectButton = nativeSelect.nextElementSibling?.querySelector('.admin-lot-select__button');

                clearHistoryResult();

                if (!loteId) {
                    content.innerHTML = '<div class="alert alert-info"><i class="fas fa-circle-info"></i> Seleccione un lote para consultar su historial.</div>';
                    customSelectButton?.focus();
                    return;
                }

                button.disabled = true;
                button.classList.add('is-loading');
                if (icon) icon.className = 'fas fa-circle-notch fa-spin';
                if (label) label.textContent = 'Cargando historial...';
                content.innerHTML = '<div class="text-center"><i class="fas fa-circle-notch fa-spin"></i><p>Cargando historial...</p></div>';

                try {
                    await this.ensureFormModule();
                    const response = await fetch(`admin/agricultura/lotes/${encodeURIComponent(loteId)}/historial?_=${Date.now()}`, {
                        cache: 'no-store',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);

                    const result = document.createElement('div');
                    result.className = 'admin-lot-history-result';
                    result.innerHTML = await response.text();
                    content.replaceChildren(result);

                    window.AppTable?.enhance?.(content);
                } catch (error) {
                    console.error('Error al cargar historial del lote:', error);
                    content.innerHTML = '<div class="alert alert-danger"><i class="fas fa-triangle-exclamation"></i> No se pudo cargar el historial. Intente nuevamente.</div>';
                } finally {
                    button.disabled = false;
                    button.classList.remove('is-loading');
                    if (icon) icon.className = 'fas fa-magnifying-glass';
                    if (label) label.textContent = 'Ver Historial Completo';
                }
            });
        },

        setupLotHistoryControlDelegates: function () {
            if (document.body.dataset.adminLotHistoryControlsDelegated === '1') return;
            document.body.dataset.adminLotHistoryControlsDelegated = '1';

            const ownerId = 'historialLoteContent';
            const getResult = () => document.querySelector('#historialLoteContent .admin-lot-history-result');
            const getRows = () => Array.from(document.querySelectorAll('#historialLoteContent .admin-lot-history-table tbody tr'));
            const normalize = (value) => String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim()
                .toLowerCase();
            const getStatus = (row) => {
                const cell = row.cells[4];
                return (cell?.querySelector('.app-table-status-capsule, .badge')?.textContent || cell?.textContent || '').trim();
            };
            const getMenu = () => document.querySelector(`.app-table-filter__menu[data-app-table-owner="${ownerId}"]`);
            const closeMenu = () => {
                const result = getResult();
                const filter = result?.querySelector('.app-table-filter');
                const button = result?.querySelector('.app-table-filter__button');
                const menu = getMenu();
                filter?.classList.remove('is-open');
                menu?.classList.remove('is-open');
                button?.setAttribute('aria-expanded', 'false');
            };
            const positionMenu = () => {
                const result = getResult();
                const button = result?.querySelector('.app-table-filter__button');
                const menu = getMenu();
                if (!button || !menu) return;

                const rect = button.getBoundingClientRect();
                const viewportGap = 12;
                const width = Math.min(Math.max(rect.width, 270), window.innerWidth - (viewportGap * 2));
                const left = Math.min(Math.max(viewportGap, rect.right - width), window.innerWidth - width - viewportGap);
                menu.style.left = `${Math.round(left)}px`;
                menu.style.top = `${Math.round(rect.bottom + 7)}px`;
                menu.style.width = `${Math.round(width)}px`;
            };
            const render = () => {
                const result = getResult();
                if (!result) return;

                const rows = getRows();
                const query = normalize(result.querySelector('.app-table-search input')?.value || '');
                const status = result.dataset.historyStatus || '';
                const pageSize = 10;
                let page = Number(result.dataset.historyPage || '1') || 1;
                const filtered = rows.filter(row => {
                    return (!query || normalize(row.textContent).includes(query))
                        && (!status || getStatus(row).toLowerCase() === status);
                });
                const pages = Math.max(1, Math.ceil(filtered.length / pageSize));
                page = Math.min(Math.max(1, page), pages);
                result.dataset.historyPage = String(page);

                const visibleRows = new Set(filtered.slice((page - 1) * pageSize, page * pageSize));
                rows.forEach(row => {
                    row.style.display = visibleRows.has(row) ? '' : 'none';
                });

                const pagination = result.querySelector('.app-table-pagination');
                if (!pagination) return;
                const info = pagination.querySelector('.app-table-page-info');
                const prev = pagination.querySelector('[data-prev]');
                const next = pagination.querySelector('[data-next]');
                if (info) info.textContent = `${filtered.length} registros · Página ${page} de ${pages}`;
                if (prev) prev.disabled = page === 1;
                if (next) next.disabled = page === pages;
            };

            document.addEventListener('input', (event) => {
                if (!event.target.matches('#historialLoteContent .app-table-search input')) return;
                const result = getResult();
                if (result) result.dataset.historyPage = '1';
                render();
            }, true);

            document.addEventListener('click', (event) => {
                const filterButton = event.target.closest('#historialLoteContent .app-table-filter__button');
                if (filterButton) {
                    event.preventDefault();
                    event.stopPropagation();
                    const result = getResult();
                    const filter = result?.querySelector('.app-table-filter');
                    const menu = getMenu();
                    const willOpen = !filter?.classList.contains('is-open');
                    filter?.classList.toggle('is-open', willOpen);
                    menu?.classList.toggle('is-open', willOpen);
                    filterButton.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                    if (willOpen) positionMenu();
                    return;
                }

                const option = event.target.closest(`.app-table-filter__menu[data-app-table-owner="${ownerId}"] .app-table-filter__option`);
                if (option) {
                    event.preventDefault();
                    event.stopPropagation();
                    const result = getResult();
                    if (result) {
                        result.dataset.historyStatus = option.dataset.value || '';
                        result.dataset.historyPage = '1';
                        const label = result.querySelector('.app-table-filter__current');
                        if (label) label.textContent = option.querySelector('span')?.textContent || 'Todos los estados';
                    }
                    getMenu()?.querySelectorAll('.app-table-filter__option').forEach(item => {
                        const isSelected = item === option;
                        item.classList.toggle('is-selected', isSelected);
                        item.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                    });
                    closeMenu();
                    render();
                    return;
                }

                const prev = event.target.closest('#historialLoteContent .app-table-pagination [data-prev]');
                const next = event.target.closest('#historialLoteContent .app-table-pagination [data-next]');
                if (prev || next) {
                    event.preventDefault();
                    event.stopPropagation();
                    const result = getResult();
                    if (result) {
                        const current = Number(result.dataset.historyPage || '1') || 1;
                        result.dataset.historyPage = String(prev ? current - 1 : current + 1);
                    }
                    render();
                    return;
                }

                if (!event.target.closest('#historialLoteContent .app-table-filter') && !event.target.closest(`.app-table-filter__menu[data-app-table-owner="${ownerId}"]`)) {
                    closeMenu();
                }
            }, true);

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') closeMenu();
            });
            window.addEventListener('resize', positionMenu);
        },

        // Init: engancha listeners de pestañas (evita duplicar listeners)
        init: function () {
            this.setupAccountMenu();

            if (document.querySelector('form, select, input.form-control, textarea.form-control')) {
                this.ensureFormModule()
                    .then(() => this.setupDynamicForms(document))
                    .catch(error => console.error('No se pudo inicializar formularios:', error));
            }

            document.addEventListener('hidden.bs.modal', () => {
                window.setTimeout(() => Admin.cleanupModalState(), 0);
            });

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
            if (editRol) {
                editRol.value = rol || '';
                editRol.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (editContrasena) editContrasena.value = '';
            const modal = document.getElementById('modalEditarUsuario');
            if (modal && typeof bootstrap !== 'undefined') {
                new bootstrap.Modal(modal).show();
            }
        }, 50);
    };

    window.eliminarUsuario = function (trigger) {
        const modalElement = document.getElementById('adminUserDeleteModal');
        if (!modalElement || typeof bootstrap === 'undefined' || !bootstrap.Modal) return;

        const id = trigger?.dataset?.userId || String(trigger || '');
        const name = trigger?.dataset?.userName || 'Usuario seleccionado';
        const email = trigger?.dataset?.userEmail || 'Sin correo disponible';

        const idInput = modalElement.querySelector('#adminUserDeleteId');
        const idDisplay = modalElement.querySelector('#adminUserDeleteDisplayId');
        const nameDisplay = modalElement.querySelector('#adminUserDeleteName');
        const emailDisplay = modalElement.querySelector('#adminUserDeleteEmail');

        if (idInput) idInput.value = id;
        if (idDisplay) idDisplay.textContent = id;
        if (nameDisplay) nameDisplay.textContent = name;
        if (emailDisplay) emailDisplay.textContent = email;

        bootstrap.Modal.getOrCreateInstance(modalElement).show();
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

    const initAdmin = function () {
        if (document.body?.dataset.adminInitialized === '1') return;
        if (document.body) document.body.dataset.adminInitialized = '1';
        Admin.init();
    };

    // Inicializamos cuando DOM esté listo, o de inmediato si el script llegó tarde.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAdmin);
    } else {
        initAdmin();
    }
    
})(window, document);
