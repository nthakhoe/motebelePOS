<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'user_id',
        'adjustment_type',
        'quantity',
        'stock_before',
        'stock_after',
        'reference_no',
        'reason',
        'adjustment_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'stock_before' => 'decimal:2',
        'stock_after' => 'decimal:2',
        'adjustment_date' => 'datetime',
    ];

    /**
     * Company that owns the adjustment.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Product that was adjusted.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * User who performed the adjustment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if the adjustment increased stock.
     */
    public function isIncrease(): bool
    {
        return $this->adjustment_type === 'increase';
    }

    /**
     * Determine if the adjustment decreased stock.
     */
    public function isDecrease(): bool
    {
        return $this->adjustment_type === 'decrease';
    }

    public function items()
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    /**
     * Display badge color in Filament.
     */
    public function getAdjustmentColorAttribute(): string
    {
        return match ($this->adjustment_type) {
            'increase' => 'success',
            'decrease' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Display friendly adjustment type.
     */
    public function getAdjustmentLabelAttribute(): string
    {
        return ucfirst($this->adjustment_type);
    }
}
