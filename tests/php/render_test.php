<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
if (!function_exists('app_config')) {
    function app_config(): array
    {
        static $config;
        return $config ??= require dirname(__DIR__, 2) . '/config/app.php';
    }
}
require_once $root . '/src/catalog.php';
require_once $root . '/src/router.php';
require_once $root . '/src/seo.php';

$renderSource = dirname(__DIR__, 2) . '/src/render.php';
if (is_file($renderSource)) {
    require_once $renderSource;
}

test('layout renders the shared accessible shell', function (): void {
    truthy(function_exists('render_page'));
    if (!function_exists('render_page')) {
        return;
    }

    $html = render_page('test-fixture', [
        'seo' => [
            'title' => 'Тест',
            'description' => 'Описание тестовой страницы',
            'canonical' => 'https://example.ru/test/',
            'robots' => 'index,follow',
            'og_type' => 'website',
            'image' => 'https://example.ru/assets/images/test.webp',
        ],
        'heading' => 'Страница',
        'schemas' => [organization_schema()],
    ]);

    same(1, substr_count($html, '<h1'));
    truthy(str_contains($html, 'rel="canonical"'));
    truthy(str_contains($html, 'class="skip-link"'));
    truthy(str_contains($html, 'id="main"'));
    truthy(str_contains($html, 'aria-expanded="false"'));
    truthy(str_contains($html, 'data-modal'));
    truthy(str_contains($html, 'data-cookie-notice'));
    truthy(str_contains($html, 'logo-oksma-dark.webp'));
    truthy(str_contains($html, '+7 937 435-17-00'));
});

test('renderer escapes partial data by default helper', function (): void {
    truthy(function_exists('e'));
    if (!function_exists('e')) {
        return;
    }

    same('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', e('<script>alert("x")</script>'));
    truthy(str_contains(icon('phone'), '<svg'));
    truthy(str_contains(icon('phone'), 'aria-hidden="true"'));
});

test('token files are valid DTCG JSON and theme exposes semantic variables', function (): void {
    $root = dirname(__DIR__, 2);
    foreach (glob($root . '/tokens/*.json') as $file) {
        truthy(is_array(json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR)));
    }

    $theme = is_file($root . '/assets/css/theme.css')
        ? (string) file_get_contents($root . '/assets/css/theme.css')
        : '';
    truthy(str_contains($theme, '--color-action-primary'));
    truthy(str_contains($theme, '--space-section'));
    truthy(str_contains($theme, '--focus-ring'));
});
