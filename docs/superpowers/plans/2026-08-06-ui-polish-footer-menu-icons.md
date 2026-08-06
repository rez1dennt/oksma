# ОКСМА UI Polish Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Обновить футер под золотой бренд, устранить скачок страницы при работе мобильного меню и заменить упрощённые пиктограммы согласованным SVG-набором.

**Architecture:** Scroll-lock становится отдельным тестируемым JS-модулем, который хранит координаты и поддерживает вложенные блокировки. Цвет футера добавляется через существующие DTCG-токены и единый `theme.css`. Иконки остаются встроенными SVG через существующий `icon()` API, поэтому шаблоны и доступные имена не меняются.

**Tech Stack:** PHP 8.1+, vanilla HTML/CSS, ES modules, Node test runner, DTCG JSON tokens, inline SVG.

## Global Constraints

- Сохранить существующие маршруты, данные, формы, focus trap и ARIA-семантику.
- Не использовать чистые `#000`/`#fff`, emoji и отдельную палитру только для одной страницы.
- Красный остаётся цветом основных действий; золото является акцентом футера и иконок.
- На `prefers-reduced-motion` убрать перемещение панели.
- На ширине 320 px не должно быть горизонтального скролла.
- Любое новое поведение реализуется через RED/GREEN TDD.

---

### Task 1: Scroll-lock без изменения позиции страницы

**Files:**
- Create: `assets/js/scroll-lock.js`
- Modify: `assets/js/site.js`
- Modify: `assets/css/main.css`
- Test: `tests/js/scroll-lock.test.mjs`

**Interfaces:**
- Produces: `createScrollLock(viewport, documentElement, body)` returning `{ lock(): void, unlock(): void, isLocked(): boolean }`.
- Consumes: viewport fields `scrollX`, `scrollY`, `innerWidth`, method `scrollTo(x, y)`; DOMTokenList-compatible `body.classList`; CSSStyleDeclaration-compatible `documentElement.style`.

- [ ] **Step 1: Write the failing nested scroll-lock test**

```js
import test from 'node:test';
import assert from 'node:assert/strict';
import { createScrollLock } from '../../assets/js/scroll-lock.js';

test('scroll lock restores the exact coordinates after the final unlock', () => {
  const classes = new Set();
  const props = new Map();
  const calls = [];
  const viewport = { scrollX: 13, scrollY: 739, innerWidth: 390, scrollTo: (x, y) => calls.push([x, y]) };
  const root = { clientWidth: 375, style: { setProperty: (k, v) => props.set(k, v), removeProperty: (k) => props.delete(k) } };
  const body = { classList: { add: (v) => classes.add(v), remove: (v) => classes.delete(v), contains: (v) => classes.has(v) } };
  const scrollLock = createScrollLock(viewport, root, body);

  scrollLock.lock();
  scrollLock.lock();
  assert.equal(classes.has('is-locked'), true);
  assert.equal(props.get('--scroll-lock-x'), '-13px');
  assert.equal(props.get('--scroll-lock-y'), '-739px');
  scrollLock.unlock();
  assert.deepEqual(calls, []);
  scrollLock.unlock();
  assert.deepEqual(calls, [[13, 739]]);
  assert.equal(scrollLock.isLocked(), false);
});
```

- [ ] **Step 2: Run the test and verify RED**

Run: `node --test tests/js/scroll-lock.test.mjs`

Expected: FAIL with `ERR_MODULE_NOT_FOUND` for `assets/js/scroll-lock.js`.

- [ ] **Step 3: Implement the scroll-lock module**

```js
import { scrollbarCompensation } from './overlay-motion.js';

export function createScrollLock(viewport, documentElement, body) {
  let count = 0;
  let savedX = 0;
  let savedY = 0;

  return {
    lock() {
      if (count === 0) {
        savedX = viewport.scrollX;
        savedY = viewport.scrollY;
        documentElement.style.setProperty('--scrollbar-compensation', `${scrollbarCompensation(viewport.innerWidth, documentElement.clientWidth)}px`);
        documentElement.style.setProperty('--scroll-lock-x', `${-savedX}px`);
        documentElement.style.setProperty('--scroll-lock-y', `${-savedY}px`);
        body.classList.add('is-locked');
      }
      count += 1;
    },
    unlock() {
      count = Math.max(0, count - 1);
      if (count !== 0 || !body.classList.contains('is-locked')) return;
      body.classList.remove('is-locked');
      documentElement.style.removeProperty('--scrollbar-compensation');
      documentElement.style.removeProperty('--scroll-lock-x');
      documentElement.style.removeProperty('--scroll-lock-y');
      viewport.scrollTo(savedX, savedY);
    },
    isLocked() { return count > 0; },
  };
}
```

- [ ] **Step 4: Integrate it into `site.js` and make focus restoration non-scrolling**

```js
import { createScrollLock } from './scroll-lock.js';

const pageScrollLock = createScrollLock(window, document.documentElement, document.body);
const focusWithoutScroll = (element) => element?.focus?.({ preventScroll: true });
```

Replace `lockPage()`/`unlockPage()` calls with `pageScrollLock.lock()`/`pageScrollLock.unlock()`. Replace menu/dialog focus calls with `focusWithoutScroll(...)`.

- [ ] **Step 5: Change locked-body CSS to fixed-position preservation**

```css
body.is-locked {
  position: fixed;
  inset-block-start: var(--scroll-lock-y, 0);
  inset-inline-start: var(--scroll-lock-x, 0);
  inline-size: 100%;
  overflow: hidden;
  padding-inline-end: var(--scrollbar-compensation, 0);
}
```

- [ ] **Step 6: Run focused and full JS tests**

Run: `node --test tests/js/scroll-lock.test.mjs tests/js/overlay-motion.test.mjs`

Expected: all scroll-lock and overlay tests PASS.

Run: `npm run test:js`

Expected: all JS tests PASS.

- [ ] **Step 7: Commit**

```powershell
git add assets/js/scroll-lock.js assets/js/site.js assets/css/main.css tests/js/scroll-lock.test.mjs
git commit -m "fix: preserve page position for overlays"
```

---

### Task 2: Золотой футер через семантические токены

**Files:**
- Modify: `tokens/colors.json`
- Modify: `assets/css/theme.css`
- Modify: `assets/css/main.css`
- Modify: `tests/php/render_test.php`
- Modify: `scripts/validate_contrast.py`

**Interfaces:**
- Produces CSS variables: `--color-footer-bg`, `--color-footer-raised`, `--color-footer-text`, `--color-footer-muted`, `--color-footer-accent`, `--color-footer-accent-hover`.
- Consumes existing `.site-footer` markup unchanged.

- [ ] **Step 1: Add a failing footer token contract test**

```php
test('footer uses the shared warm gold token contract', function (): void {
    $theme = (string) file_get_contents(dirname(__DIR__, 2) . '/assets/css/theme.css');
    $css = (string) file_get_contents(dirname(__DIR__, 2) . '/assets/css/main.css');
    foreach (['--color-footer-bg', '--color-footer-text', '--color-footer-muted', '--color-footer-accent'] as $token) {
        truthy(str_contains($theme, $token));
    }
    truthy((bool) preg_match('/\.site-footer\s*\{[^}]*background:\s*var\(--color-footer-bg\)/s', $css));
});
```

- [ ] **Step 2: Run the PHP suite and verify RED**

Run: `php tests/php/run.php`

Expected: FAIL in `footer uses the shared warm gold token contract`.

- [ ] **Step 3: Add DTCG primitive and semantic roles**

Add exact primitive colors to `tokens/colors.json`:

```json
"gold": {
  "50": {"$type":"color","$value":"#fffaf0","$description":"Lightest champagne"},
  "100": {"$type":"color","$value":"#fff3cf","$description":"Warm pale champagne"},
  "200": {"$type":"color","$value":"#f4e1ad","$description":"Pale gold"},
  "300": {"$type":"color","$value":"#e7cb83","$description":"Footer gold highlight"},
  "400": {"$type":"color","$value":"#b88a32","$description":"Bright brand gold"},
  "500": {"$type":"color","$value":"#8a641f","$description":"Accessible gold text on light surfaces"},
  "600": {"$type":"color","$value":"#79561c","$description":"Gold hover on light surfaces"},
  "700": {"$type":"color","$value":"#674817","$description":"Deep gold"},
  "800": {"$type":"color","$value":"#503716","$description":"Darker gold"},
  "900": {"$type":"color","$value":"#3a2917","$description":"Near-brown gold"},
  "950": {"$type":"color","$value":"#241a10","$description":"Darkest gold"}
},
"warmDark": {
  "900": {"$type":"color","$value":"#2f291f","$description":"Raised warm graphite"},
  "950": {"$type":"color","$value":"#211d17","$description":"Footer warm graphite"}
}
```

Add semantic footer aliases for background, raised surface, text, muted text and accent.

- [ ] **Step 4: Emit the same semantic variables in `theme.css`**

```css
--color-footer-bg: #211d17;
--color-footer-raised: #2f291f;
--color-footer-text: #fff8e9;
--color-footer-muted: #d9cfb7;
--color-footer-accent: #e7cb83;
--color-footer-accent-hover: #fff3cf;
```

- [ ] **Step 5: Restyle footer states using only tokens**

```css
.site-footer { color: var(--color-footer-text); background: var(--color-footer-bg); }
.site-footer__brand p, .site-footer__legal { color: var(--color-footer-muted); }
.site-footer__title, .site-footer__contacts .icon { color: var(--color-footer-accent); }
.site-footer nav a, .site-footer__contacts a { color: var(--color-footer-text); }
.site-footer nav a:hover, .site-footer__contacts a:hover { color: var(--color-footer-accent-hover); }
.site-footer__legal { border-color: var(--color-footer-raised); }
```

- [ ] **Step 6: Extend contrast checks and run validation**

Add required pairs for footer text/background, muted/background and accent/background to `scripts/validate_contrast.py`.

Run: `python scripts/validate_tokens.py; python scripts/validate_contrast.py`

Expected: valid tokens and every required footer pair PASS.

- [ ] **Step 7: Run PHP tests and commit**

Run: `php tests/php/run.php`

Expected: all PHP tests PASS.

```powershell
git add tokens/colors.json assets/css/theme.css assets/css/main.css scripts/validate_contrast.py tests/php/render_test.php
git commit -m "style: align footer with gold brand"
```

---

### Task 3: Согласованный Lucide SVG-набор

**Files:**
- Modify: `src/render.php`
- Modify: `assets/css/main.css`
- Modify: `tests/php/render_test.php`
- Modify: `tests/php/pages_test.php`

**Interfaces:**
- Keeps: `icon(string $name): string`.
- Produces: inline SVG with `viewBox="0 0 24 24"`, `fill="none"`, `stroke="currentColor"`, `stroke-width="2"`, `aria-hidden="true"`.

- [ ] **Step 1: Add failing SVG quality tests**

```php
test('renderer exposes the complete consistent svg icon set', function (): void {
    foreach (['arrow-right', 'check', 'close', 'mail', 'menu', 'phone', 'grid', 'list', 'printer', 'shield', 'truck', 'wrench'] as $name) {
        $svg = icon($name);
        truthy(str_contains($svg, 'viewBox="0 0 24 24"'));
        truthy(str_contains($svg, 'stroke="currentColor"'));
        truthy(str_contains($svg, 'stroke-width="2"'));
        truthy(str_contains($svg, 'aria-hidden="true"'));
    }
    truthy(str_contains(icon('shield'), 'm9 12 2 2 4-4'));
});
```

- [ ] **Step 2: Run tests and verify RED**

Run: `php tests/php/run.php`

Expected: FAIL because the current SVG wrapper uses `stroke-width="1.8"` and simplified paths.

- [ ] **Step 3: Replace icon paths without changing template calls**

Use the corresponding Lucide/Feather 24×24 geometry. The wrapper must be:

```php
return '<svg class="icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
```

For shield use:

```php
'shield' => '<path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3z"/><path d="m9 12 2 2 4-4"/>',
```

For truck use:

```php
'truck' => '<path d="M10 17h4V5H2v12h3"/><path d="M14 9h4l4 4v4h-3"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="16.5" cy="17.5" r="2.5"/>',
```

- [ ] **Step 4: Improve the visual containers**

```css
.benefit-item__icon,
.product-benefit__icon {
  color: var(--color-footer-bg);
  background: color-mix(in srgb, var(--color-footer-accent) 24%, var(--color-surface-card));
  border-color: color-mix(in srgb, var(--color-footer-accent) 48%, var(--color-border-default));
}
.benefit-item__icon .icon { width: 2rem; height: 2rem; }
.product-benefit__icon .icon { width: 1.75rem; height: 1.75rem; }
```

- [ ] **Step 5: Run PHP tests and responsive render checks**

Run: `php tests/php/run.php`

Expected: all PHP tests PASS.

- [ ] **Step 6: Commit**

```powershell
git add src/render.php assets/css/main.css tests/php/render_test.php tests/php/pages_test.php
git commit -m "style: replace interface icons with lucide svg"
```

---

### Task 4: Browser verification and UI quality gates

**Files:**
- Modify only if QA exposes a regression in files from Tasks 1–3.

**Interfaces:**
- Consumes the running local site at `http://127.0.0.1:8765/`.

- [ ] **Step 1: Verify menu scroll invariance at 390 px**

Scroll to a non-zero coordinate, record `scrollY`, open menu, close it, record again.

Expected: coordinate before opening equals coordinate after closing; panel starts at `scrollTop = 0`; background remains fixed.

- [ ] **Step 2: Repeat at 320 px and test keyboard flow**

Expected: no horizontal overflow, Escape closes the menu, Tab stays inside, focus returns to toggle without scrolling.

- [ ] **Step 3: Inspect footer and icons at 320, 390, 768 and 1440 px**

Expected: columns reflow without clipping; gold accents stay restrained; icons are optically aligned and readable; all touch targets remain at least 44 px where applicable.

- [ ] **Step 4: Run complete verification**

```powershell
php tests/php/run.php
npm run test:js
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
python scripts/validate_tokens.py
python scripts/validate_contrast.py
python C:\Users\bahti\.codex\skills\ux-ui-agent-skills\scripts\validate_theme_refs.py assets/css/theme.css assets/css/main.css
git diff --check
```

Expected: zero test, lint, token, contrast, theme-reference and whitespace failures.

- [ ] **Step 5: Commit any QA-only correction**

```powershell
git add assets/css/main.css assets/css/theme.css assets/js/site.js assets/js/scroll-lock.js tokens/colors.json tests/js/scroll-lock.test.mjs tests/php/render_test.php scripts/validate_contrast.py
git commit -m "fix: finalize responsive ui polish"
```
