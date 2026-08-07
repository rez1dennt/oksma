# Mobile Navigation and Compact Modal UX Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restore the exact mobile scroll position after browser Back, remove the burger-menu jump, and make the lead modal fit normal phone viewports without internal scrolling.

**Architecture:** Keep native multi-page navigation and native browser history restoration. Replace fixed-body scroll locking with overflow-based locking plus a coordinate fallback, make drawer entrance wait for two rendered frames, synchronously release the overlay before link navigation, and add a compact form variant used only inside the shared modal.

**Tech Stack:** PHP 8 templates, vanilla ES modules, CSS custom-property tokens, Node built-in test runner, custom PHP test runner, static Vercel exporter.

## Global Constraints

- Canonical source remains in `assets/`, `templates/`, and `src/`; never hand-edit `vercel-demo/`.
- Preserve native multi-page URLs and `history.scrollRestoration = auto`; do not add `sessionStorage` scroll positions or an SPA router.
- Preserve menu and dialog accessibility: Escape, focus trap, inert background, correct ARIA state, and focus restoration.
- Use only `transform` and `opacity` for drawer motion and honor `prefers-reduced-motion`.
- The normal empty modal must fit without internal scrolling at `320 × 568`, `360 × 740`, and `390 × 844`; safety scrolling remains allowed for keyboard, zoom, large text, or multiple validation errors.
- The non-modal lead form on the home page must keep its existing layout.
- Follow TDD: every behavior change starts with a test that fails for the intended reason.

---

### Task 1: Scroll Lock That Preserves Native History Position

**Files:**
- Modify: `tests/js/scroll-lock.test.mjs`
- Modify: `assets/js/scroll-lock.js`
- Modify: `assets/css/main.css:2-5`

**Interfaces:**
- Consumes: `scrollbarCompensation(viewportWidth, clientWidth): number` from `assets/js/overlay-motion.js`.
- Produces: `createScrollLock(viewport, documentElement, body)` with unchanged public methods `lock()`, `unlock()`, and `isLocked()`.
- Invariant: `lock()` does not intentionally change `viewport.scrollX` or `viewport.scrollY`; `unlock()` calls `scrollTo` only if the browser changed the coordinates while locked.

- [ ] **Step 1: Extend the fixture and write failing scroll-lock tests**

Replace the fixture and assertions in `tests/js/scroll-lock.test.mjs` so the expected contract is explicit:

```js
function fixture({ x = 13, y = 739, innerWidth = 390, clientWidth = 375 } = {}) {
  const bodyClasses = new Set();
  const rootClasses = new Set();
  const properties = new Map();
  const scrollCalls = [];
  const viewport = {
    scrollX: x,
    scrollY: y,
    innerWidth,
    scrollTo(nextX, nextY) {
      scrollCalls.push([nextX, nextY]);
      this.scrollX = nextX;
      this.scrollY = nextY;
    },
  };
  const classList = (classes) => ({
    add: (name) => classes.add(name),
    remove: (name) => classes.delete(name),
    contains: (name) => classes.has(name),
  });
  const documentElement = {
    clientWidth,
    classList: classList(rootClasses),
    style: {
      setProperty: (name, value) => properties.set(name, value),
      removeProperty: (name) => properties.delete(name),
    },
  };
  const body = { classList: classList(bodyClasses) };

  return { viewport, documentElement, body, bodyClasses, rootClasses, properties, scrollCalls };
}

test('scroll lock preserves coordinates and avoids an unnecessary scroll on release', () => {
  const state = fixture();
  const scrollLock = createScrollLock(state.viewport, state.documentElement, state.body);

  scrollLock.lock();
  scrollLock.lock();

  assert.equal(state.viewport.scrollY, 739);
  assert.equal(state.bodyClasses.has('is-locked'), true);
  assert.equal(state.rootClasses.has('is-scroll-locked'), true);
  assert.equal(state.properties.get('--scrollbar-compensation'), '15px');
  assert.equal(state.properties.has('--scroll-lock-y'), false);

  scrollLock.unlock();
  assert.deepEqual(state.scrollCalls, []);
  scrollLock.unlock();

  assert.deepEqual(state.scrollCalls, []);
  assert.equal(state.bodyClasses.has('is-locked'), false);
  assert.equal(state.rootClasses.has('is-scroll-locked'), false);
  assert.equal(state.properties.size, 0);
});

test('scroll lock restores saved coordinates only when the browser moved', () => {
  const state = fixture({ x: 7, y: 512 });
  const scrollLock = createScrollLock(state.viewport, state.documentElement, state.body);

  scrollLock.lock();
  state.viewport.scrollX = 0;
  state.viewport.scrollY = 0;
  scrollLock.unlock();

  assert.deepEqual(state.scrollCalls, [[7, 512]]);
});
```

Keep the harmless-extra-unlock behavior with this exact test:

```js
test('extra unlock calls are harmless and never move the page', () => {
  const state = fixture({ x: 0, y: 320, innerWidth: 320, clientWidth: 320 });
  const scrollLock = createScrollLock(state.viewport, state.documentElement, state.body);

  scrollLock.unlock();

  assert.deepEqual(state.scrollCalls, []);
  assert.equal(state.properties.size, 0);
  assert.equal(state.bodyClasses.has('is-locked'), false);
  assert.equal(state.rootClasses.has('is-scroll-locked'), false);
});
```

- [ ] **Step 2: Run the focused test and verify RED**

Run:

```powershell
node --test tests\js\scroll-lock.test.mjs
```

Expected: FAIL because the current implementation sets `--scroll-lock-y`, does not add `is-scroll-locked` to the root, and always calls `scrollTo` on final unlock.

- [ ] **Step 3: Implement overflow-based scroll locking**

Replace `assets/js/scroll-lock.js` with:

```js
import { scrollbarCompensation } from './overlay-motion.js';

export function createScrollLock(viewport, documentElement, body) {
  let count = 0;
  let savedX = 0;
  let savedY = 0;

  const release = () => {
    body.classList.remove('is-locked');
    documentElement.classList.remove('is-scroll-locked');
    documentElement.style.removeProperty('--scrollbar-compensation');
    if (viewport.scrollX !== savedX || viewport.scrollY !== savedY) {
      viewport.scrollTo(savedX, savedY);
    }
  };

  return {
    lock() {
      if (count === 0) {
        savedX = viewport.scrollX;
        savedY = viewport.scrollY;
        const compensation = scrollbarCompensation(viewport.innerWidth, documentElement.clientWidth);
        documentElement.style.setProperty('--scrollbar-compensation', `${compensation}px`);
        documentElement.classList.add('is-scroll-locked');
        body.classList.add('is-locked');
      }
      count += 1;
    },
    unlock() {
      count = Math.max(0, count - 1);
      if (count !== 0 || !body.classList.contains('is-locked')) return;
      release();
    },
    isLocked() {
      return count > 0;
    },
  };
}
```

Replace the fixed-body CSS at the top of `assets/css/main.css` with:

```css
html.is-scroll-locked, body.is-locked { overflow: hidden; overscroll-behavior: none; }
body.is-locked { padding-inline-end: var(--scrollbar-compensation, 0); }
@supports (scrollbar-gutter: stable) { body.is-locked { padding-inline-end: 0; } }
```

- [ ] **Step 4: Run focused and full JavaScript tests and verify GREEN**

Run:

```powershell
node --test tests\js\scroll-lock.test.mjs
node --test tests\js\*.test.mjs
```

Expected: scroll-lock tests pass; complete JS suite reports zero failures.

- [ ] **Step 5: Commit the scroll-lock fix**

```powershell
git add assets/js/scroll-lock.js assets/css/main.css tests/js/scroll-lock.test.mjs
git commit -m "fix: preserve mobile scroll position while overlays are open"
```

---

### Task 2: Deterministic Drawer Motion and Immediate Navigation Cleanup

**Files:**
- Modify: `tests/js/overlay-motion.test.mjs`
- Modify: `assets/js/overlay-motion.js`
- Modify: `assets/js/site.js:4,34-88`
- Modify: `assets/css/theme.css`
- Modify: `assets/css/main.css:56-63,328-332`
- Modify: `tests/php/render_test.php`

**Interfaces:**
- Produces: `afterNextPaint(scheduleFrame = requestAnimationFrame): Promise<void>` from `assets/js/overlay-motion.js`.
- `setupMenu()` must synchronously finish cleanup when called with `{ immediate: true }` before a menu link performs default navigation.
- Existing menu HTML and ARIA attributes remain unchanged.

- [ ] **Step 1: Write the failing two-frame scheduler test**

Append to `tests/js/overlay-motion.test.mjs`:

```js
import { afterNextPaint, scrollbarCompensation, transitionTimeout } from '../../assets/js/overlay-motion.js';

test('after next paint resolves only after two animation frames', async () => {
  const callbacks = [];
  const pending = afterNextPaint((callback) => callbacks.push(callback));
  let resolved = false;
  pending.then(() => { resolved = true; });

  assert.equal(callbacks.length, 1);
  callbacks.shift()();
  await Promise.resolve();
  assert.equal(resolved, false);
  assert.equal(callbacks.length, 1);

  callbacks.shift()();
  await pending;
  assert.equal(resolved, true);
});
```

Update the existing import rather than leaving a duplicate import line.

- [ ] **Step 2: Add failing PHP/CSS behavior contracts**

Append a test to `tests/php/render_test.php` that requires the menu motion and reduced-motion contracts:

```php
test('mobile overlays define deterministic tokenized motion', function (): void {
    $theme = (string) file_get_contents(dirname(__DIR__, 2) . '/assets/css/theme.css');
    $css = (string) file_get_contents(dirname(__DIR__, 2) . '/assets/css/main.css');

    truthy(str_contains($theme, '--ease-enter'));
    truthy(str_contains($theme, '--ease-exit'));
    truthy((bool) preg_match('/\.mobile-menu__panel\s*\{[^}]*opacity:\s*0/s', $css));
    truthy((bool) preg_match('/\.mobile-menu\.is-open \.mobile-menu__panel\s*\{[^}]*opacity:\s*1/s', $css));
    truthy((bool) preg_match('/@media \(prefers-reduced-motion: reduce\).*?\.mobile-menu__panel\s*\{[^}]*transform:\s*none/s', $css));
});
```

- [ ] **Step 3: Run focused tests and verify RED**

Run:

```powershell
node --test tests\js\overlay-motion.test.mjs
php tests\php\run.php
```

Expected: JS fails because `afterNextPaint` does not exist; PHP fails because the new motion tokens and opacity states are absent.

- [ ] **Step 4: Implement the two-frame helper**

Add to `assets/js/overlay-motion.js`:

```js
export function afterNextPaint(scheduleFrame = requestAnimationFrame) {
  return new Promise((resolve) => {
    scheduleFrame(() => scheduleFrame(resolve));
  });
}
```

- [ ] **Step 5: Refactor menu lifecycle without delaying navigation**

Change the import in `assets/js/site.js`:

```js
import { afterNextPaint, transitionTimeout } from './overlay-motion.js';
```

Inside `setupMenu()`, use a synchronous finalizer and a two-frame open:

```js
  const finishClose = (restoreFocus) => {
    menu.hidden = true;
    state = 'closed';
    setPageInert(false, menu);
    if (menuLocked) {
      pageScrollLock.unlock();
      menuLocked = false;
    }
    if (restoreFocus) focusWithoutScroll(toggle);
  };

  const close = async ({ restoreFocus = true, immediate = false } = {}) => {
    if (state === 'closed' || state === 'closing') return;
    state = 'closing';
    menu.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Открыть меню');
    if (immediate) {
      finishClose(restoreFocus);
      return;
    }
    await transitionTimeout(panel, 360);
    if (state === 'closing') finishClose(restoreFocus);
  };

  const open = async () => {
    if (state === 'open' || state === 'opening') return;
    state = 'opening';
    menu.hidden = false;
    toggle.setAttribute('aria-expanded', 'true');
    toggle.setAttribute('aria-label', 'Закрыть меню');
    if (!menuLocked) {
      pageScrollLock.lock();
      menuLocked = true;
    }
    setPageInert(true, menu);
    await afterNextPaint();
    if (state !== 'opening') return;
    menu.classList.add('is-open');
    state = 'open';
    focusWithoutScroll(closeButton ?? panel ?? menu);
  };
```

Change the menu click handler so default link navigation starts only after synchronous cleanup:

```js
  menu.addEventListener('click', (event) => {
    if (event.target === menu) close();
    else if (event.target.closest('a')) close({ restoreFocus: false, immediate: true });
  });
```

- [ ] **Step 6: Add tokenized enter/exit motion**

Add to `:root` in `assets/css/theme.css`:

```css
  --ease-enter: cubic-bezier(.16, 1, .3, 1);
  --ease-exit: cubic-bezier(.4, 0, 1, 1);
```

Update the menu rules in `assets/css/main.css`:

```css
.mobile-menu { position: fixed; inset: 0; z-index: var(--z-drawer); overflow: hidden; background: var(--color-overlay); opacity: 0; pointer-events: none; transition: opacity var(--duration-fast) var(--ease-exit); }
.mobile-menu.is-open { opacity: 1; pointer-events: auto; transition-duration: var(--duration-base); transition-timing-function: var(--ease-enter); }
.mobile-menu__panel { width: min(92vw, 28rem); height: 100dvh; min-height: 0; margin-inline-start: auto; overflow-y: auto; overscroll-behavior: contain; padding-block: max(var(--space-6), env(safe-area-inset-top)) max(var(--space-8), env(safe-area-inset-bottom)); padding-inline: var(--space-6); color: var(--color-text-primary); background: var(--color-surface-card); border-inline-start: var(--border-thin) solid var(--color-border-default); box-shadow: var(--shadow-dialog); opacity: 0; transform: translateX(100%); transition: transform var(--duration-base) var(--ease-exit), opacity var(--duration-fast) var(--ease-exit); will-change: transform, opacity; }
.mobile-menu.is-open .mobile-menu__panel { opacity: 1; transform: translateX(0); transition-duration: var(--duration-moderate), var(--duration-base); transition-timing-function: var(--ease-enter), var(--ease-enter); }
```

Add inside the existing reduced-motion media query:

```css
  .mobile-menu__panel { opacity: 0; transform: none; }
  .mobile-menu.is-open .mobile-menu__panel { opacity: 1; transform: none; }
```

- [ ] **Step 7: Run focused and full tests and verify GREEN**

Run:

```powershell
node --test tests\js\overlay-motion.test.mjs tests\js\scroll-lock.test.mjs
node --test tests\js\*.test.mjs
php tests\php\run.php
```

Expected: zero failures.

- [ ] **Step 8: Commit drawer motion and navigation cleanup**

```powershell
git add assets/js/overlay-motion.js assets/js/site.js assets/css/theme.css assets/css/main.css tests/js/overlay-motion.test.mjs tests/php/render_test.php
git commit -m "fix: smooth mobile drawer navigation"
```

---

### Task 3: Compact Accessible Lead Modal

**Files:**
- Modify: `templates/partials/lead-form.php`
- Modify: `templates/partials/modal.php`
- Modify: `assets/css/main.css:76-100,319-326`
- Modify: `tests/php/render_test.php`

**Interfaces:**
- `templates/partials/lead-form.php` accepts optional `$compact` boolean, default `false`.
- Compact form class: `lead-form lead-form--compact`.
- Field modifier classes: `field--name`, `field--phone`, `field--email`, `field--message`.
- The regular home-page form keeps class `lead-form` without the compact modifier.

- [ ] **Step 1: Write failing render and CSS contract tests**

Extend `layout renders the shared accessible shell` in `tests/php/render_test.php`:

```php
    truthy(str_contains($html, 'class="lead-form lead-form--compact"'));
    truthy(str_contains($html, 'class="field field--phone"'));
    truthy(str_contains($html, 'class="field field--message"'));
```

Add a separate CSS contract test:

```php
test('compact modal form fits normal mobile viewports without clipping', function (): void {
    $css = (string) file_get_contents(dirname(__DIR__, 2) . '/assets/css/main.css');

    truthy((bool) preg_match('/\.modal__dialog\s*\{[^}]*100dvh/s', $css));
    truthy((bool) preg_match('/\.lead-form--compact\s*\{[^}]*grid-template-columns:\s*repeat\(2,/s', $css));
    truthy((bool) preg_match('/\.lead-form--compact \.field-grid\s*\{[^}]*display:\s*contents/s', $css));
    truthy(str_contains($css, '.lead-form--compact .field__error:empty'));
    truthy(str_contains($css, '.lead-form--compact .form-status:empty'));
    truthy((bool) preg_match('/@media \(max-width: 22em\).*?\.lead-form--compact \.field--phone/s', $css));
});
```

- [ ] **Step 2: Run PHP tests and verify RED**

Run:

```powershell
php tests\php\run.php
```

Expected: FAIL because compact markup and CSS do not exist and the dialog uses `100vh`.

- [ ] **Step 3: Add compact form markup without changing field order**

At the top of `templates/partials/lead-form.php`:

```php
$formId = $formId ?? 'lead';
$compact = (bool) ($compact ?? false);
$formClass = 'lead-form' . ($compact ? ' lead-form--compact' : '');
$csrfToken = function_exists('issue_csrf_token') ? issue_csrf_token() : '';
```

Render the class safely:

```php
<form class="<?= e($formClass) ?>" action="/submit.php" method="post" data-lead-form novalidate>
```

Apply modifier classes to the four field containers while keeping their DOM order:

```php
<div class="field field--name">
<div class="field field--phone">
<div class="field field--email">
<div class="field field--message">
```

In `templates/partials/modal.php`, opt into the compact variant:

```php
<?= render_partial('lead-form', ['formId' => 'modal-lead', 'compact' => true]) ?>
```

Shorten only the modal explanation to:

```html
<p>Оставьте контакты. Подготовим предложение под вашу задачу.</p>
```

- [ ] **Step 4: Implement compact token-driven modal CSS**

Update modal rules in `assets/css/main.css` and add the compact variant:

```css
.modal { position: fixed; inset: 0; z-index: var(--z-modal); display: grid; place-items: center; padding: var(--space-4); }
.modal__backdrop { position: absolute; inset: 0; background: var(--color-overlay); }
.modal__dialog { position: relative; width: min(100%, 42rem); max-height: calc(100dvh - var(--space-8)); overflow-y: auto; padding: clamp(var(--space-5), 4vw, var(--space-8)); background: var(--color-surface-card); border-radius: var(--radius-lg); box-shadow: var(--shadow-dialog); }
.modal__dialog .eyebrow { margin-block-end: var(--space-2); }
.modal__dialog h2 { margin-block-end: var(--space-2); font-size: clamp(var(--font-size-2xl), 7vw, var(--font-size-4xl)); }
.modal__dialog > p:not(.eyebrow) { margin-block-end: var(--space-4); color: var(--color-text-secondary); }
.modal__close { position: absolute; inset-block-start: var(--space-2); inset-inline-end: var(--space-2); }

.lead-form--compact { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--space-3); }
.lead-form--compact .field-grid { display: contents; }
.lead-form--compact .field { min-width: 0; gap: var(--space-1); }
.lead-form--compact .field input, .lead-form--compact .field textarea { min-height: var(--space-12); padding: var(--space-2) var(--space-3); font-size: var(--font-size-sm); }
.lead-form--compact .field textarea { height: calc(var(--space-12) + var(--space-2)); resize: none; }
.lead-form--compact .field__error:empty, .lead-form--compact .form-status:empty { display: none; }
.lead-form--compact .consent, .lead-form--compact .button, .lead-form--compact .form-status { grid-column: 1 / -1; }
```

Add a narrow-phone rule before reduced motion:

```css
@media (max-width: 22em) {
  .modal { padding: var(--space-2); }
  .modal__dialog { max-height: calc(100dvh - var(--space-4)); padding: var(--space-4); }
  .lead-form--compact .field--phone, .lead-form--compact .field--message { grid-column: 1 / -1; }
}
```

- [ ] **Step 5: Run PHP tests and verify GREEN**

Run:

```powershell
php tests\php\run.php
```

Expected: zero failures and the new compact modal contracts pass.

- [ ] **Step 6: Commit compact modal implementation**

```powershell
git add templates/partials/lead-form.php templates/partials/modal.php assets/css/main.css tests/php/render_test.php
git commit -m "fix: compact the mobile lead modal"
```

---

### Task 4: Regenerate Demo and Verify Real Mobile Flows

**Files:**
- Regenerate: `vercel-demo/**`
- No manual edits to generated files.

**Interfaces:**
- Consumes: canonical PHP templates and assets from Tasks 1–3.
- Produces: a static demo with identical menu, scroll-lock, and modal presentation code.

- [ ] **Step 1: Export the static demo**

Run:

```powershell
php scripts\export-vercel-demo.php
```

Expected:

```text
Exported 36 pages and 58 assets.
Validation passed.
```

- [ ] **Step 2: Run all automated tests**

Run:

```powershell
php tests\php\run.php
node --test tests\js\*.test.mjs
python -m unittest discover -s tests\import -p "test_*.py"
git diff --check
```

Expected: PHP, JavaScript, and Python suites report zero failures; `git diff --check` exits successfully.

- [ ] **Step 3: Verify Back restoration at `390 × 844` in the browser**

Use the local site at `http://127.0.0.1:8765/`:

1. Set viewport to `390 × 844`.
2. Scroll the home page to a measurable nonzero coordinate.
3. Record `window.scrollY`.
4. Open the burger menu.
5. Confirm `window.scrollY` has not changed.
6. Navigate through an internal menu link.
7. Call browser Back.
8. Confirm returned `window.scrollY` equals the recorded coordinate within one CSS pixel.

- [ ] **Step 4: Verify motion, modal size, and accessibility across viewports**

For `320 × 568`, `360 × 740`, `390 × 844`, and `1280 × 720`:

- menu panel has a measurable intermediate transform/opacity state between closed and open;
- opening and closing do not move page coordinates or sticky header;
- modal normal-state reports `scrollHeight <= clientHeight`;
- document width does not exceed viewport width;
- Escape closes menu and modal;
- Tab/Shift+Tab stay inside the active overlay;
- normal close returns focus to the trigger;
- browser console has no warning or error messages.

For reduced motion, first use browser media emulation if that capability is exposed. If it is not exposed, record that limitation and use the passing PHP CSS contract from Task 2 as the verification that `.mobile-menu__panel` has `transform: none` inside `prefers-reduced-motion: reduce`; do not claim a live reduced-motion browser pass.

- [ ] **Step 5: Run UX/UI verification scripts that apply to the changed source**

From `C:\Users\bahti\.codex\skills\ux-ui-agent-skills`, run these two static gates against the project CSS. Record actual output and treat render-only gates that require a standalone HTML entrypoint as unsupported rather than claiming a pass:

```powershell
python scripts\lint_hardcodes.py "C:\Users\bahti\Documents\сайт каталог заказ\assets\css"
python scripts\validate_theme_refs.py "C:\Users\bahti\Documents\сайт каталог заказ\assets\css\theme.css" "C:\Users\bahti\Documents\сайт каталог заказ\assets\css"
```

Also inspect the rendered mobile screenshots against the approved visual direction: warm industrial palette, compact form, clear focus, no clipped labels, no horizontal overflow.

- [ ] **Step 6: Confirm deterministic export**

Run the exporter a second time and verify `git diff --name-only` does not gain new files or content changes beyond the already regenerated demo. If Git reports only timestamp/stat noise on Windows, compare `git hash-object` against `HEAD` before refreshing the index.

- [ ] **Step 7: Commit generated demo and final synchronization**

```powershell
git add vercel-demo
git commit -m "build: refresh Vercel demo after mobile UX fixes"
```

- [ ] **Step 8: Final status and publication check**

Run:

```powershell
git status --short --branch
git log -5 --oneline
```

Expected: only the pre-existing user-owned `.playwright-mcp/` and `ui-mobile-footer.png` remain untracked; no project source or demo changes remain unstaged.
