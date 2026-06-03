<?php

namespace App\Services;

/**
 * configuration:
 * - kafka brokers are loaded from the application configuration.
 * - socket timeout is configured to fail quickly if kafka becomes unavailable.
 *
 * behavior:
 * - messages are serialized to json before publishing.
 * - partition keys are used to ensure related messages are routed
 *   to the same kafka partition and preserve ordering.
 * - publishing is non-blocking to avoid delaying api responses.
 */
class KafkaProducerService
{
    /**
     * kafka producer instance.
     *
     */
    private \RdKafka\Producer $producer;

    /**
     * create a new kafka producer instance.
     *
     * loads kafka broker configuration and initializes the producer.
     * a short socket timeout is configured so api requests do not
     * hang when kafka is unavailable.
     */
    public function __construct()
    {
        $conf = new \RdKafka\Conf();

        $conf->set(
            'metadata.broker.list',
            config('services.kafka.brokers', env('KAFKA_BROKERS'))
        );

        // timeout quickly if kafka is down so we do not freeze the api
        $conf->set('socket.timeout.ms', '5000');

        $this->producer = new \RdKafka\Producer($conf);
    }

    /**
     * publish a message to a kafka topic.
     *
     * the payload is encoded as json and sent to the specified topic.
     * the partition key ensures that related records are consistently
     * routed to the same partition, preserving message ordering.
     *
     * parameters:
     * - topicname:
     *   kafka topic that will receive the message.
     *
     * - payload:
     *   associative array containing the message data.
     *   this will be serialized to json before publishing.
     *
     * - partitionkey:
     *   key used by kafka to determine the target partition.
     *   records with the same key are sent to the same partition.
     *
     * examples:
     * - tenant-123
     * - customer-456
     * - organization-789
     *
     */
    public function produce(string $topicName, array $payload, string $partitionKey): void
    {
        $topic = $this->producer->newTopic($topicName);

        $topic->produce(
            partition: -1, // let kafka automatically choose the partition
            msgflags: 0,
            payload: json_encode($payload),
            key: $partitionKey
        );

        /**
         * trigger delivery handling without blocking.
         *
         * poll(0) processes internal producer events immediately and
         * returns control to the application without waiting for
         * message delivery confirmation.
         */
        $this->producer->poll(0);
    }
}
