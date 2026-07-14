<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_products');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_products');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_products');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_products');
    }
}
