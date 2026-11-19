<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'unit_id',
        'quantity',
        'unit_price',
        'discount',

        // Main tax allocation
        'tax_rate',
        'lekuka_tax_id',
        'lekuka_tax_type',
        'lekuka_tax_code',

        // Additional tax allocation
        'lekuka_additional_tax_id',
        'lekuka_additional_tax_type',
        'lekuka_additional_tax_code',
        'lekuka_additional_tax_rate',

        // Financial values
        'tax_amount',
        'line_total',
        'cost_price',
        'profit',

        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',

        // Main tax
        'tax_rate' => 'decimal:2',
        'lekuka_tax_id' => 'integer',

        // Additional tax
        'lekuka_additional_tax_id' => 'integer',
        'lekuka_additional_tax_rate' => 'decimal:4',

        // Financial values
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'profit' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function getSubtotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }

    public function calculateProfit(): float
    {
        return ($this->unit_price - $this->cost_price) * $this->quantity;
    }
}