<?php

declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function render_partial(string $name, array $data = []): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require dirname(__DIR__) . "/templates/partials/{$name}.php";

    return (string) ob_get_clean();
}

function render_page(string $view, array $data): string
{
    $viewFile = dirname(__DIR__) . "/templates/pages/{$view}.php";
    if (!is_file($viewFile)) {
        throw new RuntimeException("Unknown page template: {$view}");
    }

    $data['config'] = app_config();
    extract($data, EXTR_SKIP);
    ob_start();
    require $viewFile;
    $content = (string) ob_get_clean();

    ob_start();
    require dirname(__DIR__) . '/templates/layout.php';

    return (string) ob_get_clean();
}

function icon(string $name): string
{
    $paths = [
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
        'mail' => '<path d="M4 6h16v12H4zM4 7l8 6 8-6"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'phone' => '<path d="M7 3h3l2 5-2 2c1.2 2.4 2.8 4 5.2 5.2l2-2 4.8 2V18c0 1.7-1.3 3-3 3C10.2 21 3 13.8 3 5c0-1.1.9-2 2-2h2z"/>',
        'truck' => '<path d="M3 6h11v10H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>',
        'wrench' => '<path d="M14 6a4 4 0 0 0-5 5L3 17l4 4 6-6a4 4 0 0 0 5-5l-3 3-4-4z"/>',
    ];
    $path = $paths[$name] ?? $paths['arrow-right'];

    return '<svg class="icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}
