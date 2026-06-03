<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->user()?->tenant_id;

        abort_unless($tenantId, 401, 'No tenant context');

        // Bind the tenant ID to Laravel's service container
        app()->instance('current.tenant.id', $tenantId);

        return $next($request);
    }
}
