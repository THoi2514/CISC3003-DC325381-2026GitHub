<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot_password.php', true, 303);
    exit;
}

$email = trim((string) ($_POST['email'] ?? ''));
$emailOk = filter_var($email, FILTER_VALIDATE_EMAIL);
if ($emailOk === false) {
    $_SESSION['info'] = 'If the account exists, a reset email has been sent.';
    header('Location: login.php', true, 303);
    exit;
}
$email = $emailOk;

$stmt = db()->prepare('SELECT id, full_name FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

$_SESSION['info'] = 'If the account exists, a reset email has been sent.';

if (!is_array($user)) {
    header('Location: login.php', true, 303);
    exit;
}

$token = bin2hex(random_bytes(32));
$uid = (int) $user['id'];
$upd = db()->prepare('UPDATE users SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?');
$upd->bind_param('si', $token, $uid);
$upd->execute();
$upd->close();

$fullName = (string) $user['full_name'];
$link = rtrim(PUBLIC_SITE_BASE, '/') . '/php/reset_password.php?token=' . urlencode($token);
$subject = 'Reset your Scenario C password';
$html = '<p>Hello ' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . ',</p>'
    . '<p>Reset your password using this link (valid for one hour):</p>'
    . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">Reset password</a></p>'
    . '<p>If you did not request this, you can ignore this email.</p>';
$text = "Reset password:\n{$link}\n";

try {
    send_html_mail($email, $fullName, $subject, $html, $text);
} catch (Throwable $e) {
    // swallow
}

header('Location: login.php', true, 303);
exit;
