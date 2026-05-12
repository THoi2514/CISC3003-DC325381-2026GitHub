<?php
/**
 * B.05 POST / redirect / GET: this script never renders HTML after a POST.
 * B.03 send mail via PHPMailer; B.04 stores debug output on failure.
 */
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php', true, 303);
    exit;
}

$return = (string) ($_POST['_return'] ?? 'index');
$returnPage = $return === 'register' ? 'register.php' : '../index.php';

$name = trim((string) ($_POST['sender_name'] ?? ''));
$email = trim((string) ($_POST['sender_email'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

$errors = [];
if ($name === '' || mb_strlen($name) < 2) {
    $errors[] = 'Name is required.';
}
$emailOk = filter_var($email, FILTER_VALIDATE_EMAIL);
if ($emailOk === false) {
    $errors[] = 'Valid email is required.';
} else {
    $email = $emailOk;
}
if ($subject === '' || mb_strlen($subject) < 3) {
    $errors[] = 'Subject is required.';
}
if ($message === '' || mb_strlen($message) < 10) {
    $errors[] = 'Message must be at least 10 characters.';
}

if ($errors !== []) {
    $_SESSION['contact_errors'] = $errors;
    $_SESSION['contact_old'] = $_POST;
    header('Location: ' . $returnPage, true, 303);
    exit;
}

$configFile = __DIR__ . '/mailer_config.php';
if (!is_file($configFile)) {
    $_SESSION['mail_error'] = 'Missing php/mailer_config.php (copy mailer_config.sample.php).';
    $_SESSION['mail_debug'] = '';
    header('Location: debug_mail.php', true, 303);
    exit;
}

/** @var array<string, mixed> $cfg */
$cfg = require $configFile;

$mail = new PHPMailer(true);
$debugBuffer = '';

try {
    $mail->isSMTP();
    $mail->Host = (string) $cfg['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = (string) $cfg['smtp_user'];
    $mail->Password = (string) $cfg['smtp_pass'];
    $mail->SMTPSecure = (string) $cfg['smtp_secure'];
    $mail->Port = (int) $cfg['smtp_port'];

    $mail->SMTPDebug = 2;
    $mail->Debugoutput = static function (string $str, int $level) use (&$debugBuffer): void {
        $debugBuffer .= $str;
    };

    $mail->setFrom((string) $cfg['from_email'], (string) $cfg['from_name']);
    $mail->addAddress((string) $cfg['to_email']);
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'), false);
    $mail->AltBody = $message;

    $mail->send();

    // Optional persistence (prepared statement)
    $stmt = db()->prepare('INSERT INTO contact_log (sender_name, sender_email, subject, outcome) VALUES (?, ?, ?, ?)');
    $outcome = 'sent';
    $stmt->bind_param('ssss', $name, $email, $subject, $outcome);
    $stmt->execute();
    $stmt->close();

    $sentLocation = $return === 'register' ? 'register.php?sent=1' : '../index.php?sent=1';
    header('Location: ' . $sentLocation, true, 303);
    exit;
} catch (Throwable $e) {
    $_SESSION['mail_error'] = $mail->ErrorInfo !== '' ? $mail->ErrorInfo : $e->getMessage();
    $_SESSION['mail_debug'] = $debugBuffer;

    try {
        $stmt = db()->prepare('INSERT INTO contact_log (sender_name, sender_email, subject, outcome) VALUES (?, ?, ?, ?)');
        $outcome = 'failed';
        $stmt->bind_param('ssss', $name, $email, $subject, $outcome);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $ignored) {
        // ignore logging failure
    }

    header('Location: debug_mail.php', true, 303);
    exit;
}
