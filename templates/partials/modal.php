<div class="modal" data-modal hidden>
  <div class="modal__backdrop" data-modal-close></div>
  <section class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="lead-dialog-title" tabindex="-1">
    <button class="icon-button modal__close" type="button" aria-label="Закрыть окно" data-modal-close><?= icon('close') ?></button>
    <p class="eyebrow">Расчёт стоимости</p>
    <h2 id="lead-dialog-title">Обсудим вашу задачу</h2>
    <p>Оставьте контакты. Подготовим предложение под вашу задачу.</p>
    <?= render_partial('lead-form', ['formId' => 'modal-lead', 'compact' => true]) ?>
  </section>
</div>
