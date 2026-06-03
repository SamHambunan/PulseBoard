<?php

namespace Tests\Feature\Services;

use App\Jobs\WriteMetricToTimestream;
use App\Services\MetricFanoutService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 *
 *
 * this test suite verifies the core real-time processing pipeline:
 *
 * - redis snapshot storage
 * - delta computation between metric updates
 * - queue dispatching for long-term persistence
 *
 * the service is responsible for:
 * - computing metric deltas
 * - publishing real-time updates via redis pub/sub
 * - maintaining latest metric snapshot per tenant/metric
 * - dispatching async jobs for time-series storage
 */
class MetricFanoutServiceTest extends TestCase
{
    /**
     * clean redis state after each test.
     *
     * ensures test isolation by preventing leftover snapshot data
     * from affecting other test cases.
     */
    protected function tearDown(): void
    {
        Redis::flushDB();
        parent::tearDown();
    }

    /**
     * verify first metric initialization behavior.
     *
     * scenario:
     * - no previous metric exists in redis
     * - first metric is published
     *
     * expected behavior:
     * - snapshot is created in redis
     * - value is stored correctly
     * - delta is null (no previous baseline exists)
     * - timestream job is dispatched
     */
    public function test_first_metric_has_null_delta_and_sets_snapshot()
    {
        Queue::fake();

        $service = new MetricFanoutService();

        $payload = [
            'tenant_id' => 'tenant-123',
            'metric'    => 'cpu_usage',
            'value'     => 50.0,
            'ts'        => 1600000000000,
        ];

        // execute fan-out logic
        $service->publish($payload);

        // verify snapshot exists in redis
        $snapshotRaw = Redis::get('snapshot:tenant-123:cpu_usage');
        $this->assertNotNull($snapshotRaw);

        $snapshot = json_decode($snapshotRaw, true);

        $this->assertEquals(50.0, $snapshot['value']);

        // first metric has no previous value → delta should be null
        $this->assertNull($snapshot['delta']);

        // verify async job was dispatched
        Queue::assertPushed(WriteMetricToTimestream::class);
    }

    /**
     * verify delta calculation between consecutive metrics.
     *
     * scenario:
     * - first metric value is stored (50.0)
     * - second metric value is published (65.5)
     *
     * expected behavior:
     * - snapshot is updated with latest value
     * - delta is correctly calculated (65.5 - 50.0 = 15.5)
     */
    public function test_second_metric_calculates_delta_correctly()
    {
        Queue::fake();

        $service = new MetricFanoutService();

        // first metric (baseline)
        $service->publish([
            'tenant_id' => 'tenant-123',
            'metric'    => 'cpu_usage',
            'value'     => 50.0,
            'ts'        => 1600000000000,
        ]);

        // second metric (delta = +15.5)
        $service->publish([
            'tenant_id' => 'tenant-123',
            'metric'    => 'cpu_usage',
            'value'     => 65.5,
            'ts'        => 1600000005000,
        ]);

        // verify updated snapshot in redis
        $snapshotRaw = Redis::get('snapshot:tenant-123:cpu_usage');
        $snapshot = json_decode($snapshotRaw, true);

        $this->assertEquals(65.5, $snapshot['value']);

        // delta = 65.5 - 50.0
        $this->assertEquals(15.5, $snapshot['delta']);
    }
}
