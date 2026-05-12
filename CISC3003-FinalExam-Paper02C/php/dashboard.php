<?php
declare(strict_types=1);

session_start();

$dashOk = $_SESSION['dash_ok'] ?? '';
$dashErr = $_SESSION['dash_error'] ?? '';
unset($_SESSION['dash_ok'], $_SESSION['dash_error']);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

require_login();

$user = find_user_by_id((int) $_SESSION['user_id']);
if ($user === null) {
    session_destroy();
    header('Location: login.php', true, 303);
    exit;
}

page_start('Scenario C — Dashboard', ['css/dashboad.css']);
?>
<?php if ($dashOk !== ''): ?>
    <div class="flash ok"><?= htmlspecialchars($dashOk, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($dashErr !== ''): ?>
    <div class="flash err"><?= htmlspecialchars($dashErr, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="dash-card">
    <h2>Welcome, <?= htmlspecialchars((string) $user['full_name'], ENT_QUOTES, 'UTF-8') ?></h2>
    <p class="dash-meta"><strong>Member since:</strong> <?= htmlspecialchars((string) $user['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
    <p class="field-hint">C.09: quick services available after sign-in.</p>
</section>

<section class="dash-card">
    <h3>Profile</h3>
    <p><strong>Email:</strong> <?= htmlspecialchars((string) $user['email'], ENT_QUOTES, 'UTF-8') ?></p>
</section>

<section class="dash-card">
    <h3>Change password</h3>
    <form method="post" action="process_change_password.php" id="change-password-form">
        <label for="current_password">Current password</label>
        <input type="password" id="current_password" name="current_password" required minlength="8" maxlength="128" autocomplete="current-password">

        <label for="new_password">New password</label>
        <input type="password" id="new_password" name="new_password" required minlength="8" maxlength="128" autocomplete="new-password">

        <label for="new_password_confirm">Confirm new password</label>
        <input type="password" id="new_password_confirm" name="new_password_confirm" required minlength="8" maxlength="128" autocomplete="new-password">

        <p><button type="submit">Update password</button></p>
    </form>
</section>

<section class="dash-card">
    <h3>Shortcuts</h3>
    <ul>
        <li><a href="https://watercss.kognise.dev/" target="_blank" rel="noopener">Water.css reference</a></li>
        <li><a href="forgot_password.php">Password reset via email</a></li>
        <li><a href="logout.php">Sign out</a></li>
    </ul>
</section>
<?php
page_end();
