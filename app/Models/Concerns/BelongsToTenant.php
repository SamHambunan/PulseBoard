<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * automatically scopes model queries to the current tenant
 * and assigns tenant_id during model creation.
 *
 * intended for Eloquent models participating in
 * multi-tenant isolation.
 *
 */

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->bound('current.tenant.id')) {
                $builder->where('tenant_id', app('current.tenant.id'));
            }
        });

        static::creating(function (Model $model) {
            if (! $model->tenant_id && app()->bound('current.tenant.id')) {
                $model->tenant_id = app('current.tenant.id');
            }
        });
    }
}
