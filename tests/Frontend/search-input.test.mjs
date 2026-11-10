import test from 'node:test';
import assert from 'node:assert/strict';
import { initializeSearchInputs } from '../../resources/js/search-input.js';

// Uproszczony DOM sprawdza rzeczywisty moduł; nie symuluje renderowania CSS ani klawiatury.
function field(value = '', { hasInput = true, hasButton = true } = {}) {
    const inputListeners = new Map();
    const buttonListeners = new Map();
    const input = { value, addEventListener: (event, listener) => inputListeners.set(event, listener) };
    const button = {
        hidden: false,
        dataset: { clearUrl: '/admin/users?account_type=user&sort=name&direction=desc' },
        addEventListener: (event, listener) => buttonListeners.set(event, listener),
    };
    const container = {
        querySelector: (selector) => ({
            'input[type="search"]': hasInput ? input : null,
            '[data-clear-search]': hasButton ? button : null,
        })[selector],
    };
    return { input, button, container, inputListeners, buttonListeners };
}

function root(fields) {
    return { querySelectorAll: (selector) => {
        assert.equal(selector, '[data-search-input]');
        return fields.map((item) => item.container);
    } };
}

for (const [label, value, hidden] of [
    ['empty', '', true], ['nonempty', 'Sport', false], ['whitespace', ' ', false],
]) {
    test(`clear control initializes visibility for ${label} input`, () => {
        const item = field(value);
        item.button.hidden = !hidden;
        initializeSearchInputs(root([item]));
        assert.equal(item.button.hidden, hidden);
    });
}

test('typing, deleting and retyping update visibility without submitting the search', () => {
    const item = field();
    initializeSearchInputs(root([item]));

    for (const [value, hidden] of [['Sport', false], ['', true], ['S', false]]) {
        item.input.value = value;
        item.inputListeners.get('input')();
        assert.equal(item.button.hidden, hidden);
    }
});

test('click clears an unsent phrase and navigates to the supplied applied criteria', () => {
    const item = field('Niewysłana fraza & + zażółć');
    const navigations = [];
    const previousWindow = globalThis.window;
    globalThis.window = { location: { assign: (url) => {
        assert.equal(item.input.value, '');
        assert.equal(item.button.hidden, true);
        navigations.push(url);
    } } };

    try {
        initializeSearchInputs(root([item]));
        item.buttonListeners.get('click')();
        assert.deepEqual(navigations, ['/admin/users?account_type=user&sort=name&direction=desc']);
    } finally {
        if (previousWindow === undefined) {
            delete globalThis.window;
        } else {
            globalThis.window = previousWindow;
        }
    }
});

test('editing one instance does not change another instance', () => {
    const first = field('First');
    const second = field('Second');
    initializeSearchInputs(root([first, second]));

    first.input.value = '';
    first.inputListeners.get('input')();
    assert.equal(first.button.hidden, true);
    assert.equal(second.button.hidden, false);
    assert.equal(second.input.value, 'Second');

    second.input.value = '';
    second.inputListeners.get('input')();
    assert.equal(second.button.hidden, true);
    first.input.value = 'Again';
    first.inputListeners.get('input')();
    assert.equal(first.button.hidden, false);
    assert.equal(second.button.hidden, true);
});

test('page without search components initializes safely', () => {
    assert.doesNotThrow(() => initializeSearchInputs(root([])));
});

for (const [label, options] of [
    ['input is missing', { hasInput: false }],
    ['button is missing', { hasButton: false }],
    ['both elements are missing', { hasInput: false, hasButton: false }],
]) {
    test(`incomplete component is skipped when ${label}, while the next one initializes`, () => {
        const incomplete = field('', options);
        const complete = field();
        assert.doesNotThrow(() => initializeSearchInputs(root([incomplete, complete])));
        assert.equal(incomplete.inputListeners.size, 0);
        assert.equal(incomplete.buttonListeners.size, 0);
        assert.equal(complete.button.hidden, true);
        assert.equal(complete.inputListeners.has('input'), true);
        assert.equal(complete.buttonListeners.has('click'), true);
    });
}
