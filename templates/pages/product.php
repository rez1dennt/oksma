<section class="product-top section">
  <div class="container">
    <?= render_partial('breadcrumbs', ['items' => $breadcrumbs ?? [
        ['name' => 'Главная', 'url' => '/'],
        ['name' => $category['name'], 'url' => "/catalog/{$category['slug']}/"],
        ['name' => $product['name'], 'url' => "/product/{$product['slug']}/"],
    ]]) ?>
    <div class="product-top__grid">
      <div class="gallery" data-gallery role="region" aria-label="Фотографии <?= e($product['name']) ?>">
        <div class="gallery__stage">
          <img src="<?= e($product['images'][0]) ?>" width="1200" height="900" alt="<?= e($product['name'] . ' ' . $product['subtitle']) ?>" data-gallery-main>
        </div>
        <?php if (count($product['images']) > 1): ?>
          <div class="gallery__thumbs" aria-label="Выбор фотографии">
            <?php foreach ($product['images'] as $index => $image): ?>
              <button type="button" aria-label="Показать фотографию <?= $index + 1 ?>" aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>" data-gallery-thumb data-src="<?= e($image) ?>">
                <img src="<?= e($image) ?>" width="180" height="135" alt="">
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="product-summary">
        <?php if ($product['badge'] !== ''): ?><p class="product-badge"><?= e($product['badge']) ?></p><?php endif; ?>
        <h1><?= e($product['name']) ?> <span><?= e($product['subtitle']) ?></span></h1>
        <p class="product-summary__lead"><?= e($product['summary']) ?></p>
        <div class="product-summary__actions">
          <button class="button button--primary" type="button" data-modal-open>Запросить цену<?= icon('arrow-right') ?></button>
          <button class="button button--secondary" type="button" data-print><?= icon('printer') ?>Распечатать</button>
        </div>
        <dl class="product-meta">
          <div><dt>Артикул</dt><dd><?= e($product['sku']) ?></dd></div>
          <div><dt>Категория</dt><dd><a href="/catalog/<?= e($category['slug']) ?>/"><?= e($category['name']) ?></a></dd></div>
          <div><dt>Стоимость</dt><dd>по запросу</dd></div>
        </dl>
      </div>
    </div>
  </div>
</section>

<?= render_partial('product-benefits', ['benefits' => $product['benefits']]) ?>

<?php if (($product['applications'] ?? []) !== []): ?>
<section class="product-applications" aria-labelledby="product-applications-title">
  <div class="container">
    <header class="product-applications__heading">
      <p class="eyebrow">Под вашу задачу</p>
      <h2 id="product-applications-title">Варианты исполнения</h2>
      <p>Подберём состав оборудования и способ монтажа после проверки исходных данных по шасси.</p>
    </header>
    <div class="product-applications__grid">
      <?php foreach ($product['applications'] as $index => $application): ?>
        <article class="product-application-card">
          <span class="product-application-card__number" aria-hidden="true"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div>
            <h3><?= e($application['title']) ?></h3>
            <p><?= e($application['text']) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section product-details">
  <div class="container">
    <div class="tabs" data-tabs>
      <div class="tabs__list" role="tablist" aria-label="Информация о товаре">
        <button role="tab" id="tab-description" aria-selected="true" aria-controls="panel-description" tabindex="0">Описание</button>
        <button role="tab" id="tab-equipment" aria-selected="false" aria-controls="panel-equipment" tabindex="-1">Комплектация</button>
        <?php if ($documents !== []): ?>
          <button role="tab" id="tab-documents" aria-selected="false" aria-controls="panel-documents" tabindex="-1">Документы</button>
        <?php endif; ?>
      </div>
      <div class="tabs__panel" role="tabpanel" id="panel-description" aria-labelledby="tab-description">
        <div class="spec-grid">
          <section><h2>Характеристики</h2><dl class="spec-list"><?php foreach ($product['specs'] as $label => $value): ?><div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div><?php endforeach; ?></dl></section>
          <section><h2>Габариты</h2><dl class="spec-list"><?php foreach ($product['dimensions'] as $label => $value): ?><div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div><?php endforeach; ?></dl></section>
        </div>
        <div class="product-description"><h2>О модели</h2><p><?= e($product['description']) ?></p><p>Финальные характеристики, сроки изготовления и состав поставки фиксируются в коммерческом предложении после согласования задачи.</p></div>
      </div>
      <div class="tabs__panel" role="tabpanel" id="panel-equipment" aria-labelledby="tab-equipment" hidden>
        <h2>Базовая комплектация</h2>
        <ul class="equipment-list"><?php foreach ($product['equipment'] as $item): ?><li><?= icon('check') ?><?= e($item) ?></li><?php endforeach; ?></ul>
        <button class="button button--primary" type="button" data-modal-open>Уточнить комплектацию</button>
      </div>
      <?php if ($documents !== []): ?>
        <div class="tabs__panel" role="tabpanel" id="panel-documents" aria-labelledby="tab-documents" hidden>
          <div class="document-grid document-grid--product">
            <?php foreach ($documents as $document): ?>
              <?= render_partial('document-card', ['document' => $document, 'compact' => true]) ?>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if ($related !== []): ?>
<section class="section related-section">
  <div class="container">
    <div class="section-heading section-heading--compact"><div><p class="eyebrow">Другие решения</p><h2>Похожие модели</h2></div></div>
    <div class="product-grid product-grid--related"><?php foreach ($related as $relatedProduct): ?><?= render_partial('product-card', ['product' => $relatedProduct, 'headingTag' => 'h3']) ?><?php endforeach; ?></div>
  </div>
</section>
<?php endif; ?>
