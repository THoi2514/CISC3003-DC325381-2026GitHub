<?php
/**
 * 本機除錯：連線 MySQL 並列出 workshop_applications 是否存在。
 * 瀏覽器開：http://localhost/專案資料夾/php/db_check.php
 */
declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

require_once __DIR__ . '/connect.php';

try {
    $mysqli = db();
    echo "OK: connected to database.\n";

    $res = $mysqli->query(
        "SELECT COUNT(*) AS c FROM information_schema.tables
         WHERE table_schema = 'cisc3003_paper02a' AND table_name = 'workshop_applications'"
    );
    $row = $res->fetch_assoc();
    echo 'Table workshop_applications: ' . (($row['c'] ?? '0') === '1' ? 'exists' : 'MISSING — import database.sql') . "\n";
} catch (Throwable $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
    echo "Hint: MySQL running? DB imported? php/connect.php DB_USER / DB_PASS correct?\n";
}
