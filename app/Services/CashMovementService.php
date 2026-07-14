<?php

namespace App\Services;

use App\Models\CashMovement;

class CashMovementService
{
    public static function generateReference(): string
    {
        return 'CM-'
            . now()->format('Ymd')
            . '-'
            . str_pad(
                CashMovement::count() + 1,
                6,
                '0',
                STR_PAD_LEFT
            );
    }
}