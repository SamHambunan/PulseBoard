<?php

namespace App\Console\Commands;

use App\Services\MetricFanoutService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\Facades\Kafka;

/**
 * class consumemetrics
 *
 * console command responsible for consuming metric events from kafka
 * and forwarding them into the internal fan-out processing pipeline.
 *
 * this command acts as the bridge between:
 * - kafka ingestion layer (event stream)
 * - application processing layer (metric fan-out service)
 *
 * responsibilities:
 * - consume messages from kafka topic
 * - validate incoming payloads
 * - forward valid metrics to MetricFanoutService
 * - handle and log errors safely
 *
 * design notes:
 * - manual offset commit control is enabled
 * - invalid messages are skipped (not re-queued)
 * - fatal errors are re-thrown to allow supervisor restart
 */
class ConsumeMetrics extends Command
{
    /**
     * artisan command signature.
     *
     * usage:
     * php artisan kafka:consume
     */
    protected $signature = 'kafka:consume';

    /**
     * command description shown in artisan list.
     */
    protected $description = 'consume metric events and fan-out';

    /**
     * handle the kafka consumer process.
     *
     * this method initializes the kafka consumer, listens for incoming
     * messages, validates them, and forwards them to the fan-out service.
     */
    public function handle(MetricFanoutService $fanout): int
    {
        $this->info('starting kafka consumer...');

        /**
         * create kafka consumer instance.
         *
         * topic:
         * - metrics.events
         *
         * consumer group:
         * - pulseboard-fanout
         *
         * note:
         * manual offset commit is disabled to allow controlled processing.
         */
        $consumer = Kafka::consumer(['metrics.events'])
            ->withBrokers(config('services.kafka.brokers', config('KAFKA_BROKERS')))
            ->withConsumerGroupId('pulseboard-fanout')
            ->withAutoCommit(false)
            ->withOptions([
                'auto.offset.reset' => 'earliest',
            ])

            /**
             * message handler callback.
             *
             * processes each kafka message individually.
             */
            ->withHandler(function (ConsumerMessage $message, MessageConsumer $consumer) use ($fanout) {

                try {
                    /**
                     * extract payload from kafka message.
                     */
                    $payload = $message->getBody();

                    /**
                     * validate payload before processing.
                     */
                    if (! $this->validate($payload)) {
                        Log::warning('kafka.consumer.invalid_payload', [
                            'payload' => $payload,
                        ]);
                        $consumer->commit($message);

                        return; // skip invalid message
                    }

                    /**
                     * forward valid payload to fan-out service.
                     */
                    $fanout->publish($payload);
                    $consumer->commit($message);

                } catch (\Throwable $e) {

                    /**
                     * log unexpected errors and rethrow.
                     *
                     * rethrowing ensures process failure is visible to
                     * supervisor/systemd so the consumer can restart.
                     */
                    Log::error('kafka.consumer.error', [
                        'error' => $e->getMessage(),
                        'payload' => $message->getBody(),
                    ]);

                    throw $e;
                }
            })
            ->build();

        /**
         * start consuming messages (blocking call).
         */
        $consumer->consume();

        return self::SUCCESS;
    }

    /**
     * validate kafka payload structure.
     *
     * ensures the message contains all required fields
     * and that value is numeric before processing.
     */
    private function validate(mixed $payload): bool
    {
        if (! is_array($payload)) {
            return false;
        }

        return isset(
            $payload['tenant_id'],
            $payload['metric'],
            $payload['value'],
            $payload['ts']
        )
            && is_numeric($payload['value']);
    }
}
