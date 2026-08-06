<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

echo render_page('home', [
    'seo' => seo_for_page('home'),
    'categories' => catalog_categories(),
    'schemas' => [organization_schema()],
    'pageClass' => 'page-home',
]);
