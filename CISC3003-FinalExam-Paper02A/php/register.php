<?php
/**
 * A.01–A.04: HTML form best practices (labels, semantics), text inputs, textarea,
 * select list, radios, checkboxes.
 */
declare(strict_types=1);

session_start();

require_once __DIR__ . '/layout.php';

$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['form_errors']);

page_start('Scenario A — Application form');
?>
<?php if ($errors !== []): ?>
    <div class="flash err" role="alert">
        <p>Please fix the highlighted fields.</p>
        <ul>
            <?php foreach ($errors as $msg): ?>
                <li><?= htmlspecialchars((string) $msg, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form class="form-grid" method="post" action="process_application.php" novalidate>
    <!-- A.02 text inputs -->
    <label for="full_name">Full name</label>
    <input type="text" id="full_name" name="full_name" autocomplete="name" required maxlength="120"
           value="<?= htmlspecialchars((string) ($old['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label for="email">Email</label>
    <input type="email" id="email" name="email" autocomplete="email" required maxlength="190"
           value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label for="phone">Phone</label>
    <input type="tel" id="phone" name="phone" autocomplete="tel" required maxlength="40"
           value="<?= htmlspecialchars((string) ($old['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <!-- A.04 select list -->
    <label for="country">Country / region</label>
    <select id="country" name="country" required>
        <?php
        $countries = [
            '' => 'Select…',
            'Macau' => 'Macau SAR',
            'Hong Kong' => 'Hong Kong SAR',
            'Mainland China' => 'Mainland China',
            'Other' => 'Other',
        ];
        $sel = (string) ($old['country'] ?? '');
        foreach ($countries as $val => $label) {
            $attrs = $sel === $val ? ' selected' : '';
            echo '<option value="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . "\"$attrs>"
                . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
        }
        ?>
    </select>

    <!-- A.04 radio buttons -->
    <fieldset>
        <legend>Experience level</legend>
        <?php
        $levels = [
            'beginner' => 'Beginner',
            'intermediate' => 'Intermediate',
            'advanced' => 'Advanced',
        ];
        $expOld = (string) ($old['experience_level'] ?? '');
        foreach ($levels as $val => $label) {
            $id = 'exp_' . $val;
            $chk = $expOld === $val ? ' checked' : '';
            echo '<label for="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">';
            echo '<input type="radio" name="experience_level" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8')
                . '" value="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . "\" required$chk> ";
            echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            echo '</label><br>';
        }
        ?>
    </fieldset>

    <!-- A.04 checkboxes (multiple) -->
    <fieldset>
        <legend>Workshop topics (pick at least one)</legend>
        <?php
        $topicMap = [
            'php' => 'PHP',
            'mysql' => 'MySQL',
            'html' => 'HTML',
            'css' => 'CSS',
            'javascript' => 'JavaScript',
        ];
        $oldTopics = [];
        if (isset($old['topics']) && is_array($old['topics'])) {
            $oldTopics = array_map('strval', $old['topics']);
        }
        foreach ($topicMap as $val => $label) {
            $id = 'topic_' . $val;
            $chk = in_array($val, $oldTopics, true) ? ' checked' : '';
            echo '<label for="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">';
            echo '<input type="checkbox" name="topics[]" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8')
                . '" value="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . "\"$chk> ";
            echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            echo '</label><br>';
        }
        ?>
    </fieldset>

    <label>
        <input type="checkbox" name="wants_newsletter" value="1" <?= !empty($old['wants_newsletter']) ? 'checked' : '' ?>>
        Send me occasional workshop updates (optional)
    </label>

    <!-- A.03 textarea -->
    <label for="comments">Comments / motivation (multi-line)</label>
    <textarea id="comments" name="comments" rows="6" maxlength="4000" required><?=
        htmlspecialchars((string) ($old['comments'] ?? ''), ENT_QUOTES, 'UTF-8')
    ?></textarea>

    <p>
        <button type="submit">Submit application</button>
        <a class="secondary" href="../index.php">Cancel</a>
    </p>
</form>
<?php
page_end();
