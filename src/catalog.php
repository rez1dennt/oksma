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

function product_benefit_cards(array $benefits): array
{
    return array_map(
        static function (string $title, int $index): array {
            $icon = 'truck';
            $description = 'Конструкция и рабочие функции подобраны для ежедневной эксплуатации.';

            if (preg_match('/изготов|комплект|шасси|задач/ui', $title)) {
                $icon = 'wrench';
                $description = 'Исполнение и оснащение согласуем под ваши условия работы.';
            } elseif (preg_match('/гарант|сервис|сопровожд|поддерж/ui', $title)) {
                $icon = 'shield';
                $description = 'Остаёмся на связи и сопровождаем технику после поставки.';
            } elseif (preg_match('/достав/ui', $title)) {
                $icon = 'truck';
                $description = 'Организуем отправку готовой техники в любой регион России.';
            }

            return [
                'index' => sprintf('%02d', $index + 1),
                'title' => $title,
                'description' => $description,
                'icon' => $icon,
            ];
        },
        array_values($benefits),
        array_keys(array_values($benefits))
    );
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

        $benefits = array_values(array_filter(
            $product['benefits'] ?? [],
            static fn (mixed $benefit): bool => is_string($benefit) && trim($benefit) !== ''
        ));
        if (count($benefits) !== 3 || count(array_unique($benefits)) !== 3) {
            $errors[] = "Product {$key} must expose exactly three unique benefits";
        }

        foreach ($product['related'] ?? [] as $relatedSlug) {
            if ($relatedSlug !== $key && find_product((string) $relatedSlug) === null) {
                $errors[] = "Product {$key} has an unknown related product {$relatedSlug}";
            }
        }
    }

    return $errors;
}
