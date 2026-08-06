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
