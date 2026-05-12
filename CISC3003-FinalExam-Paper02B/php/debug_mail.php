<?php
/**
 * B.04: show last SMTP debug buffer / PHPMailer error after a failed send (PRG landing page).
 */
declare(strict_types=1);

session_start();

require_once __DIR__ . '/layout.php';

$err = (string) ($_SESSION['mail_error'] ?? '');
$debug = (string) ($_SESSION['mail_debug'] ?? '');
unset($_SESSION['mail_error'], $_SESSION['mail_debug']);

page_start('Scenario B — Mail debug output');
?>
<section class="dash-card">
    <h2>Last mailer status</h2>
    <?php if ($err === '' && $debug === ''): ?>
        <p>No captured debug yet. Try sending the contact form with an intentional wrong password to populate this view.</p>
    <?php else: ?>
        <h3>ErrorInfo / exception message</h3>
        <pre class="debug-box"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></pre>
        <h3>SMTP session transcript</h3>
        <pre class="debug-box"><?= htmlspecialchars($debug, ENT_QUOTES, 'UTF-8') ?></pre>
    <?php endif; ?>
    <p><a href="../index.php">Back to contact form</a></p>
</section>
<?php
page_end();
