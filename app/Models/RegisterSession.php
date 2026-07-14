<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegisterSession extends Model
{
    protected $guarded = [];

    protected $casts = [

        'opened_at'=>'datetime',

        'closed_at'=>'datetime',
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

    public function cashier()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class,'opened_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class,'closed_by');
    }

    public function cashMovements()
    {
        return $this->hasMany(CashMovement::class);
    }
}