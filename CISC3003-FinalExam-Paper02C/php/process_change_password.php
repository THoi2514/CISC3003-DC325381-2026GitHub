<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php', true, 303);
    exit;
}

$current = (string) ($_POST['current_password'] ?? '');
$new = (string) ($_POST['new_password'] ?? '');
$new2 = (string) ($_POST['new_password_confirm'] ?? '');

if (mb_strlen($new) < 8 || !hash_equals($new, $new2)) {
    $_SESSION['dash_error'] = 'New password must be at least 8 characters and match confirmation.';
    header('Location: dashboard.php', true, 303);
    exit;
}

$user = find_user_by_id((int) $_SESSION['user_id']);
if ($user === null || !password_verify($current, (string) $user['password_hash'])) {
    $_SESSION['dash_error'] = 'Current password is incorrect.';
    header('Location: dashboard.php', true, 303);
    exit;
}

$hash = password_hash($new, PASSWORD_DEFAULT);
$id = (int) $user['id'];
$stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
$stmt->bind_param('si', $hash, $id);
$stmt->execute();
$stmt->close();

$_SESSION['dash_ok'] = 'Password updated.';
header('Location: dashboard.php', true, 303);
exit;
