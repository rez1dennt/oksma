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
    truthy(str_contains($html, 'logo-oksma-header-gold.webp'));
    truthy(str_contains($html, 'logo-oksma-footer-gold.webp'));
    truthy(str_contains($html, '+7 937 435-17-00'));
    truthy(str_contains($html, '<meta name="theme-color" content="#25302d">'));
    $headerClose = strpos($html, '</header>');
    $mobileDrawer = strpos($html, 'class="mobile-menu"');
    truthy($headerClose !== false && $mobileDrawer !== false && $headerClose < $mobileDrawer);
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

test('renderer exposes the complete consistent svg icon set', function (): void {
    foreach (['arrow-right', 'check', 'close', 'mail', 'menu', 'phone', 'grid', 'list', 'printer', 'shield', 'truck', 'wrench'] as $name) {
        $svg = icon($name);
        truthy(str_contains($svg, 'viewBox="0 0 24 24"'));
        truthy(str_contains($svg, 'stroke="currentColor"'));
        truthy(str_contains($svg, 'stroke-width="2"'));
        truthy(str_contains($svg, 'aria-hidden="true"'));
    }
    truthy(str_contains(icon('shield'), 'm9 12 2 2 4-4'));
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

test('footer uses the shared warm gold token contract', function (): void {
    $theme = (string) file_get_contents(dirname(__DIR__, 2) . '/assets/css/theme.css');
    $css = (string) file_get_contents(dirname(__DIR__, 2) . '/assets/css/main.css');
    foreach (['--color-footer-bg', '--color-footer-text', '--color-footer-muted', '--color-footer-accent'] as $token) {
        truthy(str_contains($theme, $token));
    }
    truthy((bool) preg_match('/\.site-footer\s*\{[^}]*background:\s*var\(--color-footer-bg\)/s', $css));
});

test('long responsive headings can wrap without clipping', function (): void {
    $css = (string) file_get_contents(dirname(__DIR__, 2) . '/assets/css/main.css');

    truthy((bool) preg_match('/\.page-hero h1, \.legal-hero h1\s*\{[^}]*overflow-wrap:\s*anywhere;/s', $css));
});

test('document cards define responsive and print-safe presentation', function (): void {
    $css = (string) file_get_contents(dirname(__DIR__, 2) . '/assets/css/main.css');
    foreach (['.documents-hero', '.document-grid', '.document-card', '.document-card__status', '.document-card__actions'] as $selector) {
        truthy(str_contains($css, $selector));
    }
    truthy((bool) preg_match('/@media \(min-width: 48em\).*?\.document-grid\s*\{[^}]*grid-template-columns:/s', $css));
    truthy((bool) preg_match('/@media print.*?\.document-card__actions/s', $css));
});
