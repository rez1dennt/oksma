<?php

declare(strict_types=1);

function document_registry(): array
{
    static $documents;

    return $documents ??= require dirname(__DIR__) . '/data/documents.php';
}

function all_documents(): array
{
    return array_values(document_registry());
}

function find_document(string $id): ?array
{
    return document_registry()[$id] ?? null;
}

function documents_for_product(array $product): array
{
    $documents = [];

    foreach (array_unique($product['document_ids'] ?? []) as $id) {
        $document = find_document((string) $id);
        if ($document !== null) {
            $documents[] = $document;
        }
    }

    return $documents;
}

function format_document_date(string $isoDate): string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $isoDate);

    return $date === false ? $isoDate : $date->format('d.m.Y');
}

function document_integrity_errors(?string $rootPath = null): array
{
    $errors = [];
    $rootPath ??= dirname(__DIR__);

    foreach (document_registry() as $key => $document) {
        if (($document['id'] ?? null) !== $key) {
            $errors[] = "Document key {$key} differs from id";
        }

        foreach (['title', 'registration_number', 'status', 'valid_from', 'valid_until', 'file'] as $field) {
            if (($document[$field] ?? '') === '') {
                $errors[] = "Document {$key} has an empty {$field}";
            }
        }

        foreach (['valid_from', 'valid_until'] as $field) {
            if (DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($document[$field] ?? '')) === false) {
                $errors[] = "Document {$key} has an invalid {$field}";
            }
        }

        $file = $rootPath . str_replace('/', DIRECTORY_SEPARATOR, (string) ($document['file'] ?? ''));
        if (!is_file($file) || filesize($file) < 10000) {
            $errors[] = "Document {$key} PDF is missing or empty";
        }
    }

    if (function_exists('catalog_products')) {
        foreach (catalog_products() as $product) {
            foreach ($product['document_ids'] ?? [] as $id) {
                if (find_document((string) $id) === null) {
                    $errors[] = "Product {$product['slug']} has an unknown document {$id}";
                }
            }
        }
    }

    return $errors;
}
