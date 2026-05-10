<?php
declare(strict_types=1);

require_once __DIR__ . '/public_paths.php';

$b = $footerBase ?? '';
$footerLogo = $b . 'assets/images/brand/um-rental.png';
$footerHome = $b . 'home.php';
?>
<footer class="site-footer" id="contact" role="contentinfo">
    <div class="site-footer-inner">
        <div class="site-footer-brand">
            <a class="brand-footer-lockup" href="<?= htmlspecialchars($footerHome) ?>">
                <span class="brand-footer-logo-box" aria-hidden="true">
                    <img class="brand-footer-logo" src="<?= htmlspecialchars($footerLogo) ?>" alt="" decoding="async" loading="lazy">
                </span>
                <span class="brand-footer-text brand-wordmark" data-i18n="navBrand">UM Rental</span>
            </a>
        </div>
        <div class="site-footer-grid">
            <div class="site-footer-col">
                <h3 class="site-footer-heading" data-i18n="footColAbout">About UM Mobility</h3>
                <ul class="site-footer-links">
                    <li><a href="<?= htmlspecialchars($b) ?>home.php" data-i18n="footLinkHome">Home</a></li>
                    <li><a href="<?= htmlspecialchars($b) ?>about.php" data-i18n="footLinkAbout">About the system</a></li>
                    <li><a href="<?= htmlspecialchars($b) ?>services.php" data-i18n="footLinkServices">Services</a></li>
                    <li><a href="<?= htmlspecialchars($b) ?>contact.php" data-i18n="footLinkContact">Contact</a></li>
                </ul>
            </div>
            <div class="site-footer-col">
                <h3 class="site-footer-heading" data-i18n="footColPartner">Portals & access</h3>
                <ul class="site-footer-links">
                    <li><a href="<?= htmlspecialchars($b) ?>login.php" data-i18n="footLinkLogin">Login</a></li>
                    <li><a href="<?= htmlspecialchars($b) ?>register.php" data-i18n="footLinkRegister">Register</a></li>
                    <li><a href="<?= htmlspecialchars($b) ?>staff/home.php" data-i18n="footLinkStaff">Staff portal</a></li>
                    <li><a href="<?= htmlspecialchars($b) ?>admin/admin_dashboard.php" data-i18n="footLinkAdmin">Admin portal</a></li>
                </ul>
            </div>
            <div class="site-footer-col">
                <h3 class="site-footer-heading" data-i18n="footColLegal">Terms & policies</h3>
                <ul class="site-footer-links">
                    <li><a href="<?= htmlspecialchars($b) ?>about.php" data-i18n="footLinkTerms">Terms of service</a></li>
                    <li><a href="<?= htmlspecialchars($b) ?>contact.php" data-i18n="footLinkPrivacy">Privacy policy</a></li>
                    <li><a href="<?= htmlspecialchars($b) ?>about.php" data-i18n="footLinkCookies">Cookie notice</a></li>
                </ul>
            </div>
            <div class="site-footer-col site-footer-col-pay">
                <h3 class="site-footer-heading" data-i18n="footColPay">Payment methods</h3>
                <p class="site-footer-pay-hint muted" data-i18n="footPayHint">Wallet & campus payments (demo badges)</p>
                <div class="pay-badge-grid" role="group" aria-label="Payment badges">
                    <span class="pay-badge pay-visa">VISA</span>
                    <span class="pay-badge pay-mc">Mastercard</span>
                    <span class="pay-badge pay-amex">AMEX</span>
                    <span class="pay-badge pay-jcb">JCB</span>
                    <span class="pay-badge pay-line">LINE Pay</span>
                    <span class="pay-badge pay-apple">Apple Pay</span>
                    <span class="pay-badge pay-jko">JKO Pay</span>
                    <span class="pay-badge pay-aftee">AFTEE</span>
                    <span class="pay-badge pay-google">Google Pay</span>
                </div>
            </div>
        </div>
        <div class="site-footer-divider" aria-hidden="true"></div>
        <div class="site-footer-bottom">
            <p class="site-footer-legal" data-i18n="footLegalNotice">Legal</p>
            <div class="site-footer-social" aria-label="Social">
                <span class="visually-hidden" data-i18n="footSocialLabel">Follow us</span>
                <a class="social-icon" href="#" aria-label="Facebook">
                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M22 12a10 10 0 10-11.5 9.95v-7.05H7V12h3.5V9.5c0-3.45 2.2-3.5 3.52-3.5.96 0 2 .07 2 .07V9h-1.75c-1.03 0-1.35.64-1.35 1.3V12h3.35l-.53 3.9H12.5V22A10 10 0 0022 12z"/></svg>
                </a>
                <a class="social-icon" href="#" aria-label="Instagram">
                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M7 2h10a5 5 0 015 5v10a5 5 0 01-5 5H7a5 5 0 01-5-5V7a5 5 0 015-5zm5 4.5a4.5 4.5 0 100 9 4.5 4.5 0 000-9zM18 6.3a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"/></svg>
                </a>
                <a class="social-icon" href="#" aria-label="Threads">
                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M12 2C7 2 3 6 3 11v7h4v-7c0-3 2.5-5.5 5.5-5.5S18 8 18 11v7h4v-7c0-5-4-9-10-9zm-7 18v2h14v-2H5z"/></svg>
                </a>
            </div>
        </div>
    </div>
</footer>
