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

const ctx = document.getElementById('etapaChart').getContext('2d');
const etapaChart = new Chart(ctx,{
    type:'doughnut',
    data:{
        labels: farmerStageLabels,
        datasets:[{
            data: farmerStageTotals,
            backgroundColor:['#08752b','#145ee8','#ffb43b','#94a3b8'],
            borderColor: document.documentElement.dataset.theme === 'light'
                ? '#ffffff'
                : (document.documentElement.dataset.theme === 'night' ? '#080d0a' : '#172033'),
            borderWidth:3,
            hoverOffset:5
        }]
    },
    options:{
        responsive: true,
        maintainAspectRatio: false,
        cutout: '66%',
        plugins:{
            legend:{display:false}
        },
        layout:{
            padding:4
        }
    }
});

window.addEventListener('app:themechange', function(event) {
    etapaChart.data.datasets[0].borderColor = event.detail.theme === 'light'
        ? '#ffffff'
        : (event.detail.theme === 'night' ? '#080d0a' : '#172033');
    etapaChart.update('none');
});

document.addEventListener('DOMContentLoaded', function() {
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
                label.textContent = option.textContent.trim();
                select.classList.remove('is-invalid');
                options.forEach((item) => item.classList.toggle('is-selected', item === option));
                closeSelect(select);
            });
        });
    }

    getSelects().forEach(initializeSelect);
    document.addEventListener('ag-select:mount', function(event) {
        initializeSelect(event.detail?.select);
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

    function addSupplyProduct() {
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

    addButton.addEventListener('click', addSupplyProduct);
    addSupplyProduct();
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
