<?php
/**
 * Scenario A — database connection (mysqli).
 * A.07: user input is never concatenated into SQL; use prepared statements in processors.
 */
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/** 與 phpMyAdmin 一致請用 localhost；127.0.0.1 可能被視為另一種連線而出現 Access denied。 */
const DB_HOST = 'localhost';
const DB_NAME = 'cisc3003_paper02a';
const DB_USER = 'root';
/** 須與本機 MySQL「root」密碼一致；若空白會出現 Access denied (using password: NO)。 */
const DB_PASS = '';
/** 須與 phpMyAdmin 首頁「伺服器」埠號一致（未寫則預設連 3306，會連錯）。標準 XAMPP 常為 3306。 */
const DB_PORT = 3307;
const DB_CHARSET = 'utf8mb4';

function db(): mysqli
{
    static $mysqli = null;
    if ($mysqli instanceof mysqli) {
        return $mysqli;
    }
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    $mysqli->set_charset(DB_CHARSET);
    return $mysqli;
}
