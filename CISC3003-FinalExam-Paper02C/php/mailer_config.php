<?php
/**
 * Copy this file to `mailer_config.php` and fill in real SMTP credentials (Gmail App Password, etc.).
 * @return array<string, mixed>
 */
declare(strict_types=1);

return [
    'from_email' => 'th2555114@gmail.com',
    'from_name' => 'CISC3003 Paper02B',
    'to_email' => 'th2555114@gmail.com',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls', // PHPMailer::ENCRYPTION_STARTTLS
    'smtp_user' => 'th2555114@gmail.com',
    'smtp_pass' => 'pnueyqweebzzntuo',
];
