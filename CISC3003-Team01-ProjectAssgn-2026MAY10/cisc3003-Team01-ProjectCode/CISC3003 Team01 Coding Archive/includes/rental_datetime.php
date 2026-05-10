<?php
declare(strict_types=1);

/**
 * Format a rental_orders.start_time value for JavaScript Date parsing (always unambiguous).
 * Input is treated as Asia/Macau wall clock (matches DATETIME after SET time_zone +08:00).
 */
function rental_start_time_for_js(string $sqlDatetime): string
{
    $s = trim($sqlDatetime);
    if ($s === '') {
        return $sqlDatetime;
    }
    if (preg_match('/\.\d+$/', $s)) {
        $s = (string)preg_replace('/\.\d+$/', '', $s);
    }
    $tzMacau = new DateTimeZone('Asia/Macau');
    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $s, $tzMacau);
    if ($dt === false) {
        try {
            $dt = new DateTimeImmutable($s, $tzMacau);
        } catch (Throwable $e) {
            return $sqlDatetime;
        }
    }
    return $dt->format(DATE_ATOM);
}
