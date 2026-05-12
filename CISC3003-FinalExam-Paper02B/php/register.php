<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/layout.php';

$errors = $_SESSION['contact_errors'] ?? [];
$old = $_SESSION['contact_old'] ?? [];
unset($_SESSION['contact_errors']);

page_start('Scenario B — Register interest');
?>
<?php if (isset($_GET['sent']) && $_GET['sent'] === '1'): ?>
    <div class="flash ok" role="status">Interest message sent successfully.</div>
<?php endif; ?>

<p>This page reuses the same mail pipeline to satisfy the shared deliverable filenames for Paper 02.</p>

<?php if ($errors !== []): ?>
    <div class="flash err" role="alert">
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars((string) $err, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form id="register-interest-form" method="post" action="process_contact.php" novalidate>
    <input type="hidden" name="_return" value="register">
    <input type="hidden" name="subject" value="Course interest registration">

    <label for="sender_name">Your name</label>
    <input type="text" id="sender_name" name="sender_name" required minlength="2" maxlength="120"
           value="<?= htmlspecialchars((string) ($old['sender_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label for="sender_email">Your email</label>
    <input type="email" id="sender_email" name="sender_email" required maxlength="190"
           value="<?= htmlspecialchars((string) ($old['sender_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label for="message">What would you like to follow up on?</label>
    <textarea id="message" name="message" rows="6" required minlength="10" maxlength="4000"><?=
        htmlspecialchars((string) ($old['message'] ?? ''), ENT_QUOTES, 'UTF-8')
    ?></textarea>

    <p>
        <button type="submit">Send interest</button>
    </p>
</form>
<?php
unset($_SESSION['contact_old']);
page_end();
