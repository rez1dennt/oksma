<nav class="breadcrumbs" aria-label="Хлебные крошки">
  <ol>
    <?php foreach ($items as $index => $item): ?>
      <li>
        <?php if ($index === array_key_last($items)): ?>
          <span aria-current="page"><?= e($item['name']) ?></span>
        <?php else: ?>
          <a href="<?= e($item['url']) ?>"><?= e($item['name']) ?></a><span aria-hidden="true">/</span>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
</nav>
