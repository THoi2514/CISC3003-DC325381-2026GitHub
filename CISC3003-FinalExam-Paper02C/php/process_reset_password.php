<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot_password.php', true, 303);
    exit;
}

$token = (string) ($_POST['token'] ?? '');
$password = (string) ($_POST['password'] ?? '');
$passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

if ($token === '' || strlen($token) !== 64 || !ctype_xdigit($token)) {
    $_SESSION['login_errors'] = ['Invalid reset token.'];
    header('Location: forgot_password.php', true, 303);
    exit;
}

if (mb_strlen($password) < 8 || !hash_equals($password, $passwordConfirm)) {
    $_SESSION['login_errors'] = ['Password must be at least 8 characters and match confirmation.'];
    header('Location: reset_password.php?token=' . urlencode($token), true, 303);
    exit;
}

$stmt = db()->prepare('SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1');
$stmt->bind_param('s', $token);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!is_array($row)) {
    $_SESSION['login_errors'] = ['Reset link is invalid or expired.'];
    header('Location: forgot_password.php', true, 303);
    exit;
}

$id = (int) $row['id'];
$hash = password_hash($password, PASSWORD_DEFAULT);
$upd = db()->prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?');
$upd->bind_param('si', $hash, $id);
$upd->execute();
$upd->close();

$_SESSION['info'] = 'Password updated. You can sign in with your new password.';
header('Location: login.php', true, 303);
exit;
