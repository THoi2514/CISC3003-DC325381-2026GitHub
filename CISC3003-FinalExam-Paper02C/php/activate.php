<?php
/**
 * C.08 email confirmation handler.
 */
declare(strict_types=1);

session_start();

require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/layout.php';

$token = (string) ($_GET['token'] ?? '');
if ($token === '' || strlen($token) !== 64 || !ctype_xdigit($token)) {
    page_start('Activation');
    echo '<p class="flash err">Invalid activation link.</p>';
    page_end();
    exit;
}

$stmt = db()->prepare(
    'SELECT id FROM users WHERE verify_token = ? AND verify_expires > NOW() AND email_verified_at IS NULL LIMIT 1'
);
$stmt->bind_param('s', $token);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!is_array($row)) {
    page_start('Activation');
    echo '<p class="flash err">This activation link is expired or already used.</p>';
    page_end();
    exit;
}

$id = (int) $row['id'];
$upd = db()->prepare('UPDATE users SET email_verified_at = NOW(), verify_token = NULL, verify_expires = NULL WHERE id = ?');
$upd->bind_param('i', $id);
$upd->execute();
$upd->close();

page_start('Activation complete');
?>
<div class="flash ok">Your email is confirmed. You can sign in now.</div>
<p><a href="login.php">Go to sign in</a></p>
<?php
page_end();
