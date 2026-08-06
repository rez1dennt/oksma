<?php
$cards = product_benefit_cards($benefits ?? []);
?>
<section class="product-benefits" aria-labelledby="product-benefits-title">
  <div class="container">
    <header class="product-benefits__heading">
      <p class="eyebrow">Продумано для работы</p>
      <h2 id="product-benefits-title">Ключевые преимущества</h2>
    </header>
    <ol class="product-benefits__list">
      <?php foreach ($cards as $card): ?>
        <li class="product-benefit-card">
          <div class="product-benefit-card__topline">
            <span class="product-benefit-card__icon"><?= icon($card['icon']) ?></span>
            <span class="product-benefit-card__index" aria-hidden="true"><?= e($card['index']) ?></span>
          </div>
          <h3><?= e($card['title']) ?></h3>
          <p><?= e($card['description']) ?></p>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
