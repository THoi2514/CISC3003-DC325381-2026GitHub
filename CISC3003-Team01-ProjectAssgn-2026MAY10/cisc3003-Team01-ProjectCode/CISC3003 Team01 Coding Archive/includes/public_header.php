<?php
declare(strict_types=1);

require_once __DIR__ . '/public_paths.php';

$b = $publicHeaderBase ?? '';
$activePage = $activePage ?? '';
$navUserMode = $navUserMode ?? 'auto';

if ($navUserMode === 'auto') {
    $navUserMode = 'none';
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['user_id'])) {
        $role = (string)($_SESSION['role'] ?? 'student');
        if ($role === 'admin') {
            $navUserMode = 'admin';
        } elseif ($role === 'staff') {
            $navUserMode = 'staff';
        } else {
            $navUserMode = 'student';
        }
    }
}

$home = $b . 'home.php';
$services = $b . 'services.php';
$about = $b . 'about.php';
$howto = $b . 'home.php#howto';
$contact = $b . 'contact.php';
$login = $b . 'login.php';
$dashboard = $b . 'dashboard.php';
$account = $b . 'account.php';
$forum = $b . 'forum.php';
$logout = $b . 'api/logout.php';
$staffHome = $b . 'staff/home.php';
$adminDash = $b . 'admin/admin_dashboard.php';
$brandLogo = $b . 'assets/images/brand/um-rental.png';
?>
<header class="nav" data-nav-mode="<?= htmlspecialchars($navUserMode, ENT_QUOTES, 'UTF-8') ?>">
    <div class="container nav-inner">
        <a class="brand brand-lockup" href="<?= htmlspecialchars($home) ?>">
            <img class="brand-logo" src="<?= htmlspecialchars($brandLogo) ?>" alt="" decoding="async">
            <span class="brand-text brand-wordmark" data-i18n="navBrand">UM Rental</span>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="siteMenu" aria-label="Toggle navigation">☰</button>
        <nav class="menu" aria-label="Primary">
            <span class="lang-wrap">🌐
                <select id="languageSelect" class="language-select" aria-label="Language">
                    <option value="en">English</option>
                    <option value="zh-CN">简体中文</option>
                    <option value="zh-TW">繁體中文</option>
                </select>
            </span>
            <a class="nav-main-link nav-pill" href="<?= htmlspecialchars($home) ?>" data-i18n="navHome"<?= $activePage === 'home' ? ' aria-current="page"' : '' ?>>Home</a>
            <?php if ($navUserMode === 'none'): ?>
            <a class="nav-main-link nav-pill" href="<?= htmlspecialchars($services) ?>" data-i18n="navFeatured"<?= $activePage === 'services' ? ' aria-current="page"' : '' ?>>Services</a>
            <a class="nav-main-link nav-pill" href="<?= htmlspecialchars($about) ?>" data-i18n="navAbout"<?= $activePage === 'about' ? ' aria-current="page"' : '' ?>>Why us</a>
            <a class="nav-main-link nav-pill" href="<?= htmlspecialchars($howto) ?>" data-i18n="navHowto">How to use</a>
            <?php endif; ?>
            <a class="nav-main-link nav-pill" href="<?= htmlspecialchars($contact) ?>" data-i18n="navContact"<?= $activePage === 'contact' ? ' aria-current="page"' : '' ?>>Contact Us</a>

            <?php if ($navUserMode === 'none'): ?>
                <a class="cta" href="<?= htmlspecialchars($login) ?>" data-i18n="cta"<?= $activePage === 'login' ? ' aria-current="page"' : '' ?>>Login / Use Service</a>
            <?php elseif ($navUserMode === 'student'): ?>
                <a class="nav-pill" href="<?= htmlspecialchars($account) ?>" data-i18n="navAccount"<?= $activePage === 'account' ? ' aria-current="page"' : '' ?>>My Account</a>
                <a class="nav-pill" href="<?= htmlspecialchars($forum) ?>" data-i18n="navForum"<?= $activePage === 'forum' ? ' aria-current="page"' : '' ?>>Forum</a>
                <a class="nav-pill nav-pill--logout" href="<?= htmlspecialchars($logout) ?>" data-i18n="navLogout">Logout</a>
                <a class="cta" href="<?= htmlspecialchars($dashboard) ?>" data-i18n="navMyRental"<?= $activePage === 'dashboard' ? ' aria-current="page"' : '' ?>>My rental</a>
            <?php elseif ($navUserMode === 'staff'): ?>
                <a class="nav-pill" href="<?= htmlspecialchars($account) ?>" data-i18n="navAccount"<?= $activePage === 'account' ? ' aria-current="page"' : '' ?>>My Account</a>
                <a class="nav-pill" href="<?= htmlspecialchars($forum) ?>" data-i18n="navForum"<?= $activePage === 'forum' ? ' aria-current="page"' : '' ?>>Forum</a>
                <a class="nav-pill" href="<?= htmlspecialchars($dashboard) ?>" data-i18n="navUserDashboard">User Dashboard</a>
                <a class="nav-pill nav-pill--logout" href="<?= htmlspecialchars($logout) ?>" data-i18n="navLogout">Logout</a>
                <a class="cta" href="<?= htmlspecialchars($staffHome) ?>" data-i18n="navStaffHome"<?= $activePage === 'staff' ? ' aria-current="page"' : '' ?>>Staff portal</a>
            <?php else: /* admin */ ?>
                <a class="nav-pill" href="<?= htmlspecialchars($account) ?>" data-i18n="navAccount"<?= $activePage === 'account' ? ' aria-current="page"' : '' ?>>My Account</a>
                <a class="nav-pill" href="<?= htmlspecialchars($forum) ?>" data-i18n="navForum"<?= $activePage === 'forum' ? ' aria-current="page"' : '' ?>>Forum</a>
                <a class="nav-pill" href="<?= htmlspecialchars($dashboard) ?>" data-i18n="navUserDashboard">User Dashboard</a>
                <a class="nav-pill nav-pill--admin-only" href="<?= htmlspecialchars($staffHome) ?>" data-i18n="navStaffHomeShort">Staff</a>
                <a class="nav-pill nav-pill--logout" href="<?= htmlspecialchars($logout) ?>" data-i18n="navLogout">Logout</a>
                <a class="nav-pill nav-pill--admin-only" href="<?= htmlspecialchars($adminDash) ?>" data-i18n="navAdminPortal"<?= $activePage === 'admin' ? ' aria-current="page"' : '' ?>>Admin portal</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<script>
(() => {
    const nav = document.currentScript?.previousElementSibling;
    if (!nav || !nav.classList.contains('nav')) return;
    const toggle = nav.querySelector('.nav-toggle');
    const menu = nav.querySelector('.menu');
    if (!toggle || !menu) return;
    menu.id = 'siteMenu';
    const closeMenu = () => {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
    };
    toggle.addEventListener('click', () => {
        const willOpen = !nav.classList.contains('is-open');
        nav.classList.toggle('is-open', willOpen);
        toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });
    menu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.matchMedia('(max-width: 940px)').matches) closeMenu();
        });
    });
    window.addEventListener('resize', () => {
        if (!window.matchMedia('(max-width: 940px)').matches) closeMenu();
    });
})();
</script>
