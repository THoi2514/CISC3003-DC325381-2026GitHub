<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/connect.php';

page_start('Scenario A — Dashboard', ['css/dashboad.css']);
?>
<?php
$flash = $_SESSION['flash_ok'] ?? '';
unset($_SESSION['flash_ok']);
$lastId = isset($_SESSION['last_insert_id']) ? (int) $_SESSION['last_insert_id'] : 0;

$row = null;
if ($lastId > 0) {
    try {
        $stmt = db()->prepare('SELECT id, full_name, email, created_at FROM workshop_applications WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $lastId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
    } catch (Throwable $e) {
        $row = null;
    }
}
?>

<?php if ($flash !== ''): ?>
    <div class="flash ok"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="dash-card">
    <h2>Latest saved application</h2>
    <?php if (is_array($row)): ?>
        <p><strong>Name:</strong> <?= htmlspecialchars((string) $row['full_name'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars((string) $row['email'], ENT_QUOTES, 'UTF-8') ?></p>
        <p class="dash-meta"><strong>Recorded at:</strong> <?= htmlspecialchars((string) $row['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
        <p class="field-hint">This timestamp is what you can screenshot for “data saved to MySQL” evidence.</p>
    <?php else: ?>
        <p>No recent submission in this session. Submit the <a href="register.php">application form</a> first.</p>
    <?php endif; ?>
</section>
<?php
page_end();
