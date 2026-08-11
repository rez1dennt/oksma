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
        '/assets/images/products/ppts/ppts-20-1.webp',
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

test('catalog cards and product galleries show complete source frames', function () use ($root): void {
    $css = (string) file_get_contents($root . '/assets/css/main.css');

    truthy((bool) preg_match('/\.product-card__media img\s*\{[^}]*object-fit:\s*contain/s', $css));
    truthy((bool) preg_match('/\.gallery__stage img\s*\{[^}]*object-fit:\s*contain/s', $css));
    truthy((bool) preg_match('/\.gallery__thumbs img\s*\{[^}]*object-fit:\s*contain/s', $css));
    truthy(!str_contains($css, '.product-card:hover .product-card__media img { transform: scale'));
});

test('client supplied pzk 15 frame and corrected ppts photos are preserved', function () use ($root): void {
    $pzk15 = $root . '/assets/images/products/pzk/pzk-15-1.webp';
    truthy(is_file($pzk15));
    $size = getimagesize($pzk15);
    truthy(is_array($size));
    same([717, 534], [$size[0], $size[1]]);
    same('image/webp', $size['mime']);

    same(
        'd31d0ee29b515cdbbab7f76259f4f1b7ce584a6b7280d4262a7678c9d831e083',
        hash_file('sha256', $root . '/assets/images/products/ppts/ppts-12-1.webp')
    );
    same(
        'b5483dda120e24e5181f5a4d0c0be694d6b52bde493e4117c0b8c8b62c4647d9',
        hash_file('sha256', $root . '/assets/images/products/ppts/ppts-20-1.webp')
    );
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
