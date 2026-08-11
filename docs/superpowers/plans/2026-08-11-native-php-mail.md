# Native PHP Mail Delivery Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Send every validated lead to `oksmaprom@yandex.ru` through native PHP `mail()` with the same address in `From`, without SMTP credentials or Composer dependencies.

**Architecture:** Keep the existing `LeadMailer` boundary and replace the PHPMailer SMTP adapter with a `NativeMailLeadMailer`. Inject a `Closure` around `mail()` so tests capture the exact envelope and headers without sending real messages; wire `submit.php` directly from `app_config()`.

**Tech Stack:** PHP 8.1+, native `mail()`, existing custom PHP test runner, plain-text UTF-8 MIME email.

## Global Constraints

- Recipient and sender are exactly `oksmaprom@yandex.ru` from `config/app.php`.
- Visitor email may appear only in a validated `Reply-To` header.
- No SMTP password, `config/mail.php`, PHPMailer, Composer dependency, HTML email, or real email call in automated tests.
- A `true` result from `mail()` means only that the hosting mail transport accepted the message; delivery is verified on hosting.
- Existing lead validation, CSRF behavior, neutral user errors, and server-side error logging remain unchanged.

---

### Task 1: Native mail transport

**Files:**
- Modify: `tests/php/form_test.php`
- Modify: `src/mailer.php`

**Interfaces:**
- Consumes: `lead_email_body(array $lead): string` and validated lead arrays from `validate_lead_request()`.
- Produces: `NativeMailLeadMailer::__construct(string $recipient, string $fromAddress, string $fromName = 'Сайт ОКСМА', ?Closure $mailFunction = null)` and `NativeMailLeadMailer::send(array $lead): void`.

- [ ] **Step 1: Write failing transport tests**

Add tests that inject a closure and capture `$to`, `$subject`, `$message`, and `$headers`. Assert the recipient and `From` address are `oksmaprom@yandex.ru`, body contains the normalized lead, `Reply-To` exists only for a non-empty email, header values contain no injected line breaks, and a closure returning `false` produces `RuntimeException`.

```php
$calls = [];
$transport = new NativeMailLeadMailer(
    'oksmaprom@yandex.ru',
    'oksmaprom@yandex.ru',
    'Сайт ОКСМА',
    static function (string $to, string $subject, string $message, string $headers) use (&$calls): bool {
        $calls[] = compact('to', 'subject', 'message', 'headers');
        return true;
    }
);
$transport->send($lead);
same('oksmaprom@yandex.ru', $calls[0]['to']);
truthy(str_contains($calls[0]['headers'], '<oksmaprom@yandex.ru>'));
truthy(str_contains($calls[0]['headers'], 'Reply-To:'));
```

- [ ] **Step 2: Run the PHP suite and verify RED**

Run: `php tests/php/run.php`

Expected: FAIL because `NativeMailLeadMailer` does not exist.

- [ ] **Step 3: Implement the minimal transport**

Replace `SmtpLeadMailer` and the PHPMailer import with helpers that reject `CR`/`LF`, encode UTF-8 header text, and a native transport:

```php
final class NativeMailLeadMailer implements LeadMailer
{
    private readonly Closure $mailFunction;

    public function __construct(
        private readonly string $recipient,
        private readonly string $fromAddress,
        private readonly string $fromName = 'Сайт ОКСМА',
        ?Closure $mailFunction = null,
    ) {
        validate_mailbox($this->recipient);
        validate_mailbox($this->fromAddress);
        reject_mail_header_breaks($this->fromName);
        $this->mailFunction = $mailFunction ?? static fn (
            string $to,
            string $subject,
            string $message,
            string $headers,
        ): bool => mail($to, $subject, $message, $headers);
    }

    public function send(array $lead): void
    {
        $headers = build_lead_mail_headers($lead, $this->fromAddress, $this->fromName);
        $sent = ($this->mailFunction)(
            $this->recipient,
            encode_mail_header('Заявка с сайта ОКСМА — ' . $lead['name']),
            lead_email_body($lead),
            implode("\r\n", $headers),
        );
        if (!$sent) {
            throw new RuntimeException('Native mail transport rejected the lead.');
        }
    }
}
```

- [ ] **Step 4: Run the PHP suite and verify GREEN**

Run: `php tests/php/run.php`

Expected: all tests pass.

- [ ] **Step 5: Commit the transport**

```bash
git add src/mailer.php tests/php/form_test.php
git commit -m "feat: add native php lead mailer"
```

---

### Task 2: Wire the form without secret configuration

**Files:**
- Modify: `tests/php/form_test.php`
- Modify: `src/mailer.php`
- Modify: `submit.php`

**Interfaces:**
- Consumes: `app_config(): array`, with `name` and `email` keys.
- Produces: `create_app_lead_mailer(array $config, ?Closure $mailFunction = null): NativeMailLeadMailer`.

- [ ] **Step 1: Write a failing application wiring test**

Add a factory test that injects a capture closure and proves both recipient and `From` use the app email. Also assert the submit controller no longer mentions `config/mail.php` or `SmtpLeadMailer`.

```php
$mailer = create_app_lead_mailer([
    'name' => 'ОКСМА',
    'email' => 'oksmaprom@yandex.ru',
], $capture);
$mailer->send($lead);
same('oksmaprom@yandex.ru', $calls[0]['to']);
$submit = file_get_contents($root . '/submit.php');
same(false, str_contains($submit, 'config/mail.php'));
same(false, str_contains($submit, 'SmtpLeadMailer'));
```

- [ ] **Step 2: Run the PHP suite and verify RED**

Run: `php tests/php/run.php`

Expected: FAIL because `create_app_lead_mailer()` is undefined and `submit.php` still requires SMTP configuration.

- [ ] **Step 3: Implement the factory and controller wiring**

Add the factory in `src/mailer.php`:

```php
function create_app_lead_mailer(array $config, ?Closure $mailFunction = null): NativeMailLeadMailer
{
    $address = (string) ($config['email'] ?? '');
    $name = 'Сайт ' . (string) ($config['name'] ?? 'ОКСМА');
    return new NativeMailLeadMailer($address, $address, $name, $mailFunction);
}
```

Replace the SMTP config block in `submit.php` with:

```php
deliver_lead($result['data'], create_app_lead_mailer(app_config()));
```

Keep the existing `try/catch`, CSRF rotation, JSON responses, redirect responses, and `error_log()` call.

- [ ] **Step 4: Verify controller syntax and GREEN tests**

Run: `php -l src/mailer.php; php -l submit.php; php tests/php/run.php`

Expected: no syntax errors and all tests pass.

- [ ] **Step 5: Commit the form wiring**

```bash
git add src/mailer.php submit.php tests/php/form_test.php
git commit -m "feat: send leads with native php mail"
```

---

### Task 3: Remove SMTP dependency and update deployment docs

**Files:**
- Modify: `tests/php/security_test.php`
- Modify: `bootstrap.php`
- Modify: `.gitignore`
- Modify: `README.md`
- Modify: `docs/DEPLOYMENT.md`
- Delete: `config/mail.example.php`
- Delete: `composer.json`
- Delete: `composer.lock`

**Interfaces:**
- Consumes: the native transport from Tasks 1–2.
- Produces: a PHP-hosting package with no SMTP secret or Composer install requirement.

- [ ] **Step 1: Write a failing dependency-removal test**

Replace the SMTP secret test with assertions that the SMTP example and Composer manifest are absent, `bootstrap.php` has no vendor autoload, and deployment documentation explicitly mentions PHP `mail()` and `oksmaprom@yandex.ru`.

```php
test('native mail delivery needs no smtp secret or composer dependency', function () use ($root): void {
    same(false, is_file($root . '/config/mail.example.php'));
    same(false, is_file($root . '/composer.json'));
    same(false, str_contains(file_get_contents($root . '/bootstrap.php'), 'vendor/autoload.php'));
    truthy(str_contains(file_get_contents($root . '/docs/DEPLOYMENT.md'), 'PHP `mail()`'));
    truthy(str_contains(file_get_contents($root . '/docs/DEPLOYMENT.md'), 'oksmaprom@yandex.ru'));
});
```

- [ ] **Step 2: Run the PHP suite and verify RED**

Run: `php tests/php/run.php`

Expected: FAIL because SMTP/Composer files still exist and docs still describe SMTP.

- [ ] **Step 3: Remove dependencies and update documentation**

Delete `config/mail.example.php`, `composer.json`, and `composer.lock`. Remove the optional vendor autoload block from `bootstrap.php`; remove obsolete mail-secret and Composer lines from `.gitignore`; replace SMTP instructions in README and `docs/DEPLOYMENT.md` with these facts:

```markdown
- Формы отправляются встроенной функцией PHP `mail()`.
- Получатель и отправитель: `oksmaprom@yandex.ru`.
- Пароль и `config/mail.php` не нужны.
- На хостинге должна быть разрешена функция `mail()` и настроен системный почтовый транспорт.
```

- [ ] **Step 4: Run all PHP tests and syntax checks**

Run: `php -l bootstrap.php; php -l src/mailer.php; php -l submit.php; php tests/php/run.php`

Expected: all syntax checks and all PHP tests pass.

- [ ] **Step 5: Commit dependency cleanup**

```bash
git add .gitignore README.md bootstrap.php docs/DEPLOYMENT.md tests/php/security_test.php
git rm config/mail.example.php composer.json composer.lock
git commit -m "chore: remove smtp mail dependency"
```

---

### Task 4: Refresh demo and verify release

**Files:**
- Modify if generated content changes: `vercel-demo/**`

**Interfaces:**
- Consumes: completed native-mail implementation and all existing project validators.
- Produces: a verified PHP source tree and current static Vercel demo.

- [ ] **Step 1: Refresh the static demo**

Run: `php scripts/export-vercel-demo.php`

Expected: `Exported 36 pages and 58 assets.` followed by `Validation passed.`

- [ ] **Step 2: Run the complete verification matrix**

Run:

```powershell
php tests/php/run.php
npm run test:js
python -m unittest discover -s tests/import -p 'test_*.py'
python scripts/validate_tokens.py
python scripts/validate_contrast.py
git diff --check
```

Expected: 0 failures, all required contrast pairs pass, and no whitespace errors.

- [ ] **Step 3: Commit generated demo changes if any**

```bash
git add vercel-demo
git diff --cached --quiet || git commit -m "build: refresh native mail demo"
```

- [ ] **Step 4: Complete and publish**

Merge the isolated branch into `main`, rerun PHP and JavaScript tests on merged `main`, push `main` to `origin`, and preserve unrelated untracked user files.
