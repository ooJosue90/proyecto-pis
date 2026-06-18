(function (window, document) {
    'use strict';

    const PAGE_SIZE = 10;

    const AppUI = {
        init() {
            this.setupMotion();
            this.setupTheme();
            this.setupNotifications();
            this.setupShell();
            this.setupSidebarTabs();
            this.setupModalMotion();
            this.enhanceAll();
            this.observeDynamicContent();
        },

        setupMotion() {
            this.motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
            document.documentElement.classList.toggle('app-reduced-motion', this.motionQuery.matches);
            this.motionQuery.addEventListener?.('change', (event) => {
                document.documentElement.classList.toggle('app-reduced-motion', event.matches);
            });

            window.requestAnimationFrame(() => {
                window.requestAnimationFrame(() => document.body.classList.add('app-motion-ready'));
            });
        },

        motionAllowed() {
            return !this.motionQuery?.matches;
        },

        setupTheme() {
            const root = document.documentElement;

            if (document.body.classList.contains('auth-login-page')) {
                root.dataset.themePreference = 'light';
                root.dataset.theme = 'light';
                root.style.colorScheme = 'light';
                return;
            }

            const media = window.matchMedia('(prefers-color-scheme: dark)');
            const appearance = document.querySelector('[data-app-appearance]');
            const toggle = appearance?.querySelector('[data-app-theme-toggle]');
            const options = appearance?.querySelectorAll('[data-theme-value]') || [];
            const themes = {
                light: { label: 'Claro', icon: 'fa-sun' },
                dark: { label: 'Oscuro', icon: 'fa-moon' },
                night: { label: 'Noche', icon: 'fa-star' },
                auto: { label: 'Automático', icon: 'fa-circle-half-stroke' },
            };

            const readPreference = () => {
                try {
                    const saved = localStorage.getItem('theme') || localStorage.getItem('appTheme');
                    return themes[saved] ? saved : 'auto';
                } catch (error) {
                    return 'auto';
                }
            };

            const applyTheme = (preference, persist = false) => {
                const selected = themes[preference] ? preference : 'auto';
                const resolved = selected === 'auto' ? (media.matches ? 'dark' : 'light') : selected;
                root.dataset.themePreference = selected;
                root.dataset.theme = resolved;
                root.style.colorScheme = resolved === 'light' ? 'light' : 'dark';

                if (toggle) {
                    toggle.querySelector('[data-app-theme-label]').textContent = themes[selected].label;
                    toggle.querySelector('[data-app-theme-icon]').className = `fas ${themes[selected].icon}`;
                    toggle.setAttribute('aria-label', `Apariencia: ${themes[selected].label}`);
                }

                options.forEach((option) => {
                    const active = option.dataset.themeValue === selected;
                    option.classList.toggle('active', active);
                    option.setAttribute('aria-checked', active ? 'true' : 'false');
                });

                window.dispatchEvent(new CustomEvent('app:themechange', {
                    detail: { preference: selected, theme: resolved },
                }));

                if (!persist) return;
                try {
                    localStorage.setItem('theme', selected);
                    localStorage.removeItem('appTheme');
                } catch (error) {
                    // El tema permanece activo aunque el navegador bloquee localStorage.
                }
            };

            const closeMenu = () => {
                appearance?.classList.remove('open');
                document.body.classList.remove('app-appearance-open');
                toggle?.setAttribute('aria-expanded', 'false');
            };

            applyTheme(readPreference());

            toggle?.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                const open = appearance.classList.toggle('open');
                document.body.classList.toggle('app-appearance-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });

            appearance?.addEventListener('click', (event) => {
                const option = event.target.closest('[data-theme-value]');
                if (!option || !appearance.contains(option)) return;

                event.preventDefault();
                event.stopPropagation();
                applyTheme(option.dataset.themeValue, true);
                closeMenu();
                toggle?.focus();
            });

            document.addEventListener('click', (event) => {
                if (!appearance?.contains(event.target)) closeMenu();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') closeMenu();
            });
            media.addEventListener?.('change', () => {
                if (root.dataset.themePreference === 'auto') applyTheme('auto');
            });
            window.addEventListener('storage', (event) => {
                if (event.key === 'theme') applyTheme(readPreference());
            });
        },

        refresh(root = document) {
            this.bindNotifications(root);
            this.enhanceViews(root);
            this.enhanceCards(root);
            this.enhanceTables(root);
            this.enhanceForms(root);
            this.enhanceDateInputs(root);
            this.enhanceButtons(root);
            this.enhanceProgress(root);
            this.enhanceCounters(root);
            this.enhanceLoadingStates(root);
        },

        enhanceAll() {
            this.refresh(document);
        },

        enhanceDateInputs(root) {
            const inputs = [];
            if (root.matches?.('input[type="date"]')) inputs.push(root);
            root.querySelectorAll?.('input[type="date"]').forEach((input) => inputs.push(input));

            inputs.forEach((input) => {
                if (input.dataset.appDateBound === '1') return;
                input.dataset.appDateBound = '1';

                const wrapper = document.createElement('div');
                const trigger = document.createElement('button');
                wrapper.className = 'app-date-field';
                trigger.type = 'button';
                trigger.className = 'app-date-field__trigger';
                trigger.setAttribute('aria-label', 'Abrir calendario');
                trigger.innerHTML = '<i class="fas fa-calendar-days" aria-hidden="true"></i>';

                input.parentNode.insertBefore(wrapper, input);
                wrapper.append(input, trigger);
                input.classList.add('app-date-field__input');
                input.type = 'text';
                input.inputMode = 'none';
                input.pattern = '\\d{4}-\\d{2}-\\d{2}';
                input.setAttribute('autocomplete', 'off');
                input.setAttribute('aria-haspopup', 'dialog');
                input.addEventListener('beforeinput', (event) => event.preventDefault());
                input.addEventListener('paste', (event) => event.preventDefault());

                const open = () => this.openDatePicker(input, trigger);
                input.addEventListener('click', open);
                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        open();
                    }
                });
                trigger.addEventListener('click', open);
            });
        },

        openDatePicker(input, trigger) {
            this.datePickerState ||= {};
            const state = this.datePickerState;
            const parseDate = (value) => {
                const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
                if (!match) return null;
                const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
                return Number.isNaN(date.getTime()) ? null : date;
            };
            const formatValue = (date) => [
                date.getFullYear(),
                String(date.getMonth() + 1).padStart(2, '0'),
                String(date.getDate()).padStart(2, '0'),
            ].join('-');
            const selected = parseDate(input.value);
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

            if (!state.element) {
                const picker = document.createElement('div');
                picker.className = 'app-date-picker';
                picker.setAttribute('role', 'dialog');
                picker.setAttribute('aria-modal', 'false');
                picker.setAttribute('aria-label', 'Seleccionar fecha');
                document.body.appendChild(picker);
                state.element = picker;

                document.addEventListener('pointerdown', (event) => {
                    if (!state.input || picker.contains(event.target)) return;
                    if (state.input.closest('.app-date-field')?.contains(event.target)) return;
                    this.closeDatePicker();
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && state.input) {
                        this.closeDatePicker();
                        state.trigger?.focus();
                    }
                });
                window.addEventListener('resize', () => this.positionDatePicker());
                window.addEventListener('scroll', () => {
                    if (state.input) this.closeDatePicker();
                }, true);
            }

            state.input = input;
            state.trigger = trigger;
            state.view = new Date(
                (selected || today).getFullYear(),
                (selected || today).getMonth(),
                1
            );
            state.parseDate = parseDate;
            state.formatValue = formatValue;
            this.renderDatePicker();
            state.element.classList.add('is-open');
            input.closest('.app-date-field')?.classList.add('is-open');
            this.positionDatePicker();
        },

        closeDatePicker() {
            const state = this.datePickerState;
            if (!state?.element) return;
            state.input?.closest('.app-date-field')?.classList.remove('is-open');
            state.element.classList.remove('is-open');
            state.input = null;
            state.trigger = null;
        },

        positionDatePicker() {
            const state = this.datePickerState;
            if (!state?.input || !state.element?.classList.contains('is-open')) return;
            const rect = state.input.closest('.app-date-field').getBoundingClientRect();
            const width = Math.min(340, window.innerWidth - 24);
            const height = state.element.offsetHeight || 390;
            const spaceBelow = window.innerHeight - rect.bottom;
            const top = spaceBelow >= height + 12
                ? rect.bottom + 8
                : Math.max(12, rect.top - height - 8);
            const left = Math.min(
                Math.max(12, rect.left),
                Math.max(12, window.innerWidth - width - 12)
            );

            Object.assign(state.element.style, {
                width: `${width}px`,
                top: `${top}px`,
                left: `${left}px`,
            });
        },

        renderDatePicker() {
            const state = this.datePickerState;
            if (!state?.input || !state.element) return;

            const input = state.input;
            const view = state.view;
            const selected = state.parseDate(input.value);
            const min = state.parseDate(input.getAttribute('min'));
            const max = state.parseDate(input.getAttribute('max'));
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const monthLabel = new Intl.DateTimeFormat('es-EC', {
                month: 'long',
                year: 'numeric',
            }).format(view);
            const firstDay = new Date(view.getFullYear(), view.getMonth(), 1);
            const mondayOffset = (firstDay.getDay() + 6) % 7;
            const gridStart = new Date(view.getFullYear(), view.getMonth(), 1 - mondayOffset);
            const sameDay = (first, second) => first && second
                && first.getFullYear() === second.getFullYear()
                && first.getMonth() === second.getMonth()
                && first.getDate() === second.getDate();
            const isDisabled = (date) => (min && date < min) || (max && date > max);

            const days = Array.from({ length: 42 }, (_, index) => {
                const date = new Date(gridStart);
                date.setDate(gridStart.getDate() + index);
                const outside = date.getMonth() !== view.getMonth();
                const disabled = isDisabled(date);
                const classes = [
                    'app-date-picker__day',
                    outside ? 'is-outside' : '',
                    sameDay(date, today) ? 'is-today' : '',
                    sameDay(date, selected) ? 'is-selected' : '',
                ].filter(Boolean).join(' ');

                return `<button type="button" class="${classes}" data-date="${state.formatValue(date)}" ${disabled ? 'disabled' : ''} aria-label="${date.toLocaleDateString('es-EC')}">${date.getDate()}</button>`;
            }).join('');

            state.element.innerHTML = `
                <div class="app-date-picker__header">
                    <div>
                        <span>Seleccionar fecha</span>
                        <strong>${monthLabel}</strong>
                    </div>
                    <div class="app-date-picker__nav">
                        <button type="button" data-date-prev aria-label="Mes anterior"><i class="fas fa-chevron-left"></i></button>
                        <button type="button" data-date-next aria-label="Mes siguiente"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="app-date-picker__weekdays" aria-hidden="true">
                    <span>Lu</span><span>Ma</span><span>Mi</span><span>Ju</span><span>Vi</span><span>Sá</span><span>Do</span>
                </div>
                <div class="app-date-picker__days">${days}</div>
                <div class="app-date-picker__footer">
                    <button type="button" data-date-clear><i class="fas fa-eraser"></i> Borrar</button>
                    <button type="button" data-date-today><i class="fas fa-calendar-check"></i> Hoy</button>
                </div>
            `;

            state.element.querySelector('[data-date-prev]').addEventListener('click', () => {
                state.view = new Date(view.getFullYear(), view.getMonth() - 1, 1);
                this.renderDatePicker();
                this.positionDatePicker();
            });
            state.element.querySelector('[data-date-next]').addEventListener('click', () => {
                state.view = new Date(view.getFullYear(), view.getMonth() + 1, 1);
                this.renderDatePicker();
                this.positionDatePicker();
            });
            state.element.querySelector('[data-date-clear]').addEventListener('click', () => {
                input.value = '';
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                this.closeDatePicker();
                input.focus();
            });
            state.element.querySelector('[data-date-today]').addEventListener('click', () => {
                if (isDisabled(today)) return;
                input.value = state.formatValue(today);
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                this.closeDatePicker();
                input.focus();
            });
            state.element.querySelectorAll('[data-date]').forEach((button) => {
                button.addEventListener('click', () => {
                    input.value = button.dataset.date;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    this.closeDatePicker();
                    input.focus();
                });
            });
        },

        setupNotifications() {
            window.appNotify = (message, type = 'info', options = {}) =>
                this.notify(message, type, options);

            window.alert = (message) => {
                const text = String(message || '');
                const type = /error|inválid|denegad|no se pudo|obligatori|mayor al stock/i.test(text)
                    ? 'danger'
                    : 'success';
                this.notify(text, type, { persist: true });
            };

            try {
                const pending = JSON.parse(sessionStorage.getItem('appPendingNotifications') || '[]');
                sessionStorage.removeItem('appPendingNotifications');
                pending.forEach((notification) => {
                    this.notify(notification.message, notification.type, { persist: false });
                });
            } catch (error) {
                sessionStorage.removeItem('appPendingNotifications');
            }

            const url = new URL(window.location.href);
            const urlMessage = url.searchParams.get('notification');
            if (urlMessage) {
                this.notify(urlMessage, url.searchParams.get('type') || 'info');
                url.searchParams.delete('notification');
                url.searchParams.delete('type');
                window.history.replaceState({}, document.title, `${url.pathname}${url.search}${url.hash}`);
            }
        },

        notificationStack() {
            let stack = document.querySelector('[data-app-notification-stack]');
            if (stack) return stack;

            stack = document.createElement('div');
            stack.className = 'app-notification-stack';
            stack.dataset.appNotificationStack = '1';
            stack.setAttribute('aria-live', 'polite');
            stack.setAttribute('aria-atomic', 'true');
            document.body.appendChild(stack);
            return stack;
        },

        notify(message, type = 'info', options = {}) {
            const text = String(message || '').trim();
            if (!text) return null;

            const allowedTypes = ['success', 'danger', 'warning', 'info'];
            const notificationType = allowedTypes.includes(type) ? type : 'info';
            const icons = {
                success: 'fa-check',
                danger: 'fa-exclamation',
                warning: 'fa-exclamation',
                info: 'fa-info',
            };
            const titles = {
                success: 'Operación completada',
                danger: 'No se pudo completar',
                warning: 'Requiere atención',
                info: 'Información',
            };

            if (options.persist) {
                try {
                    const notificationId = `${Date.now()}-${Math.random().toString(16).slice(2)}`;
                    const pending = JSON.parse(sessionStorage.getItem('appPendingNotifications') || '[]');
                    pending.push({ id: notificationId, message: text, type: notificationType });
                    sessionStorage.setItem('appPendingNotifications', JSON.stringify(pending.slice(-4)));

                    window.setTimeout(() => {
                        try {
                            const current = JSON.parse(sessionStorage.getItem('appPendingNotifications') || '[]');
                            const remaining = current.filter((item) => item.id !== notificationId);
                            if (remaining.length) {
                                sessionStorage.setItem('appPendingNotifications', JSON.stringify(remaining));
                            } else {
                                sessionStorage.removeItem('appPendingNotifications');
                            }
                        } catch (error) {
                            sessionStorage.removeItem('appPendingNotifications');
                        }
                    }, 1000);
                } catch (error) {
                    // La notificación actual sigue funcionando aunque el navegador bloquee sessionStorage.
                }
            }

            const notification = document.createElement('div');
            notification.className = `app-notification app-notification--${notificationType}`;
            notification.dataset.appNotification = '1';
            notification.dataset.duration = String(options.duration || 4500);
            notification.setAttribute('role', notificationType === 'danger' ? 'alert' : 'status');
            notification.innerHTML = `
                <span class="app-notification__icon" aria-hidden="true">
                    <i class="fas ${icons[notificationType]}"></i>
                </span>
                <span class="app-notification__content">
                    <strong class="app-notification__title">${titles[notificationType]}</strong>
                    <span class="app-notification__message"></span>
                </span>
                <button class="app-notification__close" type="button" data-app-notification-close aria-label="Cerrar notificación">
                    <i class="fas fa-xmark"></i>
                </button>
            `;
            notification.querySelector('.app-notification__message').textContent = text;
            this.notificationStack().appendChild(notification);
            this.bindNotification(notification);
            return notification;
        },

        bindNotifications(root) {
            root.querySelectorAll('[data-app-notification]').forEach((notification) => {
                this.bindNotification(notification);
            });
        },

        bindNotification(notification) {
            if (notification.dataset.appNotificationBound === '1') return;
            notification.dataset.appNotificationBound = '1';

            const duration = Math.max(1500, Number(notification.dataset.duration || 4500));
            const close = () => {
                if (notification.dataset.closing === '1') return;
                notification.dataset.closing = '1';
                notification.classList.add('app-notification--closing');
                window.setTimeout(() => notification.remove(), 260);
            };

            notification.querySelector('[data-app-notification-close]')
                ?.addEventListener('click', close);

            window.setTimeout(close, duration);
        },

        setupShell() {
            if (!document.querySelector('.app-sidebar')) return;

            document.body.classList.add('app-shell-ready');
            const mobileSidebarQuery = window.matchMedia('(max-width: 991px)');

            const syncSidebarMode = () => {
                if (mobileSidebarQuery.matches) {
                    document.body.classList.remove('app-sidebar-collapsed');
                    return;
                }

                document.body.classList.toggle(
                    'app-sidebar-collapsed',
                    localStorage.getItem('appSidebarCollapsed') === '1'
                );
                document.body.classList.remove('app-mobile-nav-open');
            };

            syncSidebarMode();
            mobileSidebarQuery.addEventListener?.('change', syncSidebarMode);

            const currentPath = window.location.pathname.split('/').pop() || 'index.html';
            document.querySelectorAll('.app-sidebar-link[href]').forEach((link) => {
                const linkPath = link.getAttribute('href').split('/').pop();
                if (linkPath === currentPath) link.classList.add('active');
            });

            document.querySelectorAll('[data-app-sidebar-toggle]').forEach((button) => {
                if (button.dataset.appSidebarToggleBound === '1') return;
                button.dataset.appSidebarToggleBound = '1';

                button.addEventListener('click', () => {
                    if (mobileSidebarQuery.matches) {
                        document.body.classList.toggle('app-mobile-nav-open');
                        return;
                    }

                    document.body.classList.toggle('app-sidebar-collapsed');
                    localStorage.setItem(
                        'appSidebarCollapsed',
                        document.body.classList.contains('app-sidebar-collapsed') ? '1' : '0'
                    );
                });
            });

            document.querySelectorAll('[data-app-mobile-close]').forEach((element) => {
                if (element.dataset.appMobileCloseBound === '1') return;
                element.dataset.appMobileCloseBound = '1';

                element.addEventListener('click', () => document.body.classList.remove('app-mobile-nav-open'));
            });
        },

        setupSidebarTabs() {
            if (document.documentElement.dataset.appSidebarTabsBound === '1') return;
            document.documentElement.dataset.appSidebarTabsBound = '1';

            document.addEventListener('click', (event) => {
                const trigger = event.target.closest('[data-app-tab]');
                if (!trigger) return;

                event.preventDefault();
                const target = trigger.getAttribute('data-app-tab');
                const tabPane = document.querySelector(target);
                if (!tabPane) return;

                this.activatePane(tabPane);

                // Disparar evento personalizado para gatillar los cargadores AJAX de admin.js
                tabPane.dispatchEvent(new CustomEvent('shown.bs.tab', { bubbles: true, detail: { target: tabPane } }));
                
                this.markActiveSidebar(trigger);
                document.body.classList.remove('app-mobile-nav-open');
            });

            document.querySelectorAll('[data-bs-toggle="tab"]').forEach((tab) => {
                tab.addEventListener('shown.bs.tab', (event) => {
                    const target = event.target.getAttribute('data-bs-target');
                    const sidebarTrigger = document.querySelector(`[data-app-tab="${target}"]`);
                    if (sidebarTrigger) this.markActiveSidebar(sidebarTrigger);
                });
            });

            const activateHashTarget = () => {
                const hashTarget = window.location.hash;
                const hashPane = hashTarget ? document.querySelector(hashTarget) : null;
                const hashTrigger = hashTarget
                    ? document.querySelector(`[data-app-tab="${hashTarget}"]`)
                    : null;

                if (!hashPane || !hashTrigger) return;

                this.activatePane(hashPane);
                hashPane.dispatchEvent(new CustomEvent('shown.bs.tab', {
                    bubbles: true,
                    detail: { target: hashPane },
                }));
                this.markActiveSidebar(hashTrigger);
            };

            window.addEventListener('hashchange', activateHashTarget);
            window.setTimeout(activateHashTarget, 0);
        },

        activatePane(tabPane) {
            const tabContent = tabPane.closest('.tab-content');
            if (!tabContent) {
                tabPane.classList.add('show', 'active');
                return;
            }

            Array.from(tabContent.children).forEach((pane) => {
                if (!pane.classList || !pane.classList.contains('tab-pane')) return;
                pane.classList.toggle('show', pane === tabPane);
                pane.classList.toggle('active', pane === tabPane);
            });

            this.replayEntrance(tabPane);
        },

        markActiveSidebar(activeItem) {
            const group = activeItem.closest('.app-sidebar-nav');
            if (!group) return;

            group.querySelectorAll('.app-sidebar-link').forEach((item) => item.classList.remove('active'));
            activeItem.classList.add('active');
        },

        observeDynamicContent() {
            if (document.documentElement.dataset.appDynamicObserverBound === '1') return;
            document.documentElement.dataset.appDynamicObserverBound = '1';

            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            this.refresh(node);
                        }
                    });
                });
            });

            observer.observe(document.body, { childList: true, subtree: true });
        },

        setupModalMotion() {
            document.addEventListener('show.bs.modal', (event) => {
                event.target.classList.add('app-modal-entering');
            });
            document.addEventListener('shown.bs.modal', (event) => {
                event.target.classList.remove('app-modal-entering');
            });
        },

        replayEntrance(element) {
            if (!element || !this.motionAllowed()) return;
            element.classList.remove('app-view-enter');
            void element.offsetWidth;
            element.classList.add('app-view-enter');
        },

        enhanceViews(root) {
            const candidates = [];
            if (root.matches?.('.tab-pane.active, main, .app-main')) candidates.push(root);
            root.querySelectorAll?.('.tab-pane.active, main, .app-main').forEach((element) => {
                candidates.push(element);
            });

            candidates.forEach((element) => {
                if (element.dataset.appView === '1') return;
                element.dataset.appView = '1';
                element.classList.add('app-view-enter');
                element.addEventListener('animationend', (event) => {
                    if (event.animationName === 'appViewIn') {
                        element.classList.remove('app-view-enter');
                    }
                });
            });
        },

        enhanceCards(root) {
            const cards = [];
            if (root.matches?.('.card:not([data-app-card])')) cards.push(root);
            root.querySelectorAll?.('.card:not([data-app-card])').forEach((card) => cards.push(card));

            cards.forEach((card, index) => {
                card.dataset.appCard = '1';
                card.classList.add('app-fade-in');
                card.style.setProperty('--app-stagger-delay', `${Math.min(index * 28, 168)}ms`);
            });
        },

        enhanceForms(root) {
            root.querySelectorAll('form:not([data-app-form])').forEach((form) => {
                form.dataset.appForm = '1';

                form.querySelectorAll('input[required], select[required], textarea[required]').forEach((field) => {
                    field.addEventListener('blur', () => {
                        field.classList.toggle('is-invalid', !field.checkValidity());
                        field.classList.toggle('is-valid', field.checkValidity());
                    });
                    field.addEventListener('input', () => {
                        if (field.classList.contains('is-invalid') && field.checkValidity()) {
                            field.classList.remove('is-invalid');
                            field.classList.add('is-valid');
                        }
                    });
                });
            });
        },

        enhanceButtons(root) {
            root.querySelectorAll('form:not([data-app-loading-bound])').forEach((form) => {
                form.dataset.appLoadingBound = '1';
                form.addEventListener('submit', () => {
                    const button = form.querySelector('button[type="submit"]');
                    if (!button || button.dataset.skipLoading === '1') return;

                    button.dataset.originalHtml = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Procesando';
                    button.disabled = true;
                });
            });

            root.querySelectorAll?.('.app-button-ripple').forEach((ripple) => ripple.remove());
            root.querySelectorAll?.('[data-app-ripple]').forEach((button) => {
                button.removeAttribute('data-app-ripple');
            });
        },

        enhanceProgress(root) {
            root.querySelectorAll('.progress[data-progress]:not([data-app-progress])').forEach((progress) => {
                progress.dataset.appProgress = '1';
                const value = Math.max(0, Math.min(100, Number(progress.dataset.progress || 0)));
                const bar = progress.querySelector('.progress-bar');
                if (bar) bar.style.width = `${value}%`;
            });
        },

        enhanceTables(root) {
            root.querySelectorAll('.table').forEach((table) => {
                if (table.dataset.appTable === '1') return;
                if (table.closest('.modal')) return;

                const tbody = table.tBodies[0];
                if (!tbody || tbody.rows.length < 3) return;

                table.dataset.appTable = '1';
                const rows = Array.from(tbody.rows);
                let currentPage = 1;
                let query = '';
                let status = '';
                const normalizeHeading = (value) => String(value || '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .trim()
                    .toLowerCase();
                const headings = Array.from(table.tHead?.rows[0]?.cells || []);
                const filterColumnIndex = headings.findIndex((heading) => {
                    const value = normalizeHeading(heading.textContent);
                    return value === 'estado' || value === 'rol';
                });
                const filterHeading = filterColumnIndex >= 0
                    ? normalizeHeading(headings[filterColumnIndex].textContent)
                    : '';
                const filterAllLabel = filterHeading === 'rol' ? 'Todos los roles' : 'Todos los estados';

                const wrapper = table.closest('.table-responsive') || table.parentElement;
                const tableOwner = table.closest('[id]')?.id || '';
                const tableHost = wrapper.parentElement;
                if (tableOwner) {
                    tableHost.querySelectorAll(
                        `:scope > .app-table-tools[data-app-table-owner="${tableOwner}"], ` +
                        `:scope > .app-table-pagination[data-app-table-owner="${tableOwner}"]`
                    ).forEach(element => element.remove());
                    document.querySelectorAll(
                        `.app-table-filter__menu[data-app-table-owner="${tableOwner}"]`
                    ).forEach(element => element.remove());
                }
                const tools = document.createElement('div');
                tools.className = 'app-table-tools';
                if (tableOwner) tools.dataset.appTableOwner = tableOwner;
                tools.innerHTML = `
                    <div class="app-table-tools-left">
                        <div class="input-group app-table-search">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="search" class="form-control" placeholder="Buscar en la tabla">
                        </div>
                    </div>
                    <div class="app-table-tools-right">
                        <div class="app-table-filter">
                            <button type="button" class="app-table-filter__button" aria-haspopup="listbox" aria-expanded="false">
                                <i class="fas fa-filter" aria-hidden="true"></i>
                                <span>${filterAllLabel}</span>
                                <i class="fas fa-chevron-down" aria-hidden="true"></i>
                            </button>
                            <div class="app-table-filter__menu" role="listbox" aria-label="Filtrar por ${filterHeading || 'estado'}"></div>
                        </div>
                    </div>
                `;

                wrapper.parentElement.insertBefore(tools, wrapper);

                const pagination = document.createElement('div');
                pagination.className = 'app-table-pagination';
                if (tableOwner) pagination.dataset.appTableOwner = tableOwner;
                pagination.innerHTML = `
                    <span class="app-table-page-info"></span>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-prev><i class="fas fa-chevron-left"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-next><i class="fas fa-chevron-right"></i></button>
                `;
                wrapper.parentElement.insertBefore(pagination, wrapper.nextSibling);

                const searchInput = tools.querySelector('input[type="search"]');
                const statusFilter = tools.querySelector('.app-table-filter');
                const statusButton = tools.querySelector('.app-table-filter__button');
                const statusLabel = statusButton.querySelector('span');
                const statusMenu = tools.querySelector('.app-table-filter__menu');
                if (tableOwner) statusMenu.dataset.appTableOwner = tableOwner;
                const statusValues = new Set();
                const statusTone = (value) => {
                    const normalized = normalizeHeading(value);
                    if (!normalized) return 'all';
                    if (/pendiente|espera|revision|cosecha|warning/.test(normalized)) return 'warning';
                    if (/aprobado|procesando|informacion|administrador/.test(normalized)) return 'info';
                    if (/entregado|activo|finalizado|completado|agricultor/.test(normalized)) return 'success';
                    if (/rechazado|error|critico/.test(normalized)) return 'danger';
                    if (/cancelado|inactivo|bodeguero/.test(normalized)) return 'neutral';
                    return 'default';
                };
                const setCurrentStatus = (value, label) => {
                    statusLabel.textContent = label;
                    statusLabel.className = 'app-table-filter__current';
                };
                const positionStatusMenu = () => {
                    const rect = statusButton.getBoundingClientRect();
                    const viewportGap = 12;
                    const desiredHeight = Math.min(statusMenu.scrollHeight || 280, 280);
                    const spaceBelow = window.innerHeight - rect.bottom - viewportGap;
                    const spaceAbove = rect.top - viewportGap;
                    const openAbove = spaceBelow < Math.min(desiredHeight, 190) && spaceAbove > spaceBelow;
                    const availableHeight = Math.max(120, openAbove ? spaceAbove - 8 : spaceBelow - 8);
                    const width = Math.min(Math.max(rect.width, 270), window.innerWidth - (viewportGap * 2));
                    const left = Math.min(
                        Math.max(viewportGap, rect.right - width),
                        window.innerWidth - width - viewportGap
                    );

                    statusMenu.style.left = `${Math.round(left)}px`;
                    statusMenu.style.width = `${Math.round(width)}px`;
                    statusMenu.style.maxHeight = `${Math.min(280, availableHeight)}px`;
                    statusMenu.style.top = openAbove
                        ? `${Math.round(rect.top - Math.min(desiredHeight, availableHeight) - 7)}px`
                        : `${Math.round(rect.bottom + 7)}px`;
                    statusMenu.dataset.placement = openAbove ? 'top' : 'bottom';
                };
                const closeStatusMenu = () => {
                    statusFilter.classList.remove('is-open');
                    statusMenu.classList.remove('is-open');
                    statusButton.setAttribute('aria-expanded', 'false');
                };
                const getRowStatus = (row) => {
                    if (filterColumnIndex < 0) return '';
                    const cell = row.cells[filterColumnIndex];
                    const statusElement = cell?.querySelector('.app-table-status-capsule, .badge');
                    return (statusElement?.textContent || cell?.textContent || '').trim();
                };

                rows.forEach((row) => {
                    const value = getRowStatus(row);
                    if (filterHeading === 'estado' && value) {
                        const cell = row.cells[filterColumnIndex];
                        let capsule = cell.querySelector('.app-table-status-capsule');

                        if (!capsule) {
                            capsule = document.createElement('span');
                            cell.replaceChildren(capsule);
                        }

                        capsule.className = `app-table-status-capsule app-table-status-capsule--${statusTone(value)}`;
                        capsule.textContent = value;
                    }
                    if (value) statusValues.add(value);
                });

                const addFilterOption = (value, label) => {
                    const option = document.createElement('button');
                    const optionLabel = document.createElement('span');
                    const checkIcon = document.createElement('i');
                    option.type = 'button';
                    option.className = 'app-table-filter__option';
                    option.dataset.value = value;
                    option.setAttribute('role', 'option');
                    option.setAttribute('aria-selected', value === '' ? 'true' : 'false');
                    optionLabel.className = 'app-table-filter__option-label';
                    optionLabel.textContent = label;
                    checkIcon.className = 'fas fa-check';
                    checkIcon.setAttribute('aria-hidden', 'true');
                    option.append(optionLabel, checkIcon);
                    statusMenu.appendChild(option);
                };

                if (filterColumnIndex < 0 || statusValues.size === 0) {
                    statusFilter.remove();
                } else {
                    addFilterOption('', filterAllLabel);
                    Array.from(statusValues)
                        .sort((first, second) => first.localeCompare(second, 'es'))
                        .forEach((value) => addFilterOption(value.toLowerCase(), value));
                    document.body.appendChild(statusMenu);
                    setCurrentStatus('', filterAllLabel);
                }

                const getFilteredRows = () => rows.filter((row) => {
                    const text = row.textContent.toLowerCase();
                    const matchesQuery = !query || text.includes(query);
                    const matchesStatus = !status || getRowStatus(row).toLowerCase() === status;
                    return matchesQuery && matchesStatus;
                });

                const render = () => {
                    const filtered = getFilteredRows();
                    const pages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
                    currentPage = Math.min(currentPage, pages);
                    const start = (currentPage - 1) * PAGE_SIZE;
                    const visible = new Set(filtered.slice(start, start + PAGE_SIZE));

                    rows.forEach((row) => {
                        row.style.display = visible.has(row) ? '' : 'none';
                    });

                    if (this.motionAllowed()) {
                        Array.from(visible).forEach((row, index) => {
                            row.classList.remove('app-row-enter');
                            row.style.setProperty('--app-row-delay', `${Math.min(index * 24, 144)}ms`);
                            void row.offsetWidth;
                            row.classList.add('app-row-enter');
                        });
                    }

                    pagination.querySelector('.app-table-page-info').textContent =
                        `${filtered.length} registros · Página ${currentPage} de ${pages}`;
                    pagination.querySelector('[data-prev]').disabled = currentPage === 1;
                    pagination.querySelector('[data-next]').disabled = currentPage === pages;
                };

                searchInput.addEventListener('input', () => {
                    query = searchInput.value.trim().toLowerCase();
                    currentPage = 1;
                    render();
                });

                if (statusFilter.isConnected) {
                    statusButton.addEventListener('click', () => {
                        const willOpen = !statusFilter.classList.contains('is-open');
                        statusFilter.classList.toggle('is-open', willOpen);
                        statusMenu.classList.toggle('is-open', willOpen);
                        statusButton.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                        if (willOpen) positionStatusMenu();
                    });

                    statusMenu.addEventListener('click', (event) => {
                        const option = event.target.closest('.app-table-filter__option');
                        if (!option) return;

                        status = option.dataset.value || '';
                        setCurrentStatus(status, option.querySelector('span').textContent);
                        statusMenu.querySelectorAll('.app-table-filter__option').forEach(item => {
                            const isSelected = item === option;
                            item.classList.toggle('is-selected', isSelected);
                            item.setAttribute('aria-selected', isSelected ? 'true' : 'false');
                        });
                        closeStatusMenu();
                        currentPage = 1;
                        render();
                    });

                    const handleFilterEscape = (event) => {
                        if (event.key === 'Escape') {
                            closeStatusMenu();
                            statusButton.focus();
                        }
                    };
                    statusFilter.addEventListener('keydown', handleFilterEscape);
                    statusMenu.addEventListener('keydown', handleFilterEscape);

                    document.addEventListener('click', (event) => {
                        if (!statusFilter.contains(event.target) && !statusMenu.contains(event.target)) {
                            closeStatusMenu();
                        }
                    });

                    window.addEventListener('resize', () => {
                        if (statusFilter.classList.contains('is-open')) positionStatusMenu();
                    });
                    window.addEventListener('scroll', () => {
                        if (statusFilter.classList.contains('is-open')) positionStatusMenu();
                    }, true);
                }

                pagination.querySelector('[data-prev]').addEventListener('click', () => {
                    currentPage = Math.max(1, currentPage - 1);
                    render();
                });

                pagination.querySelector('[data-next]').addEventListener('click', () => {
                    currentPage += 1;
                    render();
                });

                render();
            });
        },

        enhanceCounters(root) {
            const counters = [];
            const selector = '[data-app-counter], .stats-card h3, .stats-card h4, .summary-card h3, .summary-card h4';
            if (root.matches?.(selector)) counters.push(root);
            root.querySelectorAll?.(selector).forEach((counter) => counters.push(counter));

            counters.forEach((counter) => {
                if (counter.dataset.appCounterBound === '1') return;

                const match = counter.textContent.trim().match(/^([^\d-]*)(-?\d+(?:[.,]\d+)?)(.*)$/);
                if (!match) return;

                counter.dataset.appCounterBound = '1';
                const prefix = match[1];
                const rawValue = match[2];
                const suffix = match[3];
                const decimalSeparator = rawValue.includes(',') ? ',' : '.';
                const decimals = rawValue.includes(decimalSeparator)
                    ? rawValue.split(decimalSeparator)[1].length
                    : 0;
                const target = Number(rawValue.replace(',', '.'));
                if (!Number.isFinite(target) || !this.motionAllowed()) return;

                const animate = () => {
                    const startedAt = performance.now();
                    const duration = 380;
                    const step = (now) => {
                        const progress = Math.min(1, (now - startedAt) / duration);
                        const eased = 1 - Math.pow(1 - progress, 3);
                        const value = target * eased;
                        const formatted = value.toFixed(decimals).replace('.', decimalSeparator);
                        counter.textContent = `${prefix}${formatted}${suffix}`;
                        if (progress < 1) window.requestAnimationFrame(step);
                    };
                    window.requestAnimationFrame(step);
                };

                if ('IntersectionObserver' in window) {
                    const observer = new IntersectionObserver((entries) => {
                        if (!entries.some((entry) => entry.isIntersecting)) return;
                        observer.disconnect();
                        animate();
                    }, { threshold: 0.35 });
                    observer.observe(counter);
                } else {
                    animate();
                }
            });
        },

        enhanceLoadingStates(root) {
            if (root.matches?.('.fa-spinner, .fa-circle-notch')) {
                const state = root.closest('.text-center');
                if (state && !state.closest('button')) state.classList.add('app-loading-state');
            }

            root.querySelectorAll?.('.fa-spinner, .fa-circle-notch').forEach((spinner) => {
                const state = spinner.closest('.text-center');
                if (state && !state.closest('button')) state.classList.add('app-loading-state');
            });
        },
    };

    window.AppUI = AppUI;
    document.addEventListener('DOMContentLoaded', () => AppUI.init());
})(window, document);
