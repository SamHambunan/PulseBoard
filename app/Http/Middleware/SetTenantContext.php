<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * resolves and registers the current tenant for the request.
 *
 * this middleware extracts the authenticated user's tenant_id and
 * stores it in the service container as `current.tenant.id`.
 *
 * models using the BelongsToTenant trait rely on this container binding
 * to automatically:
 * - scope queries to the active tenant
 * - assign tenant_id during model creation
 *
 * this middleware should run after authentication middleware.
 */
class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->user()?->tenant_id;

        abort_unless($tenantId, 401, 'No tenant context');

        app()->instance('current.tenant.id', $tenantId);

        return $next($request);
    }
}
