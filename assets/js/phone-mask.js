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

export function isCompleteRussianPhone(value) {
  return normalizeRussianPhone(value).length === 11;
}
