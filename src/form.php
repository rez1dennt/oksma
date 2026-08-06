<?php

declare(strict_types=1);

function issue_csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(string $token): bool
{
    $stored = $_SESSION['csrf_token'] ?? '';
    return is_string($stored) && $stored !== '' && hash_equals($stored, $token);
}

function rotate_csrf_token(): void
{
    unset($_SESSION['csrf_token']);
}

function normalize_lead_phone(string $value): string
{
    $digits = preg_replace('/\D+/', '', $value) ?? '';
    if (str_starts_with($digits, '8')) {
        $digits = '7' . substr($digits, 1);
    } elseif ($digits !== '' && !str_starts_with($digits, '7')) {
        $digits = '7' . $digits;
    }
    $digits = substr($digits, 0, 11);
    return preg_match('/^7\d{10}$/', $digits) === 1 ? '+' . $digits : '';
}

function clean_lead_line(mixed $value, int $maxLength): string
{
    $clean = strip_tags((string) $value);
    $clean = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $clean) ?? '';
    $clean = preg_replace('/\s+/u', ' ', $clean) ?? '';
    return text_slice(trim($clean), $maxLength);
}

function clean_lead_message(mixed $value, int $maxLength): string
{
    $clean = strip_tags((string) $value);
    $clean = str_replace(["\r\n", "\r"], "\n", $clean);
    $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean) ?? '';
    return text_slice(trim($clean), $maxLength);
}

function text_slice(string $value, int $maxLength): string
{
    return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
}

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function clean_lead_source(mixed $value): string
{
    $source = clean_lead_line($value, 500);
    if ($source === '' || !str_starts_with($source, '/') || str_starts_with($source, '//')) {
        return '/';
    }
    return $source;
}

function validate_lead_request(array $input, int $now, string $expectedCsrf): array
{
    $data = [
        'name' => clean_lead_line($input['name'] ?? '', 80),
        'phone' => normalize_lead_phone((string) ($input['phone'] ?? '')),
        'email' => clean_lead_line($input['email'] ?? '', 160),
        'message' => clean_lead_message($input['message'] ?? '', 3000),
        'source' => clean_lead_source($input['source'] ?? '/'),
    ];
    $errors = [];

    $csrf = (string) ($input['csrf_token'] ?? '');
    if ($expectedCsrf === '' || $csrf === '' || !hash_equals($expectedCsrf, $csrf)) {
        $errors['csrf_token'] = 'Сессия формы истекла. Обновите страницу и повторите отправку.';
    }

    $startedAt = filter_var($input['started_at'] ?? null, FILTER_VALIDATE_INT);
    if ($startedAt === false || $startedAt > $now || $now - $startedAt < 3) {
        $errors['started_at'] = 'Форма отправлена слишком быстро.';
    }
    if (clean_lead_line($input['website'] ?? '', 200) !== '') {
        $errors['website'] = 'Запрос отклонён.';
    }
    if (text_length($data['name']) < 2) {
        $errors['name'] = 'Укажите имя — минимум 2 символа.';
    }
    if ($data['phone'] === '') {
        $errors['phone'] = 'Введите российский номер телефона полностью.';
    }
    if ($data['email'] !== '' && filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Проверьте адрес электронной почты.';
    }
    if (($input['privacy'] ?? '') !== '1') {
        $errors['privacy'] = 'Необходимо согласие с политикой конфиденциальности.';
    }

    return ['ok' => $errors === [], 'errors' => $errors, 'data' => $data];
}
