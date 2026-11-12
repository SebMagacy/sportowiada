import test from 'node:test';
import assert from 'node:assert/strict';
import { initializeRowSelections } from '../../resources/js/row-selection.js';

function element(properties = {}) {
    const listeners = new Map();
    return {
        checked: false, disabled: false, indeterminate: false, focused: false,
        ...properties,
        addEventListener(event, handler) { listeners.set(event, handler); },
        trigger(event) { listeners.get(event)(); },
        focus() { this.focused = true; },
    };
}

function selection(rowCount = 2, options = {}) {
    const page = options.missingPage ? null : element();
    const rows = Array.from({ length: rowCount }, () => element());
    const toolbar = { hidden: true };
    const count = { textContent: '' };
    const clear = element();
    const elements = {
        '[data-select-page]': page,
        '[data-selection-toolbar]': toolbar,
        '[data-selection-count]': count,
        '[data-clear-selection]': clear,
    };
    const container = {
        querySelector: (selector) => options.withoutToolbar && selector !== '[data-select-page]' ? null : elements[selector] ?? null,
        querySelectorAll: (selector) => selector === '[data-select-user]' ? rows : [],
    };
    return { container, page, rows, toolbar, count, clear };
}

function initialize(...lists) {
    initializeRowSelections({ querySelectorAll: (selector) => selector === '[data-row-selection]' ? lists.map((list) => list.container) : [] });
}

test('initialization starts with no selection and a hidden toolbar', () => {
    const list = selection();
    initialize(list);
    assert.equal(list.page.disabled, false);
    assert.equal(list.page.checked, false);
    assert.equal(list.page.indeterminate, false);
    assert.equal(list.count.textContent, '0');
    assert.equal(list.toolbar.hidden, true);
});

test('page checkbox selects and deselects every row and updates the toolbar', () => {
    const list = selection();
    initialize(list);
    list.page.checked = true;
    list.page.trigger('change');
    assert.deepEqual(list.rows.map((row) => row.checked), [true, true]);
    assert.equal(list.count.textContent, '2');
    assert.equal(list.toolbar.hidden, false);
    list.page.checked = false;
    list.page.trigger('change');
    assert.deepEqual(list.rows.map((row) => row.checked), [false, false]);
    assert.equal(list.count.textContent, '0');
    assert.equal(list.toolbar.hidden, true);
});

test('individual changes synchronize partial, complete and empty selection', () => {
    const list = selection();
    initialize(list);
    list.rows[0].checked = true;
    list.rows[0].trigger('change');
    assert.equal(list.page.checked, false);
    assert.equal(list.page.indeterminate, true);
    assert.equal(list.count.textContent, '1');
    list.rows[1].checked = true;
    list.rows[1].trigger('change');
    assert.equal(list.page.checked, true);
    assert.equal(list.page.indeterminate, false);
    list.rows[0].checked = false;
    list.rows[0].trigger('change');
    assert.equal(list.page.checked, false);
    assert.equal(list.page.indeterminate, true);
    list.rows[1].checked = false;
    list.rows[1].trigger('change');
    assert.equal(list.page.indeterminate, false);
    assert.equal(list.count.textContent, '0');
    assert.equal(list.toolbar.hidden, true);
});

test('clear removes selection, hides toolbar and returns focus to the page checkbox', () => {
    const list = selection();
    initialize(list);
    list.rows[0].checked = true;
    list.rows[0].trigger('change');
    list.clear.trigger('click');
    assert.deepEqual(list.rows.map((row) => row.checked), [false, false]);
    assert.equal(list.page.checked, false);
    assert.equal(list.page.indeterminate, false);
    assert.equal(list.page.focused, true);
    assert.equal(list.count.textContent, '0');
    assert.equal(list.toolbar.hidden, true);
});

test('empty results disable the page checkbox and hide the toolbar', () => {
    const list = selection(0);
    initialize(list);
    assert.equal(list.page.disabled, true);
    assert.equal(list.page.checked, false);
    assert.equal(list.page.indeterminate, false);
    assert.equal(list.count.textContent, '0');
    assert.equal(list.toolbar.hidden, true);
});

test('multiple lists keep selection and clearing independent', () => {
    const first = selection();
    const second = selection(1);
    initialize(first, second);
    first.page.checked = true;
    first.page.trigger('change');
    second.page.checked = true;
    second.page.trigger('change');
    second.clear.trigger('click');
    assert.equal(first.count.textContent, '2');
    assert.equal(first.toolbar.hidden, false);
    assert.deepEqual(first.rows.map((row) => row.checked), [true, true]);
    assert.equal(second.count.textContent, '0');
    assert.equal(second.toolbar.hidden, true);
});

test('missing page checkbox does not stop another list from initializing', () => {
    const complete = selection();
    initialize(selection(2, { missingPage: true }), complete);
    assert.equal(complete.count.textContent, '0');
    assert.equal(complete.page.disabled, false);
});

test('a table without optional toolbar controls still supports selection', () => {
    const list = selection(1, { withoutToolbar: true });
    initialize(list);
    list.page.checked = true;
    list.page.trigger('change');
    assert.equal(list.rows[0].checked, true);
});

test('a page without selection containers initializes safely', () => {
    assert.doesNotThrow(() => initialize());
});
