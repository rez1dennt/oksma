# Product Benefits Cards Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the full-width product-benefits strip with three responsive, accessible light cards while preserving product data, printing, and all existing page behavior.

**Architecture:** Catalog data remains the source of benefit titles and is normalized to exactly three entries per product. A pure catalog helper maps titles to card presentation metadata, a dedicated partial owns semantic markup, and the product stylesheet owns layout and interaction states. Existing SVG and design-token systems are reused without dependencies.

**Tech Stack:** PHP 8+, server-rendered templates, CSS custom properties, inline SVG helper, the existing PHP test harness, Node test runner, Python `unittest` import suite.

## Global Constraints

- Do not change gallery, specifications, equipment, documents, lead form, routes, or SEO schema.
- Keep the existing `data-print` button and `window.print()` behavior.
- Render exactly three benefit cards for each of the current 26 products.
- Use only existing semantic CSS tokens and SVG icons from `src/render.php`.
- Desktop and tablet use three columns; viewports below `48em` use one column.
- Keep `.product-benefits` hidden in `@media print`.
- Preserve reduced-motion behavior through the existing global media query.

---

### Task 1: Normalize benefits and produce card metadata

**Files:**
- Modify: `tests/php/catalog_test.php`
- Modify: `data/catalog.php`
- Modify: `src/catalog.php`

**Interfaces:**
- Consumes: product arrays with `benefits: list<string>`.
- Produces: `product_benefit_cards(array $benefits): array<int, array{index: string, title: string, description: string, icon: string}>`.
- Enforces: every published product has exactly three unique non-empty benefit titles.

- [ ] **Step 1: Write failing catalog tests**

Append to `tests/php/catalog_test.php`:

```php
test('every product exposes exactly three benefits', function (): void {
    foreach (catalog_products() as $product) {
        same(3, count($product['benefits'] ?? []));
        same(3, count(array_unique($product['benefits'] ?? [])));
    }
});

test('benefit cards map copy to the shared icon system', function (): void {
    $cards = product_benefit_cards([
        'Самозагрузка и перемешивание',
        'Комплектация под задачу',
        'Доставка по России',
    ]);

    same(['01', '02', '03'], array_column($cards, 'index'));
    same(['truck', 'wrench', 'truck'], array_column($cards, 'icon'));
    same('Самозагрузка и перемешивание', $cards[0]['title']);
    truthy(str_contains($cards[1]['description'], 'условия работы'));
    truthy(str_contains($cards[2]['description'], 'регион России'));
});
```

- [ ] **Step 2: Run the PHP suite and verify RED**

Run:

```powershell
php tests\php\run.php
```

Expected: failures because four products expose one benefit and `product_benefit_cards()` does not exist.

- [ ] **Step 3: Normalize the four incomplete product arrays**

In `data/catalog.php`, replace the single-item `benefits` arrays for `zsk-12`, `zsk-20`, `zsk-21`, and `pzk-15` with their product-specific title followed by the two verified company-wide benefits:

```php
'benefits' => ['Изготовление под задачу', 'Доставка по России', 'Сервисная поддержка'],
```

```php
'benefits' => ['Увеличенный объём', 'Доставка по России', 'Сервисная поддержка'],
```

```php
'benefits' => ['Проектная комплектация', 'Доставка по России', 'Сервисная поддержка'],
```

```php
'benefits' => ['Вместительный бункер', 'Доставка по России', 'Сервисная поддержка'],
```

- [ ] **Step 4: Implement the pure metadata mapper**

Add to `src/catalog.php` after `related_products()`:

```php
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
```

- [ ] **Step 5: Extend integrity validation**

Inside the product loop in `catalog_integrity_errors()`, add:

```php
$benefits = array_values(array_filter(
    $product['benefits'] ?? [],
    static fn (mixed $benefit): bool => is_string($benefit) && trim($benefit) !== ''
));
if (count($benefits) !== 3 || count(array_unique($benefits)) !== 3) {
    $errors[] = "Product {$key} must expose exactly three unique benefits";
}
```

- [ ] **Step 6: Run tests and verify GREEN**

Run:

```powershell
php tests\php\run.php
```

Expected: all PHP tests pass with `0 failure(s)`.

- [ ] **Step 7: Commit the data layer**

```powershell
git add data/catalog.php src/catalog.php tests/php/catalog_test.php
git commit -m "feat: normalize product benefit content"
```

---

### Task 2: Render an accessible benefits-card component

**Files:**
- Create: `templates/partials/product-benefits.php`
- Modify: `templates/pages/product.php`
- Modify: `tests/php/pages_test.php`

**Interfaces:**
- Consumes: partial data `['benefits' => list<string>]` and `product_benefit_cards()` from Task 1.
- Produces: one labelled `<section>`, one semantic `<ol>`, and three `.product-benefit-card` list items.

- [ ] **Step 1: Replace the legacy rendering assertion with failing component assertions**

In the existing `product renders gallery tabs specifications and related items` test in `tests/php/pages_test.php`, replace the old icon-count assertion with:

```php
same(3, substr_count($html, 'class="product-benefit-card"'));
truthy(str_contains($html, 'id="product-benefits-title"'));
truthy(str_contains($html, 'class="product-benefits__list"'));
truthy(str_contains($html, 'class="product-benefit-card__index" aria-hidden="true">01</span>'));
truthy(str_contains($html, 'Ключевые преимущества'));
truthy(str_contains($html, 'Исполнение и оснащение согласуем под ваши условия работы.'));
```

- [ ] **Step 2: Run the PHP suite and verify RED**

Run:

```powershell
php tests\php\run.php
```

Expected: the product-page test fails because the card component markup is absent.

- [ ] **Step 3: Create the partial**

Create `templates/partials/product-benefits.php`:

```php
<?php
$cards = product_benefit_cards($benefits ?? []);
?>
<section class="product-benefits" aria-labelledby="product-benefits-title">
  <div class="container">
    <header class="product-benefits__heading">
      <p class="eyebrow">Продумано для работы</p>
      <h2 id="product-benefits-title">Ключевые преимущества</h2>
    </header>
    <ol class="product-benefits__list">
      <?php foreach ($cards as $card): ?>
        <li class="product-benefit-card">
          <div class="product-benefit-card__topline">
            <span class="product-benefit-card__icon"><?= icon($card['icon']) ?></span>
            <span class="product-benefit-card__index" aria-hidden="true"><?= e($card['index']) ?></span>
          </div>
          <h3><?= e($card['title']) ?></h3>
          <p><?= e($card['description']) ?></p>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
```

- [ ] **Step 4: Replace inline legacy markup**

In `templates/pages/product.php`, replace the current `.product-benefits` section with:

```php
<?= render_partial('product-benefits', ['benefits' => $product['benefits']]) ?>
```

- [ ] **Step 5: Run tests and verify GREEN**

Run:

```powershell
php tests\php\run.php
```

Expected: all PHP tests pass with `0 failure(s)`.

- [ ] **Step 6: Commit the semantic component**

```powershell
git add templates/partials/product-benefits.php templates/pages/product.php tests/php/pages_test.php
git commit -m "feat: render product benefit cards"
```

---

### Task 3: Apply the responsive visual design

**Files:**
- Modify: `assets/css/main.css`
- Modify: `tests/php/render_test.php`

**Interfaces:**
- Consumes: class names from `templates/partials/product-benefits.php`.
- Produces: one-column mobile layout, three-column layout from `48em`, subtle pointer hover, and print suppression.

- [ ] **Step 1: Write failing CSS-contract tests**

Append to `tests/php/render_test.php`:

```php
test('product benefits use isolated cards and responsive columns', function (): void {
    $css = (string) file_get_contents(dirname(__DIR__, 2) . '/assets/css/main.css');

    truthy((bool) preg_match('/\.product-benefits\s*\{[^}]*background:\s*var\(--color-surface-page\)/s', $css));
    truthy((bool) preg_match('/\.product-benefits__list\s*\{[^}]*grid-template-columns:\s*1fr/s', $css));
    truthy((bool) preg_match('/\.product-benefit-card\s*\{[^}]*background:\s*var\(--color-surface-card\)/s', $css));
    truthy((bool) preg_match('/@media \(min-width: 48em\).*?\.product-benefits__list\s*\{[^}]*repeat\(3,/s', $css));
    truthy((bool) preg_match('/@media print.*?\.product-benefits/s', $css));
});
```

- [ ] **Step 2: Run the PHP suite and verify RED**

Run:

```powershell
php tests\php\run.php
```

Expected: the CSS-contract test fails against the legacy full-width strip.

- [ ] **Step 3: Replace the legacy benefit styles**

Replace the current `.product-benefits`, `.product-benefits__grid`, and `.product-benefit__icon` rules in `assets/css/main.css` with:

```css
.product-benefits { padding-block: 0 var(--space-section); color: var(--color-text-primary); background: var(--color-surface-page); }
.product-benefits__heading { display: grid; gap: var(--space-2); margin-block-end: var(--space-8); }
.product-benefits__heading h2 { margin: 0; font-size: clamp(var(--font-size-2xl), 4vw, var(--font-size-4xl)); }
.product-benefits__list { display: grid; grid-template-columns: 1fr; gap: var(--space-4); margin: 0; padding: 0; list-style: none; }
.product-benefit-card { position: relative; display: grid; align-content: start; gap: var(--space-4); min-width: 0; padding: clamp(var(--space-5), 3vw, var(--space-8)); background: var(--color-surface-card); border: var(--border-thin) solid color-mix(in srgb, var(--color-footer-accent) 38%, var(--color-border-default)); border-radius: var(--radius-lg); box-shadow: var(--shadow-card); transition: transform var(--duration-base) var(--ease-standard), border-color var(--duration-fast) var(--ease-standard), box-shadow var(--duration-base) var(--ease-standard); }
.product-benefit-card__topline { display: flex; align-items: flex-start; justify-content: space-between; gap: var(--space-4); }
.product-benefit-card__icon { display: grid; width: 3.75rem; height: 3.75rem; place-items: center; color: var(--color-footer-bg); background: color-mix(in srgb, var(--color-footer-accent) 20%, var(--color-surface-card)); border: var(--border-thin) solid color-mix(in srgb, var(--color-footer-accent) 48%, var(--color-border-default)); border-radius: var(--radius-md); }
.product-benefit-card__icon .icon { width: 2rem; height: 2rem; }
.product-benefit-card__index { color: var(--color-footer-accent); font-size: var(--font-size-sm); font-weight: var(--weight-bold); letter-spacing: .12em; }
.product-benefit-card h3 { margin: 0; font-size: var(--font-size-xl); }
.product-benefit-card p { margin: 0; color: var(--color-text-secondary); font-size: var(--font-size-sm); }
@media (hover: hover) and (pointer: fine) {
  .product-benefit-card:hover { border-color: color-mix(in srgb, var(--color-footer-accent) 70%, var(--color-border-default)); box-shadow: var(--shadow-raised); transform: translateY(calc(var(--space-1) * -1)); }
}
```

Inside the existing `@media (min-width: 48em)` block, replace the legacy two-column benefit grid rule with:

```css
.product-benefits__list { grid-template-columns: repeat(3, minmax(0, 1fr)); }
```

Remove the legacy `.product-benefits__grid` rule from `@media (min-width: 64em)` so that no four-column override remains.

- [ ] **Step 4: Run tests and verify GREEN**

Run:

```powershell
php tests\php\run.php
```

Expected: all PHP tests pass with `0 failure(s)`.

- [ ] **Step 5: Run token and hardcode validation**

Run:

```powershell
& 'C:\Users\bahti\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' scripts\validate_tokens.py
& 'C:\Users\bahti\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' 'C:\Users\bahti\.codex\skills\ux-ui-agent-skills\scripts\lint_hardcodes.py' assets\css\main.css
```

Expected: both commands exit `0` without token or off-theme color errors.

- [ ] **Step 6: Commit the visual layer**

```powershell
git add assets/css/main.css tests/php/render_test.php
git commit -m "style: redesign product benefit cards"
```

---

### Task 4: Verify behavior, rendering, and print safety

**Files:**
- Verify only; no production files should change.

**Interfaces:**
- Consumes: completed Tasks 1–3.
- Produces: fresh test evidence and a clean intended diff.

- [ ] **Step 1: Run all automated suites**

```powershell
php tests\php\run.php
npm run test:js
& 'C:\Users\bahti\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe' -m unittest discover -s tests\import -p 'test_*.py'
```

Expected: PHP reports `0 failure(s)`, Node reports `0 fail`, and Python reports `OK`.

- [ ] **Step 2: Smoke-test product routes**

```powershell
$paths = @('/product/pc-11v/', '/product/zsk-12/', '/product/lowbed-trailer/')
foreach ($path in $paths) {
    $response = Invoke-WebRequest -Uri ('http://127.0.0.1:8765' + $path) -UseBasicParsing -TimeoutSec 10
    if ($response.StatusCode -ne 200 -or ([regex]::Matches($response.Content, 'class="product-benefit-card"')).Count -ne 3) {
        throw "Product benefits smoke check failed for $path"
    }
}
```

Expected: command exits `0`; all three representative routes return `200` and exactly three cards.

- [ ] **Step 3: Inspect responsive rendering**

Use the in-app browser at `http://127.0.0.1:8765/product/pc-11v/` and inspect at 360, 768, and 1440 CSS pixels:

- 360: one card per row, no horizontal scroll, complete text.
- 768: three equal cards, wrapped headings, no overlap.
- 1440: three balanced cards aligned inside the main container.
- Print preview: benefits absent; product identity, image, metadata, specifications, dimensions, and description present.

- [ ] **Step 4: Check the final diff**

```powershell
git diff --check
git status --short
git diff --stat HEAD~3..HEAD
```

Expected: no whitespace errors; only planned files are committed; unrelated `.playwright-mcp/` and `ui-mobile-footer.png` remain untouched.

- [ ] **Step 5: Push the completed branch**

```powershell
git push origin main
```

Expected: `main` is updated on `https://github.com/rez1dennt/oksma.git`. If DNS resolution is unavailable, preserve the local commits and report the exact network error without claiming push success.
