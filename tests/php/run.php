<?php

declare(strict_types=1);

$tests = [];

function test(string $name, callable $callback): void
{
    global $tests;
    $tests[$name] = $callback;
}

function same(mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            'Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)
        );
    }
}

function truthy(bool $value): void
{
    if (!$value) {
        throw new RuntimeException('Expected truthy value');
    }
}

foreach (glob(__DIR__ . '/*_test.php') as $file) {
    require $file;
}

$failed = 0;

foreach ($tests as $name => $callback) {
    try {
        $callback();
        echo "PASS {$name}\n";
    } catch (Throwable $error) {
        $failed++;
        echo "FAIL {$name}: {$error->getMessage()}\n";
    }
}

echo sprintf("%d test(s), %d failure(s)\n", count($tests), $failed);
exit($failed === 0 ? 0 : 1);
