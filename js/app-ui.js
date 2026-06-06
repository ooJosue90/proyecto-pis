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
            this.enhanceButtons(root);
            this.enhanceProgress(root);
            this.enhanceCounters(root);
            this.enhanceLoadingStates(root);
        },

        enhanceAll() {
            this.refresh(document);
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

            if (localStorage.getItem('appSidebarCollapsed') === '1') {
                document.body.classList.add('app-sidebar-collapsed');
            }

            const currentPath = window.location.pathname.split('/').pop() || 'index.html';
            document.querySelectorAll('.app-sidebar-link[href]').forEach((link) => {
                const linkPath = link.getAttribute('href').split('/').pop();
                if (linkPath === currentPath) link.classList.add('active');
            });

            document.querySelectorAll('[data-app-sidebar-toggle]').forEach((button) => {
                if (button.dataset.appSidebarToggleBound === '1') return;
                button.dataset.appSidebarToggleBound = '1';

                button.addEventListener('click', () => {
                    if (window.matchMedia('(max-width: 991px)').matches) {
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

            const buttons = [];
            const buttonSelector = '.btn:not([data-app-ripple]), .app-icon-button:not([data-app-ripple])';
            if (root.matches?.(buttonSelector)) buttons.push(root);
            root.querySelectorAll?.(buttonSelector).forEach((button) => buttons.push(button));

            buttons.forEach((button) => {
                    button.dataset.appRipple = '1';
                    button.addEventListener('pointerdown', (event) => {
                        if (!this.motionAllowed() || button.disabled) return;

                        const rect = button.getBoundingClientRect();
                        const ripple = document.createElement('span');
                        const size = Math.max(rect.width, rect.height) * 1.4;
                        ripple.className = 'app-button-ripple';
                        ripple.style.width = `${size}px`;
                        ripple.style.height = `${size}px`;
                        ripple.style.left = `${event.clientX - rect.left - size / 2}px`;
                        ripple.style.top = `${event.clientY - rect.top - size / 2}px`;
                        button.appendChild(ripple);
                        ripple.addEventListener('animationend', () => ripple.remove(), { once: true });
                    });
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

                const wrapper = table.closest('.table-responsive') || table.parentElement;
                const tools = document.createElement('div');
                tools.className = 'app-table-tools';
                tools.innerHTML = `
                    <div class="app-table-tools-left">
                        <div class="input-group app-table-search">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="search" class="form-control" placeholder="Buscar en la tabla">
                        </div>
                    </div>
                    <div class="app-table-tools-right">
                        <select class="form-select form-select-sm app-table-status" aria-label="Filtrar por estado">
                            <option value="">Todos los estados</option>
                        </select>
                    </div>
                `;

                wrapper.parentElement.insertBefore(tools, wrapper);

                const pagination = document.createElement('div');
                pagination.className = 'app-table-pagination';
                pagination.innerHTML = `
                    <span class="app-table-page-info"></span>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-prev><i class="fas fa-chevron-left"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-next><i class="fas fa-chevron-right"></i></button>
                `;
                wrapper.parentElement.insertBefore(pagination, wrapper.nextSibling);

                const searchInput = tools.querySelector('input[type="search"]');
                const statusSelect = tools.querySelector('.app-table-status');
                const statusValues = new Set();

                rows.forEach((row) => {
                    row.querySelectorAll('.badge').forEach((badge) => {
                        const value = badge.textContent.trim();
                        if (value) statusValues.add(value);
                    });
                });

                statusValues.forEach((value) => {
                    const option = document.createElement('option');
                    option.value = value.toLowerCase();
                    option.textContent = value;
                    statusSelect.appendChild(option);
                });

                if (statusValues.size === 0) {
                    statusSelect.parentElement.remove();
                }

                const getFilteredRows = () => rows.filter((row) => {
                    const text = row.textContent.toLowerCase();
                    const matchesQuery = !query || text.includes(query);
                    const matchesStatus = !status || Array.from(row.querySelectorAll('.badge'))
                        .some((badge) => badge.textContent.trim().toLowerCase() === status);
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

                statusSelect.addEventListener('change', () => {
                    status = statusSelect.value;
                    currentPage = 1;
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
