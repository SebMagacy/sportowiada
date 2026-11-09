import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const application = readFileSync(new URL('../../resources/js/user-filters.js', import.meta.url), 'utf8');

// Uproszczony DOM sprawdza rzeczywisty moduł, bez renderowania strony w przeglądarce.
function environment(hasPanel = true, hasToggle = true) {
    const attributes = new Map();
    const listeners = new Map();
    const panel = { hidden: false };
    const toggle = {
        setAttribute: (name, value) => attributes.set(name, value),
        addEventListener: (event, callback) => listeners.set(event, callback),
    };
    vm.runInNewContext(application, {
        document: {
            getElementById: (id) => id === 'users-filters-panel'
                ? (hasPanel ? panel : null)
                : (hasToggle ? toggle : null),
        },
    });
    return { panel, attributes, listeners };
}

test('filter panel starts closed and two clicks synchronize visibility and expanded state', () => {
    const page = environment();
    assert.equal(page.panel.hidden, true);
    assert.equal(page.attributes.get('aria-expanded'), 'false');

    page.listeners.get('click')();
    assert.equal(page.panel.hidden, false);
    assert.equal(page.attributes.get('aria-expanded'), 'true');

    page.listeners.get('click')();
    assert.equal(page.panel.hidden, true);
    assert.equal(page.attributes.get('aria-expanded'), 'false');
});

for (const [label, hasPanel, hasToggle] of [
    ['neither element exists', false, false],
    ['panel is missing', false, true],
    ['toggle is missing', true, false],
]) {
    test(`filter module does not throw or register handlers when ${label}`, () => {
        const page = environment(hasPanel, hasToggle);
        assert.equal(page.listeners.size, 0);
        assert.equal(page.attributes.size, 0);
    });
}
