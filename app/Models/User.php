<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /** Roles a user may request; "admin" is never self-assignable. */
    public const REQUESTABLE_ROLES = ['doctor', 'pharmacist', 'lab_tech', 'storekeeper', 'accountant', 'hr'];

    /** Role => dashboard key used by the web portal. */
    public const DASHBOARDS = [
        'admin' => 'admin',
        'doctor' => 'doctor',
        'pharmacist' => 'pharmacy',
        'lab_tech' => 'lab_tech',
        'storekeeper' => 'storekeeper',
        'accountant' => 'accountant',
        'hr' => 'hr',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved' && $this->role !== 'pending';
    }

    public function isAdmin(): bool
    {
        return $this->isApproved() && $this->role === 'admin';
    }

    public function dashboardKey(): ?string
    {
        return self::DASHBOARDS[$this->role] ?? null;
    }
}
