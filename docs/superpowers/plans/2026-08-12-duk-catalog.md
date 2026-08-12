# DUK Catalog Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add DUK-2 and a 1900 L DUK tank as verified catalog products, add a responsive execution-options block, and replace PZK-10 photography.

**Architecture:** Keep products in the existing single PHP catalog source and render them through the shared category/product templates. Add one optional `applications` field rendered by a shared product-page section; prepare three 1200×900 white-canvas WebP assets from client sources.

**Tech Stack:** PHP 8.1, semantic HTML, token-driven vanilla CSS, ImageMagick, existing PHP/JS test suites and static exporter.

## Global Constraints

- Publish technical information only; exclude prices, bank details and payment terms.
- DUK-2 and the tank each expose exactly three benefits and valid related-product links.
- Tank installation on GAZon/UAZ is presented as a configurable manufacturing service subject to chassis verification.
- Product images are unique 1200×900 WebP files on white catalog canvases with the complete machine visible.
- UI reuses the existing theme, responsive grid, focus behavior and reduced-motion policy.

---

### Task 1: Product data and routes

**Files:**
- Modify: `tests/php/catalog_test.php`
- Modify: `tests/php/pages_test.php`
- Modify: `data/catalog.php`

**Interfaces:**
- Produces products `duk-2` and `duk-tank-1900` in category `dezinfekcionnye-ustanovki` with `applications` arrays.

- [ ] Add failing tests for both slugs, exact capacities, chassis copy, three benefits, category count and rendered product copy.
- [ ] Run `php tests/php/run.php` and confirm failures are caused by missing DUK products.
- [ ] Add both products with verified specs, dimensions, equipment, SEO and related links.
- [ ] Run `php tests/php/run.php` and confirm green.
- [ ] Commit `feat: add duk equipment to catalog`.

### Task 2: Product execution-options section

**Files:**
- Modify: `tests/php/pages_test.php`
- Modify: `templates/pages/product.php`
- Modify: `assets/css/main.css`

**Interfaces:**
- Consumes optional `product['applications']` records with `title` and `description`.
- Produces `.product-applications` section on relevant product pages only.

- [ ] Add a failing render test for the section, headings and GAZon/UAZ copy.
- [ ] Confirm RED with `php tests/php/run.php`.
- [ ] Add semantic section markup and responsive token-only CSS.
- [ ] Confirm GREEN and run the hardcode/theme-reference validators.
- [ ] Commit `feat: present duk configuration options`.

### Task 3: Catalog images

**Files:**
- Modify: `tests/php/assets_test.php`
- Modify: `assets/images/product-pzk-10-1.webp`
- Create: `assets/images/products/duk/duk-2-1.webp`
- Create: `assets/images/products/duk/duk-tank-1900-1.webp`
- Create: `source-assets/client-corrections/2026-08-12/pzk-10.jpeg`
- Create: `source-assets/client-corrections/2026-08-12/duk-2.png`
- Create: `source-assets/client-corrections/2026-08-12/duk-tank-1900.png`

**Interfaces:**
- Produces three unique 1200×900 WebP catalog images referenced by product data.

- [ ] Add failing assertions for paths, dimensions and distinct hashes.
- [ ] Extract the two equipment images from DOCX and copy the PZK-10 client source into the archive.
- [ ] Use image editing only to isolate the supplied machines on clean white catalog backgrounds while preserving geometry; normalize with ImageMagick to 1200×900 WebP.
- [ ] Run image and PHP validations and visually inspect the three results.
- [ ] Commit `assets: add duk and corrected pzk photography`.

### Task 4: Static demo and release

**Files:**
- Modify: `vercel-demo/**`

**Interfaces:**
- Produces current static routes and assets for all public PHP pages.

- [ ] Run `php scripts/export-vercel-demo.php` and confirm validation.
- [ ] Test category and product pages at mobile and desktop widths in the browser.
- [ ] Run PHP, JS, import, token, contrast, CSS reference and syntax validation.
- [ ] Commit generated demo changes.
- [ ] Merge into `main`, repeat PHP/JS checks and push `origin/main` while preserving unrelated user files.
