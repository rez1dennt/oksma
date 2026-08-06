<section class="documents-hero section">
  <div class="container">
    <?= render_partial('breadcrumbs', ['items' => $breadcrumbs ?? [
        ['name' => 'Главная', 'url' => '/'],
        ['name' => 'Документы и декларации', 'url' => '/documents/'],
    ]]) ?>
    <p class="eyebrow">Официальные документы</p>
    <h1>Документы и декларации</h1>
    <p>Публикуем действующие декларации на серийно выпускаемую технику ОКСМА. Документы можно открыть в браузере или скачать.</p>
  </div>
</section>

<section class="section documents-section">
  <div class="container document-grid">
    <?php foreach ($documents as $document): ?>
      <?= render_partial('document-card', ['document' => $document]) ?>
    <?php endforeach; ?>
  </div>
</section>
