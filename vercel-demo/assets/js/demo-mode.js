document.addEventListener('submit', (event) => {
  const form = event.target instanceof HTMLFormElement && event.target.matches('[data-lead-form]')
    ? event.target
    : null;
  if (!form) return;

  event.preventDefault();
  event.stopImmediatePropagation();
  const status = form.querySelector('[data-form-status]');
  if (status) {
    status.textContent = 'Демонстрационная версия: заявка не отправляется.';
    status.dataset.state = 'success';
  }
}, true);
