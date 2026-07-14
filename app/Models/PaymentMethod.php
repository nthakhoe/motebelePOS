<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'requires_reference',
        'is_cash',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'requires_reference' => 'boolean',
        'is_cash' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Sales using this payment method.
     */
    public function salePayments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    /**
     * Scope active payment methods.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}