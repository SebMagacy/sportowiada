export function generateDisciplineAlias(name) {
    return name
        .toLowerCase()
        .replace(/ł/g, 'l')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 255)
        .replace(/-+$/g, '');
}

export function initializeDisciplineForms(root = document) {
    root.querySelectorAll('[data-discipline-form]').forEach((form) => {
        const nameInput = form.querySelector('input[name="name"]');
        const aliasInput = form.querySelector('input[name="alias"]');

        if (!nameInput || !aliasInput) {
            return;
        }

        let isAliasManuallyEdited = aliasInput.value !== '';

        aliasInput.addEventListener('input', () => {
            isAliasManuallyEdited = true;
        });

        nameInput.addEventListener('input', () => {
            if (isAliasManuallyEdited) {
                return;
            }

            aliasInput.value = generateDisciplineAlias(nameInput.value);
        });
    });
}