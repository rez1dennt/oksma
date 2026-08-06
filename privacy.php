<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$breadcrumbs = [
    ['name' => 'Главная', 'url' => '/'],
    ['name' => 'Политика конфиденциальности', 'url' => '/privacy/'],
];

echo render_page('privacy', [
    'seo' => seo_for_page('privacy'),
    'breadcrumbs' => $breadcrumbs,
    'schemas' => [organization_schema(), breadcrumb_schema($breadcrumbs)],
    'pageClass' => 'page-privacy',
]);
