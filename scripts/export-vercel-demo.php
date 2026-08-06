<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
require $projectRoot . '/bootstrap.php';
require $projectRoot . '/src/static-demo.php';

$outputRoot = isset($argv[1])
    ? static_demo_normalize_path((string) $argv[1])
    : $projectRoot . '/vercel-demo';

try {
    $report = static_demo_export($projectRoot, $outputRoot);
    echo "Exported {$report['pages']} pages and {$report['assets']} assets." . PHP_EOL;

    if ($report['errors'] !== []) {
        foreach ($report['errors'] as $error) {
            fwrite(STDERR, "ERROR: {$error}" . PHP_EOL);
        }
        exit(1);
    }

    echo 'Validation passed.' . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'Export failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
