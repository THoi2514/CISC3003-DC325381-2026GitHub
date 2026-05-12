<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/layout.php';

$token = (string) ($_GET['token'] ?? '');
if ($token === '' || strlen($token) !== 64 || !ctype_xdigit($token)) {
    page_start('Reset password');
    echo '<p class="flash err">Invalid reset link.</p>';
    page_end();
    exit;
}

page_start('Scenario C — Choose a new password');
?>
<form method="post" action="process_reset_password.php">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
    <label for="password">New password</label>
    <input type="password" id="password" name="password" required minlength="8" maxlength="128" autocomplete="new-password">
    <label for="password_confirm">Confirm new password</label>
    <input type="password" id="password_confirm" name="password_confirm" required minlength="8" maxlength="128" autocomplete="new-password">
    <p><button type="submit">Update password</button></p>
</form>
<?php
page_end();
