import test from 'node:test';
import assert from 'node:assert/strict';
import { initializePerPageSelects } from '../../resources/js/per-page-select.js';

function control(hasSelect = true) {
    const listeners = new Map();
    const item = { submissions: 0, listeners };
    item.form = {
        querySelector(selector) {
            assert.equal(selector, 'select[name="per_page"]');
            return hasSelect ? { addEventListener: (event, handler) => listeners.set(event, handler) } : null;
        },
        requestSubmit() { item.submissions++; },
    };
    return item;
}

function root(items) {
    return { querySelectorAll(selector) {
        assert.equal(selector, '[data-per-page-select]');
        return items.map((item) => item.form);
    } };
}

test('initialization does not submit, but each change submits the owning form once', () => {
    const item = control();
    initializePerPageSelects(root([item]));
    assert.equal(item.submissions, 0);
    item.listeners.get('change')();
    assert.equal(item.submissions, 1);
    item.listeners.get('change')();
    assert.equal(item.submissions, 2);
});

test('multiple controls submit independently', () => {
    const first = control();
    const second = control();
    initializePerPageSelects(root([first, second]));
    second.listeners.get('change')();
    assert.equal(first.submissions, 0);
    assert.equal(second.submissions, 1);
    first.listeners.get('change')();
    assert.equal(first.submissions, 1);
    assert.equal(second.submissions, 1);
});

test('a missing select is skipped without preventing other controls from initializing', () => {
    const incomplete = control(false);
    const complete = control();
    initializePerPageSelects(root([incomplete, complete]));
    assert.equal(incomplete.listeners.size, 0);
    complete.listeners.get('change')();
    assert.equal(complete.submissions, 1);
});

test('a page without controls initializes safely', () => {
    assert.doesNotThrow(() => initializePerPageSelects(root([])));
});
