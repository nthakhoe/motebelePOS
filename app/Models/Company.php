<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [

        'company_name',
        'trading_name',

        'tax_number',
        'vat_number',
        'registration_number',

        'phone',
        'mobile',
        'email',

        'website',

        'physical_address',

        'city',
        'district',

        'country',

        'currency',

        'timezone',

        'logo',

        'is_active',
    ];

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function terminals()
    {
        return $this->hasMany(Terminal::class);
    }
}
