<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$breadcrumbs = [
    ['name' => 'Главная', 'url' => '/'],
    ['name' => 'Документы и декларации', 'url' => '/documents/'],
];

echo render_page('documents', [
    'seo' => seo_for_page('documents'),
    'documents' => all_documents(),
    'breadcrumbs' => $breadcrumbs,
    'schemas' => [organization_schema(), breadcrumb_schema($breadcrumbs)],
    'pageClass' => 'page-documents',
]);
