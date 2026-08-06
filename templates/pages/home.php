<section class="hero">
  <div class="container hero__grid">
    <div class="hero__content">
      <p class="eyebrow">Производство и поставка по России</p>
      <h1>Техника для точной работы и больших задач</h1>
      <p class="hero__lead">Производим загрузчики сухих кормов, прицепную технику и оборудование. Подбираем исполнение под маршруты, шасси и условия вашего предприятия.</p>
      <div class="hero__actions">
        <a class="button button--primary" href="/catalog/zagruzchiki-suhih-kormov/">Смотреть каталог<?= icon('arrow-right') ?></a>
        <button class="button button--secondary" type="button" data-modal-open>Получить расчёт</button>
      </div>
      <dl class="hero__facts">
        <div><dt>По России</dt><dd>доставка готовой техники</dd></div>
        <div><dt>Под задачу</dt><dd>комплектация и изготовление</dd></div>
      </dl>
    </div>
    <div class="hero__media">
      <img src="/assets/images/hero-industrial-loader.webp" width="1920" height="1100" fetchpriority="high" alt="Красный загрузчик сухих кормов на промышленной площадке">
      <div class="hero__plate"><span>ОКСМА</span><strong>Техника, готовая к работе</strong></div>
    </div>
  </div>
</section>

<section class="benefit-band" aria-label="Преимущества">
  <div class="container benefit-band__grid">
    <?php foreach ([
        ['truck', 'Доставка по России', 'Организуем отправку в ваш регион'],
        ['wrench', 'Собственное производство', 'Контролируем сборку и комплектацию'],
        ['shield', 'Гарантия качества', 'Сопровождаем технику после поставки'],
        ['phone', 'Инженерная консультация', 'Помогаем выбрать рабочее решение'],
    ] as [$iconName, $title, $text]): ?>
      <article class="benefit-item"><?= icon($iconName) ?><div><h2><?= e($title) ?></h2><p><?= e($text) ?></p></div></article>
    <?php endforeach; ?>
  </div>
</section>

<section class="section catalog-preview">
  <div class="container">
    <div class="section-heading">
      <div><p class="eyebrow">Направления производства</p><h2>Каталог техники</h2></div>
      <p>Основные решения для перевозки, загрузки и обслуживания. Характеристики демонстрационные, финальные данные добавим при наполнении каталога.</p>
    </div>
    <div class="category-grid">
      <?php foreach (array_values($categories) as $index => $category): ?>
        <article class="category-card<?= $index === 0 ? ' category-card--featured' : '' ?>">
          <img src="<?= e($category['image']) ?>" width="900" height="675" loading="lazy" alt="<?= e($category['name']) ?>">
          <div class="category-card__content">
            <p class="category-card__number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></p>
            <h3><?= e($category['name']) ?></h3>
            <p><?= e($category['summary']) ?></p>
            <a class="text-link" href="/catalog/<?= e($category['slug']) ?>/"><?= icon('arrow-right') ?>Открыть раздел</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section trust-section">
  <div class="container">
    <div class="section-heading section-heading--compact"><div><p class="eyebrow">Будущие партнёры</p><h2>Нам доверяют</h2></div><p>Здесь появятся логотипы ваших клиентов после согласования материалов.</p></div>
    <div class="trust-grid" aria-label="Места для логотипов клиентов">
      <?php for ($index = 1; $index <= 5; $index++): ?>
        <div class="trust-mark"><span>Логотип</span><strong>Партнёр <?= str_pad((string) $index, 2, '0', STR_PAD_LEFT) ?></strong></div>
      <?php endfor; ?>
    </div>
  </div>
</section>

<section class="request-section" id="request">
  <div class="container request-section__grid">
    <div class="request-section__copy">
      <p class="eyebrow">Если готовой модели недостаточно</p>
      <h2>Почему выбирают ОКСМА</h2>
      <p>Разберём задачу, предложим несколько вариантов и подготовим технику к вашим условиям эксплуатации.</p>
      <ul class="check-list">
        <li><?= icon('check') ?>Работаем с техническим заданием</li>
        <li><?= icon('check') ?>Согласовываем комплектацию до производства</li>
        <li><?= icon('check') ?>Организуем доставку в регионы России</li>
        <li><?= icon('check') ?>Остаёмся на связи после поставки</li>
      </ul>
    </div>
    <div class="request-section__form">
      <p class="eyebrow">Получить предложение</p>
      <h2>Расскажите, какая техника нужна</h2>
      <?= render_partial('lead-form', ['formId' => 'home-lead']) ?>
    </div>
  </div>
</section>
