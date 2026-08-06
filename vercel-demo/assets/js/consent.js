export const CONSENT_KEY = 'oksma.cookieConsent';

export function hasConsent(storage = globalThis.localStorage, now = Date.now()) {
  try {
    const value = JSON.parse(storage?.getItem(CONSENT_KEY) ?? 'null');
    return value?.accepted === true && Number(value.expiresAt) > now;
  } catch {
    return false;
  }
}

export function saveConsent(storage = globalThis.localStorage, now = Date.now(), days = 180) {
  const value = {
    accepted: true,
    expiresAt: now + days * 86400000,
  };
  try {
    storage?.setItem(CONSENT_KEY, JSON.stringify(value));
  } catch {
    // Consent remains effective for the current view even when storage is blocked.
  }
  return value;
}
