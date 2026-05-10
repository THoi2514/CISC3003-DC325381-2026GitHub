<?php
declare(strict_types=1);
$projectRoot = dirname(__DIR__, 2);

require_once $projectRoot . '/includes/auth.php';
startAuthSession();

if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string)$_SESSION['csrf_token'];

if (isset($_SESSION['user_id'])) {
    $role = (string)($_SESSION['role'] ?? '');
    if ($role === 'admin') {
        header('Location: ./admin/admin_dashboard.php');
    } elseif ($role === 'staff') {
        header('Location: ./staff/home.php');
    } else {
        header('Location: ./dashboard.php');
    }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = (string)($_POST['_csrf'] ?? '');
    if ($postedCsrf === '' || !hash_equals($csrfToken, $postedCsrf)) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $loginId = trim((string)($_POST['login_id'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        try {
            $result = attemptLogin($loginId, $password);
            if ($result['ok'] === true) {
                $role = (string)($_SESSION['role'] ?? '');
                if ($role === 'admin') {
                    header('Location: ./admin/admin_dashboard.php');
                } elseif ($role === 'staff') {
                    header('Location: ./staff/home.php');
                } else {
                    header('Location: ./dashboard.php');
                }
                exit;
            }
            $error = (string)$result['message'];
        } catch (Throwable $e) {
            $error = 'Login failed unexpectedly. Please try again.';
        }
    }
}

$oauthError = trim((string)($_GET['oauth_error'] ?? ''));
if ($oauthError !== '') {
    $error = $oauthError;
}

$authActive = 'login';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require $projectRoot . '/includes/favicon_links.php'; ?>
    <title data-i18n="pageTitle">Login | UM Rental</title>
    <link rel="stylesheet" href="./assets/um_landing.css?v=<?= file_exists($projectRoot . '/assets/um_landing.css') ? filemtime($projectRoot . '/assets/um_landing.css') : time() ?>">
    <link rel="stylesheet" href="./assets/home-landing.css?v=<?= file_exists($projectRoot . '/assets/home-landing.css') ? filemtime($projectRoot . '/assets/home-landing.css') : time() ?>">
    <link rel="stylesheet" href="./assets/login.css?v=<?= file_exists($projectRoot . '/assets/login.css') ? filemtime($projectRoot . '/assets/login.css') : time() ?>">
    <link rel="stylesheet" href="./assets/site-footer.css?v=<?= file_exists($projectRoot . '/assets/site-footer.css') ? filemtime($projectRoot . '/assets/site-footer.css') : time() ?>">
</head>
<body class="auth-body">
<?php require $projectRoot . '/includes/auth_header.php'; ?>

<div class="auth-page-fill">
    <div class="auth-split">
        <aside class="auth-aside" style="--auth-aside-img: url('./assets/images/home/hero-bg.jpg');">
            <div class="auth-aside-logo-wrap">
                <img src="./assets/images/brand/um-rental.png" alt="UM Rental logo" class="auth-aside-logo" width="640" style="max-width:98%;height:auto;max-height:460px;">
            </div>
            <div class="auth-aside-inner">
                <h2 class="auth-aside-title" data-i18n="authAsideTitle">Campus mobility, one account</h2>
                <p class="auth-aside-desc" data-i18n="authAsideDesc">Rent bicycles and e-scooters at UM stations—with transparent pricing and trip history.</p>
            </div>
        </aside>
        <div class="auth-form-column">
            <section class="login-card auth-card">
                <h1 data-i18n="titleText">UM Rental Login</h1>
                <p data-i18n="subtitle">Sign in using your Campus ID or email and password.</p>

                <?php if ($error !== ''): ?>
                    <div class="error-box"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <a class="btn btn-google" href="./api/google_login_start.php">
                    <svg class="google-logo" viewBox="0 0 48 48" aria-hidden="true" focusable="false">
                        <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12
                c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24
                c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/>
                        <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039
                l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/>
                        <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.133,35.091,26.715,36,24,36
                c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/>
                        <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571
                c0.001-0.001,0.003-0.002,0.004-0.003l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/>
                    </svg>
                    <span data-i18n="googleBtn">Continue with Google</span>
                </a>
                <div class="divider"><span data-i18n="orText">or</span></div>

                <form method="post" action="./login.php">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                    <label for="login_id" data-i18n="loginIdLabel">Campus ID or Email</label>
                    <input id="login_id" name="login_id" required placeholder="e.g. s1234567 or user@um.edu.mo" data-i18n-placeholder="loginIdPlaceholder">

                    <label for="password" data-i18n="passwordLabel">Password</label>
                    <input id="password" name="password" type="password" required placeholder="Enter password" data-i18n-placeholder="passwordPlaceholder">

                    <button class="btn" type="submit" data-i18n="loginBtn">Login</button>
                </form>

                <div class="auth-links-block">
                    <p class="auth-switch-line"><span data-i18n="authNoAccount"></span> <a href="./register.php" data-i18n="authRegisterHere">Register here</a></p>
                    <p class="auth-sub-link"><a href="./home.php" data-i18n="backHome">Back to Home</a></p>
                </div>
            </section>
        </div>
    </div>
</div>
<?php require $projectRoot . '/includes/public_footer.php'; ?>
<script src="./assets/footer_i18n.js?v=<?= file_exists($projectRoot . '/assets/footer_i18n.js') ? filemtime($projectRoot . '/assets/footer_i18n.js') : time() ?>"></script>
<script>
const i18n = mergeFooterI18n({
    en: {
        pageTitle: 'Login | UM Rental',
        navBrand: 'UM Rental',
        navHome: 'Home',
        navFeatured: 'Services',
        navAbout: 'Why us',
        navHowto: 'How to use',
        navContact: 'Contact Us',
        cta: 'Login / Use Service',
        langLabel: 'Language',
        authAsideTitle: 'Campus mobility, one account',
        authAsideDesc: 'Rent bicycles and e-scooters at UM stations—with transparent pricing and trip history.',
        titleText: 'UM Rental Login',
        subtitle: 'Sign in using your Campus ID or email and password.',
        googleBtn: 'Continue with Google',
        orText: 'or',
        loginIdLabel: 'Campus ID or Email',
        loginIdPlaceholder: 'e.g. s1234567 or user@um.edu.mo',
        passwordLabel: 'Password',
        passwordPlaceholder: 'Enter password',
        loginBtn: 'Login',
        authNoAccount: 'New here?',
        authRegisterHere: 'Create an account',
        backHome: 'Back to Home',
        authHasAccount: 'Already have an account?',
        authLoginHere: 'Login here'
    },
    'zh-CN': {
        pageTitle: '登录 | UM 租赁',
        navBrand: 'UM 租赁',
        navHome: '首页',
        navFeatured: '服务',
        navAbout: '核心价值',
        navHowto: '使用说明',
        navContact: '联系我们',
        cta: '立即登录 / 使用服务',
        langLabel: '语言',
        authAsideTitle: '智慧出行，一个账号',
        authAsideDesc: '在 UM 站点租借单车与电动滑板车——计费透明，行程可查。',
        titleText: 'UM 租赁登录',
        subtitle: '使用校园 ID 或电子邮箱和密码登录。',
        googleBtn: '使用 Google 继续',
        orText: '或',
        loginIdLabel: '校园 ID 或邮箱',
        loginIdPlaceholder: '例如 s1234567 或 user@um.edu.mo',
        passwordLabel: '密码',
        passwordPlaceholder: '请输入密码',
        loginBtn: '登录',
        authNoAccount: '新用户？',
        authRegisterHere: '立即注册',
        backHome: '返回主页',
        authHasAccount: '已有账号？',
        authLoginHere: '前往登录'
    },
    'zh-TW': {
        pageTitle: '登入 | UM 租賃',
        navBrand: 'UM 租賃',
        navHome: '首頁',
        navFeatured: '服務',
        navAbout: '核心價值',
        navHowto: '使用說明',
        navContact: '聯繫我們',
        cta: '立即登入 / 使用服務',
        langLabel: '語言',
        authAsideTitle: '智慧出行，一個帳號',
        authAsideDesc: '在 UM 站點租借單車與電動滑板車——計費透明，行程可查。',
        titleText: 'UM 租賃登入',
        subtitle: '使用校園 ID 或電子郵件與密碼登入。',
        googleBtn: '使用 Google 繼續',
        orText: '或',
        loginIdLabel: '校園 ID 或電郵',
        loginIdPlaceholder: '例如 s1234567 或 user@um.edu.mo',
        passwordLabel: '密碼',
        passwordPlaceholder: '請輸入密碼',
        loginBtn: '登入',
        authNoAccount: '新用戶？',
        authRegisterHere: '建立帳戶',
        backHome: '返回主頁',
        authHasAccount: '已有帳戶？',
        authLoginHere: '前往登入'
    }
});

let lang = localStorage.getItem('lang') || 'en';
if (!i18n[lang]) lang = 'en';

function t(k) {
    return (i18n[lang] && i18n[lang][k]) || i18n.en[k] || k;
}

function applyLanguage() {
    document.documentElement.lang = lang;
    document.title = t('pageTitle');
    document.querySelectorAll('[data-i18n]').forEach((el) => { el.textContent = t(el.dataset.i18n); });
    document.querySelectorAll('[data-i18n-placeholder]').forEach((el) => { el.placeholder = t(el.dataset.i18nPlaceholder); });
}

const langSelect = document.getElementById('languageSelect');
if (langSelect) {
    langSelect.value = lang;
    langSelect.addEventListener('change', () => {
        lang = langSelect.value;
        localStorage.setItem('lang', lang);
        applyLanguage();
    });
}
applyLanguage();
</script>
</body>
</html>
