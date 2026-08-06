<?php

declare(strict_types=1);

if (!function_exists('app_config')) {
    function app_config(): array
    {
        static $config;
        return $config ??= require __DIR__ . '/config/app.php';
    }
}

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

require_once __DIR__ . '/src/catalog.php';
require_once __DIR__ . '/src/router.php';
require_once __DIR__ . '/src/seo.php';
require_once __DIR__ . '/src/render.php';
require_once __DIR__ . '/src/form.php';
require_once __DIR__ . '/src/mailer.php';

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Lax',
    ]);
    session_start();
}
