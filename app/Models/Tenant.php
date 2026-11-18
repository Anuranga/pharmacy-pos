<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'phone',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function rolePermissions()
    {
        return $this->hasMany(TenantRolePermission::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class)->withoutGlobalScopes();
    }

    public function sales()
    {
        return $this->hasMany(Sale::class)->withoutGlobalScopes();
    }
}
