import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

// Wykonujemy rzeczywisty kod aplikacji; importy zastępujemy kontrolowanym obiektem Vue.
// To sprawdzenie logiki nie uruchamia przeglądarki ani renderowania komponentu.
const initialization = readFileSync(new URL('../../public/js/admin-sidebar-init.js', import.meta.url), 'utf8');
const application = readFileSync(new URL('../../resources/js/app.js', import.meta.url), 'utf8')
    .replace(/^import .*;\s*$/gm, '');
const administration = readFileSync(new URL('../../resources/js/admin.js', import.meta.url), 'utf8')
    .replace(/^import .*;\s*$/gm, '');

// Każdy test dostaje własny uproszczony DOM i localStorage, bez współdzielenia stanu.
function environment(stored = null, hasSidebar = true, hasVue = false) {
    const classes = new Set();
    const attributes = new Map();
    const storage = new Map(stored === null ? [] : [['admin-sidebar-collapsed', stored]]);
    let click;
    let mounted;
    const toggle = {
        setAttribute: (key, value) => attributes.set(key, value),
        addEventListener: (event, callback) => { if (event === 'click') click = callback; },
    };
    const vueRoot = {};
    const context = {
        localStorage: { getItem: (key) => storage.get(key) ?? null, setItem: (key, value) => storage.set(key, value) },
        document: {
            documentElement: { classList: {
                add: (name) => classes.add(name),
                contains: (name) => classes.has(name),
                toggle: (name) => { if (classes.has(name)) { classes.delete(name); return false; } classes.add(name); return true; },
            } },
            getElementById: (id) => id === 'app' ? (hasVue ? vueRoot : null)
                : hasSidebar ? (id === 'sidebar-toggle' ? toggle : {}) : null,
        },
        App: {},
        createApp: () => ({ mount: (root) => { mounted = root; } }),
        initializeSearchInputs: () => {},
        initializePerPageSelects: () => {},
    };
    vm.createContext(context);
    vm.runInContext(initialization, context);
    vm.runInContext(application, context);
    vm.runInContext(administration, context);
    return { classes, attributes, storage, click: () => click(), mounted: () => mounted, vueRoot };
}

// Dwa kliknięcia muszą zwinąć i rozwinąć panel, aktualizując dostępność i zapis stanu.
test('panel starts expanded and toggles state, accessibility labels and persistence', () => {
    const page = environment();
    assert.equal(page.attributes.get('aria-expanded'), 'true');
    page.click();
    assert.equal(page.classes.has('sidebar-collapsed'), true);
    assert.equal(page.attributes.get('aria-expanded'), 'false');
    assert.equal(page.attributes.get('aria-label'), 'Rozwiń panel nawigacyjny');
    assert.equal(page.storage.get('admin-sidebar-collapsed'), 'true');
    page.click();
    assert.equal(page.classes.has('sidebar-collapsed'), false);
    assert.equal(page.attributes.get('aria-expanded'), 'true');
    assert.equal(page.attributes.get('aria-label'), 'Zwiń panel nawigacyjny');
    assert.equal(page.storage.get('admin-sidebar-collapsed'), 'false');
});

// Zapis z poprzedniej wizyty ma ustawić panel jako zwinięty już przy inicjalizacji.
test('panel restores collapsed state from local storage', () => {
    const page = environment('true');
    assert.equal(page.classes.has('sidebar-collapsed'), true);
    assert.equal(page.attributes.get('aria-expanded'), 'false');
    assert.equal(page.attributes.get('aria-label'), 'Rozwiń panel nawigacyjny');
});

// Publiczna strona uruchamia Vue mimo braku elementów panelu administratora.
test('public page mounts Vue without requiring admin sidebar', () => {
    const page = environment(null, false, true);
    assert.equal(page.mounted(), page.vueRoot);
});

// Brak elementów obu funkcji nie powinien powodować błędu ani uruchamiać Vue.
test('page without Vue root and sidebar executes without mounting Vue', () => {
    const page = environment(null, false, false);
    assert.equal(page.mounted(), undefined);
});
