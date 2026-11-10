export function initializePerPageSelects(root = document) {
    root.querySelectorAll('[data-per-page-select]').forEach((form) => {
        const select = form.querySelector('select[name="per_page"]');

        if (!select) {
            return;
        }

        select.addEventListener('change', () => {
            form.requestSubmit();
        });
    });
}