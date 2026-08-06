# Oksma UI Polish Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Перевести все публичные страницы ОКСМА на мягкий industrial-editorial UI, исправить резкое поведение drawer и подготовить проверенный `main` для GitHub.

**Architecture:** Сохраняем PHP-шаблоны, каталог данных и доступную разметку. Визуальный слой меняется через семантические токены, локальный variable font и целевые CSS-компоненты; drawer получает явную JS state machine `hidden → opening/open → closing → hidden` с компенсацией scrollbar.

**Tech Stack:** PHP 8.1, semantic HTML, CSS custom properties, vanilla ES modules, Node test runner, Apache, Git.

## Global Constraints

- Не добавлять frontend-фреймворк или сборщик.
- Использовать self-hosted Onest variable WOFF2 с кириллицей и лицензией OFL-1.1.
- Сохранить один `h1`, текущие SEO-схемы, focus trap, Escape и `prefers-reduced-motion`.
- Проверить 360, 390, 768, 1024, 1168, 1440 и 1920 px без horizontal overflow.
- SMTP-конфиг и другие секреты не коммитить.

---

### Task 1: Typography and semantic palette

**Files:**
- Create: `assets/fonts/onest-cyrillic-wght-normal.woff2`
- Create: `assets/fonts/onest-latin-wght-normal.woff2`
- Create: `assets/fonts/OFL.txt`
- Modify: `assets/css/theme.css`
- Modify: `tokens/colors.json`
- Modify: `tokens/typography.json`

**Interfaces:**
- Produces: CSS variables `--font-sans`, updated semantic surface/text/border/shadow/radius tokens.

- [ ] Download the official Fontsource Onest Cyrillic and Latin variable WOFF2 files plus OFL license into `assets/fonts/`.
- [ ] Add `@font-face` declarations with `font-display: swap`, weight range `100 900`, and precise unicode ranges.
- [ ] Replace black/white semantics with milk `#f5f3ef`, warm card `#fffdfa`, ink `#202523`, graphite `#25302d`, raised graphite `#323d39`, accent `#c52d23`.
- [ ] Update DTCG token JSON to match CSS semantics.
- [ ] Run `python scripts/validate_tokens.py` and `python scripts/validate_contrast.py`; required pairs must pass.
- [ ] Commit as `style: refresh typography and palette`.

### Task 2: Full-background hero and softer page heroes

**Files:**
- Modify: `templates/pages/home.php`
- Modify: `templates/pages/category.php`
- Modify: `templates/pages/privacy.php`
- Modify: `assets/css/main.css`
- Test: `tests/php/pages_test.php`

**Interfaces:**
- Produces: `.hero__media` as absolute layer, `.hero__content` glass panel, `.page-hero__media` full photo background.

- [ ] Extend page tests to require hero media, glass content and category photo media; run and observe failure.
- [ ] Move main hero media before the container, keep the real `<img>` and alt text, remove the black plate, add a light `.hero__chip`.
- [ ] Add category image media inside `.page-hero`; keep privacy hero image-free with `.legal-hero` modifier.
- [ ] Implement full-bleed `object-fit: cover`, directional scrims, 28 px panels and mobile full-width CTA layout.
- [ ] Run `php tests/php/run.php`; expected 0 failures.
- [ ] Commit as `style: rebuild hero compositions`.

### Task 3: Component and section polish

**Files:**
- Modify: `assets/css/main.css`
- Modify: `templates/pages/home.php`
- Modify: `templates/pages/product.php`

**Interfaces:**
- Consumes: updated tokens from Task 1.
- Produces: soft request section, consistent buttons, larger benefit icons, rounded cards/forms/chips.

- [ ] Add assertions to `tests/php/pages_test.php` for the unified request panel and benefits structure; run RED.
- [ ] Restyle buttons to 52–56 px with 12 px radius; set hero/product CTA to full width below 48rem.
- [ ] Restyle benefit icons to 56 px containers with 30–34 px SVGs.
- [ ] Replace request black background with `--color-surface-tint`, dark copy, white rounded form card and low shadow.
- [ ] Restyle product benefits/footer/page cards using graphite instead of black and 16–22 px radii.
- [ ] Remove duplicate reduced-motion and print media blocks.
- [ ] Run PHP tests and token validators; expected PASS.
- [ ] Commit as `style: soften industrial components`.

### Task 4: Animated drawer without layout jump

**Files:**
- Create: `assets/js/overlay-motion.js`
- Modify: `assets/js/site.js`
- Modify: `assets/css/main.css`
- Create: `tests/js/overlay-motion.test.mjs`

**Interfaces:**
- Produces: `scrollbarCompensation(viewportWidth, clientWidth): number` and `transitionTimeout(element, fallbackMs): Promise<void>`.

- [ ] Write tests asserting compensation never goes below zero and uses `viewportWidth - clientWidth`; test transition fallback resolves.
- [ ] Run `node --test tests/js/overlay-motion.test.mjs`; expect module-not-found failure.
- [ ] Implement helper module and rerun until green.
- [ ] Change drawer open to unhide then add `.is-open` on the next animation frame; change close to remove state then wait before hiding.
- [ ] Set `--scrollbar-compensation` before locking and clear after the final overlay closes.
- [ ] Add overlay opacity and panel translate transitions, `100dvh`, internal overflow and reduced-motion behavior.
- [ ] Run the complete JS suite; expected 0 failures.
- [ ] Commit as `fix: animate drawer without viewport jump`.

### Task 5: Browser QA and regression verification

**Files:**
- Modify only files implicated by reproducible QA defects.

**Interfaces:**
- Validates: all public routes and interactions.

- [ ] Reload the local site and inspect home/category/product/privacy at 390 and 1440 px.
- [ ] Verify drawer open/close/Escape/focus, modal, phone mask, gallery, tabs, catalog view and cookie.
- [ ] Measure `scrollWidth <= innerWidth` at 360, 390, 768, 1024, 1168, 1440 and 1920 px.
- [ ] Run PHP tests, JS tests, JS syntax checks, PHP lint, Composer validate, token validation and contrast validation.
- [ ] Commit QA fixes as `fix: polish responsive UI details` if changes are required.

### Task 6: Integrate and publish

**Files:**
- Git metadata only.

**Interfaces:**
- Produces: GitHub branch `main` at `https://github.com/rez1dennt/oksma.git`.

- [ ] Verify `git status --porcelain` is empty in `feat/oksma-catalog`.
- [ ] Merge `feat/oksma-catalog` into the root base branch without deleting the live worktree.
- [ ] Rename the root branch to `main`.
- [ ] Add or update remote `origin` to `https://github.com/rez1dennt/oksma.git`.
- [ ] Push `main` with upstream tracking.
- [ ] Verify remote `refs/heads/main` equals the local `main` commit.
