<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$slug = (string) ($_GET['slug'] ?? '');
$product = find_product($slug);

if ($product === null) {
    http_response_code(404);
    echo render_page('404', ['seo' => seo_for_page('404'), 'schemas' => [], 'pageClass' => 'page-404']);
    exit;
}

$category = find_category($product['category']);
$breadcrumbs = [
    ['name' => 'Главная', 'url' => '/'],
    ['name' => $category['name'], 'url' => "/catalog/{$category['slug']}/"],
    ['name' => $product['name'], 'url' => "/product/{$product['slug']}/"],
];

echo render_page('product', [
    'seo' => seo_for_page('product', $product),
    'product' => $product,
    'category' => $category,
    'related' => related_products($product),
    'breadcrumbs' => $breadcrumbs,
    'schemas' => [organization_schema(), breadcrumb_schema($breadcrumbs), product_schema($product)],
    'pageClass' => 'page-product',
]);
