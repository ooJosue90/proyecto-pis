(function (window, document) {
    'use strict';

    const Fito = {
        modal(id) {
            const element = document.getElementById(id);
            if (!element || !window.bootstrap?.Modal) return null;
            return window.bootstrap.Modal.getOrCreateInstance(element);
        },

        showModal(id, attempt = 0) {
            const modal = this.modal(id);
            if (modal) {
                modal.show();
                return;
            }

            if (attempt < 12) {
                window.setTimeout(() => this.showModal(id, attempt + 1), 50);
                return;
            }

            this.showModalFallback(id);
        },

        showModalFallback(id) {
            const element = document.getElementById(id);
            if (!element) return;

            element.style.display = 'block';
            element.removeAttribute('aria-hidden');
            element.setAttribute('aria-modal', 'true');
            element.setAttribute('role', 'dialog');
            element.classList.add('show');
            document.body.classList.add('modal-open');

            if (!document.querySelector(`.modal-backdrop[data-fito-fallback="${id}"]`)) {
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.dataset.fitoFallback = id;
                document.body.append(backdrop);
            }

            element.querySelectorAll('[data-bs-dismiss="modal"], .btn-close').forEach((button) => {
                if (button.dataset.fitoFallbackDismiss === '1') return;
                button.dataset.fitoFallbackDismiss = '1';
                button.addEventListener('click', () => this.hideModalFallback(id));
            });
        },

        hideModalFallback(id) {
            const element = document.getElementById(id);
            if (!element) return;

            element.classList.remove('show');
            element.style.display = 'none';
            element.setAttribute('aria-hidden', 'true');
            element.removeAttribute('aria-modal');
            element.removeAttribute('role');
            document.querySelectorAll(`.modal-backdrop[data-fito-fallback="${id}"]`).forEach((backdrop) => backdrop.remove());

            if (!document.querySelector('.modal.show')) {
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            }
        },

        setValue(selector, value, root = document) {
            const field = root.querySelector(selector);
            if (!field) return;
            if (field.tagName === 'SELECT' && value && !Array.from(field.options).some((option) => option.value === value)) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = `${value} · Registrado anteriormente`;
                field.appendChild(option);
            }
            field.value = value || '';
            field.dispatchEvent(new Event('change', { bubbles: true }));
        },

        refreshStockHint(select) {
            const field = select.closest('.record-field-card')?.querySelector('[data-fito-stock-hint]');
            if (!field) return;

            field.textContent = '';
            field.classList.remove('is-active');
        },

        number(value) {
            const parsed = Number(String(value || '').replace(',', '.'));
            return Number.isFinite(parsed) ? parsed : 0;
        },

        formatNumber(value) {
            return Number(value).toFixed(2).replace(/\.00$/, '');
        },

        iconForSelect(select) {
            const name = `${select.name || ''} ${select.dataset.fitoSelect || ''}`.toLowerCase();
            if (name.includes('lote')) return 'fa-map-location-dot';
            if (name.includes('tipo')) return 'fa-bug';
            if (name.includes('severidad')) return 'fa-triangle-exclamation';
            if (name.includes('estado')) return 'fa-circle-check';
            if (name.includes('insumo') || name.includes('producto')) return 'fa-box-medical';
            return 'fa-leaf';
        },

        setupSelects(root = document) {
            const scope = root instanceof Element || root === document ? root : document;
            const selects = scope.matches?.('.phytosanitary-page')
                ? scope.querySelectorAll('select.form-select')
                : scope.querySelectorAll('.phytosanitary-page select.form-select, .phytosanitary-modal select.form-select');

            selects.forEach((nativeSelect) => {
                if (nativeSelect.multiple || nativeSelect.dataset.fitoSelectReady === '1') return;

                nativeSelect.dataset.fitoSelectReady = '1';
                nativeSelect.dataset.hasCustomSelect = '1';
                nativeSelect.classList.add('phytosanitary-select__native');
                nativeSelect.tabIndex = -1;
                nativeSelect.setAttribute('aria-hidden', 'true');

                if (!nativeSelect.id) {
                    window.fitoSelectSequence = (window.fitoSelectSequence || 0) + 1;
                    nativeSelect.id = `fito-select-${window.fitoSelectSequence}`;
                }

                const customSelect = document.createElement('div');
                const button = document.createElement('button');
                const leading = document.createElement('span');
                const label = document.createElement('span');
                const arrow = document.createElement('i');
                const menu = document.createElement('div');
                const list = document.createElement('div');
                const icon = this.iconForSelect(nativeSelect);

                customSelect.className = 'phytosanitary-select';
                button.type = 'button';
                button.className = 'phytosanitary-select__button';
                button.disabled = nativeSelect.disabled;
                button.setAttribute('aria-haspopup', 'listbox');
                button.setAttribute('aria-expanded', 'false');
                leading.className = 'phytosanitary-select__leading';
                leading.innerHTML = `<i class="fas ${icon}" aria-hidden="true"></i>`;
                label.className = 'phytosanitary-select__label';
                arrow.className = 'fas fa-chevron-down phytosanitary-select__arrow';
                arrow.setAttribute('aria-hidden', 'true');
                menu.className = 'phytosanitary-select__menu';
                menu.dataset.nativeId = nativeSelect.id;
                list.className = 'phytosanitary-select__list';
                list.setAttribute('role', 'listbox');
                list.setAttribute('aria-label', nativeSelect.getAttribute('aria-label') || 'Seleccionar opción');

                button.append(leading, label, arrow);
                menu.append(list);
                customSelect.append(button);
                nativeSelect.insertAdjacentElement('afterend', customSelect);
                document.body.append(menu);

                const optionText = (option) => option.textContent.trim().replace(/\s+/g, ' ');
                const close = () => {
                    customSelect.classList.remove('is-open');
                    menu.classList.remove('is-open');
                    button.setAttribute('aria-expanded', 'false');
                };
                const positionMenu = () => {
                    const rect = button.getBoundingClientRect();
                    const gap = 10;
                    const below = window.innerHeight - rect.bottom - gap;
                    const above = rect.top - gap;
                    const openAbove = below < 220 && above > below;

                    menu.style.left = `${Math.round(rect.left)}px`;
                    menu.style.width = `${Math.round(rect.width)}px`;
                    menu.style.maxHeight = `${Math.min(320, Math.max(150, openAbove ? above : below))}px`;
                    menu.style.top = openAbove ? 'auto' : `${Math.round(rect.bottom + 8)}px`;
                    menu.style.bottom = openAbove ? `${Math.round(window.innerHeight - rect.top + 8)}px` : 'auto';
                    menu.dataset.placement = openAbove ? 'top' : 'bottom';
                };
                const sync = () => {
                    const selected = nativeSelect.selectedOptions[0];
                    label.textContent = selected ? optionText(selected) : 'Seleccionar opción';
                    button.classList.toggle('is-placeholder', !nativeSelect.value);
                    button.disabled = nativeSelect.disabled;
                    customSelect.classList.toggle('is-disabled', nativeSelect.disabled);
                    customSelect.classList.remove('is-invalid');

                    list.querySelectorAll('.phytosanitary-select__option').forEach((option) => {
                        const active = option.dataset.index === String(nativeSelect.selectedIndex);
                        option.classList.toggle('is-selected', active);
                        option.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                };
                const renderOptions = () => {
                    list.innerHTML = '';
                    Array.from(nativeSelect.options).forEach((nativeOption, index) => {
                        const option = document.createElement('button');
                        const optionIcon = document.createElement('i');
                        const optionLabel = document.createElement('span');
                        const check = document.createElement('i');

                        option.type = 'button';
                        option.className = 'phytosanitary-select__option';
                        option.dataset.index = String(index);
                        option.disabled = nativeOption.disabled;
                        option.setAttribute('role', 'option');
                        optionIcon.className = `fas ${icon} phytosanitary-select__option-icon`;
                        optionIcon.setAttribute('aria-hidden', 'true');
                        optionLabel.className = 'phytosanitary-select__option-label';
                        optionLabel.textContent = optionText(nativeOption);
                        check.className = 'fas fa-check';
                        check.setAttribute('aria-hidden', 'true');
                        option.append(optionIcon, optionLabel, check);
                        option.addEventListener('click', () => {
                            nativeSelect.selectedIndex = index;
                            nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                            if (nativeSelect.matches('[data-fito-product-select]')) {
                                this.refreshStockHint(nativeSelect);
                                this.refreshDoseHint(nativeSelect);
                            } else if (nativeSelect.matches('select[name="id_lote"]')) {
                                this.refreshDoseHintsForForm(nativeSelect.closest('form'));
                            }
                            sync();
                            close();
                            button.focus();
                        });
                        list.append(option);
                    });
                    sync();
                };

                renderOptions();

                if (window.MutationObserver) {
                    const observer = new MutationObserver(renderOptions);
                    observer.observe(nativeSelect, { childList: true, subtree: true, characterData: true });
                }

                button.addEventListener('click', () => {
                    if (button.disabled) return;
                    const willOpen = !customSelect.classList.contains('is-open');
                    document.querySelectorAll('.phytosanitary-select.is-open').forEach((select) => {
                        if (select !== customSelect) select.classList.remove('is-open');
                    });
                    document.querySelectorAll('.phytosanitary-select__menu.is-open').forEach((openMenu) => {
                        if (openMenu !== menu) openMenu.classList.remove('is-open');
                    });
                    customSelect.classList.toggle('is-open', willOpen);
                    menu.classList.toggle('is-open', willOpen);
                    button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                    if (willOpen) positionMenu();
                });

                const handleKeys = (event) => {
                    const options = Array.from(list.querySelectorAll('.phytosanitary-select__option:not([hidden]):not(:disabled)'));
                    const current = options.indexOf(document.activeElement);
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        close();
                        button.focus();
                        return;
                    }
                    if ((event.key === 'Enter' || event.key === ' ') && current >= 0) {
                        event.preventDefault();
                        options[current].click();
                        return;
                    }
                    if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key) || !options.length) return;
                    event.preventDefault();
                    let next = current;
                    if (event.key === 'Home') next = 0;
                    if (event.key === 'End') next = options.length - 1;
                    if (event.key === 'ArrowDown') next = current < options.length - 1 ? current + 1 : 0;
                    if (event.key === 'ArrowUp') next = current > 0 ? current - 1 : options.length - 1;
                    options[next].focus();
                };

                button.addEventListener('keydown', (event) => {
                    if (!['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) return;
                    event.preventDefault();
                    if (!customSelect.classList.contains('is-open')) button.click();
                    const selected = list.querySelector('.phytosanitary-select__option.is-selected');
                    (selected || list.querySelector('.phytosanitary-select__option:not(:disabled)'))?.focus();
                });
                list.addEventListener('keydown', handleKeys);
                nativeSelect.addEventListener('change', sync);
                nativeSelect.addEventListener('invalid', (event) => {
                    event.preventDefault();
                    customSelect.classList.add('is-invalid');
                    button.focus();
                });
                nativeSelect.form?.addEventListener('reset', () => window.setTimeout(sync, 0));
                document.addEventListener('click', (event) => {
                    if (!customSelect.contains(event.target) && !menu.contains(event.target)) close();
                });
                window.addEventListener('resize', () => customSelect.classList.contains('is-open') && positionMenu());
                window.addEventListener('scroll', () => customSelect.classList.contains('is-open') && positionMenu(), true);
                document.addEventListener('shown.bs.modal', () => {
                    sync();
                    if (customSelect.classList.contains('is-open')) positionMenu();
                });
            });
        },

        areaForForm(form) {
            const treatmentArea = this.number(form.querySelector('[data-fito-treatment-area]')?.value);
            if (treatmentArea > 0) return treatmentArea;

            const lotSelect = form.querySelector('select[name="id_lote"]');
            const selectedLot = lotSelect?.selectedOptions?.[0];
            return this.number(selectedLot?.dataset.area);
        },

        refreshDoseHint(select) {
            const form = select.closest('form');
            const card = select.closest('.record-field-card');
            const field = card?.querySelector('[data-fito-dose-hint]');
            if (!form) return;

            const option = select.selectedOptions[0];
            if (!option || !option.value) {
                if (field) {
                    field.innerHTML = '';
                    field.classList.remove('is-active', 'is-warning');
                }
                this.resetDosePanel(form);
                return;
            }

            const area = this.areaForForm(form);
            const dose = this.number(option.dataset.dose);
            const doseUnit = option.dataset.doseUnit || option.dataset.unit || 'unidades';
            const applicationUnit = option.dataset.applicationUnit || 'ha';

            if (field) {
                field.innerHTML = '';
                field.classList.remove('is-active', 'is-warning');
            }

            if (area <= 0) {
                this.resetDosePanel(form);
                return;
            }

            if (dose <= 0) {
                this.resetDosePanel(form);
                return;
            }

            this.updateDosePanel(form, {
                area,
                recommendedDose: dose,
                appliedDose: dose,
                doseUnit,
                applicationUnit,
            });

            if (field) field.innerHTML = '';
        },

        resetDosePanel(form) {
            const panel = form?.querySelector('[data-fito-dose-panel]');
            if (!panel) return;
            panel.querySelector('[data-fito-recommended-display]').textContent = 'Seleccione producto';
            panel.querySelector('[data-fito-suggested-display]').textContent = '--';
            panel.querySelector('[data-fito-dose-unit]').textContent = '--';
            panel.querySelector('[data-fito-applied-dose]').value = '';
            panel.querySelector('[data-fito-applied-quantity]').value = '';
            this.toggleAdjustmentReason(form, false);
        },

        updateDosePanel(form, detail) {
            const panel = form.querySelector('[data-fito-dose-panel]');
            if (!panel) return;
            const appliedDoseInput = panel.querySelector('[data-fito-applied-dose]');
            const appliedQuantityInput = panel.querySelector('[data-fito-applied-quantity]');
            const recommendedDisplay = panel.querySelector('[data-fito-recommended-display]');
            const suggestedDisplay = panel.querySelector('[data-fito-suggested-display]');
            const unitDisplay = panel.querySelector('[data-fito-dose-unit]');
            const appliedDose = this.number(appliedDoseInput.value) > 0 && appliedDoseInput.dataset.userEdited === '1'
                ? this.number(appliedDoseInput.value)
                : detail.appliedDose;
            const suggested = detail.area * appliedDose;

            recommendedDisplay.textContent = `${this.formatNumber(detail.recommendedDose)} ${detail.doseUnit}/${detail.applicationUnit}`;
            unitDisplay.textContent = `${detail.doseUnit}/${detail.applicationUnit}`;
            suggestedDisplay.textContent = `${this.formatNumber(suggested)} ${detail.doseUnit}`;

            if (!appliedDoseInput.dataset.userEdited) {
                appliedDoseInput.value = detail.recommendedDose.toFixed(2);
            }
            if (!appliedQuantityInput.dataset.userEdited) {
                appliedQuantityInput.value = suggested.toFixed(2);
            }

            this.toggleAdjustmentReason(form, Math.abs(appliedDose - detail.recommendedDose) > 0.0001);
        },

        toggleAdjustmentReason(form, shouldShow) {
            const wrap = form.querySelector('[data-fito-adjustment-wrap]');
            const textarea = form.querySelector('[data-fito-adjustment-reason]');
            const warning = form.querySelector('[data-fito-adjustment-warning]');

            if (warning) {
                warning.hidden = !shouldShow;
            }

            if (wrap && textarea) {
                wrap.hidden = !shouldShow;
                textarea.required = shouldShow;
                if (!shouldShow) textarea.value = '';
            }
        },

        recalculateDoseFromInput(input) {
            const form = input.closest('form');
            const select = form?.querySelector('[data-fito-product-select]');
            const option = select?.selectedOptions?.[0];
            if (!form || !option || !option.value) return;
            const area = this.areaForForm(form);
            const recommendedDose = this.number(option.dataset.dose);
            if (area <= 0 || recommendedDose <= 0) return;
            this.updateDosePanel(form, {
                area,
                recommendedDose,
                appliedDose: this.number(input.value) || recommendedDose,
                doseUnit: option.dataset.doseUnit || option.dataset.unit || 'unidades',
                applicationUnit: option.dataset.applicationUnit || 'ha',
            });
        },

        refreshDoseHintsForForm(form) {
            if (!form) return;
            form.querySelectorAll('[data-fito-product-select]').forEach((select) => {
                this.refreshDoseHint(select);
            });
        },

        refreshProductSelects(root = document) {
            const scope = root instanceof Element || root === document ? root : document;
            scope.querySelectorAll('[data-fito-product-select]').forEach((select) => {
                this.refreshStockHint(select);
                this.refreshDoseHint(select);
            });
        },

        bindEdit(button) {
            const modal = document.getElementById('fitoEditModal');
            if (!modal) return;

            this.setValue('[data-fito-edit-id]', button.dataset.id, modal);
            this.setValue('[data-fito-edit-lote]', button.dataset.lote, modal);
            this.setValue('[data-fito-edit-tipo]', button.dataset.tipo, modal);
            this.setValue('[data-fito-edit-problema]', button.dataset.problema, modal);
            this.setValue('[data-fito-edit-severidad]', button.dataset.severidad, modal);
            this.setValue('[data-fito-edit-descripcion]', button.dataset.descripcion, modal);
            this.setValue('[data-fito-edit-producto]', button.dataset.idInsumo, modal);
            this.setValue('[data-fito-edit-fecha-deteccion]', button.dataset.fechaDeteccion, modal);
            this.setValue('[data-fito-edit-fecha-aplicacion]', button.dataset.fechaAplicacion, modal);
            this.setValue('[data-fito-edit-observaciones]', button.dataset.observaciones, modal);

            this.showModal('fitoEditModal');
        },

        bindTreatment(button) {
            const modal = document.getElementById('fitoTreatmentModal');
            if (!modal) return;

            const form = modal.querySelector('form');
            form?.reset();
            form?.querySelectorAll('[data-user-edited]').forEach((field) => {
                delete field.dataset.userEdited;
            });
            this.setValue('[data-fito-treatment-id]', button.dataset.fitoTreatment, modal);
            this.setValue('[data-fito-treatment-area]', button.dataset.area, modal);
            this.refreshDoseHintsForForm(form || modal);
            this.showModal('fitoTreatmentModal');
        },

        bindStatus(button) {
            const modal = document.getElementById('fitoStatusModal');
            if (!modal) return;

            this.setValue('[data-fito-status-id]', button.dataset.id, modal);
            this.setValue('[data-fito-status-estado]', button.dataset.estado, modal);
            this.showModal('fitoStatusModal');
        },

        showDetail(id) {
            const modal = document.getElementById('fitoDetailModal');
            const content = modal?.querySelector('[data-fito-detail-content]');
            if (!modal || !content) return;

            content.innerHTML = '<div class="text-center mt-4"><i class="fas fa-spinner fa-spin"></i><p>Cargando...</p></div>';
            this.showModal('fitoDetailModal');

            fetch(`fitosanitario_detalle.php?id=${encodeURIComponent(id)}`)
                .then((response) => response.text())
                .then((html) => {
                    content.innerHTML = html;
                    window.AppUI?.refresh(content);
                })
                .catch((error) => {
                    console.error('Error cargando detalle fitosanitario:', error);
                    content.innerHTML = '<div class="alert alert-danger">No se pudo cargar el detalle.</div>';
                });
        },

        bindActionButtons(root = document) {
            const scope = root instanceof Element || root === document ? root : document;

            scope.querySelectorAll('[data-fito-detail]').forEach((button) => {
                if (button.dataset.fitoActionBound === '1') return;
                button.dataset.fitoActionBound = '1';
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    this.showDetail(button.dataset.fitoDetail);
                });
            });

            scope.querySelectorAll('[data-fito-edit]').forEach((button) => {
                if (button.dataset.fitoActionBound === '1') return;
                button.dataset.fitoActionBound = '1';
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    this.bindEdit(button);
                });
            });

            scope.querySelectorAll('[data-fito-treatment]').forEach((button) => {
                if (button.dataset.fitoActionBound === '1') return;
                button.dataset.fitoActionBound = '1';
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    this.bindTreatment(button);
                });
            });

            scope.querySelectorAll('[data-fito-status]').forEach((button) => {
                if (button.dataset.fitoActionBound === '1') return;
                button.dataset.fitoActionBound = '1';
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    this.bindStatus(button);
                });
            });
        },

        submitAjaxForm(form) {
            const button = form.querySelector('button[type="submit"]');
            const original = button?.innerHTML;
            const data = new FormData(form);
            data.append('ajax', '1');

            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span> Procesando...</span>';
            }

            fetch('fitosanitario_acciones.php', {
                method: 'POST',
                body: data,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'fetch',
                },
            })
                .then((response) => response.json())
                .then((payload) => {
                    if (!payload.success) {
                        window.appNotify?.(payload.message || 'No se pudo completar la acción.', 'danger', { persist: true });
                        return;
                    }

                    window.appNotify?.(payload.message || 'Operación completada.', 'success', { persist: true });
                    document.querySelectorAll('.modal.show').forEach((modalElement) => {
                        window.bootstrap?.Modal.getInstance(modalElement)?.hide();
                    });

                    if (window.Admin?.loadFitosanitario) {
                        window.Admin.loadFitosanitario();
                    } else {
                        window.location.reload();
                    }
                })
                .catch((error) => {
                    console.error('Error procesando acción fitosanitaria:', error);
                    window.appNotify?.('Error de conexión al procesar la acción.', 'danger', { persist: true });
                })
                .finally(() => {
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = original;
                    }
                });
        },

        init() {
            this.setupSelects(document);
            if (window.Admin && window.AdminFormMethods?.setupDynamicForms) try {
                window.AdminFormMethods?.setupDynamicForms?.call(window.AdminFormMethods, document);
            } catch (error) {
                console.warn('No se pudieron inicializar formularios administrativos en fitosanitario:', error);
            }
            this.refreshProductSelects(document);
            this.bindActionButtons(document);

            if (document.documentElement.dataset.fitoBound === '1') return;
            document.documentElement.dataset.fitoBound = '1';

            document.addEventListener('click', (event) => {
                if (event.defaultPrevented) return;

                const detail = event.target.closest('[data-fito-detail]');
                if (detail) {
                    event.preventDefault();
                    this.showDetail(detail.dataset.fitoDetail);
                    return;
                }

                const edit = event.target.closest('[data-fito-edit]');
                if (edit) {
                    event.preventDefault();
                    this.bindEdit(edit);
                    return;
                }

                const treatment = event.target.closest('[data-fito-treatment]');
                if (treatment) {
                    event.preventDefault();
                    this.bindTreatment(treatment);
                    return;
                }

                const status = event.target.closest('[data-fito-status]');
                if (status) {
                    event.preventDefault();
                    this.bindStatus(status);
                }
            });

            document.addEventListener('submit', (event) => {
                const form = event.target.closest('[data-fito-ajax-form]');
                if (!form) return;
                event.preventDefault();
                this.submitAjaxForm(form);
            });

            document.addEventListener('change', (event) => {
                const select = event.target.closest('[data-fito-product-select]');
                if (select) {
                    this.refreshStockHint(select);
                    this.refreshDoseHint(select);
                    return;
                }

                const lotSelect = event.target.closest('select[name="id_lote"]');
                if (lotSelect) {
                    this.refreshDoseHintsForForm(lotSelect.closest('form'));
                }
            });

            document.addEventListener('input', (event) => {
                const field = event.target.closest('[data-fito-applied-quantity], [data-fito-applied-dose]');
                if (!field) return;
                field.dataset.userEdited = '1';
                if (field.matches('[data-fito-applied-dose]')) {
                    this.recalculateDoseFromInput(field);
                }
            });

            this.refreshProductSelects(document);
        },
    };

    window.Fitosanitario = Fito;
    document.addEventListener('DOMContentLoaded', () => Fito.init());
    Fito.init();
})(window, document);
