<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Automatically scope every query to the current tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->bound('current.tenant.id')) {
                $builder->where('tenant_id', app('current.tenant.id'));
            }
        });

        // Automatically stamp tenant_id on create
        static::creating(function (Model $model) {
            if (! $model->tenant_id && app()->bound('current.tenant.id')) {
                $model->tenant_id = app('current.tenant.id');
            }
        });
    }
}
