<?php
declare(strict_types=1);
$projectRoot = dirname(__DIR__, 2);
require_once $projectRoot . '/includes/auth.php';
startAuthSession();
$activePage = 'services';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require $projectRoot . '/includes/favicon_links.php'; ?>
    <title data-i18n="title">Services | UM Mobility</title>
    <link rel="stylesheet" href="./assets/um_landing.css?v=<?= file_exists($projectRoot . '/assets/um_landing.css') ? filemtime($projectRoot . '/assets/um_landing.css') : time() ?>">
    <link rel="stylesheet" href="./assets/home-landing.css?v=<?= file_exists($projectRoot . '/assets/home-landing.css') ? filemtime($projectRoot . '/assets/home-landing.css') : time() ?>">
    <link rel="stylesheet" href="./assets/public-page.css?v=<?= file_exists($projectRoot . '/assets/public-page.css') ? filemtime($projectRoot . '/assets/public-page.css') : time() ?>">
    <link rel="stylesheet" href="./assets/site-footer.css?v=<?= file_exists($projectRoot . '/assets/site-footer.css') ? filemtime($projectRoot . '/assets/site-footer.css') : time() ?>">
</head>
<body>
<?php require $projectRoot . '/includes/public_header.php'; ?>

<main class="section container public-page-main">
    <h2 data-i18n="heading">Services</h2>
    <div class="features">
        <article class="feature-card">
            <h3 data-i18n="s1t">Real-Time Availability</h3>
            <p class="muted" data-i18n="s1d">View available bicycles and scooters by station before you start walking.</p>
        </article>
        <article class="feature-card">
            <h3 data-i18n="s2t">Fast Rental Workflow</h3>
            <p class="muted" data-i18n="s2d">Rent in seconds, follow active trip timer, and return at any valid station.</p>
        </article>
        <article class="feature-card">
            <h3 data-i18n="s3t">Transparent Billing</h3>
            <p class="muted" data-i18n="s3d">Time-based fee calculation with clear order history and wallet records.</p>
        </article>
        <article class="feature-card">
            <h3 data-i18n="s4t">Student Account Controls</h3>
            <p class="muted" data-i18n="s4d">Staff can update account status and maintain a healthy service environment.</p>
        </article>
        <article class="feature-card">
            <h3 data-i18n="s5t">Fleet Management</h3>
            <p class="muted" data-i18n="s5d">Add/remove bicycles, monitor rental status, and track latest usage records.</p>
        </article>
        <article class="feature-card">
            <h3 data-i18n="s6t">Security & Reliability</h3>
            <p class="muted" data-i18n="s6d">Includes CSRF checks, rate limiting, and operation logs for safer usage.</p>
        </article>
    </div>
</main>

<?php require $projectRoot . '/includes/public_footer.php'; ?>
<script>
const i18n = {
    en: {
        title: 'Services | UM Mobility',
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
        heading: 'Services',
        s1t: 'Real-Time Availability', s1d: 'View available bicycles and scooters by station before you start walking.',
        s2t: 'Fast Rental Workflow', s2d: 'Rent in seconds, follow active trip timer, and return at any valid station.',
        s3t: 'Transparent Billing', s3d: 'Time-based fee calculation with clear order history and wallet records.',
        s4t: 'Student Account Controls', s4d: 'Staff can update account status and maintain a healthy service environment.',
        s5t: 'Fleet Management', s5d: 'Add/remove bicycles, monitor rental status, and track latest usage records.',
        s6t: 'Security & Reliability', s6d: 'Includes CSRF checks, rate limiting, and operation logs for safer usage.',
    },
    'zh-CN': {
        title: '服务 | 澳大智慧出行',
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
        heading: '服务',
        s1t: '实时可用车辆', s1d: '出发前即可按站点查看可租自行车与滑板车。',
        s2t: '快速租还流程', s2d: '几秒完成租借，支持计时显示，并可在有效站点归还。',
        s3t: '透明计费', s3d: '按时段计算费用，订单记录与钱包流水清晰可查。',
        s4t: '学生账户管理', s4d: '工作人员可更新账户状态，维护良好使用环境。',
        s5t: '车队管理', s5d: '支持新增/移除自行车，查看租借状态与最近使用记录。',
        s6t: '安全与稳定', s6d: '包含 CSRF 校验、限流与操作日志，提升系统安全性。',
    },
    'zh-TW': {
        title: '服務 | 澳大智慧出行',
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
        heading: '服務',
        s1t: '即時可用車輛', s1d: '出發前即可依站點查看可租自行車與滑板車。',
        s2t: '快速租還流程', s2d: '幾秒完成租借，支援計時顯示，並可在有效站點歸還。',
        s3t: '透明計費', s3d: '按時段計算費用，訂單記錄與錢包流水清晰可查。',
        s4t: '學生帳戶管理', s4d: '工作人員可更新帳戶狀態，維護良好使用環境。',
        s5t: '車隊管理', s5d: '支援新增/移除自行車，查看租借狀態與最近使用記錄。',
        s6t: '安全與穩定', s6d: '包含 CSRF 驗證、限流與操作日誌，提升系統安全性。',
    }
};
</script>
<script src="./assets/footer_i18n.js?v=<?= file_exists($projectRoot . '/assets/footer_i18n.js') ? filemtime($projectRoot . '/assets/footer_i18n.js') : time() ?>"></script>
<script src="./assets/public_i18n.js?v=<?= file_exists($projectRoot . '/assets/public_i18n.js') ? filemtime($projectRoot . '/assets/public_i18n.js') : time() ?>"></script>
<script>initPublicI18n(i18n);</script>
</body>
</html>
