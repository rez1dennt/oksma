<?php

declare(strict_types=1);

interface LeadMailer
{
    public function send(array $lead): void;
}

function deliver_lead(array $lead, LeadMailer $mailer): void
{
    $mailer->send($lead);
}

function lead_email_body(array $lead): string
{
    return implode("\n", [
        'Новая заявка с сайта ОКСМА',
        '',
        'Имя: ' . $lead['name'],
        'Телефон: ' . $lead['phone'],
        'Email: ' . ($lead['email'] !== '' ? $lead['email'] : 'не указан'),
        'Страница: ' . $lead['source'],
        '',
        'Сообщение:',
        $lead['message'] !== '' ? $lead['message'] : 'не указано',
    ]);
}

function reject_mail_header_breaks(string $value): void
{
    if (str_contains($value, "\r") || str_contains($value, "\n")) {
        throw new InvalidArgumentException('Mail header values cannot contain line breaks.');
    }
}

function validate_mailbox(string $address): void
{
    reject_mail_header_breaks($address);
    if (filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
        throw new InvalidArgumentException('Invalid mail address.');
    }
}

function encode_mail_header(string $value): string
{
    reject_mail_header_breaks($value);
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function build_lead_mail_headers(array $lead, string $fromAddress, string $fromName): array
{
    validate_mailbox($fromAddress);
    reject_mail_header_breaks($fromName);

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'From: ' . encode_mail_header($fromName) . ' <' . $fromAddress . '>',
    ];

    $replyAddress = (string) ($lead['email'] ?? '');
    if ($replyAddress !== '') {
        validate_mailbox($replyAddress);
        $replyName = (string) ($lead['name'] ?? 'Посетитель сайта');
        $headers[] = 'Reply-To: ' . encode_mail_header($replyName) . ' <' . $replyAddress . '>';
    }

    return $headers;
}

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
            encode_mail_header('Заявка с сайта ОКСМА — ' . (string) $lead['name']),
            lead_email_body($lead),
            implode("\r\n", $headers),
        );

        if (!$sent) {
            throw new RuntimeException('Native mail transport rejected the lead.');
        }
    }
}
