<?php
declare(strict_types=1);
$projectRoot = dirname(__DIR__, 2);
require_once $projectRoot . '/includes/auth.php';
startAuthSession();
$activePage = 'contact';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require $projectRoot . '/includes/favicon_links.php'; ?>
    <title data-i18n="title">Contact | UM Mobility</title>
    <link rel="stylesheet" href="./assets/um_landing.css?v=<?= file_exists($projectRoot . '/assets/um_landing.css') ? filemtime($projectRoot . '/assets/um_landing.css') : time() ?>">
    <link rel="stylesheet" href="./assets/home-landing.css?v=<?= file_exists($projectRoot . '/assets/home-landing.css') ? filemtime($projectRoot . '/assets/home-landing.css') : time() ?>">
    <link rel="stylesheet" href="./assets/public-page.css?v=<?= file_exists($projectRoot . '/assets/public-page.css') ? filemtime($projectRoot . '/assets/public-page.css') : time() ?>">
    <link rel="stylesheet" href="./assets/site-footer.css?v=<?= file_exists($projectRoot . '/assets/site-footer.css') ? filemtime($projectRoot . '/assets/site-footer.css') : time() ?>">
</head>
<body>
<?php require $projectRoot . '/includes/public_header.php'; ?>

<main class="section container public-page-main">
    <h2 data-i18n="heading">Contact Us</h2>
    <div class="features">
        <article class="feature-card">
            <h3 data-i18n="c1t">General Support</h3>
            <p class="muted" data-i18n="c1d">Email: dc32510@um.edu.mo</p>
            <p class="muted" data-i18n="c1d2">Response Time: within 1 business day</p>
        </article>
        <article class="feature-card">
            <h3 data-i18n="c2t">Operations Desk</h3>
            <p class="muted" data-i18n="c2d">Service Hours: Mon-Fri, 09:00-18:00</p>
            <p class="muted" data-i18n="c2d2">Location: UM Campus Mobility Office</p>
        </article>
        <article class="feature-card">
            <h3 data-i18n="c3t">Emergency Issue</h3>
            <p class="muted" data-i18n="c3d">For urgent safety cases, contact campus security first.</p>
            <p class="muted" data-i18n="c3d2">Then submit vehicle serial/station details to support.</p>
        </article>
    </div>
</main>

<?php require $projectRoot . '/includes/public_footer.php'; ?>
<script>
const i18n = {
    en: {
        title: 'Contact | UM Mobility',
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
        heading: 'Contact Us',
        c1t: 'General Support', c1d: 'Email: dc32510@um.edu.mo', c1d2: 'Response Time: within 1 business day',
        c2t: 'Operations Desk', c2d: 'Service Hours: Mon-Fri, 09:00-18:00', c2d2: 'Location: UM Campus Mobility Office',
        c3t: 'Emergency Issue', c3d: 'For urgent safety cases, contact campus security first.', c3d2: 'Then submit vehicle serial/station details to support.',
    },
    'zh-CN': {
        title: '联系 | 澳大智慧出行',
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
        heading: '联系我们',
        c1t: '一般支持', c1d: '邮箱：dc32510@um.edu.mo', c1d2: '响应时间：1个工作日内',
        c2t: '运营服务台', c2d: '服务时间：周一至周五 09:00-18:00', c2d2: '地点：UM 校园出行办公室',
        c3t: '紧急问题', c3d: '如遇安全紧急情况，请先联系校园保安。', c3d2: '随后向支持团队提交车辆编号/站点信息。',
    },
    'zh-TW': {
        title: '聯絡 | 澳大智慧出行',
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
        heading: '聯絡我們',
        c1t: '一般支援', c1d: 'Email：dc32510@um.edu.mo', c1d2: '回覆時間：1 個工作天內',
        c2t: '營運服務台', c2d: '服務時間：週一至週五 09:00-18:00', c2d2: '地點：UM 校園出行辦公室',
        c3t: '緊急問題', c3d: '如遇安全緊急情況，請先聯絡校園保安。', c3d2: '再向支援團隊提交車輛編號/站點資訊。',
    }
};
</script>
<script src="./assets/footer_i18n.js?v=<?= file_exists($projectRoot . '/assets/footer_i18n.js') ? filemtime($projectRoot . '/assets/footer_i18n.js') : time() ?>"></script>
<script src="./assets/public_i18n.js?v=<?= file_exists($projectRoot . '/assets/public_i18n.js') ? filemtime($projectRoot . '/assets/public_i18n.js') : time() ?>"></script>
<script>initPublicI18n(i18n);</script>
</body>
</html>
