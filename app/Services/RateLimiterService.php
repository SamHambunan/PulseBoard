<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

/**
 * service for handling api rate limiting using redis.
 *
 * this service enforces a fixed window rate limit per api key
 * using an atomic redis lua script to prevent race conditions.
 *
 * limits:
 * - 1000 requests per minute per api key
 */
class RateLimiterService
{
    /**
     * maximum number of allowed requests per time window.
     */
    private const LIMIT = 1000;

    /**
     * time window in seconds for rate limiting.
     */
    private const WINDOW = 60;

    /**
     * determine whether an api key is allowed to proceed.
     *
     * uses redis lua scripting to atomically:
     * - increment request counter
     * - set expiration on first request
     * - return current count
     *
     */
    public function allow(string $apiKeyHash): bool
    {
        $key = "rate_limit:{$apiKeyHash}";

        // lua script ensures atomic increment + expiry handling
        $script = <<<'LUA'
        local key    = KEYS[1]
        local limit  = tonumber(ARGV[1])
        local window = tonumber(ARGV[2])

        local count  = redis.call('INCR', key)

        if count == 1 then
            redis.call('EXPIRE', key, window)
        end

        return count
        LUA;

        // execute atomic rate limit check in redis
        $count = Redis::eval($script, 1, $key, self::LIMIT, self::WINDOW);

        return $count <= self::LIMIT;
    }
}
