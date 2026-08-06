<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

$staticDemoSource = dirname(__DIR__, 2) . '/src/static-demo.php';
if (is_file($staticDemoSource)) {
    require_once $staticDemoSource;
}

test('static demo enumerates every public route', function (): void {
    truthy(function_exists('static_demo_routes'));
    if (!function_exists('static_demo_routes')) {
        return;
    }

    $routes = static_demo_routes();
    same(36, count($routes));
    same('index.html', $routes['/']);
    same('catalog/zagruzchiki-suhih-kormov/index.html', $routes['/catalog/zagruzchiki-suhih-kormov/']);
    same('product/pc-11v/index.html', $routes['/product/pc-11v/']);
    same('404.html', $routes['/404.html']);
    same('sitemap.xml', $routes['/sitemap.xml']);
    same('robots.txt', $routes['/robots.txt']);
    truthy(!isset($routes['/catalog/zapchasti/']));
});

test('static demo transforms forms without changing source templates', function (): void {
    truthy(function_exists('static_demo_transform_html'));
    if (!function_exists('static_demo_transform_html')) {
        return;
    }

    $html = '<form action="/submit.php" data-lead-form></form></body>';
    $result = static_demo_transform_html($html);
    truthy(str_contains($result, 'action="#demo-form"'));
    truthy(str_contains($result, '/assets/js/demo-mode.js'));
    truthy(!str_contains($result, 'action="/submit.php"'));
    same($result, static_demo_transform_html($result));
});

test('static demo validator reports a missing output directory', function (): void {
    truthy(function_exists('static_demo_validate_output'));
    if (!function_exists('static_demo_validate_output')) {
        return;
    }

    $missing = dirname(__DIR__, 2) . '/.tmp/missing-vercel-demo';
    $errors = static_demo_validate_output($missing, static_demo_routes());
    same(1, count($errors));
    truthy(str_contains($errors[0], 'does not exist'));
});
