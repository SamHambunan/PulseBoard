<?php

namespace App\Http\Middleware;

use App\Jobs\UpdateApiKeyLastUsed;
use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * middleware for authenticating requests using api keys.
 *
 * this middleware validates bearer tokens, resolves the associated api key,
 * enforces tenant context, and attaches api key metadata to the request.
 *
 * it also caches api key lookups in redis and updates last_used_at asynchronously.
 */
class AuthenticateApiKey
{
    /**
     * handle an incoming request and authenticate using api key.
     *
     * steps:
     * - extract bearer token from request
     * - validate and resolve api key from cache or database
     * - enforce tenant context for multi-tenancy
     * - attach api key to request attributes
     * - dispatch background job to update last used timestamp
     */
    public function handle(Request $request, Closure $next): Response
    {
        // get raw api key from authorization header
        $rawKey = $request->bearerToken();

        abort_if(! $rawKey, 401, 'missing api key');

        $cacheKey = 'api_key:'.hash('sha256', $rawKey);

        // check cache first, fallback to database if missing
        $apiKey = Cache::remember($cacheKey, 30, function () use ($rawKey) {
            return ApiKey::where('key_hash', hash('sha256', $rawKey))
                ->whereNull('revoked_at')
                ->first();
        });

        abort_if(! $apiKey, 401, 'invalid api key');

        // set tenant context for request lifecycle
        app()->instance('current.tenant.id', $apiKey->tenant_id);

        // attach api key to request for downstream usage
        $request->attributes->set('api_key', $apiKey);

        // update last used timestamp asynchronously
        dispatch(new UpdateApiKeyLastUsed($apiKey->id))->afterResponse();

        return $next($request);
    }
}
