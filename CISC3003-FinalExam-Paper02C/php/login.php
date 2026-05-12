<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/layout.php';

$errors = $_SESSION['login_errors'] ?? [];
$old = $_SESSION['login_old'] ?? [];
unset($_SESSION['login_errors']);

page_start('Scenario C — Sign in');
?>
<?php if (!empty($_SESSION['info'])): ?>
    <div class="flash ok"><?= htmlspecialchars((string) $_SESSION['info'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php unset($_SESSION['info']); ?>
<?php endif; ?>

<?php if ($errors !== []): ?>
    <div class="flash err" role="alert">
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars((string) $err, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form id="login-form" method="post" action="process_login.php" novalidate>
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required maxlength="190" autocomplete="username"
           value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required minlength="8" maxlength="128" autocomplete="current-password">

    <p>
        <button type="submit">Sign in</button>
        <a href="forgot_password.php">Forgot password?</a>
    </p>
</form>
<?php
unset($_SESSION['login_old']);
page_end();
