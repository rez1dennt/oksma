<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

test('apache rules deny direct access to internal project directories', function () use ($root): void {
    $rules = file_get_contents($root . '/.htaccess');
    foreach (['config', 'data', 'docs', 'scripts', 'src', 'storage', 'templates', 'tests', 'tokens', 'tools', 'vendor'] as $directory) {
        truthy(str_contains($rules, $directory));
    }
    truthy(str_contains($rules, 'Options -Indexes'));
});

test('native mail delivery needs no smtp secret or composer dependency', function () use ($root): void {
    same(false, is_file($root . '/config/mail.example.php'));
    same(false, is_file($root . '/composer.json'));
    same(false, is_file($root . '/composer.lock'));
    same(false, str_contains(file_get_contents($root . '/bootstrap.php'), 'vendor/autoload.php'));

    $deployment = file_get_contents($root . '/docs/DEPLOYMENT.md');
    truthy(str_contains($deployment, 'PHP `mail()`'));
    truthy(str_contains($deployment, 'oksmaprom@yandex.ru'));
    truthy(str_contains(file_get_contents($root . '/README.md'), 'PHP `mail()`'));
});
