<?php
declare(strict_types=1);
$projectRoot = dirname(__DIR__, 2);
require_once $projectRoot . '/includes/auth.php';
startAuthSession();
$activePage = 'about';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require $projectRoot . '/includes/favicon_links.php'; ?>
    <title data-i18n="title">About | UM Mobility</title>
    <link rel="stylesheet" href="./assets/um_landing.css?v=<?= file_exists($projectRoot . '/assets/um_landing.css') ? filemtime($projectRoot . '/assets/um_landing.css') : time() ?>">
    <link rel="stylesheet" href="./assets/home-landing.css?v=<?= file_exists($projectRoot . '/assets/home-landing.css') ? filemtime($projectRoot . '/assets/home-landing.css') : time() ?>">
    <link rel="stylesheet" href="./assets/public-page.css?v=<?= file_exists($projectRoot . '/assets/public-page.css') ? filemtime($projectRoot . '/assets/public-page.css') : time() ?>">
    <link rel="stylesheet" href="./assets/site-footer.css?v=<?= file_exists($projectRoot . '/assets/site-footer.css') ? filemtime($projectRoot . '/assets/site-footer.css') : time() ?>">
</head>
<body>
<?php require $projectRoot . '/includes/public_header.php'; ?>

<main class="section container public-page-main">
    <h2 data-i18n="heading">About UM Rental</h2>
    <p class="muted" data-i18n="p1">UM Rental is a campus mobility platform designed for students, staff, and authorized visitors. It focuses on convenience, safety, and station-based parking discipline.</p>
    <p class="muted" data-i18n="p2">Our objective is to reduce walking time between academic zones, residences, and shared facilities while keeping vehicle usage transparent and accountable through clear trip records.</p>
    <div class="features">
        <article class="feature-card">
            <h3 data-i18n="c1t">Mission</h3>
            <p class="muted" data-i18n="c1d">Provide reliable and affordable short-distance transport for daily campus travel.</p>
        </article>
        <article class="feature-card">
            <h3 data-i18n="c2t">Operating Model</h3>
            <p class="muted" data-i18n="c2d">Vehicles are managed by stations to reduce random parking and improve route safety.</p>
        </article>
        <article class="feature-card">
            <h3 data-i18n="c3t">Governance</h3>
            <p class="muted" data-i18n="c3d">Staff and admin tools support account control, fleet maintenance, and operational monitoring.</p>
        </article>
    </div>
</main>

<?php require $projectRoot . '/includes/public_footer.php'; ?>
<script>
const i18n = {
    en: {
        title: 'About | UM Mobility',
        navBrand: 'UM Rental',
        navHome: 'Home',
        navFeatured: 'Services',
        navAbout: 'Why us',
        navHowto: 'How to use',
        navContact: 'Contact Us',
        cta: 'Login / Use Service',
        navAccount: 'My Account',
        navLogout: 'Logout',
        navMyRental: 'My rental',
        navUserDashboard: 'User Dashboard',
        navStaffHome: 'Staff portal',
        navStaffHomeShort: 'Staff',
        navAdminPortal: 'Admin portal',
        heading: 'About UM Rental',
        p1: 'UM Rental is a campus mobility platform designed for students, staff, and authorized visitors. It focuses on convenience, safety, and station-based parking discipline.',
        p2: 'Our objective is to reduce walking time between academic zones, residences, and shared facilities while keeping vehicle usage transparent and accountable through clear trip records.',
        c1t: 'Mission', c1d: 'Provide reliable and affordable short-distance transport for daily campus travel.',
        c2t: 'Operating Model', c2d: 'Vehicles are managed by stations to reduce random parking and improve route safety.',
        c3t: 'Governance', c3d: 'Staff and admin tools support account control, fleet maintenance, and operational monitoring.',
    },
    'zh-CN': {
        title: '关于 | 澳大智慧出行',
        navBrand: 'UM 租赁',
        navHome: '首页',
        navFeatured: '服务',
        navAbout: '核心价值',
        navHowto: '使用说明',
        navContact: '联系我们',
        cta: '立即登录 / 使用服务',
        navAccount: '我的账户',
        navLogout: '退出',
        navMyRental: '我的租借',
        navUserDashboard: '用户仪表板',
        navStaffHome: '员工入口',
        navStaffHomeShort: '员工',
        navAdminPortal: '管理入口',
        heading: '关于 UM 租赁',
        p1: 'UM 租赁是面向学生、教职员与授权访客的校园出行平台，强调便捷、安全与站点化停放秩序。',
        p2: '我们的目标是缩短教学区、宿舍与公共设施之间的通行时间，并通过清晰的行程记录保障车辆使用可追踪。',
        c1t: '使命', c1d: '为校园日常短途出行提供稳定且可负担的交通方式。',
        c2t: '运营模式', c2d: '以站点管理车辆，减少乱停放并提升通行安全。',
        c3t: '治理机制', c3d: '通过 staff 与 admin 功能支持账户管理、车队维护与运营监控。',
    },
    'zh-TW': {
        title: '關於 | 澳大智慧出行',
        navBrand: 'UM 租賃',
        navHome: '首頁',
        navFeatured: '服務',
        navAbout: '核心價值',
        navHowto: '使用說明',
        navContact: '聯繫我們',
        cta: '立即登入 / 使用服務',
        navAccount: '我的帳戶',
        navLogout: '登出',
        navMyRental: '我的租借',
        navUserDashboard: '用戶儀表板',
        navStaffHome: '員工入口',
        navStaffHomeShort: '員工',
        navAdminPortal: '管理入口',
        heading: '關於 UM 租賃',
        p1: 'UM 租賃是面向學生、教職員與授權訪客的校園出行平台，強調便捷、安全與站點化停放秩序。',
        p2: '我們的目標是縮短教學區、宿舍與公共設施之間的通行時間，並透過清晰行程紀錄保障車輛使用可追蹤。',
        c1t: '使命', c1d: '為校園日常短途出行提供穩定且可負擔的交通方式。',
        c2t: '營運模式', c2d: '以站點管理車輛，減少亂停放並提升通行安全。',
        c3t: '治理機制', c3d: '透過 staff 與 admin 功能支援帳戶管理、車隊維護與營運監控。',
    }
};
</script>
<script src="./assets/footer_i18n.js?v=<?= file_exists($projectRoot . '/assets/footer_i18n.js') ? filemtime($projectRoot . '/assets/footer_i18n.js') : time() ?>"></script>
<script src="./assets/public_i18n.js?v=<?= file_exists($projectRoot . '/assets/public_i18n.js') ? filemtime($projectRoot . '/assets/public_i18n.js') : time() ?>"></script>
<script>initPublicI18n(i18n);</script>
</body>
</html>
