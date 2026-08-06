<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

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

final class SmtpLeadMailer implements LeadMailer
{
    public function __construct(private readonly array $config)
    {
    }

    public function send(array $lead): void
    {
        if (!class_exists(PHPMailer::class)) {
            throw new RuntimeException('PHPMailer is not installed.');
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string) $this->config['host'];
        $mail->Port = (int) $this->config['port'];
        $mail->SMTPAuth = true;
        $mail->Username = (string) $this->config['username'];
        $mail->Password = (string) $this->config['password'];
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->SMTPSecure = ($this->config['encryption'] ?? 'smtps') === 'starttls'
            ? PHPMailer::ENCRYPTION_STARTTLS
            : PHPMailer::ENCRYPTION_SMTPS;
        $mail->setFrom((string) $this->config['from_address'], (string) $this->config['from_name']);
        $mail->addAddress((string) $this->config['recipient']);
        if ($lead['email'] !== '') {
            $mail->addReplyTo($lead['email'], $lead['name']);
        }
        $mail->Subject = 'Заявка с сайта ОКСМА — ' . $lead['name'];
        $mail->Body = lead_email_body($lead);
        $mail->AltBody = $mail->Body;
        $mail->send();
    }
}
