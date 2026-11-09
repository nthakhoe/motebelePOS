<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantOrderItem extends Model
{
    protected $fillable = [
        'restaurant_order_id', 'product_id', 'product_name', 'sku', 'quantity',
        'unit_price', 'tax_rate', 'subtotal', 'tax_amount', 'total_amount',
        'special_instructions',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(RestaurantOrder::class, 'restaurant_order_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
