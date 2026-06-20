<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * metricapi feature tests
 *
 * tests the behavior of the metric api endpoints:
 * - snapshot (redis-based real-time data)
 * - history (cached aws timestream data)
 *
 * ensures correctness, tenant isolation, and caching behavior.
 */
class MetricApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * teardown method
     *
     * ensures all external state (redis + cache) is cleaned
     * after each test to prevent cross-test contamination.
     */
    protected function tearDown(): void
    {
        // clear redis snapshot data
        Redis::flushDB();

        // clear laravel cache (history endpoint cache)
        Cache::flush();

        parent::tearDown();
    }

    /**
     * snapshot endpoint returns last known value from redis
     *
     * this test ensures:
     * - redis-backed snapshot is correctly returned
     * - api properly decodes stored json
     * - response includes expected value and delta
     */
    public function test_snapshot_returns_last_known_value_from_redis()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        // seed redis with a fake metric snapshot
        $snapshotKey = "snapshot:{$tenant->id}:cpu_usage";

        $payload = json_encode([
            'tenant_id' => $tenant->id,
            'metric' => 'cpu_usage',
            'value' => 88.5,
            'delta' => 2.5,
            'ts' => 1600000000000,
        ]);

        Redis::set($snapshotKey, $payload);

        // act: call snapshot endpoint
        $response = $this
            ->actingAs($user)
            ->getJson('/api/v1/snapshots/cpu_usage');

        // assert: redis data is returned correctly
        $response->assertStatus(200)
            ->assertJson([
                'value' => 88.5,
                'delta' => 2.5,
            ]);
    }

    /**
     * snapshot endpoint returns null payload when redis is empty
     *
     * ensures graceful fallback behavior when no snapshot exists
     * instead of throwing errors or 500 responses.
     */
    public function test_snapshot_returns_null_if_redis_is_empty()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        // act: request metric that does not exist in redis
        $response = $this
            ->actingAs($user)
            ->getJson('/api/v1/snapshots/offline_server');

        // assert: api returns safe null structure
        $response->assertStatus(200)
            ->assertJson([
                'value' => null,
                'delta' => null,
                'ts' => null,
            ]);
    }

    /**
     * history endpoint returns cached timestream data
     *
     * this test ensures:
     * - api returns properly structured time-series data
     * - laravel cache is used to avoid repeated aws calls
     * - cache key includes tenant isolation
     */
    public function test_history_endpoint_returns_mock_data_and_caches_it()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        // spy on cache to ensure caching behavior is triggered
        $spy = Cache::spy();

        // act: call history endpoint
        $response = $this
            ->actingAs($user)
            ->getJson('/api/v1/metrics/cpu_usage/history?range=1h');

        // assert: response structure is valid time-series format
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['ts', 'value'],
                ],
                'range',
            ]);

        // assert: cache remember was called with correct key
        $spy->shouldHaveReceived('remember')
            ->with(
                "history:{$tenant->id}:cpu_usage:1h",
                30,
                \Mockery::any()
            );
    }
}
