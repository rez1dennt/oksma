import test from 'node:test';
import assert from 'node:assert/strict';
import {
  normalizeRussianPhone,
  formatRussianPhone,
  isCompleteRussianPhone,
  deleteRussianPhoneDigit,
} from '../../assets/js/phone-mask.js';

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

test('backspace removes the preceding digit when a closing parenthesis is at the caret', () => {
  assert.deepEqual(deleteRussianPhoneDigit('+7 (243)', 8, 'backward'), {
    value: '+7 (24',
    caret: 6,
  });
});

test('backspace can clear the remaining country prefix', () => {
  assert.deepEqual(deleteRussianPhoneDigit('+7 (', 4, 'backward'), {
    value: '',
    caret: 0,
  });
});

test('delete removes the next digit across an opening parenthesis and keeps the caret', () => {
  assert.deepEqual(deleteRussianPhoneDigit('+7 (243) 5', 3, 'forward'), {
    value: '+7 (435)',
    caret: 3,
  });
});
