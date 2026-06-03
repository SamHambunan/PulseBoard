<?php

namespace Tests\Feature\Ingest;

use App\Models\ApiKey;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * class ingestvalidationtest
 *
 * feature tests for request validation on the metric ingestion endpoint.
 *
 * these tests verify that invalid request payloads are rejected before
 * they reach the application processing layer.
 *
 * validation coverage:
 * - required fields
 * - metric naming rules
 * - numeric value validation
 *
 * expected behavior:
 * - invalid requests return http 422 unprocessable entity.
 * - validation errors identify the specific fields that failed validation.
 */
class IngestValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * valid api key used for authenticated test requests.
     *
     * @var string
     */
    private string $validKey = 'secret_key_123';

    /**
     * prepare test dependencies before each test.
     *
     * creates:
     * - a tenant record
     * - a valid api key associated with the tenant
     *
     * the api key is used to bypass authentication concerns so that
     * each test can focus exclusively on request validation behavior.
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::factory()->create();

        ApiKey::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Test Key',
            'key_hash'  => hash('sha256', $this->validKey),
        ]);
    }

    /**
     * verify that requests missing required fields
     * are rejected with validation errors.
     *
     * scenario:
     * - submit an empty request payload.
     *
     * expected result:
     * - http status 422.
     * - validation errors for:
     *   - metric
     *   - value
     *
     */
    public function test_missing_metric_and_value_returns_422()
    {
        $response = $this->withToken($this->validKey)
            ->postJson('/api/v1/ingest', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'metric',
                'value',
            ]);
    }

    /**
     * verify that metric names must match
     * the allowed naming convention.
     *
     * allowed characters:
     * - letters
     * - numbers
     * - periods (.)
     * - underscores (_)
     * - hyphens (-)
     *
     * scenario:
     * - submit a metric name containing spaces
     *   and punctuation characters.
     *
     * expected result:
     * - http status 422.
     * - validation error for the metric field.
     *
     */
    public function test_invalid_metric_name_returns_422()
    {
        $response = $this->withToken($this->validKey)
            ->postJson('/api/v1/ingest', [
                'metric' => 'CPU Usage!!!',
                'value'  => 55.5,
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'metric',
            ]);
    }

    /**
     * verify that the value field must contain
     * a numeric value.
     *
     * scenario:
     * - submit a string instead of a number.
     *
     * expected result:
     * - http status 422.
     * - validation error for the value field.
     *
     */
    public function test_value_must_be_numeric()
    {
        $response = $this->withToken($this->validKey)
            ->postJson('/api/v1/ingest', [
                'metric' => 'cpu_usage',
                'value'  => 'fifty-five',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'value',
            ]);
    }
}
