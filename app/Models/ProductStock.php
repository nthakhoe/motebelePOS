<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    protected $table = 'product_stocks';
    
    protected $fillable = [

        'company_id',

        'branch_id',

        'product_id',

        'quantity_on_hand',

        'quantity_reserved',

        'quantity_available',

        'average_cost',

        'last_cost',

        'minimum_stock',

        'maximum_stock',

        'reorder_level',

        'last_received_at',

        'last_sold_at',
    ];

    protected $casts = [

        'quantity_on_hand' => 'decimal:3',

        'quantity_reserved' => 'decimal:3',

        'quantity_available' => 'decimal:3',

        'average_cost' => 'decimal:2',

        'last_cost' => 'decimal:2',

        'minimum_stock' => 'decimal:3',

        'maximum_stock' => 'decimal:3',

        'reorder_level' => 'decimal:3',

        'last_received_at' => 'datetime',

        'last_sold_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
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

    public function getIsLowStockAttribute(): bool
    {
        return $this->quantity_on_hand <= $this->reorder_level;
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->quantity_on_hand <= 0;
    }
}