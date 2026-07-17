<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LekukaAuditLog extends Model
{
    protected $fillable = [

        'company_id',

        'branch_id',

        'device_id',

        'user_id',

        'sale_id',

        'correlation_id',

        'action',

        'endpoint',

        'http_method',

        'http_status',

        'status',

        'request',

        'response',

        'error_message',

        'duration_ms',

        'ip_address',

        'reference_no',
    ];

    protected $casts = [

        'request' => 'array',

        'response' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function device()
    {
        return $this->belongsTo(LekukaDevice::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}