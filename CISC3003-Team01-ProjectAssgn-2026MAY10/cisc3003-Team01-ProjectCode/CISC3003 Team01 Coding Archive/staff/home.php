<?php
declare(strict_types=1);
require_once __DIR__ . '/_guard.php';

$name = (string)($_SESSION['full_name'] ?? $_SESSION['campus_id'] ?? 'Staff');
$role = (string)($_SESSION['role'] ?? 'staff');
$layoutBase = '../';
$activePage = 'staff';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require __DIR__ . '/../includes/favicon_links.php'; ?>
    <title>Staff Home</title>
    <link rel="stylesheet" href="../assets/um_landing.css?v=<?= file_exists(__DIR__ . '/../assets/um_landing.css') ? filemtime(__DIR__ . '/../assets/um_landing.css') : time() ?>">
    <link rel="stylesheet" href="../assets/home-landing.css?v=<?= file_exists(__DIR__ . '/../assets/home-landing.css') ? filemtime(__DIR__ . '/../assets/home-landing.css') : time() ?>">
    <link rel="stylesheet" href="../style.css?v=<?= file_exists(__DIR__ . '/../style.css') ? filemtime(__DIR__ . '/../style.css') : time() ?>">
    <link rel="stylesheet" href="../assets/site-footer.css?v=<?= file_exists(__DIR__ . '/../assets/site-footer.css') ? filemtime(__DIR__ . '/../assets/site-footer.css') : time() ?>">
</head>
<body>
<?php require __DIR__ . '/../includes/public_header.php'; ?>

<main class="layout single-column">
    <section class="content-card">
        <h2><span data-i18n="welcome">Welcome</span>, <?= htmlspecialchars($name) ?></h2>
        <p class="meta" data-i18n="chooseManage">Choose what you want to manage.</p>

        <div class="grid-2">
            <div class="kpi">
                <h3 data-i18n="bikeManagement">Bicycle Management</h3>
                <p class="meta" data-i18n="bikeDesc">Add bicycles, remove bicycles, and check bicycle inventory.</p>
                <a class="btn" href="./staff_dashboard.php#bikeSection" data-i18n="goBike">Go to Bicycle Management</a>
            </div>
            <div class="kpi">
                <h3 data-i18n="studentManagement">Student Account Management</h3>
                <p class="meta" data-i18n="studentDesc">Review student accounts, update status, and disable accounts.</p>
                <a class="btn" href="./staff_dashboard.php?section=student#studentSection" data-i18n="goStudent">Go to Student Accounts</a>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/public_footer.php'; ?>
<script src="../assets/footer_i18n.js?v=<?= file_exists(__DIR__ . '/../assets/footer_i18n.js') ? filemtime(__DIR__ . '/../assets/footer_i18n.js') : time() ?>"></script>
<script>
const i18n = mergeFooterI18n({
    en: {
        pageTitle: 'Staff Home',
        navBrand: 'UM Rental', navHome: 'Home', navFeatured: 'Services', navAbout: 'Why us', navHowto: 'How to use', navContact: 'Contact Us', cta: 'Login / Use Service',
        navAccount: 'My Account', navLogout: 'Logout', navMyRental: 'My rental', navUserDashboard: 'User Dashboard', navStaffHome: 'Staff portal', navStaffHomeShort: 'Staff', navAdminPortal: 'Admin portal',
        title: 'UM Staff Home', userDashboard: 'User Dashboard', adminPortal: 'Admin Portal', logout: 'Logout',
        welcome: 'Welcome', chooseManage: 'Choose what you want to manage.',
        bikeManagement: 'Bicycle Management', bikeDesc: 'Add bicycles, remove bicycles, and check bicycle inventory.', goBike: 'Go to Bicycle Management',
        studentManagement: 'Student Account Management', studentDesc: 'Review student accounts, update status, and disable accounts.', goStudent: 'Go to Student Accounts'
    },
    'zh-CN': {
        pageTitle: '员工主页',
        navBrand: 'UM 租赁', navHome: '首页', navFeatured: '服务', navAbout: '核心价值', navHowto: '使用说明', navContact: '联系我们', cta: '立即登录 / 使用服务',
        navAccount: '我的账户', navLogout: '退出', navMyRental: '我的租借', navUserDashboard: '用户仪表板', navStaffHome: '员工入口', navStaffHomeShort: '员工', navAdminPortal: '管理入口',
        title: 'UM 员工主页', userDashboard: '用户仪表板', adminPortal: '管理员后台', logout: '登出',
        welcome: '欢迎', chooseManage: '请选择你要管理的项目。',
        bikeManagement: '自行车管理', bikeDesc: '新增、删除自行车并查看库存。', goBike: '前往自行车管理',
        studentManagement: '学生账户管理', studentDesc: '查看学生账户、更新状态、停用账户。', goStudent: '前往学生账户'
    },
    'zh-TW': {
        pageTitle: '職員主頁',
        navBrand: 'UM 租賃', navHome: '首頁', navFeatured: '服務', navAbout: '核心價值', navHowto: '使用說明', navContact: '聯繫我們', cta: '立即登入 / 使用服務',
        navAccount: '我的帳戶', navLogout: '登出', navMyRental: '我的租借', navUserDashboard: '用戶儀表板', navStaffHome: '員工入口', navStaffHomeShort: '員工', navAdminPortal: '管理入口',
        title: 'UM 職員主頁', userDashboard: '用戶儀表板', adminPortal: '管理後台', logout: '登出',
        welcome: '歡迎', chooseManage: '請選擇你要管理的項目。',
        bikeManagement: '自行車管理', bikeDesc: '新增、刪除自行車並查看庫存。', goBike: '前往自行車管理',
        studentManagement: '學生帳戶管理', studentDesc: '查看學生帳戶、更新狀態、停用帳戶。', goStudent: '前往學生帳戶'
    }
});
let lang = localStorage.getItem('lang') || 'en';
function t(k){ return (i18n[lang] && i18n[lang][k]) || i18n.en[k] || k; }
function applyLanguage(){
    document.documentElement.lang = lang;
    document.title = t('pageTitle');
    document.querySelectorAll('[data-i18n]').forEach(el => el.textContent = t(el.dataset.i18n));
}
const langSelect = document.getElementById('languageSelect');
langSelect.value = lang;
langSelect.addEventListener('change', () => {
    lang = langSelect.value;
    localStorage.setItem('lang', lang);
    applyLanguage();
});
applyLanguage();
</script>
</body>
</html>
