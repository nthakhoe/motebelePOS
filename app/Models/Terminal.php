<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Terminal extends Model
{
    protected $fillable = [

        'company_id',

        'branch_id',

        'terminal_name',

        'terminal_code',

        'serial_number',

        'computer_name',

        'ip_address',

        'mac_address',

        'status',

        'default_terminal',

        'is_active',

        'description',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function settings()
    {
        return $this->hasOne(TerminalSetting::class);
    }

    public function device()
    {
        return $this->hasOne(TerminalDevice::class);
    }

    protected static function booted(): void
    {
        static::created(function (Terminal $terminal) {

            $terminal->settings()->create();

            $terminal->device()->create();

        });
    }

    public function registerSessions()
    {
        return $this->hasMany(RegisterSession::class);
    }

    public function currentSession()
    {
        return $this->hasOne(RegisterSession::class)
            ->where('status','Open');
    }

    public function cashMovements()
    {
        return $this->hasMany(CashMovement::class);
    }
}
