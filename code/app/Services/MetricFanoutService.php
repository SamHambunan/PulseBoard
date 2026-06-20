<?php

namespace App\Services;

use App\Jobs\WriteMetricToTimestream;
use Illuminate\Support\Facades\Redis;

/**
 * responsible for distributing (fanout) metric events to multiple downstream systems.
 *
 * this service acts as a real-time processing layer that:
 * - calculates metric deltas
 * - broadcasts updates via redis pub/sub
 * - maintains a short-lived snapshot cache
 * - queues long-term persistence jobs (time-series storage)
 *
 * typical usage:
 * called after a metric event is ingested (e.g., from kafka consumer or controller pipeline)
 *
 * data flow:
 * incoming payload → delta computation → redis broadcast → snapshot update → async db write
 */
class MetricFanoutService
{
    /**
     * publish a metric event to downstream systems.
     *
     * this method performs multiple operations:
     *
     * 1. computes delta from previous metric snapshot
     * 2. publishes real-time update via redis pub/sub
     * 3. updates cached snapshot (ttl: 5 minutes)
     * 4. dispatches async job for long-term storage
     *
     * expected payload structure:
     *
     * [
     *   'tenant_id' => string|int,
     *   'metric'    => string,
     *   'value'     => float|int|string,
     *   'ts'        => int (unix timestamp or milliseconds depending on system design)
     * ]
     */
    public function publish(array $payload): void
    {
        $tenantId = $payload['tenant_id'];
        $metric = $payload['metric'];
        $value = (float) $payload['value'];

        /**
         * redis key used to store the last known snapshot
         * for a given tenant + metric pair.
         */
        $snapshotKey = "snapshot:{$tenantId}:{$metric}";

        /**
         * retrieve previous snapshot from redis.
         *
         * used to calculate delta between current and last value.
         */
        $prevRaw = Redis::get($snapshotKey);
        $prev = $prevRaw ? json_decode($prevRaw, true) : null;

        /**
         * compute delta between current value and previous value.
         *
         * if no previous snapshot exists, delta is null.
         */
        $delta = $prev ? round($value - $prev['value'], 4) : null;

        /**
         * build normalized message payload.
         *
         * this message is used for:
         * - redis pub/sub broadcasting
         * - snapshot caching
         */
        $message = json_encode([
            'tenant_id' => $tenantId,
            'metric' => $metric,
            'value' => $value,
            'delta' => $delta,
            'ts' => $payload['ts'],
        ]);

        /**
         * publish real-time metric update.
         *
         * channel format:
         * metrics:{tenant_id}
         */
        Redis::publish("metrics:{$tenantId}", $message);

        /**
         * update snapshot cache.
         *
         * stores the latest metric value per tenant/metric pair.
         * ttl: 300 seconds (5 minutes)
         */
        Redis::setex($snapshotKey, 300, $message);

        /**
         * dispatch async job for time-series persistence.
         *
         * this decouples storage from real-time processing.
         */
        dispatch(new WriteMetricToTimestream($payload, $delta))
            ->onQueue('default');
    }
}
