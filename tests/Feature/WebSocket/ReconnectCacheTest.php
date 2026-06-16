<?php

namespace Tests\Feature\WebSocket;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ReconnectCacheTest extends TestCase
{
    use RefreshDatabase;

    /**
     * clean up any cached authorization data after each test.
     *
     * this prevents cache entries from leaking between tests
     * and ensures each test runs in a predictable state.
     */
    protected function tearDown(): void
    {
        // clear all cached entries created during the test
        Cache::flush();

        parent::tearDown();
    }

    /**
     * verify that channel authorization responses are cached
     * to reduce repeated authorization processing during
     * websocket reconnect storms.
     *
     * expected behavior:
     * - the first request generates and stores an auth response
     * - the auth response is written to the cache
     * - subsequent requests use the cache layer
     * - repeated reconnects do not unnecessarily execute
     *   channel authorization logic
     *
     * @return void
     */
    public function test_auth_response_is_cached_to_prevent_thundering_herd()
    {
        // create a tenant and a user belonging to that tenant
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        // define the presence channel to authenticate against
        $channel = "presence-dashboard.{$tenant->id}";

        // send the initial authentication request
        // this should execute the authorization logic
        // and populate the cache
        $this->actingAs($user)->postJson('/api/v1/broadcasting/auth', [
            'channel_name' => $channel,
            'socket_id' => '12345.67890',
        ])->assertStatus(200);

        // build the cache key used by the controller
        $cacheKey = "broadcast_auth:{$user->id}:".md5($channel);

        // verify that the authorization result was stored
        $this->assertTrue(Cache::has($cacheKey));

        // create a spy to monitor cache interactions
        $spy = Cache::spy();

        // send a second authentication request for the same channel
        $this->actingAs($user)->postJson('/api/v1/broadcasting/auth', [
            'channel_name' => $channel,
            'socket_id' => '12345.67890',
        ])->assertStatus(200);

        // verify that the cache layer was consulted using
        // the expected cache key and ttl value
        $spy->shouldHaveReceived('remember')
            ->with($cacheKey, 30, \Mockery::any());
    }
}
