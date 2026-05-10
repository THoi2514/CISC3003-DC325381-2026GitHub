<?php
declare(strict_types=1);
$projectRoot = dirname(__DIR__, 2);

$authActive = 'register';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require $projectRoot . '/includes/favicon_links.php'; ?>
    <title data-i18n="pageTitle">Register | UM Rental</title>
    <link rel="stylesheet" href="./assets/um_landing.css?v=<?= file_exists($projectRoot . '/assets/um_landing.css') ? filemtime($projectRoot . '/assets/um_landing.css') : time() ?>">
    <link rel="stylesheet" href="./assets/home-landing.css?v=<?= file_exists($projectRoot . '/assets/home-landing.css') ? filemtime($projectRoot . '/assets/home-landing.css') : time() ?>">
    <link rel="stylesheet" href="./assets/login.css?v=<?= file_exists($projectRoot . '/assets/login.css') ? filemtime($projectRoot . '/assets/login.css') : time() ?>">
    <link rel="stylesheet" href="./assets/site-footer.css?v=<?= file_exists($projectRoot . '/assets/site-footer.css') ? filemtime($projectRoot . '/assets/site-footer.css') : time() ?>">
</head>
<body class="auth-body">
<?php require $projectRoot . '/includes/auth_header.php'; ?>

<div class="auth-page-fill">
    <div class="auth-split">
        <aside class="auth-aside" style="--auth-aside-img: url('./assets/images/home/feature-campus.jpg');">
            <div class="auth-aside-logo-wrap">
                <img src="./assets/images/brand/um-rental.png" alt="UM Rental logo" class="auth-aside-logo" width="640" style="max-width:98%;height:auto;max-height:460px;">
            </div>
            <div class="auth-aside-inner">
                <h2 class="auth-aside-title" data-i18n="authAsideTitleReg">Create your campus profile</h2>
                <p class="auth-aside-desc" data-i18n="authAsideDescReg">Register with your UM email, then sign in with Campus ID or email.</p>
            </div>
        </aside>
        <div class="auth-form-column">
            <section class="login-card auth-card auth-card-wide">
                <h1 data-i18n="title">Create Account</h1>
                <p data-i18n="subtitle">Register with your email, then log in using Campus ID or email.</p>

                <div id="messageBox" class="error-box is-hidden"></div>

                <form id="registerForm">
                    <label for="campus_id" data-i18n="campusId">Campus ID</label>
                    <input id="campus_id" required placeholder="e.g. s1234567" data-i18n-placeholder="campusIdPlaceholder">

                    <label for="full_name" data-i18n="fullName">Full Name</label>
                    <input id="full_name" required placeholder="e.g. Chan Tai Man" data-i18n-placeholder="fullNamePlaceholder">

                    <label for="role" data-i18n="roleLabel">Account type</label>
                    <select id="role" required>
                        <option value="student" data-i18n="roleStudent">Student</option>
                        <option value="teacher" data-i18n="roleTeacher">Teacher / faculty</option>
                    </select>

                    <label for="email" data-i18n="email">Email</label>
                    <input id="email" type="email" required placeholder="e.g. user@um.edu.mo" data-i18n-placeholder="emailPlaceholder">

                    <div class="verification-row">
                        <div class="verification-input-wrap">
                            <label for="verification_code" data-i18n="verificationCode">Verification code</label>
                            <input id="verification_code" inputmode="numeric" maxlength="6" required placeholder="6-digit code" data-i18n-placeholder="verificationCodePlaceholder">
                        </div>
                        <button class="btn verification-send-btn" type="button" id="sendCodeBtn" data-i18n="sendCodeBtn">Send Code</button>
                    </div>

                    <label for="phone" data-i18n="phone">Phone (optional)</label>
                    <input id="phone" placeholder="e.g. +853-0000-0000" data-i18n-placeholder="phonePlaceholder">

                    <label for="password" data-i18n="password">Password</label>
                    <input id="password" type="password" required placeholder="At least 8 chars with letters and numbers" data-i18n-placeholder="passwordPlaceholder">

                    <label for="confirm_password" data-i18n="confirmPassword">Confirm Password</label>
                    <input id="confirm_password" type="password" required placeholder="Re-enter your password" data-i18n-placeholder="confirmPasswordPlaceholder">

                    <button class="btn" type="submit" data-i18n="registerBtn">Register</button>
                </form>

                <div class="auth-links-block">
                    <p class="auth-switch-line"><span data-i18n="authHasAccount"></span> <a href="./login.php" data-i18n="authLoginHere">Login here</a></p>
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
        pageTitle: 'Register | UM Rental',
        navBrand: 'UM Rental',
        navHome: 'Home',
        navFeatured: 'Services',
        navAbout: 'Why us',
        navHowto: 'How to use',
        navContact: 'Contact Us',
        cta: 'Login / Use Service',
        langLabel: 'Language',
        authAsideTitleReg: 'Create your campus profile',
        authAsideDescReg: 'Register with your UM email, then sign in with Campus ID or email.',
        title: 'Create Account',
        subtitle: 'Register with your email, then log in using Campus ID or email.',
        campusId: 'Campus ID', campusIdPlaceholder: 'e.g. s1234567',
        fullName: 'Full Name', fullNamePlaceholder: 'e.g. Chan Tai Man',
        email: 'Email', emailPlaceholder: 'e.g. user@um.edu.mo',
        verificationCode: 'Verification code', verificationCodePlaceholder: '6-digit code',
        sendCodeBtn: 'Send Code',
        sendCodeResend: 'Resend ({sec}s)',
        sendCodeSending: 'Sending...',
        verificationEmailRequired: 'Please enter a valid email before requesting a code.',
        verificationCodeSent: 'Verification code sent. Check your inbox.',
        verificationCodeDevHint: 'Local mode: use this test code - {code}',
        verificationCodeRequired: 'Please enter a valid 6-digit verification code.',
        phone: 'Phone (optional)', phonePlaceholder: 'e.g. +853-0000-0000',
        password: 'Password', passwordPlaceholder: 'At least 8 chars with letters and numbers',
        confirmPassword: 'Confirm Password', confirmPasswordPlaceholder: 'Re-enter your password',
        registerBtn: 'Register',
        backHome: 'Back to Home',
        authHasAccount: 'Already have an account?',
        authLoginHere: 'Login here',
        passwordsNotMatch: 'Passwords do not match.',
        registerSuccess: 'Registration successful. Redirecting to login...',
        registerFailed: 'Registration failed.',
        roleLabel: 'Account type',
        roleStudent: 'Student',
        roleTeacher: 'Teacher / faculty',
    },
    'zh-CN': {
        pageTitle: '注册 | UM 租赁门户',
        navBrand: 'UM 租赁',
        navHome: '首页',
        navFeatured: '服务',
        navAbout: '核心价值',
        navHowto: '使用说明',
        navContact: '联系我们',
        cta: '立即登录 / 使用服务',
        langLabel: '语言',
        authAsideTitleReg: '建立校园账户',
        authAsideDescReg: '使用 UM 邮箱注册后，可用校园 ID 或邮箱登录。',
        title: '创建账户',
        subtitle: '使用邮箱注册后，可用校园 ID 或邮箱登录。',
        campusId: '校园 ID', campusIdPlaceholder: '例如 s1234567',
        fullName: '姓名', fullNamePlaceholder: '例如 陈大文',
        email: '邮箱', emailPlaceholder: '例如 user@um.edu.mo',
        verificationCode: '验证码', verificationCodePlaceholder: '6位数字验证码',
        sendCodeBtn: '发送验证码',
        sendCodeResend: '重新发送（{sec}秒）',
        sendCodeSending: '发送中...',
        verificationEmailRequired: '请先输入有效邮箱，再发送验证码。',
        verificationCodeSent: '验证码已发送，请检查邮箱。',
        verificationCodeDevHint: '本机模式：请使用测试验证码 - {code}',
        verificationCodeRequired: '请输入正确的6位数字验证码。',
        phone: '电话（可选）', phonePlaceholder: '例如 +853-0000-0000',
        password: '密码', passwordPlaceholder: '至少8位，包含字母和数字',
        confirmPassword: '确认密码', confirmPasswordPlaceholder: '再次输入密码',
        registerBtn: '注册',
        backHome: '返回主页',
        authHasAccount: '已有账号？',
        authLoginHere: '前往登录',
        passwordsNotMatch: '两次输入的密码不一致。',
        registerSuccess: '注册成功，正在跳转到登录页...',
        registerFailed: '注册失败。',
        roleLabel: '账户类型',
        roleStudent: '学生',
        roleTeacher: '教职员',
    },
    'zh-TW': {
        pageTitle: '註冊 | UM 租賃入口',
        navBrand: 'UM 租賃',
        navHome: '首頁',
        navFeatured: '服務',
        navAbout: '核心價值',
        navHowto: '使用說明',
        navContact: '聯繫我們',
        cta: '立即登入 / 使用服務',
        langLabel: '語言',
        authAsideTitleReg: '建立校園帳戶',
        authAsideDescReg: '使用 UM 電郵註冊後，可用校園 ID 或電郵登入。',
        title: '建立帳戶',
        subtitle: '使用電郵註冊後，可用校園 ID 或電郵登入。',
        campusId: '校園 ID', campusIdPlaceholder: '例如 s1234567',
        fullName: '姓名', fullNamePlaceholder: '例如 陳大文',
        email: '電郵', emailPlaceholder: '例如 user@um.edu.mo',
        verificationCode: '驗證碼', verificationCodePlaceholder: '6位數字驗證碼',
        sendCodeBtn: '發送驗證碼',
        sendCodeResend: '重新發送（{sec}秒）',
        sendCodeSending: '發送中...',
        verificationEmailRequired: '請先輸入有效電郵，再發送驗證碼。',
        verificationCodeSent: '驗證碼已發送，請檢查電郵。',
        verificationCodeDevHint: '本機模式：請使用測試驗證碼 - {code}',
        verificationCodeRequired: '請輸入正確的6位數字驗證碼。',
        phone: '電話（可選）', phonePlaceholder: '例如 +853-0000-0000',
        password: '密碼', passwordPlaceholder: '至少8位，包含字母和數字',
        confirmPassword: '確認密碼', confirmPasswordPlaceholder: '再次輸入密碼',
        registerBtn: '註冊',
        backHome: '返回主頁',
        authHasAccount: '已有帳戶？',
        authLoginHere: '前往登入',
        passwordsNotMatch: '兩次輸入的密碼不一致。',
        registerSuccess: '註冊成功，正在跳轉到登入頁...',
        registerFailed: '註冊失敗。',
        roleLabel: '帳戶類型',
        roleStudent: '學生',
        roleTeacher: '教職員',
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
    setSendCodeBtnState(false);
}

const form = document.getElementById('registerForm');
const messageBox = document.getElementById('messageBox');
const sendCodeBtn = document.getElementById('sendCodeBtn');
let sendCodeCooldownTimer = null;
let sendCodeCooldownSec = 0;

function showMessage(text, isError = true) {
    messageBox.classList.remove('is-hidden');
    messageBox.textContent = text;
    if (isError) {
        messageBox.style.background = '#fef2f2';
        messageBox.style.color = '#b91c1c';
        messageBox.style.borderColor = '#fecaca';
    } else {
        messageBox.style.background = '#ecfdf5';
        messageBox.style.color = '#065f46';
        messageBox.style.borderColor = '#a7f3d0';
    }
}

function setSendCodeBtnState(isSending = false) {
    if (!sendCodeBtn) return;
    if (isSending) {
        sendCodeBtn.disabled = true;
        sendCodeBtn.textContent = t('sendCodeSending');
        return;
    }
    if (sendCodeCooldownSec > 0) {
        sendCodeBtn.disabled = true;
        sendCodeBtn.textContent = t('sendCodeResend').replace('{sec}', String(sendCodeCooldownSec));
        return;
    }
    sendCodeBtn.disabled = false;
    sendCodeBtn.textContent = t('sendCodeBtn');
}

function startSendCodeCooldown(seconds) {
    sendCodeCooldownSec = Math.max(0, Number(seconds || 0));
    if (sendCodeCooldownTimer) clearInterval(sendCodeCooldownTimer);
    setSendCodeBtnState(false);
    if (sendCodeCooldownSec <= 0) return;
    sendCodeCooldownTimer = setInterval(() => {
        sendCodeCooldownSec -= 1;
        if (sendCodeCooldownSec <= 0) {
            clearInterval(sendCodeCooldownTimer);
            sendCodeCooldownTimer = null;
            sendCodeCooldownSec = 0;
        }
        setSendCodeBtnState(false);
    }, 1000);
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const payload = {
        campusID: document.getElementById('campus_id').value.trim(),
        fullName: document.getElementById('full_name').value.trim(),
        role: document.getElementById('role').value,
        email: document.getElementById('email').value.trim(),
        phone: document.getElementById('phone').value.trim(),
        password: document.getElementById('password').value,
        verificationCode: document.getElementById('verification_code').value.trim(),
    };

    const confirmPassword = document.getElementById('confirm_password').value;
    if (payload.password !== confirmPassword) {
        showMessage(t('passwordsNotMatch'));
        return;
    }
    if (!/^\d{6}$/.test(payload.verificationCode)) {
        showMessage(t('verificationCodeRequired'));
        return;
    }

    try {
        const response = await fetch('./api/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || t('registerFailed'));

        showMessage(t('registerSuccess'), false);
        setTimeout(() => {
            window.location.href = './login.php';
        }, 800);
    } catch (error) {
        showMessage(error.message || t('registerFailed'));
    }
});

if (sendCodeBtn) {
    sendCodeBtn.addEventListener('click', async () => {
        const email = document.getElementById('email').value.trim();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showMessage(t('verificationEmailRequired'));
            return;
        }
        try {
            setSendCodeBtnState(true);
            const response = await fetch('./api/register_send_code.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email }),
            });
            const data = await response.json();
            if (!data.success) throw new Error(data.message || t('registerFailed'));

            if (data.debugCode) {
                showMessage(t('verificationCodeDevHint').replace('{code}', String(data.debugCode)), false);
            } else {
                showMessage(t('verificationCodeSent'), false);
            }
            startSendCodeCooldown(Number(data.cooldownSec || 60));
        } catch (error) {
            showMessage(error.message || t('registerFailed'));
            setSendCodeBtnState(false);
        }
    });
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
