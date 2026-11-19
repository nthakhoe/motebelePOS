<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

        // Pricing
        'cost_price',
        'margin_percentage',
        'selling_price',
        'minimum_price',

        // Main Lekuka tax
        'tax_rate',
        'lekuka_tax_id',
        'lekuka_tax_type',
        'lekuka_tax_code',

        // Additional Lekuka tax
        'lekuka_additional_tax_id',
        'lekuka_additional_tax_type',
        'lekuka_additional_tax_code',
        'lekuka_additional_tax_rate',

        // Inventory
        'track_inventory',
        'allow_negative_stock',
        'minimum_stock',
        'maximum_stock',
        'reorder_level',

        // Dimensions
        'weight',
        'length',
        'width',
        'height',

        // Product status
        'is_service',
        'is_active',

        // Media
        'image',

        // Audit
        'created_by',
        'updated_by',
    ];

    protected $casts = [

        // Pricing
        'cost_price' => 'decimal:2',
        'margin_percentage' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'minimum_price' => 'decimal:2',

        // Main tax
        'tax_rate' => 'decimal:2',
        'lekuka_tax_id' => 'integer',

        // Additional tax
        'lekuka_additional_tax_id' => 'integer',
        'lekuka_additional_tax_rate' => 'decimal:4',

        // Inventory
        'minimum_stock' => 'decimal:2',
        'maximum_stock' => 'decimal:2',
        'reorder_level' => 'decimal:2',

        // Dimensions
        'weight' => 'decimal:3',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',

        // Boolean fields
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