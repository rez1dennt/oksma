# Public Declarations Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Publish both official ОКСМА declarations on a dedicated page and show each declaration only on explicitly linked product pages.

**Architecture:** A focused document registry owns official metadata and PDF paths. Products reference stable document IDs explicitly, while the public documents page and the conditional product tab render the same reusable document-card partial. Route, SEO, sitemap, asset integrity, responsive layout, and accessibility are covered by PHP tests and browser QA.

**Tech Stack:** PHP 8, server-rendered HTML, Apache rewrite rules, CSS custom properties, vanilla JavaScript tabs, native PHP/Node test runners, PDF assets.

## Global Constraints

- Publish a public `/documents/` route and allow every visitor to open or download each PDF.
- Preserve the content of both official PDFs exactly; do not modify the originals in `C:\Users\bahti\Downloads`.
- Use ASCII public filenames without spaces or `№`.
- Link declarations to products only through explicit document IDs; never infer coverage from similar names or categories.
- Declaration `ЕАЭС N RU Д-RU.РА04.В.69139/26` applies to ПЗК-7, ПЗК-10, ПЗК-15, ПЗК-20 and ПЦ-2, ПЦ-6, ПЦ-8, ПЦ-10, ПЦ-11, ПЦ-12, ПЦ-16, ПЦ-20, ПЦ-24; it does not apply to ЗСК.
- Declaration `ЕАЭС N RU Д-RU.РА05.В.72423/26` applies to ППТС-6,5, ППТС-10, ППТС-12, ППТС-15, ППТС-18, ППТС-25, ППТС-30, ПГТС-6,5, ПГТС-10, ПГТС-12, ПГТС-15 and 1504М1-1504М6.
- Do not add an admin panel, upload UI, new products, product photos, or product specifications in this iteration.
- Keep the existing Onest typography, warm industrial palette, responsive behavior, phone mask, forms, cookie notice, and privacy page unchanged.

## File Map

- Create `data/documents.php`: canonical declaration metadata.
- Create `src/documents.php`: registry lookup, date formatting, product resolution, and integrity checks.
- Create `assets/documents/oksma-feed-trailers-declaration.pdf`: unchanged copy of declaration 1.
- Create `assets/documents/oksma-tractor-trailers-declaration.pdf`: unchanged copy of declaration 2.
- Create `documents.php`: page controller for `/documents/`.
- Create `templates/pages/documents.php`: documents page layout.
- Create `templates/partials/document-card.php`: shared document display for the page and product tab.
- Create `tests/php/documents_test.php`: registry, asset, relationship, and lookup tests.
- Modify `bootstrap.php`: load the document module.
- Modify `src/router.php`, `router.php`, and `.htaccess`: resolve and dispatch the clean route.
- Modify `src/seo.php`: documents metadata and sitemap entry.
- Modify `templates/partials/footer.php`: public documents link.
- Modify `data/catalog.php`: explicit declaration IDs for ПЗК-10 and ПЗК-15 only.
- Modify `product.php` and `templates/pages/product.php`: resolve and conditionally render product documents.
- Modify `assets/css/main.css`: documents page, cards, actions, responsive layout, and print behavior.
- Modify `tests/php/router_seo_test.php`, `tests/php/pages_test.php`, `tests/php/render_test.php`, and `tests/php/assets_test.php`: route, output, style, and server configuration coverage.

---

### Task 1: Document registry and immutable PDF assets

**Files:**
- Create: `data/documents.php`
- Create: `src/documents.php`
- Create: `tests/php/documents_test.php`
- Create: `assets/documents/oksma-feed-trailers-declaration.pdf`
- Create: `assets/documents/oksma-tractor-trailers-declaration.pdf`
- Modify: `bootstrap.php`

**Interfaces:**
- Produces: `document_registry(): array<string,array>`
- Produces: `all_documents(): array<int,array>`
- Produces: `find_document(string $id): ?array`
- Produces: `documents_for_product(array $product): array<int,array>`
- Produces: `format_document_date(string $isoDate): string`
- Produces: `document_integrity_errors(?string $rootPath = null): array<int,string>`
- Consumes later: `templates/pages/documents.php`, `documents.php`, `product.php`, and product-page tests use these functions.

- [ ] **Step 1: Write the failing registry tests**

Create `tests/php/documents_test.php` with three tests:

```php
<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/bootstrap.php';

test('document registry exposes two complete public declarations', function (): void {
    truthy(function_exists('document_registry'));
    truthy(function_exists('document_integrity_errors'));
    same(2, count(all_documents()));
    same('ЕАЭС N RU Д-RU.РА04.В.69139/26', find_document('feed-trailers-2026')['registration_number']);
    same('31.05.2031', format_document_date(find_document('feed-trailers-2026')['valid_until']));
    same('ЕАЭС N RU Д-RU.РА05.В.72423/26', find_document('tractor-trailers-2026')['registration_number']);
});

test('document lookup requires explicit product identifiers', function (): void {
    same(['feed-trailers-2026'], array_column(documents_for_product([
        'document_ids' => ['feed-trailers-2026'],
    ]), 'id'));
    same([], documents_for_product(['category' => 'zagruzchiki-suhih-kormov', 'name' => 'ЗСК-10']));
    same([], documents_for_product(['document_ids' => ['unknown-document']]));
});

test('document registry and public PDF assets pass integrity checks', function (): void {
    same([], document_integrity_errors(dirname(__DIR__, 2)));
});
```

- [ ] **Step 2: Run the PHP suite and verify the new tests fail**

Run: `php tests/php/run.php`

Expected: FAIL because `document_registry()` and the new public PDF files do not exist.

- [ ] **Step 3: Add the canonical document data**

Create `data/documents.php` returning two keyed records. Use these exact fields for both records: `id`, `title`, `registration_number`, `status`, `valid_from`, `valid_until`, `product_groups`, and `file`.

```php
<?php

declare(strict_types=1);

return [
    'feed-trailers-2026' => [
        'id' => 'feed-trailers-2026',
        'title' => 'Декларация на прицепные загрузчики кормов и цистерны',
        'registration_number' => 'ЕАЭС N RU Д-RU.РА04.В.69139/26',
        'status' => 'Действует',
        'valid_from' => '2026-06-03',
        'valid_until' => '2031-05-31',
        'product_groups' => [
            'ПЗК-7, ПЗК-10, ПЗК-15, ПЗК-20',
            'ПЦ-2, ПЦ-6, ПЦ-8, ПЦ-10, ПЦ-11, ПЦ-12, ПЦ-16, ПЦ-20, ПЦ-24',
        ],
        'file' => '/assets/documents/oksma-feed-trailers-declaration.pdf',
    ],
    'tractor-trailers-2026' => [
        'id' => 'tractor-trailers-2026',
        'title' => 'Декларация на тракторные полуприцепы и прицепы',
        'registration_number' => 'ЕАЭС N RU Д-RU.РА05.В.72423/26',
        'status' => 'Действует',
        'valid_from' => '2026-07-07',
        'valid_until' => '2031-07-05',
        'product_groups' => [
            'ППТС-6,5, ППТС-10, ППТС-12, ППТС-15, ППТС-18, ППТС-25, ППТС-30',
            'ПГТС-6,5, ПГТС-10, ПГТС-12, ПГТС-15',
            '1504М1, 1504М2, 1504М3, 1504М4, 1504М5, 1504М6',
        ],
        'file' => '/assets/documents/oksma-tractor-trailers-declaration.pdf',
    ],
];
```

- [ ] **Step 4: Implement the registry API and validation**

Create `src/documents.php` with deterministic lookups and validation:

```php
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
```

Load it immediately after `src/catalog.php` in `bootstrap.php`:

```php
require_once __DIR__ . '/src/catalog.php';
require_once __DIR__ . '/src/documents.php';
require_once __DIR__ . '/src/router.php';
```

- [ ] **Step 5: Copy the original PDFs without modifying them**

Run the following PowerShell commands from the repository root:

```powershell
New-Item -ItemType Directory -Force -Path 'assets\documents' | Out-Null
Copy-Item -LiteralPath 'C:\Users\bahti\Downloads\Деклорация ОКСМА ПРОМ №1.pdf' -Destination 'assets\documents\oksma-feed-trailers-declaration.pdf'
Copy-Item -LiteralPath 'C:\Users\bahti\Downloads\Деклорация ОКСМА ПРОМ №2.pdf' -Destination 'assets\documents\oksma-tractor-trailers-declaration.pdf'
```

Verify byte-for-byte copies:

```powershell
Get-FileHash -Algorithm SHA256 'C:\Users\bahti\Downloads\Деклорация ОКСМА ПРОМ №1.pdf','assets\documents\oksma-feed-trailers-declaration.pdf'
Get-FileHash -Algorithm SHA256 'C:\Users\bahti\Downloads\Деклорация ОКСМА ПРОМ №2.pdf','assets\documents\oksma-tractor-trailers-declaration.pdf'
```

Expected: each source hash equals its corresponding public asset hash.

- [ ] **Step 6: Run tests and commit the registry**

Run: `php tests/php/run.php`

Expected: 30 tests, 0 failures.

Commit:

```powershell
git add bootstrap.php data/documents.php src/documents.php tests/php/documents_test.php assets/documents
git commit -m "feat: add declaration registry and assets"
```

---

### Task 2: Public documents route, page, SEO, sitemap, and footer link

**Files:**
- Create: `documents.php`
- Create: `templates/pages/documents.php`
- Create: `templates/partials/document-card.php`
- Modify: `src/router.php`
- Modify: `router.php`
- Modify: `.htaccess`
- Modify: `src/seo.php`
- Modify: `templates/partials/footer.php`
- Modify: `tests/php/router_seo_test.php`
- Modify: `tests/php/pages_test.php`
- Modify: `tests/php/assets_test.php`

**Interfaces:**
- Consumes: `all_documents()`, `format_document_date()`, `seo_for_page('documents')`, `breadcrumb_schema()`.
- Produces: clean public route `/documents/` and reusable `document-card` partial accepting `document: array` and optional `compact: bool`.

- [ ] **Step 1: Add failing route, SEO, sitemap, page, and MIME assertions**

Extend `tests/php/router_seo_test.php`:

```php
same(['name' => 'documents'], resolve_route('/documents/'));
```

Add a dedicated SEO test:

```php
test('documents page is indexable canonical and present in sitemap', function (): void {
    $seo = seo_for_page('documents');
    same('https://example.ru/documents/', $seo['canonical']);
    same('index,follow', $seo['robots']);
    truthy(str_contains($seo['title'], 'Документы'));
    truthy(in_array('https://example.ru/documents/', sitemap_urls(), true));
});
```

Add to `tests/php/pages_test.php`:

```php
test('documents page renders two accessible declaration cards', function (): void {
    $html = render_page('documents', [
        'seo' => seo_for_page('documents'),
        'documents' => all_documents(),
        'schemas' => [],
    ]);
    same(2, substr_count($html, 'class="document-card"'));
    truthy(str_contains($html, 'ЕАЭС N RU Д-RU.РА04.В.69139/26'));
    truthy(str_contains($html, 'ЕАЭС N RU Д-RU.РА05.В.72423/26'));
    truthy(str_contains($html, 'target="_blank"'));
    truthy(str_contains($html, ' download'));
    truthy(str_contains($html, 'href="/documents/"'));
});
```

Add to `tests/php/assets_test.php`:

```php
test('apache declares the PDF content type', function () use ($root): void {
    $rules = (string) file_get_contents($root . '/.htaccess');
    truthy(str_contains($rules, 'AddType application/pdf .pdf'));
});
```

- [ ] **Step 2: Run the PHP suite and verify route/page tests fail**

Run: `php tests/php/run.php`

Expected: FAIL because the route, SEO branch, page template, and MIME rule are absent.

- [ ] **Step 3: Add the route and controller**

Add to `resolve_route()` in `src/router.php` immediately after privacy:

```php
if ($normalized === 'documents') {
    return ['name' => 'documents'];
}
```

Add the controller mapping to root `router.php`:

```php
'documents' => 'documents.php',
```

Create root `documents.php`:

```php
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
```

Add to `.htaccess` before the sitemap rule:

```apache
RewriteRule ^documents/?$ documents.php [L,NC]
```

Add the MIME declaration before the security headers block:

```apache
<IfModule mod_mime.c>
  AddType application/pdf .pdf
</IfModule>
```

- [ ] **Step 4: Add documents SEO and sitemap coverage**

Add to `seo_for_page()` in `src/seo.php`:

```php
if ($type === 'documents') {
    return array_replace($seo, [
        'title' => 'Документы и декларации на технику — ОКСМА',
        'description' => 'Действующие декларации о соответствии на прицепные загрузчики, цистерны, полуприцепы и прицепы ОКСМА.',
        'canonical' => site_url('/documents/'),
    ]);
}
```

Change the sitemap seed to:

```php
$urls = [site_url('/'), site_url('/documents/'), site_url('/privacy/')];
```

- [ ] **Step 5: Create the reusable document card and page template**

Create `templates/partials/document-card.php`:

```php
<?php $compact = (bool) ($compact ?? false); ?>
<article class="document-card<?= $compact ? ' document-card--compact' : '' ?>">
  <div class="document-card__header">
    <p class="document-card__type">Декларация о соответствии</p>
    <span class="document-card__status"><?= e($document['status']) ?></span>
  </div>
  <h2><?= e($document['title']) ?></h2>
  <dl class="document-card__meta">
    <div><dt>Регистрационный номер</dt><dd class="document-card__number"><?= e($document['registration_number']) ?></dd></div>
    <div><dt>Срок действия</dt><dd><time datetime="<?= e($document['valid_from']) ?>"><?= e(format_document_date($document['valid_from'])) ?></time> — <time datetime="<?= e($document['valid_until']) ?>"><?= e(format_document_date($document['valid_until'])) ?></time></dd></div>
  </dl>
  <div class="document-card__groups">
    <h3>Распространяется на модели</h3>
    <ul><?php foreach ($document['product_groups'] as $group): ?><li><?= e($group) ?></li><?php endforeach; ?></ul>
  </div>
  <div class="document-card__actions">
    <a class="button button--primary" href="<?= e($document['file']) ?>" target="_blank" rel="noopener">Открыть PDF<span class="sr-only"> в новой вкладке</span></a>
    <a class="button button--secondary" href="<?= e($document['file']) ?>" download>Скачать</a>
  </div>
</article>
```

Create `templates/pages/documents.php`:

```php
<section class="documents-hero section">
  <div class="container">
    <?= render_partial('breadcrumbs', ['items' => $breadcrumbs ?? [
        ['name' => 'Главная', 'url' => '/'],
        ['name' => 'Документы и декларации', 'url' => '/documents/'],
    ]]) ?>
    <p class="eyebrow">Официальные документы</p>
    <h1>Документы и декларации</h1>
    <p>Публикуем действующие декларации на серийно выпускаемую технику ОКСМА. Документы можно открыть в браузере или скачать.</p>
  </div>
</section>

<section class="section documents-section">
  <div class="container document-grid">
    <?php foreach ($documents as $document): ?>
      <?= render_partial('document-card', ['document' => $document]) ?>
    <?php endforeach; ?>
  </div>
</section>
```

Add this item to the footer navigation before the privacy link:

```php
<li><a href="/documents/">Документы и декларации</a></li>
```

- [ ] **Step 6: Run tests and commit the public route**

Run: `php tests/php/run.php`

Expected: 33 tests, 0 failures.

Commit:

```powershell
git add .htaccess documents.php src/router.php router.php src/seo.php templates/pages/documents.php templates/partials/document-card.php templates/partials/footer.php tests/php/router_seo_test.php tests/php/pages_test.php tests/php/assets_test.php
git commit -m "feat: publish declarations page"
```

---

### Task 3: Explicit product links and conditional documents tab

**Files:**
- Modify: `data/catalog.php`
- Modify: `product.php`
- Modify: `templates/pages/product.php`
- Modify: `tests/php/documents_test.php`
- Modify: `tests/php/pages_test.php`

**Interfaces:**
- Consumes: `documents_for_product(array $product): array` and `document-card` partial.
- Produces: `$documents` controller view data and a third tab only for products with declarations.

- [ ] **Step 1: Add failing product relationship tests**

Append to `tests/php/documents_test.php`:

```php
test('catalog links the first declaration to PZK but never to ZSK', function (): void {
    same(['feed-trailers-2026'], array_column(documents_for_product(find_product('pzk-10')), 'id'));
    same(['feed-trailers-2026'], array_column(documents_for_product(find_product('pzk-15')), 'id'));
    same([], documents_for_product(find_product('zsk-10')));
    same([], document_integrity_errors(dirname(__DIR__, 2)));
});
```

Append to `tests/php/pages_test.php`:

```php
test('product documents tab is conditional and uses the shared declaration data', function (): void {
    $pzk = find_product('pzk-10');
    $pzkHtml = render_page('product', [
        'seo' => seo_for_page('product', $pzk),
        'product' => $pzk,
        'category' => find_category($pzk['category']),
        'related' => related_products($pzk),
        'documents' => documents_for_product($pzk),
        'schemas' => [product_schema($pzk)],
    ]);
    truthy(str_contains($pzkHtml, 'id="tab-documents"'));
    truthy(str_contains($pzkHtml, 'ЕАЭС N RU Д-RU.РА04.В.69139/26'));

    $zsk = find_product('zsk-10');
    $zskHtml = render_page('product', [
        'seo' => seo_for_page('product', $zsk),
        'product' => $zsk,
        'category' => find_category($zsk['category']),
        'related' => related_products($zsk),
        'documents' => documents_for_product($zsk),
        'schemas' => [product_schema($zsk)],
    ]);
    truthy(!str_contains($zskHtml, 'id="tab-documents"'));
    truthy(!str_contains($zskHtml, 'ЕАЭС N RU Д-RU.РА04.В.69139/26'));
});
```

- [ ] **Step 2: Run tests and verify only PZK mapping/tab tests fail**

Run: `php tests/php/run.php`

Expected: FAIL because ПЗК product records do not contain `document_ids` and the product template has no documents tab.

- [ ] **Step 3: Add explicit catalog relationships**

Add the following field to both `pzk-10` and `pzk-15` records in `data/catalog.php`, next to `related`:

```php
'document_ids' => ['feed-trailers-2026'],
```

Do not add `document_ids` to any `zsk-*` record.

- [ ] **Step 4: Resolve documents in the product controller**

Add this view value in root `product.php`:

```php
'documents' => documents_for_product($product),
```

- [ ] **Step 5: Render the conditional accessible tab**

In `templates/pages/product.php`, add this button after the equipment tab only when `$documents !== []`:

```php
<?php if ($documents !== []): ?>
  <button role="tab" id="tab-documents" aria-selected="false" aria-controls="panel-documents" tabindex="-1">Документы</button>
<?php endif; ?>
```

Add its panel after `panel-equipment`:

```php
<?php if ($documents !== []): ?>
  <div class="tabs__panel" role="tabpanel" id="panel-documents" aria-labelledby="tab-documents" hidden>
    <div class="document-grid document-grid--product">
      <?php foreach ($documents as $document): ?>
        <?= render_partial('document-card', ['document' => $document, 'compact' => true]) ?>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>
```

Update the existing product render test to pass:

```php
'documents' => documents_for_product($product),
```

The existing JavaScript already discovers every `[role="tab"]`, updates `aria-selected`, toggles the controlled panel, and supports ArrowLeft, ArrowRight, Home, and End. No JavaScript change is required.

- [ ] **Step 6: Run tests and commit product linkage**

Run: `php tests/php/run.php`

Expected: 35 tests, 0 failures.

Commit:

```powershell
git add data/catalog.php product.php templates/pages/product.php tests/php/documents_test.php tests/php/pages_test.php
git commit -m "feat: link declarations to eligible products"
```

---

### Task 4: Responsive document UI and print behavior

**Files:**
- Modify: `assets/css/main.css`
- Modify: `tests/php/render_test.php`

**Interfaces:**
- Consumes: classes emitted by `documents.php` and `document-card.php`.
- Produces: responsive two-column page cards, single-column product cards, full-width mobile actions, visible focus inherited from `.button`, and hidden print actions.

- [ ] **Step 1: Add a failing CSS contract test**

Append to `tests/php/render_test.php`:

```php
test('document cards define responsive and print-safe presentation', function (): void {
    $css = (string) file_get_contents(dirname(__DIR__, 2) . '/assets/css/main.css');
    foreach (['.documents-hero', '.document-grid', '.document-card', '.document-card__status', '.document-card__actions'] as $selector) {
        truthy(str_contains($css, $selector));
    }
    truthy((bool) preg_match('/@media \(min-width: 48em\).*?\.document-grid\s*\{[^}]*grid-template-columns:/s', $css));
    truthy((bool) preg_match('/@media print.*?\.document-card__actions/s', $css));
});
```

- [ ] **Step 2: Run tests and verify the CSS contract fails**

Run: `php tests/php/run.php`

Expected: FAIL because the document selectors are not present.

- [ ] **Step 3: Add the base document styles**

Add this block before the product-page styles in `assets/css/main.css`:

```css
.documents-hero { color: var(--color-text-primary); background: linear-gradient(135deg, var(--color-surface-sage), var(--color-surface-page)); }
.documents-hero .breadcrumbs { margin-block-end: var(--space-8); }
.documents-hero h1 { max-width: 14ch; overflow-wrap: anywhere; }
.documents-hero > .container > p:last-child { max-width: var(--measure); color: var(--color-text-secondary); font-size: var(--font-size-lg); }
.documents-section { background: var(--color-surface-page); }
.document-grid { display: grid; gap: var(--space-6); }
.document-grid--product { grid-template-columns: 1fr; }
.document-card { display: grid; align-content: start; gap: var(--space-6); min-width: 0; padding: clamp(var(--space-6), 4vw, var(--space-10)); background: var(--color-surface-card); border: var(--border-thin) solid var(--color-border-default); border-radius: var(--radius-lg); box-shadow: var(--shadow-card); }
.document-card--compact { box-shadow: none; }
.document-card__header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: var(--space-3); }
.document-card__type { margin: 0; color: var(--color-action-primary); font-size: var(--font-size-xs); font-weight: var(--weight-bold); letter-spacing: .08em; text-transform: uppercase; }
.document-card__status { display: inline-flex; align-items: center; min-height: var(--control-sm); padding-inline: var(--space-4); color: var(--color-feedback-success); background: var(--color-surface-sage); border-radius: var(--radius-full); font-size: var(--font-size-sm); font-weight: var(--weight-bold); }
.document-card h2 { margin: 0; font-size: clamp(var(--font-size-xl), 3vw, var(--font-size-2xl)); }
.document-card__meta { display: grid; gap: var(--space-3); margin: 0; }
.document-card__meta div { display: grid; gap: var(--space-1); }
.document-card__meta dt { color: var(--color-text-tertiary); font-size: var(--font-size-sm); }
.document-card__meta dd { margin: 0; font-weight: var(--weight-semibold); }
.document-card__number { overflow-wrap: anywhere; }
.document-card__groups h3 { font-size: var(--font-size-base); }
.document-card__groups ul { display: grid; gap: var(--space-2); margin: 0; padding-inline-start: var(--space-5); color: var(--color-text-secondary); }
.document-card__actions { display: flex; flex-wrap: wrap; gap: var(--space-3); margin-block-start: auto; padding-block-start: var(--space-2); }
```

- [ ] **Step 4: Add responsive and print rules**

Inside the existing `@media (min-width: 48em)` block, add:

```css
.document-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.document-grid--product { grid-template-columns: 1fr; }
```

Inside the existing `@media (max-width: 47.999em)` block, add:

```css
.document-card__actions { display: grid; }
.document-card__actions .button { width: 100%; }
```

Inside the existing `@media print` block, add `.document-card__actions` to the hidden selector list. `.site-footer` is already hidden there. Keep `.documents-hero`, document titles, numbers, dates, and model lists printable. The resulting hidden selector must include:

```css
.document-card__actions { display: none !important; }
```

- [ ] **Step 5: Run tests and commit the UI**

Run: `php tests/php/run.php`

Expected: 36 tests, 0 failures.

Commit:

```powershell
git add assets/css/main.css tests/php/render_test.php
git commit -m "style: polish declaration cards"
```

---

### Task 5: Full verification and browser QA

**Files:**
- Verify only; fix the smallest relevant source/test file if a check exposes a defect.

**Interfaces:**
- Consumes: completed route, registry, product relationships, templates, CSS, existing tabs, and PDF assets.
- Produces: evidence that public documents work without regressions on desktop and mobile.

- [ ] **Step 1: Run all automated suites**

Run:

```powershell
php tests\php\run.php
npm run test:js
```

Expected: 36 PHP tests and 11 JavaScript tests pass with zero failures.

- [ ] **Step 2: Check syntax and repository diff**

Run:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
git diff --check
git status --short
```

Expected: every PHP file reports `No syntax errors detected`, `git diff --check` prints nothing, and status lists only intended declaration feature files.

- [ ] **Step 3: Verify public HTTP behavior**

With the existing local server at `http://127.0.0.1:8765`, verify:

- `/documents/` returns 200 and contains exactly two cards.
- Both «Открыть PDF» links return 200 with `Content-Type: application/pdf`.
- Both «Скачать» links point to the same unchanged files and have the `download` attribute.
- `/product/pzk-10/` and `/product/pzk-15/` contain the documents tab and first declaration.
- `/product/zsk-10/` has no documents tab and no first-declaration number.
- `/sitemap.xml` contains `/documents/` exactly once.

- [ ] **Step 4: Perform browser accessibility and responsive QA**

Use the in-app browser to inspect `/documents/`, `/product/pzk-10/`, and `/product/zsk-10/` at 1440×900 and 390×844. Confirm:

- no horizontal scroll;
- cards align evenly on desktop and stack on mobile;
- mobile buttons span the card width without awkward wrapping;
- registration numbers wrap without clipping;
- tab keyboard controls still work through ArrowLeft, ArrowRight, Home, and End;
- focus is visible on both PDF actions;
- opening a PDF uses a new tab and downloading does not navigate away;
- page typography, warm surfaces, sage status, and graphite/red accents match the current site.

- [ ] **Step 5: Commit any QA-only correction and record the final state**

If QA required a correction, stage only the corrected feature files and commit:

```powershell
git add assets/css/main.css templates/pages/documents.php templates/partials/document-card.php templates/pages/product.php tests/php
git commit -m "fix: finalize declaration experience"
```

If no correction was required, do not create an empty commit. Record `git status --short --branch` and the latest commit hash for handoff.
