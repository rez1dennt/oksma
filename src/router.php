<?php

declare(strict_types=1);

function resolve_route(string $path): array
{
    $path = (string) (parse_url($path, PHP_URL_PATH) ?: '/');
    $normalized = trim(preg_replace('#/+#', '/', $path), '/');

    if ($normalized === '') {
        return ['name' => 'home'];
    }

    if ($normalized === 'privacy') {
        return ['name' => 'privacy'];
    }

    if ($normalized === 'documents') {
        return ['name' => 'documents'];
    }

    if ($normalized === 'sitemap.xml') {
        return ['name' => 'sitemap'];
    }

    if ($normalized === 'robots.txt') {
        return ['name' => 'robots'];
    }

    if (preg_match('#^catalog/([a-z0-9-]+)$#', $normalized, $matches) === 1) {
        return find_category($matches[1]) !== null
            ? ['name' => 'category', 'slug' => $matches[1]]
            : ['name' => 'not-found'];
    }

    if (preg_match('#^product/([a-z0-9-]+)$#', $normalized, $matches) === 1) {
        return find_product($matches[1]) !== null
            ? ['name' => 'product', 'slug' => $matches[1]]
            : ['name' => 'not-found'];
    }

    return ['name' => 'not-found'];
}

function site_url(string $path = '/'): string
{
    $baseUrl = rtrim((string) app_config()['base_url'], '/');
    $normalizedPath = '/' . ltrim($path, '/');

    return $baseUrl . ($normalizedPath === '/' ? '/' : $normalizedPath);
}
