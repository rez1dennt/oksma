import test from 'node:test';
import assert from 'node:assert/strict';

import { createScrollLock } from '../../assets/js/scroll-lock.js';

function fixture({ x = 13, y = 739, innerWidth = 390, clientWidth = 375 } = {}) {
  const classes = new Set();
  const properties = new Map();
  const scrollCalls = [];
  const viewport = {
    scrollX: x,
    scrollY: y,
    innerWidth,
    scrollTo: (nextX, nextY) => scrollCalls.push([nextX, nextY]),
  };
  const documentElement = {
    clientWidth,
    style: {
      setProperty: (name, value) => properties.set(name, value),
      removeProperty: (name) => properties.delete(name),
    },
  };
  const body = {
    classList: {
      add: (name) => classes.add(name),
      remove: (name) => classes.delete(name),
      contains: (name) => classes.has(name),
    },
  };

  return { viewport, documentElement, body, classes, properties, scrollCalls };
}

test('scroll lock restores the exact coordinates after the final unlock', () => {
  const state = fixture();
  const scrollLock = createScrollLock(state.viewport, state.documentElement, state.body);

  scrollLock.lock();
  scrollLock.lock();

  assert.equal(state.classes.has('is-locked'), true);
  assert.equal(state.properties.get('--scroll-lock-x'), '-13px');
  assert.equal(state.properties.get('--scroll-lock-y'), '-739px');
  assert.equal(state.properties.get('--scrollbar-compensation'), '15px');
  assert.equal(scrollLock.isLocked(), true);

  scrollLock.unlock();
  assert.deepEqual(state.scrollCalls, []);
  assert.equal(scrollLock.isLocked(), true);

  scrollLock.unlock();
  assert.deepEqual(state.scrollCalls, [[13, 739]]);
  assert.equal(state.classes.has('is-locked'), false);
  assert.equal(state.properties.size, 0);
  assert.equal(scrollLock.isLocked(), false);
});

test('extra unlock calls are harmless and never move the page', () => {
  const state = fixture({ x: 0, y: 320, innerWidth: 320, clientWidth: 320 });
  const scrollLock = createScrollLock(state.viewport, state.documentElement, state.body);

  scrollLock.unlock();

  assert.deepEqual(state.scrollCalls, []);
  assert.equal(state.properties.size, 0);
});
