<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * @param array<string, string> $headers optional extra headers
 */
function send_html_mail(string $toEmail, string $toName, string $subject, string $htmlBody, string $altBody): void
{
    $configFile = __DIR__ . '/mailer_config.php';
    if (!is_file($configFile)) {
        throw new RuntimeException('Missing php/mailer_config.php (copy mailer_config.sample.php).');
    }
    /** @var array<string, string> $cfg */
    $cfg = require $configFile;

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $cfg['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $cfg['smtp_user'];
    $mail->Password = $cfg['smtp_pass'];
    $mail->SMTPSecure = $cfg['smtp_secure'];
    $mail->Port = (int) $cfg['smtp_port'];

    $mail->setFrom($cfg['from_email'], $cfg['from_name']);
    $mail->addAddress($toEmail, $toName);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $htmlBody;
    $mail->AltBody = $altBody;
    $mail->send();
}
