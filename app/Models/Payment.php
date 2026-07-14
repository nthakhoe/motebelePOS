<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [

        'company_id',

        'branch_id',

        'sale_id',

        'payment_method_id',

        'user_id',

        'amount_due',

        'amount_received',

        'amount_paid',

        'change_amount',

        'reference_number',

        'authorization_code',

        'status',

        'payment_date',

        'remarks',

    ];

    protected $casts = [

        'amount_due' => 'decimal:2',

        'amount_received' => 'decimal:2',

        'amount_paid' => 'decimal:2',

        'change_amount' => 'decimal:2',

        'payment_date' => 'datetime',

    ];

    /*
     |--------------------------------------------------------------------------
     | Relationships
     |--------------------------------------------------------------------------
     */

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}