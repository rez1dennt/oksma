<footer class="site-footer">
  <div class="container site-footer__grid">
    <div class="site-footer__brand">
      <a class="brand brand--light" href="/" aria-label="ОКСМА, на главную">
        <img src="/assets/images/logo-oksma-light.webp" width="164" height="46" alt="ОКСМА">
      </a>
      <p>Производим и поставляем технику для хозяйств и предприятий. Подбираем комплектацию под рабочие условия.</p>
    </div>
    <nav aria-label="Навигация в подвале">
      <h2 class="site-footer__title">Разделы</h2>
      <ul>
        <li><a href="/">Главная</a></li>
        <li><a href="/catalog/zagruzchiki-suhih-kormov/">Каталог техники</a></li>
        <li><a href="/privacy/">Политика конфиденциальности</a></li>
      </ul>
    </nav>
    <div class="site-footer__contacts">
      <h2 class="site-footer__title">Связаться с нами</h2>
      <?php foreach ($config['phones'] as $phone): ?>
        <a href="tel:<?= e($phone['href']) ?>"><?= icon('phone') ?><?= e($phone['display']) ?></a>
      <?php endforeach; ?>
      <a href="mailto:<?= e($config['email']) ?>"><?= icon('mail') ?><?= e($config['email']) ?></a>
    </div>
  </div>
  <div class="container site-footer__legal">
    <span>© <?= date('Y') ?> <?= e($config['name']) ?></span>
    <span><?= e($config['legal_name']) ?>, ИНН <?= e($config['requisites']['inn']) ?></span>
  </div>
</footer>
