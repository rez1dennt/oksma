<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$path = (string) ($argv[1] ?? '/');
$siteUrl = (string) (getenv('STATIC_DEMO_SITE_URL') ?: 'https://oksma-demo.vercel.app');
putenv('SITE_URL=' . rtrim($siteUrl, '/'));

$_SERVER['REQUEST_URI'] = $path;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = (string) (parse_url($siteUrl, PHP_URL_HOST) ?: 'oksma-demo.vercel.app');
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = '443';
$_GET = [];

require $projectRoot . '/bootstrap.php';

$route = resolve_route($path);
$controller = match ($route['name']) {
    'home' => 'index.php',
    'category' => 'catalog.php',
    'product' => 'product.php',
    'privacy' => 'privacy.php',
    'documents' => 'documents.php',
    'sitemap' => 'sitemap.php',
    'robots' => 'robots.php',
    default => '404.php',
};

if (isset($route['slug'])) {
    $_GET['slug'] = $route['slug'];
}

require $projectRoot . '/' . $controller;
