<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'customer_code',
        'customer_type',

        'first_name',
        'last_name',
        'business_name',

        'phone',
        'alternative_phone',
        'email',

        'tax_number',
        'id_number',

        'country',
        'district',
        'city',
        'physical_address',

        'credit_limit',
        'current_balance',

        'is_walk_in',
        'is_active',

        'notes',
    ];

    protected $casts = [
        'credit_limit'   => 'decimal:2',
        'current_balance'=> 'decimal:2',
        'is_walk_in'     => 'boolean',
        'is_active'      => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFullNameAttribute(): string
    {
        if ($this->customer_type === 'business') {
            return $this->business_name;
        }

        return trim($this->first_name . ' ' . $this->last_name);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWalkIn($query)
    {
        return $query->where('is_walk_in', true);
    }

    public function scopeRegistered($query)
    {
        return $query->where('is_walk_in', false);
    }
}