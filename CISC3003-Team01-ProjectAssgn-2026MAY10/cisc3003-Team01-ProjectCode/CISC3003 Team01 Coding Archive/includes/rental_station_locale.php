<?php
declare(strict_types=1);

/**
 * Whether rental_stations has name_zh_cn / name_zh_tw (migration applied).
 */
function rentalStationsHasLocaleColumns(PDO $pdo): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    try {
        $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
        if (!is_string($db) || $db === '') {
            $cache = false;
            return false;
        }
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = \'rental_stations\'
               AND COLUMN_NAME IN (\'name_zh_cn\', \'name_zh_tw\')'
        );
        $stmt->execute([$db]);
        $cache = ((int)$stmt->fetchColumn() >= 2);
        return $cache;
    } catch (Throwable $e) {
        $cache = false;
        return false;
    }
}
