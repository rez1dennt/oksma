<?php $compact = (bool) ($compact ?? false); ?>
<article class="document-card<?= $compact ? ' document-card--compact' : '' ?>">
  <div class="document-card__header">
    <p class="document-card__type">Декларация о соответствии</p>
    <span class="document-card__status"><?= e($document['status']) ?></span>
  </div>
  <h2><?= e($document['title']) ?></h2>
  <dl class="document-card__meta">
    <div>
      <dt>Регистрационный номер</dt>
      <dd class="document-card__number"><?= e($document['registration_number']) ?></dd>
    </div>
    <div>
      <dt>Срок действия</dt>
      <dd><time datetime="<?= e($document['valid_from']) ?>"><?= e(format_document_date($document['valid_from'])) ?></time> — <time datetime="<?= e($document['valid_until']) ?>"><?= e(format_document_date($document['valid_until'])) ?></time></dd>
    </div>
  </dl>
  <div class="document-card__groups">
    <h3>Распространяется на модели</h3>
    <ul>
      <?php foreach ($document['product_groups'] as $group): ?>
        <li><?= e($group) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="document-card__actions">
    <a class="button button--primary" href="<?= e($document['file']) ?>" target="_blank" rel="noopener" aria-label="Открыть PDF: <?= e($document['title']) ?>, в новой вкладке">Открыть PDF<span class="sr-only"> в новой вкладке</span></a>
    <a class="button button--secondary" href="<?= e($document['file']) ?>" download aria-label="Скачать PDF: <?= e($document['title']) ?>">Скачать</a>
  </div>
</article>
