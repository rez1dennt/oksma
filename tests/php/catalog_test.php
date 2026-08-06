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

test('commercial proposal archive publishes all verified models', function (): void {
    $expected = [
        'lowbed-trailer',
        'pc-2', 'pc-5v', 'pc-6', 'pc-11v', 'pc-12v', 'pc-20',
        'pgts-3', 'pgts-6-5', 'pgts-9', 'pgts-12',
        'ppts-9', 'ppts-12', 'ppts-15', 'ppts-18', 'ppts-20', 'ppts-20p',
        'pzk-10',
        'zsk-7', 'zsk-10', 'zsk-15', 'zsk-15u',
    ];

    foreach ($expected as $slug) {
        $product = find_product($slug);
        truthy($product !== null, "Missing imported product {$slug}");
        truthy(($product['specs'] ?? []) !== [], "Missing specifications for {$slug}");
        truthy(($product['images'] ?? []) !== [], "Missing image for {$slug}");
    }
});

test('every product exposes exactly three benefits', function (): void {
    foreach (catalog_products() as $product) {
        same(3, count($product['benefits'] ?? []));
        same(3, count(array_unique($product['benefits'] ?? [])));
    }
});

test('benefit cards map copy to the shared icon system', function (): void {
    $cards = product_benefit_cards([
        'Самозагрузка и перемешивание',
        'Комплектация под задачу',
        'Доставка по России',
    ]);

    same(['01', '02', '03'], array_column($cards, 'index'));
    same(['truck', 'wrench', 'truck'], array_column($cards, 'icon'));
    same('Самозагрузка и перемешивание', $cards[0]['title']);
    truthy(str_contains($cards[1]['description'], 'условия работы'));
    truthy(str_contains($cards[2]['description'], 'регион России'));
});
