<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: text/plain; charset=UTF-8');
echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /config/\n";
echo "Disallow: /data/\n";
echo "Disallow: /src/\n";
echo 'Sitemap: ' . site_url('/sitemap.xml') . "\n";
