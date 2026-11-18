<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (Auth::hasUser()) {
                $user = Auth::user();
                if ($user && $user->tenant_id) {
                    $builder->where($builder->getQuery()->from . '.tenant_id', $user->tenant_id);
                }
            }
        });

        static::creating(function ($model) {
            if (empty($model->tenant_id) && Auth::hasUser()) {
                $user = Auth::user();
                if ($user && $user->tenant_id) {
                    $model->tenant_id = $user->tenant_id;
                }
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
