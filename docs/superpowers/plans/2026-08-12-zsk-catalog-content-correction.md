# ZSK/PZK Catalog Content Correction Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Correct the ZSK/PZK product copy and specifications, remove the public dimensions block from every feed-loader product, and synchronize the dynamic and Vercel-demo builds.

**Architecture:** Keep product facts in the existing catalog data sources and make the shared product template tolerate products without dimensions. Tests assert the public merged catalog, rendered HTML, and static export so imported records cannot silently restore removed dimensions. Original commercial proposals stay outside Git.

**Tech Stack:** PHP 8 catalog/templates, PHP test harness, static HTML exporter, Microsoft Word conversion for source review, Git.

## Global Constraints

- Remove public dimensions from every product in category `zagruzchiki-suhih-kormov`, including imported `zsk-15u`.
- Preserve confirmed dimensions for products in other categories.
- Use the ZSK-20 and ZSK-21 facts from the two client commercial proposals; do not publish prices or proposal typos.
- PZK-15 uses the PZK-10 description and the ZSK-15 specification values while retaining its own identity, image, type, and relationships.
- Mention dry feed, grain, external bins, and seed-drill loading during sowing in feed-loader copy.
- Keep the source `.doc` files in the user's local `Downloads` folder and exclude the local working-copy directory from Git.

---

### Task 1: Lock the content contract with failing tests

**Files:**
- Modify: `tests/php/catalog_test.php`
- Modify: `tests/php/pages_test.php`
- Modify: `tests/php/assets_test.php`

**Interfaces:**
- Consumes: `find_product(string): ?array`, `products_for_category(string): array`, `render_page(string, array): string`.
- Produces: regression assertions for merged product content and conditional dimensions rendering.

- [ ] **Step 1: Add catalog assertions**

Assert that all feed-loader products lack public `dimensions`; ZSK-20 and ZSK-21 expose the exact confirmed values; PZK-15 description equals PZK-10 and specs equal ZSK-15; all feed-loader descriptions mention `сеял`.

- [ ] **Step 2: Add renderer assertions**

Render ZSK-20 and assert it has `Характеристики` but no `Габариты`; render DUK-2 and assert its confirmed `Габариты` block remains.

- [ ] **Step 3: Run the PHP tests and verify RED**

Run: `php tests/php/run.php`

Expected: failures for current dimensions, missing seed-drill copy, old ZSK-20/ZSK-21 values, old PZK-15 content, and unconditional renderer.

### Task 2: Correct catalog data and conditional rendering

**Files:**
- Modify: `data/catalog.php`
- Modify: `data/imported-products.php`
- Modify: `templates/pages/product.php`
- Modify: `.gitignore`

**Interfaces:**
- Consumes: the existing array-based catalog schema.
- Produces: feed-loader products without `dimensions` and a product template that renders the section only when `dimensions` is non-empty.

- [ ] **Step 1: Keep source documents local**

Verify both supplied `.doc` files remain in the user's `Downloads` folder and exclude the local proposal-copy directory from Git.

- [ ] **Step 2: Update category and product copy**

Add dry feed, grain, external-bin loading, and seed-drill loading to the category and feed-loader descriptions using concise catalog language.

- [ ] **Step 3: Apply confirmed ZSK-20 and ZSK-21 specs**

Set ZSK-20 to 17 m³, 5 sections, at least 5700 mm mounting frame, 15 t/h, 2400 kg, maximum 11 t at 0.65 t/m³. Set ZSK-21 to 20.5 m³, 6 sections, at least 6800 mm from the gearbox linkage, 15 t/h, 2650 kg, maximum 13.3 t at 0.65 t/m³.

- [ ] **Step 4: Align PZK-15**

Copy the PZK-10 `description` value and the merged ZSK-15 `specs` values to PZK-15, preserving all product-specific identity fields.

- [ ] **Step 5: Remove feed-loader dimensions at both data layers**

Remove `dimensions` from hand-authored ZSK/PZK records and imported feed-loader records so the merged public result cannot restore them.

- [ ] **Step 6: Make dimensions rendering conditional**

Wrap the shared `Габариты` section in `if (($product['dimensions'] ?? []) !== [])` while leaving `Характеристики` unchanged.

- [ ] **Step 7: Run the PHP tests and verify GREEN**

Run: `php tests/php/run.php`

Expected: all tests pass except the tracked static-demo synchronization test until Task 3.

### Task 3: Rebuild and verify the deployable site

**Files:**
- Modify: generated files under `vercel-demo/catalog/`, `vercel-demo/product/`, and SEO outputs as produced by the exporter.

**Interfaces:**
- Consumes: `scripts/export-vercel-demo.php`.
- Produces: a static demo identical to the dynamic catalog contract.

- [ ] **Step 1: Export the static demo**

Run: `php scripts/export-vercel-demo.php`

Expected: 38 pages exported and validation passed.

- [ ] **Step 2: Run all automated suites**

Run: `php tests/php/run.php`

Run: `npm run test:js`

Run: `python -m unittest discover -s tests/import -p "test_*.py" -v`

Expected: all tests pass with zero failures.

- [ ] **Step 3: Perform responsive browser QA**

Inspect the category and `/product/zsk-20/`, `/product/zsk-21/`, `/product/pzk-15/` at desktop and 390×844. Confirm no `Габариты` section, readable specifications, and no horizontal overflow; confirm a DUK page still renders dimensions.

- [ ] **Step 4: Run repository checks**

Run: `git diff --check`

Expected: no whitespace errors.

- [ ] **Step 5: Commit implementation**

Stage only the catalog correction, tests, template, generated demo, and safety documentation. Commit with `fix: correct ZSK catalog content`.

- [ ] **Step 6: Integrate and push**

Fast-forward `main`, rerun the full suites on `main`, and push `origin main`.
