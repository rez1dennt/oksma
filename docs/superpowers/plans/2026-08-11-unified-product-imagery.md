# Unified Product Imagery Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Standardize the primary image of every one of the 26 catalog products on a clean 1200 × 900 white canvas, remove ALIKOM branding and unrelated markings, install the client-supplied ZSK-10 and PPTS-12 images, and remove the company requisites line from the visible footer.

**Architecture:** Preserve every currently active product image in a dated, non-public source archive. Process each primary product asset independently through the built-in image editing workflow, then use ImageMagick only for deterministic 1200 × 900 WebP normalization. Keep the existing public filenames so catalog cards, product pages, and the static Vercel demo update without catalog-data migrations.

**Tech Stack:** PHP 8 catalog/templates and tests; built-in image generation/editing; ImageMagick 7; WebP; vanilla HTML/CSS; static demo exporter.

## Global Constraints

- Process all 26 primary product images returned by `catalog_products()`.
- Final image format is WebP at exactly 1200 × 900 pixels on `#ffffff`.
- Keep the complete machine in frame, including wheels, drawbar, augers, ladders, and attachments.
- Do not change axle count, wheel count, body sections, hydraulics, or other construction details.
- Remove ALIKOM logos, manufacturer/service writing, model writing, decals, and watermarks from the image surface.
- Do not add new text, logos, watermarks, decorative objects, or a scenic background.
- Keep only a subtle neutral contact shadow when needed to avoid a floating appearance.
- Use the supplied red image for ZSK-10 and supplied blue image for PPTS-12.
- Remove `ООО «СпецТехПром», ИНН 5258079050` from the footer only; keep approved operator details on `/privacy/`.
- Preserve user-owned untracked `.playwright-mcp/` and `ui-mobile-footer.png` unchanged.

---

### Task 1: Add failing regression tests for the approved image standard

**Files:**
- Modify: `tests/php/assets_test.php`
- Modify: `tests/php/pages_test.php`

**Interfaces:**
- Consumes: `catalog_products(): array`, `render_page(string, array): string`.
- Produces: regression gates for dimensions, MIME type, footer removal, and privacy retention.

- [ ] **Step 1: Replace obsolete image hashes with the uniform-dimension test**

In `tests/php/assets_test.php`, replace the test named `client supplied pzk 15 frame and corrected ppts photos are preserved` with:

```php
test('all primary product images use the approved white catalog canvas', function () use ($root): void {
    foreach (catalog_products() as $product) {
        $path = $product['images'][0] ?? '';
        truthy($path !== '');
        $file = $root . str_replace('/', DIRECTORY_SEPARATOR, $path);
        truthy(is_file($file));
        $size = getimagesize($file);
        truthy(is_array($size));
        same([1200, 900], [$size[0], $size[1]]);
        same('image/webp', $size['mime']);
    }
});
```

- [ ] **Step 2: Add footer/privacy visibility regression**

Append to `tests/php/pages_test.php`:

```php
test('footer hides company requisites while privacy keeps operator details', function (): void {
    $home = render_page('home', [
        'seo' => seo_for_page('home'),
        'categories' => catalog_categories(),
        'partners' => partners(),
        'schemas' => [],
    ]);
    truthy(!str_contains($home, 'ООО «СпецТехПром», ИНН 5258079050'));

    $privacy = render_page('privacy', [
        'seo' => seo_for_page('privacy'),
        'schemas' => [],
    ]);
    truthy(str_contains($privacy, 'ООО «СпецТехПром»'));
    truthy(str_contains($privacy, '5258079050'));
});
```

- [ ] **Step 3: Run the focused tests to verify failure**

Run:

```powershell
php tests/php/run.php
```

Expected: the image-canvas test fails because current product assets do not all have dimensions `1200 × 900`; the footer test fails because the requisites line is still rendered.

- [ ] **Step 4: Commit the regression tests**

```powershell
git add tests/php/assets_test.php tests/php/pages_test.php
git commit -m "test: define uniform catalog image contract"
```

---

### Task 2: Preserve the existing product sources and install the two client replacements

**Files:**
- Create: `source-assets/catalog-originals/2026-08-11/**`
- Modify: `assets/images/product-zsk-10-1.webp`
- Modify: `assets/images/products/ppts/ppts-12-1.webp`

**Interfaces:**
- Consumes: the first image path of every product in `data/catalog.php` and the two supplied files in `C:\Users\bahti\Downloads`.
- Produces: a complete dated archive plus the two approved source replacements ready for image editing.

- [ ] **Step 1: Copy each of the 26 active primary images into the dated source archive**

Preserve the path below `assets/images` under `source-assets/catalog-originals/2026-08-11`. Verify that the archive contains 26 files and that their SHA-256 hashes match the pre-copy sources.

- [ ] **Step 2: Copy the client inputs into the source archive**

Copy these exact files without modification:

```text
C:\Users\bahti\Downloads\diting_result_6603362a957811f1a347d6fa4732454c_1.jpeg
  -> source-assets/catalog-originals/2026-08-11/client-zsk-10.jpeg
C:\Users\bahti\Downloads\diting_result_2b8c566c957911f18930ee389d7377be_1.png
  -> source-assets/catalog-originals/2026-08-11/client-ppts-12.png
```

- [ ] **Step 3: Inspect the archived client inputs**

Confirm the ZSK-10 source shows the red loader body on white and the PPTS-12 source shows the complete blue two-axle trailer on white. Neither source may be cropped before semantic cleanup.

- [ ] **Step 4: Commit the source archive**

```powershell
git add source-assets/catalog-originals/2026-08-11
git commit -m "assets: preserve catalog image sources"
```

---

### Task 3: Produce the 26 clean product images

**Files:**
- Modify: all 26 primary WebP paths listed below.

**Interfaces:**
- Consumes: archived originals from Task 2.
- Produces: 26 public WebP files at their existing URLs.

- [ ] **Step 1: Edit each source independently with the built-in image tool**

Use one edit call per row in this exact target list:

```text
zsk-10  assets/images/product-zsk-10-1.webp              source: client-zsk-10.jpeg
zsk-7   assets/images/products/zsk/zsk-7-1.webp          source: archived active image
zsk-12  assets/images/product-zsk-12-1.webp              source: archived active image
zsk-15  assets/images/product-zsk-15-1.webp              source: archived active image
zsk-20  assets/images/product-zsk-20-1.webp              source: archived active image
zsk-21  assets/images/product-zsk-21-1.webp              source: archived active image
pzk-10  assets/images/product-pzk-10-1.webp              source: archived active image
pzk-15  assets/images/products/pzk/pzk-15-1.webp          source: archived active image
lowbed-trailer assets/images/products/lowbed/lowbed-trailer-1.webp source: archived active image
pc-11v  assets/images/products/pc/pc-11v-1.webp           source: archived active image
pc-12v  assets/images/products/pc/pc-12v-1.webp           source: archived active image
pc-2    assets/images/products/pc/pc-2-1.webp             source: archived active image
pc-20   assets/images/products/pc/pc-20-1.webp            source: archived active image
pc-5v   assets/images/products/pc/pc-5v-1.webp            source: archived active image
pc-6    assets/images/products/pc/pc-6-1.webp             source: archived active image
pgts-12 assets/images/products/pgts/pgts-12-1.webp        source: archived active image
pgts-3  assets/images/products/pgts/pgts-3-1.webp         source: archived active image
pgts-6-5 assets/images/products/pgts/pgts-6-5-1.webp      source: archived active image
pgts-9  assets/images/products/pgts/pgts-9-1.webp         source: archived active image
ppts-12 assets/images/products/ppts/ppts-12-1.webp         source: client-ppts-12.png
ppts-15 assets/images/products/ppts/ppts-15-1.webp         source: archived active image
ppts-18 assets/images/products/ppts/ppts-18-1.webp         source: archived active image
ppts-20 assets/images/products/ppts/ppts-20-1.webp         source: archived active image
ppts-20p assets/images/products/ppts/ppts-20p-1.webp       source: archived active image
ppts-9  assets/images/products/ppts/ppts-9-1.webp          source: archived active image
zsk-15u assets/images/products/zsk/zsk-15u-1.webp          source: archived active image
```

For every call, use this exact editing intent:

```text
Use case: precise-object-edit
Asset type: primary industrial machinery catalog image
Primary request: isolate this exact machine on a pure white studio catalog background and remove every visible word, model marking, manufacturer logo, ALIKOM emblem, round A emblem, decal, and watermark from the machine surface.
Scene/backdrop: perfectly clean solid white, no environment, no wall, no floor horizon, no gradient.
Composition/framing: keep the entire machine visible with generous even padding; do not crop wheels, drawbar, auger, ladder, frame, lights, or attachments; preserve the original viewing angle.
Constraints: preserve the exact machine geometry, paint color, axle count, wheel count, body sections, hydraulics, hoses, frame, and attachments; change only the background and visible writing/branding; a very soft neutral contact shadow is allowed.
Avoid: invented parts, missing parts, altered proportions, scenic background, people, vehicles not part of the machine, text, logos, watermarks, reflections, or hard shadows.
```

- [ ] **Step 2: Normalize each approved edit with ImageMagick**

For each selected generated image, resize proportionally to fit inside `1080 × 810`, center it on a `1200 × 900` `#ffffff` canvas, strip metadata, and encode WebP at quality 88. Never use a crop operation.

Canonical command shape:

```powershell
magick input.png -auto-orient -resize '1080x810>' -background '#ffffff' -gravity center -extent 1200x900 -strip -quality 88 output.webp
```

- [ ] **Step 3: Inspect a four-column contact sheet**

Create a temporary contact sheet in `%TEMP%` and visually confirm all 26 objects are complete, similarly scaled, on white, and free from ALIKOM/model text. Specifically inspect PPTS-15, PPTS-9, and PPTS-20P for the removed round `A` emblem.

- [ ] **Step 4: Run the image contract tests**

Run:

```powershell
php tests/php/run.php
```

Expected: the 1200 × 900 image contract passes; the footer regression still fails until Task 4.

- [ ] **Step 5: Commit the normalized catalog imagery**

```powershell
git add assets/images
git commit -m "assets: standardize product catalog photography"
```

---

### Task 4: Remove company requisites from the visible footer

**Files:**
- Modify: `templates/partials/footer.php`

**Interfaces:**
- Consumes: existing `$config['name']` for the copyright line.
- Produces: a footer with only the copyright line in `.site-footer__legal`; privacy rendering remains unchanged.

- [ ] **Step 1: Delete only the requisites span**

Change the footer legal block to:

```php
<div class="container site-footer__legal">
  <span>© <?= date('Y') ?> <?= e($config['name']) ?></span>
</div>
```

- [ ] **Step 2: Run the PHP tests**

```powershell
php tests/php/run.php
```

Expected: all PHP tests pass, including footer removal and privacy retention.

- [ ] **Step 3: Commit the footer change**

```powershell
git add templates/partials/footer.php
git commit -m "fix: hide company requisites from footer"
```

---

### Task 5: Refresh the static demo and perform final verification

**Files:**
- Modify: `vercel-demo/**`

**Interfaces:**
- Consumes: current PHP templates, catalog data, and public assets.
- Produces: a static demo matching the original site content and imagery.

- [ ] **Step 1: Re-export the Vercel demo**

```powershell
php scripts/export-vercel-demo.php
```

Expected: exporter completes successfully and copies the 26 updated WebP assets plus refreshed HTML.

- [ ] **Step 2: Run complete automated verification**

```powershell
php tests/php/run.php
npm run test:js
python scripts/validate_tokens.py
python scripts/validate_contrast.py
```

Expected: every command exits `0`.

- [ ] **Step 3: Verify static output content**

Search both original templates and `vercel-demo` output for the removed footer string. It must not occur in the footer, while the privacy page must still contain `ООО «СпецТехПром»` and `5258079050`.

- [ ] **Step 4: Review repository status**

Confirm only planned files are modified and user-owned `.playwright-mcp/` and `ui-mobile-footer.png` remain untouched.

- [ ] **Step 5: Commit the refreshed demo**

```powershell
git add vercel-demo
git commit -m "build: refresh uniform catalog demo"
```

