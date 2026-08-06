export function normalizeRussianPhone(value) {
  let digits = String(value ?? '').replace(/\D/g, '').slice(0, 11);
  if (!digits) return '';

  if (digits.startsWith('8')) {
    digits = `7${digits.slice(1)}`;
  } else if (!digits.startsWith('7')) {
    digits = `7${digits}`.slice(0, 11);
  }

  return digits;
}

export function formatRussianPhone(value) {
  const digits = normalizeRussianPhone(value);
  if (!digits) return '';

  const local = digits.slice(1);
  let formatted = '+7';
  if (local.length > 0) formatted += ` (${local.slice(0, 3)}`;
  if (local.length >= 3) formatted += ')';
  if (local.length > 3) formatted += ` ${local.slice(3, 6)}`;
  if (local.length > 6) formatted += `-${local.slice(6, 8)}`;
  if (local.length > 8) formatted += `-${local.slice(8, 10)}`;
  return formatted;
}

export function deleteRussianPhoneDigit(value, caret, direction = 'backward') {
  const text = String(value ?? '');
  const offset = Math.max(0, Math.min(Number(caret) || 0, text.length));
  const adjacent = direction === 'forward' ? text[offset] : text[offset - 1];
  if (adjacent === undefined || /\d/.test(adjacent)) return null;

  let index = direction === 'forward' ? offset : offset - 1;
  const step = direction === 'forward' ? 1 : -1;
  while (index >= 0 && index < text.length && !/\d/.test(text[index])) index += step;
  if (index < 0 || index >= text.length || index <= 1) return { value: '', caret: 0 };

  const formatted = formatRussianPhone(text.slice(0, index) + text.slice(index + 1));
  return { value: formatted, caret: formatted.length };
}

export function isCompleteRussianPhone(value) {
  return normalizeRussianPhone(value).length === 11;
}
