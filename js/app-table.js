(function (window, document) {
    'use strict';

    const PAGE_SIZE = 10;

    const AppTable = {
        tableCounter: 0,

        motionAllowed() {
            return window.AppUI?.motionAllowed?.() ?? true;
        },

        enhance(root) {
            root.querySelectorAll('.table').forEach((table) => {
                if (table.dataset.appTable === '1') return;
                if (table.closest('.modal')) return;

                const tbody = table.tBodies[0];
                if (!tbody || tbody.rows.length === 0) return;
                if (tbody.rows.length < 3 && !table.dataset.appTableOwner) return;

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
                const normalizeSearch = (value) => String(value || '')
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
                const tableOwnerBase = table.closest('[id]')?.id || 'app-table';
                const tableOwner = table.dataset.appTableOwner
                    || `${tableOwnerBase}--table-${++this.tableCounter}`;
                const usesMaterialIcons = document.body.classList.contains('farmer-admin-page');
                const icon = (fontAwesomeClass, materialName) => usesMaterialIcons
                    ? `<span class="material-symbols-outlined" aria-hidden="true">${materialName}</span>`
                    : `<i class="${fontAwesomeClass}" aria-hidden="true"></i>`;
                table.dataset.appTableOwner = tableOwner;
                const tableHost = wrapper.parentElement;
                tableHost.querySelectorAll(
                    `:scope > .app-table-tools[data-app-table-owner="${tableOwner}"], ` +
                    `:scope > .app-table-pagination[data-app-table-owner="${tableOwner}"]`
                ).forEach(element => element.remove());
                document.querySelectorAll(
                    `.app-table-filter__menu[data-app-table-owner="${tableOwner}"]`
                ).forEach(element => element.remove());
                const tools = document.createElement('div');
                tools.className = 'app-table-tools';
                tools.dataset.appTableOwner = tableOwner;
                tools.innerHTML = `
                    <div class="app-table-tools-left">
                        <div class="app-table-search">
                            <span class="input-group-text">${icon('fas fa-search', 'search')}</span>
                            <input type="search" class="form-control" placeholder="Buscar en la tabla" autocomplete="off">
                        </div>
                    </div>
                    <div class="app-table-tools-right">
                        <div class="app-table-filter">
                            <button type="button" class="app-table-filter__button" aria-haspopup="listbox" aria-expanded="false">
                                ${icon('fas fa-filter', 'filter_alt')}
                                <span class="app-table-filter__current">${filterAllLabel}</span>
                                ${icon('fas fa-chevron-down', 'keyboard_arrow_down')}
                            </button>
                            <div class="app-table-filter__menu" role="listbox" aria-label="Filtrar por ${filterHeading || 'estado'}"></div>
                        </div>
                    </div>
                `;

                wrapper.parentElement.insertBefore(tools, wrapper);

                const pagination = document.createElement('div');
                pagination.className = 'app-table-pagination';
                pagination.dataset.appTableOwner = tableOwner;
                pagination.innerHTML = `
                    <span class="app-table-page-info"></span>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-prev>${icon('fas fa-chevron-left', 'chevron_left')}</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-next>${icon('fas fa-chevron-right', 'chevron_right')}</button>
                `;
                wrapper.parentElement.insertBefore(pagination, wrapper.nextSibling);

                const searchInput = tools.querySelector('input[type="search"]');
                const statusFilter = tools.querySelector('.app-table-filter');
                const statusButton = tools.querySelector('.app-table-filter__button');
                const statusLabel = statusButton.querySelector('.app-table-filter__current');
                const statusMenu = tools.querySelector('.app-table-filter__menu');
                statusMenu.dataset.appTableOwner = tableOwner;
                const statusValues = new Set();
                const statusTone = (value) => {
                    const normalized = normalizeHeading(value);
                    if (!normalized) return 'all';
                    if (/pendiente|espera|revision|cosecha|warning|registrad[ao]/.test(normalized)) return 'warning';
                    if (/procesando|informacion|administrador/.test(normalized)) return 'info';
                    if (/aprobad[ao]|entregad[ao]|activ[ao]|finalizad[ao]|completad[ao]|agricultor/.test(normalized)) return 'success';
                    if (/rechazad[ao]|error|critic[ao]/.test(normalized)) return 'danger';
                    if (/cancelad[ao]|anulad[ao]|inactiv[ao]|bodeguero/.test(normalized)) return 'neutral';
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
                const getStatusText = (element) => {
                    if (!element) return '';
                    const clone = element.cloneNode(true);
                    clone.querySelectorAll('[aria-hidden="true"]').forEach((node) => node.remove());
                    return clone.textContent.trim();
                };
                const getRowStatus = (row) => {
                    if (filterColumnIndex < 0) return '';
                    const cell = row.cells[filterColumnIndex];
                    const statusElement = cell?.querySelector('.app-table-status-capsule, .crop-status, .badge');
                    return (getStatusText(statusElement) || cell?.textContent || '').trim();
                };

                rows.forEach((row) => {
                    const value = getRowStatus(row);
                    if (filterHeading === 'estado' && value) {
                        const cell = row.cells[filterColumnIndex];
                        let capsule = cell.querySelector('.app-table-status-capsule, .crop-status, .badge');

                        if (!capsule) {
                            capsule = document.createElement('span');
                            capsule.textContent = value;
                            cell.replaceChildren(capsule);
                        }

                        Array.from(capsule.classList)
                            .filter((className) => className.startsWith('app-table-status-capsule--'))
                            .forEach((className) => capsule.classList.remove(className));
                        capsule.classList.add('app-table-status-capsule', `app-table-status-capsule--${statusTone(value)}`);
                    }
                    if (value) statusValues.add(value);
                });

                const addFilterOption = (value, label) => {
                    const option = document.createElement('button');
                    const optionLabel = document.createElement('span');
                    const checkIcon = document.createElement(usesMaterialIcons ? 'span' : 'i');
                    option.type = 'button';
                    option.className = 'app-table-filter__option';
                    option.dataset.value = value;
                    option.setAttribute('role', 'option');
                    option.setAttribute('aria-selected', value === '' ? 'true' : 'false');
                    optionLabel.className = 'app-table-filter__option-label';
                    optionLabel.textContent = label;
                    checkIcon.className = usesMaterialIcons ? 'material-symbols-outlined' : 'fas fa-check';
                    if (usesMaterialIcons) checkIcon.textContent = 'check';
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
                    const text = normalizeSearch(row.textContent);
                    const matchesQuery = !query || text.includes(query);
                    const matchesStatus = !status || normalizeSearch(getRowStatus(row)) === status;
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

                const handleSearch = () => {
                    query = normalizeSearch(searchInput.value);
                    currentPage = 1;
                    render();
                };
                searchInput.addEventListener('input', handleSearch);
                searchInput.addEventListener('search', handleSearch);
                searchInput.closest('.app-table-search')?.addEventListener('click', () => {
                    searchInput.focus();
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

                        status = normalizeSearch(option.dataset.value || '');
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
    };

    window.AppTable = AppTable;
})(window, document);
