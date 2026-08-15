<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'sale_id',
        'user_id',
        'register_id',
        'refund_number',
        'total_amount',
        'refund_method',
        'reference_number',
        'reason',
        'remarks',
        'status',
        'refunded_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'refunded_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(Register::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RefundItem::class);
    }
}