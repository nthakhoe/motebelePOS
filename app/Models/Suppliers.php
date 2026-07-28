<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Suppliers extends Model
{
    use HasFactory;

    protected $fillable = [

        'company_id',

        'supplier_code',

        'business_name',

        'contact_person',

        'phone',
        'alternative_phone',
        'email',
        'website',

        'tin_number',
        'vat_number',

        'address_line1',
        'address_line2',
        'city',
        'district',
        'country',

        'bank_name',
        'account_name',
        'account_number',
        'branch_name',

        'credit_days',

        'opening_balance',
        'current_balance',

        'is_active',

        'notes',
    ];

    protected $casts = [

        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',

        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
