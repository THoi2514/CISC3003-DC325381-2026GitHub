<?php
/**
 * C.01 signup page; C.06 Ajax email check wired in js/script.js
 */
declare(strict_types=1);

session_start();

require_once __DIR__ . '/layout.php';

$errors = $_SESSION['register_errors'] ?? [];
$old = $_SESSION['register_old'] ?? [];
unset($_SESSION['register_errors']);

page_start('Scenario C — Create account');
?>
<?php if ($errors !== []): ?>
    <div class="flash err" role="alert">
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars((string) $err, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form id="signup-form" method="post" action="process_register.php" novalidate autocomplete="off">
    <label for="full_name">Full name</label>
    <input type="text" id="full_name" name="full_name" required minlength="2" maxlength="120" autocomplete="name"
           value="<?= htmlspecialchars((string) ($old['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label for="email">Email</label>
    <input type="email" id="email" name="email" required maxlength="190" autocomplete="email"
           value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <p id="email-ajax-hint" class="field-hint" aria-live="polite"></p>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required minlength="8" maxlength="128" autocomplete="new-password">

    <label for="password_confirm">Confirm password</label>
    <input type="password" id="password_confirm" name="password_confirm" required minlength="8" maxlength="128" autocomplete="new-password">

    <p>
        <button type="submit">Create account</button>
        <a class="secondary" href="login.php">Already have an account?</a>
    </p>
</form>
<?php
unset($_SESSION['register_old']);
page_end();
