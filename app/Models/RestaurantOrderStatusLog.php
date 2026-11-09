<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantOrderStatusLog extends Model
{
    protected $fillable = ['restaurant_order_id', 'changed_by', 'from_status', 'to_status', 'notes'];

    public function order(): BelongsTo { return $this->belongsTo(RestaurantOrder::class, 'restaurant_order_id'); }
    public function changedBy(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
}
