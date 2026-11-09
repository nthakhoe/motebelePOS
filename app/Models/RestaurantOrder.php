<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class RestaurantOrder extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY = 'ready';
    public const STATUS_SERVED = 'served';
    public const STATUS_BILL_REQUESTED = 'bill_requested';
    public const STATUS_PAID = 'paid';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_OPEN, self::STATUS_SUBMITTED, self::STATUS_PREPARING,
        self::STATUS_READY, self::STATUS_SERVED, self::STATUS_BILL_REQUESTED,
        self::STATUS_PAID, self::STATUS_CLOSED, self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'company_id', 'branch_id', 'table_id', 'waiter_id', 'order_number',
        'guest_count', 'customer_name', 'status', 'subtotal', 'tax_amount',
        'discount_amount', 'total_amount', 'notes', 'opened_at',
        'bill_requested_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'guest_count' => 'integer',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'opened_at' => 'datetime',
            'bill_requested_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function table(): BelongsTo { return $this->belongsTo(RestaurantTable::class, 'table_id'); }
    public function waiter(): BelongsTo { return $this->belongsTo(User::class, 'waiter_id'); }
    public function items(): HasMany { return $this->hasMany(RestaurantOrderItem::class, 'restaurant_order_id'); }
    public function statusLogs(): HasMany { return $this->hasMany(RestaurantOrderStatusLog::class, 'restaurant_order_id'); }
}
