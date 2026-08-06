# Industrial Catalog Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the approved responsive industrial equipment catalog as a production-ready PHP/HTML/CSS/JavaScript site with reusable catalog data, SMTP forms, SEO, and four primary page types.

**Architecture:** PHP front controllers render shared templates from one catalog data source. Pure domain, routing, SEO, form-validation, and JavaScript state helpers remain independently testable; Apache rewrites provide clean public URLs while `router.php` mirrors them for local development. PHPMailer 7 is the only production library and is deployed with Composer's generated `vendor` directory, so Node.js is never required on hosting.

**Tech Stack:** PHP 8.1+, Apache `.htaccess`, PHPMailer `^7.0`, semantic HTML5, CSS custom properties, native ES modules, Node 22 built-in test runner for development only.

## Global Constraints

- Production hosting requires only PHP 8.1+, Apache, HTML, CSS, and JavaScript.
- No admin panel, database, shopping cart, payment, search, or filtering in this phase.
- Catalog content lives in one PHP data file and is edited together with the client later.
- Preserve the references' industrial meaning, but use independent copy, composition details, and generated/local imagery.
- Do not include the LionTrade logo, name, customer logos, or branded machine markings.
- Forms send to email through SMTP; secrets never enter Git or browser output.
- Pages must support clean URLs, responsive layouts, keyboard access, reduced motion, and technical SEO.
- Implement behavior test-first and run fresh verification before every completion claim.

---

## File Map

```text
/.htaccess                     Apache clean-URL rules and security headers
/404.php                       Not-found page
/bootstrap.php                 Config, autoloading, session, shared helpers
/catalog.php                   Category front controller
/composer.json                 PHPMailer dependency and PHP platform floor
/config/app.php                Non-secret site configuration
/config/mail.example.php       Documented SMTP configuration shape
/data/catalog.php              Categories and products
/index.php                     Home page
/privacy.php                   Privacy page
/product.php                   Product front controller
/robots.php                    Environment-aware robots response
/router.php                    PHP development-server router
/sitemap.php                   XML sitemap response
/submit.php                    POST-only form endpoint
/assets/css/main.css           Tokens, layout, components, responsive and print styles
/assets/js/catalog-view.js     Pure catalog view preference logic
/assets/js/consent.js          Pure consent persistence logic
/assets/js/phone-mask.js       Pure Russian phone formatting logic
/assets/js/site.js             DOM wiring for navigation, modal, tabs, gallery and forms
/assets/images/                Generated hero and local demonstration imagery
/src/catalog.php               Catalog lookup and integrity functions
/src/form.php                  Form normalization, validation and anti-spam
/src/mailer.php                MailTransport abstraction and PHPMailer adapter
/src/render.php                Layout and component rendering helpers
/src/router.php                Route resolution and URL generation
/src/seo.php                   Metadata, JSON-LD and sitemap functions
/templates/layout.php          Shared HTML document shell
/templates/pages/              Home, category, product, privacy and 404 views
/templates/partials/           Header, footer, breadcrumb, cards, forms, modal and cookie notice
/tests/php/                    Dependency-free PHP test runner and test cases
/tests/js/                     Node built-in unit tests for pure JavaScript helpers
/docs/deployment.md            Hosting upload, SMTP and domain configuration
```

### Task 1: Project foundation and catalog domain

**Files:**
- Create: `.gitignore`
- Create: `composer.json`
- Create: `config/app.php`
- Create: `data/catalog.php`
- Create: `src/catalog.php`
- Create: `tests/php/run.php`
- Create: `tests/php/catalog_test.php`

**Interfaces:**
- Produces: `catalog_data(): array`, `catalog_categories(): array`, `catalog_products(): array`, `find_category(string): ?array`, `find_product(string): ?array`, `related_products(array): array`, `catalog_integrity_errors(): array`.
- Consumes: no project interfaces.

- [ ] **Step 1: Write the failing catalog tests**

```php
<?php
// tests/php/catalog_test.php
require_once dirname(__DIR__, 2) . '/src/catalog.php';

test('catalog has valid unique relationships', function (): void {
    same([], catalog_integrity_errors());
    truthy(find_category('zagruzchiki-suhih-kormov') !== null);
    same('ЗСК-10', find_product('zsk-10')['name']);
});

test('related products ignore missing and current entries', function (): void {
    $product = find_product('zsk-10');
    $related = related_products($product);
    truthy(count($related) > 0);
    truthy(!in_array('zsk-10', array_column($related, 'slug'), true));
});
```

```php
<?php
// tests/php/run.php
$tests = [];
function test(string $name, callable $callback): void { global $tests; $tests[$name] = $callback; }
function same(mixed $expected, mixed $actual): void {
    if ($expected !== $actual) throw new RuntimeException('Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
}
function truthy(bool $value): void { if (!$value) throw new RuntimeException('Expected truthy value'); }
foreach (glob(__DIR__ . '/*_test.php') as $file) require $file;
$failed = 0;
foreach ($tests as $name => $callback) {
    try { $callback(); echo "PASS {$name}\n"; }
    catch (Throwable $error) { $failed++; echo "FAIL {$name}: {$error->getMessage()}\n"; }
}
exit($failed === 0 ? 0 : 1);
```

- [ ] **Step 2: Run the test and verify the expected red state**

Run: `php tests/php/run.php`

Expected: FAIL because `src/catalog.php` and its functions do not exist.

- [ ] **Step 3: Add the minimal catalog implementation and configuration**

```php
<?php
// data/catalog.php
return [
    'categories' => [
        'zagruzchiki-suhih-kormov' => [
            'slug' => 'zagruzchiki-suhih-kormov',
            'name' => 'Загрузчики сухих кормов',
            'summary' => 'Техника для бережной перевозки и точной выгрузки комбикорма.',
            'image' => '/assets/images/category-feed.webp',
            'seo_title' => 'Загрузчики сухих кормов — каталог техники',
            'seo_description' => 'Каталог загрузчиков сухих кормов с изготовлением под задачу и доставкой по России.',
        ],
    ],
    'products' => [
        'zsk-10' => [
            'slug' => 'zsk-10', 'category' => 'zagruzchiki-suhih-kormov', 'name' => 'ЗСК-10',
            'subtitle' => 'Загрузчик сухих кормов', 'sku' => 'ZSK-10', 'badge' => 'Популярная модель',
            'summary' => 'Манёвренная техника для перевозки и дозированной выгрузки сухих кормов.',
            'description' => 'Модель подходит хозяйствам, которым важны быстрая загрузка, надёжная механика и выбор комплектации.',
            'images' => ['/assets/images/product-zsk-10-1.webp', '/assets/images/product-zsk-10-2.webp'],
            'benefits' => ['Доставка по России', 'Изготовление под задачу', 'Гарантия качества', 'Поддержка специалиста'],
            'specs' => ['Объём бункера' => '10 м³', 'Количество секций' => '3', 'Высота выгрузки' => 'до 6,5 м'],
            'dimensions' => ['Длина' => '5 090 мм', 'Ширина' => '2 400 мм', 'Высота' => '2 450 мм'],
            'equipment' => ['Карданный вал', 'Лестница', 'Защитное ограждение'],
            'related' => ['zsk-7'],
            'seo_title' => 'ЗСК-10 — загрузчик сухих кормов',
            'seo_description' => 'Характеристики и комплектация загрузчика сухих кормов ЗСК-10. Запросите расчёт стоимости.',
        ],
        'zsk-7' => [
            'slug' => 'zsk-7', 'category' => 'zagruzchiki-suhih-kormov', 'name' => 'ЗСК-7',
            'subtitle' => 'Компактный загрузчик сухих кормов', 'sku' => 'ZSK-7', 'badge' => '',
            'summary' => 'Компактная модель для хозяйств и узких производственных площадок.',
            'description' => 'Демонстрационное описание модели.',
            'images' => ['/assets/images/product-zsk-7-1.webp'], 'benefits' => ['Доставка по России'],
            'specs' => ['Объём бункера' => '7 м³'], 'dimensions' => ['Длина' => '4 700 мм'],
            'equipment' => ['Карданный вал'], 'related' => ['zsk-10'],
            'seo_title' => 'ЗСК-7 — загрузчик сухих кормов',
            'seo_description' => 'Компактный загрузчик сухих кормов ЗСК-7 с доставкой по России.',
        ],
    ],
];
```

```php
<?php
// src/catalog.php
function catalog_data(): array { static $data; return $data ??= require dirname(__DIR__) . '/data/catalog.php'; }
function catalog_categories(): array { return catalog_data()['categories']; }
function catalog_products(): array { return catalog_data()['products']; }
function find_category(string $slug): ?array { return catalog_categories()[$slug] ?? null; }
function find_product(string $slug): ?array { return catalog_products()[$slug] ?? null; }
function related_products(array $product): array {
    return array_values(array_filter(array_map(fn (string $slug) => find_product($slug), $product['related'] ?? [])));
}
function catalog_integrity_errors(): array {
    $errors = [];
    foreach (catalog_products() as $key => $product) {
        if ($key !== $product['slug']) $errors[] = "Product key {$key} differs from slug";
        if (find_category($product['category']) === null) $errors[] = "Product {$key} has an unknown category";
        foreach ($product['related'] ?? [] as $related) if ($related !== $key && find_product($related) === null) $errors[] = "Product {$key} has an unknown related product";
    }
    return $errors;
}
```

Add `composer.json` with PHP `>=8.1` and `phpmailer/phpmailer:^7.0`, `config/app.php` with the temporary site name `ПромТехника`, local base URL, phone, and email labels, and `.gitignore` entries for `/vendor/`, `/config/mail.php`, `/storage/*.log`, and `.env`.

- [ ] **Step 4: Run the catalog tests and verify green**

Run: `php tests/php/run.php`

Expected: both catalog tests PASS and exit code 0.

- [ ] **Step 5: Commit the foundation**

```powershell
git add .gitignore composer.json config/app.php data/catalog.php src/catalog.php tests/php
git commit -m "feat: add catalog domain foundation"
```

### Task 2: Clean routing, SEO, robots and sitemap

**Files:**
- Create: `.htaccess`
- Create: `bootstrap.php`
- Create: `router.php`
- Create: `src/router.php`
- Create: `src/seo.php`
- Create: `robots.php`
- Create: `sitemap.php`
- Create: `tests/php/router_seo_test.php`

**Interfaces:**
- Consumes: `find_category(string): ?array`, `find_product(string): ?array`.
- Produces: `resolve_route(string): array`, `site_url(string): string`, `seo_for_page(string, ?array): array`, `breadcrumb_schema(array): array`, `product_schema(array): array`, `sitemap_urls(): array`.

- [ ] **Step 1: Write failing route and SEO tests**

```php
<?php
require_once dirname(__DIR__, 2) . '/src/router.php';
require_once dirname(__DIR__, 2) . '/src/seo.php';

test('router resolves clean category and product URLs', function (): void {
    same(['name' => 'category', 'slug' => 'zagruzchiki-suhih-kormov'], resolve_route('/catalog/zagruzchiki-suhih-kormov/'));
    same(['name' => 'product', 'slug' => 'zsk-10'], resolve_route('/product/zsk-10/'));
    same(['name' => 'not-found'], resolve_route('/product/unknown/'));
});

test('product SEO contains canonical and Product schema without price', function (): void {
    $product = find_product('zsk-10');
    $seo = seo_for_page('product', $product);
    same('https://example.ru/product/zsk-10/', $seo['canonical']);
    same('Product', product_schema($product)['@type']);
    truthy(!array_key_exists('offers', product_schema($product)));
});

test('sitemap contains only valid public routes', function (): void {
    $urls = sitemap_urls();
    truthy(in_array('https://example.ru/product/zsk-10/', $urls, true));
    truthy(!in_array('https://example.ru/404/', $urls, true));
});
```

- [ ] **Step 2: Run the tests and verify red**

Run: `php tests/php/run.php`

Expected: FAIL because routing and SEO functions are absent.

- [ ] **Step 3: Implement routing and SEO helpers**

```php
<?php
// src/router.php
function resolve_route(string $path): array {
    $path = '/' . trim(parse_url($path, PHP_URL_PATH) ?: '/', '/') . '/';
    if ($path === '//') return ['name' => 'home'];
    if ($path === '/privacy/') return ['name' => 'privacy'];
    if (preg_match('#^/catalog/([a-z0-9-]+)/$#', $path, $m) && find_category($m[1])) return ['name' => 'category', 'slug' => $m[1]];
    if (preg_match('#^/product/([a-z0-9-]+)/$#', $path, $m) && find_product($m[1])) return ['name' => 'product', 'slug' => $m[1]];
    return ['name' => 'not-found'];
}
function site_url(string $path = '/'): string {
    $base = rtrim(app_config()['base_url'], '/');
    return $base . '/' . ltrim($path, '/');
}
```

```php
<?php
// src/seo.php
function seo_for_page(string $type, ?array $entity = null): array {
    $defaults = ['title' => app_config()['name'], 'description' => app_config()['description'], 'canonical' => site_url('/')];
    if ($type === 'category' && $entity) return ['title' => $entity['seo_title'], 'description' => $entity['seo_description'], 'canonical' => site_url("/catalog/{$entity['slug']}/")];
    if ($type === 'product' && $entity) return ['title' => $entity['seo_title'], 'description' => $entity['seo_description'], 'canonical' => site_url("/product/{$entity['slug']}/")];
    return $defaults;
}
function product_schema(array $product): array {
    return ['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $product['name'], 'sku' => $product['sku'], 'description' => $product['summary'], 'image' => array_map('site_url', $product['images'])];
}
function breadcrumb_schema(array $items): array {
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_map(
        fn (array $item, int $index): array => ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $item['name'], 'item' => site_url($item['url'])],
        $items,
        array_keys($items),
    )];
}
function sitemap_urls(): array {
    $urls = [site_url('/'), site_url('/privacy/')];
    foreach (catalog_categories() as $category) $urls[] = site_url("/catalog/{$category['slug']}/");
    foreach (catalog_products() as $product) $urls[] = site_url("/product/{$product['slug']}/");
    return $urls;
}
```

```apache
# .htaccess
Options -Indexes
RewriteEngine On
RewriteRule ^(?:config|data|src|templates|tests|storage)(?:/|$) - [F,L,NC]
RewriteRule ^catalog/([a-z0-9-]+)/?$ catalog.php?slug=$1 [QSA,L,NC]
RewriteRule ^product/([a-z0-9-]+)/?$ product.php?slug=$1 [QSA,L,NC]
RewriteRule ^privacy/?$ privacy.php [L,NC]
RewriteRule ^sitemap\.xml$ sitemap.php [L,NC]
RewriteRule ^robots\.txt$ robots.php [L,NC]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . 404.php [L]
```

Create `bootstrap.php` to define `app_config()` from `config/app.php`, start a secure SameSite session, load `vendor/autoload.php` when present, and require project helpers. Mirror the Apache routes in `router.php` for `php -S`, returning static files directly and including the matching controller for every other route.

- [ ] **Step 4: Run tests and syntax checks**

Run: `php tests/php/run.php`

Run: `php -l bootstrap.php; php -l router.php; php -l sitemap.php; php -l robots.php`

Expected: all tests PASS and every syntax check reports no errors.

- [ ] **Step 5: Commit routing and SEO**

```powershell
git add .htaccess bootstrap.php router.php robots.php sitemap.php src/router.php src/seo.php tests/php/router_seo_test.php
git commit -m "feat: add clean routes and SEO outputs"
```

### Task 3: Shared rendering system and industrial design tokens

**Files:**
- Create: `src/render.php`
- Create: `templates/layout.php`
- Create: `templates/partials/header.php`
- Create: `templates/partials/footer.php`
- Create: `templates/partials/breadcrumbs.php`
- Create: `templates/partials/product-card.php`
- Create: `templates/partials/lead-form.php`
- Create: `templates/partials/modal.php`
- Create: `templates/partials/cookie-notice.php`
- Create: `templates/pages/test-fixture.php`
- Create: `assets/css/main.css`
- Create: `tests/php/render_test.php`

**Interfaces:**
- Consumes: SEO arrays, catalog entities, `site_url()`.
- Produces: `render_page(string, array): string`, `render_partial(string, array): string`, semantic shared components and CSS tokens.

- [ ] **Step 1: Write the failing render-shell test**

```php
<?php
require_once dirname(__DIR__, 2) . '/src/render.php';

test('layout renders one h1, canonical, skip link and shared controls', function (): void {
    $html = render_page('test-fixture', ['seo' => ['title' => 'Тест', 'description' => 'Описание', 'canonical' => 'https://example.ru/test/'], 'heading' => 'Страница']);
    same(1, substr_count($html, '<h1'));
    truthy(str_contains($html, 'rel="canonical"'));
    truthy(str_contains($html, 'class="skip-link"'));
    truthy(str_contains($html, 'aria-expanded="false"'));
    truthy(str_contains($html, 'data-cookie-notice'));
});
```

- [ ] **Step 2: Run the test and verify red**

Run: `php tests/php/run.php`

Expected: FAIL because the renderer and templates do not exist.

- [ ] **Step 3: Implement rendering and tokens**

```php
<?php
// src/render.php
function render_partial(string $name, array $data = []): string {
    extract($data, EXTR_SKIP);
    ob_start();
    require dirname(__DIR__) . "/templates/partials/{$name}.php";
    return (string) ob_get_clean();
}
function render_page(string $view, array $data): string {
    extract($data, EXTR_SKIP);
    $viewFile = dirname(__DIR__) . "/templates/pages/{$view}.php";
    ob_start(); require $viewFile; $content = (string) ob_get_clean();
    ob_start(); require dirname(__DIR__) . '/templates/layout.php'; return (string) ob_get_clean();
}
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
```

```php
<?php
// templates/pages/test-fixture.php
?><section class="section"><div class="container"><h1><?= e($heading) ?></h1></div></section>
```

```css
/* assets/css/main.css */
:root {
  --color-bg: #f5f6f7; --color-surface: #fff; --color-ink: #15181b;
  --color-muted: #66707a; --color-line: #dfe3e6; --color-dark: #171b1e;
  --color-accent: #d91e18; --color-accent-strong: #b81713;
  --radius-sm: .5rem; --radius-md: 1rem; --shadow-card: 0 1rem 3rem rgb(18 24 29 / .08);
  --container: 80rem; --space-section: clamp(4rem, 8vw, 7.5rem);
  --font-display: "Arial Narrow", "Roboto Condensed", Arial, sans-serif;
  --font-body: Inter, Arial, sans-serif;
}
*, *::before, *::after { box-sizing: border-box; }
html { scroll-behavior: smooth; }
body { margin: 0; color: var(--color-ink); background: var(--color-bg); font-family: var(--font-body); line-height: 1.6; }
img { display: block; max-width: 100%; height: auto; }
a, button, input, textarea { font: inherit; }
:focus-visible { outline: .1875rem solid var(--color-accent); outline-offset: .1875rem; }
.container { width: min(calc(100% - 2rem), var(--container)); margin-inline: auto; }
.section { padding-block: var(--space-section); }
.button { min-height: 3rem; display: inline-flex; align-items: center; justify-content: center; gap: .65rem; border: 0; border-radius: var(--radius-sm); padding: .8rem 1.25rem; font-weight: 800; text-decoration: none; cursor: pointer; }
.button--primary { color: #fff; background: var(--color-accent); }
.button--primary:hover { background: var(--color-accent-strong); }
@media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; animation-duration: .01ms !important; } }
@media print { .site-header, .site-footer, .lead-form, [data-modal], [data-cookie-notice], .button { display: none !important; } }
```

Build the layout with one skip link, header/nav, `<main id="main">`, footer, modal, cookie notice, canonical/meta/OG fields, optional JSON-LD scripts, deferred local CSS/JS, and no external CDN. Build partials with escaped content, explicit image dimensions, lazy loading outside hero, labels, error containers, and `aria-live="polite"` for form feedback.

- [ ] **Step 4: Run render and syntax tests**

Run: `php tests/php/run.php`

Run: `php -l src/render.php`

Expected: render test PASS and no PHP syntax errors.

- [ ] **Step 5: Commit the shared UI foundation**

```powershell
git add src/render.php templates assets/css/main.css tests/php/render_test.php
git commit -m "feat: add shared industrial UI system"
```

### Task 4: Home, category, product, privacy and 404 pages

**Files:**
- Create: `index.php`
- Create: `catalog.php`
- Create: `product.php`
- Create: `privacy.php`
- Create: `404.php`
- Create: `templates/pages/home.php`
- Create: `templates/pages/category.php`
- Create: `templates/pages/product.php`
- Create: `templates/pages/privacy.php`
- Create: `templates/pages/404.php`
- Create: `tests/php/pages_test.php`

**Interfaces:**
- Consumes: all catalog, route, SEO and render helpers.
- Produces: all public HTML page types and HTTP 404 behavior.

- [ ] **Step 1: Write failing page smoke tests**

```php
<?php
test('home contains required first-stage sections', function (): void {
    $html = render_page('home', ['seo' => seo_for_page('home'), 'categories' => catalog_categories()]);
    foreach (['Главный экран', 'Каталог техники', 'Почему выбирают нас', 'Нам доверяют', 'Получить предложение'] as $label) truthy(str_contains($html, $label));
});

test('category and product pages render their catalog entities', function (): void {
    $category = find_category('zagruzchiki-suhih-kormov');
    $categoryHtml = render_page('category', ['seo' => seo_for_page('category', $category), 'category' => $category, 'products' => array_values(array_filter(catalog_products(), fn ($p) => $p['category'] === $category['slug']))]);
    truthy(str_contains($categoryHtml, 'ЗСК-10'));
    $product = find_product('zsk-10');
    $productHtml = render_page('product', ['seo' => seo_for_page('product', $product), 'product' => $product, 'related' => related_products($product)]);
    truthy(str_contains($productHtml, 'Объём бункера'));
    truthy(str_contains($productHtml, 'data-gallery'));
});
```

- [ ] **Step 2: Run tests and verify red**

Run: `php tests/php/run.php`

Expected: FAIL because public page templates are absent.

- [ ] **Step 3: Implement the page templates and controllers**

Each controller requires `bootstrap.php`, resolves its entity, sends HTTP 404 when missing, builds page-specific SEO and schemas, and echoes `render_page()`.

```php
<?php
// catalog.php
require __DIR__ . '/bootstrap.php';
$slug = (string) ($_GET['slug'] ?? '');
$category = find_category($slug);
if (!$category) { http_response_code(404); echo render_page('404', ['seo' => seo_for_page('404')]); exit; }
$products = array_values(array_filter(catalog_products(), fn (array $product): bool => $product['category'] === $slug));
echo render_page('category', ['seo' => seo_for_page('category', $category), 'category' => $category, 'products' => $products]);
```

Implement the approved structures: home hero/benefits/categories/trust/lead section; category hero/breadcrumb/count/view toggle/cards; product gallery/summary/actions/benefits/tabs/specifications/equipment/related items; readable privacy sections; and a 404 recovery panel. Extend `main.css` mobile-first, then add layout changes at 48rem and 64rem with fluid spacing and no horizontal overflow.

- [ ] **Step 4: Run page tests and render every route locally**

Run: `php tests/php/run.php`

Run in a separate terminal: `php -S 127.0.0.1:8080 router.php`

Check: `/`, `/catalog/zagruzchiki-suhih-kormov/`, `/product/zsk-10/`, `/privacy/`, and `/missing/` return the intended page and status.

- [ ] **Step 5: Commit all page types**

```powershell
git add index.php catalog.php product.php privacy.php 404.php templates/pages assets/css/main.css tests/php/pages_test.php
git commit -m "feat: build catalog page templates"
```

### Task 5: JavaScript interactions and phone mask

**Files:**
- Create: `package.json`
- Create: `assets/js/phone-mask.js`
- Create: `assets/js/catalog-view.js`
- Create: `assets/js/consent.js`
- Create: `assets/js/site.js`
- Create: `tests/js/phone-mask.test.mjs`
- Create: `tests/js/preferences.test.mjs`

**Interfaces:**
- Produces: `digitsFromRussianPhone(string): string`, `formatRussianPhone(string): string`, `isCompleteRussianPhone(string): boolean`, `readCatalogView(Storage): string`, `writeCatalogView(Storage, string): string`, `hasCookieConsent(Storage, number): boolean`, `acceptCookieConsent(Storage, number): void`.
- Consumes: data attributes emitted by shared templates.

- [ ] **Step 1: Write failing pure JavaScript tests**

```js
// tests/js/phone-mask.test.mjs
import test from 'node:test';
import assert from 'node:assert/strict';
import { digitsFromRussianPhone, formatRussianPhone, isCompleteRussianPhone } from '../../assets/js/phone-mask.js';

test('normalizes 8, 7 and ten-digit Russian phone input', () => {
  assert.equal(digitsFromRussianPhone('8 999 123-45-67'), '79991234567');
  assert.equal(digitsFromRussianPhone('9991234567'), '79991234567');
});
test('formats partial input without fake digits', () => {
  assert.equal(formatRussianPhone('999'), '+7 (999)');
  assert.equal(formatRussianPhone('7999123'), '+7 (999) 123');
  assert.equal(formatRussianPhone('89991234567'), '+7 (999) 123-45-67');
  assert.equal(isCompleteRussianPhone('+7 (999) 123-45-67'), true);
});
```

```js
// tests/js/preferences.test.mjs
import test from 'node:test';
import assert from 'node:assert/strict';
import { readCatalogView, writeCatalogView } from '../../assets/js/catalog-view.js';
import { hasCookieConsent, acceptCookieConsent } from '../../assets/js/consent.js';
const memory = () => ({ data: new Map(), getItem(k) { return this.data.get(k) ?? null; }, setItem(k, v) { this.data.set(k, v); } });
test('catalog view accepts only grid or list', () => { const s = memory(); assert.equal(writeCatalogView(s, 'list'), 'list'); assert.equal(readCatalogView(s), 'list'); assert.equal(writeCatalogView(s, 'x'), 'grid'); });
test('consent expires after configured timestamp', () => { const s = memory(); acceptCookieConsent(s, 1000); assert.equal(hasCookieConsent(s, 999), true); assert.equal(hasCookieConsent(s, 1001), false); });
```

- [ ] **Step 2: Run Node tests and verify red**

Run: `node --test tests/js/*.test.mjs`

Expected: FAIL because the ES modules are missing.

- [ ] **Step 3: Implement pure helpers and DOM wiring**

```js
// assets/js/phone-mask.js
export function digitsFromRussianPhone(value) {
  let digits = String(value).replace(/\D/g, '').slice(0, 11);
  if (digits.startsWith('8')) digits = `7${digits.slice(1)}`;
  else if (digits && !digits.startsWith('7')) digits = `7${digits}`.slice(0, 11);
  return digits;
}
export function formatRussianPhone(value) {
  const local = digitsFromRussianPhone(value).slice(1);
  if (!local) return '+7';
  let result = `+7 (${local.slice(0, 3)}`;
  if (local.length >= 3) result += ')';
  if (local.length > 3) result += ` ${local.slice(3, 6)}`;
  if (local.length > 6) result += `-${local.slice(6, 8)}`;
  if (local.length > 8) result += `-${local.slice(8, 10)}`;
  return result;
}
export const isCompleteRussianPhone = value => digitsFromRussianPhone(value).length === 11;
```

Implement storage helpers with guarded `try/catch` for privacy modes. Wire the page from a single module entry point:

```js
// assets/js/site.js
import { formatRussianPhone } from './phone-mask.js';
import { readCatalogView, writeCatalogView } from './catalog-view.js';
import { hasCookieConsent, acceptCookieConsent } from './consent.js';

const on = (root, event, selector, handler) => root.addEventListener(event, e => {
  const target = e.target.closest(selector);
  if (target) handler(e, target);
});

function initPhones() {
  document.querySelectorAll('[data-phone]').forEach(input => input.addEventListener('input', () => { input.value = formatRussianPhone(input.value); }));
}
function initCatalogView() {
  const catalog = document.querySelector('[data-catalog]');
  if (!catalog) return;
  catalog.dataset.view = readCatalogView(localStorage);
  on(document, 'click', '[data-view]', (_, button) => { catalog.dataset.view = writeCatalogView(localStorage, button.dataset.view); });
}
function initConsent() {
  const notice = document.querySelector('[data-cookie-notice]');
  if (!notice || hasCookieConsent(localStorage, Date.now())) return;
  notice.hidden = false;
  on(notice, 'click', '[data-cookie-accept]', () => { acceptCookieConsent(localStorage, Date.now() + 31_536_000_000); notice.hidden = true; });
}
initPhones(); initCatalogView(); initConsent();
```

Extend this entry point with accessible mobile navigation, a focus trap and Escape handling, modal open/close and focus restoration, gallery thumbnails, tabs with ARIA keyboard behavior, fetch-based form submission with non-JavaScript POST fallback, and `window.print()`.

- [ ] **Step 4: Run JavaScript and PHP regression tests**

Run: `node --test tests/js/*.test.mjs`

Run: `php tests/php/run.php`

Expected: all JavaScript and PHP tests PASS.

- [ ] **Step 5: Commit interactions**

```powershell
git add package.json assets/js tests/js templates assets/css/main.css
git commit -m "feat: add accessible catalog interactions"
```

### Task 6: Secure form validation and SMTP delivery

**Files:**
- Create: `config/mail.example.php`
- Create: `src/form.php`
- Create: `src/mailer.php`
- Create: `submit.php`
- Create: `storage/.gitkeep`
- Create: `tests/php/form_test.php`
- Modify: `composer.json`
- Modify: `bootstrap.php`

**Interfaces:**
- Produces: `normalize_lead(array): array`, `validate_lead(array, array): array`, `issue_csrf_token(): string`, `verify_csrf_token(string): bool`, `MailTransport::send(array): void`, `PhpMailerTransport`.
- Consumes: `PHPMailer\PHPMailer\PHPMailer` and secret `config/mail.php` matching the example shape.

- [ ] **Step 1: Write failing form-domain tests**

```php
<?php
require_once dirname(__DIR__, 2) . '/src/form.php';

test('lead validation accepts normalized Russian contact data', function (): void {
    $lead = normalize_lead(['name' => ' Анна ', 'phone' => '8 999 123-45-67', 'email' => 'ANNA@example.ru', 'message' => 'Нужен расчёт', 'website' => '', 'started_at' => '100']);
    same('Анна', $lead['name']); same('+79991234567', $lead['phone']); same('anna@example.ru', $lead['email']);
    same([], validate_lead($lead, ['now' => 105, 'csrf_valid' => true]));
});

test('lead validation rejects spam and malformed values', function (): void {
    $lead = normalize_lead(['name' => '', 'phone' => '123', 'email' => 'bad', 'message' => str_repeat('x', 3001), 'website' => 'bot', 'started_at' => '100']);
    $errors = validate_lead($lead, ['now' => 101, 'csrf_valid' => false]);
    foreach (['name', 'phone', 'email', 'message', 'spam', 'csrf'] as $key) truthy(isset($errors[$key]));
});
```

- [ ] **Step 2: Run PHP tests and verify red**

Run: `php tests/php/run.php`

Expected: FAIL because form functions do not exist.

- [ ] **Step 3: Implement validation, transport boundary and endpoint**

```php
<?php
// src/form.php
function normalize_lead(array $input): array {
    $digits = preg_replace('/\D+/', '', (string) ($input['phone'] ?? ''));
    if (strlen($digits) === 10) $digits = '7' . $digits;
    if (strlen($digits) === 11 && $digits[0] === '8') $digits[0] = '7';
    return [
        'name' => trim(strip_tags((string) ($input['name'] ?? ''))),
        'phone' => $digits ? '+' . $digits : '',
        'email' => strtolower(trim((string) ($input['email'] ?? ''))),
        'message' => trim(strip_tags((string) ($input['message'] ?? ''))),
        'website' => trim((string) ($input['website'] ?? '')),
        'started_at' => (int) ($input['started_at'] ?? 0),
        'source' => trim((string) ($input['source'] ?? '/')),
    ];
}
function validate_lead(array $lead, array $context): array {
    $errors = [];
    if (mb_strlen($lead['name']) < 2 || mb_strlen($lead['name']) > 80) $errors['name'] = 'Укажите имя.';
    if (!preg_match('/^\+7\d{10}$/', $lead['phone'])) $errors['phone'] = 'Укажите российский номер телефона.';
    if ($lead['email'] !== '' && !filter_var($lead['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Проверьте email.';
    if (mb_strlen($lead['message']) > 3000) $errors['message'] = 'Сообщение слишком длинное.';
    if ($lead['website'] !== '' || ($context['now'] - $lead['started_at']) < 3) $errors['spam'] = 'Запрос отклонён.';
    if (!$context['csrf_valid']) $errors['csrf'] = 'Обновите страницу и повторите отправку.';
    return $errors;
}
```

```php
<?php
// src/mailer.php
use PHPMailer\PHPMailer\PHPMailer;

interface MailTransport { public function send(array $lead): void; }
final class PhpMailerTransport implements MailTransport {
    public function __construct(private array $config) {}
    public function send(array $lead): void {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8'; $mail->isSMTP();
        $mail->Host = $this->config['host']; $mail->Port = $this->config['port'];
        $mail->SMTPAuth = true; $mail->Username = $this->config['username']; $mail->Password = $this->config['password'];
        $mail->SMTPSecure = $this->config['encryption'];
        $mail->setFrom($this->config['from_email'], $this->config['from_name']);
        $mail->addAddress($this->config['to_email']);
        if ($lead['email'] !== '') $mail->addReplyTo($lead['email'], $lead['name']);
        $mail->Subject = 'Новая заявка с сайта';
        $mail->Body = build_lead_html($lead); $mail->AltBody = build_lead_text($lead); $mail->isHTML(true);
        $mail->send();
    }
}
```

Make `submit.php` POST-only, return JSON for fetch requests and a safe redirect status for regular posts, log exception class/message without secrets, and never expose PHPMailer debug output.

Create `config/mail.example.php` with keys `host`, `port`, `encryption`, `username`, `password`, `from_email`, `from_name`, and `to_email`. Download Composer's stable PHAR into ignored `tools/composer.phar`, run `php tools/composer.phar install`, retain `composer.lock`, and package the generated `vendor` directory for hosting. PHPMailer 7 is compatible with PHP 8.5 and supports authenticated SMTP according to its official README.

- [ ] **Step 4: Verify validation and endpoint syntax**

Run: `php tests/php/run.php`

Run: `php -l src/form.php; php -l src/mailer.php; php -l submit.php`

Expected: all tests PASS and syntax checks report no errors. A live SMTP test is deferred until the client supplies real credentials and recipient address.

- [ ] **Step 5: Commit form delivery code without secrets**

```powershell
git add composer.json composer.lock config/mail.example.php src/form.php src/mailer.php submit.php storage/.gitkeep tests/php/form_test.php bootstrap.php
git commit -m "feat: add secure SMTP lead delivery"
```

### Task 7: Generate and optimize demonstration imagery

**Files:**
- Create: `assets/images/hero-industrial-loader.webp`
- Create: `assets/images/category-feed.webp`
- Create: `assets/images/product-zsk-10-1.webp`
- Create: `assets/images/product-zsk-10-2.webp`
- Create: `assets/images/product-zsk-7-1.webp`
- Create: `assets/images/client-mark-01.svg` through `client-mark-05.svg`
- Modify: `data/catalog.php`
- Modify: `templates/pages/home.php`

**Interfaces:**
- Consumes: approved visual direction and image paths in catalog data.
- Produces: local, brand-neutral, responsive image assets with explicit dimensions and meaningful alt text.

- [ ] **Step 1: Add an asset integrity test and verify red**

```php
<?php
test('all catalog images exist and are non-empty', function (): void {
    foreach (catalog_products() as $product) foreach ($product['images'] as $path) {
        $file = dirname(__DIR__, 2) . $path;
        truthy(is_file($file)); truthy(filesize($file) > 1000);
    }
});
```

Run: `php tests/php/run.php`

Expected: FAIL because demonstration images have not been created.

- [ ] **Step 2: Generate the original hero asset**

Use the `imagegen` skill to create a photorealistic, brand-neutral red dry-feed loader truck outside a modern industrial building, wide three-quarter view, generous bright negative space on the left for Russian heading copy, no text, no logos, no watermarks, realistic commercial product-photography lighting.

- [ ] **Step 3: Create supporting assets and web variants**

Use the `imagemagick-conversion` workflow to auto-orient, strip metadata, resize, and encode the hero and demonstration product crops as WebP. Target approximately 1920×1100 for the hero, 1200×900 for product images, and visually lossless quality at a practical web file size. Create five abstract monochrome SVG client marks that cannot be mistaken for real companies.

- [ ] **Step 4: Run asset tests and inspect the images**

Run: `php tests/php/run.php`

Use local image inspection for each raster output and verify there are no accidental logos, malformed machinery, unreadable generated text, or awkward responsive crops.

- [ ] **Step 5: Commit the optimized visual assets**

```powershell
git add assets/images data/catalog.php templates/pages/home.php tests/php
git commit -m "feat: add original industrial imagery"
```

### Task 8: Full verification and deployment handoff

**Files:**
- Create: `docs/deployment.md`
- Modify: `config/app.php`
- Modify: `config/mail.example.php`
- Modify: any file required to resolve verification defects.

**Interfaces:**
- Consumes: complete first-stage site.
- Produces: verified deployment instructions and clean release state.

- [ ] **Step 1: Write deployment instructions with exact hosting actions**

Document PHP 8.1+ selection, upload destination, Apache rewrite requirement, `composer install --no-dev --classmap-authoritative` before packaging, creation of `config/mail.php`, SMTP fields, base domain change, writable `storage` directory, HTTPS redirect, test-email procedure, and the files/content that the client will replace later.

- [ ] **Step 2: Run the full automated suite**

```powershell
php tests/php/run.php
node --test tests/js/*.test.mjs
$phpFiles = rg --files -g '*.php'
foreach ($phpFile in $phpFiles) { php -l $phpFile }
```

Expected: zero failed PHP tests, zero failed JavaScript tests, and no PHP syntax errors.

- [ ] **Step 3: Start the local server and perform browser QA**

Run: `php -S 127.0.0.1:8080 router.php`

Inspect widths 360, 390, 768, 1024, 1440, and 1920. Verify clean routes, HTTP 404, header, burger focus behavior, hero crop, catalog grid/list persistence, gallery, tabs, modal focus restoration, phone paste/delete behavior, form client errors, server error state without SMTP secrets, cookie persistence, privacy link, print view, keyboard navigation, visible focus, reduced motion, and absence of horizontal scrolling.

- [ ] **Step 4: Validate SEO output and internal links**

Inspect rendered source for one `h1`, unique titles/descriptions, canonical URL, OG tags, JSON-LD, explicit image dimensions, lazy loading outside hero, valid `robots.txt`, valid sitemap XML, no 404 URL in sitemap, and no broken internal href/src paths.

- [ ] **Step 5: Re-run the full suite after QA fixes and commit**

Run the commands from Step 2 again and require fresh clean output.

```powershell
git add docs/deployment.md config assets src templates tests *.php .htaccess composer.json composer.lock package.json
git commit -m "docs: add deployment and verification guide"
git status --short --branch
```

Expected: verification commands exit 0 and Git shows only intentionally untracked local Serena metadata.
