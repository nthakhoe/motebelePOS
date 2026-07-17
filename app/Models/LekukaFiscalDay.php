<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LekukaFiscalDay extends Model
{
    protected $fillable = [

        'company_id',

        'branch_id',

        'device_id',

        'correlation_id',

        'fiscal_day_no',

        'business_date',

        'opened_at',

        'closed_at',

        'status',

        'response'
    ];

    protected $casts = [

        'business_date' => 'date',

        'opened_at' => 'datetime',

        'closed_at' => 'datetime',

        'response' => 'array'
    ];

    public function device()
    {
        return $this->belongsTo(
            LekukaDevice::class,
            'device_id'
        );
    }
}
