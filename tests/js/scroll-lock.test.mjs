import test from 'node:test';
import assert from 'node:assert/strict';

import { createScrollLock } from '../../assets/js/scroll-lock.js';

function fixture({ x = 13, y = 739, innerWidth = 390, clientWidth = 375 } = {}) {
  const bodyClasses = new Set();
  const rootClasses = new Set();
  const properties = new Map();
  const scrollCalls = [];
  const viewport = {
    scrollX: x,
    scrollY: y,
    innerWidth,
    scrollTo(nextX, nextY) {
      scrollCalls.push([nextX, nextY]);
      this.scrollX = nextX;
      this.scrollY = nextY;
    },
  };
  const classList = (classes) => ({
    add: (name) => classes.add(name),
    remove: (name) => classes.delete(name),
    contains: (name) => classes.has(name),
  });
  const documentElement = {
    clientWidth,
    classList: classList(rootClasses),
    style: {
      setProperty: (name, value) => properties.set(name, value),
      removeProperty: (name) => properties.delete(name),
    },
  };
  const body = { classList: classList(bodyClasses) };

  return { viewport, documentElement, body, bodyClasses, rootClasses, properties, scrollCalls };
}

test('scroll lock preserves coordinates and avoids an unnecessary scroll on release', () => {
  const state = fixture();
  const scrollLock = createScrollLock(state.viewport, state.documentElement, state.body);

  scrollLock.lock();
  scrollLock.lock();

  assert.equal(state.viewport.scrollY, 739);
  assert.equal(state.bodyClasses.has('is-locked'), true);
  assert.equal(state.rootClasses.has('is-scroll-locked'), true);
  assert.equal(state.properties.get('--scrollbar-compensation'), '15px');
  assert.equal(state.properties.has('--scroll-lock-y'), false);
  assert.equal(scrollLock.isLocked(), true);

  scrollLock.unlock();
  assert.deepEqual(state.scrollCalls, []);
  assert.equal(scrollLock.isLocked(), true);

  scrollLock.unlock();
  assert.deepEqual(state.scrollCalls, []);
  assert.equal(state.bodyClasses.has('is-locked'), false);
  assert.equal(state.rootClasses.has('is-scroll-locked'), false);
  assert.equal(state.properties.size, 0);
  assert.equal(scrollLock.isLocked(), false);
});

test('scroll lock restores saved coordinates only when the browser moved', () => {
  const state = fixture({ x: 7, y: 512 });
  const scrollLock = createScrollLock(state.viewport, state.documentElement, state.body);

  scrollLock.lock();
  state.viewport.scrollX = 0;
  state.viewport.scrollY = 0;
  scrollLock.unlock();

  assert.deepEqual(state.scrollCalls, [[7, 512]]);
});

test('extra unlock calls are harmless and never move the page', () => {
  const state = fixture({ x: 0, y: 320, innerWidth: 320, clientWidth: 320 });
  const scrollLock = createScrollLock(state.viewport, state.documentElement, state.body);

  scrollLock.unlock();

  assert.deepEqual(state.scrollCalls, []);
  assert.equal(state.properties.size, 0);
  assert.equal(state.bodyClasses.has('is-locked'), false);
  assert.equal(state.rootClasses.has('is-scroll-locked'), false);
});
