<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [

        'purchase_order_id',

        'product_id',

        'ordered_quantity',
        'received_quantity',
        'remaining_quantity',

        'unit_cost',

        'discount',
        'tax',

        'line_total',

        'remarks',

    ];

    protected $casts = [

        'ordered_quantity' => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'remaining_quantity' => 'decimal:2',

        'unit_cost' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'line_total' => 'decimal:2',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    protected function outstandingQuantity(): Attribute
    {
        return Attribute::make(
            get: fn () => max(
                0,
                $this->ordered_quantity - $this->received_quantity
            )
        );
    }

    protected function receivedPercentage(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->ordered_quantity == 0
                ? 0
                : round(
                    ($this->received_quantity / $this->ordered_quantity) * 100,
                    2
                )
        );
    }
}