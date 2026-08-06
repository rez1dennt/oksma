<?php

declare(strict_types=1);

$path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$staticFile = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $path);

if ($path !== '/' && is_file($staticFile)) {
    return false;
}

require __DIR__ . '/bootstrap.php';
$route = resolve_route($path);

$controller = match ($route['name']) {
    'home' => 'index.php',
    'category' => 'catalog.php',
    'product' => 'product.php',
    'privacy' => 'privacy.php',
    'sitemap' => 'sitemap.php',
    'robots' => 'robots.php',
    default => '404.php',
};

if (isset($route['slug'])) {
    $_GET['slug'] = $route['slug'];
}

require __DIR__ . '/' . $controller;
