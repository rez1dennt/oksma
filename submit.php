<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$wantsJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

function form_response(int $status, array $payload, bool $json): never
{
    http_response_code($status);
    if ($json) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        $query = ($payload['ok'] ?? false) ? 'sent=1' : 'form=error';
        header('Location: /?' . $query . '#request', true, 303);
    }
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    form_response(405, ['ok' => false, 'message' => 'Метод не поддерживается.'], $wantsJson);
}

$expectedCsrf = is_string($_SESSION['csrf_token'] ?? null) ? $_SESSION['csrf_token'] : '';
$result = validate_lead_request($_POST, time(), $expectedCsrf);
if (!$result['ok']) {
    form_response(422, [
        'ok' => false,
        'message' => 'Проверьте заполнение формы и повторите отправку.',
        'errors' => $result['errors'],
    ], $wantsJson);
}

$mailConfigPath = __DIR__ . '/config/mail.php';
if (!is_file($mailConfigPath)) {
    form_response(503, ['ok' => false, 'message' => 'Отправка ещё не настроена. Позвоните нам по указанному номеру.'], $wantsJson);
}

try {
    $mailConfig = require $mailConfigPath;
    deliver_lead($result['data'], new SmtpLeadMailer($mailConfig));
    rotate_csrf_token();
    form_response(200, ['ok' => true, 'message' => 'Спасибо! Заявка отправлена, мы свяжемся с вами.'], $wantsJson);
} catch (Throwable $error) {
    error_log('Lead form delivery failed: ' . $error->getMessage());
    form_response(503, ['ok' => false, 'message' => 'Не удалось отправить заявку. Позвоните нам или попробуйте ещё раз позже.'], $wantsJson);
}
