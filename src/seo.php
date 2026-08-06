<?php

declare(strict_types=1);

function seo_for_page(string $type, ?array $entity = null): array
{
    $config = app_config();
    $seo = [
        'title' => 'ОКСМА — промышленная техника с доставкой по России',
        'description' => $config['description'],
        'canonical' => site_url('/'),
        'robots' => 'index,follow',
        'og_type' => 'website',
        'image' => site_url('/assets/images/hero-industrial-loader.webp'),
    ];

    if ($type === 'category' && $entity !== null) {
        return array_replace($seo, [
            'title' => $entity['seo_title'],
            'description' => $entity['seo_description'],
            'canonical' => site_url("/catalog/{$entity['slug']}/"),
        ]);
    }

    if ($type === 'product' && $entity !== null) {
        return array_replace($seo, [
            'title' => $entity['seo_title'],
            'description' => $entity['seo_description'],
            'canonical' => site_url("/product/{$entity['slug']}/"),
            'og_type' => 'product',
            'image' => site_url($entity['images'][0]),
        ]);
    }

    if ($type === 'privacy') {
        return array_replace($seo, [
            'title' => 'Политика конфиденциальности — ОКСМА',
            'description' => 'Правила обработки персональных данных посетителей сайта ОКСМА.',
            'canonical' => site_url('/privacy/'),
        ]);
    }

    if ($type === 'documents') {
        return array_replace($seo, [
            'title' => 'Документы и декларации на технику — ОКСМА',
            'description' => 'Действующие декларации о соответствии на прицепные загрузчики, цистерны, полуприцепы и прицепы ОКСМА.',
            'canonical' => site_url('/documents/'),
        ]);
    }

    if ($type === '404') {
        return array_replace($seo, [
            'title' => 'Страница не найдена — ОКСМА',
            'description' => 'Запрошенная страница не найдена.',
            'canonical' => site_url('/404/'),
            'robots' => 'noindex,follow',
        ]);
    }

    return $seo;
}

function organization_schema(): array
{
    $config = app_config();

    return [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $config['name'],
        'legalName' => $config['legal_name'],
        'url' => site_url('/'),
        'email' => $config['email'],
        'telephone' => $config['phones'][0]['href'],
        'identifier' => [
            ['@type' => 'PropertyValue', 'propertyID' => 'ИНН', 'value' => $config['requisites']['inn']],
            ['@type' => 'PropertyValue', 'propertyID' => 'ОГРН', 'value' => $config['requisites']['ogrn']],
        ],
    ];
}

function breadcrumb_schema(array $items): array
{
    $elements = [];

    foreach (array_values($items) as $index => $item) {
        $elements[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['name'],
            'item' => site_url($item['url']),
        ];
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $elements,
    ];
}

function product_schema(array $product): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product['name'] . ' ' . $product['subtitle'],
        'sku' => $product['sku'],
        'description' => $product['summary'],
        'image' => array_map(static fn (string $path): string => site_url($path), $product['images']),
        'brand' => ['@type' => 'Brand', 'name' => app_config()['name']],
        'manufacturer' => ['@type' => 'Organization', 'name' => app_config()['legal_name']],
    ];
}

function sitemap_urls(): array
{
    $urls = [site_url('/'), site_url('/documents/'), site_url('/privacy/')];

    foreach (catalog_categories() as $category) {
        $urls[] = site_url("/catalog/{$category['slug']}/");
    }

    foreach (catalog_products() as $product) {
        $urls[] = site_url("/product/{$product['slug']}/");
    }

    return array_values(array_unique($urls));
}
