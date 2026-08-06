import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { runInNewContext } from 'node:vm';
import test from 'node:test';

test('demo mode intercepts lead forms and clearly reports that nothing was sent', async () => {
  const source = await readFile(new URL('../../scripts/static-demo/demo-mode.js', import.meta.url), 'utf8');
  const listeners = new Map();
  const status = { textContent: '', dataset: {} };

  class FakeHTMLFormElement {
    matches(selector) {
      return selector === '[data-lead-form]';
    }

    querySelector(selector) {
      return selector === '[data-form-status]' ? status : null;
    }
  }

  const document = {
    addEventListener(type, listener, capture) {
      listeners.set(type, { listener, capture });
    },
  };

  runInNewContext(source, { document, HTMLFormElement: FakeHTMLFormElement });
  const registration = listeners.get('submit');
  assert.equal(registration.capture, true);

  let prevented = false;
  let stopped = false;
  registration.listener({
    target: new FakeHTMLFormElement(),
    preventDefault() { prevented = true; },
    stopImmediatePropagation() { stopped = true; },
  });

  assert.equal(prevented, true);
  assert.equal(stopped, true);
  assert.equal(status.textContent, 'Демонстрационная версия: заявка не отправляется.');
  assert.equal(status.dataset.state, 'success');
});
