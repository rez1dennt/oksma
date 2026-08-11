<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/bootstrap.php';

function valid_lead_payload(array $overrides = []): array
{
    return array_replace([
        'csrf_token' => 'test-token',
        'started_at' => '1722945590',
        'website' => '',
        'name' => 'Иван Петров',
        'phone' => '8 (937) 435-17-00',
        'email' => 'ivan@example.ru',
        'message' => 'Нужен расчёт загрузчика.',
        'privacy' => '1',
        'source' => '/product/demo/',
    ], $overrides);
}

test('lead validator normalizes an accepted request', function (): void {
    $result = validate_lead_request(valid_lead_payload(), 1722945600, 'test-token');
    truthy($result['ok']);
    same('+79374351700', $result['data']['phone']);
    same('/product/demo/', $result['data']['source']);
});

test('lead validator rejects invalid fields consent spam and csrf', function (): void {
    $result = validate_lead_request(valid_lead_payload([
        'csrf_token' => 'wrong',
        'started_at' => '1722945599',
        'website' => 'spam.example',
        'name' => 'A',
        'phone' => '+7 999',
        'email' => 'broken@',
        'privacy' => '',
    ]), 1722945600, 'test-token');
    same(false, $result['ok']);
    foreach (['csrf_token', 'started_at', 'website', 'name', 'phone', 'email', 'privacy'] as $field) {
        truthy(isset($result['errors'][$field]));
    }
});

test('lead validator strips markup controls and external source URLs', function (): void {
    $result = validate_lead_request(valid_lead_payload([
        'name' => "<b>Иван</b>\r\nBcc: bad@example.com",
        'message' => '<script>alert(1)</script>Нужен расчёт',
        'source' => 'https://evil.example/phish',
    ]), 1722945600, 'test-token');
    truthy($result['ok']);
    same(false, str_contains($result['data']['name'], '<'));
    same(false, str_contains($result['data']['name'], "\r"));
    same(false, str_contains($result['data']['message'], '<script>'));
    same('/', $result['data']['source']);
});

test('csrf token can be issued verified and rotated', function (): void {
    $_SESSION = [];
    $first = issue_csrf_token();
    truthy(strlen($first) >= 32);
    truthy(verify_csrf_token($first));
    rotate_csrf_token();
    $second = issue_csrf_token();
    truthy($first !== $second);
});

test('mailer abstraction receives sanitized lead data', function (): void {
    $fake = new class implements LeadMailer {
        public array $received = [];
        public function send(array $lead): void { $this->received = $lead; }
    };
    $lead = validate_lead_request(valid_lead_payload(), 1722945600, 'test-token')['data'];
    deliver_lead($lead, $fake);
    same('+79374351700', $fake->received['phone']);
});

test('native mailer sends a utf8 lead to the approved mailbox', function (): void {
    $calls = [];
    $mailer = new NativeMailLeadMailer(
        'oksmaprom@yandex.ru',
        'oksmaprom@yandex.ru',
        'Сайт ОКСМА',
        static function (string $to, string $subject, string $message, string $headers) use (&$calls): bool {
            $calls[] = compact('to', 'subject', 'message', 'headers');
            return true;
        },
    );
    $lead = validate_lead_request(valid_lead_payload(), 1722945600, 'test-token')['data'];

    $mailer->send($lead);

    same(1, count($calls));
    same('oksmaprom@yandex.ru', $calls[0]['to']);
    truthy(str_contains($calls[0]['subject'], '=?UTF-8?B?'));
    truthy(str_contains($calls[0]['message'], 'Иван Петров'));
    truthy(str_contains($calls[0]['message'], '+79374351700'));
    truthy(str_contains($calls[0]['headers'], 'From:'));
    truthy(str_contains($calls[0]['headers'], '<oksmaprom@yandex.ru>'));
    truthy(str_contains($calls[0]['headers'], 'Reply-To:'));
    truthy(str_contains($calls[0]['headers'], '<ivan@example.ru>'));
    same(false, str_contains($calls[0]['headers'], "\nBcc:"));
});

test('native mailer omits reply-to when the visitor email is empty', function (): void {
    $headers = '';
    $mailer = new NativeMailLeadMailer(
        'oksmaprom@yandex.ru',
        'oksmaprom@yandex.ru',
        'Сайт ОКСМА',
        static function (string $to, string $subject, string $message, string $capturedHeaders) use (&$headers): bool {
            $headers = $capturedHeaders;
            return true;
        },
    );
    $lead = validate_lead_request(valid_lead_payload(['email' => '']), 1722945600, 'test-token')['data'];

    $mailer->send($lead);

    same(false, str_contains($headers, 'Reply-To:'));
});

test('native mailer reports a rejected system mail handoff', function (): void {
    $mailer = new NativeMailLeadMailer(
        'oksmaprom@yandex.ru',
        'oksmaprom@yandex.ru',
        'Сайт ОКСМА',
        static fn (string $to, string $subject, string $message, string $headers): bool => false,
    );
    $lead = validate_lead_request(valid_lead_payload(), 1722945600, 'test-token')['data'];
    $thrown = false;

    try {
        $mailer->send($lead);
    } catch (RuntimeException $error) {
        $thrown = true;
        same('Native mail transport rejected the lead.', $error->getMessage());
    }

    truthy($thrown);
});

test('native mailer rejects line breaks in configured headers', function (): void {
    $thrown = false;

    try {
        new NativeMailLeadMailer(
            'oksmaprom@yandex.ru',
            'oksmaprom@yandex.ru',
            "Сайт ОКСМА\r\nBcc: attacker@example.ru",
        );
    } catch (InvalidArgumentException) {
        $thrown = true;
    }

    truthy($thrown);
});

test('application mailer uses the public contact without smtp configuration', function () use ($root): void {
    $calls = [];
    $capture = static function (string $to, string $subject, string $message, string $headers) use (&$calls): bool {
        $calls[] = compact('to', 'subject', 'message', 'headers');
        return true;
    };
    $mailer = create_app_lead_mailer([
        'name' => 'ОКСМА',
        'email' => 'oksmaprom@yandex.ru',
    ], $capture);
    $lead = validate_lead_request(valid_lead_payload(), 1722945600, 'test-token')['data'];

    $mailer->send($lead);

    same('oksmaprom@yandex.ru', $calls[0]['to']);
    truthy(str_contains($calls[0]['headers'], '<oksmaprom@yandex.ru>'));
    $submit = file_get_contents($root . '/submit.php');
    same(false, str_contains($submit, 'config/mail.php'));
    same(false, str_contains($submit, 'SmtpLeadMailer'));
    truthy(str_contains($submit, 'create_app_lead_mailer(app_config())'));
});
