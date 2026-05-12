<?php
/**
 * Minimal autoloader for bundled PHPMailer (Scenario B/C) when Composer is unavailable.
 */
declare(strict_types=1);

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
