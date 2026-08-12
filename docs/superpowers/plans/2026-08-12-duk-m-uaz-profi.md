# DUK M UAZ PROFI Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a verified «ДУК М на базе УАЗ PROFI» product with client photography, sourced copy, SEO, catalog navigation, and synchronized PHP/Vercel versions.

**Architecture:** Extend the existing PHP catalog data without adding a new template or dependency. Preserve the selected client photo as a source asset, produce one standardized 1200×900 WebP catalog image, and rely on the existing product renderer, router, sitemap, schema, category and static-export mechanisms. Lock the content and image mapping with PHP regression tests before adding production data.

**Tech Stack:** PHP 8 catalog data/templates, PHPUnit-style custom PHP test runner, vanilla JavaScript tests, Python import tests, ImageMagick/ImageGen for one catalog photograph, static PHP exporter for Vercel.

## Global Constraints

- Product name: `ДУК М на базе УАЗ PROFI`.
- Public route: `/product/duk-m-uaz-profi/`.
- Category: `dezinfekcionnye-ustanovki`.
- Use one of the four provided HEIC photographs and preserve the real vehicle and installation.
- Publish a complete, uncropped 1200×900 WebP image on a clean white catalog background.
- Paraphrase purpose and application from the user-provided STP page; do not copy long passages.
- Do not transfer GAZon NEXT equipment specifications to the UAZ PROFI product.
- Publish only values clearly sourced for UAZ PROFI or its verified disinfection equipment; otherwise use `по согласованной комплектации`.
- Update the dynamic PHP site and tracked `vercel-demo` output together.

---

### Task 1: Verify source material and select the photograph

**Files:**
- Read: `C:/Users/bahti/Downloads/IMG_0928.HEIC`
- Read: `C:/Users/bahti/Downloads/IMG_0929.HEIC`
- Read: `C:/Users/bahti/Downloads/IMG_0930.HEIC`
- Read: `C:/Users/bahti/Downloads/IMG_0931.HEIC`
- Create: `source-assets/client-corrections/2026-08-12/duk-m-uaz-profi/<selected-original>.HEIC`
- Create: `assets/images/products/duk/duk-m-uaz-profi-1.webp`

**Interfaces:**
- Consumes: four client photographs and the catalog's 1200×900 white-canvas convention.
- Produces: one archived source and one web-ready product image at the exact path used by catalog data.

- [ ] Convert all four HEIC files into temporary preview PNGs with EXIF orientation applied.
- [ ] Inspect every preview and choose the frame with the whole vehicle visible, minimal obstruction, strongest side/three-quarter product readability and sufficient resolution.
- [ ] Copy the selected original into `source-assets/client-corrections/2026-08-12/duk-m-uaz-profi/` without modifying it.
- [ ] Remove or replace only the background; keep the vehicle, installation geometry, colors and visible equipment invariant.
- [ ] Fit the complete subject within a 1200×900 white canvas, export WebP at quality 88, strip metadata and verify dimensions, white corners and readable subject scale.

### Task 2: Define the product contract through failing tests

**Files:**
- Modify: `tests/php/catalog_test.php`
- Modify: `tests/php/assets_test.php`
- Modify: `tests/php/pages_test.php`

**Interfaces:**
- Consumes: `find_product()`, `products_for_category()`, `render_page()` and the shared product image rules.
- Produces: regression contract for the product data, public page content and product image.

- [ ] Add a catalog test asserting `find_product('duk-m-uaz-profi')` exists, uses the DUK category, exact display name, three benefits, non-empty applications, related DUK products, and contains no borrowed `1 600 л`, `1 500 л`, `4 × 96 л` or `2,5 кгс/см²` values.
- [ ] Add an asset test asserting `assets/images/products/duk/duk-m-uaz-profi-1.webp` is a valid 1200×900 WebP with a stable approved hash.
- [ ] Add a page test rendering the product and asserting the title, UAZ PROFI purpose copy and application section are visible.
- [ ] Run `php tests/php/run.php` and confirm the new tests fail because the product does not exist.

### Task 3: Add catalog content, SEO and relationships

**Files:**
- Modify: `data/catalog.php`

**Interfaces:**
- Consumes: existing product associative-array schema.
- Produces: `duk-m-uaz-profi` record used automatically by category listing, route resolution, schema, sitemap and static export.

- [ ] Add the product with exact slug, category, name, subtitle, SKU, badge, summary, paraphrased description, one image, three benefits, verified specs, relevant dimensions, equipment wording, two or more applications, related DUK products, SEO title and SEO description.
- [ ] Add `duk-m-uaz-profi` to the related arrays of `duk-2` and `duk-tank-1900`, retaining valid existing relationships.
- [ ] Run `php tests/php/run.php` and confirm all catalog, page, asset, route, SEO and sitemap tests pass.

### Task 4: Synchronize the static demo and verify the rendered UI

**Files:**
- Modify/Create: `vercel-demo/product/duk-m-uaz-profi/index.html`
- Modify: `vercel-demo/catalog/dezinfekcionnye-ustanovki/index.html`
- Modify: `vercel-demo/sitemap.xml`
- Modify/Create: `vercel-demo/assets/images/products/duk/duk-m-uaz-profi-1.webp`

**Interfaces:**
- Consumes: completed PHP catalog and `scripts/export-vercel-demo.php`.
- Produces: deployable static product/category pages matching the PHP site.

- [ ] Run `php scripts/export-vercel-demo.php` and require `Validation passed`.
- [ ] Start an isolated local PHP server and verify status 200 for the product and category routes.
- [ ] Inspect desktop and mobile renderings: full image visible, no cropping, heading wraps cleanly, applications and CTA remain readable, no horizontal overflow.
- [ ] Inspect browser console errors and verify the dynamic page and Vercel image hashes match.

### Task 5: Full verification and delivery

**Files:**
- Verify all modified files.

**Interfaces:**
- Consumes: completed product feature.
- Produces: reviewed commit ready for `main`.

- [ ] Run `php tests/php/run.php` and require zero failures.
- [ ] Run `npm run test:js` and require all 16 tests pass.
- [ ] Run the bundled Python `unittest` discovery under `tests/import` and require all tests pass.
- [ ] Run `git diff --check`, inspect the staged diff and confirm no unrelated user files are included.
- [ ] Commit the implementation, cherry-pick it into `main`, rerun the full tests from `main`, push `origin/main`, and remove only the temporary worktree.
