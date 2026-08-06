import test from 'node:test';
import assert from 'node:assert/strict';
import { normalizeRussianPhone, formatRussianPhone, isCompleteRussianPhone } from '../../assets/js/phone-mask.js';

test('normalizes Russian numbers entered with 8, 7 or ten local digits', () => {
  assert.equal(normalizeRussianPhone('8 937 435-17-00'), '79374351700');
  assert.equal(normalizeRussianPhone('+7 (937) 435-17-00'), '79374351700');
  assert.equal(normalizeRussianPhone('9374351700'), '79374351700');
});

test('formats complete and partial input without inventing digits', () => {
  assert.equal(formatRussianPhone('79374351700'), '+7 (937) 435-17-00');
  assert.equal(formatRussianPhone('79374'), '+7 (937) 4');
  assert.equal(formatRussianPhone(''), '');
});

test('checks completeness by normalized digit count', () => {
  assert.equal(isCompleteRussianPhone('+7 (937) 435-17-00'), true);
  assert.equal(isCompleteRussianPhone('+7 (937) 435-17'), false);
});
