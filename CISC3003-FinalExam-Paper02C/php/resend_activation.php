<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/layout.php';

$old = $_SESSION['login_old'] ?? [];
$resendErrors = $_SESSION['resend_errors'] ?? [];
$loginErrors = $_SESSION['login_errors'] ?? [];
unset($_SESSION['resend_errors'], $_SESSION['login_errors']);

page_start('Scenario C — Resend activation email');
?>
<p>If your account is not yet verified, enter your email and we will send a fresh activation link.</p>

<?php if ($loginErrors !== []): ?>
    <div class="flash err" role="alert">
        <ul>
            <?php foreach ($loginErrors as $err): ?>
                <li><?= htmlspecialchars((string) $err, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($resendErrors !== []): ?>
    <div class="flash err" role="alert">
        <ul>
            <?php foreach ($resendErrors as $err): ?>
                <li><?= htmlspecialchars((string) $err, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="process_resend_activation.php">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required maxlength="190"
           value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <p><button type="submit">Send activation email</button></p>
</form>
<?php
page_end();
