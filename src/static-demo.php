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

function static_demo_normalize_path(string $path): string
{
    $path = str_replace('\\', '/', $path);
    if (preg_match('~^(?:[A-Za-z]:)?/~', $path) !== 1) {
        $path = str_replace('\\', '/', (string) getcwd()) . '/' . $path;
    }

    $prefix = '';
    if (preg_match('~^[A-Za-z]:~', $path) === 1) {
        $prefix = strtoupper(substr($path, 0, 2));
        $path = substr($path, 2);
    }

    $segments = [];
    foreach (explode('/', $path) as $segment) {
        if ($segment === '' || $segment === '.') {
            continue;
        }
        if ($segment === '..') {
            array_pop($segments);
            continue;
        }
        $segments[] = $segment;
    }

    return $prefix . '/' . implode('/', $segments);
}

function static_demo_remove_tree(string $projectRoot, string $target): void
{
    $root = rtrim(static_demo_normalize_path($projectRoot), '/');
    $normalizedTarget = rtrim(static_demo_normalize_path($target), '/');
    $comparisonRoot = DIRECTORY_SEPARATOR === '\\' ? strtolower($root) : $root;
    $comparisonTarget = DIRECTORY_SEPARATOR === '\\' ? strtolower($normalizedTarget) : $normalizedTarget;
    $basename = basename($normalizedTarget);

    if (
        $comparisonTarget === $comparisonRoot
        || !str_starts_with($comparisonTarget, $comparisonRoot . '/')
        || !in_array($basename, ['vercel-demo', 'test-vercel-demo'], true)
    ) {
        throw new RuntimeException("Refusing to remove unsafe static demo target: {$target}");
    }

    if (!is_dir($normalizedTarget)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($normalizedTarget, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isDir() && !$file->isLink()) {
            if (!rmdir($file->getPathname())) {
                throw new RuntimeException("Unable to remove directory: {$file->getPathname()}");
            }
            continue;
        }
        if (!unlink($file->getPathname())) {
            throw new RuntimeException("Unable to remove file: {$file->getPathname()}");
        }
    }

    if (!rmdir($normalizedTarget)) {
        throw new RuntimeException("Unable to remove directory: {$normalizedTarget}");
    }
}

function static_demo_copy_tree(string $source, string $target): int
{
    if (!is_dir($source)) {
        throw new RuntimeException("Public asset directory does not exist: {$source}");
    }
    if (!is_dir($target) && !mkdir($target, 0775, true) && !is_dir($target)) {
        throw new RuntimeException("Unable to create asset directory: {$target}");
    }

    $copied = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $relativePath = substr($item->getPathname(), strlen($source) + 1);
        $destination = $target . DIRECTORY_SEPARATOR . $relativePath;
        if ($item->isDir()) {
            if (!is_dir($destination) && !mkdir($destination, 0775, true) && !is_dir($destination)) {
                throw new RuntimeException("Unable to create asset directory: {$destination}");
            }
            continue;
        }
        if (!copy($item->getPathname(), $destination)) {
            throw new RuntimeException("Unable to copy public asset: {$relativePath}");
        }
        $copied++;
    }

    return $copied;
}

function static_demo_render_route(string $projectRoot, string $route): string
{
    $renderer = $projectRoot . '/scripts/render-static-route.php';
    if (!is_file($renderer)) {
        throw new RuntimeException("Static route renderer does not exist: {$renderer}");
    }

    $descriptorSpec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open([PHP_BINARY, $renderer, $route], $descriptorSpec, $pipes, $projectRoot);
    if (!is_resource($process)) {
        throw new RuntimeException("Unable to start static renderer for {$route}");
    }

    $html = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        throw new RuntimeException("Static renderer failed for {$route}: " . trim((string) $error));
    }

    return (string) $html;
}

function static_demo_export(string $projectRoot, string $outputRoot): array
{
    $projectRoot = rtrim(static_demo_normalize_path($projectRoot), '/');
    $outputRoot = rtrim(static_demo_normalize_path($outputRoot), '/');
    static_demo_remove_tree($projectRoot, $outputRoot);

    if (!mkdir($outputRoot, 0775, true) && !is_dir($outputRoot)) {
        throw new RuntimeException("Unable to create static demo output: {$outputRoot}");
    }

    $routes = static_demo_routes();
    $pages = 0;
    foreach ($routes as $route => $relativePath) {
        $contents = static_demo_render_route($projectRoot, $route);
        if (str_ends_with($relativePath, '.html')) {
            $contents = static_demo_transform_html($contents);
        }

        $destination = $outputRoot . '/' . $relativePath;
        $directory = dirname($destination);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create page directory: {$directory}");
        }
        if (file_put_contents($destination, $contents) === false) {
            throw new RuntimeException("Unable to write static page: {$relativePath}");
        }
        $pages++;
    }

    $assets = static_demo_copy_tree($projectRoot . '/assets', $outputRoot . '/assets');
    foreach ([
        $projectRoot . '/favicon.ico' => $outputRoot . '/favicon.ico',
        $projectRoot . '/scripts/static-demo/demo-mode.js' => $outputRoot . '/assets/js/demo-mode.js',
        $projectRoot . '/scripts/static-demo/vercel.json' => $outputRoot . '/vercel.json',
    ] as $source => $destination) {
        if (!is_file($source)) {
            throw new RuntimeException("Static demo template does not exist: {$source}");
        }
        if (!is_dir(dirname($destination)) && !mkdir(dirname($destination), 0775, true) && !is_dir(dirname($destination))) {
            throw new RuntimeException("Unable to create output directory: " . dirname($destination));
        }
        if (!copy($source, $destination)) {
            throw new RuntimeException("Unable to copy static demo file: {$source}");
        }
        $assets++;
    }

    return [
        'pages' => $pages,
        'assets' => $assets,
        'errors' => static_demo_validate_output($outputRoot, $routes),
    ];
}
