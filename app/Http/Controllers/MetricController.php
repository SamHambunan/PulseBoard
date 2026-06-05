<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

/**
 * metriccontroller
 *
 * handles retrieval of real-time and historical metric data
 * in a multi-tenant environment.
 *
 * data sources:
 * - redis (for latest snapshot / real-time data)
 * - aws timestream (for historical data via service layer)
 */
class MetricController extends Controller
{
    /**
     * snapshot endpoint
     *
     * returns the most recent known value of a metric from redis.
     *
     * this is a cold-load optimized endpoint designed for fast reads
     * (typically < 50ms) used by dashboards and realtime ui.
     *
     * redis acts as a fast cache populated by the fan-out ingestion service.
     *
     * response format:
     * {
     *   value: number|null,
     *   delta: number|null,
     *   ts: string|null
     * }
     *
     * @param request $request http request instance
     * @param string $metric metric name being requested
     * @return jsonresponse
     */
    public function snapshot(Request $request, string $metric): JsonResponse
    {
        // tenant id is injected securely by settenantcontext middleware
        $tenantId = app('current.tenant.id');

        // redis key structure ensures strict tenant + metric isolation
        $raw = Redis::get("snapshot:{$tenantId}:{$metric}");

        // if no cached snapshot exists, return a consistent empty payload
        if (! $raw) {
            return response()->json([
                'value' => null,
                'delta' => null,
                'ts'    => null
            ]);
        }

        // redis stores json string; decode and return as api response
        return response()->json(json_decode($raw, true));
    }

    /**
     * history endpoint
     *
     * returns historical metric data for a given time range.
     *
     * data is retrieved from aws timestream via timestreamservice.
     * results are cached for 30 seconds to reduce repeated aws queries.
     *
     * supported ranges:
     * - 1h (default)
     * - 6h
     * - 24h
     * - 7d
     * - 30d
     *
     * response format:
     * {
     *   data: array<{timestamp: string, value: number}>,
     *   range: string
     * }
     *
     * @param request $request http request instance
     * @param string $metric metric name being requested
     * @return jsonresponse
     */
    public function history(Request $request, string $metric): JsonResponse
    {
        // time range selector (defaults to 1 hour if not provided)
        $range = $request->query('range', '1h');

        // tenant isolation from middleware context
        $tenantId = app('current.tenant.id');

        // cache key includes tenant + metric + time range
        $cacheKey = "history:{$tenantId}:{$metric}:{$range}";

        // cache layer prevents repeated aws timestream queries
        // ttl: 30 seconds
        $data = \Illuminate\Support\Facades\Cache::remember(
            $cacheKey,
            30,
            fn() => app(\App\Services\TimestreamService::class)
                ->query($tenantId, $metric, $range)
        );

        // return historical dataset with applied range context
        return response()->json([
            'data'  => $data,
            'range' => $range
        ]);
    }
}
