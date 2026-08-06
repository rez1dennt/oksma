<?php

declare(strict_types=1);

function catalog_data(): array
{
    static $data;

    return $data ??= require dirname(__DIR__) . '/data/catalog.php';
}

function catalog_categories(): array
{
    return catalog_data()['categories'];
}

function catalog_products(): array
{
    return catalog_data()['products'];
}

function find_category(string $slug): ?array
{
    return catalog_categories()[$slug] ?? null;
}

function find_product(string $slug): ?array
{
    return catalog_products()[$slug] ?? null;
}

function products_for_category(string $categorySlug): array
{
    return array_values(array_filter(
        catalog_products(),
        static fn (array $product): bool => $product['category'] === $categorySlug
    ));
}

function related_products(array $product): array
{
    $currentSlug = $product['slug'] ?? '';
    $related = [];

    foreach (array_unique($product['related'] ?? []) as $slug) {
        if ($slug === $currentSlug) {
            continue;
        }

        $candidate = find_product((string) $slug);
        if ($candidate !== null) {
            $related[] = $candidate;
        }
    }

    return $related;
}

function catalog_integrity_errors(): array
{
    $errors = [];

    foreach (catalog_categories() as $key => $category) {
        if (($category['slug'] ?? null) !== $key) {
            $errors[] = "Category key {$key} differs from slug";
        }
    }

    foreach (catalog_products() as $key => $product) {
        if (($product['slug'] ?? null) !== $key) {
            $errors[] = "Product key {$key} differs from slug";
        }

        if (find_category((string) ($product['category'] ?? '')) === null) {
            $errors[] = "Product {$key} has an unknown category";
        }

        foreach ($product['related'] ?? [] as $relatedSlug) {
            if ($relatedSlug !== $key && find_product((string) $relatedSlug) === null) {
                $errors[] = "Product {$key} has an unknown related product {$relatedSlug}";
            }
        }
    }

    return $errors;
}
