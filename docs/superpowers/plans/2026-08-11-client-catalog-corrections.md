# ОКСМА Client Catalog Corrections Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Исправить изображения и обозначения моделей по клиентскому документу, добавить круглый знак ОКСМА рядом с надписью в шапке и синхронизировать основную и статическую версии сайта.

**Architecture:** Каталог остаётся источником истины для названий, характеристик, URL и SEO. Ошибочные назначения фотографий исправляются в данных или в семантически именованных WebP-файлах; предоставленный PNG конвертируется без обрезки. Шапка получает два независимых фирменных изображения внутри одной доступной ссылки, а Vercel-демо пересобирается штатным экспортёром.

**Tech Stack:** PHP 8.1, HTML, CSS, JavaScript, PHP test harness, Python validation scripts, ImageMagick, WebP, static Vercel export.

## Global Constraints

- Оригинал `C:/Users/bahti/Downloads/ПЗК 15.png` не изменяется.
- Предоставленное фото сохраняет полный кадр 717×534; `-crop`, `-trim` и генеративная обработка запрещены.
- Синий поднятый полуприцеп относится к ППТС-12, красный двухосный — к ППТС-20.
- Прицепное шасси обозначается ПЗК, автомобильное — ЗСК.
- Активные изображения не должны содержать логотип, сайт или телефон «Аликом».
- URL, характеристики и SEO ППТС-12/ППТС-20 сохраняются; меняются только ошибочно назначенные фотографии.
- `.playwright-mcp/` и `ui-mobile-footer.png` являются пользовательскими неотслеживаемыми файлами и не изменяются.

---

### Task 1: Исправить изображения каталога и классификацию ЗСК/ПЗК

**Files:**
- Modify: `tests/php/catalog_test.php`
- Modify: `tests/php/assets_test.php`
- Modify: `data/catalog.php`
- Modify: `data/imported-products.php`
- Create: `assets/images/category-feed-clean.webp`
- Create: `assets/images/products/pzk/pzk-15-1.webp`
- Modify binary: `assets/images/products/ppts/ppts-12-1.webp`
- Modify binary: `assets/images/products/ppts/ppts-20-1.webp`
- Delete after reference check: `assets/images/products/pzk/pzk-10-1.webp`
- Delete after reference check: `assets/images/products/zsk/zsk-15-1.webp`

**Interfaces:**
- Consumes: `C:/Users/bahti/Downloads/ПЗК 15.png` and existing clean WebP files `product-pzk-10-1.webp`, `product-zsk-10-1.webp`, `product-zsk-15-1.webp`.
- Produces: corrected `catalog_categories()` and `find_product(string $slug)` image paths plus semantic PПТС asset contents.

- [ ] **Step 1: Add failing catalog mapping tests**

Append to `tests/php/catalog_test.php`:

```php
test('client corrections map feed equipment to approved clean photography', function (): void {
    same('/assets/images/category-feed-clean.webp', find_category('zagruzchiki-suhih-kormov')['image']);
    same('/assets/images/product-pzk-10-1.webp', find_product('pzk-10')['images'][0]);
    same('/assets/images/products/pzk/pzk-15-1.webp', find_product('pzk-15')['images'][0]);
    same('/assets/images/product-zsk-10-1.webp', find_product('zsk-10')['images'][0]);
    same('/assets/images/product-zsk-15-1.webp', find_product('zsk-15')['images'][0]);
});

test('ppts model records retain unique names urls and specifications', function (): void {
    $ppts12 = find_product('ppts-12');
    $ppts20 = find_product('ppts-20');

    same('ППТС-12', $ppts12['name']);
    same('12 000 кг', $ppts12['specs']['Грузоподъёмность']);
    same('ППТС-20', $ppts20['name']);
    same('20 000 кг', $ppts20['specs']['Грузоподъёмность']);
});
```

- [ ] **Step 2: Add failing binary asset checks**

In `tests/php/assets_test.php`, replace the old `/assets/images/product-pzk-15-1.webp` entry in `requested replacement product photography uses distinct source frames` with no PЗК-15 entry, then append:

```php
test('client supplied pzk 15 frame and corrected ppts photos are preserved', function () use ($root): void {
    $pzk15 = $root . '/assets/images/products/pzk/pzk-15-1.webp';
    truthy(is_file($pzk15));
    $size = getimagesize($pzk15);
    truthy(is_array($size));
    same([717, 534], [$size[0], $size[1]]);
    same('image/webp', $size['mime']);

    same(
        'd31d0ee29b515cdbbab7f76259f4f1b7ce584a6b7280d4262a7678c9d831e083',
        hash_file('sha256', $root . '/assets/images/products/ppts/ppts-12-1.webp')
    );
    same(
        'b5483dda120e24e5181f5a4d0c0be694d6b52bde493e4117c0b8c8b62c4647d9',
        hash_file('sha256', $root . '/assets/images/products/ppts/ppts-20-1.webp')
    );
});
```

- [ ] **Step 3: Run the PHP tests and confirm RED**

Run:

```powershell
php tests/php/run.php
```

Expected: FAIL for the old category/product paths, missing `products/pzk/pzk-15-1.webp`, and unswapped PПТС hashes.

- [ ] **Step 4: Convert the provided PЗК-15 PNG without cropping**

Run:

```powershell
magick 'C:\Users\bahti\Downloads\ПЗК 15.png' -auto-orient -strip -quality 88 -define webp:method=6 'assets\images\products\pzk\pzk-15-1.webp'
magick identify 'assets\images\products\pzk\pzk-15-1.webp'
```

Expected: `WEBP 717x534`; the source PNG remains unchanged.

- [ ] **Step 5: Create a clean full-frame feed category image**

Copy the existing people-free automobile image without re-encoding:

```powershell
Copy-Item -LiteralPath 'assets\images\product-zsk-10-1.webp' -Destination 'assets\images\category-feed-clean.webp'
magick identify 'assets\images\category-feed-clean.webp'
```

Expected: valid WebP, at least 800×600, with the complete vehicle frame.

- [ ] **Step 6: Swap the two PПТС source files without recompression**

Use a task-specific temporary directory and verify every resolved target before overwriting:

```powershell
$oksmaSwapDir = Join-Path ([System.IO.Path]::GetTempPath()) 'oksma-ppts-swap'
New-Item -ItemType Directory -Force -Path $oksmaSwapDir | Out-Null
$oksmaPpts12 = (Resolve-Path 'assets\images\products\ppts\ppts-12-1.webp').Path
$oksmaPpts20 = (Resolve-Path 'assets\images\products\ppts\ppts-20-1.webp').Path
$oksmaPpts12
$oksmaPpts20
Copy-Item -LiteralPath $oksmaPpts12 -Destination (Join-Path $oksmaSwapDir 'red-ppts-20.webp')
Copy-Item -LiteralPath $oksmaPpts20 -Destination $oksmaPpts12 -Force
Copy-Item -LiteralPath (Join-Path $oksmaSwapDir 'red-ppts-20.webp') -Destination $oksmaPpts20 -Force
```

Expected: `ppts-12-1.webp` has SHA-256 `d31d…e083` and `ppts-20-1.webp` has `b548…47d9`; dimensions and complete frames remain unchanged.

- [ ] **Step 7: Point catalog records to the approved images**

In `data/catalog.php`, change:

```php
'image' => '/assets/images/category-feed-clean.webp',
```

and for `pzk-15`:

```php
'images' => ['/assets/images/products/pzk/pzk-15-1.webp'],
```

In `data/imported-products.php`, change only the three first-image values:

```php
// pzk-10
"images" => ["/assets/images/product-pzk-10-1.webp"],

// zsk-10
"images" => ["/assets/images/product-zsk-10-1.webp"],

// zsk-15
"images" => ["/assets/images/product-zsk-15-1.webp"],
```

Do not change PПТС names, characteristics, slugs or SEO fields.

- [ ] **Step 8: Prove branded images are no longer referenced, then remove them**

Run:

```powershell
rg -n "products/(pzk/pzk-10-1|zsk/zsk-15-1)\.webp" data templates tests scripts
```

Expected: no matches. Resolve the exact files and confirm they are within the project before deletion:

```powershell
$oksmaBrandedFiles = @(
  (Resolve-Path 'assets\images\products\pzk\pzk-10-1.webp').Path,
  (Resolve-Path 'assets\images\products\zsk\zsk-15-1.webp').Path
)
$oksmaBrandedFiles
Remove-Item -LiteralPath $oksmaBrandedFiles
```

Expected: only the two explicitly listed, Git-recoverable branded files are removed.

- [ ] **Step 9: Run PHP tests and confirm GREEN**

Run:

```powershell
php tests/php/run.php
```

Expected: all PHP tests pass; no duplicate PПТС records and no missing raster assets.

- [ ] **Step 10: Commit the catalog and asset corrections**

```powershell
git add -- data/catalog.php data/imported-products.php tests/php/catalog_test.php tests/php/assets_test.php assets/images/category-feed-clean.webp assets/images/products/pzk/pzk-15-1.webp assets/images/products/ppts/ppts-12-1.webp assets/images/products/ppts/ppts-20-1.webp assets/images/products/pzk/pzk-10-1.webp assets/images/products/zsk/zsk-15-1.webp
git commit -m "fix: correct catalog model photography"
```

---

### Task 2: Add the round ОКСМА mark beside the header wordmark

**Files:**
- Modify: `tests/php/render_test.php`
- Modify: `templates/partials/header.php`
- Modify: `assets/css/main.css`

**Interfaces:**
- Consumes: existing `/assets/images/logo-oksma-footer-gold.webp` and `/assets/images/logo-oksma-header-gold.webp`.
- Produces: `.brand__mark` and `.brand__wordmark` children inside `.brand--header-gold`.

- [ ] **Step 1: Add failing header structure and responsive CSS tests**

Append to `tests/php/render_test.php`:

```php
test('header pairs the round oksma mark with the gold wordmark', function (): void {
    $header = (string) file_get_contents(dirname(__DIR__, 2) . '/templates/partials/header.php');
    $css = (string) file_get_contents(dirname(__DIR__, 2) . '/assets/css/main.css');

    truthy(str_contains($header, 'class="brand__mark"'));
    truthy(str_contains($header, 'logo-oksma-footer-gold.webp'));
    truthy(str_contains($header, 'class="brand__wordmark"'));
    truthy(str_contains($header, 'logo-oksma-header-gold.webp'));
    truthy((bool) preg_match('/\.brand--header-gold\s*\{[^}]*gap:/s', $css));
    truthy((bool) preg_match('/\.brand--header-gold \.brand__mark\s*\{[^}]*aspect-ratio:\s*1/s', $css));
    truthy((bool) preg_match('/\.brand--header-gold \.brand__wordmark\s*\{[^}]*clamp\(/s', $css));
});
```

- [ ] **Step 2: Run the PHP tests and confirm RED**

Run:

```powershell
php tests/php/run.php
```

Expected: FAIL because `.brand__mark` and `.brand__wordmark` are absent.

- [ ] **Step 3: Render both logo assets in one accessible link**

Replace the current brand link in `templates/partials/header.php` with:

```php
<a class="brand brand--header-gold" href="/" aria-label="ОКСМА, на главную">
  <img class="brand__mark" src="/assets/images/logo-oksma-footer-gold.webp" width="220" height="220" alt="">
  <img class="brand__wordmark" src="/assets/images/logo-oksma-header-gold.webp" width="420" height="76" alt="">
</a>
```

The empty image `alt` values are intentional because the link already has the complete accessible name.

- [ ] **Step 4: Add compact responsive dimensions**

Replace the old `.brand--header-gold img` rule in `assets/css/main.css` with:

```css
.brand--header-gold { min-width: 0; gap: clamp(var(--space-2), 1vw, var(--space-3)); }
.brand--header-gold .brand__mark { width: clamp(2rem, 4vw, 2.75rem); aspect-ratio: 1; object-fit: contain; }
.brand--header-gold .brand__wordmark { width: clamp(6.75rem, 13vw, 11.25rem); }
```

Keep `.brand--footer-mark img` unchanged.

- [ ] **Step 5: Run PHP tests and confirm GREEN**

Run:

```powershell
php tests/php/run.php
```

Expected: all PHP tests pass.

- [ ] **Step 6: Commit the header change**

```powershell
git add -- templates/partials/header.php assets/css/main.css tests/php/render_test.php
git commit -m "feat: add oksma mark to header"
```

---

### Task 3: Rebuild the static demo and verify every correction

**Files:**
- Regenerate: `vercel-demo/**`
- Modify only if QA reveals a defect: affected source or test files from Tasks 1–2.

**Interfaces:**
- Consumes: corrected PHP source, CSS and WebP assets.
- Produces: matching PHP-hosted site and Vercel static demo.

- [ ] **Step 1: Rebuild the static Vercel demo**

Run:

```powershell
php scripts/export-vercel-demo.php
```

Expected: successful export with regenerated catalog, product, CSS and image files.

- [ ] **Step 2: Confirm the demo contains the corrected assets**

Run:

```powershell
rg -n "brand__mark|category-feed-clean|products/pzk/pzk-15-1" vercel-demo
```

Expected: matches in rendered HTML/CSS; no rendered page references `products/pzk/pzk-10-1.webp` or `products/zsk/zsk-15-1.webp`.

- [ ] **Step 3: Run the complete automated regression suite**

Run:

```powershell
php tests/php/run.php
node --test tests/js/*.test.mjs
python scripts/validate_tokens.py
python scripts/validate_contrast.py
git diff --check
```

Expected: every test passes, both validators exit 0 and `git diff --check` reports no whitespace errors.

- [ ] **Step 4: Verify the live site on desktop and mobile**

Open the existing local site at `http://127.0.0.1:8765/` and check at 1440×1000 and 390×844:

1. The round mark sits left of «ОКСМА» without header overflow.
2. The feed category background contains no people.
3. `/product/pzk-15/` shows the complete supplied 717×534 frame.
4. `/product/ppts-12/` shows the blue raised trailer.
5. `/product/ppts-20/` shows the red two-axle trailer.
6. `/product/pzk-10/`, `/product/zsk-10/` and `/product/zsk-15/` show the correct trailer/automobile class and no «Аликом» branding.
7. Mobile navigation still opens smoothly and the page has no horizontal scroll.

- [ ] **Step 5: Commit the regenerated demo and any verified corrections**

```powershell
git add -- vercel-demo
git commit -m "build: refresh corrected catalog demo"
```

- [ ] **Step 6: Confirm final repository state**

Run:

```powershell
git status --short
git log -4 --oneline
```

Expected: only the pre-existing user-owned `.playwright-mcp/` and `ui-mobile-footer.png` remain untracked; the latest commits contain the catalog, header and demo changes.
