<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/bootstrap.php';

test('catalog can select products by category', function (): void {
    truthy(function_exists('products_for_category'));
    if (!function_exists('products_for_category')) {
        return;
    }

    truthy(count(products_for_category('zagruzchiki-suhih-kormov')) >= 9);
    same([], products_for_category('unknown'));
});

test('home contains all required first-stage sections', function (): void {
    $view = $GLOBALS['root'] . '/templates/pages/home.php';
    truthy(is_file($view));
    if (!is_file($view)) {
        return;
    }

    $html = render_page('home', [
        'seo' => seo_for_page('home'),
        'categories' => catalog_categories(),
        'schemas' => [organization_schema()],
    ]);

    same(1, substr_count($html, '<h1'));
    foreach (['Техника для', 'Каталог техники', 'Почему выбирают ОКСМА', 'Нам доверяют', 'Получить предложение'] as $label) {
        truthy(str_contains($html, $label));
    }
    truthy(str_contains($html, 'class="hero__media"'));
    truthy(str_contains($html, 'class="hero__content hero__content--glass"'));
    truthy(str_contains($html, 'class="hero__chip"'));
    truthy(strpos($html, 'class="hero__media"') < strpos($html, 'class="container hero__grid"'));
    truthy(str_contains($html, 'class="request-section__panel"'));
    truthy(str_contains($html, 'partner-stp-2008.webp'));
    same(0, substr_count($html, 'Партнёр 0'));
    same(4, substr_count($html, 'class="benefit-item__icon"'));
});

test('category renders every product and both view controls', function (): void {
    $view = $GLOBALS['root'] . '/templates/pages/category.php';
    truthy(is_file($view));
    if (!is_file($view)) {
        return;
    }

    $category = find_category('zagruzchiki-suhih-kormov');
    $products = products_for_category($category['slug']);
    $html = render_page('category', [
        'seo' => seo_for_page('category', $category),
        'category' => $category,
        'products' => $products,
        'schemas' => [],
    ]);

    same(count($products), substr_count($html, 'class="product-card"'));
    truthy(str_contains($html, 'data-view="grid"'));
    truthy(str_contains($html, 'data-view="list"'));
    truthy(str_contains($html, 'data-catalog'));
    truthy(str_contains($html, 'class="page-hero__media"'));
    truthy(str_contains($html, e($category['image'])));
});

test('product renders gallery tabs specifications and related items', function (): void {
    $view = $GLOBALS['root'] . '/templates/pages/product.php';
    truthy(is_file($view));
    if (!is_file($view)) {
        return;
    }

    $product = find_product('zsk-10');
    $html = render_page('product', [
        'seo' => seo_for_page('product', $product),
        'product' => $product,
        'category' => find_category($product['category']),
        'related' => related_products($product),
        'documents' => documents_for_product($product),
        'schemas' => [product_schema($product)],
    ]);

    same(1, substr_count($html, '<h1'));
    truthy(str_contains($html, 'data-gallery'));
    truthy(str_contains($html, 'role="tablist"'));
    truthy(str_contains($html, 'Вместимость бункера'));
    truthy(str_contains($html, 'Похожие модели'));
    same(count($product['benefits']), substr_count($html, 'class="product-benefit__icon"'));
});

test('product documents tab is conditional and uses the shared declaration data', function (): void {
    $pzk = find_product('pzk-10');
    $pzkHtml = render_page('product', [
        'seo' => seo_for_page('product', $pzk),
        'product' => $pzk,
        'category' => find_category($pzk['category']),
        'related' => related_products($pzk),
        'documents' => documents_for_product($pzk),
        'schemas' => [product_schema($pzk)],
    ]);
    truthy(str_contains($pzkHtml, 'id="tab-documents"'));
    truthy(str_contains($pzkHtml, 'ЕАЭС N RU Д-RU.РА04.В.69139/26'));

    $zsk = find_product('zsk-10');
    $zskHtml = render_page('product', [
        'seo' => seo_for_page('product', $zsk),
        'product' => $zsk,
        'category' => find_category($zsk['category']),
        'related' => related_products($zsk),
        'documents' => documents_for_product($zsk),
        'schemas' => [product_schema($zsk)],
    ]);
    truthy(!str_contains($zskHtml, 'id="tab-documents"'));
    truthy(!str_contains($zskHtml, 'ЕАЭС N RU Д-RU.РА04.В.69139/26'));
});

test('privacy publishes approved operator details and contact', function (): void {
    $view = $GLOBALS['root'] . '/templates/pages/privacy.php';
    truthy(is_file($view));
    if (!is_file($view)) {
        return;
    }

    $html = render_page('privacy', ['seo' => seo_for_page('privacy'), 'schemas' => []]);
    truthy(str_contains($html, 'ООО «СпецТехПром»'));
    truthy(str_contains($html, '5258079050'));
    truthy(str_contains($html, 'oksmaprom@yandex.ru'));
    truthy(str_contains($html, 'id="cookies"'));
});

test('documents page renders two accessible declaration cards', function (): void {
    $html = render_page('documents', [
        'seo' => seo_for_page('documents'),
        'documents' => all_documents(),
        'schemas' => [],
    ]);
    same(2, substr_count($html, 'class="document-card"'));
    truthy(str_contains($html, 'ЕАЭС N RU Д-RU.РА04.В.69139/26'));
    truthy(str_contains($html, 'ЕАЭС N RU Д-RU.РА05.В.72423/26'));
    truthy(str_contains($html, 'target="_blank"'));
    truthy(str_contains($html, ' download'));
    truthy(str_contains($html, 'href="/documents/"'));
});

test('404 page offers routes back to useful content', function (): void {
    $view = $GLOBALS['root'] . '/templates/pages/404.php';
    truthy(is_file($view));
    if (!is_file($view)) {
        return;
    }

    $html = render_page('404', ['seo' => seo_for_page('404'), 'schemas' => []]);
    truthy(str_contains($html, 'Страница не найдена'));
    truthy(str_contains($html, 'href="/"'));
    truthy(str_contains($html, '/catalog/zagruzchiki-suhih-kormov/'));
});
