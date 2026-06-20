<?php

namespace App\Console\Commands;

use App\Events\MetricUpdated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

/**
 * laravel artisan command that bridges real-time metrics from redis pub/sub
 * to websocket clients via the MetricUpdated event.
 *
 * responsibilities:
 * - subscribe to all redis channels matching 'metrics:*'
 * - decode incoming metric messages
 * - dispatch metric updates as websocket events to frontends
 * - log broadcasts for observability
 *
 * use case:
 * this command enables a low-latency data pipeline from backend ingestion
 * (or fan-out) to live dashboards without directly exposing redis to clients.
 */
class StartRedisBridge extends Command
{
    /**
     * the console command signature.
     *
     * usage: php artisan reverb:redis-bridge
     *
     * @var string
     */
    protected $signature = 'reverb:redis-bridge';

    /**
     * command description.
     *
     * @var string
     */
    protected $description = 'Listen to Redis Pub/Sub and broadcast to Reverb WebSockets';

    /**
     * execute the command.
     *
     * - subscribes to all channels starting with metrics: using psubscribe
     * - decodes messages (json) from redis
     * - dispatches them as MetricUpdated events to broadcast to websockets
     * - logs each broadcast
     */
    public function handle(): void
    {
        $this->info('Starting Redis -> Reverb Bridge...');

        // psubscribe allows pattern matching: listen to ALL channels that match 'metrics:*'
        Redis::psubscribe(['metrics:*'], function (string $message, string $channel) {

            // decode the message payload from redis
            $data = json_decode($message, true);

            // dispatch websocket event to broadcast to subscribed frontend clients
            MetricUpdated::dispatch(
                $data['tenant_id'],
                $data['metric'],
                (float) $data['value'],
                isset($data['delta']) ? (float) $data['delta'] : null,
                (int) $data['ts']
            );

            // log the broadcast for visibility
            $this->info("Broadcasted {$data['metric']} for {$data['tenant_id']}");
        });
    }
}
