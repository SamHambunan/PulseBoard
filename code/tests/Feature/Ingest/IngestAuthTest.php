<?php

namespace Tests\Feature\Ingest;

use App\Models\ApiKey;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * class ingestauthtest
 *
 * feature tests for api key authentication during metric ingestion.
 *
 * these tests verify that the ingestion endpoint correctly handles
 * authentication scenarios including:
 *
 * - missing api keys
 * - invalid api keys
 * - revoked api keys
 *
 * expected behavior:
 * - unauthenticated requests must be rejected with a 401 response.
 * - only valid and active api keys should be allowed to access
 *   the ingestion endpoint.
 */
class IngestAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * verify that requests without an api key
     * are rejected with a 401 unauthorized response.
     *
     * scenario:
     * - client submits a valid payload.
     * - no bearer token is provided.
     *
     * expected result:
     * - http status 401.
     * - response contains a "missing api key" message.
     *
     * @return void
     */
    public function test_missing_api_key_returns_401()
    {
        $response = $this->postJson('/api/v1/ingest', [
            'metric' => 'cpu_usage',
            'value' => 55.5,
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'missing api key',
            ]);
    }

    /**
     * verify that requests using an invalid api key
     * are rejected with a 401 unauthorized response.
     *
     * scenario:
     * - client provides a bearer token.
     * - token does not match any stored api key.
     *
     * expected result:
     * - http status 401.
     * - response contains an "invalid api key" message.
     *
     * @return void
     */
    public function test_invalid_api_key_returns_401()
    {
        $response = $this->withToken('fake_invalid_key')
            ->postJson('/api/v1/ingest', [
                'metric' => 'cpu_usage',
                'value' => 55.5,
            ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'invalid api key',
            ]);
    }

    /**
     * verify that revoked api keys cannot be used
     * to access the ingestion endpoint.
     *
     * scenario:
     * - create a tenant.
     * - create an api key associated with the tenant.
     * - mark the api key as revoked.
     * - attempt to submit a metric using the revoked key.
     *
     * expected result:
     * - http status 401.
     * - request is denied even though the key exists.
     *
     * @return void
     */
    public function test_revoked_api_key_returns_401()
    {
        /**
         * create a test tenant.
         */
        $tenant = Tenant::factory()->create();

        /**
         * create a revoked api key.
         */
        $apiKey = ApiKey::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Key',
            'key_hash' => hash('sha256', 'secret_key_123'),
            'revoked_at' => now(),
        ]);

        /**
         * attempt authentication using the revoked key.
         */
        $response = $this->withToken('secret_key_123')
            ->postJson('/api/v1/ingest', [
                'metric' => 'cpu_usage',
                'value' => 55.5,
            ]);

        $response->assertStatus(401);
    }
}
