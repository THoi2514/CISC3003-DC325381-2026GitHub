<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php', true, 303);
    exit;
}

$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

$errors = [];
$emailOk = filter_var($email, FILTER_VALIDATE_EMAIL);
if ($emailOk === false) {
    $errors[] = 'Please provide a valid email.';
} else {
    $email = $emailOk;
}
if ($password === '') {
    $errors[] = 'Password is required.';
}

if ($errors !== []) {
    $_SESSION['login_errors'] = $errors;
    $_SESSION['login_old'] = $_POST;
    header('Location: login.php', true, 303);
    exit;
}

$stmt = db()->prepare(
    'SELECT id, full_name, email, password_hash, email_verified_at
     FROM users WHERE email = ? LIMIT 1'
);
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!is_array($user) || !password_verify($password, (string) $user['password_hash'])) {
    $_SESSION['login_errors'] = ['Invalid email or password.'];
    $_SESSION['login_old'] = $_POST;
    header('Location: login.php', true, 303);
    exit;
}

if ($user['email_verified_at'] === null) {
    $_SESSION['login_errors'] = ['Please confirm your email before signing in. You can resend the activation email below.'];
    $_SESSION['login_old'] = $_POST;
    header('Location: resend_activation.php', true, 303);
    exit;
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['user_name'] = (string) $user['full_name'];
$_SESSION['user_email'] = (string) $user['email'];

header('Location: dashboard.php', true, 303);
exit;
