<section class="page-hero">
  <div class="page-hero__media" aria-hidden="true">
    <img src="<?= e($category['image']) ?>" width="900" height="675" alt="">
  </div>
  <div class="container page-hero__inner">
    <?= render_partial('breadcrumbs', ['items' => $breadcrumbs ?? [
        ['name' => 'Главная', 'url' => '/'],
        ['name' => $category['name'], 'url' => "/catalog/{$category['slug']}/"],
    ]]) ?>
    <p class="eyebrow">Каталог ОКСМА</p>
    <h1><?= e($category['name']) ?></h1>
    <p><?= e($category['summary']) ?></p>
  </div>
</section>

<section class="section catalog-listing">
  <div class="container">
    <div class="catalog-toolbar">
      <p>Показано моделей: <strong><?= count($products) ?></strong></p>
      <div class="view-toggle" role="group" aria-label="Вид каталога">
        <button type="button" aria-label="Показать плиткой" aria-pressed="true" data-view="grid"><?= icon('grid') ?></button>
        <button type="button" aria-label="Показать списком" aria-pressed="false" data-view="list"><?= icon('list') ?></button>
      </div>
    </div>
    <?php if ($products === []): ?>
      <div class="empty-state"><h2>Раздел готовится к наполнению</h2><p>Свяжитесь с нами, и мы подберём оборудование под вашу задачу.</p><button class="button button--primary" type="button" data-modal-open>Получить консультацию</button></div>
    <?php else: ?>
      <div class="product-grid" data-catalog data-view="grid">
        <?php foreach ($products as $product): ?><?= render_partial('product-card', ['product' => $product]) ?><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
