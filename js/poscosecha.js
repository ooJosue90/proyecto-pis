(function () {
    'use strict';

    const STAGES = ['Recepción', 'Lavado', 'Clasificación', 'Empaque', 'Almacenamiento', 'Finalizada'];

    function numberValue(value) {
        return Number.parseFloat(String(value || '0').replace(',', '.')) || 0;
    }

    function formatKg(value) {
        return `${numberValue(value).toFixed(2)} kg`;
    }

    function notify(message, type) {
        if (typeof window.appNotify === 'function') {
            window.appNotify(message, type || 'warning');
            return;
        }

        window.alert(message);
    }

    function setInput(form, name, value) {
        const input = form.querySelector(`[data-stage-input="${name}"]`);
        if (!input) return;
        input.value = Number.isFinite(Number(value)) ? Number(value).toFixed(2) : (value || '');
    }

    function getInput(form, name) {
        return form.querySelector(`[data-stage-input="${name}"]`);
    }

    function stageLabel(stage) {
        return {
            Lavado: 'Registrar lavado',
            Clasificación: 'Registrar clasificación',
            Empaque: 'Definir destino',
            Almacenamiento: 'Registrar almacenamiento',
            Finalizada: 'Finalizar poscosecha'
        }[stage] || 'Registrar avance';
    }

    function datasetNumber(button, key) {
        return numberValue(button?.dataset?.[key]);
    }

    function stageTotals(form) {
        const received = numberValue(form.dataset.kgRecibidos);
        const washed = numberValue(getInput(form, 'kg_lavados')?.value || form.dataset.kgLavados);
        const classified = numberValue(getInput(form, 'kg_primera')?.value || form.dataset.kgPrimera)
            + numberValue(getInput(form, 'kg_segunda')?.value || form.dataset.kgSegunda)
            + numberValue(getInput(form, 'kg_descarte')?.value || form.dataset.kgDescarte)
            + numberValue(getInput(form, 'kg_merma')?.value || form.dataset.kgMerma);
        const distributed = numberValue(getInput(form, 'kg_exportacion')?.value || form.dataset.kgExportacion)
            + numberValue(getInput(form, 'kg_mercado_nacional')?.value || form.dataset.kgMercadoNacional)
            + numberValue(getInput(form, 'kg_procesamiento')?.value || form.dataset.kgProcesamiento);

        return {
            received,
            washed,
            classified,
            distributed,
            pending: Math.max(received - distributed, 0)
        };
    }

    function updateLiveSummary(form) {
        const totals = stageTotals(form);
        const liveReceived = form.querySelector('[data-live-recibido]');
        const liveClassified = form.querySelector('[data-live-clasificado]');
        const liveDistributed = form.querySelector('[data-live-distribuido]');
        const livePending = form.querySelector('[data-live-pendiente]');

        if (liveReceived) liveReceived.textContent = formatKg(totals.received);
        if (liveClassified) liveClassified.textContent = formatKg(totals.classified);
        if (liveDistributed) liveDistributed.textContent = formatKg(totals.distributed);
        if (livePending) livePending.textContent = formatKg(totals.pending);

        livePending?.classList.toggle('text-danger', totals.pending > 0 && form.dataset.targetStage === 'Empaque');
    }

    function showStageSection(form, targetStage) {
        form.querySelectorAll('[data-stage-section]').forEach((section) => {
            section.hidden = section.dataset.stageSection !== targetStage;
        });
    }

    function setupReceptionForm(form) {
        if (!form || form.dataset.ready === '1') return;
        form.dataset.ready = '1';

        const select = form.querySelector('[data-poscosecha-reception-cosecha]');
        const kgInput = form.querySelector('[data-poscosecha-reception-kg]');

        select?.addEventListener('change', () => {
            const kg = select.selectedOptions[0]?.dataset?.kg || '';
            if (kgInput && kg) kgInput.value = numberValue(kg).toFixed(2);
        });

        form.addEventListener('submit', (event) => {
            if (!select?.value) {
                event.preventDefault();
                notify('Seleccione una cosecha recibida.');
                return;
            }

            if (numberValue(kgInput?.value) <= 0) {
                event.preventDefault();
                notify('Los kg recibidos deben ser mayores que cero.');
            }
        });
    }

    function fillStageModal(form, button) {
        const targetStage = button.dataset.next || '';
        const currentStage = button.dataset.current || 'Recepción';
        const idInput = form.querySelector('[data-stage-field="id_poscosecha"]');
        const targetInput = form.querySelector('[data-stage-field="etapa_nueva"]');
        const title = form.querySelector('[data-stage-title]');
        const current = form.querySelector('[data-stage-current]');
        const submit = form.querySelector('[data-stage-submit]');

        form.reset();
        form.dataset.targetStage = targetStage;
        form.dataset.currentStage = currentStage;
        form.dataset.kgRecibidos = String(datasetNumber(button, 'kgRecibidos'));
        form.dataset.kgLavados = String(datasetNumber(button, 'kgLavados'));
        form.dataset.kgClasificados = String(datasetNumber(button, 'kgClasificados'));
        form.dataset.kgPrimera = String(datasetNumber(button, 'kgPrimera'));
        form.dataset.kgSegunda = String(datasetNumber(button, 'kgSegunda'));
        form.dataset.kgDescarte = String(datasetNumber(button, 'kgDescarte'));
        form.dataset.kgMerma = String(datasetNumber(button, 'kgMerma'));
        form.dataset.kgExportacion = String(datasetNumber(button, 'kgExportacion'));
        form.dataset.kgMercadoNacional = String(datasetNumber(button, 'kgMercadoNacional'));
        form.dataset.kgProcesamiento = String(datasetNumber(button, 'kgProcesamiento'));

        if (idInput) idInput.value = button.dataset.id || '';
        if (targetInput) targetInput.value = targetStage;
        if (title) title.textContent = stageLabel(targetStage);
        if (current) current.textContent = `${currentStage} → ${targetStage}`;
        if (submit) submit.innerHTML = `<i class="fas fa-arrow-right"></i> ${stageLabel(targetStage)}`;

        const received = numberValue(form.dataset.kgRecibidos);
        const washed = numberValue(form.dataset.kgLavados) || received;
        const primera = numberValue(form.dataset.kgPrimera);
        const segunda = numberValue(form.dataset.kgSegunda);
        const descarte = numberValue(form.dataset.kgDescarte);
        const merma = numberValue(form.dataset.kgMerma);
        const classifiedTotal = primera + segunda + descarte + merma;
        const usable = Math.max(classifiedTotal > 0 ? primera + segunda : washed, 0);
        const exportacion = numberValue(form.dataset.kgExportacion);
        const nacional = numberValue(form.dataset.kgMercadoNacional);
        const procesamiento = numberValue(form.dataset.kgProcesamiento);
        const destinationTotal = exportacion + nacional + procesamiento;

        setInput(form, 'kg_lavados', washed);
        setInput(form, 'kg_primera', targetStage === 'Clasificación' && classifiedTotal <= 0 ? washed : primera);
        setInput(form, 'kg_segunda', segunda);
        setInput(form, 'kg_descarte', descarte);
        setInput(form, 'kg_merma', merma);
        if (targetStage === 'Empaque' && destinationTotal <= 0) {
            setInput(form, 'kg_exportacion', usable);
            setInput(form, 'kg_mercado_nacional', 0);
            setInput(form, 'kg_procesamiento', 0);
        } else {
            setInput(form, 'kg_exportacion', exportacion);
            setInput(form, 'kg_mercado_nacional', nacional);
            setInput(form, 'kg_procesamiento', procesamiento > 0 ? procesamiento : Math.max(usable - exportacion - nacional, 0));
        }

        showStageSection(form, targetStage);
        updateLiveSummary(form);
    }

    function validateStageForm(form) {
        const targetStage = form.dataset.targetStage || '';
        const totals = stageTotals(form);
        const kgMerma = numberValue(getInput(form, 'kg_merma')?.value);
        const motivoMerma = String(getInput(form, 'motivo_merma')?.value || '').trim();

        if (!STAGES.includes(targetStage)) return 'Seleccione una etapa válida.';

        if (targetStage === 'Lavado') {
            if (totals.washed <= 0) return 'No puede pasar a Clasificación sin registrar kg lavados.';
            if (totals.washed > totals.received) return 'Los kg lavados no pueden superar los kg recibidos.';
        }

        if (targetStage === 'Clasificación') {
            if (totals.classified <= 0) return 'Registre la clasificación antes de avanzar.';
            if (totals.classified > totals.received) return 'La clasificación no puede superar los kg recibidos.';
            if (kgMerma > 0 && motivoMerma === '') return 'Ingrese el motivo de la merma.';
        }

        if (targetStage === 'Empaque') {
            if (totals.classified <= 0) return 'No se puede definir destino sin clasificación.';
            if (totals.distributed <= 0) return 'Defina al menos un destino.';
            if (totals.distributed > totals.received) return 'La distribución no puede superar los kg recibidos.';
        }

        if (targetStage === 'Almacenamiento' && totals.distributed <= 0) {
            return 'No se puede almacenar sin destino definido.';
        }

        if (targetStage === 'Finalizada' && totals.distributed <= 0) {
            return 'No se puede finalizar sin destino definido.';
        }

        return '';
    }

    function setupStageForm(form) {
        if (!form || form.dataset.ready === '1') return;
        form.dataset.ready = '1';

        document.querySelectorAll('[data-poscosecha-stage-open]').forEach((button) => {
            if (button.dataset.ready === '1') return;
            button.dataset.ready = '1';
            button.addEventListener('click', () => fillStageModal(form, button));
        });

        form.querySelectorAll('[data-stage-input]').forEach((input) => {
            input.addEventListener('input', () => updateLiveSummary(form));
        });

        form.addEventListener('submit', (event) => {
            const error = validateStageForm(form);
            if (error) {
                event.preventDefault();
                notify(error);
            }
        });
    }

    function setupFilters() {
        document.querySelectorAll('[data-poscosecha-filter]').forEach((filter) => {
            if (filter.dataset.ready === '1') return;
            filter.dataset.ready = '1';
            filter.addEventListener('change', () => {
                const value = filter.value || 'todos';
                document.querySelectorAll('[data-poscosecha-row]').forEach((row) => {
                    row.hidden = value !== 'todos' && row.dataset.filterStatus !== value;
                });
            });
        });
    }

    function setupConfirmations() {
        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-confirm-message]');
            if (!button) return;

            if (!window.confirm(button.dataset.confirmMessage || '¿Confirmar acción?')) {
                event.preventDefault();
            }
        });
    }

    function init() {
        document.querySelectorAll('[data-poscosecha-reception-form]').forEach(setupReceptionForm);
        document.querySelectorAll('[data-poscosecha-stage-form]').forEach(setupStageForm);
        setupFilters();
    }

    document.addEventListener('DOMContentLoaded', init);
    setupConfirmations();
    window.initPoscosecha = init;
})();
