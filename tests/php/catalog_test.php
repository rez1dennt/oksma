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

test('empty spare-parts category is not public', function (): void {
    same(4, count(catalog_categories()));
    same(null, find_category('zapchasti'));
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
        'ppts-9', 'ppts-12', 'ppts-15', 'ppts-18', 'ppts-20p',
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

test('client corrections map feed equipment to approved clean photography', function (): void {
    same('/assets/images/category-feed-clean.webp', find_category('zagruzchiki-suhih-kormov')['image']);
    same('/assets/images/product-pzk-10-1.webp', find_product('pzk-10')['images'][0]);
    same('/assets/images/products/pzk/pzk-15-1.webp', find_product('pzk-15')['images'][0]);
    same('/assets/images/product-zsk-10-1.webp', find_product('zsk-10')['images'][0]);
    same('/assets/images/product-zsk-15-1.webp', find_product('zsk-15')['images'][0]);
});

test('ppts model records retain unique names urls and specifications', function (): void {
    $ppts12 = find_product('ppts-12');
    $ppts20p = find_product('ppts-20p');

    same('ППТС-12', $ppts12['name']);
    same('12 000 кг', $ppts12['specs']['Грузоподъёмность']);
    same('ППТС-20П', $ppts20p['name']);
    same('Не более 20 000 кг', $ppts20p['specs']['Грузоподъёмность']);
});

test('ppts-20 is completely removed from the public catalog and relationships', function (): void {
    same(null, find_product('ppts-20'));

    foreach (catalog_products() as $product) {
        truthy(!in_array('ppts-20', $product['related'] ?? [], true), "{$product['slug']} still links to removed ppts-20");
    }
});

test('duk commercial proposals publish the complete installation and tank', function (): void {
    $duk = find_product('duk-2');
    $tank = find_product('duk-tank-1900');

    truthy($duk !== null);
    same('dezinfekcionnye-ustanovki', $duk['category']);
    same('1 600 л (рабочая — 1 500 л)', $duk['specs']['Цистерна']);
    same('4 × 96 л', $duk['specs']['Баки для дезрастворов']);
    same('20 м либо 2 × 10 м', $duk['specs']['Раздаточный рукав']);
    truthy(str_contains($duk['description'], 'ГАЗ-3308'));
    same(3, count($duk['benefits']));

    truthy($tank !== null);
    same('dezinfekcionnye-ustanovki', $tank['category']);
    same('1 900 л', $tank['specs']['Объём цистерны']);
    same('6 мм', $tank['specs']['Толщина обечайки']);
    truthy(str_contains($tank['description'], 'УАЗ'));
    same(3, count($tank['benefits']));

    same(2, count(products_for_category('dezinfekcionnye-ustanovki')));
});
