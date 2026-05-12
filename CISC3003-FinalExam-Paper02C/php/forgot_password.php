<?php
/**
 * C.07 password reset request page.
 */
declare(strict_types=1);

session_start();

require_once __DIR__ . '/layout.php';

page_start('Scenario C — Forgot password');
?>
<p>Enter your account email. If it exists, a reset link will be sent (expires in one hour).</p>
<form method="post" action="process_forgot_password.php">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required maxlength="190" autocomplete="email">
    <p><button type="submit">Send reset link</button></p>
</form>
<?php
page_end();
