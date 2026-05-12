<?php
declare(strict_types=1);

require_once __DIR__ . '/php/layout.php';

page_start('Scenario A — Workshop intake demo');
?>
<section>
    <p>
        This project satisfies <strong>Scenario A</strong> tasks <strong>A.01–A.10</strong>:
        accessible HTML form controls, server-side processing with PHP <code>filter_*</code> validation,
        SQL injection safe inserts via prepared statements, and a companion <code>db/database.sql</code>
        script for phpMyAdmin.
    </p>
    <p class="field-hint">Use the buttons below (wired in <code>js/script.js</code>) or the navigation links.</p>
    <p>
        <button type="button" id="btn-go-register">Go to application form</button>
        <button type="button" id="btn-go-login" class="secondary">Go to login info</button>
    </p>
</section>
<?php
page_end();
