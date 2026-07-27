(function (window, document) {
    'use strict';

function escapeSupplyHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function supplyOptionLabel(option) {
    const label = option?.lastElementChild?.textContent || option?.textContent || '';
    return label.trim();
}

function buildSupplyInsumoSelect(index) {
    const options = supplyInsumosOptions.map((insumo) => `
        <button type="button" class="ag-select-option" data-value="${escapeSupplyHtml(insumo.id)}" role="option">
            <span class="material-symbols-outlined" aria-hidden="true">inventory_2</span>
            <span>${escapeSupplyHtml(insumo.label)}</span>
        </button>
    `).join('');

    return `
        <div class="ag-select ag-select--supply" data-ag-select>
            <input type="hidden" name="productos[${index}][id_insumo]" data-ag-select-value>
            <button type="button" class="ag-select-button" data-ag-select-button aria-haspopup="listbox" aria-expanded="false">
                <span class="material-symbols-outlined" aria-hidden="true">science</span>
                <span data-ag-select-label>Selecciona insumo</span>
                <span class="material-symbols-outlined" aria-hidden="true">keyboard_arrow_down</span>
            </button>
            <div class="ag-select-menu" data-ag-select-menu role="listbox" aria-label="Seleccionar insumo">
                ${options}
            </div>
        </div>
    `;
}

let etapaChart = null;
let stageChartFallback = null;
let stageChartResizeObserver = null;

function chartBorderColor(theme = document.documentElement.dataset.theme) {
    return theme === 'light' ? '#ffffff' : (theme === 'night' ? '#080d0a' : '#172033');
}

function drawStageChartFallback() {
    if (!stageChartFallback) return;

    const { canvas, values, colors, hasData } = stageChartFallback;
    const wrapper = canvas.closest('.farmer-chart-wrap');
    const width = Math.max(220, Math.round(wrapper?.clientWidth || 320));
    const height = Math.max(180, Math.round(wrapper?.clientHeight || 240));
    const ratio = Math.max(1, window.devicePixelRatio || 1);
    const context = canvas.getContext('2d');
    const total = hasData ? values.reduce((sum, value) => sum + value, 0) : 0;
    const renderedValues = hasData ? values : [1];
    const renderedTotal = hasData ? total : 1;
    const radius = Math.max(48, Math.min(width, height) * 0.36);
    const lineWidth = Math.max(20, radius * 0.34);
    const centerX = width / 2;
    const centerY = height / 2;
    let startAngle = -Math.PI / 2;

    canvas.width = Math.round(width * ratio);
    canvas.height = Math.round(height * ratio);
    context.setTransform(ratio, 0, 0, ratio, 0, 0);
    context.clearRect(0, 0, width, height);
    context.lineWidth = lineWidth;
    context.lineCap = 'butt';

    renderedValues.forEach((value, index) => {
        if (value <= 0) return;
        const angle = (value / renderedTotal) * Math.PI * 2;
        context.beginPath();
        context.strokeStyle = colors[index] || '#94a3b8';
        context.arc(centerX, centerY, radius - lineWidth / 2, startAngle, startAngle + angle);
        context.stroke();
        startAngle += angle;
    });

    const styles = getComputedStyle(document.documentElement);
    context.fillStyle = styles.getPropertyValue('--admin-text').trim() || '#172033';
    context.textAlign = 'center';
    context.textBaseline = 'middle';
    context.font = '900 28px Raleway, sans-serif';
    context.fillText(String(total), centerX, centerY - 8);
    context.fillStyle = styles.getPropertyValue('--admin-muted').trim() || '#64748b';
    context.font = '800 11px Raleway, sans-serif';
    context.fillText(total === 1 ? 'lote' : 'lotes', centerX, centerY + 18);
    canvas.dataset.chartFallback = '1';
}

function useStageChartFallback(canvas, values, colors, hasData) {
    const wrapper = canvas.closest('.farmer-chart-wrap');
    wrapper?.classList.remove('farmer-chart-wrap--error');
    wrapper?.classList.toggle('farmer-chart-wrap--empty', !hasData);
    stageChartFallback = { canvas, values, colors, hasData };
    drawStageChartFallback();

    stageChartResizeObserver?.disconnect();
    if (typeof ResizeObserver === 'function' && wrapper) {
        stageChartResizeObserver = new ResizeObserver(drawStageChartFallback);
        stageChartResizeObserver.observe(wrapper);
    }
}

function initializeStageChart() {
    const canvas = document.getElementById('etapaChart');
    const wrapper = canvas?.closest('.farmer-chart-wrap');

    if (!canvas) return;

    const rawTotals = typeof farmerStageTotals !== 'undefined' && Array.isArray(farmerStageTotals)
        ? farmerStageTotals
        : [];
    const rawLabels = typeof farmerStageLabels !== 'undefined' && Array.isArray(farmerStageLabels)
        ? farmerStageLabels
        : [];
    const totals = rawTotals.map((value) => {
        const number = Number(value);
        return Number.isFinite(number) && number > 0 ? number : 0;
    });
    const hasData = totals.some((value) => value > 0);
    const labels = hasData ? rawLabels : ['Sin lotes registrados'];
    const values = hasData ? totals : [1];
    const colors = hasData
        ? ['#08752b', '#145ee8', '#ffb43b', '#94a3b8']
        : ['#d7ddd9'];

    if (typeof window.Chart !== 'function') {
        useStageChartFallback(canvas, values, colors, hasData);
        return;
    }

    wrapper?.classList.toggle('farmer-chart-wrap--empty', !hasData);
    window.Chart.getChart?.(canvas)?.destroy();

    try {
        etapaChart = new window.Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderColor: chartBorderColor(),
                    borderWidth: 3,
                    hoverOffset: hasData ? 5 : 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '66%',
                animation: {
                    duration: 450,
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: hasData },
                },
                layout: {
                    padding: 4,
                },
            },
        });
    } catch (error) {
        etapaChart = null;
        useStageChartFallback(canvas, values, colors, hasData);
        console.error('No se pudo inicializar la gráfica de etapas.', error);
    }
}

window.addEventListener('app:themechange', function(event) {
    if (etapaChart) {
        etapaChart.data.datasets[0].borderColor = chartBorderColor(event.detail.theme);
        etapaChart.update('none');
        return;
    }
    drawStageChartFallback();
});

document.addEventListener('DOMContentLoaded', function() {
    initializeStageChart();

    const lotForm = document.querySelector('[data-lot-form]');
    const stageNames = ['etapa_siembra', 'etapa_riego', 'etapa_cosecha'];
    const stageInputs = stageNames
        .map((name) => lotForm?.querySelector(`input[name="${name}"]`))
        .filter(Boolean);
    const cropSelect = lotForm?.querySelector('[data-lot-crop-select]');
    const cropValue = cropSelect?.querySelector('[data-ag-select-value]');
    const stageDates = [
        {
            name: 'Siembra',
            start: lotForm?.querySelector('[name="fecha_inicio_siembra"]'),
            end: lotForm?.querySelector('[name="fecha_fin_siembra"]'),
        },
        {
            name: 'Riego',
            start: lotForm?.querySelector('[name="fecha_inicio_riego"]'),
            end: lotForm?.querySelector('[name="fecha_fin_riego"]'),
        },
        {
            name: 'Cosecha',
            start: lotForm?.querySelector('[name="fecha_inicio_cosecha"]'),
            end: lotForm?.querySelector('[name="fecha_fin_cosecha"]'),
        },
    ];

    function stageIsAvailable(index) {
        if (index === 0) return Boolean(cropValue?.value);
        return Boolean(stageInputs[index - 1]?.checked && stageDates[index - 1]?.end?.value);
    }

    function refreshStageAvailability() {
        stageInputs.forEach((input, index) => {
            const card = input.closest('.lot-stage-card');
            const toggle = input.closest('.lot-stage-toggle');
            const availability = card?.querySelector('[data-stage-availability]');
            const previousCompleted = stageIsAvailable(index);

            card?.classList.toggle('is-locked', !previousCompleted);
            card?.classList.toggle('is-selected', input.checked);
            toggle?.setAttribute('aria-disabled', previousCompleted ? 'false' : 'true');
            [stageDates[index]?.start, stageDates[index]?.end].forEach((dateInput) => {
                if (!dateInput) return;
                dateInput.disabled = !previousCompleted;
                const dateTrigger = dateInput.closest('.app-date-field')
                    ?.querySelector('.app-date-field__trigger');
                if (dateTrigger) dateTrigger.disabled = !previousCompleted;
            });

            if (availability) {
                const previousName = index === 1 ? 'Siembra' : 'Riego';
                availability.textContent = previousCompleted
                    ? (input.checked ? 'Marcada' : 'Disponible')
                    : (index === 0 ? 'Seleccione cultivo' : `Finalice ${previousName}`);
            }
        });

        if (stageDates[2]?.end) {
            stageDates[2].end.required = Boolean(stageInputs[2]?.checked);
        }
    }

    function openStage(index) {
        const input = stageInputs[index];
        const dates = stageDates[index];
        if (!input || !dates || !stageIsAvailable(index)) return;

        if (!input.checked) {
            input.checked = true;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
            refreshStageAvailability();
        }
        input.closest('.lot-stage-card')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        window.setTimeout(() => {
            dates.start?.focus();
            dates.start?.click();
        }, 280);
    }

    stageInputs.forEach((input, index) => {
        input.addEventListener('click', function(event) {
            if (index === 0 && cropValue?.value && !input.checked) {
                event.preventDefault();
                window.appNotify?.('La etapa Siembra se activa automáticamente con el cultivo seleccionado.', 'info');
                return;
            }
            if (stageIsAvailable(index)) {
                return;
            }

            event.preventDefault();
            const previousName = index === 0 ? 'el cultivo' : stageDates[index - 1].name;
            const currentName = stageDates[index].name;
            window.appNotify?.(
                `Debe completar ${previousName} antes de acceder a ${currentName}.`,
                'warning'
            );
        });

        input.addEventListener('change', function() {
            if (!input.checked) {
                stageInputs.slice(index + 1).forEach((following) => {
                    following.checked = false;
                });
            }
            refreshStageAvailability();
        });

        [stageDates[index]?.start, stageDates[index]?.end].forEach((dateInput) => {
            dateInput?.addEventListener('change', function() {
                if (!dateInput.value || !stageIsAvailable(index) || input.checked) {
                    refreshStageAvailability();
                    return;
                }

                input.checked = true;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        stageDates[index]?.end?.addEventListener('change', function() {
            const end = stageDates[index].end;
            if (!end.value || !end.checkValidity()) {
                refreshStageAvailability();
                return;
            }

            const next = stageDates[index + 1];
            if (!next) {
                window.appNotify?.('Cronograma completo. Ya puede registrar el lote.', 'success');
                lotForm?.querySelector('button[type="submit"]')?.focus();
                return;
            }

            next.start?.setAttribute('min', end.value);
            if (next.start?.value && next.start.value < end.value) {
                next.start.value = '';
            }
            window.appNotify?.(
                `${stageDates[index].name} completada. Continúe con ${next.name}.`,
                'success',
                { duration: 6500 }
            );
            openStage(index + 1);
        });
    });

    refreshStageAvailability();

    const harvestModal = document.getElementById('finalizarCosechaModal');

    harvestModal?.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        harvestModal.querySelector('[data-harvest-lot-input]').value = button?.dataset.harvestLotId || '';
        harvestModal.querySelector('[data-harvest-lot-label]').textContent = button?.dataset.harvestLotName || 'este lote';
    });

    const getSelects = () => Array.from(document.querySelectorAll('[data-ag-select]'));

    function closeSelect(select) {
        select.classList.remove('is-open');
        select.querySelector('[data-ag-select-button]')?.setAttribute('aria-expanded', 'false');
    }

    function closeAll(except = null) {
        getSelects().forEach((select) => {
            if (select !== except) {
                closeSelect(select);
            }
        });
    }

    function initializeSelect(select) {
        if (!select || select.dataset.agSelectBound === '1') {
            return;
        }
        select.dataset.agSelectBound = '1';

        const button = select.querySelector('[data-ag-select-button]');
        const value = select.querySelector('[data-ag-select-value]');
        const label = select.querySelector('[data-ag-select-label]');
        const options = Array.from(select.querySelectorAll('.ag-select-option'));

        button.addEventListener('click', function() {
            const willOpen = !select.classList.contains('is-open');
            closeAll(select);
            select.classList.toggle('is-open', willOpen);
            button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });

        options.forEach((option) => {
            option.addEventListener('click', function() {
                value.value = option.dataset.value || '';
                label.textContent = supplyOptionLabel(option);
                select.classList.remove('is-invalid');
                options.forEach((item) => item.classList.toggle('is-selected', item === option));
                closeSelect(select);
                value.dispatchEvent(new Event('change', { bubbles: true }));
                select.dispatchEvent(new CustomEvent('ag-select:change', {
                    bubbles: true,
                    detail: { value: value.value, option },
                }));
            });
        });
    }

    getSelects().forEach(initializeSelect);
    document.addEventListener('ag-select:mount', function(event) {
        initializeSelect(event.detail?.select);
    });

    cropSelect?.addEventListener('ag-select:change', function(event) {
        const plantingDate = event.detail?.option?.dataset.plantingDate || '';
        const planting = stageDates[0];
        if (!plantingDate || !planting?.start) return;

        planting.start.value = plantingDate;
        planting.start.setAttribute('min', plantingDate);
        planting.end?.setAttribute('min', plantingDate);
        planting.start.dispatchEvent(new Event('change', { bubbles: true }));
        if (planting.end?.value && planting.end.value < plantingDate) {
            planting.end.value = '';
        }
        stageInputs[0].checked = true;
        stageInputs[0].dispatchEvent(new Event('change', { bubbles: true }));
        refreshStageAvailability();
        stageInputs[0].closest('.lot-stage-card')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        window.setTimeout(() => {
            planting.end?.focus();
            planting.end?.click();
        }, 280);
    });

    document.addEventListener('click', function(event) {
        if (!event.target.closest('[data-ag-select]')) {
            closeAll();
        }
    });

    document.addEventListener('submit', function(event) {
        const formSelects = Array.from(event.target.querySelectorAll('[data-ag-select]'));
        const invalidSelect = formSelects.find((select) => {
            const value = select.querySelector('[data-ag-select-value]');
            return !value || value.value === '';
        });

        if (!invalidSelect) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        invalidSelect.classList.add('is-invalid', 'is-open');
        invalidSelect.querySelector('[data-ag-select-button]')?.focus();
    }, true);

    const requestedTab = new URLSearchParams(window.location.search).get('tab');
    const requestedTrigger = requestedTab
        ? document.querySelector(`[data-bs-target="#${CSS.escape(requestedTab)}"]`)
        : null;

    if (requestedTrigger && window.bootstrap?.Tab) {
        window.bootstrap.Tab.getOrCreateInstance(requestedTrigger).show();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const supplyForm = document.querySelector('[data-supply-form]');

    if (!supplyForm) {
        return;
    }

    const container = supplyForm.querySelector('[data-supply-products]');
    const addButton = supplyForm.querySelector('[data-add-supply-product]');
    let supplyProductIndex = 0;

    function readCalculatorContext() {
        try {
            const raw = sessionStorage.getItem('pis.calculatorRequestContext');
            if (!raw) return null;
            const context = JSON.parse(raw);
            const isRecent = Number(context?.createdAt) > Date.now() - (2 * 60 * 60 * 1000);
            if (!isRecent || !context?.loteId || !Array.isArray(context?.products)) {
                sessionStorage.removeItem('pis.calculatorRequestContext');
                return null;
            }
            return context;
        } catch (error) {
            return null;
        }
    }

    function setSelectValue(select, value) {
        const option = Array.from(select?.querySelectorAll('.ag-select-option') || [])
            .find((candidate) => String(candidate.dataset.value) === String(value));
        if (!select || !option) return false;
        select.querySelector('[data-ag-select-value]').value = option.dataset.value || '';
        select.querySelector('[data-ag-select-label]').textContent = supplyOptionLabel(option);
        option.classList.add('is-selected');
        return true;
    }

    function addSupplyProduct(prefill = null) {
        const row = document.createElement('div');
        row.className = 'producto-item farmer-product-row supply-product-row';
        row.innerHTML = `
            <div class="farmer-product-grid">
                <span class="supply-product-index">${String(supplyProductIndex + 1).padStart(2, '0')}</span>
                <label>
                    <span>Insumo</span>
                    ${buildSupplyInsumoSelect(supplyProductIndex)}
                </label>
                <label>
                    <span>Cantidad por hectárea</span>
                    <input type="number" step="0.01" min="0.01" name="productos[${supplyProductIndex}][cantidad]" class="form-control" placeholder="Ej. 10" required>
                </label>
                <button type="button" class="btn btn-danger btn-sm remove-producto" aria-label="Eliminar insumo"><span class="material-symbols-outlined" aria-hidden="true">delete</span></button>
            </div>
        `;

        container.appendChild(row);
        document.dispatchEvent(new CustomEvent('ag-select:mount', {
            detail: { select: row.querySelector('[data-ag-select]') }
        }));
        if (prefill) {
            setSelectValue(row.querySelector('[data-ag-select]'), prefill.id);
            row.querySelector('input[type="number"]').value = String(prefill.quantity);
        }
        supplyProductIndex++;

        row.querySelector('.remove-producto').addEventListener('click', function() {
            if (container.children.length === 1) {
                const select = row.querySelector('[data-ag-select]');
                select.querySelector('[data-ag-select-value]').value = '';
                select.querySelector('[data-ag-select-label]').textContent = 'Selecciona insumo';
                select.querySelectorAll('.ag-select-option').forEach((option) => {
                    option.classList.remove('is-selected');
                });
                select.classList.remove('is-invalid', 'is-open');
                row.querySelector('input[type="number"]').value = '';
                return;
            }

            row.remove();
        });
    }

    addButton.addEventListener('click', () => addSupplyProduct());
    const calculatorContext = readCalculatorContext();
    const normalizedName = (value) => String(value || '').trim().toLocaleLowerCase('es-EC');
    const prefilledProducts = calculatorContext
        ? calculatorContext.products.map((product) => {
            const inventoryItem = supplyInsumosOptions.find(
                (item) => normalizedName(item.name) === normalizedName(product.name)
            );
            return inventoryItem && Number(product.quantityPerHectare) > 0
                ? { id: inventoryItem.id, quantity: product.quantityPerHectare }
                : null;
        }).filter(Boolean)
        : [];

    if (calculatorContext) {
        const lotSelect = supplyForm.querySelector('[data-ag-select]');
        const hectares = supplyForm.querySelector('input[name="hectareas"]');
        setSelectValue(lotSelect, calculatorContext.loteId);
        if (hectares && Number(calculatorContext.area) > 0) {
            hectares.value = String(calculatorContext.area);
        }
        prefilledProducts.forEach(addSupplyProduct);
        if (prefilledProducts.length === 0) addSupplyProduct();
        sessionStorage.removeItem('pis.calculatorRequestContext');
        window.appNotify?.(
            `Pedido preparado con el lote calculado y ${prefilledProducts.length} insumo(s) disponible(s) en inventario.`,
            'success'
        );
        supplyForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        addSupplyProduct();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const pestForm = document.querySelector('[data-pest-form]');

    if (!pestForm) {
        return;
    }

    const cards = Array.from(pestForm.querySelectorAll('.pest-card'));
    const total = pestForm.querySelector('[data-pest-total]');
    const high = pestForm.querySelector('[data-pest-high]');
    const medium = pestForm.querySelector('[data-pest-medium]');
    const low = pestForm.querySelector('[data-pest-low]');
    const updated = pestForm.querySelector('[data-pest-updated]');

    function formatNow() {
        return new Intl.DateTimeFormat('es-EC', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date());
    }

    function updatePestSummary() {
        const selected = cards.filter((card) => card.querySelector('input').checked);
        const counts = { alto: 0, medio: 0, bajo: 0 };

        selected.forEach((card) => {
            const severity = card.dataset.severity;
            if (Object.prototype.hasOwnProperty.call(counts, severity)) {
                counts[severity]++;
            }
        });

        total.textContent = selected.length;
        high.textContent = counts.alto;
        medium.textContent = counts.medio;
        low.textContent = counts.bajo;
        updated.textContent = formatNow();
    }

    cards.forEach((card) => {
        const checkbox = card.querySelector('input');

        checkbox.addEventListener('change', updatePestSummary);
        card.addEventListener('keydown', function(event) {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    pestForm.addEventListener('submit', function(event) {
        const hasSelectedPest = cards.some((card) => card.querySelector('input').checked);

        if (!hasSelectedPest) {
            event.preventDefault();
            event.stopImmediatePropagation();
            cards[0]?.focus();
            alert('Seleccione al menos una plaga detectada.');
        }
    }, true);
});

// Script para calcular insumos y actualizar el formulario
document.addEventListener('DOMContentLoaded', function() {
    const loteSelect = document.getElementById('id_lote');
    const hectareasInput = document.getElementById('hectareas');
    const insumosContainer = document.getElementById('insumosCalculados');
    const insumosJsonInput = document.getElementById('insumos_json');

    if (!loteSelect || !hectareasInput || !insumosContainer || !insumosJsonInput) {
        return;
    }

    function actualizarInsumos() {
        const loteId = loteSelect.value;
        const hectareas = parseFloat(hectareasInput.value);
        if (!loteId || isNaN(hectareas) || hectareas <= 0) {
            insumosContainer.innerHTML = '<p>Seleccione un lote y cantidad de hectáreas para calcular insumos.</p>';
            insumosJsonInput.value = '';
            return;
        }

        insumosContainer.innerHTML = '<p>Cargando insumos...</p>';

        fetch(`api/insumos/calcular/${encodeURIComponent(loteId)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    insumosContainer.innerHTML = `<p class="text-danger">${data.error}</p>`;
                    insumosJsonInput.value = '';
                    return;
                }

                let html = `<h5>Área del lote: ${data.area} ha</h5>`;
                html += '<table class="table table-bordered"><thead><tr><th>Etapa</th><th>Insumo</th><th>Cantidad Total</th><th>Unidad</th></tr></thead><tbody>';

                const insumosCalculados = [];

                data.insumos.forEach(insumo => {
                    const cantidadTotal = insumo.cantidad_total * hectareas;
                    insumosCalculados.push({
                        nombre: insumo.nombre,
                        cantidad_total: cantidadTotal
                    });
                    html += `<tr>
                        <td>${insumo.etapa}</td>
                        <td>${insumo.nombre}</td>
                        <td>${cantidadTotal.toFixed(2)}</td>
                        <td>${insumo.unidad}</td>
                    </tr>`;
                });

                html += '</tbody></table>';
                insumosContainer.innerHTML = html;
                insumosJsonInput.value = JSON.stringify(insumosCalculados);
            })
            .catch(err => {
                insumosContainer.innerHTML = `<p class="text-danger">Error al cargar insumos: ${err}</p>`;
                insumosJsonInput.value = '';
            });
    }

    loteSelect.addEventListener('change', actualizarInsumos);
    hectareasInput.addEventListener('input', actualizarInsumos);
});
})(window, document);
