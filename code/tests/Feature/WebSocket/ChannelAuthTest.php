<?php

namespace Tests\Feature\WebSocket;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * verify that a user can successfully authenticate
     * to a presence channel that belongs to their tenant.
     *
     * expected behavior:
     * - the request returns a 200 status code
     * - presence channel authentication succeeds
     * - the returned channel data contains the correct user information
     */
    public function test_user_can_authenticate_to_own_tenant_channel(): void
    {
        // create a tenant and a user assigned to that tenant

        /** @var Tenant $tenant */
        $tenant = Tenant::factory()->create();

        /** @var User $user */
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        // attempt to authenticate against the tenant's presence channel
        $response = $this->actingAs($user)->postJson('/api/v1/broadcasting/auth', [
            'channel_name' => "presence-dashboard.{$tenant->id}",
            'socket_id' => '12345.67890', // required by reverb
        ]);

        // a 200 response indicates the channel authorization succeeded
        $response->assertStatus(200);

        // decode the presence channel payload returned by laravel
        $authData = json_decode($response->json('channel_data'), true);

        // verify the authenticated user's id was included
        $this->assertEquals($user->id, $authData['user_id']);

        // verify the authenticated user's name was included
        $this->assertEquals($user->name, $authData['user_info']['name']);
    }

    /**
     * verify that a user cannot authenticate
     * to a presence channel belonging to a different tenant.
     *
     * expected behavior:
     * - the request returns a 403 forbidden response
     * - channel authorization is denied
     * - tenant isolation rules are enforced
     */
    public function test_user_cannot_authenticate_to_different_tenant_channel(): void
    {
        // create tenant a and a user belonging to tenant a

        /** @var Tenant $tenantA */
        $tenantA = Tenant::factory()->create();

        /** @var User $userA */
        $userA = User::factory()->create([
            'tenant_id' => $tenantA->id,
        ]);

        // create a separate tenant that the user does not belong to

        /** @var Tenant $tenantB */
        $tenantB = Tenant::factory()->create();

        // attempt to authenticate to another tenant's channel
        $response = $this->actingAs($userA)->postJson('/api/v1/broadcasting/auth', [
            'channel_name' => "presence-dashboard.{$tenantB->id}",
            'socket_id' => '12345.67890',
        ]);

        // a 403 response indicates authorization was correctly denied
        $response->assertStatus(403);
    }
}
