<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Payment;
use App\Models\LekukaReceipt;


class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'register_id',
        'user_id',
        'customer_id',

        'sale_number',

        'subtotal',
        'discount',
        'tax',
        'total',

        'amount_paid',
        'change',

        'sale_type',
        'status',

        'submitted_to_lekuka',
        'lekuka_receipt_id',
        'qr_code',

        'remarks',

        'completed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',

        'amount_paid' => 'decimal:2',
        'change' => 'decimal:2',

        'submitted_to_lekuka' => 'boolean',

        'completed_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(RegisterSession::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function lekukaReceipt()
    {
        return $this->belongsTo(
            LekukaReceipt::class,
            'lekuka_receipt_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getItemsCountAttribute(): int
    {
        return $this->items()->count();
    }

    public function getBalanceAttribute(): float
    {
        return max(0, $this->total - $this->amount_paid);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->amount_paid >= $this->total;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function getRefundedAmountAttribute(): float
    {
        return (float) $this->refunds()
            ->where('status', 'completed')
            ->sum('total_amount');
    }

    public function getRefundableAmountAttribute(): float
    {
        return max(
            0,
            (float) $this->total - $this->refunded_amount
        );
    }
}