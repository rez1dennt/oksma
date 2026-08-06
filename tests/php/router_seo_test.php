<?php

declare(strict_types=1);

if (!function_exists('app_config')) {
    function app_config(): array
    {
        static $config;
        return $config ??= require dirname(__DIR__, 2) . '/config/app.php';
    }
}

require_once dirname(__DIR__, 2) . '/src/catalog.php';

foreach (['router', 'seo'] as $module) {
    $source = dirname(__DIR__, 2) . "/src/{$module}.php";
    if (is_file($source)) {
        require_once $source;
    }
}

test('router resolves clean public URLs', function (): void {
    truthy(function_exists('resolve_route'));
    if (!function_exists('resolve_route')) {
        return;
    }

    same(['name' => 'home'], resolve_route('/'));
    same(['name' => 'category', 'slug' => 'zagruzchiki-suhih-kormov'], resolve_route('/catalog/zagruzchiki-suhih-kormov/'));
    same(['name' => 'product', 'slug' => 'zsk-10'], resolve_route('/product/zsk-10/'));
    same(['name' => 'privacy'], resolve_route('/privacy/'));
    same(['name' => 'documents'], resolve_route('/documents/'));
    same(['name' => 'not-found'], resolve_route('/product/unknown/'));
});

test('product SEO contains canonical and Product schema without price', function (): void {
    truthy(function_exists('seo_for_page'));
    truthy(function_exists('product_schema'));
    if (!function_exists('seo_for_page') || !function_exists('product_schema')) {
        return;
    }

    $product = find_product('zsk-10');
    $seo = seo_for_page('product', $product);
    same('https://example.ru/product/zsk-10/', $seo['canonical']);
    same('index,follow', $seo['robots']);
    same('product', $seo['og_type']);
    same('Product', product_schema($product)['@type']);
    truthy(!array_key_exists('offers', product_schema($product)));
});

test('breadcrumb schema numbers all items in order', function (): void {
    truthy(function_exists('breadcrumb_schema'));
    if (!function_exists('breadcrumb_schema')) {
        return;
    }

    $schema = breadcrumb_schema([
        ['name' => 'Главная', 'url' => '/'],
        ['name' => 'Каталог', 'url' => '/catalog/zagruzchiki-suhih-kormov/'],
    ]);
    same('BreadcrumbList', $schema['@type']);
    same(1, $schema['itemListElement'][0]['position']);
    same(2, $schema['itemListElement'][1]['position']);
});

test('sitemap contains only valid indexable routes', function (): void {
    truthy(function_exists('sitemap_urls'));
    if (!function_exists('sitemap_urls')) {
        return;
    }

    $urls = sitemap_urls();
    truthy(in_array('https://example.ru/product/zsk-10/', $urls, true));
    truthy(in_array('https://example.ru/privacy/', $urls, true));
    truthy(!in_array('https://example.ru/catalog/zapchasti/', $urls, true));
    truthy(!in_array('https://example.ru/404/', $urls, true));
    same(count($urls), count(array_unique($urls)));
});

test('removed spare-parts category route is not found', function (): void {
    same('not-found', resolve_route('/catalog/zapchasti/')['name']);
});

test('documents page is indexable canonical and present in sitemap', function (): void {
    $seo = seo_for_page('documents');
    same('https://example.ru/documents/', $seo['canonical']);
    same('index,follow', $seo['robots']);
    truthy(str_contains($seo['title'], 'Документы'));
    truthy(in_array('https://example.ru/documents/', sitemap_urls(), true));
});

test('not-found pages are explicitly non-indexable', function (): void {
    truthy(function_exists('seo_for_page'));
    if (!function_exists('seo_for_page')) {
        return;
    }

    same('noindex,follow', seo_for_page('404')['robots']);
});
