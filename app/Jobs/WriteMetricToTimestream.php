<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * class writemetrictotimestream
 *
 * queued job responsible for persisting metric data into a time-series database.
 *
 * this job is part of the asynchronous processing pipeline and is dispatched
 * after a metric has been processed and faned out by the application.
 *
 * current behavior (phase 4 implementation):
 * - simulates writing to aws timestream
 * - logs metric data for debugging and verification
 *
 * future behavior (phase 5):
 * - will persist metric data into aws timestream or another time-series database
 *
 * this job runs asynchronously on a queue to avoid blocking the main request
 * or real-time processing pipeline.
 */
class WriteMetricToTimestream implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * create a new job instance.
     *
     * @param array $payload
     *   raw metric payload containing:
     *   - tenant_id
     *   - metric
     *   - value
     *   - ts
     *
     * @param float|null $delta
     *   difference between current and previous metric value
     */
    public function __construct(
        private readonly array $payload,
        private readonly ?float $delta
    ) {}

    /**
     * execute the job.
     *
     * this method is executed by the queue worker.
     *
     * current implementation:
     * - logs the metric to laravel log system for debugging
     *
     * future implementation:
     * - write metric to aws timestream
     *
     */
    public function handle(): void
    {
        /**
         * simulate persistence to a time-series database.
         *
         * this allows verification that:
         * - the job was dispatched correctly
         * - payload and delta values are correctly passed
         */
        Log::info('simulating write to aws timestream', [
            'metric' => $this->payload['metric'],
            'value'  => $this->payload['value'],
            'delta'  => $this->delta,
        ]);
    }
}
