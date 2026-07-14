<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentItem extends Model
{
    protected $fillable = [

        'stock_adjustment_id',

        'product_id',

        'system_quantity',

        'counted_quantity',

        'adjustment_quantity',

        'unit_cost',

        'total_cost',

        'reason',

        'remarks',

    ];

    protected $casts = [

        'system_quantity'     => 'decimal:3',

        'counted_quantity'    => 'decimal:3',

        'adjustment_quantity' => 'decimal:3',

        'unit_cost'           => 'decimal:2',

        'total_cost'          => 'decimal:2',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function adjustment()
    {
        return $this->belongsTo(
            StockAdjustment::class,
            'stock_adjustment_id'
        );
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getVarianceAttribute(): float
    {
        return $this->counted_quantity - $this->system_quantity;
    }

    public function getAdjustmentTypeAttribute(): string
    {
        if ($this->adjustment_quantity > 0) {
            return 'Increase';
        }

        if ($this->adjustment_quantity < 0) {
            return 'Decrease';
        }

        return 'No Change';
    }
}