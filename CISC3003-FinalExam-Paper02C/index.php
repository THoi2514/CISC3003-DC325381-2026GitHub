<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/php/layout.php';

page_start('Scenario C — Sign up / Sign in demo');
?>
<section>
    <p>
        Scenario C implements server-side validation, MySQL persistence, browser validation, Ajax email checks,
        PHPMailer-based activation and password reset, and a small post-login dashboard (C.09).
    </p>
    <p>
        <button type="button" id="btn-go-register">Sign up</button>
        <button type="button" id="btn-go-login" class="secondary">Sign in</button>
    </p>
</section>
<?php
page_end();
