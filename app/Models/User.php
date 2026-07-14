<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Spatie\Permission\Traits\HasRoles;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles;


    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {

            'admin' => $this->hasRole('Super Admin'),

            'company' => $this->hasAnyRole([
                'Company Admin',
                'Manager',
            ]),

            'cashier' => $this->hasRole('Cashier'),

            default => false,
        };
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [

        'company_id',

        'branch_id',

        'name',

        'email',

        'password',

        'employee_number',

        'phone',

        'position',

        'is_active',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function registerSessions()
    {
        return $this->hasMany(RegisterSession::class);
    }

    public function cashMovements()
    {
        return $this->hasMany(CashMovement::class);
    }
}
