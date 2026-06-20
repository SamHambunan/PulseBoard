<?php

namespace Tests\Feature\Dashboard;

use App\Models\Dashboard;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class DashboardCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_dashboard_and_widgets()
    {
        // create a tenant and an owner user
        $tenant = Tenant::factory()->create();
        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'workspace_role' => 'owner',
        ]);

        // create a new dashboard
        $dashResponse = $this->actingAs($owner)->postJson('/api/v1/dashboards', [
            'name' => 'Server Metrics',
        ]);

        // verify dashboard was created successfully
        $dashResponse->assertStatus(201);
        $dashboardId = $dashResponse->json('id');

        // add a widget to the newly created dashboard
        $widgetResponse = $this->actingAs($owner)->postJson(
            "/api/v1/dashboards/{$dashboardId}/widgets",
            [
                'type' => 'line_chart',
                'position_x' => 0,
                'position_y' => 0,
                'width' => 6,
                'height' => 4,
                'config' => [
                    'metric' => 'cpu_usage',
                ],
            ]
        );

        // verify widget was created successfully
        $widgetResponse->assertStatus(201);

        // confirm dashboard record exists in the database
        $this->assertDatabaseHas('dashboards', [
            'name' => 'Server Metrics',
            'tenant_id' => $tenant->id,
        ]);

        // confirm widget record exists and is linked to the dashboard
        $this->assertDatabaseHas('widgets', [
            'type' => 'line_chart',
            'dashboard_id' => $dashboardId,
        ]);
    }

    public function test_viewer_cannot_update_dashboard()
    {
        // create a tenant with an owner and a viewer
        $tenant = Tenant::factory()->create();

        /** @var User $owner */
        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'workspace_role' => 'owner',
        ]);

        /** @var User viewer */
        $viewer = User::factory()->create([
            'tenant_id' => $tenant->id,
            'workspace_role' => 'viewer',
        ]);

        // set the active tenant context
        app()->instance('current.tenant.id', $tenant->id);

        // create a dashboard owned by the owner user
        /** @var Dashboard $dashboard */
        $dashboard = Dashboard::factory()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $owner->id,
        ]);

        // attempt to update the dashboard as a viewer
        $response = $this->actingAs($viewer)->putJson(
            "/api/v1/dashboards/{$dashboard->id}",
            [
                'name' => 'Hacked Name',
            ]
        );

        // verify access is denied
        $response->assertStatus(403);
    }

    public function test_presence_endpoint_returns_redis_data()
    {
        // create a tenant and owner user
        $tenant = Tenant::factory()->create();

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'workspace_role' => 'owner',
        ]);

        // set the active tenant context
        app()->instance('current.tenant.id', $tenant->id);

        // create a dashboard for testing presence data
        $dashboard = Dashboard::factory()->create([
            'tenant_id' => $tenant->id,
            'created_by' => $owner->id,
        ]);

        // simulate a connected user stored in redis presence data
        Redis::hset(
            "presence:dashboard.{$tenant->id}",
            'socket_123',
            json_encode([
                'user_info' => [
                    'id' => 99,
                    'name' => 'Bob',
                ],
            ])
        );

        // request the dashboard presence endpoint
        $response = $this->actingAs($owner)
            ->getJson("/api/v1/dashboards/{$dashboard->id}/presence");

        // verify the redis presence data is returned in the response
        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => 99,
                'name' => 'Bob',
            ]);

        // clear redis after the test completes
        Redis::flushDB();
    }
}
