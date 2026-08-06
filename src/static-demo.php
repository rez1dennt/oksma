<?php

declare(strict_types=1);

function static_demo_routes(): array
{
    $routes = [
        '/' => 'index.html',
        '/documents/' => 'documents/index.html',
        '/privacy/' => 'privacy/index.html',
        '/sitemap.xml' => 'sitemap.xml',
        '/robots.txt' => 'robots.txt',
        '/404.html' => '404.html',
    ];

    foreach (catalog_categories() as $category) {
        $slug = (string) $category['slug'];
        $routes["/catalog/{$slug}/"] = "catalog/{$slug}/index.html";
    }

    foreach (catalog_products() as $product) {
        $slug = (string) $product['slug'];
        $routes["/product/{$slug}/"] = "product/{$slug}/index.html";
    }

    return $routes;
}

function static_demo_transform_html(string $html): string
{
    $html = str_replace('action="/submit.php"', 'action="#demo-form"', $html);

    if (!str_contains($html, '/assets/js/demo-mode.js')) {
        $script = '  <script src="/assets/js/demo-mode.js" defer></script>' . PHP_EOL;
        $html = str_replace('</body>', $script . '</body>', $html);
    }

    return $html;
}

function static_demo_validate_output(string $outputRoot, array $routes): array
{
    if (!is_dir($outputRoot)) {
        return ["Static demo output directory does not exist: {$outputRoot}"];
    }

    $errors = [];

    foreach ($routes as $route => $relativePath) {
        $file = $outputRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        if (!is_file($file)) {
            $errors[] = "Missing output for {$route}: {$relativePath}";
        }
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($outputRoot, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $extension = strtolower($file->getExtension());
        $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($outputRoot) + 1));

        if ($extension === 'php') {
            $errors[] = "Forbidden PHP file in static demo: {$relativePath}";
            continue;
        }

        if ($extension !== 'html') {
            continue;
        }

        $html = (string) file_get_contents($file->getPathname());
        if (!str_contains($html, '/assets/js/demo-mode.js')) {
            $errors[] = "Demo mode script is missing from {$relativePath}";
        }
        if (str_contains($html, 'action="/submit.php"')) {
            $errors[] = "Live form action remains in {$relativePath}";
        }

        preg_match_all('~\b(?:href|src)=(?:"([^"]+)"|\'([^\']+)\')~i', $html, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $target = (string) ($match[1] !== '' ? $match[1] : $match[2]);
            if ($target === '' || $target[0] === '#' || str_starts_with($target, '//')) {
                continue;
            }
            if (preg_match('~^(?:https?:|mailto:|tel:|data:)~i', $target) === 1) {
                continue;
            }

            $path = (string) (parse_url($target, PHP_URL_PATH) ?? '');
            if ($path === '' || $path[0] !== '/') {
                continue;
            }

            $decodedPath = rawurldecode($path);
            $expected = $decodedPath === '/'
                ? 'index.html'
                : (str_ends_with($decodedPath, '/')
                    ? ltrim($decodedPath, '/') . 'index.html'
                    : ltrim($decodedPath, '/'));
            $expectedFile = $outputRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $expected);

            if (!is_file($expectedFile)) {
                $errors[] = "Broken internal target {$target} in {$relativePath}";
            }
        }
    }

    $errors = array_values(array_unique($errors));
    sort($errors);
    return $errors;
}
