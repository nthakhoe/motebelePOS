<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LekukaConfiguration extends Model
{
    protected $fillable = [

        'company_id',

        'branch_id',

        'device_id',

        'operation_id',

        'device_serial_no',

        'device_operating_mode',

        'taxpayer_name',

        'taxpayer_tin',

        'vat_number',

        'branch_name',

        'branch_address',

        'branch_contacts',

        'device_reporting_frequency',

        'taxpayer_day_max_hours',

        'day_end_notification_hours',

        'receipt_forms',

        'taxes',

        'payment_types',

        'raw_response',

        'downloaded_at',
    ];

    protected $casts = [

        'branch_address' => 'array',

        'branch_contacts' => 'array',

        'receipt_forms' => 'array',

        'taxes' => 'array',

        'payment_types' => 'array',

        'raw_response' => 'array',

        'downloaded_at' => 'datetime',
    ];
}
