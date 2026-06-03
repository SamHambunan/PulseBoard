<?php

namespace Tests\Feature\Auth;

use App\Models\Dashboard;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * feature tests for tenant data isolation.
 *
 * these tests verify that the application's multi-tenant architecture
 * correctly restricts data access through global scopes, ensuring that
 * users can only access records belonging to their own tenant.
 */

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;
    /**
     * verify that the tenant global scope prevents cross-tenant data access.
     *
     * this test creates two separate tenants, each with its own user and
     * dashboard. it then simulates requests from one tenant and confirms
     * that only records belonging to that tenant are returned, even though
     * records from multiple tenants exist in the database.
     *
     * test flow:
     * 1. create Tenant A and its dashboard.
     * 2. create Tenant B and its dashboard.
     * 3. verify that both dashboards exist in the database.
     * 4. simulate Tenant A's context.
     * 5. confirm that Tenant A can only retrieve its own dashboard.
     *
     */
    public function test_global_scope_prevents_cross_tenant_data_leaks()
    {
        // create tenant A and an associated user.
        $tenantA = Tenant::factory()->create();
        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);

        // simulate middleware setting the current tenant context.
        app()->instance('current.tenant.id', $tenantA->id);

        // create a dashboard owned by tenant A.
        Dashboard::factory()->create(['tenant_id' => $tenantA->id, 'created_by' => $userA->id]);

        // create Tenant B and an associated user.
        $tenantB = Tenant::factory()->create();
        $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

        // simulate middleware setting tenant B's context.
        app()->instance('current.tenant.id', $tenantB->id);

        // create a dashboard owned by tenant B.
        Dashboard::factory()->create(['tenant_id' => $tenantB->id, 'created_by' => $userB->id]);

        // verify that both dashboard records exist in the database.
        $this->assertDatabaseCount('dashboards', 2);

        // simulate a request from Tenant A.
        app()->instance('current.tenant.id', $tenantA->id);
        // retrieve dashboards through the model, which applies the global scope.
        $dashboards = Dashboard::all();

        // confirm that only Tenant A's dashboard is visible.
        $this->assertCount(1, $dashboards);

        // verify that the returned dashboard belongs to Tenant A.
        $this->assertEquals($tenantA->id, $dashboards->first()->tenant_id);
    }
}
