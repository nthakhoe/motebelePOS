<?php

namespace App\Services;

class RegisterSessionService
{
    public static function generateNumber(): string
    {
        return 'RS-'
            . now()->format('Ymd')
            . '-'
            . str_pad(
                RegisterSession::count()+1,
                5,
                '0',
                STR_PAD_LEFT
            );
    }
}