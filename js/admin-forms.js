(function (window, document) {
    'use strict';

    window.AdminFormMethods = {
        enhanceAdminControls: function (container) {
            const root = container || document;
            const forms = [];
            const controls = [];

            if (root.matches?.('form')) forms.push(root);
            root.querySelectorAll?.('form').forEach(form => forms.push(form));
            root.querySelectorAll?.('input.form-control, textarea.form-control, select.form-control, select.form-select')
                .forEach(control => controls.push(control));

            forms.forEach(form => form.classList.add('admin-form-surface'));

            controls.forEach(control => {
                if (control.dataset.adminControlReady === '1') return;
                control.dataset.adminControlReady = '1';

                const type = (control.getAttribute('type') || '').toLowerCase();
                if (['hidden', 'checkbox', 'radio'].includes(type)) return;

                control.classList.add('admin-control');
                if (control.matches('select')) {
                    control.classList.add('admin-control--select');
                }

                const field = control.closest(
                    '.mb-3, .form-group, .admin-invoice-filter, .admin-purchase-field, .purchase-field, .admin-lot-history-field'
                );
                field?.classList.add('admin-field');

                const label = field?.querySelector(':scope > label, :scope > .form-label');
                if (!control.id) {
                    window.adminControlSequence = (window.adminControlSequence || 0) + 1;
                    control.id = `admin-control-${window.adminControlSequence}`;
                }
                if (label && !label.htmlFor) label.htmlFor = control.id;

                if (control.required) {
                    control.setAttribute('aria-required', 'true');
                    if (label && !label.querySelector('b, .required') && !label.textContent.includes('*')) {
                        label.classList.add('admin-required');
                    }
                }

                const clearValidation = () => control.classList.remove('is-invalid');
                control.addEventListener('input', clearValidation);
                control.addEventListener('change', clearValidation);
            });
        },

        bindAdminListbox: function (button, menu, close) {
            if (!button || !menu || button.dataset.adminKeyboardReady === '1') return;
            button.dataset.adminKeyboardReady = '1';

            if (!menu.id) {
                window.adminListboxSequence = (window.adminListboxSequence || 0) + 1;
                menu.id = `admin-listbox-${window.adminListboxSequence}`;
            }
            button.setAttribute('aria-controls', menu.id);

            const options = () => Array.from(menu.querySelectorAll('[role="option"]:not(:disabled)'));
            const focusOption = direction => {
                const items = options();
                if (!items.length) return;
                const selected = items.findIndex(option => option.classList.contains('is-selected'));
                const index = direction < 0
                    ? (selected > 0 ? selected - 1 : items.length - 1)
                    : (selected >= 0 && selected < items.length - 1 ? selected + 1 : 0);
                items[index].focus();
            };

            button.addEventListener('keydown', event => {
                if (!['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) return;
                event.preventDefault();
                if (button.getAttribute('aria-expanded') !== 'true') button.click();
                window.requestAnimationFrame(() => focusOption(event.key === 'ArrowUp' ? -1 : 1));
            });

            menu.addEventListener('keydown', event => {
                const items = options();
                const current = items.indexOf(document.activeElement);
                if (event.key === 'Escape') {
                    event.preventDefault();
                    close();
                    button.focus();
                    return;
                }
                if (event.key === 'Enter' || event.key === ' ') {
                    if (current >= 0) {
                        event.preventDefault();
                        items[current].click();
                    }
                    return;
                }
                if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key) || !items.length) return;
                event.preventDefault();
                let next = current;
                if (event.key === 'Home') next = 0;
                if (event.key === 'End') next = items.length - 1;
                if (event.key === 'ArrowDown') next = current < items.length - 1 ? current + 1 : 0;
                if (event.key === 'ArrowUp') next = current > 0 ? current - 1 : items.length - 1;
                items[next].focus();
            });
        },

        setupGenericAdminSelects: function (container) {
            const root = container || document;
            document.querySelectorAll('.admin-select__menu').forEach(menu => {
                if (!document.getElementById(menu.dataset.nativeId || '')) menu.remove();
            });

            root.querySelectorAll('select.form-control, select.form-select').forEach(nativeSelect => {
                const forceAdminSelect = nativeSelect.matches('[data-admin-select]');
                if (nativeSelect.multiple || nativeSelect.dataset.hasCustomSelect || (!forceAdminSelect && (
                    nativeSelect.matches('[data-admin-lot-select], [data-purchase-select], [data-product-filter-select], .admin-user-role__native') ||
                    nativeSelect.closest('.phytosanitary-page, .phytosanitary-admin, .phytosanitary-modal') ||
                    nativeSelect.matches('[data-fito-product-select], [data-fito-edit-lote], [data-fito-edit-tipo], [data-fito-edit-severidad], [data-fito-status-estado]')
                ))) return;

                nativeSelect.dataset.hasCustomSelect = '1';
                nativeSelect.classList.add('admin-select__native');
                nativeSelect.tabIndex = -1;
                nativeSelect.setAttribute('aria-hidden', 'true');
                if (!nativeSelect.id) {
                    window.adminControlSequence = (window.adminControlSequence || 0) + 1;
                    nativeSelect.id = `admin-control-${window.adminControlSequence}`;
                }

                const select = document.createElement('div');
                const button = document.createElement('button');
                const leading = document.createElement('span');
                const label = document.createElement('span');
                const arrow = document.createElement('i');
                const menu = document.createElement('div');
                const list = document.createElement('div');
                const options = Array.from(nativeSelect.options);
                const fieldName = (nativeSelect.name || nativeSelect.id).toLowerCase();
                const isFilterControl = nativeSelect.matches('[data-filter-control]');
                const iconName = isFilterControl ? 'fa-filter'
                    : fieldName.includes('estado') ? 'fa-circle-check'
                    : fieldName.includes('lote') ? 'fa-map-location-dot'
                    : fieldName.includes('usuario') ? 'fa-user'
                    : fieldName.includes('proveedor') ? 'fa-truck'
                    : fieldName.includes('producto') || fieldName.includes('insumo') ? 'fa-box'
                    : fieldName.includes('cultivo') ? 'fa-seedling'
                    : 'fa-sliders';
                const optionIconName = fieldName.includes('lote') ? 'fa-seedling'
                    : fieldName.includes('estado') ? 'fa-circle-check'
                    : fieldName.includes('severidad') ? 'fa-triangle-exclamation'
                    : fieldName.includes('tipo') ? 'fa-bug'
                    : fieldName.includes('producto') || fieldName.includes('insumo') ? 'fa-box'
                    : iconName;

                select.className = `admin-select${isFilterControl ? ' admin-select--filter' : ''}`;
                button.type = 'button';
                button.className = 'admin-select__button';
                button.disabled = nativeSelect.disabled;
                button.setAttribute('aria-haspopup', 'listbox');
                button.setAttribute('aria-expanded', 'false');
                leading.className = 'admin-select__leading';
                leading.innerHTML = `<i class="fas ${iconName}" aria-hidden="true"></i>`;
                label.className = 'admin-select__label';
                arrow.className = 'fas fa-chevron-down admin-select__arrow';
                menu.className = `admin-select__menu${isFilterControl ? ' admin-select__menu--filter' : ''}`;
                menu.dataset.nativeId = nativeSelect.id;
                list.className = 'admin-select__list';
                list.setAttribute('role', 'listbox');
                list.setAttribute('aria-label', nativeSelect.getAttribute('aria-label') || 'Seleccionar opción');

                button.append(leading, label, arrow);
                menu.append(list);
                select.append(button);
                nativeSelect.insertAdjacentElement('afterend', select);
                document.body.append(menu);

                if (options.filter(option => !option.disabled && option.value).length > 7) {
                    const searchWrap = document.createElement('label');
                    const search = document.createElement('input');
                    searchWrap.className = 'admin-select__search';
                    searchWrap.innerHTML = '<i class="fas fa-magnifying-glass" aria-hidden="true"></i>';
                    search.type = 'search';
                    search.placeholder = 'Buscar opción...';
                    search.setAttribute('aria-label', 'Buscar opción');
                    searchWrap.append(search);
                    menu.prepend(searchWrap);
                    search.addEventListener('input', () => {
                        const term = search.value.trim().toLocaleLowerCase('es');
                        list.querySelectorAll('.admin-select__option').forEach(option => {
                            option.hidden = !option.textContent.toLocaleLowerCase('es').includes(term);
                        });
                    });
                }

                const close = () => {
                    select.classList.remove('is-open');
                    menu.classList.remove('is-open');
                    button.setAttribute('aria-expanded', 'false');
                };
                const positionMenu = () => {
                    const rect = button.getBoundingClientRect();
                    const below = window.innerHeight - rect.bottom;
                    const openAbove = below < 230 && rect.top > below;
                    menu.style.left = `${Math.round(rect.left)}px`;
                    menu.style.width = `${Math.round(rect.width)}px`;
                    menu.style.maxHeight = `${Math.min(340, Math.max(150, openAbove ? rect.top - 14 : below - 14))}px`;
                    menu.style.top = openAbove ? 'auto' : `${Math.round(rect.bottom + 8)}px`;
                    menu.style.bottom = openAbove ? `${Math.round(window.innerHeight - rect.top + 8)}px` : 'auto';
                    menu.dataset.placement = openAbove ? 'top' : 'bottom';
                };
                const sync = () => {
                    const selected = nativeSelect.selectedOptions[0];
                    label.textContent = selected?.textContent.trim().replace(/\s+/g, ' ') || 'Seleccionar opción';
                    button.classList.toggle('is-placeholder', !nativeSelect.value);
                    button.disabled = nativeSelect.disabled;
                    select.classList.remove('is-invalid');
                    list.querySelectorAll('.admin-select__option').forEach(option => {
                        const active = option.dataset.index === String(nativeSelect.selectedIndex);
                        option.classList.toggle('is-selected', active);
                        option.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                };

                options.forEach((nativeOption, index) => {
                    const option = document.createElement('button');
                    const optionIcon = document.createElement('i');
                    const optionLabel = document.createElement('span');
                    const check = document.createElement('i');
                    option.type = 'button';
                    option.className = 'admin-select__option';
                    option.dataset.index = String(index);
                    option.setAttribute('role', 'option');
                    option.disabled = nativeOption.disabled;
                    optionIcon.className = `fas ${optionIconName} admin-select__option-icon`;
                    optionIcon.setAttribute('aria-hidden', 'true');
                    optionLabel.textContent = nativeOption.textContent.trim().replace(/\s+/g, ' ');
                    optionLabel.className = 'admin-select__option-label';
                    check.className = 'fas fa-check';
                    check.setAttribute('aria-hidden', 'true');
                    option.append(optionIcon, optionLabel, check);
                    option.addEventListener('click', () => {
                        nativeSelect.selectedIndex = index;
                        nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        sync();
                        close();
                        button.focus();
                    });
                    list.append(option);
                });

                this.bindAdminListbox(button, list, close);
                button.addEventListener('click', () => {
                    if (button.disabled) return;
                    document.querySelectorAll('.admin-select__menu.is-open').forEach(openMenu => {
                        if (openMenu !== menu) openMenu.classList.remove('is-open');
                    });
                    document.querySelectorAll('.admin-select.is-open').forEach(openSelect => {
                        if (openSelect !== select) openSelect.classList.remove('is-open');
                    });
                    const willOpen = !select.classList.contains('is-open');
                    select.classList.toggle('is-open', willOpen);
                    menu.classList.toggle('is-open', willOpen);
                    button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                    if (willOpen) {
                        positionMenu();
                        menu.querySelector('input')?.focus();
                    }
                });
                nativeSelect.addEventListener('change', sync);
                nativeSelect.addEventListener('invalid', event => {
                    event.preventDefault();
                    select.classList.add('is-invalid');
                    button.focus();
                });
                nativeSelect.form?.addEventListener('reset', () => window.setTimeout(sync, 0));
                document.addEventListener('click', event => {
                    if (!select.contains(event.target) && !menu.contains(event.target)) close();
                });
                window.addEventListener('resize', () => select.classList.contains('is-open') && positionMenu());
                window.addEventListener('scroll', () => select.classList.contains('is-open') && positionMenu(), true);
                document.addEventListener('shown.bs.modal', sync);
                sync();
            });
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

                this.bindAdminListbox(button, customSelect.querySelector('.admin-user-role__menu'), close);

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
                formData.append('_token', root.querySelector('[data-users-csrf]')?.dataset.usersCsrf || '');

                try {
                    const response = await fetch('usuarios/eliminar', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (!data.success) {
                        throw new Error(data.message || 'No se pudo eliminar el usuario');
                    }

                    await window.Admin.closeModal(modalElement);
                    alert(data.message || 'Usuario eliminado exitosamente');
                    window.Admin.loadUsuarios();
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
                nativeSelect.tabIndex = -1;
                nativeSelect.setAttribute('aria-hidden', 'true');

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
                    const hasValue = Boolean(nativeSelect.value);
                    label.textContent = nativeSelect.value
                        ? selectedOption?.textContent.trim().replace(/\s+/g, ' ')
                        : 'Seleccione un lote';
                    customSelect.classList.toggle('has-value', hasValue);
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

                this.bindAdminListbox(button, menu, close);

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
                nativeSelect.tabIndex = -1;
                nativeSelect.setAttribute('aria-hidden', 'true');

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
                menu.dataset.nativeId = nativeSelect.id;
                menu.dataset.purchaseModalId = nativeSelect.closest('.modal')?.id || '';

                button.append(leadingIcon, label, arrow);
                customSelect.append(button);
                nativeSelect.insertAdjacentElement('afterend', customSelect);
                document.body.appendChild(menu);

                if (options.filter(option => option.value).length > 8) {
                    const searchWrap = document.createElement('label');
                    const searchIcon = document.createElement('i');
                    const searchInput = document.createElement('input');

                    searchWrap.className = 'admin-purchase-select__search';
                    searchIcon.className = 'fas fa-magnifying-glass';
                    searchIcon.setAttribute('aria-hidden', 'true');
                    searchInput.type = 'search';
                    searchInput.placeholder = 'Buscar producto...';
                    searchInput.setAttribute('aria-label', 'Buscar producto');
                    searchWrap.append(searchIcon, searchInput);
                    menu.appendChild(searchWrap);

                    searchInput.addEventListener('input', () => {
                        const term = searchInput.value.trim().toLocaleLowerCase('es');
                        menu.querySelectorAll('.admin-purchase-select__option').forEach(option => {
                            option.hidden = !option.textContent.toLocaleLowerCase('es').includes(term);
                        });
                    });
                }

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
                    button.disabled = nativeSelect.disabled;
                    customSelect.classList.toggle('has-value', Boolean(nativeSelect.value));
                    customSelect.classList.toggle('is-disabled', nativeSelect.disabled);
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

                this.bindAdminListbox(button, menu, close);

                button.addEventListener('click', () => {
                    if (nativeSelect.disabled) return;
                    const willOpen = !customSelect.classList.contains('is-open');
                    document.querySelectorAll('.admin-purchase-select.is-open').forEach(select => {
                        if (select !== customSelect) select.querySelector('button')?.click();
                    });
                    customSelect.classList.toggle('is-open', willOpen);
                    menu.classList.toggle('is-open', willOpen);
                    button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                    if (willOpen) {
                        positionMenu();
                        menu.querySelector('.admin-purchase-select__search input')?.focus();
                    }
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

        setupProductFilters: function (container) {
            const root = container || document;

            root.querySelectorAll('[data-product-filter]').forEach(input => {
                if (input.dataset.hasListener) return;
                input.dataset.hasListener = '1';

                const select = document.getElementById(input.dataset.productFilter);
                if (!select) return;

                input.addEventListener('input', () => {
                    const term = input.value.trim().toLocaleLowerCase('es');
                    let visibleCount = 0;
                    const customMenu = document.querySelector(`.admin-purchase-select__menu[data-native-id="${select.id}"]`);
                    const customOptions = customMenu
                        ? Array.from(customMenu.querySelectorAll('.admin-purchase-select__option'))
                        : [];

                    Array.from(select.options).forEach((option) => {
                        if (!option.value) {
                            option.hidden = false;
                            return;
                        }

                        const haystack = `${option.textContent} ${option.dataset.search || ''}`.toLocaleLowerCase('es');
                        const visible = !term || haystack.includes(term);
                        option.hidden = !visible;
                        customOptions
                            .filter(customOption => customOption.dataset.value === option.value)
                            .forEach(customOption => { customOption.hidden = !visible; });
                        visibleCount += visible ? 1 : 0;
                    });

                    if (select.selectedOptions[0]?.hidden) {
                        select.value = '';
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    input.dataset.matches = String(visibleCount);
                });
            });
        },

        setupNewProductToggle: function (container) {
            const root = container || document;

            root.querySelectorAll('[data-new-product-toggle]').forEach(toggle => {
                if (toggle.dataset.hasListener) return;
                toggle.dataset.hasListener = '1';

                const form = toggle.closest('form');
                const fieldsPanel = form?.querySelector('[data-new-product-fields]');
                const productSelect = form?.querySelector('#crear_pedido_producto');

                const sync = () => {
                    const enabled = toggle.checked;

                    if (fieldsPanel) {
                        fieldsPanel.hidden = !enabled;
                        fieldsPanel.querySelectorAll('input, select, textarea').forEach(field => {
                            field.disabled = !enabled;
                            field.required = field.hasAttribute('data-new-product-required') && enabled;
                            if (!enabled) field.value = '';
                            if (field.matches('select[data-purchase-select]')) {
                                field.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        });
                    }

                    if (productSelect) {
                        productSelect.disabled = enabled;
                        productSelect.required = !enabled;
                        if (enabled) productSelect.value = '';
                        productSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                };

                toggle.addEventListener('change', sync);
                form?.addEventListener('reset', () => window.setTimeout(sync, 0));
                sync();
            });
        },

        setupLotHistoryTable: function (container) {
            const root = container || document;
            const result = root.querySelector?.('.admin-lot-history-result') || root;
            const table = result.querySelector?.('.admin-lot-history-table');
            if (!table || table.dataset.adminHistoryReady === '1') return;

            const tbody = table.tBodies[0];
            const rows = Array.from(tbody?.rows || []);
            if (!tbody || rows.length === 0) return;

            table.dataset.adminHistoryReady = '1';
            table.dataset.appTable = '1';

            result.querySelectorAll(':scope > .app-table-tools, :scope > .app-table-pagination').forEach(element => element.remove());
            document.querySelectorAll('.app-table-filter__menu[data-app-table-owner="historialLoteContent"]').forEach(menu => menu.remove());

            const headings = Array.from(table.tHead?.rows[0]?.cells || []);
            const normalize = (value) => String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim()
                .toLowerCase();
            const statusIndex = headings.findIndex(heading => normalize(heading.textContent) === 'estado');
            const getStatus = (row) => {
                if (statusIndex < 0) return '';
                const cell = row.cells[statusIndex];
                return (cell?.querySelector('.app-table-status-capsule, .badge')?.textContent || cell?.textContent || '').trim();
            };

            const tools = document.createElement('div');
            tools.className = 'app-table-tools';
            tools.dataset.appTableOwner = 'historialLoteContent';
            tools.innerHTML = `
                <div class="app-table-tools-left">
                      <div class="app-table-search">
                          <span class="input-group-text"><i class="fas fa-search"></i></span>
                          <input type="search" class="form-control" placeholder="Buscar en la tabla" autocomplete="off">
                      </div>
                </div>
                <div class="app-table-tools-right">
                    <div class="app-table-filter">
                        <button type="button" class="app-table-filter__button" aria-haspopup="listbox" aria-expanded="false">
                            <i class="fas fa-filter" aria-hidden="true"></i>
                            <span class="app-table-filter__current">Todos los estados</span>
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            `;

            const pagination = document.createElement('div');
            pagination.className = 'app-table-pagination';
            pagination.dataset.appTableOwner = 'historialLoteContent';
            pagination.innerHTML = `
                <span class="app-table-page-info"></span>
                <span class="app-table-pagination__buttons">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-prev><i class="fas fa-chevron-left"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-next><i class="fas fa-chevron-right"></i></button>
                </span>
            `;

            const wrapper = table.closest('.table-responsive') || table;
            result.insertBefore(tools, wrapper);
            result.insertBefore(pagination, wrapper.nextSibling);

            const searchInput = tools.querySelector('input[type="search"]');
            const filter = tools.querySelector('.app-table-filter');
            const filterButton = tools.querySelector('.app-table-filter__button');
            const filterLabel = tools.querySelector('.app-table-filter__current');
            const filterMenu = document.createElement('div');
            filterMenu.className = 'app-table-filter__menu';
            filterMenu.dataset.appTableOwner = 'historialLoteContent';
            filterMenu.setAttribute('role', 'listbox');
            filterMenu.setAttribute('aria-label', 'Filtrar por estado');
            document.body.appendChild(filterMenu);

            const statusValues = Array.from(new Set(rows.map(getStatus).filter(Boolean)))
                .sort((first, second) => first.localeCompare(second, 'es'));
            let query = '';
            let status = '';
            let currentPage = 1;
            const pageSize = 10;

            const addOption = (value, label) => {
                const option = document.createElement('button');
                option.type = 'button';
                option.className = 'app-table-filter__option';
                option.dataset.value = value;
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', value === '' ? 'true' : 'false');
                option.innerHTML = `<span class="app-table-filter__option-label"></span><i class="fas fa-check" aria-hidden="true"></i>`;
                option.querySelector('span').textContent = label;
                filterMenu.appendChild(option);
            };

            addOption('', 'Todos los estados');
            statusValues.forEach(value => addOption(value.toLowerCase(), value));

            const positionMenu = () => {
                const rect = filterButton.getBoundingClientRect();
                const viewportGap = 12;
                const width = Math.min(Math.max(rect.width, 270), window.innerWidth - (viewportGap * 2));
                const left = Math.min(Math.max(viewportGap, rect.right - width), window.innerWidth - width - viewportGap);
                filterMenu.style.left = `${Math.round(left)}px`;
                filterMenu.style.top = `${Math.round(rect.bottom + 7)}px`;
                filterMenu.style.width = `${Math.round(width)}px`;
            };

            const closeMenu = () => {
                filter.classList.remove('is-open');
                filterMenu.classList.remove('is-open');
                filterButton.setAttribute('aria-expanded', 'false');
            };

            const render = () => {
                const filtered = rows.filter(row => {
                    const rowText = normalize(row.textContent);
                    const rowStatus = getStatus(row).toLowerCase();
                    return (!query || rowText.includes(query)) && (!status || rowStatus === status);
                });
                const pages = Math.max(1, Math.ceil(filtered.length / pageSize));
                currentPage = Math.min(currentPage, pages);
                const visibleRows = new Set(filtered.slice((currentPage - 1) * pageSize, currentPage * pageSize));

                rows.forEach(row => {
                    row.style.display = visibleRows.has(row) ? '' : 'none';
                });

                pagination.querySelector('.app-table-page-info').textContent =
                    `${filtered.length} registros · Página ${currentPage} de ${pages}`;
                pagination.querySelector('[data-prev]').disabled = currentPage === 1;
                pagination.querySelector('[data-next]').disabled = currentPage === pages;
            };

            searchInput.addEventListener('input', () => {
                query = normalize(searchInput.value);
                currentPage = 1;
                render();
            });
            searchInput.closest('.app-table-search')?.addEventListener('click', () => {
                searchInput.focus();
            });

            filterButton.addEventListener('click', () => {
                const willOpen = !filter.classList.contains('is-open');
                filter.classList.toggle('is-open', willOpen);
                filterMenu.classList.toggle('is-open', willOpen);
                filterButton.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                if (willOpen) positionMenu();
            });

            filterMenu.addEventListener('click', event => {
                const option = event.target.closest('.app-table-filter__option');
                if (!option) return;
                status = option.dataset.value || '';
                filterLabel.textContent = option.querySelector('span')?.textContent || 'Todos los estados';
                filterMenu.querySelectorAll('.app-table-filter__option').forEach(item => {
                    const isSelected = item === option;
                    item.classList.toggle('is-selected', isSelected);
                    item.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                });
                currentPage = 1;
                closeMenu();
                render();
            });

            pagination.querySelector('[data-prev]').addEventListener('click', () => {
                currentPage = Math.max(1, currentPage - 1);
                render();
            });
            pagination.querySelector('[data-next]').addEventListener('click', () => {
                currentPage += 1;
                render();
            });

            document.addEventListener('click', event => {
                if (!filter.contains(event.target) && !filterMenu.contains(event.target)) closeMenu();
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') closeMenu();
            });
            window.addEventListener('resize', () => {
                if (filter.classList.contains('is-open')) positionMenu();
            });

            if (statusValues.length === 0) {
                filter.remove();
                filterMenu.remove();
            }
            render();
        },

        setupAdminLotHistory: function (container) {
            // El listener del botón de historial se delega desde admin.js para que
            // funcione aunque este módulo se cargue tarde o se refresque el DOM.
        },

        // Conecta listeners/handlers para formularios y botones dinámicos dentro de un contenedor
        setupDynamicForms: function (container) {
            // container puede ser elemento o id string; si null => document
            let root = container;
            if (!root) root = document;
            if (typeof container === 'string') root = document.getElementById(container) || document;

            this.enhanceAdminControls(root);
            this.setupRequestConfirmation(root);
            this.setupInvoiceConfirmation(root);
            this.setupCropDeletion(root);
            this.setupUserRoleSelect(root);
            this.setupUserDeletion(root);
            this.setupAdminLotSelect(root);
            this.setupAdminLotHistory(root);
            this.setupPurchaseSelects(root);
            this.setupProductFilters(root);
            this.setupNewProductToggle(root);
            this.setupGenericAdminSelects(root);

            const invoiceFilters = root.querySelector('#purchaseInvoiceFilters');
            if (invoiceFilters && !invoiceFilters.dataset.hasListener) {
                invoiceFilters.dataset.hasListener = '1';
                invoiceFilters.addEventListener('submit', function (event) {
                    event.preventDefault();
                    const query = new URLSearchParams(new FormData(this)).toString();
                    window.Admin.loadContent(`facturas?${query}`, 'facturas-content', { useCache: false });
                });
            }

            const clearInvoiceFilters = root.querySelector('[data-clear-invoice-filters]');
            if (clearInvoiceFilters && !clearInvoiceFilters.dataset.hasListener) {
                clearInvoiceFilters.dataset.hasListener = '1';
                clearInvoiceFilters.addEventListener('click', function () {
                    window.Admin.loadFacturas();
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

                    fetch('usuarios', { method: 'POST', body: fd })
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
                                    window.Admin.loadUsuarios();
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
                    fetch('usuarios/actualizar', { method: 'POST', body: fd })
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
                                    window.Admin.loadUsuarios();
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
                fetch('proveedores', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Proveedor creado exitosamente');
                        window.Admin.refreshPedidosProveedores(
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
                const submitButton = this.querySelector('[type="submit"]');
                const originalContent = submitButton?.innerHTML;
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
                }
                const fd = new FormData(this);
                fd.append('action','editar_proveedor');
                fetch('proveedores/actualizar', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Proveedor actualizado');
                        window.Admin.refreshPedidosProveedores(
                            document.getElementById('modalEditarProveedor')
                        );
                    }
                    else alert('Error: ' + (data.message || 'Error desconocido'));
                }).catch(err => { console.error('Editar proveedor:', err); alert('Error de conexión'); })
                .finally(() => {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalContent;
                    }
                });
            });
            }

            const deleteProv = root.querySelector('#formEliminarProveedor');
            if (deleteProv && !deleteProv.dataset.hasListener) {
            deleteProv.dataset.hasListener = '1';
            deleteProv.addEventListener('submit', function (e) {
                e.preventDefault();
                const modalElement = document.getElementById('modalEliminarProveedor');
                const submitButton = this.querySelector('[type="submit"]');
                const buttonLabel = submitButton?.querySelector('span');
                const errorMessage = modalElement?.querySelector('[data-delete-provider-error]');

                if (errorMessage) {
                    errorMessage.hidden = true;
                    errorMessage.textContent = '';
                }
                if (submitButton) submitButton.disabled = true;
                if (buttonLabel) buttonLabel.textContent = 'Eliminando...';

                const fd = new FormData(this);
                fd.append('action', 'eliminar_proveedor');
                fetch('proveedores/eliminar', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'No se pudo eliminar el proveedor.');
                        }
                        alert(data.message || 'Proveedor eliminado exitosamente.');
                        return window.Admin.refreshPedidosProveedores(modalElement);
                    })
                    .catch(err => {
                        console.error('Eliminar proveedor:', err);
                        if (errorMessage) {
                            errorMessage.textContent = err.message === 'Failed to fetch'
                                ? 'No se pudo conectar con el servidor. Inténtalo nuevamente.'
                                : err.message;
                            errorMessage.hidden = false;
                        }
                    })
                    .finally(() => {
                        if (submitButton) submitButton.disabled = false;
                        if (buttonLabel) buttonLabel.textContent = 'Eliminar proveedor';
                    });
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
                fetch('pedidos', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Pedido creado exitosamente');
                        window.Admin.refreshPedidosProveedores(
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
                fetch('pedidos/actualizar', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Pedido actualizado exitosamente');
                        window.Admin.refreshPedidosProveedores(
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
                        if (field) {
                            field.value = value || '';
                            if (field.matches('select')) {
                                field.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        }
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

            window.eliminarProveedor = function (id, nombre, email) {
            const modalElement = document.getElementById('modalEliminarProveedor');
            if (!modalElement || typeof bootstrap === 'undefined') return;

            const idInput = modalElement.querySelector('#delete_proveedor_id');
            const nameDisplay = modalElement.querySelector('#delete_proveedor_nombre');
            const codeDisplay = modalElement.querySelector('#delete_proveedor_codigo');
            const emailDisplay = modalElement.querySelector('#delete_proveedor_email');
            const errorMessage = modalElement.querySelector('[data-delete-provider-error]');

            if (idInput) idInput.value = id;
            if (nameDisplay) nameDisplay.textContent = nombre || 'Proveedor sin nombre';
            if (codeDisplay) codeDisplay.textContent = `#${id}`;
            if (emailDisplay) emailDisplay.textContent = email || 'Sin correo registrado';
            if (errorMessage) {
                errorMessage.hidden = true;
                errorMessage.textContent = '';
            }

            bootstrap.Modal.getOrCreateInstance(modalElement).show();
            };

            root.querySelectorAll('[data-delete-provider]:not(:disabled)').forEach((button) => {
                if (button.dataset.hasListener) return;
                button.dataset.hasListener = '1';
                button.addEventListener('click', () => {
                    window.eliminarProveedor(
                        button.dataset.providerId,
                        button.dataset.providerName,
                        button.dataset.providerEmail
                    );
                });
            });

            window.cancelarPedido = function (id) {
            if (!confirm('¿Seguro de cancelar este pedido? Esta acción impedirá registrar su comprobante.')) return;
            const fd = new FormData(); fd.append('action','cancelar_pedido'); fd.append('id_pedido', id);
            fd.append('_token', root.querySelector('[data-abastecimiento-csrf]')?.dataset.abastecimientoCsrf || '');
            fetch('pedidos/cancelar', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                if (data.success) {
                    alert(data.message || 'Pedido cancelado');
                    window.Admin.loadPedidosProveedores();
                }
                else alert('Error: ' + (data.message || 'Error desconocido'));
                }).catch(err => { console.error('Cancelar pedido:', err); alert('Error de conexión'); });
            };
            // --- FIN: Handlers para Proveedores y Pedidos ---

            if (root.id) {
                root.querySelectorAll('.modal').forEach(modalElement => {
                    window.Admin.mountDynamicModal(modalElement, root.id);
                });
            }
        },

    };
})(window, document);
