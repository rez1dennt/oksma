# ОКСМА Brand Assets and Phone Mask Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Встроить три предоставленных фирменных изображения в хедер, футер и блок партнёров и исправить удаление цифр рядом с форматирующими символами телефонной маски.

**Architecture:** Оригиналы остаются вне проекта; ImageMagick создаёт три компактных WebP в `assets/images/`. Шаблоны используют отдельные классы для разных пропорций логотипов. Поведение удаления оформляется чистой функцией в `phone-mask.js`, а обработчик поля вызывает её только для Backspace/Delete рядом с форматирующим символом.

**Tech Stack:** PHP 8.1 templates, HTML, CSS, JavaScript ES modules, Node test runner, ImageMagick, WebP.

## Global Constraints

- Оригиналы в `C:/Users/bahti/Downloads` не изменяются.
- В блоке «Нам доверяют» остаётся один настоящий партнёр без текстовых заглушек.
- Хедер использует прозрачную золотую надпись, футер — круглый золотой знак.
- Пользователь может стереть телефон полностью; скобки и дефисы не блокируют Backspace/Delete.
- Навигация, каталог и цветовая система сайта не меняются.

---

### Task 1: Подготовить оптимизированные фирменные изображения

**Files:**
- Create: `assets/images/logo-oksma-header-gold.webp`
- Create: `assets/images/logo-oksma-footer-gold.webp`
- Create: `assets/images/partner-stp-2008.webp`
- Test: `tests/php/assets_test.php`

**Interfaces:**
- Consumes: три JPEG из `C:/Users/bahti/Downloads`.
- Produces: три WebP с альфа-каналом/обрезанными полями для шаблонов Task 2.

- [ ] **Step 1: Добавить падающие проверки новых ресурсов**

В список обязательных изображений в `tests/php/assets_test.php` добавить:

```php
'/assets/images/logo-oksma-header-gold.webp',
'/assets/images/logo-oksma-footer-gold.webp',
'/assets/images/partner-stp-2008.webp',
```

- [ ] **Step 2: Запустить PHP-тест и подтвердить RED**

Run: `php tests/php/run.php`

Expected: FAIL в тесте растровых ресурсов, потому что новые WebP ещё отсутствуют.

- [ ] **Step 3: Создать оптимизированные копии**

Run из корня проекта:

```powershell
magick 'C:\Users\bahti\Downloads\diting_result_faf94a44916a11f1a51ece5c6ac00432_1.jpeg' -auto-orient -alpha on -fuzz 14% -transparent black -trim +repage -resize '420x110>' -strip -define webp:lossless=true 'assets\images\logo-oksma-header-gold.webp'
magick 'C:\Users\bahti\Downloads\ЛОГОТИП ОКСМА.jpeg' -auto-orient -alpha on -fuzz 8% -transparent white -trim +repage -resize '220x220>' -strip -quality 90 'assets\images\logo-oksma-footer-gold.webp'
magick 'C:\Users\bahti\Downloads\FullSizeRender.jpg' -auto-orient -fuzz 5% -trim +repage -resize '260x180>' -strip -quality 90 'assets\images\partner-stp-2008.webp'
```

- [ ] **Step 4: Проверить форматы и размеры**

Run:

```powershell
magick identify assets\images\logo-oksma-header-gold.webp assets\images\logo-oksma-footer-gold.webp assets\images\partner-stp-2008.webp
```

Expected: три корректных WebP; ширина хедера не более 420 px, футер не более 220×220 px, партнёр не более 260×180 px.

- [ ] **Step 5: Запустить PHP-тест и подтвердить GREEN**

Run: `php tests/php/run.php`

Expected: все PHP-тесты проходят.

- [ ] **Step 6: Commit**

```bash
git add tests/php/assets_test.php assets/images/logo-oksma-header-gold.webp assets/images/logo-oksma-footer-gold.webp assets/images/partner-stp-2008.webp
git commit -m "feat: add approved Oksma brand assets"
```

---

### Task 2: Встроить логотипы в хедер, футер и партнёрский блок

**Files:**
- Modify: `templates/partials/header.php`
- Modify: `templates/partials/footer.php`
- Modify: `templates/pages/home.php`
- Modify: `assets/css/main.css`
- Test: `tests/php/pages_test.php`
- Test: `tests/php/render_test.php`

**Interfaces:**
- Consumes: WebP из Task 1.
- Produces: `.brand--header-gold`, `.brand--footer-mark`, `.trust-grid--single`, `.trust-mark--partner`.

- [ ] **Step 1: Добавить падающие проверки шаблонов**

Добавить проверки:

```php
truthy(str_contains($html, 'logo-oksma-header-gold.webp'));
truthy(str_contains($html, 'logo-oksma-footer-gold.webp'));
truthy(str_contains($html, 'partner-stp-2008.webp'));
same(0, substr_count($html, 'Партнёр 0'));
```

- [ ] **Step 2: Запустить PHP-тест и подтвердить RED**

Run: `php tests/php/run.php`

Expected: FAIL из-за старых путей логотипов и партнёрских заглушек.

- [ ] **Step 3: Обновить шаблоны**

Хедер:

```php
<a class="brand brand--header-gold" href="/" aria-label="ОКСМА, на главную">
  <img src="/assets/images/logo-oksma-header-gold.webp" width="420" height="110" alt="ОКСМА">
</a>
```

Футер:

```php
<a class="brand brand--footer-mark" href="/" aria-label="ОКСМА, на главную">
  <img src="/assets/images/logo-oksma-footer-gold.webp" width="220" height="220" alt="ОКСМА">
</a>
```

Блок партнёра:

```php
<div class="trust-grid trust-grid--single" aria-label="Партнёры ОКСМА">
  <div class="trust-mark trust-mark--partner">
    <img src="/assets/images/partner-stp-2008.webp" width="260" height="180" alt="Логотип партнёра СпецТехПром">
  </div>
</div>
```

- [ ] **Step 4: Добавить адаптивные стили**

Добавить в `assets/css/main.css`:

```css
.brand--header-gold img { width: clamp(8.75rem, 15vw, 11.75rem); }
.brand--footer-mark img { width: clamp(6.5rem, 12vw, 8.5rem); }
.trust-grid--single { grid-template-columns: minmax(0, 24rem); justify-content: center; }
.trust-mark--partner { min-height: 12rem; background: var(--color-surface-card); }
.trust-mark--partner img { width: min(100%, 16.25rem); max-height: 11.25rem; margin-inline: auto; object-fit: contain; }
```

- [ ] **Step 5: Запустить PHP-тест и подтвердить GREEN**

Run: `php tests/php/run.php`

Expected: все PHP-тесты проходят.

- [ ] **Step 6: Commit**

```bash
git add templates/partials/header.php templates/partials/footer.php templates/pages/home.php assets/css/main.css tests/php/pages_test.php tests/php/render_test.php
git commit -m "feat: place approved brand logos"
```

---

### Task 3: Исправить Backspace/Delete в телефонной маске

**Files:**
- Modify: `assets/js/phone-mask.js`
- Modify: `assets/js/site.js`
- Test: `tests/js/phone-mask.test.mjs`

**Interfaces:**
- Produces: `deleteRussianPhoneDigit(value: string, caret: number, direction: 'backward'|'forward'): { value: string, caret: number } | null`.
- Consumes: `formatRussianPhone(value)` из того же модуля.

- [ ] **Step 1: Добавить падающие регрессионные тесты**

```js
import { deleteRussianPhoneDigit } from '../../assets/js/phone-mask.js';

test('backspace removes the preceding digit when a closing parenthesis is at the caret', () => {
  assert.deepEqual(deleteRussianPhoneDigit('+7 (243)', 8, 'backward'), {
    value: '+7 (24',
    caret: 6,
  });
});

test('backspace can clear the remaining country prefix', () => {
  assert.deepEqual(deleteRussianPhoneDigit('+7 (', 4, 'backward'), {
    value: '',
    caret: 0,
  });
});
```

- [ ] **Step 2: Запустить JS-тест и подтвердить RED**

Run: `node --test tests/js/*.test.mjs`

Expected: FAIL, экспорт `deleteRussianPhoneDigit` отсутствует.

- [ ] **Step 3: Реализовать чистую функцию удаления**

```js
export function deleteRussianPhoneDigit(value, caret, direction = 'backward') {
  const text = String(value ?? '');
  const offset = Math.max(0, Math.min(Number(caret) || 0, text.length));
  const adjacent = direction === 'forward' ? text[offset] : text[offset - 1];
  if (adjacent === undefined || /\d/.test(adjacent)) return null;

  let index = direction === 'forward' ? offset : offset - 1;
  const step = direction === 'forward' ? 1 : -1;
  while (index >= 0 && index < text.length && !/\d/.test(text[index])) index += step;
  if (index < 0 || index >= text.length || index <= 1) return { value: '', caret: 0 };

  const formatted = formatRussianPhone(text.slice(0, index) + text.slice(index + 1));
  return { value: formatted, caret: formatted.length };
}
```

- [ ] **Step 4: Подключить обработчик клавиш**

Перед текущим `input`-обработчиком в `setupPhoneMasks()` добавить:

```js
input.addEventListener('keydown', (event) => {
  if ((event.key !== 'Backspace' && event.key !== 'Delete') || input.selectionStart !== input.selectionEnd) return;
  const edit = deleteRussianPhoneDigit(
    input.value,
    input.selectionStart ?? input.value.length,
    event.key === 'Delete' ? 'forward' : 'backward',
  );
  if (!edit) return;
  event.preventDefault();
  input.value = edit.value;
  input.setSelectionRange(edit.caret, edit.caret);
  input.setCustomValidity('');
  input.removeAttribute('aria-invalid');
});
```

Обновить импорт `site.js`, добавив `deleteRussianPhoneDigit`.

- [ ] **Step 5: Запустить JS-тест и подтвердить GREEN**

Run: `node --test tests/js/*.test.mjs`

Expected: все JS-тесты проходят.

- [ ] **Step 6: Commit**

```bash
git add assets/js/phone-mask.js assets/js/site.js tests/js/phone-mask.test.mjs
git commit -m "fix: allow deleting phone mask separators"
```

---

### Task 4: Браузерная и регрессионная проверка

**Files:**
- Modify only if QA reveals a defect.

**Interfaces:**
- Consumes: completed Tasks 1–3.
- Produces: verified responsive UI and clean Git state.

- [ ] **Step 1: Перезапустить локальный сервер и открыть главную**

Run: `php -S 127.0.0.1:8765 router.php`

Expected: `/` отвечает HTTP 200.

- [ ] **Step 2: Проверить визуально 390×844 и 1440×1000**

Проверить хедер, футер и блок «Нам доверяют»: логотипы без квадратных полей, без искажений и горизонтального переполнения.

- [ ] **Step 3: Проверить маску в браузере**

Ввести `+7 (243)`, нажать Backspace и подтвердить значение `+7 (24`. Продолжить Backspace до полностью пустого поля.

- [ ] **Step 4: Запустить полный регрессионный прогон**

Run:

```powershell
php tests/php/run.php
node --test tests/js/*.test.mjs
python scripts/validate_tokens.py
python scripts/validate_contrast.py
git diff --check
```

Expected: ноль ошибок, ноль падений, чистая проверка diff.

- [ ] **Step 5: Подтвердить чистое состояние рабочей копии**

Run: `git status --short`

Expected: вывод пустой; все изменения уже входят в коммиты Tasks 1–3.
