<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
http_response_code(404);

echo render_page('404', [
    'seo' => seo_for_page('404'),
    'schemas' => [],
    'pageClass' => 'page-404',
]);
