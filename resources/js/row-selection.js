export function initializeRowSelections(root = document) {
    root.querySelectorAll('[data-row-selection]').forEach((container) => {
        const pageCheckbox = container.querySelector('[data-select-page]');
        const rowCheckboxes = [
            ...container.querySelectorAll('[data-select-user]'),
        ];

        if (!pageCheckbox) {
            return;
        }

        const toolbar = container.querySelector('[data-selection-toolbar]');
        const count = container.querySelector('[data-selection-count]');
        const clearButton = container.querySelector('[data-clear-selection]');

        const updateSelection = () => {
            const selectedCount = rowCheckboxes.filter(
                (checkbox) => checkbox.checked,
            ).length;

            pageCheckbox.disabled = rowCheckboxes.length === 0;
            pageCheckbox.checked =
                rowCheckboxes.length > 0 &&
                selectedCount === rowCheckboxes.length;
            pageCheckbox.indeterminate =
                selectedCount > 0 && selectedCount < rowCheckboxes.length;
            if (count) {
                count.textContent = String(selectedCount);
            }

            if (toolbar) {
                toolbar.hidden = selectedCount === 0;
            }
        };

        pageCheckbox.addEventListener('change', () => {
            rowCheckboxes.forEach((checkbox) => {
                checkbox.checked = pageCheckbox.checked;
            });

            updateSelection();
        });

        rowCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', updateSelection);
        });

        clearButton?.addEventListener('click', () => {
            rowCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });

            pageCheckbox.focus();
            updateSelection();
        });

        updateSelection();
    });
}