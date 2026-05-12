<?php
declare(strict_types=1);

require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/connect.php';

page_start('Scenario B — Dashboard', ['css/dashboad.css']);

try {
    $res = db()->query('SELECT id, sender_name, sender_email, subject, outcome, created_at FROM contact_log ORDER BY id DESC LIMIT 15');
    $rows = $res->fetch_all(MYSQLI_ASSOC);
} catch (Throwable $e) {
    $rows = [];
}
?>
<section class="dash-card">
    <h2>Recent contact attempts</h2>
    <?php if ($rows === []): ?>
        <p>No rows (import <code>database.sql</code> and send at least one message).</p>
    <?php else: ?>
        <table>
            <thead>
            <tr>
                <th>When</th>
                <th>From</th>
                <th>Subject</th>
                <th>Outcome</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $r['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) $r['sender_name'], ENT_QUOTES, 'UTF-8') ?>
                        &lt;<?= htmlspecialchars((string) $r['sender_email'], ENT_QUOTES, 'UTF-8') ?>&gt;</td>
                    <td><?= htmlspecialchars((string) $r['subject'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) $r['outcome'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php
page_end();
