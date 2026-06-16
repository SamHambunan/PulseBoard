<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * class metricupdated
 *
 * event triggered when a metric is updated in the system.
 *
 * this event implements shouldbroadcast, allowing it to be broadcast
 * over laravel's broadcasting system (pusher, redis, websocket, etc.)
 *
 * primary responsibilities:
 * - notify frontend dashboards in real-time of metric updates
 * - send minimal payload (metric, value, delta, timestamp)
 * - isolate broadcasts per tenant via presence channels
 */
class MetricUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * create a new metric updated event instance
     *
     * @param  string  $tenantId  tenant identifier
     * @param  string  $metric  metric name (e.g., cpu_usage)
     * @param  float  $value  current metric value
     * @param  float|null  $delta  difference from previous metric value
     * @param  int  $ts  timestamp in milliseconds
     */
    public function __construct(
        public readonly string $tenantId,
        public readonly string $metric,
        public readonly float $value,
        public readonly ?float $delta,
        public readonly int $ts
    ) {}

    /**
     * get the channels the event should broadcast on.
     *
     * uses a presence channel specific to each tenant,
     * allowing only authorized clients to subscribe.
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("dashboard.{$this->tenantId}"),
        ];
    }

    /**
     * define the event name when broadcasting.
     */
    public function broadcastAs(): string
    {
        return 'metric.updated';
    }

    /**
     * define the payload sent to subscribers.
     *
     * only sends the fields needed for real-time dashboard updates.
     */
    public function broadcastWith(): array
    {
        return [
            'metric' => $this->metric,
            'value' => $this->value,
            'delta' => $this->delta,
            'ts' => $this->ts,
        ];
    }
}
