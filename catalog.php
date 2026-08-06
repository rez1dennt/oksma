<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$slug = (string) ($_GET['slug'] ?? '');
$category = find_category($slug);

if ($category === null) {
    http_response_code(404);
    echo render_page('404', ['seo' => seo_for_page('404'), 'schemas' => [], 'pageClass' => 'page-404']);
    exit;
}

$breadcrumbs = [
    ['name' => 'Главная', 'url' => '/'],
    ['name' => $category['name'], 'url' => "/catalog/{$category['slug']}/"],
];

echo render_page('category', [
    'seo' => seo_for_page('category', $category),
    'category' => $category,
    'products' => products_for_category($slug),
    'breadcrumbs' => $breadcrumbs,
    'schemas' => [organization_schema(), breadcrumb_schema($breadcrumbs)],
    'pageClass' => 'page-category',
]);
