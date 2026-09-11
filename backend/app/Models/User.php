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
        'cliente_id',
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

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role?->name === 'admin' || $this->rol === 'admin') {
            return true;
        }

        $permissionsToCheck = array_unique([
            $permission,
            self::permissionAliases()[$permission] ?? $permission,
        ]);

        return $this->role
            ? $this->role->permissions->whereIn('name', $permissionsToCheck)->isNotEmpty()
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

    private static function permissionAliases(): array
    {
        return [
            'users.view' => 'empleados.ver',
            'users.create' => 'empleados.crear',
            'users.update' => 'empleados.editar',
            'users.deactivate' => 'usuarios.gestionar',
            'roles.view' => 'roles.ver',
            'roles.assign' => 'usuarios.gestionar',
            'roles.manage' => 'roles.gestionar',
            'sales.view' => 'ventas.ver',
            'sales.create' => 'ventas.crear',
            'sales.update' => 'ventas.editar',
            'clients.view' => 'clientes.ver',
            'clients.create' => 'clientes.crear',
            'clients.update' => 'clientes.editar',
            'credits.view' => 'creditos.ver',
            'inventory.view' => 'inventario.ver',
            'inventory.manage' => 'inventario.gestionar',
            'cash.view' => 'caja.ver',
            'cash.open' => 'caja.crear',
            'cash.close' => 'caja.cerrar',
            'maintenance.view' => 'mantenimiento.ver',
            'maintenance.create' => 'mantenimiento.crear',
            'maintenance.update' => 'mantenimiento.editar',
            'maintenance.delete' => 'mantenimiento.desactivar',
            'reports.view' => 'reportes.ver',
            'web_requests.view' => 'marketing.ver',
            'web_requests.manage' => 'marketing.gestionar',
        ];
    }
}
