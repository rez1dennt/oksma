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
    same(38, count($routes));
    same('index.html', $routes['/']);
    same('catalog/zagruzchiki-suhih-kormov/index.html', $routes['/catalog/zagruzchiki-suhih-kormov/']);
    same('product/pc-11v/index.html', $routes['/product/pc-11v/']);
    same('product/duk-2/index.html', $routes['/product/duk-2/']);
    same('product/duk-tank-1900/index.html', $routes['/product/duk-tank-1900/']);
    same('404.html', $routes['/404.html']);
    same('sitemap.xml', $routes['/sitemap.xml']);
    same('robots.txt', $routes['/robots.txt']);
    truthy(!isset($routes['/catalog/zapchasti/']));
    truthy(!isset($routes['/product/ppts-20/']));
});

test('static demo transforms forms without changing source templates', function (): void {
    truthy(function_exists('static_demo_transform_html'));
    if (!function_exists('static_demo_transform_html')) {
        return;
    }

    $html = '<form action="/submit.php" data-lead-form>' . PHP_EOL
        . '  <input type="hidden" name="csrf_token" value="random-token">' . PHP_EOL
        . '  <input type="hidden" name="started_at" value="1786043070">' . PHP_EOL
        . '</form></body>';
    $result = static_demo_transform_html($html);
    truthy(str_contains($result, 'action="#demo-form"'));
    truthy(str_contains($result, '/assets/js/demo-mode.js'));
    truthy(!str_contains($result, 'action="/submit.php"'));
    truthy(!str_contains($result, 'name="csrf_token"'));
    truthy(!str_contains($result, 'name="started_at"'));
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

test('static demo exporter builds an isolated deployable tree', function (): void {
    truthy(function_exists('static_demo_export'));
    truthy(function_exists('static_demo_remove_tree'));
    if (!function_exists('static_demo_export') || !function_exists('static_demo_remove_tree')) {
        return;
    }

    $root = dirname(__DIR__, 2);
    $output = $root . '/.tmp/test-vercel-demo';
    static_demo_remove_tree($root, $output);

    try {
        $report = static_demo_export($root, $output);
        same(38, $report['pages']);
        same([], $report['errors']);
        truthy($report['assets'] > 0);
        truthy(is_file($output . '/index.html'));
        truthy(is_file($output . '/product/pc-11v/index.html'));
        truthy(is_file($output . '/product/duk-2/index.html'));
        truthy(is_file($output . '/product/duk-tank-1900/index.html'));
        truthy(!is_file($output . '/product/ppts-20/index.html'));
        truthy(is_file($output . '/assets/css/main.css'));
        truthy(is_file($output . '/assets/js/demo-mode.js'));
        truthy(is_file($output . '/vercel.json'));
        truthy(!is_file($output . '/index.php'));
    } finally {
        static_demo_remove_tree($root, $output);
    }
});

test('static demo removal rejects unsafe targets', function (): void {
    truthy(function_exists('static_demo_remove_tree'));
    if (!function_exists('static_demo_remove_tree')) {
        return;
    }

    $root = dirname(__DIR__, 2);
    foreach ([$root, dirname($root) . '/vercel-demo', $root . '/.tmp/not-a-demo'] as $target) {
        $rejected = false;
        try {
            static_demo_remove_tree($root, $target);
        } catch (RuntimeException) {
            $rejected = true;
        }
        truthy($rejected);
    }
});

test('tracked Vercel demo matches the public route contract', function (): void {
    $output = dirname(__DIR__, 2) . '/vercel-demo';
    same([], static_demo_validate_output($output, static_demo_routes()));
    truthy(!is_file($output . '/product/ppts-20/index.html'));
    truthy(!is_file($output . '/assets/images/products/ppts/ppts-20-1.webp'));
});
