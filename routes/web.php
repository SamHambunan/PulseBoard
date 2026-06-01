<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;

Route::get('/poc', function () {
    // Start the stopwatch
    $start = microtime(true);

    // 1. Test Redis (Write and Read)
    Redis::set('poc_test', 'redis_is_fast');
    $redisResult = Redis::get('poc_test');

    // 2. Test Kafka (Produce a message)
    $conf = new \RdKafka\Conf();
    $conf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));
    $producer = new \RdKafka\Producer($conf);

    $topic = $producer->newTopic('metrics.events');
    $topic->produce(
        partition: RD_KAFKA_PARTITION_UA,
        msgflags: 0,
        payload: json_encode(['message' => 'hello from poc'])
    );
    // Non-blocking flush
    $producer->poll(0);

    // Stop the stopwatch and convert to milliseconds
    $latencyMs = round((microtime(true) - $start) * 1000, 2);

    return response()->json([
        'status' => 'PoC Successful',
        'redis' => $redisResult,
        'kafka' => 'Message sent to metrics.events',
        'latency_ms' => $latencyMs
    ]);
});
