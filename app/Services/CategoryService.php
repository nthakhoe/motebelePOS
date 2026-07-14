<?php

namespace App\Services;

use App\Models\Category;

class CategoryService
{
    public static function generateCode()
    {
        return 'CAT'
            . str_pad(
                Category::max('id') + 1,
                6,
                '0',
                STR_PAD_LEFT
            );
    }
}