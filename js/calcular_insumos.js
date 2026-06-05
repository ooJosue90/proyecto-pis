document.addEventListener('DOMContentLoaded', function() {
    const loteSelector = document.getElementById('selectorLote');
    const insumosContainer = document.getElementById('insumosCalculados');

    if (!loteSelector || !insumosContainer) return;

    const customSelect = document.querySelector('[data-calc-select]');

    if (customSelect) {
        const button = customSelect.querySelector('[data-calc-select-button]');
        const label = customSelect.querySelector('[data-calc-select-label]');
        const options = Array.from(customSelect.querySelectorAll('.ag-select-option'));

        button.addEventListener('click', function() {
            const isOpen = customSelect.classList.toggle('is-open');
            button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        options.forEach((option) => {
            option.addEventListener('click', function() {
                loteSelector.value = option.dataset.value || '';
                label.textContent = option.textContent.trim();
                options.forEach((item) => item.classList.toggle('is-selected', item === option));
                customSelect.classList.remove('is-open');
                button.setAttribute('aria-expanded', 'false');
                loteSelector.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        document.addEventListener('click', function(event) {
            if (!event.target.closest('[data-calc-select]')) {
                customSelect.classList.remove('is-open');
                button.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function etapaClass(etapa) {
        return etapa.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function renderEmpty(icon, title, text) {
        insumosContainer.innerHTML = `
            <div class="calculator-empty-state">
                <span><i class="fas ${icon}"></i></span>
                <h2>${escapeHtml(title)}</h2>
                <p>${escapeHtml(text)}</p>
            </div>
        `;
    }

    loteSelector.addEventListener('change', function() {
        const loteId = this.value;
        if (!loteId) {
            renderEmpty('fa-circle-info', 'Seleccione un lote', 'Los insumos calculados aparecerán agrupados por Siembra, Riego y Cosecha.');
            return;
        }

        renderEmpty('fa-circle-notch fa-spin', 'Calculando insumos', 'Estamos preparando las cantidades recomendadas para el lote seleccionado.');

        fetch(`calcular_insumos.php?id_lote=${loteId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    renderEmpty('fa-triangle-exclamation', 'No se pudo calcular', data.error);
                    return;
                }

                const grouped = data.insumos.reduce((acc, insumo) => {
                    acc[insumo.etapa] = acc[insumo.etapa] || [];
                    acc[insumo.etapa].push(insumo);
                    return acc;
                }, {});
                const stages = Object.keys(grouped);
                const totalCantidad = data.insumos.reduce((sum, insumo) => sum + Number(insumo.cantidad_total || 0), 0);

                let html = `
                    <div class="calculator-results-header">
                        <div>
                            <span class="farmer-kicker">Resultado técnico</span>
                            <h2>Plan de insumos estimado</h2>
                        </div>
                        <span>${escapeHtml(data.area)} ha</span>
                    </div>
                    <div class="calculator-result-metrics">
                        <article><span>Área del lote</span><strong>${escapeHtml(data.area)} ha</strong></article>
                        <article><span>Insumos</span><strong>${data.insumos.length}</strong></article>
                        <article><span>Etapas</span><strong>${stages.length}</strong></article>
                        <article><span>Total referencial</span><strong>${totalCantidad.toFixed(2)}</strong></article>
                    </div>
                    <div class="calculator-stage-grid">
                `;

                stages.forEach((stage) => {
                    html += `
                        <section class="calculator-stage-card calculator-stage-card--${etapaClass(stage)}">
                            <div class="calculator-stage-heading">
                                <h3>${escapeHtml(stage)}</h3>
                                <span>${grouped[stage].length} insumos</span>
                            </div>
                            <div class="calculator-stage-list">
                    `;

                    grouped[stage].forEach((insumo) => {
                        html += `
                            <article class="calculator-insumo-row">
                                <div>
                                    <strong>${escapeHtml(insumo.nombre)}</strong>
                                    <span>${Number(insumo.cantidad_total).toFixed(2)} ${escapeHtml(insumo.unidad)}</span>
                                </div>
                            </article>
                        `;
                    });

                    html += '</div></section>';
                });

                html += '</div>';
                insumosContainer.innerHTML = html;
            })
            .catch(err => {
                renderEmpty('fa-triangle-exclamation', 'Error al cargar insumos', err);
            });
    });
});
