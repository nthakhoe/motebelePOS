<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\InventoryTransaction;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [

        'company_id',

        'category_id',

        'unit_id',

        'sku',

        'barcode',

        'product_name',

        'short_name',

        'description',

        'cost_price',

        'selling_price',

        'minimum_price',

        'tax_rate',

        'track_inventory',

        'allow_negative_stock',

        'minimum_stock',

        'maximum_stock',

        'reorder_level',

        'weight',

        'length',

        'width',

        'height',

        'is_service',

        'is_active',

        'image',

        'created_by',

        'updated_by',
    ];

    protected $casts = [

        'cost_price' => 'decimal:2',

        'selling_price' => 'decimal:2',

        'minimum_price' => 'decimal:2',

        'tax_rate' => 'decimal:2',

        'minimum_stock' => 'decimal:2',

        'maximum_stock' => 'decimal:2',

        'reorder_level' => 'decimal:2',

        'weight' => 'decimal:3',

        'length' => 'decimal:2',

        'width' => 'decimal:2',

        'height' => 'decimal:2',

        'track_inventory' => 'boolean',

        'allow_negative_stock' => 'boolean',

        'is_service' => 'boolean',

        'is_active' => 'boolean',
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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function stockAdjustmentItems()
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Future Relationships
    |--------------------------------------------------------------------------
    */

    public function prices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getDisplayNameAttribute(): string
    {
        return "{$this->sku} - {$this->product_name}";
    }

}