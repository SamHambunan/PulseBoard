<?php

namespace App\Jobs;

use App\Services\TimestreamService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * writemetrictotimestream job
 *
 * asynchronously writes metric data into aws timestream.
 *
 * this job is dispatched by the ingestion layer and processed
 * by a queue worker to avoid blocking the request cycle.
 *
 * responsibilities:
 * - persist metric events into timestream
 * - normalize payload values
 * - handle retry logic for transient failures
 *
 * retry strategy:
 * - attempts: 3
 * - backoff: 5 seconds between retries
 */
class WriteMetricToTimestream implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * number of retry attempts before failing the job permanently
     */
    public int $tries = 3;

    /**
     * delay (in seconds) before retrying a failed job
     */
    public int $backoff = 5;

    /**
     * job constructor
     *
     * @param array $payload
     *  expected structure:
     *  [
     *      'tenant_id' => string,
     *      'metric'    => string,
     *      'value'     => int|float|string,
     *      'source'    => string|null,
     *      'ts'        => unix timestamp (int)
     *  ]
     *
     * @param float|null $delta
     *  optional change in value compared to previous metric point
     */
    public function __construct(
        private readonly array $payload,
        private readonly ?float $delta
    ) {}

    /**
     * job handler
     *
     * writes the metric event into aws timestream using the
     * timestreamservice abstraction layer.
     *
     * this method is executed by laravel queue workers.
     *
     * @param timestreamservice $timestream
     * @return void
     */
    public function handle(TimestreamService $timestream): void
    {
        $timestream->write(
            tenantId: $this->payload['tenant_id'],
            metric:   $this->payload['metric'],
            value:    (float) $this->payload['value'],
            delta:    $this->delta,
            source:   $this->payload['source'] ?? 'unknown',
            ts:       (int) $this->payload['ts']
        );
    }
}
