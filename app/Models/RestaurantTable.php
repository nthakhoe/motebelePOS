<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantTable extends Model
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_OCCUPIED = 'occupied';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_CLEANING = 'cleaning';

    public const STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_OCCUPIED,
        self::STATUS_RESERVED,
        self::STATUS_CLEANING,
    ];

    protected $fillable = ['company_id', 'branch_id', 'area_id', 'table_number', 'capacity', 'status', 'notes', 'is_active'];

    protected function casts(): array
    {
        return ['capacity' => 'integer', 'is_active' => 'boolean'];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function area(): BelongsTo { return $this->belongsTo(RestaurantArea::class, 'area_id'); }
    public function orders(): HasMany { return $this->hasMany(RestaurantOrder::class, 'table_id'); }
    public function activeOrder(): HasMany
    {
        return $this->orders()->whereNotIn('status', [RestaurantOrder::STATUS_PAID, RestaurantOrder::STATUS_CLOSED, RestaurantOrder::STATUS_CANCELLED]);
    }
}
