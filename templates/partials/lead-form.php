<?php
$formId = $formId ?? 'lead';
$csrfToken = function_exists('issue_csrf_token') ? issue_csrf_token() : '';
?>
<form class="lead-form" action="/submit.php" method="post" data-lead-form novalidate>
  <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
  <input type="hidden" name="started_at" value="<?= time() ?>">
  <input type="hidden" name="source" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
  <div class="honeypot" aria-hidden="true"><label for="<?= e($formId) ?>-website">Сайт</label><input id="<?= e($formId) ?>-website" name="website" tabindex="-1" autocomplete="off"></div>
  <div class="field-grid">
    <div class="field">
      <label for="<?= e($formId) ?>-name">Имя <span aria-hidden="true">*</span></label>
      <input id="<?= e($formId) ?>-name" name="name" autocomplete="name" required minlength="2" maxlength="80" aria-describedby="<?= e($formId) ?>-name-error">
      <span class="field__error" id="<?= e($formId) ?>-name-error" data-error-for="name"></span>
    </div>
    <div class="field">
      <label for="<?= e($formId) ?>-phone">Телефон <span aria-hidden="true">*</span></label>
      <input id="<?= e($formId) ?>-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="+7 (___) ___-__-__" required data-phone aria-describedby="<?= e($formId) ?>-phone-error">
      <span class="field__error" id="<?= e($formId) ?>-phone-error" data-error-for="phone"></span>
    </div>
  </div>
  <div class="field">
    <label for="<?= e($formId) ?>-email">Email</label>
    <input id="<?= e($formId) ?>-email" name="email" type="email" autocomplete="email" maxlength="160" aria-describedby="<?= e($formId) ?>-email-error">
    <span class="field__error" id="<?= e($formId) ?>-email-error" data-error-for="email"></span>
  </div>
  <div class="field">
    <label for="<?= e($formId) ?>-message">Что нужно рассчитать?</label>
    <textarea id="<?= e($formId) ?>-message" name="message" rows="4" maxlength="3000" aria-describedby="<?= e($formId) ?>-message-error"></textarea>
    <span class="field__error" id="<?= e($formId) ?>-message-error" data-error-for="message"></span>
  </div>
  <label class="consent"><input type="checkbox" name="privacy" value="1" required><span>Согласен с <a href="/privacy/">политикой конфиденциальности</a></span></label>
  <button class="button button--primary" type="submit"><span>Отправить заявку</span><?= icon('arrow-right') ?></button>
  <div class="form-status" role="status" aria-live="polite" data-form-status></div>
</form>
