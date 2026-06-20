<?php

namespace Tests\Feature\Ingest;

use App\Models\ApiKey;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * class ingestratelimittest
 *
 * feature tests for request rate limiting on the metric ingestion endpoint.
 *
 * these tests verify that clients cannot exceed the configured
 * request threshold for a given api key.
 *
 * rate limiting behavior:
 * - requests are tracked using redis.
 * - limits are enforced per api key.
 * - requests exceeding the configured threshold are rejected.
 *
 * expected behavior:
 * - requests within the limit are accepted.
 * - requests exceeding the limit return http 429
 *   (too many requests).
 */
class IngestRateLimitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * verify that requests are blocked once the
     * configured rate limit has been reached.
     *
     * scenario:
     * - create a tenant and api key.
     * - simulate 1,000 previously accepted requests
     *   by directly updating the redis counter.
     * - submit the 1,001st request.
     *
     * expected result:
     * - http status 429.
     * - response contains a rate limit error message.
     *
     * @return void
     */
    public function test_rate_limit_blocks_after_1000_requests()
    {
        /**
         * create a tenant for the test.
         */
        /** @var Tenant $tenant */
        $tenant = Tenant::factory()->create();

        /**
         * create a valid api key.
         *
         * rawkey:
         * value sent by the client in the authorization header.
         *
         * keyhash:
         * value stored in the database and used for lookup.
         */
        $rawKey = 'secret_key_123';
        $keyHash = hash('sha256', $rawKey);

        ApiKey::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Key',
            'key_hash' => $keyHash,
        ]);

        /**
         * simulate a client that has already
         * consumed its full request quota.
         *
         * redis key format:
         * rate_limit:{api_key_hash}
         */
        Redis::set("rate_limit:{$keyHash}", 1000);

        /**
         * submit the next request.
         *
         * this represents request number 1,001.
         */
        $response = $this->withToken($rawKey)
            ->postJson('/api/v1/ingest', [
                'metric' => 'cpu_usage',
                'value' => 55.5,
            ]);

        /**
         * verify that the request is rejected
         * because the rate limit has been exceeded.
         */
        $response
            ->assertStatus(429)
            ->assertJson([
                'error' => 'Rate limit exceeded. Maximum 1000 requests per minute.',
            ]);
    }
}
