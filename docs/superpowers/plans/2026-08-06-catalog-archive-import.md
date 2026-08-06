# ОКСМА Catalog Archive Import Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Извлечь достоверные характеристики и фотографии из `Gmail (2).zip`, создать полный каталог моделей ЗСК, ПЗК, ПЦ, ПГТС, ППТС и низкорамного прицепа и подготовить единообразные каталожные изображения.

**Architecture:** Импорт выполняется воспроизводимыми dev-скриптами в `.tmp/catalog-import`; оригинальный ZIP не меняется и не попадает в Git. DOCX читаются через python-docx, старые DOC предварительно конвертируются установленным Microsoft Word в копии DOCX. Нормализованные данные сохраняются по семействам в `data/products/*.php`, а публичный каталог объединяет их через существующий API.

**Tech Stack:** PHP 8.1+, Python 3 с python-docx/Pillow, PowerShell + Microsoft Word COM для legacy DOC, ImageMagick 7 с HEIC/WebP, built-in ImageGen, vanilla templates.

## Global Constraints

- Не менять и не удалять файлы пользователя в `Downloads`.
- Не публиковать цены, банковские реквизиты, подписи и условия конкретного коммерческого предложения.
- Не придумывать технические значения; конфликты фиксировать в отчёте.
- Генерация изображений не является источником характеристик.
- Не добавлять административную панель, MySQL или runtime-зависимость от Python/Word.
- Все публичные фотографии сохранять как оптимизированные WebP с осмысленным alt через название товара.

---

### Task 1: Безопасная распаковка и manifest архива

**Files:**
- Create: `tools/catalog_import/Extract-CatalogArchive.ps1`
- Create: `tools/catalog_import/__init__.py`
- Create: `tools/catalog_import/build_manifest.py`
- Create: `tests/import/test_build_manifest.py`
- Verify: `.gitignore` already contains `.tmp/`

**Interfaces:**
- `Extract-CatalogArchive.ps1 -SourceZip <absolute> -Destination <workspace/.tmp/catalog-import/raw>`.
- `build_manifest.py --source <raw> --out <manifest.json>` produces entries with `relative_path`, `extension`, `size`, `sha256`, `family`, `model`, `duplicate_of`.

- [ ] **Step 1: Write failing manifest classification tests**

```python
from tools.catalog_import.build_manifest import classify_name

def test_classifies_document_families_and_models():
    assert classify_name('КП Бункер ЗСК-15У в сборе.doc') == ('zsk', 'ЗСК-15У')
    assert classify_name('Коммерческое предложение на ПЦ 11В.docx') == ('pc', 'ПЦ-11В')
    assert classify_name('КП ППТС 18 марки Оксма.doc') == ('ppts', 'ППТС-18')
    assert classify_name('КП Прицеп низкорамный.doc') == ('lowbed', 'Низкорамный прицеп')
```

- [ ] **Step 2: Run tests and verify RED**

Run: `python -m unittest discover -s tests/import -p "test_*.py"`

Expected: FAIL because `tools.catalog_import.build_manifest` does not exist.

- [ ] **Step 3: Implement safe extraction**

The PowerShell script must resolve both paths, reject destinations outside the repository `.tmp/catalog-import`, reject ZIP entries containing `..`, and use `System.IO.Compression.ZipFile` to extract copies.

```powershell
param([Parameter(Mandatory)][string]$SourceZip,[Parameter(Mandatory)][string]$Destination)
$source=(Resolve-Path -LiteralPath $SourceZip).Path
$dest=[IO.Path]::GetFullPath($Destination)
if($dest -notmatch '[\\/]\.tmp[\\/]catalog-import([\\/]|$)'){throw 'Destination must be inside .tmp/catalog-import'}
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip=[IO.Compression.ZipFile]::OpenRead($source)
try { foreach($entry in $zip.Entries){ if($entry.FullName -match '(^|[\\/])\.\.([\\/]|$)'){throw 'Unsafe ZIP entry'} } } finally { $zip.Dispose() }
Expand-Archive -LiteralPath $source -DestinationPath $dest -Force
```

- [ ] **Step 4: Implement deterministic classification and hashes**

`classify_name(name: str) -> tuple[str, str] | tuple[None, None]` must normalize spaces/dashes, match ЗСК, ПЗК, ПЦ, ПГТС, ППТС and lowbed names and never infer a model from a generic `IMG_*` filename. Duplicate files are identified by SHA-256.

- [ ] **Step 5: Run tests, extract and build manifest**

```powershell
powershell -ExecutionPolicy Bypass -File tools/catalog_import/Extract-CatalogArchive.ps1 -SourceZip 'C:\Users\bahti\Downloads\Gmail (2).zip' -Destination '.tmp\catalog-import\raw'
python tools/catalog_import/build_manifest.py --source .tmp/catalog-import/raw --out .tmp/catalog-import/manifest.json
```

Expected: 58 ZIP entries are represented; identical `diting_result_b705...` files share a duplicate hash.

- [ ] **Step 6: Commit scripts and tests, not extracted files**

```powershell
git add tools/catalog_import tests/import
git commit -m "chore: add reproducible catalog archive inventory"
```

---

### Task 2: DOC/DOCX conversion, text and media extraction

**Files:**
- Create: `tools/catalog_import/Convert-LegacyDocs.ps1`
- Create: `tools/catalog_import/extract_documents.py`
- Create: `tests/import/test_extract_documents.py`
- Generate ignored: `.tmp/catalog-import/converted/*.docx`
- Generate ignored: `.tmp/catalog-import/extracted/<document-id>/{content.json,media/*}`

**Interfaces:**
- Converter accepts `-SourceDirectory`, `-DestinationDirectory` and writes DOCX copies using Word format code `16`.
- `extract_document(path: Path, media_dir: Path) -> dict` returns paragraphs, tables, ordered text blocks and extracted media paths.

- [ ] **Step 1: Write a failing DOCX fixture test**

Create a temporary DOCX with python-docx containing heading `ППТС-18`, a two-column specifications table and an embedded PNG. Assert that `extract_document` returns all text cells and one media file with a SHA-256.

- [ ] **Step 2: Run test and verify RED**

Run: `python -m unittest discover -s tests/import -p "test_extract_documents.py" -v`

Expected: FAIL because `extract_documents.py` does not exist.

- [ ] **Step 3: Implement non-destructive Word conversion**

Use installed `C:\Program Files\Microsoft Office\root\Office16\WINWORD.EXE` through `Word.Application`, set `Visible = $false`, `DisplayAlerts = 0`, open every `.doc` read-only and call `SaveAs2($target, 16)`. Always close document and Word in `finally`.

- [ ] **Step 4: Implement DOCX extraction**

Use python-docx for paragraphs/tables, then inspect the ZIP `word/media/*` entries for images. Write `content.json` in UTF-8 with source filename, ordered text, table rows, media hashes and relationship targets.

- [ ] **Step 5: Convert and extract every commercial proposal**

```powershell
powershell -ExecutionPolicy Bypass -File tools/catalog_import/Convert-LegacyDocs.ps1 -SourceDirectory .tmp/catalog-import/raw -DestinationDirectory .tmp/catalog-import/converted
python tools/catalog_import/extract_documents.py --docx-source .tmp/catalog-import/raw --converted-source .tmp/catalog-import/converted --out .tmp/catalog-import/extracted
```

Expected: all 29 commercial proposals have `content.json`; failures are listed with exact filename and error in `.tmp/catalog-import/extraction-errors.json`.

- [ ] **Step 6: Commit extraction code and tests**

```powershell
git add tools/catalog_import tests/import
git commit -m "feat: extract catalog documents and embedded media"
```

---

### Task 3: Нормализованный технический отчёт по моделям

**Files:**
- Create: `tools/catalog_import/normalize_products.py`
- Create: `tests/import/test_normalize_products.py`
- Generate ignored: `.tmp/catalog-import/products.normalized.json`
- Create: `docs/catalog-import/2026-08-06-source-report.md`

**Interfaces:**
- `normalize_model_name(text: str) -> str | None`.
- `extract_specs(blocks: list[dict]) -> dict[str, str]`.
- Output product record: `model`, `family`, `source_documents`, `purpose`, `specs`, `dimensions`, `equipment`, `conflicts`, `media_candidates`.

- [ ] **Step 1: Write failing normalization tests for Russian units and model names**

```python
def test_normalizes_units_without_inventing_values():
    assert normalize_value('10 куб. м.') == '10 м³'
    assert normalize_value('6 500 кг.') == '6 500 кг'
    assert normalize_model_name('ППТС - 20 марки Оксма') == 'ППТС-20'
```

- [ ] **Step 2: Run tests and verify RED**

Run: `python -m unittest discover -s tests/import -p "test_normalize_products.py" -v`

Expected: FAIL because normalization functions do not exist.

- [ ] **Step 3: Implement explicit field aliases**

Map only named document labels such as `грузоподъёмность`, `объём кузова`, `масса`, `длина`, `ширина`, `высота`, `количество осей`, `производительность`, `высота выгрузки`, `комплектация`. Preserve source strings for unrecognized fields.

- [ ] **Step 4: Produce normalized JSON and conflict report**

Run: `python tools/catalog_import/normalize_products.py --manifest .tmp/catalog-import/manifest.json --documents .tmp/catalog-import/extracted --out .tmp/catalog-import/products.normalized.json --report docs/catalog-import/2026-08-06-source-report.md`

Expected: every model lists source documents; conflicting non-empty values appear under `conflicts` and are not silently merged.

- [ ] **Step 5: Manually compare every normalized model to rendered source pages**

Render the first technical page and every page containing a specification table for each proposal. Confirm model, type, capacity/load, dimensions and equipment against the normalized record. Correct parser rules, never normalized output by hand.

- [ ] **Step 6: Commit parser and evidence report**

```powershell
git add tools/catalog_import tests/import docs/catalog-import/2026-08-06-source-report.md
git commit -m "docs: map commercial proposals to catalog models"
```

---

### Task 4: Curate and optimize original product photography

**Files:**
- Create: `tools/catalog_import/prepare_images.ps1`
- Create: `tests/php/import_assets_test.php`
- Create: `assets/images/products/<family>/<model>-<index>.webp`
- Generate ignored: `.tmp/catalog-import/image-contact-sheets/*.jpg`

**Interfaces:**
- Script consumes manifest, extracted media and loose archive photos.
- Produces WebP files, width at most 1920 px, auto-oriented, metadata stripped, stable lowercase filenames.

- [ ] **Step 1: Add failing asset validation test**

Assert that `assets/images/products` contains at least one file, every discovered file has MIME `image/webp`, dimensions are at least 640×480 and no duplicate hash is published twice for the same model.

- [ ] **Step 2: Run test and verify RED**

Run: `php tests/php/run.php`

Expected: FAIL because new declared/manifest image paths are absent.

- [ ] **Step 3: Generate contact sheets and map images**

Use ImageMagick to decode HEIC/JPEG/PNG, auto-orient thumbnails and build labeled contact sheets. Match embedded document media directly to its document model; match loose photos only when visual construction and metadata are unambiguous. Record mapping in source report.

- [ ] **Step 4: Produce optimized originals**

For each approved original run ImageMagick with `-auto-orient -resize '1920x1920>' -strip -quality 84 -define webp:method=6`. Never upscale an original below minimum quality; such an image becomes a generation reference instead.

- [ ] **Step 5: Inspect every final WebP**

Expected: correct orientation, entire machine visible or useful detail, no unrelated documents/people/private information, no severe compression artifacts.

- [ ] **Step 6: Commit approved originals and test**

```powershell
git add assets/images/products tests/php/import_assets_test.php docs/catalog-import/2026-08-06-source-report.md tools/catalog_import/prepare_images.ps1
git commit -m "feat: add verified product photography"
```

---

### Task 5: Generate missing catalog hero images from document references

**Files:**
- Create: `assets/images/products/<family>/<model>-generated-1.webp`
- Modify: `docs/catalog-import/2026-08-06-source-report.md`

**Interfaces:**
- One built-in ImageGen call per missing model.
- Each call references only images already inspected for that model.

- [ ] **Step 1: List models without an approved lead image**

Derive the list from normalized products and image mapping. The report must identify reference file(s), required axle count, body/tank/bunker shape, color, tow/chassis arrangement and visible equipment.

- [ ] **Step 2: Generate one model at a time with invariant-first prompt**

Use case `product-mockup`. Required prompt structure:

```text
Asset type: primary catalog product image for the ОКСМА website.
Input image: technical reference for this exact model; preserve construction, not image quality.
Subject: the complete machine, exact body geometry, exact axle count, tow/chassis layout and visible equipment from the reference.
Scene: clean industrial yard, neutral modern building, dry pavement.
Composition: natural 3/4 view, full machine inside frame, landscape crop with safe margins.
Lighting: soft overcast daylight, realistic materials and shadows.
Constraints: preserve all structural invariants; no added mechanisms; no people; no logos; no model lettering; no licence plates; no watermark; no text.
```

- [ ] **Step 3: Inspect against the reference and iterate once per defect**

Reject wrong axle count, wrong discharge system, wrong tank/body proportions or invented components. Follow-up changes must name one defect and repeat all invariants.

- [ ] **Step 4: Move selected outputs into the project and optimize**

Copy the selected built-in output to the exact model path, then use ImageMagick to create WebP at maximum 1920 px, quality 86, metadata stripped.

- [ ] **Step 5: Update report with generated provenance**

For each generated image record model, reference paths, final prompt, final file and visual verification result.

- [ ] **Step 6: Commit generated catalog assets**

```powershell
git add assets/images/products docs/catalog-import/2026-08-06-source-report.md
git commit -m "feat: add generated catalog product imagery"
```

---

### Task 6: Publish normalized products through the catalog API

**Files:**
- Create: `data/products/feed-loaders.php`
- Create: `data/products/trailers.php`
- Create: `data/products/tankers.php`
- Modify: `data/catalog.php`
- Modify: `tests/php/catalog_test.php`
- Modify: `tests/php/pages_test.php`
- Modify: `tests/php/router_seo_test.php`

**Interfaces:**
- Each product file returns `array<string,array>` keyed by slug.
- `data/catalog.php` returns existing `categories` and `products => array_merge(...)`.
- Product schema remains compatible with `src/catalog.php`, templates and SEO.

- [ ] **Step 1: Add failing product count, category and integrity tests**

Assert all normalized model slugs exist, every previously empty confirmed category has products, `catalog_integrity_errors()` is empty, every image exists and declaration mappings are present only for covered models.

- [ ] **Step 2: Run PHP tests and verify RED**

Run: `php tests/php/run.php`

Expected: FAIL for absent imported model slugs.

- [ ] **Step 3: Create family data files from verified normalized records**

Every product must provide real `specs`, `dimensions` and `equipment` extracted from its source. Copywriting may summarize purpose but must not invent performance claims. Use stable ASCII slugs such as `pc-11v`, `ppts-18`, `pgts-6-5`.

- [ ] **Step 4: Merge product families in `data/catalog.php`**

```php
$feedProducts = require __DIR__ . '/products/feed-loaders.php';
$trailerProducts = require __DIR__ . '/products/trailers.php';
$tankerProducts = require __DIR__ . '/products/tankers.php';

// In returned array:
'products' => array_merge($feedProducts, $trailerProducts, $tankerProducts),
```

- [ ] **Step 5: Verify category/product rendering and SEO**

Run: `php tests/php/run.php`

Expected: all integrity, page, route, schema and sitemap tests PASS.

- [ ] **Step 6: Commit catalog data**

```powershell
git add data/catalog.php data/products tests/php
git commit -m "feat: publish catalog models from source documents"
```

---

### Task 7: End-to-end catalog QA

**Files:**
- Modify only files where QA proves a defect.

**Interfaces:**
- Consumes every category URL and product slug from `all_products()`.

- [ ] **Step 1: Render every category and product over HTTP**

Expected: status 200, one H1, canonical URL, Product schema on products, no PHP warning text.

- [ ] **Step 2: Inspect category cards and product galleries at 320, 390, 768 and 1440 px**

Expected: no horizontal overflow, correct aspect ratios, names wrap without clipping, specs remain scannable, galleries do not shift layout.

- [ ] **Step 3: Verify images and declarations**

Expected: every image URL returns `image/webp`; PЗК/ПЦ/ППТС/ПГТС mappings match the two public declarations exactly; ЗСК receives no feed-trailer declaration unless explicitly listed.

- [ ] **Step 4: Run complete verification**

```powershell
php tests/php/run.php
npm run test:js
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
python -m unittest discover -s tests/import -p "test_*.py"
python scripts/validate_tokens.py
python scripts/validate_contrast.py
git diff --check
```

Expected: zero failures and no untracked import artifacts outside `.tmp/`.

- [ ] **Step 5: Commit QA corrections**

```powershell
git add data/catalog.php data/products assets/images/products docs/catalog-import tests/php tests/import tools/catalog_import
git commit -m "fix: finalize imported catalog content"
```
