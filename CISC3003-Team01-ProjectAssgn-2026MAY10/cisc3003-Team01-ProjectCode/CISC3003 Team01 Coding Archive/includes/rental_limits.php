<?php
declare(strict_types=1);

/**
 * Single-ride time limit and overtime penalty (MOP) applied on return.
 */
const RENTAL_MAX_MINUTES = 120;
const RENTAL_OVERTIME_PENALTY_MOP = 500.0;

/**
 * @return array{base: float, penalty: float, total: float}
 */
function rental_fee_with_overtime(float $baseRentalFee, int $durationMinutes): array
{
    $penalty = $durationMinutes > RENTAL_MAX_MINUTES ? RENTAL_OVERTIME_PENALTY_MOP : 0.0;
    $total = round($baseRentalFee + $penalty, 2);
    return [
        'base' => round($baseRentalFee, 2),
        'penalty' => round($penalty, 2),
        'total' => $total,
    ];
}
