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

        setupUserRoleSelect: function (container) {
            const root = container || document;

            root.querySelectorAll('[data-user-role-select]').forEach(customSelect => {
                if (customSelect.dataset.hasListener) return;
                customSelect.dataset.hasListener = '1';

                const nativeSelect = customSelect.querySelector('.admin-user-role__native');
                const button = customSelect.querySelector('[data-user-role-button]');
                const label = customSelect.querySelector('[data-user-role-label]');
                const options = Array.from(customSelect.querySelectorAll('.admin-user-role__option'));
                const form = customSelect.closest('form');

                if (!nativeSelect || !button || !label) return;

                const close = () => {
                    customSelect.classList.remove('is-open');
                    button.setAttribute('aria-expanded', 'false');
                };

                const sync = () => {
                    const selected = options.find(option => option.dataset.value === nativeSelect.value);
                    label.textContent = selected
                        ? selected.querySelector('strong').textContent
                        : 'Seleccione el nivel de acceso';
                    options.forEach(option => {
                        const isSelected = option === selected;
                        option.classList.toggle('is-selected', isSelected);
                        option.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                    });
                    if (nativeSelect.value) customSelect.classList.remove('is-invalid');
                };

                button.addEventListener('click', () => {
                    const willOpen = !customSelect.classList.contains('is-open');
                    customSelect.classList.toggle('is-open', willOpen);
                    button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                });

                options.forEach(option => {
                    option.addEventListener('click', () => {
                        nativeSelect.value = option.dataset.value || '';
                        nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        sync();
                        close();
                        button.focus();
                    });
                });

                customSelect.addEventListener('keydown', event => {
                    if (event.key === 'Escape') {
                        close();
                        button.focus();
                    }
                });

                nativeSelect.addEventListener('invalid', event => {
                    event.preventDefault();
                    customSelect.classList.add('is-invalid');
                    button.focus();
                });

                nativeSelect.addEventListener('change', sync);

                document.addEventListener('click', event => {
                    if (!customSelect.contains(event.target)) close();
                });

                form?.addEventListener('reset', () => {
                    window.setTimeout(() => {
                        sync();
                        close();
                    }, 0);
                });

                sync();
            });
        },

        setupUserDeletion: function (container) {
            const root = container || document;
            const modalElement = root.querySelector('#adminUserDeleteModal');
            const form = modalElement?.querySelector('#adminUserDeleteForm');

            if (!modalElement || !form || form.dataset.hasListener) return;
            form.dataset.hasListener = '1';

            form.addEventListener('submit', async event => {
                event.preventDefault();

                const id = form.querySelector('#adminUserDeleteId')?.value || '';
                const submitButton = form.querySelector('button[type="submit"]');
                const originalContent = submitButton?.innerHTML || '';

                if (!id || !submitButton) return;

                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Eliminando...</span>';

                const formData = new FormData();
                formData.append('action', 'eliminar');
                formData.append('id_usuario', id);

                try {
                    const response = await fetch('admin_usuarios.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (!data.success) {
                        throw new Error(data.message || 'No se pudo eliminar el usuario');
                    }

                    await Admin.closeModal(modalElement);
                    alert(data.message || 'Usuario eliminado exitosamente');
                    Admin.loadUsuarios();
                } catch (error) {
                    console.error('Error al eliminar usuario:', error);
                    alert(`Error: ${error.message || 'No se pudo eliminar el usuario'}`);
                } finally {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalContent;
                }
            });
        },

        setupAdminLotSelect: function (container) {
            const root = container || document;

            root.querySelectorAll('select[data-admin-lot-select]').forEach(nativeSelect => {
                if (nativeSelect.dataset.hasCustomSelect) return;
                nativeSelect.dataset.hasCustomSelect = '1';
                nativeSelect.classList.add('admin-lot-select__native');

                const customSelect = document.createElement('div');
                const button = document.createElement('button');
                const menu = document.createElement('div');
                const icon = document.createElement('i');
                const label = document.createElement('span');
                const arrow = document.createElement('i');

                customSelect.className = 'admin-lot-select';
                button.type = 'button';
                button.className = 'admin-lot-select__button';
                button.setAttribute('aria-haspopup', 'listbox');
                button.setAttribute('aria-expanded', 'false');
                icon.className = 'fas fa-location-dot';
                icon.setAttribute('aria-hidden', 'true');
                label.className = 'admin-lot-select__label';
                arrow.className = 'fas fa-chevron-down';
                arrow.setAttribute('aria-hidden', 'true');
                menu.className = 'admin-lot-select__menu';
                menu.setAttribute('role', 'listbox');
                menu.setAttribute('aria-label', 'Seleccionar lote');

                button.append(icon, label, arrow);
                customSelect.append(button);
                nativeSelect.insertAdjacentElement('afterend', customSelect);
                document.body.appendChild(menu);

                const options = Array.from(nativeSelect.options);
                const positionMenu = () => {
                    const rect = button.getBoundingClientRect();
                    const viewportGap = 12;
                    const spaceBelow = window.innerHeight - rect.bottom - viewportGap;
                    const availableHeight = Math.max(120, spaceBelow - 8);

                    menu.style.left = `${Math.round(rect.left)}px`;
                    menu.style.width = `${Math.round(rect.width)}px`;
                    menu.style.maxHeight = `${Math.min(210, availableHeight)}px`;
                    menu.style.top = `${Math.round(rect.bottom + 7)}px`;
                    menu.dataset.placement = 'bottom';
                };
                const sync = () => {
                    const selectedOption = nativeSelect.selectedOptions[0] || options[0];
                    label.textContent = nativeSelect.value
                        ? selectedOption?.textContent.trim().replace(/\s+/g, ' ')
                        : 'Seleccione un lote';
                    menu.querySelectorAll('.admin-lot-select__option').forEach(option => {
                        const isSelected = option.dataset.value === nativeSelect.value;
                        option.classList.toggle('is-selected', isSelected);
                        option.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                    });
                };
                const close = () => {
                    customSelect.classList.remove('is-open');
                    menu.classList.remove('is-open');
                    button.setAttribute('aria-expanded', 'false');
                };

                options.forEach((nativeOption, index) => {
                    if (index === 0 && nativeOption.value === '') return;

                    const option = document.createElement('button');
                    const optionIcon = document.createElement('i');
                    const optionCopy = document.createElement('span');
                    const optionTitle = document.createElement('strong');
                    const optionHint = document.createElement('small');
                    const text = nativeOption.textContent.trim().replace(/\s+/g, ' ');
                    const parts = text.split(' - ');

                    option.type = 'button';
                    option.className = 'admin-lot-select__option';
                    option.dataset.value = nativeOption.value;
                    option.setAttribute('role', 'option');
                    optionIcon.className = 'fas fa-seedling';
                    optionTitle.textContent = parts.shift();
                    optionHint.textContent = parts.join(' - ');
                    optionCopy.append(optionTitle, optionHint);
                    option.append(optionIcon, optionCopy);
                    menu.appendChild(option);

                    option.addEventListener('click', () => {
                        nativeSelect.value = option.dataset.value;
                        nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        sync();
                        close();
                        button.focus();
                    });
                });

                button.addEventListener('click', () => {
                    const willOpen = !customSelect.classList.contains('is-open');
                    customSelect.classList.toggle('is-open', willOpen);
                    menu.classList.toggle('is-open', willOpen);
                    button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                    if (willOpen) positionMenu();
                });

                const handleEscape = event => {
                    if (event.key === 'Escape') {
                        close();
                        button.focus();
                    }
                };
                customSelect.addEventListener('keydown', handleEscape);
                menu.addEventListener('keydown', handleEscape);

                document.addEventListener('click', event => {
                    if (!customSelect.contains(event.target) && !menu.contains(event.target)) close();
                });

                window.addEventListener('resize', () => {
                    if (customSelect.classList.contains('is-open')) positionMenu();
                });
                window.addEventListener('scroll', () => {
                    if (customSelect.classList.contains('is-open')) positionMenu();
                }, true);

                nativeSelect.addEventListener('change', sync);
                sync();
            });
        },

        setupPurchaseSelects: function (container) {
            const root = container || document;

            root.querySelectorAll('select[data-purchase-select]').forEach(nativeSelect => {
                if (nativeSelect.dataset.hasCustomSelect) return;
                nativeSelect.dataset.hasCustomSelect = '1';
                nativeSelect.classList.add('admin-purchase-select__native');

                const customSelect = document.createElement('div');
                const button = document.createElement('button');
                const leadingIcon = document.createElement('i');
                const label = document.createElement('span');
                const arrow = document.createElement('i');
                const menu = document.createElement('div');
                const placeholder = nativeSelect.dataset.selectLabel || 'Seleccionar opción';
                const optionIconClass = nativeSelect.dataset.optionIcon || 'fa-circle-check';
                const options = Array.from(nativeSelect.options);

                customSelect.className = 'admin-purchase-select';
                button.type = 'button';
                button.className = 'admin-purchase-select__button';
                button.setAttribute('aria-haspopup', 'listbox');
                button.setAttribute('aria-expanded', 'false');
                leadingIcon.className = `fas ${nativeSelect.dataset.selectIcon || 'fa-list'}`;
                leadingIcon.setAttribute('aria-hidden', 'true');
                label.className = 'admin-purchase-select__label';
                arrow.className = 'fas fa-chevron-down admin-purchase-select__arrow';
                arrow.setAttribute('aria-hidden', 'true');
                menu.className = 'admin-purchase-select__menu';
                menu.setAttribute('role', 'listbox');
                menu.setAttribute('aria-label', placeholder);
                menu.dataset.purchaseModalId = nativeSelect.closest('.modal')?.id || '';

                button.append(leadingIcon, label, arrow);
                customSelect.append(button);
                nativeSelect.insertAdjacentElement('afterend', customSelect);
                document.body.appendChild(menu);

                const positionMenu = () => {
                    const rect = button.getBoundingClientRect();
                    const viewportGap = 12;
                    const roomBelow = window.innerHeight - rect.bottom - viewportGap;
                    const roomAbove = rect.top - viewportGap;
                    const openAbove = roomBelow < 190 && roomAbove > roomBelow;
                    const maxHeight = Math.min(260, Math.max(130, openAbove ? roomAbove - 8 : roomBelow - 8));

                    menu.style.left = `${Math.round(rect.left)}px`;
                    menu.style.width = `${Math.round(rect.width)}px`;
                    menu.style.maxHeight = `${Math.round(maxHeight)}px`;
                    const menuHeight = Math.min(menu.scrollHeight, maxHeight);
                    menu.style.top = openAbove
                        ? `${Math.round(rect.top - menuHeight - 7)}px`
                        : `${Math.round(rect.bottom + 7)}px`;
                    menu.dataset.placement = openAbove ? 'top' : 'bottom';
                };

                const close = () => {
                    customSelect.classList.remove('is-open');
                    menu.classList.remove('is-open');
                    button.setAttribute('aria-expanded', 'false');
                };

                const sync = () => {
                    const selectedOption = nativeSelect.selectedOptions[0];
                    label.textContent = nativeSelect.value
                        ? selectedOption?.textContent.trim().replace(/\s+/g, ' ')
                        : placeholder;
                    customSelect.classList.toggle('has-value', Boolean(nativeSelect.value));
                    menu.querySelectorAll('.admin-purchase-select__option').forEach(option => {
                        const isSelected = option.dataset.value === nativeSelect.value;
                        option.classList.toggle('is-selected', isSelected);
                        option.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                    });
                    if (nativeSelect.value) customSelect.classList.remove('is-invalid');
                };

                options.forEach(nativeOption => {
                    if (!nativeOption.value) return;

                    const option = document.createElement('button');
                    const optionIcon = document.createElement('i');
                    const optionLabel = document.createElement('span');
                    const checkIcon = document.createElement('i');

                    option.type = 'button';
                    option.className = 'admin-purchase-select__option';
                    option.dataset.value = nativeOption.value;
                    option.setAttribute('role', 'option');
                    optionIcon.className = `fas ${optionIconClass}`;
                    optionIcon.setAttribute('aria-hidden', 'true');
                    optionLabel.textContent = nativeOption.textContent.trim().replace(/\s+/g, ' ');
                    checkIcon.className = 'fas fa-check admin-purchase-select__check';
                    checkIcon.setAttribute('aria-hidden', 'true');
                    option.append(optionIcon, optionLabel, checkIcon);
                    menu.appendChild(option);

                    option.addEventListener('click', () => {
                        nativeSelect.value = option.dataset.value;
                        nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        sync();
                        close();
                        button.focus();
                    });
                });

                button.addEventListener('click', () => {
                    const willOpen = !customSelect.classList.contains('is-open');
                    document.querySelectorAll('.admin-purchase-select.is-open').forEach(select => {
                        if (select !== customSelect) select.querySelector('button')?.click();
                    });
                    customSelect.classList.toggle('is-open', willOpen);
                    menu.classList.toggle('is-open', willOpen);
                    button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                    if (willOpen) positionMenu();
                });

                const handleEscape = event => {
                    if (event.key === 'Escape') {
                        close();
                        button.focus();
                    }
                };
                customSelect.addEventListener('keydown', handleEscape);
                menu.addEventListener('keydown', handleEscape);

                document.addEventListener('click', event => {
                    if (!customSelect.contains(event.target) && !menu.contains(event.target)) close();
                });
                window.addEventListener('resize', () => {
                    if (customSelect.classList.contains('is-open')) positionMenu();
                });
                window.addEventListener('scroll', () => {
                    if (customSelect.classList.contains('is-open')) positionMenu();
                }, true);

                nativeSelect.addEventListener('change', sync);
                nativeSelect.addEventListener('invalid', event => {
                    event.preventDefault();
                    customSelect.classList.add('is-invalid');
                    button.focus();
                });
                nativeSelect.form?.addEventListener('reset', () => {
                    window.setTimeout(() => {
                        sync();
                        close();
                    }, 0);
                });

                sync();
            });
        },

        setupAdminLotHistory: function (container) {
            const root = container || document;
            const button = root.querySelector('[data-admin-lot-history]');
            const nativeSelect = document.getElementById('selectorLote');
            const content = document.getElementById('historialLoteContent');

            if (!button || !nativeSelect || !content || button.dataset.hasListener) return;
            button.dataset.hasListener = '1';

            const clearHistoryResult = () => {
                content.querySelectorAll(':scope > .app-table-tools, :scope > .app-table-pagination').forEach(element => {
                    element.remove();
                });
                document.querySelectorAll('.app-table-filter__menu[data-app-table-owner="historialLoteContent"]').forEach(menu => {
                    menu.remove();
                });
                content.replaceChildren();
            };

            button.addEventListener('click', async () => {
                const loteId = nativeSelect.value;
                const icon = button.querySelector('i');
                const label = button.querySelector('span');
                const customSelectButton = nativeSelect.nextElementSibling?.querySelector('.admin-lot-select__button');

                clearHistoryResult();
                document.querySelectorAll('.admin-lot-select__menu.is-open').forEach(menu => {
                    menu.classList.remove('is-open');
                });
                nativeSelect.nextElementSibling?.classList.remove('is-open');
                customSelectButton?.setAttribute('aria-expanded', 'false');

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
                    const response = await fetch(`lote_historial.php?id=${encodeURIComponent(loteId)}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);

                    const result = document.createElement('div');
                    result.className = 'admin-lot-history-result';
                    result.innerHTML = await response.text();
                    content.replaceChildren(result);
                    window.AppUI?.refresh?.(result);
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

        // Conecta listeners/handlers para formularios y botones dinámicos dentro de un contenedor
        setupDynamicForms: function (container) {
            // container puede ser elemento o id string; si null => document
            let root = container;
            if (!root) root = document;
            if (typeof container === 'string') root = document.getElementById(container) || document;

            this.setupRequestConfirmation(root);
            this.setupInvoiceConfirmation(root);
            this.setupCropDeletion(root);
            this.setupUserRoleSelect(root);
            this.setupUserDeletion(root);
            this.setupPurchaseSelects(root);

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

            if (root.id) {
                root.querySelectorAll('.modal').forEach(modalElement => {
                    Admin.mountDynamicModal(modalElement, root.id);
                });
            }
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

            if (modalForm.dataset.adminInvoiceListener === '1') return;
            modalForm.dataset.adminInvoiceListener = '1';

            modalForm.addEventListener('submit', function (event) {
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

                fetch('admin_cultivos.php', { method: 'POST', body: fd })
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
            fetch(`cultivo_detalle.php?id=${id}`)
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
            fetch(`lote_detalle.php?id=${id}`)
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
            fetch(`factura_detalle.php?id=${id}`)
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

        // Init: engancha listeners de pestañas (evita duplicar listeners)
        init: function () {
            this.setupDynamicForms(document);
            this.setupAdminLotSelect(document);
            this.setupAdminLotHistory(document);

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

    // Inicializamos cuando DOM esté listo
    document.addEventListener('DOMContentLoaded', function () {
        Admin.init();
    });
    
})(window, document);
