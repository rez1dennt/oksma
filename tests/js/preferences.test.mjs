import test from 'node:test';
import assert from 'node:assert/strict';
import { readCatalogView, saveCatalogView } from '../../assets/js/catalog-view.js';
import { hasConsent, saveConsent } from '../../assets/js/consent.js';

function memoryStorage() {
  const values = new Map();
  return {
    getItem: (key) => values.has(key) ? values.get(key) : null,
    setItem: (key, value) => values.set(key, value),
  };
}

test('catalog view persists only supported values and survives blocked storage', () => {
  const storage = memoryStorage();
  assert.equal(readCatalogView(storage), 'grid');
  assert.equal(saveCatalogView('list', storage), 'list');
  assert.equal(readCatalogView(storage), 'list');
  assert.equal(saveCatalogView('cards', storage), 'grid');
  assert.equal(readCatalogView({ getItem() { throw new Error('blocked'); } }), 'grid');
});

test('consent is valid until its stored expiry and handles bad data', () => {
  const storage = memoryStorage();
  const now = Date.UTC(2026, 7, 6);
  assert.equal(hasConsent(storage, now), false);
  saveConsent(storage, now, 30);
  assert.equal(hasConsent(storage, now + 29 * 86400000), true);
  assert.equal(hasConsent(storage, now + 31 * 86400000), false);
  assert.equal(hasConsent({ getItem: () => 'not-json' }, now), false);
});
