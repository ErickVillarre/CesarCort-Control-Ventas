<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'employee_id',
        'role_id',
        'must_change_password',
        'is_active',
        'last_login_at',
    ];

    protected $appends = [
        'permissions',
        'role_name',
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
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function employee()
    {
        return $this->belongsTo(Empleado::class, 'employee_id');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role?->name === 'admin' || $this->rol === 'admin') {
            return true;
        }

        return $this->role
            ? $this->role->permissions->contains('name', $permission)
            : false;
    }

    public function getPermissionsAttribute(): array
    {
        if ($this->role?->name === 'admin' || $this->rol === 'admin') {
            return ['*'];
        }

        return $this->role
            ? $this->role->permissions->pluck('name')->values()->all()
            : [];
    }

    public function getRoleNameAttribute(): string
    {
        return $this->role?->name ?? $this->rol ?? 'vendedor';
    }
}
