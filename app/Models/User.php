<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'role_id',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'cashier_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role && strtolower($this->role->name) === 'super_admin';
    }

    public function isAdmin(): bool
    {
        if (!$this->role) {
            return false;
        }
        $roleName = strtolower($this->role->name);
        return in_array($roleName, ['admin', 'super_admin']);
    }

    public function isManager(): bool
    {
        return $this->role && strtolower($this->role->name) === 'manager';
    }

    public function isCashier(): bool
    {
        return $this->role && (strtolower($this->role->name) === 'cashier' || strtolower($this->role->name) === 'sales assistant');
    }

    public function hasRole(string $roleName): bool
    {
        return $this->role && strtolower($this->role->name) === strtolower($roleName);
    }

    /**
     * Check whether the user has a given shop permission based on their role and tenant configuration.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return true;
        }

        if (!$this->tenant_id || !$this->role) {
            return false;
        }

        $roleName = strtolower($this->role->name);
        $rolePerm = TenantRolePermission::where('tenant_id', $this->tenant_id)
            ->where('role_name', $roleName)
            ->first();

        $allowed = $rolePerm
            ? (array) ($rolePerm->permissions ?? [])
            : TenantRolePermission::defaultPermissionsForRole($roleName);

        return in_array($permission, $allowed, true);
    }
}
