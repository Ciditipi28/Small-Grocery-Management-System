'use strict';

const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../public/assets/theme.js'), 'utf8');

function browser({ stored = null, darkSystem = false, storageDenied = false } = {}) {
  const documentListeners = new Map();
  const windowListeners = new Map();
  const values = new Map(stored === null ? [] : [['grocery-theme', stored]]);
  let controlsReady = false;
  let systemListener;
  class Element {
    closest(selector) { return selector === '[data-theme-toggle]' ? button : null; }
  }
  const label = { textContent: '' };
  const sun = { hidden: false };
  const moon = { hidden: false };
  const button = Object.assign(new Element(), {
    attributes: {},
    setAttribute(name, value) { this.attributes[name] = value; },
    querySelectorAll(selector) {
      return ({ '[data-theme-label]': [label], '.theme-icon-light': [sun], '.theme-icon-dark': [moon] })[selector] || [];
    },
  });
  const document = {
    readyState: 'loading',
    documentElement: { dataset: {} },
    querySelectorAll(selector) { return controlsReady && selector === '[data-theme-toggle]' ? [button] : []; },
    addEventListener(name, callback) { documentListeners.set(name, callback); },
  };
  const media = {
    matches: darkSystem,
    addEventListener(name, callback) { if (name === 'change') systemListener = callback; },
  };
  const window = {
    matchMedia() { return media; },
    localStorage: {
      getItem(key) { if (storageDenied) throw new Error('Storage denied'); return values.get(key) ?? null; },
      setItem(key, value) { if (storageDenied) throw new Error('Storage denied'); values.set(key, value); },
    },
    addEventListener(name, callback) { windowListeners.set(name, callback); },
  };
  vm.runInNewContext(source, { window, document, Element });
  return {
    document, button, label, sun, moon, values,
    ready() { controlsReady = true; documentListeners.get('DOMContentLoaded')(); },
    click() { documentListeners.get('click')({ target: new Element() }); },
    system(dark) { media.matches = dark; systemListener({ matches: dark }); },
    storage(key, value) { windowListeners.get('storage')({ key, newValue: value }); },
  };
}

test('saved preference wins over system appearance before the page finishes loading', () => {
  const page = browser({ stored: 'light', darkSystem: true });
  assert.equal(page.document.documentElement.dataset.theme, 'light');
  page.ready();
  assert.equal(page.label.textContent, 'Dark mode');
  assert.equal(page.button.attributes['aria-pressed'], 'false');
});

test('invalid saved preference falls back to system and follows system changes', () => {
  const page = browser({ stored: 'invalid', darkSystem: true });
  assert.equal(page.document.documentElement.dataset.theme, 'dark');
  page.ready();
  assert.equal(page.sun.hidden, false);
  assert.equal(page.moon.hidden, true);
  page.system(false);
  assert.equal(page.document.documentElement.dataset.theme, 'light');
  assert.equal(page.label.textContent, 'Dark mode');
});

test('toggling persists a preference that survives another page load', () => {
  const page = browser();
  page.ready();
  page.click();
  assert.equal(page.document.documentElement.dataset.theme, 'dark');
  assert.equal(page.values.get('grocery-theme'), 'dark');
  assert.equal(page.button.attributes['aria-label'], 'Switch to light mode');
  assert.equal(page.button.attributes['aria-pressed'], 'true');
  const nextPage = browser({ stored: page.values.get('grocery-theme'), darkSystem: false });
  assert.equal(nextPage.document.documentElement.dataset.theme, 'dark');
  page.system(true);
  page.system(false);
  assert.equal(page.document.documentElement.dataset.theme, 'dark');
});

test('another tab can change or clear the preference', () => {
  const page = browser({ stored: 'dark' });
  page.ready();
  page.storage('grocery-theme', 'light');
  assert.equal(page.document.documentElement.dataset.theme, 'light');
  page.storage('unrelated-key', 'dark');
  assert.equal(page.document.documentElement.dataset.theme, 'light');
  page.system(true);
  assert.equal(page.document.documentElement.dataset.theme, 'light');
  page.storage(null, null);
  assert.equal(page.document.documentElement.dataset.theme, 'dark');
  page.system(false);
  assert.equal(page.document.documentElement.dataset.theme, 'light');
});

test('blocked browser storage does not break rendering or the toggle', () => {
  const page = browser({ storageDenied: true, darkSystem: true });
  assert.equal(page.document.documentElement.dataset.theme, 'dark');
  page.ready();
  assert.doesNotThrow(() => page.click());
  assert.equal(page.document.documentElement.dataset.theme, 'light');
  assert.equal(page.label.textContent, 'Dark mode');
  assert.equal(page.values.size, 0);
  page.system(false);
  page.system(true);
  assert.equal(page.document.documentElement.dataset.theme, 'light');
});
