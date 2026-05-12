<?php
declare(strict_types=1);

require_once __DIR__ . '/layout.php';

page_start('Scenario A — Login (demo)');
?>
<section>
    <p>
        Scenario A focuses on form intake and database insertion. There is no password-based user table in
        this scenario; use the <a href="register.php">application form</a> to exercise PHP processing and
        MySQL inserts, then review the confirmation on <a href="dashboard.php">dashboard</a>.
    </p>
</section>
<?php
page_end();
