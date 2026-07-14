<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [

        'company_id',

        'branch_name',

        'branch_code',

        'phone',

        'email',

        'address',

        'is_head_office',

        'is_active',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function terminals()
    {
        return $this->hasMany(Terminal::class);
    }

    public function productStocks()
    {
        return $this->hasMany(ProductStock::class);
    }
}
