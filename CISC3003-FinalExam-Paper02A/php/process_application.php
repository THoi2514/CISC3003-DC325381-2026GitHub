<?php
/**
 * Scenario A — A.05 process POST, A.06 filter validation, A.07/A.08 prepared INSERT.
 * Uses POST/redirect/GET style flash via session for dashboard display.
 */
declare(strict_types=1);

session_start();

require_once __DIR__ . '/site_config.php';
require_once __DIR__ . '/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php', true, 303);
    exit;
}

/** @var array<string, string> $errors */
$errors = [];

// A.02 simple text inputs
$fullName = isset($_POST['full_name']) ? trim((string) $_POST['full_name']) : '';
$phone = isset($_POST['phone']) ? trim((string) $_POST['phone']) : '';
$country = isset($_POST['country']) ? trim((string) $_POST['country']) : '';

// A.03 textarea
$comments = isset($_POST['comments']) ? trim((string) $_POST['comments']) : '';

// A.04 select / radios / checkboxes
$experience = isset($_POST['experience_level']) ? (string) $_POST['experience_level'] : '';
$topics = isset($_POST['topics']) && is_array($_POST['topics']) ? $_POST['topics'] : [];
$wantsNewsletter = isset($_POST['wants_newsletter']) ? '1' : '0';

// Email (also simple text control, validated as email)
$email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';

// A.06 — validate using filter / filter_var style checks
if ($fullName === '' || mb_strlen($fullName) < 2) {
    $errors['full_name'] = 'Please enter your full name (at least 2 characters).';
}

$emailFiltered = filter_var($email, FILTER_VALIDATE_EMAIL);
if ($emailFiltered === false) {
    $errors['email'] = 'Please provide a valid email address.';
} else {
    $email = $emailFiltered;
}

$phoneOk = filter_var(
    $phone,
    FILTER_VALIDATE_REGEXP,
    ['options' => ['regexp' => '/^[0-9+()\\-\\s]{6,40}$/']]
);
if ($phoneOk === false) {
    $errors['phone'] = 'Please enter a phone number (6–40 chars, digits and + ( ) - spaces).';
} else {
    $phone = $phoneOk;
}

if ($country === '') {
    $errors['country'] = 'Please choose a country/region.';
}

$allowedLevels = ['beginner', 'intermediate', 'advanced'];
if (!in_array($experience, $allowedLevels, true)) {
    $errors['experience_level'] = 'Please select a valid experience level.';
}

$allowedTopics = ['php', 'mysql', 'html', 'css', 'javascript'];
$topics = array_values(array_filter(
    array_map('strval', $topics),
    static fn(string $t): bool => in_array($t, $allowedTopics, true)
));
if ($topics === []) {
    $errors['topics'] = 'Pick at least one workshop topic.';
}
$topicsCsv = implode(',', $topics);

if ($comments === '' || mb_strlen($comments) < 10) {
    $errors['comments'] = 'Comments must be at least 10 characters (textarea).';
}

if ($errors !== []) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old'] = $_POST;
    header('Location: register.php', true, 303);
    exit;
}

// A.08 prepared statement insert (A.07: no string-built SQL with user data)
$sql = 'INSERT INTO workshop_applications
        (full_name, email, phone, country, experience_level, topics, comments, wants_newsletter)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

try {
    $mysqli = db();
    $stmt = $mysqli->prepare($sql);
    $newsletterInt = $wantsNewsletter === '1' ? 1 : 0;
    $stmt->bind_param(
        'sssssssi',
        $fullName,
        $email,
        $phone,
        $country,
        $experience,
        $topicsCsv,
        $comments,
        $newsletterInt
    );
    $stmt->execute();
    $newId = (int) $mysqli->insert_id;
    $stmt->close();
} catch (Throwable $e) {
    $detail = 'Could not save your application. Check DB import and connection settings.';
    if (defined('DEBUG_DB') && DEBUG_DB) {
        $detail .= ' [' . $e->getMessage() . ']';
    }
    $_SESSION['form_errors'] = ['database' => $detail];
    $_SESSION['old'] = $_POST;
    header('Location: register.php', true, 303);
    exit;
}

$_SESSION['flash_ok'] = 'Application saved successfully.';
$_SESSION['last_insert_id'] = $newId;
unset($_SESSION['old'], $_SESSION['form_errors']);

header('Location: dashboard.php', true, 303);
exit;
