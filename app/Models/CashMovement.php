<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    protected $guarded = [];

    protected $casts = [

        'approved_at'=>'datetime',

    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function terminal()
    {
        return $this->belongsTo(Terminal::class);
    }

    public function registerSession()
    {
        return $this->belongsTo(RegisterSession::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class,'approved_by');
    }
}