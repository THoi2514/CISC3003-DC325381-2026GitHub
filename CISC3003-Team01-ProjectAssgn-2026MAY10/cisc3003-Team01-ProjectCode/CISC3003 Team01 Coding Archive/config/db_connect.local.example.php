<?php
/**
 * Local + Production overrides (optional)
 *
 * 1. Copy this file to: config/db_connect.local.php
 * 2. Fill both local and production values.
 * 3. Never commit db_connect.local.php (it is listed in .gitignore).
 *
 * Runtime auto switch:
 * - Local runtime -> LOCAL_DB_* values
 * - Production runtime -> PROD_DB_* values
 * - DB_* can still be used as a generic fallback
 */
return [
    // Local XAMPP
    'LOCAL_DB_HOST' => '127.0.0.1',
    'LOCAL_DB_PORT' => '3307',
    'LOCAL_DB_NAME' => 'um_rental_system',
    'LOCAL_DB_USER' => 'um_app',
    'LOCAL_DB_PASS' => '',

    // Hosting / production (example: InfinityFree)
    'PROD_DB_HOST' => 'sql103.infinityfree.com',
    'PROD_DB_PORT' => '3306',
    'PROD_DB_NAME' => 'if0_41825875_YOUR_DB_SUFFIX',
    'PROD_DB_USER' => 'if0_41825875',
    'PROD_DB_PASS' => 'paste-mysql-password-here',

    'DEBUG_MODE' => false,
];
