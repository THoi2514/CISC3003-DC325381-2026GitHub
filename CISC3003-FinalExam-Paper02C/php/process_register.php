<?php
/**
 * C.02 server validation, C.03 DB insert, C.08 activation email (must verify before login).
 */
declare(strict_types=1);

session_start();

require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php', true, 303);
    exit;
}

$fullName = trim((string) ($_POST['full_name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

$errors = [];
if ($fullName === '' || mb_strlen($fullName) < 2) {
    $errors[] = 'Please enter your full name.';
}
$emailOk = filter_var($email, FILTER_VALIDATE_EMAIL);
if ($emailOk === false) {
    $errors[] = 'Please enter a valid email address.';
} else {
    $email = $emailOk;
}

if (mb_strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}
if (!hash_equals($password, $passwordConfirm)) {
    $errors[] = 'Password confirmation does not match.';
}

if ($errors !== []) {
    $_SESSION['register_errors'] = $errors;
    $_SESSION['register_old'] = $_POST;
    header('Location: register.php', true, 303);
    exit;
}

// duplicate email check (also covered by unique index)
$stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    $_SESSION['register_errors'] = ['That email is already registered.'];
    $_SESSION['register_old'] = $_POST;
    header('Location: register.php', true, 303);
    exit;
}
$stmt->close();

$hash = password_hash($password, PASSWORD_DEFAULT);
$verifyToken = bin2hex(random_bytes(32));

$ins = db()->prepare(
    'INSERT INTO users (full_name, email, password_hash, verify_token, verify_expires)
     VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 DAY))'
);
$ins->bind_param('ssss', $fullName, $email, $hash, $verifyToken);
$ins->execute();
$ins->close();

$link = rtrim(PUBLIC_SITE_BASE, '/') . '/php/activate.php?token=' . urlencode($verifyToken);
$subject = 'Confirm your email for CISC3003 Scenario C';
$html = '<p>Hello ' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . ',</p>'
    . '<p>Please confirm your email by clicking the link below:</p>'
    . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">Activate my account</a></p>'
    . '<p>If you did not sign up, you can ignore this message.</p>';
$text = "Hello {$fullName},\n\nOpen this link to activate:\n{$link}\n";

try {
    send_html_mail($email, $fullName, $subject, $html, $text);
    $_SESSION['info'] = 'Account created. Check your email to confirm before signing in.';
} catch (Throwable $e) {
    $_SESSION['info'] = 'Account created, but the confirmation email could not be sent. Use “Resend confirmation” after configuring SMTP.';
}

header('Location: login.php', true, 303);
exit;
