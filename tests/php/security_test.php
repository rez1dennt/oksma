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

test('smtp secrets and generated dependencies stay outside source control', function () use ($root): void {
    $ignore = file_get_contents($root . '/.gitignore');
    truthy(str_contains($ignore, 'config/mail.php'));
    truthy(str_contains($ignore, 'vendor/'));
});
