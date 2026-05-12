<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: resend_activation.php', true, 303);
    exit;
}

$email = trim((string) ($_POST['email'] ?? ''));
$emailOk = filter_var($email, FILTER_VALIDATE_EMAIL);
if ($emailOk === false) {
    $_SESSION['resend_errors'] = ['Invalid email.'];
    header('Location: resend_activation.php', true, 303);
    exit;
}
$email = $emailOk;

$stmt = db()->prepare('SELECT id, full_name, email_verified_at FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!is_array($user)) {
    $_SESSION['info'] = 'If the account exists and is not verified, an email has been sent.';
    header('Location: login.php', true, 303);
    exit;
}

if ($user['email_verified_at'] !== null) {
    $_SESSION['info'] = 'This account is already verified — you can sign in.';
    header('Location: login.php', true, 303);
    exit;
}

$verifyToken = bin2hex(random_bytes(32));
$upd = db()->prepare('UPDATE users SET verify_token = ?, verify_expires = DATE_ADD(NOW(), INTERVAL 1 DAY) WHERE id = ?');
$uid = (int) $user['id'];
$upd->bind_param('si', $verifyToken, $uid);
$upd->execute();
$upd->close();

$fullName = (string) $user['full_name'];
$link = rtrim(PUBLIC_SITE_BASE, '/') . '/php/activate.php?token=' . urlencode($verifyToken);
$subject = 'Confirm your email for CISC3003 Scenario C';
$html = '<p>Hello ' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . ',</p>'
    . '<p>Please confirm your email:</p>'
    . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">Activate my account</a></p>';
$text = "Hello {$fullName},\n\nActivate:\n{$link}\n";

try {
    send_html_mail($email, $fullName, $subject, $html, $text);
    $_SESSION['info'] = 'Activation email sent. Check inbox and spam folder.';
} catch (Throwable $e) {
    $_SESSION['info'] = 'Could not send email. Ensure php/mailer_config.php exists (same SMTP as Paper02B). Technical: ' . $e->getMessage();
}
header('Location: login.php', true, 303);
exit;
