(function () {
    'use strict';

    function numberValue(value) {
        return Number.parseFloat(String(value || '0').replace(',', '.')) || 0;
    }

    function setupEditForm() {
        const form = document.querySelector('form[action="cosecha_acciones.php"] [data-cosecha-edit-id]')?.closest('form');
        if (!form) return;

        const cancelButton = form.querySelector('[data-cosecha-cancel-edit]');
        const fields = {
            id: form.querySelector('[data-cosecha-edit-id]'),
            lote: form.querySelector('[data-cosecha-lote]'),
            fecha: form.querySelector('[data-cosecha-fecha]'),
            total: form.querySelector('[data-cosecha-total]'),
            primera: form.querySelector('[data-cosecha-primera]'),
            segunda: form.querySelector('[data-cosecha-segunda]'),
            descarte: form.querySelector('[data-cosecha-descarte]'),
            observaciones: form.querySelector('[data-cosecha-observaciones]')
        };

        function ensureLoteOption(value, label) {
            if (!fields.lote || !value) return;

            let option = Array.from(fields.lote.options).find((item) => item.value === String(value));
            if (!option) {
                option = document.createElement('option');
                option.value = String(value);
                option.dataset.temporary = '1';
                fields.lote.appendChild(option);
            }

            option.textContent = label || `Lote #${value}`;
            fields.lote.value = String(value);
        }

        function removeTemporaryLoteOptions() {
            if (!fields.lote) return;

            Array.from(fields.lote.options)
                .filter((option) => option.dataset.temporary === '1')
                .forEach((option) => option.remove());
        }

        document.querySelectorAll('[data-cosecha-edit]').forEach((button) => {
            button.addEventListener('click', () => {
                fields.id.value = button.dataset.id || '';
                ensureLoteOption(button.dataset.lote, button.dataset.loteLabel);
                fields.fecha.value = button.dataset.fecha || '';
                fields.total.value = button.dataset.total || '';
                fields.primera.value = button.dataset.primera || '0';
                fields.segunda.value = button.dataset.segunda || '0';
                fields.descarte.value = button.dataset.descarte || '0';
                fields.observaciones.value = button.dataset.observaciones || '';
                cancelButton?.classList.remove('d-none');
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        cancelButton?.addEventListener('click', () => {
            form.reset();
            fields.id.value = '';
            removeTemporaryLoteOptions();
            cancelButton.classList.add('d-none');
        });

        form.addEventListener('submit', (event) => {
            const total = numberValue(fields.total.value);
            const detail = numberValue(fields.primera.value) + numberValue(fields.segunda.value) + numberValue(fields.descarte.value);
            if (total <= 0 || detail > total) {
                event.preventDefault();
                window.AppUI?.notify?.('Revise las cantidades de cosecha antes de guardar.', 'warning');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        setupEditForm();
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-confirm-message]');
        if (!button) return;

        if (!window.confirm(button.dataset.confirmMessage || '¿Confirmar acción?')) {
            event.preventDefault();
        }
    });
})();
