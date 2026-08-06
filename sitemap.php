<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/xml; charset=UTF-8');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach (sitemap_urls() as $url) {
    echo '  <url><loc>' . htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc></url>' . "\n";
}
echo '</urlset>';
