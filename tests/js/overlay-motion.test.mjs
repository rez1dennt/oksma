import test from 'node:test';
import assert from 'node:assert/strict';
import { scrollbarCompensation, transitionTimeout } from '../../assets/js/overlay-motion.js';

test('scrollbar compensation uses viewport minus document width and never goes negative', () => {
  assert.equal(scrollbarCompensation(1440, 1423), 17);
  assert.equal(scrollbarCompensation(390, 390), 0);
  assert.equal(scrollbarCompensation(375, 390), 0);
});

test('transition timeout resolves through the fallback when no transition event arrives', async () => {
  const listeners = new Map();
  const element = {
    addEventListener(type, listener) { listeners.set(type, listener); },
    removeEventListener(type) { listeners.delete(type); },
  };

  await transitionTimeout(element, 2);
  assert.equal(listeners.has('transitionend'), false);
});

test('transition timeout resolves when the target transition ends', async () => {
  const listeners = new Map();
  const element = {
    addEventListener(type, listener) { listeners.set(type, listener); },
    removeEventListener(type) { listeners.delete(type); },
  };

  const pending = transitionTimeout(element, 100);
  listeners.get('transitionend')({ target: element });
  await pending;
  assert.equal(listeners.has('transitionend'), false);
});
