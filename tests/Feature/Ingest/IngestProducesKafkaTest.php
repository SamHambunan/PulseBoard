<?php

namespace Tests\Feature\Ingest;

use App\Models\ApiKey;
use App\Models\Tenant;
use App\Services\KafkaProducerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use Mockery\MockInterface;

/**
 * class ingestproduceskafkatest
 *
 * feature tests for kafka message production during metric ingestion.
 *
 * these tests verify that valid ingestion requests:
 * - pass authentication and validation.
 * - invoke the kafka producer service.
 * - publish data to the expected kafka topic.
 * - return the correct api response.
 *
 * kafka interactions are mocked to prevent test messages from
 * being sent to a real kafka cluster.
 *
 * expected behavior:
 * - a valid request should trigger a single kafka publish operation.
 * - the correct topic, payload, and partition key should be used.
 * - the endpoint should return http 202 accepted.
 */
class IngestProducesKafkaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * verify that a valid request is published to kafka
     * and returns a successful accepted response.
     *
     * scenario:
     * - create a tenant and api key.
     * - authenticate using the valid api key.
     * - submit a valid metric payload.
     * - mock the kafka producer service.
     *
     * expected result:
     * - kafka producer is called exactly once.
     * - topic name is "metrics.events".
     * - payload contains the submitted metric.
     * - tenant id is used as the partition key.
     * - response status is 202 accepted.
     *
     * @return void
     */

        public function test_valid_request_produces_to_kafka_and_returns_202()
        {
            /**
             * create a test tenant.
             */
            $tenant = Tenant::factory()->create();

            /**
             * create a valid api key.
             *
             * rawkey:
             * value supplied by the client.
             *
             * key_hash:
             * stored representation used for lookup.
             */
            $rawKey = 'secret_key_1234';

            ApiKey::create([
                'tenant_id' => $tenant->id,
                'name'      => 'Test Key',
                'key_hash'  => hash('sha256', $rawKey),
            ]);

            /**
             * mock the kafka producer service.
             *
             * this prevents the test from publishing
             * messages to an actual kafka broker.
             *
             * assertions:
             * - produce() is called exactly once.
             * - correct topic name is used.
             * - payload contains the expected metric.
             * - tenant id is used as the partition key.
             */
            $this->mock(
                KafkaProducerService::class,
                function (MockInterface $mock) use ($tenant) {
                        $mock->shouldReceive('produce')
                        ->once()
                        ->withArgs(function (
                            $topicName,
                            $payload,
                            $partitionKey
                        ) use ($tenant) {
                            return $topicName === 'metrics.events'
                                && $payload['metric'] === 'cpu_usage'
                                && $partitionKey === $tenant->id;
                        });
                }
            );

            /**
             * submit a valid ingestion request.
             */
            $response = $this->withToken($rawKey)
                ->postJson('/api/v1/ingest', [
                    'metric' => 'cpu_usage',
                    'value'  => 55.5,
                ]);

            /**
             * verify that the request was accepted.
             *
             * a 202 response indicates that the event
             * has been successfully queued for
             * asynchronous processing.
             */
            $response
                ->assertStatus(202)
                ->assertJson([
                    'status' => 'accepted',
                ]);
        }
}
