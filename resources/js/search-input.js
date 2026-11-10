export function initializeSearchInputs(root = document) {
    root.querySelectorAll('[data-search-input]').forEach((container) => {
        const input = container.querySelector('input[type="search"]');
        const clearButton = container.querySelector('[data-clear-search]');

        if (!input || !clearButton) {
            return;
        }

        const updateVisibility = () => {
            clearButton.hidden = input.value === '';
        };

        updateVisibility();

        input.addEventListener('input', updateVisibility);

        clearButton.addEventListener('click', () => {
            input.value = '';
            updateVisibility();

            window.location.assign(clearButton.dataset.clearUrl);
        });
    });
}