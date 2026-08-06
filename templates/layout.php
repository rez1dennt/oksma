<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($seo['title']) ?></title>
  <meta name="description" content="<?= e($seo['description']) ?>">
  <meta name="robots" content="<?= e($seo['robots']) ?>">
  <link rel="canonical" href="<?= e($seo['canonical']) ?>">
  <meta property="og:locale" content="ru_RU">
  <meta property="og:type" content="<?= e($seo['og_type']) ?>">
  <meta property="og:title" content="<?= e($seo['title']) ?>">
  <meta property="og:description" content="<?= e($seo['description']) ?>">
  <meta property="og:url" content="<?= e($seo['canonical']) ?>">
  <meta property="og:image" content="<?= e($seo['image']) ?>">
  <meta name="theme-color" content="#25302d">
  <link rel="stylesheet" href="/assets/css/theme.css">
  <link rel="stylesheet" href="/assets/css/main.css">
<?php foreach ($schemas ?? [] as $schema): ?>
  <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endforeach; ?>
</head>
<body class="<?= e($pageClass ?? '') ?>">
  <a class="skip-link" href="#main">Перейти к содержанию</a>
  <?= render_partial('header', ['config' => $config]) ?>
  <main id="main" tabindex="-1"><?= $content ?></main>
  <?= render_partial('footer', ['config' => $config]) ?>
  <?= render_partial('modal', ['config' => $config]) ?>
  <?= render_partial('cookie-notice', ['config' => $config]) ?>
  <script type="module" src="/assets/js/site.js"></script>
</body>
</html>
