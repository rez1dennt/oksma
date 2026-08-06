<?php

declare(strict_types=1);

$catalogSource = dirname(__DIR__, 2) . '/src/catalog.php';
if (is_file($catalogSource)) {
    require_once $catalogSource;
}

test('catalog module exposes the required API', function (): void {
    truthy(function_exists('catalog_integrity_errors'));
    truthy(function_exists('find_category'));
    truthy(function_exists('find_product'));
    truthy(function_exists('related_products'));
});

test('catalog has valid unique relationships', function (): void {
    truthy(function_exists('catalog_integrity_errors'));
    if (!function_exists('catalog_integrity_errors')) {
        return;
    }

    same([], catalog_integrity_errors());
    truthy(find_category('zagruzchiki-suhih-kormov') !== null);
    same('ЗСК-10', find_product('zsk-10')['name']);
});

test('related products ignore missing and current entries', function (): void {
    truthy(function_exists('find_product'));
    if (!function_exists('find_product')) {
        return;
    }

    $product = find_product('zsk-10');
    $related = related_products($product);
    truthy(count($related) > 0);
    truthy(!in_array('zsk-10', array_column($related, 'slug'), true));
});
