<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

test('imported product photography is web ready and unique per family', function () use ($root): void {
    $directory = $root . '/assets/images/products';
    $files = is_dir($directory)
        ? glob($directory . '/*/*.webp') ?: []
        : [];
    truthy(count($files) > 0);

    $hashesByFamily = [];
    foreach ($files as $file) {
        $image = getimagesize($file);
        truthy(is_array($image));
        same('image/webp', $image['mime']);
        truthy($image[0] >= 640 && $image[1] >= 400);
        $family = basename(dirname($file));
        $hash = hash_file('sha256', $file);
        truthy(!isset($hashesByFamily[$family][$hash]));
        $hashesByFamily[$family][$hash] = true;
    }
});
