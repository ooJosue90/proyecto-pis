(function (window, document) {
    'use strict';

    const normalizeText = (value) => String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();

    function initializePicker(picker) {
        if (!picker || picker.dataset.associatedCropBound === '1') return;
        picker.dataset.associatedCropBound = '1';

        const toggle = picker.querySelector('[data-associated-crop-toggle]');
        const panel = picker.querySelector('[data-associated-crop-panel]');
        const customSelect = picker.querySelector('[data-associated-select]');
        const selectTrigger = picker.querySelector('[data-associated-select-trigger]');
        const selectValue = picker.querySelector('[data-associated-select-value]');
        const selectMenu = picker.querySelector('[data-associated-select-menu]');
        const searchInput = picker.querySelector('[data-associated-select-search]');
        const clearSearchButton = picker.querySelector('[data-associated-select-search-clear]');
        const optionButtons = Array.from(picker.querySelectorAll('[data-associated-select-option]'));
        const optionGroups = Array.from(picker.querySelectorAll('[data-associated-select-group]'));
        const emptyState = picker.querySelector('[data-associated-select-empty]');
        const availableCount = picker.querySelector('[data-associated-available-count]');
        const addButton = picker.querySelector('[data-associated-crop-add]');
        const addLabel = picker.querySelector('[data-associated-add-label]');
        const selectedContainer = picker.querySelector('[data-associated-crop-selected]');
        const count = picker.querySelector('[data-associated-crop-count]');
        const summary = picker.querySelector('[data-associated-summary]');
        const summaryTitle = picker.querySelector('[data-associated-summary-title]');
        const summaryCopy = picker.querySelector('[data-associated-summary-copy]');
        const clearAllButton = picker.querySelector('[data-associated-clear-all]');
        const feedback = picker.querySelector('[data-associated-feedback]');
        let selectedOption = null;
        let feedbackTimer = null;

        const selectedChips = () => Array.from(
            selectedContainer.querySelectorAll('[data-associated-crop-chip]')
        );

        const selectedCodes = () => selectedChips().map(
            (chip) => chip.dataset.associatedCropChip
        );

        const selectedLabels = () => selectedChips().map(
            (chip) => chip.dataset.associatedLabel || chip.querySelector('span')?.textContent.trim()
        ).filter(Boolean);

        const visibleEnabledOptions = () => optionButtons.filter(
            (option) => !option.hidden && !option.disabled
        );

        const showFeedback = (message, type = 'success') => {
            window.clearTimeout(feedbackTimer);
            feedback.textContent = message;
            feedback.dataset.type = type;
            feedback.hidden = false;
            feedbackTimer = window.setTimeout(() => {
                feedback.hidden = true;
            }, 4200);
        };

        const filterOptions = () => {
            const query = normalizeText(searchInput.value);
            let visibleCount = 0;

            optionGroups.forEach((group) => {
                let visibleInGroup = 0;
                group.querySelectorAll('[data-associated-select-option]').forEach((option) => {
                    const searchableText = normalizeText([
                        option.dataset.associatedLabel,
                        option.dataset.associatedDescription,
                        option.dataset.associatedCategory,
                    ].join(' '));
                    const matches = query === '' || searchableText.includes(query);
                    option.hidden = !matches;
                    if (matches) {
                        visibleCount++;
                        visibleInGroup++;
                    }
                });
                group.hidden = visibleInGroup === 0;
            });

            emptyState.hidden = visibleCount !== 0;
            clearSearchButton.hidden = query === '';
            const available = visibleEnabledOptions().length;
            availableCount.textContent = `${available} ${available === 1 ? 'disponible' : 'disponibles'}`;
        };

        const updateState = () => {
            const codes = selectedCodes();
            const labels = selectedLabels();
            const total = codes.length;

            count.textContent = `${total} ${total === 1 ? 'seleccionado' : 'seleccionados'}`;
            optionButtons.forEach((option) => {
                const added = codes.includes(option.dataset.associatedCode);
                option.disabled = added;
                option.classList.toggle('is-added', added);
                option.setAttribute('aria-disabled', added ? 'true' : 'false');
                option.setAttribute(
                    'aria-label',
                    `${option.dataset.associatedLabel}. ${option.dataset.associatedDescription}${added ? '. Ya agregado' : ''}`
                );
                if (added && option === selectedOption) selectedOption = null;
            });
            optionButtons.forEach((option) => {
                option.setAttribute('aria-selected', option === selectedOption ? 'true' : 'false');
            });

            selectValue.textContent = selectedOption?.dataset.associatedLabel || 'Seleccione una opción';
            customSelect.classList.toggle('has-value', selectedOption !== null);
            addButton.disabled = selectedOption === null;
            addLabel.textContent = selectedOption
                ? `Agregar ${selectedOption.dataset.associatedLabel}`
                : 'Agregar cultivo';

            summary.hidden = total === 0;
            summaryTitle.textContent = `${total} ${total === 1 ? 'cultivo asociado' : 'cultivos asociados'}`;
            summaryCopy.textContent = labels.join(' · ');
            clearAllButton.hidden = total < 2;
            filterOptions();
        };

        const resetSearch = () => {
            searchInput.value = '';
            filterOptions();
        };

        const closeSelect = (restoreFocus = false) => {
            selectMenu.hidden = true;
            selectTrigger.setAttribute('aria-expanded', 'false');
            customSelect.classList.remove('is-open');
            resetSearch();
            if (restoreFocus) selectTrigger.focus();
        };

        const openSelect = () => {
            selectMenu.hidden = false;
            selectTrigger.setAttribute('aria-expanded', 'true');
            customSelect.classList.add('is-open');
            filterOptions();
            window.setTimeout(() => searchInput.focus(), 40);
        };

        const selectCrop = (option) => {
            if (!option || option.disabled) return;
            selectedOption = option;
            updateState();
            closeSelect(true);
        };

        const focusRelativeOption = (direction) => {
            const enabledOptions = visibleEnabledOptions();
            if (!enabledOptions.length) return;
            const currentIndex = enabledOptions.indexOf(document.activeElement);
            const nextIndex = currentIndex < 0
                ? (direction > 0 ? 0 : enabledOptions.length - 1)
                : (currentIndex + direction + enabledOptions.length) % enabledOptions.length;
            enabledOptions[nextIndex].focus();
        };

        const removeCrop = (code, notify = true) => {
            const chip = selectedContainer.querySelector(
                `[data-associated-crop-chip="${CSS.escape(code)}"]`
            );
            if (!chip) return;
            const label = chip.dataset.associatedLabel || chip.querySelector('span')?.textContent.trim();
            chip.classList.add('is-removing');
            window.setTimeout(() => {
                chip.remove();
                updateState();
                if (notify) showFeedback(`${label} fue retirado de los cultivos asociados.`, 'info');
            }, 140);
        };

        const createChip = (code, label, iconName) => {
            const chip = document.createElement('span');
            const cropIcon = document.createElement('span');
            const text = document.createElement('span');
            const remove = document.createElement('button');
            const closeIcon = document.createElement('span');
            const input = document.createElement('input');

            chip.className = 'associated-crop-chip is-entering';
            chip.dataset.associatedCropChip = code;
            chip.dataset.associatedLabel = label;
            cropIcon.className = 'associated-crop-chip__icon material-symbols-outlined';
            cropIcon.setAttribute('aria-hidden', 'true');
            cropIcon.textContent = iconName || 'eco';
            text.textContent = label;
            remove.type = 'button';
            remove.dataset.associatedCropRemove = '';
            remove.dataset.associatedCode = code;
            remove.setAttribute('aria-label', `Quitar ${label}`);
            closeIcon.className = 'material-symbols-outlined';
            closeIcon.setAttribute('aria-hidden', 'true');
            closeIcon.textContent = 'close';
            input.type = 'hidden';
            input.name = 'cultivos_asociados[]';
            input.value = code;

            remove.append(closeIcon);
            chip.append(cropIcon, text, remove, input);
            window.setTimeout(() => chip.classList.remove('is-entering'), 200);
            return chip;
        };

        toggle.addEventListener('click', () => {
            const expanded = toggle.getAttribute('aria-expanded') !== 'true';
            toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            panel.hidden = !expanded;
            if (expanded) window.setTimeout(() => selectTrigger.focus(), 80);
        });

        selectTrigger.addEventListener('click', () => {
            if (selectMenu.hidden) {
                openSelect();
            } else {
                closeSelect();
            }
        });

        selectTrigger.addEventListener('keydown', (event) => {
            if (!['ArrowDown', 'ArrowUp'].includes(event.key)) return;
            event.preventDefault();
            openSelect();
            window.setTimeout(() => focusRelativeOption(event.key === 'ArrowDown' ? 1 : -1), 50);
        });

        searchInput.addEventListener('input', filterOptions);
        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeSelect(true);
                return;
            }
            if (event.key !== 'ArrowDown') return;
            event.preventDefault();
            focusRelativeOption(1);
        });

        clearSearchButton.addEventListener('click', () => {
            resetSearch();
            searchInput.focus();
        });

        selectMenu.addEventListener('click', (event) => {
            selectCrop(event.target.closest('[data-associated-select-option]'));
        });

        selectMenu.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeSelect(true);
                return;
            }
            if (event.key === 'Enter' && event.target.matches('[data-associated-select-option]')) {
                event.preventDefault();
                selectCrop(event.target);
                return;
            }
            if (!['ArrowDown', 'ArrowUp'].includes(event.key)) return;
            event.preventDefault();
            focusRelativeOption(event.key === 'ArrowDown' ? 1 : -1);
        });

        document.addEventListener('click', (event) => {
            if (!customSelect.contains(event.target)) closeSelect();
        });

        addButton.addEventListener('click', () => {
            const option = selectedOption;
            const code = option?.dataset.associatedCode || '';
            if (!code || selectedCodes().includes(code)) return;

            selectedContainer.append(createChip(
                code,
                option.dataset.associatedLabel,
                option.querySelector('.associated-custom-select__option-icon')?.textContent.trim()
            ));
            const label = option.dataset.associatedLabel;
            selectedOption = null;
            updateState();
            showFeedback(`${label} fue agregado. Puede seleccionar otro cultivo o guardar el registro.`);
            selectTrigger.focus();
        });

        selectedContainer.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-associated-crop-remove]');
            if (!remove) return;
            removeCrop(remove.dataset.associatedCode || '');
        });

        clearAllButton.addEventListener('click', () => {
            const total = selectedChips().length;
            selectedContainer.replaceChildren();
            selectedOption = null;
            updateState();
            showFeedback(
                `${total} ${total === 1 ? 'cultivo fue retirado' : 'cultivos fueron retirados'} de la selección.`,
                'info'
            );
            selectTrigger.focus();
        });

        selectedChips().forEach((chip) => {
            const code = chip.dataset.associatedCropChip;
            const option = optionButtons.find((item) => item.dataset.associatedCode === code);
            chip.dataset.associatedLabel = option?.dataset.associatedLabel || chip.querySelector('span')?.textContent.trim();
        });
        updateState();
    }

    function initializeAll(root = document) {
        root.querySelectorAll('[data-associated-crop-picker]').forEach(initializePicker);
    }

    document.addEventListener('DOMContentLoaded', () => initializeAll());
    document.addEventListener('app:associated-crops:mount', (event) => {
        initializeAll(event.detail?.root || document);
    });
})(window, document);
