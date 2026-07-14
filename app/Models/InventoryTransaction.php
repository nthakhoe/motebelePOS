<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [

        'quantity'=>'decimal:3',

        'unit_cost'=>'decimal:2',

        'unit_price'=>'decimal:2',

        'balance_after'=>'decimal:3',

    ];

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

    public function creator()
    {
        return $this->belongsTo(User::class,'created_by');
    }

    public function getCurrentStockAttribute(): float
    {
        $in = $this->inventoryTransactions()
            ->where('movement','IN')
            ->sum('quantity');

        $out = $this->inventoryTransactions()
            ->where('movement','OUT')
            ->sum('quantity');

        return $in - $out;
    }
}
