<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LekukaDevice extends Model
{
    protected $fillable = [

        'company_id',

        'branch_id',

        'device_id',

        'activation_key',

        'serial_number',

        'device_model',

        'device_model_version',

        'certificate_path',

        'private_key_path',

        'thumbprint',

        'configuration',

        'registered',

        'registered_at',

        'last_ping_at',
    ];

    protected $casts = [

        'configuration' => 'array',

        'registered' => 'boolean',

        'registered_at' => 'datetime',

        'last_ping_at' => 'datetime',
    ];

    public function fiscalDays()
    {
        return $this->hasMany(LekukaFiscalDay::class,'device_id');
    }
}
