<?php
declare(strict_types=1);

require_once __DIR__ . '/site_config.php';

function layout_web_base(): string
{
    $sn = $_SERVER['SCRIPT_NAME'] ?? '';
    return str_contains($sn, '/php/') ? '../' : '';
}

function layout_php_base(): string
{
    $sn = $_SERVER['SCRIPT_NAME'] ?? '';
    return str_contains($sn, '/php/') ? '' : 'php/';
}

/**
 * @param list<string> $extraStyles
 */
function page_start(string $title, array $extraStyles = []): void
{
    $wb = layout_web_base();
    $pb = layout_php_base();
    $loggedIn = !empty($_SESSION['user_id']);
    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($wb . 'css/styles.css', ENT_QUOTES, 'UTF-8') ?>">
    <?php foreach ($extraStyles as $href): ?>
        <?php
        $out = $href;
        if (!str_starts_with($href, 'http') && !str_starts_with($href, '/')) {
            $out = $wb . $href;
        }
        ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($out, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
</head>
<body>
<header class="site-header">
    <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
    <nav>
        <a href="<?= htmlspecialchars($wb . 'index.php', ENT_QUOTES, 'UTF-8') ?>">Home</a>
        <?php if ($loggedIn): ?>
            <a href="<?= htmlspecialchars($pb . 'dashboard.php', ENT_QUOTES, 'UTF-8') ?>">Dashboard</a>
            <a href="<?= htmlspecialchars($pb . 'logout.php', ENT_QUOTES, 'UTF-8') ?>">Logout</a>
        <?php else: ?>
            <a href="<?= htmlspecialchars($pb . 'register.php', ENT_QUOTES, 'UTF-8') ?>">Sign up</a>
            <a href="<?= htmlspecialchars($pb . 'login.php', ENT_QUOTES, 'UTF-8') ?>">Sign in</a>
            <a href="<?= htmlspecialchars($pb . 'forgot_password.php', ENT_QUOTES, 'UTF-8') ?>">Reset password</a>
        <?php endif; ?>
    </nav>
</header>
<main>
<?php
}

function page_end(): void
{
    $wb = layout_web_base();
    ?>
</main>
<footer class="site-footer">
    <p><?= htmlspecialchars(FOOTER_LINE, ENT_QUOTES, 'UTF-8') ?></p>
</footer>
<script src="<?= htmlspecialchars($wb . 'js/script.js', ENT_QUOTES, 'UTF-8') ?>" defer></script>
</body>
</html>
<?php
}
