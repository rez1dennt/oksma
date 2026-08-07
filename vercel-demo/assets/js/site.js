import { deleteRussianPhoneDigit, formatRussianPhone, isCompleteRussianPhone } from './phone-mask.js';
import { readCatalogView, saveCatalogView } from './catalog-view.js';
import { hasConsent, saveConsent } from './consent.js';
import { afterNextPaint, transitionTimeout } from './overlay-motion.js';
import { createScrollLock } from './scroll-lock.js';

const focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
const pageScrollLock = createScrollLock(window, document.documentElement, document.body);
const focusWithoutScroll = (element) => element?.focus?.({ preventScroll: true });

function trapFocus(container, event) {
  if (event.key !== 'Tab') return;
  const focusable = [...container.querySelectorAll(focusableSelector)].filter((element) => !element.hidden);
  if (focusable.length === 0) return;
  const first = focusable[0];
  const last = focusable.at(-1);
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault();
    last.focus();
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault();
    first.focus();
  }
}

function setPageInert(isInert, excluded) {
  ['header', 'main', 'footer'].forEach((selector) => {
    const element = document.querySelector(selector);
    if (element && !element.contains(excluded)) element.inert = isInert;
  });
}

function setupMenu() {
  const toggle = document.querySelector('[data-menu-toggle]');
  const menu = document.querySelector('[data-mobile-menu]');
  if (!toggle || !menu) return;
  const closeButton = menu.querySelector('[data-menu-close]');
  const panel = menu.querySelector('.mobile-menu__panel');
  let state = 'closed';
  let menuLocked = false;

  const finishClose = (restoreFocus) => {
    menu.hidden = true;
    state = 'closed';
    setPageInert(false, menu);
    if (menuLocked) {
      pageScrollLock.unlock();
      menuLocked = false;
    }
    if (restoreFocus) focusWithoutScroll(toggle);
  };

  const close = async ({ restoreFocus = true, immediate = false } = {}) => {
    if (state === 'closed' || state === 'closing') return;
    state = 'closing';
    menu.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Открыть меню');
    if (immediate) {
      finishClose(restoreFocus);
      return;
    }
    await transitionTimeout(panel, 360);
    if (state === 'closing') finishClose(restoreFocus);
  };
  const open = async () => {
    if (state === 'open' || state === 'opening') return;
    state = 'opening';
    menu.hidden = false;
    toggle.setAttribute('aria-expanded', 'true');
    toggle.setAttribute('aria-label', 'Закрыть меню');
    if (!menuLocked) {
      pageScrollLock.lock();
      menuLocked = true;
    }
    setPageInert(true, menu);
    await afterNextPaint();
    if (state !== 'opening') return;
    menu.classList.add('is-open');
    state = 'open';
    focusWithoutScroll(closeButton ?? panel ?? menu);
  };

  toggle.addEventListener('click', () => state === 'closed' || state === 'closing' ? open() : close());
  closeButton?.addEventListener('click', () => close());
  menu.addEventListener('click', (event) => {
    if (event.target === menu) close();
    else if (event.target.closest('a')) close({ restoreFocus: false, immediate: true });
  });
  menu.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') close();
    else trapFocus(menu, event);
  });
}

function setupModal() {
  const modal = document.querySelector('[data-modal]');
  if (!modal) return;
  const dialog = modal.querySelector('[role="dialog"]');
  let returnFocus = null;

  const close = () => {
    modal.hidden = true;
    pageScrollLock.unlock();
    setPageInert(false, modal);
    focusWithoutScroll(returnFocus);
  };
  const open = (trigger) => {
    returnFocus = trigger;
    modal.hidden = false;
    pageScrollLock.lock();
    setPageInert(true, modal);
    focusWithoutScroll(dialog);
  };

  document.querySelectorAll('[data-modal-open]').forEach((button) => button.addEventListener('click', () => open(button)));
  modal.querySelectorAll('[data-modal-close]').forEach((button) => button.addEventListener('click', close));
  modal.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') close();
    else trapFocus(modal, event);
  });
}

function setupPhoneMasks() {
  document.querySelectorAll('[data-phone]').forEach((input) => {
    input.addEventListener('keydown', (event) => {
      if ((event.key !== 'Backspace' && event.key !== 'Delete') || input.selectionStart !== input.selectionEnd) return;
      const edit = deleteRussianPhoneDigit(
        input.value,
        input.selectionStart ?? input.value.length,
        event.key === 'Delete' ? 'forward' : 'backward',
      );
      if (!edit) return;
      event.preventDefault();
      input.value = edit.value;
      input.setSelectionRange(edit.caret, edit.caret);
      input.setCustomValidity('');
      input.removeAttribute('aria-invalid');
    });
    input.addEventListener('input', () => {
      input.value = formatRussianPhone(input.value);
      input.setSelectionRange(input.value.length, input.value.length);
      input.setCustomValidity('');
      input.removeAttribute('aria-invalid');
    });
    input.addEventListener('blur', () => {
      if (input.value && !isCompleteRussianPhone(input.value)) {
        input.setCustomValidity('Введите телефон полностью');
        input.setAttribute('aria-invalid', 'true');
      }
    });
  });
}

function setupCatalogView() {
  const catalog = document.querySelector('[data-catalog]');
  if (!catalog) return;
  const buttons = [...document.querySelectorAll('[data-view]')].filter((button) => button.tagName === 'BUTTON');
  const apply = (requested) => {
    const view = requested === 'list' ? 'list' : 'grid';
    catalog.dataset.view = view;
    buttons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.view === view)));
  };
  apply(readCatalogView());
  buttons.forEach((button) => button.addEventListener('click', () => apply(saveCatalogView(button.dataset.view))));
}

function setupGallery() {
  document.querySelectorAll('[data-gallery]').forEach((gallery) => {
    const main = gallery.querySelector('[data-gallery-main]');
    const thumbs = [...gallery.querySelectorAll('[data-gallery-thumb]')];
    if (!main) return;
    thumbs.forEach((thumb) => thumb.addEventListener('click', () => {
      main.src = thumb.dataset.src;
      thumbs.forEach((item) => item.setAttribute('aria-pressed', String(item === thumb)));
    }));
  });
}

function setupTabs() {
  document.querySelectorAll('[data-tabs]').forEach((tabsRoot) => {
    const tabs = [...tabsRoot.querySelectorAll('[role="tab"]')];
    const activate = (tab, moveFocus = true) => {
      tabs.forEach((item) => {
        const selected = item === tab;
        item.setAttribute('aria-selected', String(selected));
        item.tabIndex = selected ? 0 : -1;
        const panel = document.getElementById(item.getAttribute('aria-controls'));
        if (panel) panel.hidden = !selected;
      });
      if (moveFocus) tab.focus();
    };
    tabs.forEach((tab, index) => {
      tab.addEventListener('click', () => activate(tab, false));
      tab.addEventListener('keydown', (event) => {
        let targetIndex = null;
        if (event.key === 'ArrowRight') targetIndex = (index + 1) % tabs.length;
        if (event.key === 'ArrowLeft') targetIndex = (index - 1 + tabs.length) % tabs.length;
        if (event.key === 'Home') targetIndex = 0;
        if (event.key === 'End') targetIndex = tabs.length - 1;
        if (targetIndex !== null) {
          event.preventDefault();
          activate(tabs[targetIndex]);
        }
      });
    });
  });
}

function setFieldError(form, name, message) {
  const field = form.elements.namedItem(name);
  const output = form.querySelector(`[data-error-for="${name}"]`);
  if (field instanceof HTMLElement) {
    field.setAttribute('aria-invalid', message ? 'true' : 'false');
    if ('setCustomValidity' in field) field.setCustomValidity(message);
  }
  if (output) output.textContent = message;
}

function validateForm(form) {
  const data = new FormData(form);
  const errors = {};
  const name = String(data.get('name') ?? '').trim();
  const phone = String(data.get('phone') ?? '');
  const email = String(data.get('email') ?? '').trim();
  if (name.length < 2) errors.name = 'Укажите имя — минимум 2 символа.';
  if (!isCompleteRussianPhone(phone)) errors.phone = 'Введите телефон полностью.';
  if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.email = 'Проверьте адрес электронной почты.';
  if (!data.get('privacy')) errors.privacy = 'Подтвердите согласие с политикой конфиденциальности.';
  ['name', 'phone', 'email', 'message'].forEach((field) => setFieldError(form, field, errors[field] ?? ''));
  return errors;
}

function setupForms() {
  document.querySelectorAll('[data-lead-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const status = form.querySelector('[data-form-status]');
      const submit = form.querySelector('[type="submit"]');
      const errors = validateForm(form);
      if (Object.keys(errors).length > 0) {
        status.textContent = errors.privacy ?? 'Проверьте выделенные поля.';
        form.querySelector('[aria-invalid="true"]')?.focus();
        return;
      }

      submit.disabled = true;
      status.textContent = 'Отправляем заявку…';
      try {
        const response = await fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          headers: { Accept: 'application/json' },
        });
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.message || 'Не удалось отправить заявку.');
        form.reset();
        status.textContent = result.message || 'Спасибо! Заявка отправлена.';
      } catch (error) {
        status.textContent = error.message || 'Произошла ошибка. Позвоните нам или попробуйте ещё раз.';
      } finally {
        submit.disabled = false;
      }
    });
  });
}

function setupCookieNotice() {
  const notice = document.querySelector('[data-cookie-notice]');
  if (!notice || hasConsent()) return;
  notice.hidden = false;
  notice.querySelector('[data-cookie-accept]')?.addEventListener('click', () => {
    saveConsent();
    notice.hidden = true;
  });
}

setupMenu();
setupModal();
setupPhoneMasks();
setupCatalogView();
setupGallery();
setupTabs();
setupForms();
setupCookieNotice();
document.querySelectorAll('[data-print]').forEach((button) => button.addEventListener('click', () => window.print()));
