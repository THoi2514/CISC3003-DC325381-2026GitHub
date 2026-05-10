<?php
declare(strict_types=1);
$projectRoot = dirname(__DIR__, 2);
require_once $projectRoot . '/includes/auth.php';
startAuthSession();
$activePage = 'home';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require $projectRoot . '/includes/favicon_links.php'; ?>
    <title data-i18n="title">UM Rental</title>
    <link rel="stylesheet" href="./assets/um_landing.css?v=<?= file_exists($projectRoot . '/assets/um_landing.css') ? filemtime($projectRoot . '/assets/um_landing.css') : time() ?>">
    <link rel="stylesheet" href="./assets/home-landing.css?v=<?= file_exists($projectRoot . '/assets/home-landing.css') ? filemtime($projectRoot . '/assets/home-landing.css') : time() ?>">
    <link rel="stylesheet" href="./assets/site-footer.css?v=<?= file_exists($projectRoot . '/assets/site-footer.css') ? filemtime($projectRoot . '/assets/site-footer.css') : time() ?>">
</head>
<body>
<?php require $projectRoot . '/includes/public_header.php'; ?>

<section class="hl-hero" id="home" style="--hl-hero-img: url('./assets/images/home/hero-bg.jpg');">
    <div class="container hl-hero-inner">
        <p class="hl-kicker" data-i18n="hlKicker">UM Rental</p>
        <h1 data-i18n="heroTitle">UM Rental</h1>
        <p class="hl-lead">
            <span data-i18n="heroDesc">Designed for large UM campus travel, this platform provides a reliable last-mile solution to improve mobility and reduce random parking.</span>
        </p>
        <div class="hl-hero-cta">
            <a class="hl-btn hl-btn-primary" href="./login.php" data-i18n="cta">Login / Use Service</a>
            <a class="hl-btn hl-btn-ghost" href="#featured" data-i18n="hlExplore">Explore services</a>
        </div>
    </div>
</section>

<section id="featured" class="hl-section hl-featured">
    <div class="container">
        <h2 class="hl-section-title" data-i18n="hlFeaturedTitle">What you can do on campus</h2>
        <p class="hl-section-intro" data-i18n="hlFeaturedIntro">Browse vehicles by station, ride between faculties, and return to designated docks—with transparent wallet billing.</p>
        <div class="hl-card-grid">
            <article class="hl-card reveal">
                <div class="hl-card-img">
                    <img src="./assets/images/home/feature-bicycle.jpg" width="960" height="660" loading="lazy" data-img-alt="imgAltBike" alt="">
                </div>
                <div class="hl-card-body">
                    <h3 data-i18n="cardBikeTitle">Campus bicycles</h3>
                    <p class="muted" data-i18n="cardBikeDesc">Live availability by station; unlock after you confirm rental.</p>
                    <div class="hl-card-meta" data-i18n="cardBikePrice">Demo · from MOP 2 / 30 min</div>
                    <a class="hl-card-cta" href="./login.php" data-i18n="cardCta">Go to rental</a>
                </div>
            </article>
            <article class="hl-card reveal">
                <div class="hl-card-img">
                    <img src="./assets/images/home/feature-scooter.jpg" width="960" height="660" loading="lazy" data-img-alt="imgAltScooter" alt="">
                </div>
                <div class="hl-card-body">
                    <h3 data-i18n="cardScooterTitle">E-scooters</h3>
                    <p class="muted" data-i18n="cardScooterDesc">Great for short hops; fleet checks battery before you ride.</p>
                    <div class="hl-card-meta" data-i18n="cardScooterPrice">Demo · same fare band</div>
                    <a class="hl-card-cta" href="./login.php" data-i18n="cardCta">Go to rental</a>
                </div>
            </article>
            <article class="hl-card reveal">
                <div class="hl-card-img">
                    <img src="./assets/images/home/feature-campus.jpg" width="960" height="660" loading="lazy" data-img-alt="imgAltCampus" alt="">
                </div>
                <div class="hl-card-body">
                    <h3 data-i18n="cardCampusTitle">Stations & parking</h3>
                    <p class="muted" data-i18n="cardCampusDesc">Return to marked racks; capacity updates help avoid full docks.</p>
                    <div class="hl-card-meta" data-i18n="cardCampusPrice">Map inside dashboard</div>
                    <a class="hl-card-cta" href="./login.php" data-i18n="cardCta">Go to rental</a>
                </div>
            </article>
        </div>
    </div>
</section>

<section class="section container" id="howto">
    <h2 data-i18n="howToTitle">How to Use Public Bikes</h2>
    <div class="howto-steps">
        <article class="howto-step reveal">
            <div class="howto-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <circle cx="9" cy="8" r="4" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M2.5 20c0-3.3 2.7-6 6-6h1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <circle cx="17.5" cy="15.5" r="4" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M15.8 15.5l1.2 1.2 2.2-2.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h3 data-i18n="howStep1Title">Register / Login</h3>
            <p class="muted" data-i18n="howStep1Desc">Create your account and sign in.</p>
        </article>
        <div class="howto-arrow" aria-hidden="true">›</div>
        <article class="howto-step reveal">
            <div class="howto-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <rect x="2.5" y="6" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M2.5 10h14" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M5.5 13h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <rect x="16.5" y="3.5" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.6"/>
                    <rect x="16.5" y="9.5" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.6"/>
                </svg>
            </div>
            <h3 data-i18n="howStep2Title">Rent</h3>
            <p class="muted" data-i18n="howStep2Desc">Choose an available bike and start rental.</p>
        </article>
        <div class="howto-arrow" aria-hidden="true">›</div>
        <article class="howto-step reveal">
            <div class="howto-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <circle cx="6" cy="18" r="3" stroke="currentColor" stroke-width="1.8"/>
                    <circle cx="18" cy="18" r="3" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M9 18l3-6 3 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M12 12h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <circle cx="13" cy="7" r="2" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M11.5 9.2l-2 2.2M14.5 9.2l2 2.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </div>
            <h3 data-i18n="howStep3Title">Ride</h3>
            <p class="muted" data-i18n="howStep3Desc">Ride safely to your destination.</p>
        </article>
        <div class="howto-arrow" aria-hidden="true">›</div>
        <article class="howto-step reveal">
            <div class="howto-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M8 11V8a4 4 0 118 0v3" stroke="currentColor" stroke-width="1.8"/>
                    <circle cx="12" cy="16" r="1.4" fill="currentColor"/>
                </svg>
            </div>
            <h3 data-i18n="howStep4Title">Return</h3>
            <p class="muted" data-i18n="howStep4Desc">Return the bike to an active station.</p>
        </article>
    </div>
</section>

<section id="about" class="hl-section hl-coverage">
    <div class="container">
        <div class="panel hl-strip reveal">
            <h3 data-i18n="coreValue">Core Value</h3>
            <ul>
                <li data-i18n="v1">Improve campus mobility and reduce cross-zone travel time</li>
                <li data-i18n="v2">Station-based operations to reduce random parking and blockage</li>
                <li data-i18n="v3">Real-time status and transparent pricing for better experience</li>
            </ul>
            <p class="muted" style="margin: 14px 0 0;" data-i18n="coverageDesc">Available to UM students, staff, and authorized visitors.</p>
        </div>
    </div>
</section>

<?php require $projectRoot . '/includes/public_footer.php'; ?>

<script src="./assets/footer_i18n.js?v=<?= file_exists($projectRoot . '/assets/footer_i18n.js') ? filemtime($projectRoot . '/assets/footer_i18n.js') : time() ?>"></script>
<script>
const i18n = mergeFooterI18n({
    en: {
        title: 'UM Rental',
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
        hlKicker: 'UM Rental',
        hlExplore: 'Explore services',
        heroTitle: 'UM Rental — campus bicycle & e-scooter sharing',
        heroDesc: 'Fresh routes between faculties, quick pickup at stations, and a smooth rental flow for classes and daily commute.',
        hlFeaturedTitle: 'What you can do on campus',
        hlFeaturedIntro: 'Browse vehicles by station, ride between zones, and return to designated docks—with transparent wallet billing.',
        cardBikeTitle: 'Campus bicycles',
        cardBikeDesc: 'Live availability by station; unlock after you confirm rental.',
        cardBikePrice: 'Demo · from MOP 2 / 30 min',
        cardScooterTitle: 'E-scooters',
        cardScooterDesc: 'Great for short hops; fleet checks battery before you ride.',
        cardScooterPrice: 'Demo · same fare band',
        cardCampusTitle: 'Stations & parking',
        cardCampusDesc: 'Return to marked racks; capacity updates help avoid full docks.',
        cardCampusPrice: 'Map inside dashboard',
        cardCta: 'Go to rental',
        imgAltBike: 'Campus bicycle at a docking station',
        imgAltScooter: 'Electric scooter ready for rental',
        imgAltCampus: 'University campus walkways',
        coreValue: 'Core Value',
        v1: 'Improve campus mobility and reduce cross-zone travel time',
        v2: 'Station-based operations to reduce random parking and blockage',
        v3: 'Real-time status and transparent pricing for better experience',
        coverageTitle: 'Service Audience',
        coverageDesc: 'Available to UM students, staff, and authorized visitors.',
        howToTitle: 'How to Use Public Bikes',
        howStep1Title: 'Register / Login',
        howStep1Desc: 'Create your account and sign in.',
        howStep2Title: 'Rent',
        howStep2Desc: 'Choose an available bike and start rental.',
        howStep3Title: 'Ride',
        howStep3Desc: 'Ride safely to your destination.',
        howStep4Title: 'Return',
        howStep4Desc: 'Return the bike to an active station.',
        loginTitle: 'Login (Simulated UM SSO)', password: 'Password', loginBtn: 'Login', closeLogin: 'Close',
        campusId: 'Campus ID', campusPlaceholder: 'e.g. s1234567', passwordPlaceholder: 'Enter password',
        registerLink: 'New User Registration (UM Email)', forgotLink: 'Reset Password',
        contact: 'Contact us: dc32510@um.edu.mo',
        missingInput: 'Please enter Campus ID and password.',
        loginSuccess: 'Login successful, redirecting...'
    },
    'zh-CN': {
        title:'UM 租赁',
        navBrand:'UM 租赁',
        navHome:'首页',
        navFeatured:'服务',
        navAbout:'核心价值',
        navHowto:'使用说明',
        navContact:'联系我们',
        cta:'立即登录 / 使用服务',
        navAccount:'我的账户',
        navLogout:'退出',
        navMyRental:'我的租借',
        navUserDashboard:'用户仪表板',
        navStaffHome:'员工入口',
        navStaffHomeShort:'员工',
        navAdminPortal:'管理入口',
        hlKicker:'UM 租赁',
        hlExplore:'了解服务',
        heroTitle:'UM 租赁 · 校园单车与电动滑板车',
        heroDesc:'串联教学楼与宿舍区，站点即取即还，上课通勤流程更顺畅。',
        hlFeaturedTitle:'在校园可以做什么',
        hlFeaturedIntro:'按站点浏览车辆、跨区骑行并在指定桩位归还——钱包计费透明可查。',
        cardBikeTitle:'校园自行车',
        cardBikeDesc:'站点实时可用车辆；确认租借后即可开锁取车。',
        cardBikePrice:'演示 · 低至 MOP 2 / 30 分钟',
        cardScooterTitle:'电动滑板车',
        cardScooterDesc:'短途接驳；系统会关注电量状态以便安全骑行。',
        cardScooterPrice:'演示 · 与单车同计费档次',
        cardCampusTitle:'站点与停放',
        cardCampusDesc:'归还至划线车位；容量提示减少满桩困扰。',
        cardCampusPrice:'登录后查看地图',
        cardCta:'前往租借',
        imgAltBike:'站点旁的校园共享单车',
        imgAltScooter:'可供租借的电动滑板车',
        imgAltCampus:'大学校园步道与建筑',
        coreValue:'核心价值',
        v1:'提升校园移动效率，减少跨区通勤时间',
        v2:'通过站点化管理减少乱停放与通道阻塞',
        v3:'以实时状态与透明计费提升体验',
        coverageTitle:'服务对象',
        coverageDesc:'适用于 UM 学生、教职员及授权访客。',
        howToTitle:'使用公共自行车',
        howStep1Title:'注册 / 登录',
        howStep1Desc:'完成账号注册并登录系统。',
        howStep2Title:'租借',
        howStep2Desc:'选择可租车辆并开始租借。',
        howStep3Title:'骑乘',
        howStep3Desc:'安全骑行前往目的地。',
        howStep4Title:'还车',
        howStep4Desc:'在可用站点完成还车。',
        loginTitle:'登录系统（模拟 UM SSO）', campusId:'校园 ID', password:'密码', loginBtn:'登录', closeLogin:'关闭',
        campusPlaceholder:'例如 s1234567', passwordPlaceholder:'请输入密码',
        registerLink:'新用户注册（UM 邮箱）', forgotLink:'找回密码',
        contact:'联系我们：dc32510@um.edu.mo',
        missingInput:'请输入 Campus ID 与密码。',
        loginSuccess:'登录成功，正在跳转...'
    },
    'zh-TW': {
        title:'UM 租賃',
        navBrand:'UM 租賃',
        navHome:'首頁',
        navFeatured:'服務',
        navAbout:'核心價值',
        navHowto:'使用說明',
        navContact:'聯繫我們',
        cta:'立即登入 / 使用服務',
        navAccount:'我的帳戶',
        navLogout:'登出',
        navMyRental:'我的租借',
        navUserDashboard:'用戶儀表板',
        navStaffHome:'員工入口',
        navStaffHomeShort:'員工',
        navAdminPortal:'管理入口',
        hlKicker:'UM 租賃',
        hlExplore:'了解服務',
        heroTitle:'UM 租賃 · 校園單車與電動滑板車',
        heroDesc:'串連教學樓與宿舍區，站點即取即還，讓通課與日常通勤更順暢。',
        hlFeaturedTitle:'在校園可以做什麼',
        hlFeaturedIntro:'依站點瀏覽車輛、跨區騎行並在指定桩位歸還——錢包計費透明可查。',
        cardBikeTitle:'校園自行車',
        cardBikeDesc:'站點即時可用車輛；確認租借後即可開鎖取車。',
        cardBikePrice:'演示 · 低至 MOP 2 / 30 分鐘',
        cardScooterTitle:'電動滑板車',
        cardScooterDesc:'短途接駁；系統會留意電量狀態以利安全騎行。',
        cardScooterPrice:'演示 · 與單車同計費級距',
        cardCampusTitle:'站點與停放',
        cardCampusDesc:'歸還至劃線車位；容量提示減少滿桩困擾。',
        cardCampusPrice:'登入後查看地圖',
        cardCta:'前往租借',
        imgAltBike:'站點旁的校園共享單車',
        imgAltScooter:'可供租借的電動滑板車',
        imgAltCampus:'大學校園步道與建築',
        coreValue:'核心價值',
        v1:'提升校園移動效率，縮短跨區通勤時間',
        v2:'以站點化管理減少亂停放與通道阻塞',
        v3:'透過即時狀態與透明計費提升體驗',
        coverageTitle:'服務對象',
        coverageDesc:'適用於 UM 學生、教職員及授權訪客。',
        howToTitle:'使用公共自行車',
        howStep1Title:'註冊 / 登入',
        howStep1Desc:'完成帳號註冊並登入系統。',
        howStep2Title:'租借',
        howStep2Desc:'選擇可租車輛並開始租借。',
        howStep3Title:'騎乘',
        howStep3Desc:'安全騎行前往目的地。',
        howStep4Title:'還車',
        howStep4Desc:'在可用站點完成還車。',
        loginTitle:'登入系統（模擬 UM SSO）', campusId:'校園 ID', password:'密碼', loginBtn:'登入', closeLogin:'關閉',
        campusPlaceholder:'例如 s1234567', passwordPlaceholder:'請輸入密碼',
        registerLink:'新用戶註冊（UM Email）', forgotLink:'找回密碼',
        contact:'聯繫我們：dc32510@um.edu.mo',
        missingInput:'請輸入 Campus ID 與密碼。',
        loginSuccess:'登入成功，正在跳轉...'
    }
});
let lang = localStorage.getItem('lang') || 'en';
function t(k){ return (i18n[lang] && i18n[lang][k]) || i18n.en[k] || k; }
function applyLanguage(){
    document.documentElement.lang = lang;
    document.title = t('title');
    document.querySelectorAll('[data-i18n]').forEach(el => el.textContent = t(el.dataset.i18n));
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => el.placeholder = t(el.dataset.i18nPlaceholder));
    document.querySelectorAll('[data-img-alt]').forEach(el => el.setAttribute('alt', t(el.dataset.imgAlt)));
}
const langSelect = document.getElementById('languageSelect');
langSelect.value = lang;
langSelect.addEventListener('change', () => {
    lang = langSelect.value;
    localStorage.setItem('lang', lang);
    applyLanguage();
});
applyLanguage();

window.addEventListener('scroll', () => {
    const y = Math.min(window.scrollY, 300);
    document.documentElement.style.setProperty('--hero-parallax', `${y * 0.12}px`);
}, { passive: true });

const revealEls = [...document.querySelectorAll('.reveal')];
const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            revealObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.12 });
revealEls.forEach(el => revealObserver.observe(el));

</script>
</body>
</html>
