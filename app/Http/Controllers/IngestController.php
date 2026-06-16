<?php

namespace App\Http\Controllers;

use App\Http\Requests\IngestRequest;
use App\Services\KafkaProducerService;
use App\Services\RateLimiterService;
use Illuminate\Http\JsonResponse;

/**
 * class ingestcontroller
 *
 * handles incoming metric ingestion requests.
 *
 * this controller serves as the entry point for metric data submitted
 * through the ingestion api. it performs request validation, rate limiting,
 * payload enrichment, and forwards accepted data to kafka for asynchronous
 * processing.
 *
 * request flow:
 * 1. retrieve the authenticated api key from middleware.
 * 2. verify the request is within the allowed rate limit.
 * 3. enrich the payload with tenant and metadata information.
 * 4. publish the payload to kafka.
 * 5. return an accepted response to the client.
 */
class IngestController extends Controller
{
    /**
     * create a new controller instance.
     */
    public function __construct(
        private RateLimiterService $limiter,
        private KafkaProducerService $kafka
    ) {}

    /**
     * store a metric ingestion request.
     *
     * accepts validated metric data, applies rate limiting,
     * enriches the payload with tenant information, and
     * publishes the event to kafka for downstream processing.
     *
     * request payload:
     * - metric (required)
     * - value (required)
     * - ts (optional)
     * - source (optional)
     *
     * processing steps:
     *
     * 1. retrieve the authenticated api key attached by middleware.
     *
     * 2. enforce per-api-key rate limiting.
     *    requests exceeding the configured threshold are rejected.
     *
     * 3. construct the final payload by combining:
     *    - validated request data
     *    - tenant identifier
     *    - generated timestamp (if not supplied)
     *    - source identifier (if not supplied)
     *
     * 4. publish the payload to the kafka topic.
     *
     * 5. return a 202 accepted response indicating the event
     *    has been queued for processing.
     *
     * successful response:
     *
     * {
     *     "status": "accepted"
     * }
     *
     * rate limit response:
     *
     * {
     *     "error": "rate limit exceeded. maximum 1000 requests per minute."
     * }
     */
    public function store(IngestRequest $request): JsonResponse
    {
        /**
         * get the authenticated api key provided by middleware.
         *
         * the middleware validates the incoming api key and
         * stores the corresponding model in the request attributes.
         */
        dump('Reached the controller');
        $apiKey = $request->attributes->get('api_key');

        /**
         * check whether the api key has exceeded its
         * allowed request rate.
         */
        if (! $this->limiter->allow($apiKey->key_hash)) {
            return response()->json([
                'error' => 'Rate limit exceeded. Maximum 1000 requests per minute.',
            ], 429);
        }

        /**
         * build the final event payload.
         *
         * additional metadata is automatically injected:
         * - tenant_id
         * - timestamp in milliseconds
         * - source identifier
         */
        $payload = array_merge($request->validated(), [
            'tenant_id' => $apiKey->tenant_id,

            // use the supplied timestamp or generate one in milliseconds
            'ts' => $request->input(
                'ts',
                round(microtime(true) * 1000)
            ),

            // use the supplied source or default to the api key name
            'source' => $request->input(
                'source',
                $apiKey->name
            ),
        ]);

        /**
         * publish the event to kafka.
         *
         * all events for the same tenant are routed to the
         * same partition to preserve ordering guarantees.
         */
        $this->kafka->produce(
            topicName: 'metrics.events',
            payload: $payload,
            partitionKey: $apiKey->tenant_id
        );

        /**
         * return accepted.
         *
         * a 202 status indicates the request was successfully
         * received and queued, but processing will occur asynchronously.
         */
        return response()->json([
            'status' => 'accepted',
        ], 202);
    }
}
