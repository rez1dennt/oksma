# Vercel Static Demo Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Generate a complete static Vercel demo from the canonical PHP catalog while preserving the original site for normal PHP hosting.

**Architecture:** The PHP application remains the only editable source. A small export module enumerates every public route, renders each route in an isolated PHP CLI process, transforms forms into honest demo interactions, copies public assets, and writes a self-contained `vercel-demo/` tree. Vercel publishes only that generated directory.

**Tech Stack:** PHP 8.x CLI, existing PHP renderer/catalog, vanilla JavaScript, Vercel static hosting, existing custom PHP test harness.

## Global Constraints

- The original PHP site in the repository root remains the single source of truth.
- `vercel-demo/` is generated output and must never be edited by hand.
- Remove the empty `zapchasti` category from the canonical PHP catalog; `/catalog/zapchasti/` must resolve to 404.
- Export `/`, all 4 current category routes, all 26 product routes, `/documents/`, `/privacy/`, `/sitemap.xml`, `/robots.txt`, and `/404.html`.
- Copy only public assets and `favicon.ico`; never copy PHP source, config, data, templates, tests, tools, or credentials.
- The demo must not send form submissions or pretend that a request was delivered.
- Future changes are made in the PHP source first, followed by `php scripts/export-vercel-demo.php`.
- Preserve the existing untracked `.playwright-mcp/` and `ui-mobile-footer.png` files.

---

### Task 1: Remove the empty spare-parts category

**Files:**
- Modify: `data/catalog.php`
- Modify: `tests/php/catalog_test.php`
- Modify: `tests/php/router_seo_test.php`

**Interfaces:**
- Consumes: `catalog_categories()`, `find_category()`, `resolve_route()`, and sitemap generation.
- Produces: a four-category canonical catalog with no `zapchasti` route.

- [ ] **Step 1: Write failing catalog and routing tests**

Add assertions:

```php
test('empty spare-parts category is not public', function (): void {
    same(4, count(catalog_categories()));
    same(null, find_category('zapchasti'));
    same('not-found', resolve_route('/catalog/zapchasti/')['name']);
});
```

Extend the sitemap test to assert that `/catalog/zapchasti/` is absent.

- [ ] **Step 2: Run the PHP suite and verify RED**

Run: `php tests/php/run.php`

Expected: FAIL because the category is still present and resolves as a category.

- [ ] **Step 3: Remove only the empty category definition**

Delete the `zapchasti` entry from the `categories` array in `data/catalog.php`. Do not remove the shared category image asset because generated and historical pages may still reference it until the static export is rebuilt.

- [ ] **Step 4: Run the PHP suite and verify GREEN**

Run: `php tests/php/run.php`

Expected: all tests pass; the home catalog and navigation now receive four categories from the same canonical data source.

- [ ] **Step 5: Commit the category removal**

```powershell
git add -- data/catalog.php tests/php/catalog_test.php tests/php/router_seo_test.php
git commit -m "feat: remove empty spare-parts category"
```

---

### Task 2: Define the static export contract

**Files:**
- Create: `src/static-demo.php`
- Create: `tests/php/static_demo_test.php`

**Interfaces:**
- Consumes: `catalog_categories(): array`, `catalog_products(): array` from `src/catalog.php`.
- Produces: `static_demo_routes(): array<string,string>`, `static_demo_transform_html(string): string`, and `static_demo_validate_output(string,array): array<string>`.

- [ ] **Step 1: Write failing route and transform tests**

Add tests that require `src/static-demo.php` and assert the exact contract:

```php
test('static demo enumerates every public route', function (): void {
    $routes = static_demo_routes();
    same(36, count($routes));
    same('index.html', $routes['/']);
    same('catalog/zagruzchiki-suhih-kormov/index.html', $routes['/catalog/zagruzchiki-suhih-kormov/']);
    same('product/pc-11v/index.html', $routes['/product/pc-11v/']);
    same('404.html', $routes['/404.html']);
    same('sitemap.xml', $routes['/sitemap.xml']);
    same('robots.txt', $routes['/robots.txt']);
    truthy(!isset($routes['/catalog/zapchasti/']));
});

test('static demo transforms forms without changing the source templates', function (): void {
    $html = '<form action="/submit.php" data-lead-form></form></body>';
    $result = static_demo_transform_html($html);
    truthy(str_contains($result, 'action="#demo-form"'));
    truthy(str_contains($result, '/assets/js/demo-mode.js'));
    truthy(!str_contains($result, 'action="/submit.php"'));
});
```

- [ ] **Step 2: Run the PHP suite and verify RED**

Run: `php tests/php/run.php`

Expected: FAIL because `src/static-demo.php` and its functions do not exist.

- [ ] **Step 3: Implement the route map and HTML transform**

Create `src/static-demo.php` with:

```php
<?php

declare(strict_types=1);

function static_demo_routes(): array
{
    $routes = [
        '/' => 'index.html',
        '/documents/' => 'documents/index.html',
        '/privacy/' => 'privacy/index.html',
        '/sitemap.xml' => 'sitemap.xml',
        '/robots.txt' => 'robots.txt',
        '/404.html' => '404.html',
    ];

    foreach (catalog_categories() as $category) {
        $routes['/catalog/' . $category['slug'] . '/'] = 'catalog/' . $category['slug'] . '/index.html';
    }

    foreach (catalog_products() as $product) {
        $routes['/product/' . $product['slug'] . '/'] = 'product/' . $product['slug'] . '/index.html';
    }

    return $routes;
}

function static_demo_transform_html(string $html): string
{
    $html = str_replace('action="/submit.php"', 'action="#demo-form"', $html);
    if (!str_contains($html, '/assets/js/demo-mode.js')) {
        $html = str_replace('</body>', '  <script src="/assets/js/demo-mode.js" defer></script>' . PHP_EOL . '</body>', $html);
    }
    return $html;
}
```

Implement `static_demo_validate_output()` with checks for every mapped file, forbidden `*.php` files, demo script inclusion in HTML, removal of `/submit.php`, and resolvable root-relative `href`/`src` targets.

- [ ] **Step 4: Run the PHP suite and verify GREEN**

Run: `php tests/php/run.php`

Expected: all tests pass, including the new route and transform tests.

- [ ] **Step 5: Commit the contract**

```powershell
git add -- src/static-demo.php tests/php/static_demo_test.php
git commit -m "test: define static demo export contract"
```

---

### Task 3: Implement isolated rendering and export

**Files:**
- Create: `scripts/render-static-route.php`
- Create: `scripts/export-vercel-demo.php`
- Create: `scripts/static-demo/demo-mode.js`
- Create: `scripts/static-demo/vercel.json`
- Modify: `tests/php/static_demo_test.php`

**Interfaces:**
- Consumes: `static_demo_routes()`, `static_demo_transform_html()`, `static_demo_validate_output()`.
- Produces: `static_demo_export(string $projectRoot, string $outputRoot): array{pages:int,assets:int,errors:array}` and CLI command `php scripts/export-vercel-demo.php [output-directory]`.

- [ ] **Step 1: Write the failing integration test**

Add a test that exports into `.tmp/test-vercel-demo`, validates representative pages and assets, and always removes only that checked temporary directory:

```php
test('static demo exporter builds an isolated deployable tree', function (): void {
    $root = dirname(__DIR__, 2);
    $output = $root . '/.tmp/test-vercel-demo';
    static_demo_remove_tree($root, $output);

    try {
        $report = static_demo_export($root, $output);
        same(36, $report['pages']);
        same([], $report['errors']);
        truthy(is_file($output . '/index.html'));
        truthy(is_file($output . '/product/pc-11v/index.html'));
        truthy(is_file($output . '/assets/css/main.css'));
        truthy(is_file($output . '/assets/js/demo-mode.js'));
        truthy(is_file($output . '/vercel.json'));
        truthy(!is_file($output . '/index.php'));
    } finally {
        static_demo_remove_tree($root, $output);
    }
});
```

- [ ] **Step 2: Run the PHP suite and verify RED**

Run: `php tests/php/run.php`

Expected: FAIL because `static_demo_export()` and `static_demo_remove_tree()` do not exist.

- [ ] **Step 3: Implement the isolated route renderer**

Create `scripts/render-static-route.php`. It must set `SITE_URL` from `STATIC_DEMO_SITE_URL` with fallback `https://oksma-demo.vercel.app`, initialize `REQUEST_URI`, resolve the route, set `$_GET['slug']` when present, and require the same controller mapping used by `router.php`:

```php
$controller = match ($route['name']) {
    'home' => 'index.php',
    'category' => 'catalog.php',
    'product' => 'product.php',
    'privacy' => 'privacy.php',
    'documents' => 'documents.php',
    'sitemap' => 'sitemap.php',
    'robots' => 'robots.php',
    default => '404.php',
};
```

Each route is rendered in a new PHP process so headers, response codes, `$_GET`, and static configuration cannot leak into the next page.

- [ ] **Step 4: Implement safe filesystem helpers and exporter**

In `src/static-demo.php`, add:

```php
function static_demo_remove_tree(string $projectRoot, string $target): void
function static_demo_copy_tree(string $source, string $target): int
function static_demo_render_route(string $projectRoot, string $route): string
function static_demo_export(string $projectRoot, string $outputRoot): array
```

`static_demo_remove_tree()` must reject the project root, any target outside it, and any basename other than `vercel-demo` or `test-vercel-demo`. Use `RecursiveIteratorIterator` with child-first traversal and literal filesystem paths.

`static_demo_export()` must:

1. clear the validated output directory;
2. render all 36 routes through `scripts/render-static-route.php`;
3. transform only `.html` outputs;
4. copy `assets/` and `favicon.ico`;
5. copy the two demo templates;
6. call `static_demo_validate_output()`;
7. return page, asset, and error counts.

- [ ] **Step 5: Implement honest demo form behavior**

Create `scripts/static-demo/demo-mode.js`:

```js
document.addEventListener('submit', (event) => {
  const form = event.target instanceof HTMLFormElement && event.target.matches('[data-lead-form]')
    ? event.target
    : null;
  if (!form) return;

  event.preventDefault();
  event.stopImmediatePropagation();
  const status = form.querySelector('[data-form-status]');
  if (status) {
    status.textContent = 'Демонстрационная версия: заявка не отправляется.';
    status.dataset.state = 'success';
  }
}, true);
```

Create `scripts/static-demo/vercel.json` with `trailingSlash: true`, security headers matching `.htaccess`, and immutable cache headers for `/assets/(.*)`.

- [ ] **Step 6: Add the CLI wrapper**

`scripts/export-vercel-demo.php` must require `bootstrap.php` and `src/static-demo.php`, default output to `<project>/vercel-demo`, print `Exported 36 pages and N assets`, print each validation error to STDERR, and return exit code `1` on errors.

- [ ] **Step 7: Run the PHP suite and verify GREEN**

Run: `php tests/php/run.php`

Expected: all tests pass and `.tmp/test-vercel-demo` is removed after the test.

- [ ] **Step 8: Commit the exporter**

```powershell
git add -- src/static-demo.php scripts/render-static-route.php scripts/export-vercel-demo.php scripts/static-demo tests/php/static_demo_test.php
git commit -m "feat: add static Vercel demo exporter"
```

---

### Task 4: Generate and validate the tracked Vercel demo

**Files:**
- Create: `vercel-demo/**`
- Modify: `tests/php/static_demo_test.php`

**Interfaces:**
- Consumes: `php scripts/export-vercel-demo.php`.
- Produces: a complete static deployment tree used as the Vercel Root Directory.

- [ ] **Step 1: Add a failing repository-artifact test**

Add:

```php
test('tracked Vercel demo matches the public route contract', function (): void {
    $root = dirname(__DIR__, 2);
    $output = $root . '/vercel-demo';
    same([], static_demo_validate_output($output, static_demo_routes()));
});
```

- [ ] **Step 2: Run the PHP suite and verify RED**

Run: `php tests/php/run.php`

Expected: FAIL because `vercel-demo/` does not exist yet.

- [ ] **Step 3: Generate the real demo tree**

Run: `php scripts/export-vercel-demo.php`

Expected: `Exported 36 pages and 58 assets`, followed by `Validation passed.`

- [ ] **Step 4: Run static output validation and the full test suite**

Run:

```powershell
php tests\php\run.php
npm run test:js
& "C:\Users\bahti\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe" -m unittest discover -s tests\import -p "test_*.py"
```

Expected: PHP, JavaScript, and Python suites all pass with zero failures.

- [ ] **Step 5: Smoke-test representative generated files**

Serve the demo with `php -S 127.0.0.1:8877 -t vercel-demo` and verify:

- `/` returns 200;
- `/catalog/polupricepy/` returns 200;
- `/product/pc-11v/` returns 200;
- `/documents/` returns 200;
- `/assets/css/main.css` returns 200;
- `/assets/documents/` PDF links return 200;
- the browser console contains no errors;
- navigation, burger, gallery, tabs, cookie notice, and demo form message work at mobile and desktop widths.

- [ ] **Step 6: Commit the generated deployment tree**

```powershell
git add -- vercel-demo tests/php/static_demo_test.php
git commit -m "build: generate Vercel client demo"
```

---

### Task 5: Document publishing and deliver the GitHub update

**Files:**
- Modify: `README.md`

**Interfaces:**
- Consumes: `vercel-demo/` and `php scripts/export-vercel-demo.php`.
- Produces: exact Vercel import and future refresh instructions.

- [ ] **Step 1: Document the two-version workflow**

Add a `Vercel demo` section explaining:

```text
Canonical site: repository root (PHP hosting).
Generated preview: vercel-demo/ (Vercel Root Directory).
Refresh command: php scripts/export-vercel-demo.php.
Never edit vercel-demo manually.
```

Include dashboard settings: import `rez1dennt/oksma`, set Root Directory to `vercel-demo`, leave Framework Preset as `Other`, leave Build Command empty, leave Output Directory empty, and deploy `main`.

- [ ] **Step 2: Run fresh completion verification**

Run:

```powershell
php scripts\export-vercel-demo.php
php tests\php\run.php
npm run test:js
& "C:\Users\bahti\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe" -m unittest discover -s tests\import -p "test_*.py"
git diff --check
git status --short --branch
```

Expected: exporter validation passes; all tests pass; only intentional tracked changes and the two pre-existing untracked user files remain.

- [ ] **Step 3: Commit documentation and any deterministic regenerated output**

```powershell
git add -- README.md vercel-demo
git commit -m "docs: add Vercel demo deployment steps"
```

- [ ] **Step 4: Push `main`**

Run: `git push origin main`

Expected: GitHub `main` contains the original PHP site, exporter, and current `vercel-demo/`. Vercel can then import the repository with Root Directory `vercel-demo`.
