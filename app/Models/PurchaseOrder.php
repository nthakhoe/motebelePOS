<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [

        'company_id',
        'branch_id',
        'supplier_id',

        'purchase_order_no',

        'order_date',
        'expected_delivery_date',

        'status',

        'subtotal',
        'discount',
        'tax',
        'total',

        'remarks',

        'created_by',
        'approved_by',
        'approved_at',

    ];

    protected $casts = [

        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'approved_at' => 'datetime',

        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Suppliers::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    protected function totalItems(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->items()->count()
        );
    }

    protected function totalQuantity(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->items()->sum('ordered_quantity')
        );
    }

    protected function totalReceived(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->items()->sum('received_quantity')
        );
    }

    protected function isFullyReceived(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->items()
                ->whereColumn('received_quantity', '<', 'ordered_quantity')
                ->doesntExist()
        );
    }
}