<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/php/layout.php';

$sent = isset($_GET['sent']) && $_GET['sent'] === '1';
$errors = $_SESSION['contact_errors'] ?? [];
$old = $_SESSION['contact_old'] ?? [];
unset($_SESSION['contact_errors']);

page_start('Scenario B — Contact form (PHPMailer)');
?>
<?php if ($sent): ?>
    <div class="flash ok" role="status">Message sent successfully (check inbox / spam).</div>
<?php endif; ?>

<?php if ($errors !== []): ?>
    <div class="flash err" role="alert">
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars((string) $err, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- B.01 HTML contact form + HTML5 validation attributes -->
<form id="contact-form" method="post" action="php/process_contact.php" novalidate>
    <input type="hidden" name="_return" value="index">
    <label for="sender_name">Your name</label>
    <input type="text" id="sender_name" name="sender_name" required minlength="2" maxlength="120"
           autocomplete="name"
           value="<?= htmlspecialchars((string) ($old['sender_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label for="sender_email">Your email</label>
    <input type="email" id="sender_email" name="sender_email" required maxlength="190"
           autocomplete="email"
           value="<?= htmlspecialchars((string) ($old['sender_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label for="subject">Subject</label>
    <input type="text" id="subject" name="subject" required minlength="3" maxlength="200"
           value="<?= htmlspecialchars((string) ($old['subject'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label for="message">Message</label>
    <textarea id="message" name="message" rows="8" required minlength="10" maxlength="4000"><?=
        htmlspecialchars((string) ($old['message'] ?? ''), ENT_QUOTES, 'UTF-8')
    ?></textarea>

    <p class="field-hint">B.02/B.03: configure <code>php/mailer_config.php</code> after copying the sample file.</p>

    <p>
        <button type="submit">Send message</button>
    </p>
</form>
<?php
unset($_SESSION['contact_old']);
page_end();
