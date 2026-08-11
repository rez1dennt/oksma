<?php $categories = array_values(catalog_categories()); ?>
<header class="site-header" data-header>
  <div class="container site-header__inner">
    <a class="brand brand--header-gold" href="/" aria-label="ОКСМА, на главную">
      <img class="brand__mark" src="/assets/images/logo-oksma-footer-gold.webp" width="220" height="220" alt="">
      <img class="brand__wordmark" src="/assets/images/logo-oksma-header-gold.webp" width="420" height="76" alt="">
    </a>
    <nav class="desktop-nav" aria-label="Основная навигация">
      <ul>
        <?php foreach ($categories as $category): ?>
          <li><a href="/catalog/<?= e($category['slug']) ?>/"><?= e($category['nav_name']) ?></a></li>
        <?php endforeach; ?>
        <li><a href="/#request">Контакты</a></li>
      </ul>
    </nav>
    <a class="header-phone" href="tel:<?= e($config['phones'][0]['href']) ?>"><?= e($config['phones'][0]['display']) ?></a>
    <button class="menu-toggle" type="button" aria-label="Открыть меню" aria-expanded="false" aria-controls="mobile-menu" data-menu-toggle>
      <?= icon('menu') ?>
    </button>
  </div>
</header>
<div class="mobile-menu" id="mobile-menu" data-mobile-menu hidden>
  <div class="mobile-menu__panel">
    <button class="icon-button mobile-menu__close" type="button" aria-label="Закрыть меню" data-menu-close><?= icon('close') ?></button>
    <nav aria-label="Мобильная навигация">
      <ul>
        <li><a href="/">Главная</a></li>
        <?php foreach ($categories as $category): ?>
          <li><a href="/catalog/<?= e($category['slug']) ?>/"><?= e($category['name']) ?></a></li>
        <?php endforeach; ?>
        <li><a href="/privacy/">Политика конфиденциальности</a></li>
      </ul>
    </nav>
    <a class="button button--primary" href="tel:<?= e($config['phones'][0]['href']) ?>"><?= icon('phone') ?><?= e($config['phones'][0]['display']) ?></a>
  </div>
</div>
