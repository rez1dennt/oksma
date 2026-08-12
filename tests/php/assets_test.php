<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/bootstrap.php';

test('all catalog raster assets exist and are valid webp images', function () use ($root): void {
    $paths = [
        '/assets/images/hero-industrial-loader.webp',
        '/assets/images/logo-oksma-dark.webp',
        '/assets/images/logo-oksma-light.webp',
    ];
    foreach (catalog_categories() as $category) {
        $paths[] = $category['image'];
    }
    foreach (catalog_products() as $product) {
        array_push($paths, ...$product['images']);
    }

    foreach (array_unique($paths) as $path) {
        $file = $root . str_replace('/', DIRECTORY_SEPARATOR, $path);
        truthy(is_file($file));
        truthy(filesize($file) > 1000);
        $image = getimagesize($file);
        truthy(is_array($image));
        same('image/webp', $image['mime']);
        truthy($image[0] >= 300 && $image[1] >= 100);
    }
});

test('approved brand assets exist and are valid webp images', function () use ($root): void {
    $paths = [
        '/assets/images/logo-oksma-header-gold.webp',
        '/assets/images/logo-oksma-footer-gold.webp',
        '/assets/images/partner-stp-2008.webp',
    ];

    foreach ($paths as $path) {
        $file = $root . str_replace('/', DIRECTORY_SEPARATOR, $path);
        truthy(is_file($file));
        truthy(filesize($file) > 1000);
        $image = getimagesize($file);
        truthy(is_array($image));
        same('image/webp', $image['mime']);
        truthy($image[0] >= 120 && $image[1] >= 40);
    }
});

test('catalog category photography keeps distinct full-size source frames', function () use ($root): void {
    $hashes = [];

    foreach (catalog_categories() as $category) {
        $file = $root . str_replace('/', DIRECTORY_SEPARATOR, $category['image']);
        $image = getimagesize($file);
        truthy(is_array($image));
        truthy($image[0] >= 800 && $image[1] >= 600);

        $hash = hash_file('sha256', $file);
        truthy(!isset($hashes[$hash]));
        $hashes[$hash] = true;
    }
});

test('requested replacement product photography uses distinct source frames', function () use ($root): void {
    $paths = [
        '/assets/images/products/lowbed/lowbed-trailer-1.webp',
        '/assets/images/products/pgts/pgts-12-1.webp',
        '/assets/images/products/pgts/pgts-3-1.webp',
        '/assets/images/products/pgts/pgts-6-5-1.webp',
        '/assets/images/products/ppts/ppts-12-1.webp',
        '/assets/images/products/zsk/zsk-10-1.webp',
        '/assets/images/products/zsk/zsk-7-1.webp',
        '/assets/images/products/pc/pc-2-1.webp',
        '/assets/images/product-zsk-12-1.webp',
        '/assets/images/product-zsk-20-1.webp',
        '/assets/images/product-zsk-21-1.webp',
    ];
    $hashes = [];

    foreach ($paths as $path) {
        $file = $root . str_replace('/', DIRECTORY_SEPARATOR, $path);
        $image = getimagesize($file);
        truthy(is_array($image));
        truthy($image[0] >= 800 && $image[1] >= 450);
        $hash = hash_file('sha256', $file);
        truthy(!isset($hashes[$hash]));
        $hashes[$hash] = true;
    }
});

test('approved pgts photo correction stays mapped to the requested models', function () use ($root): void {
    $pgts3 = $root . '/assets/images/products/pgts/pgts-3-1.webp';
    $pgts65 = $root . '/assets/images/products/pgts/pgts-6-5-1.webp';

    same('c82ac3aa512609247c9f8fcbe1d27c86436e592aa1387c1f5ed00f206981e5eb', hash_file('sha256', $pgts3));
    same('54fe9fe90d9f9dd409768686c280380094d7cbaba53411f45121a6795e4e047d', hash_file('sha256', $pgts65));

    $selections = json_decode(
        (string) file_get_contents($root . '/tools/catalog_import/image_selections.json'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
    same(
        'source-assets/client-corrections/2026-08-12/pgts-photo-map/image2.jpeg',
        $selections['ПГТС-3']['source'] ?? null
    );
    same(
        'source-assets/client-corrections/2026-08-12/pgts-photo-map/pgts-6-5-approved.webp',
        $selections['ПГТС-6.5']['source'] ?? null
    );
    truthy(($selections['ПГТС-6.5']['copy_source'] ?? false) === true);
});

test('approved duk m uaz profi photo uses the selected clean catalog frame', function () use ($root): void {
    $file = $root . '/assets/images/products/duk/duk-m-uaz-profi-1.webp';

    truthy(is_file($file));
    $image = getimagesize($file);
    truthy(is_array($image));
    same([1200, 900], [$image[0], $image[1]]);
    same('image/webp', $image['mime']);
    same('34fad06e55660a862d743127ea65b2e8413e1de34a4743c2025fd57bea99d630', hash_file('sha256', $file));
    truthy(is_file($root . '/source-assets/client-corrections/2026-08-12/duk-m-uaz-profi/IMG_0929.HEIC'));
});

test('removed ppts-20 has no public product image', function () use ($root): void {
    truthy(!is_file($root . '/assets/images/products/ppts/ppts-20-1.webp'));
});

test('catalog cards and product galleries show complete source frames', function () use ($root): void {
    $css = (string) file_get_contents($root . '/assets/css/main.css');

    truthy((bool) preg_match('/\.product-card__media img\s*\{[^}]*object-fit:\s*contain/s', $css));
    truthy((bool) preg_match('/\.gallery__stage img\s*\{[^}]*object-fit:\s*contain/s', $css));
    truthy((bool) preg_match('/\.gallery__thumbs img\s*\{[^}]*object-fit:\s*contain/s', $css));
    truthy(!str_contains($css, '.product-card:hover .product-card__media img { transform: scale'));
});

test('all primary product images use the approved white catalog canvas', function () use ($root): void {
    foreach (catalog_products() as $product) {
        $path = $product['images'][0] ?? '';
        truthy($path !== '');
        $file = $root . str_replace('/', DIRECTORY_SEPARATOR, $path);
        truthy(is_file($file));
        $size = getimagesize($file);
        truthy(is_array($size));
        same([1200, 900], [$size[0], $size[1]]);
        same('image/webp', $size['mime']);
    }
});

test('site exposes an explicit favicon', function () use ($root): void {
    truthy(is_file($root . '/favicon.ico'));
    $layout = (string) file_get_contents($root . '/templates/layout.php');
    truthy(str_contains($layout, 'rel="icon"'));
    truthy(str_contains($layout, '/favicon.ico'));
});

test('apache declares the PDF content type', function () use ($root): void {
    $rules = (string) file_get_contents($root . '/.htaccess');
    truthy(str_contains($rules, 'AddType application/pdf .pdf'));
});
