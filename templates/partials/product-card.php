<article class="product-card">
  <a class="product-card__media" href="/product/<?= e($product['slug']) ?>/" tabindex="-1" aria-hidden="true">
    <img src="<?= e($product['images'][0]) ?>" width="640" height="480" loading="lazy" alt="">
  </a>
  <div class="product-card__body">
    <p class="product-card__sku"><?= e($product['sku']) ?></p>
    <h2 class="product-card__title"><a href="/product/<?= e($product['slug']) ?>/"><?= e($product['name']) ?> <span><?= e($product['subtitle']) ?></span></a></h2>
    <p class="product-card__summary"><?= e($product['summary']) ?></p>
    <a class="text-link" href="/product/<?= e($product['slug']) ?>/" aria-label="Подробнее о <?= e($product['name']) ?>"><?= icon('arrow-right') ?>Подробнее</a>
  </div>
</article>
