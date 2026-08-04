<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LekukaReceipt extends Model
{
    protected $fillable = [

        'company_id',

        'branch_id',

        'device_id',

        'sale_id',

        'correlation_id',

        'receipt_number',

        'receipt_global_no',

        'receipt_counter',

        'fiscal_day_no',

        'qr_code',

        'verification_code',

        'server_signature',

        'device_hash',

        'device_signature',

        'status',

        'request',

        'response',

        'submitted_at',

    ];

    protected $casts = [

        'request' => 'array',

        'response' => 'array',

        'submitted_at' => 'datetime',

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

    public function device(): BelongsTo
    {
        return $this->belongsTo(LekukaDevice::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isSubmitted(): bool
    {
        return $this->status === 'SUBMITTED';
    }

    public function isFailed(): bool
    {
        return $this->status === 'FAILED';
    }

    public function hasQrCode(): bool
    {
        return ! empty($this->qr_code);
    }

    public function hasVerificationCode(): bool
    {
        return ! empty($this->verification_code);
    }

    public function hasServerSignature(): bool
    {
        return ! empty($this->server_signature);
    }

    public function hasDeviceSignature(): bool
    {
        return ! empty($this->device_signature);
    }

    public function hasDeviceHash(): bool
    {
        return ! empty($this->device_hash);
    }
}