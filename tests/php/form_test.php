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
