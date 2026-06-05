<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class MetricController extends Controller
{
    /**
     * Cold-load: Get the last known value from Redis (< 50ms)
     */
    public function snapshot(Request $request, string $metric): JsonResponse
    {
        // Get the tenant ID that was securely set by the SetTenantContext middleware
        $tenantId = app('current.tenant.id');

        $raw = Redis::get("snapshot:{$tenantId}:{$metric}");

        if (! $raw) {
            return response()->json([
                'value' => null,
                'delta' => null,
                'ts'    => null
            ]);
        }

        // Return the exact JSON we stored in the Fan-Out service
        return response()->json(json_decode($raw, true));
    }
}
