<?php

namespace Tests\Feature;

use App\Models\Dashboard;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * feature tests for dashboard authorization policies.
 *
 * this test suite validates role-based and permission-based access control
 * rules for dashboards in a multi-tenant environment.
 *
 * it ensures that:
 * - tenant isolation is respected through current tenant context
 * - workspace roles (owner, admin, member, viewer) enforce correct permissions
 * - pivot-based permissions (dashboard_user table) correctly control access
 * - sensitive actions like update and delete are properly restricted
 *
 * tests use laravel's can() method which relies on policies or gates.
 */
class DashboardPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * active tenant used across all tests.
     */
    private Tenant $tenant;

    /**
     * dashboard instance used in tests.
     */
    private Dashboard $dashboard;

    /**
     * setup shared test environment before each test.
     *
     * creates a tenant, sets tenant context, creates an owner user,
     * and creates a dashboard for that tenant.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // create tenant for isolation

        $this->tenant = Tenant::factory()->create();

        // simulate middleware setting current tenant context
        app()->instance('current.tenant.id', $this->tenant->id);

        // create owner user
        $owner = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'workspace_role' => 'owner',
        ]);

        // create dashboard owned by tenant
        $this->dashboard = Dashboard::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $owner->id,
        ]);
    }

    /**
     * verify admins can manage dashboards fully.
     *
     * admins should be able to view, update, and delete dashboards
     * within their tenant.
     */
    public function test_owners_and_admins_can_manage_everything()
    {
        $admin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'workspace_role' => 'admin',
        ]);

        $this->assertTrue($admin->can('view', $this->dashboard));
        $this->assertTrue($admin->can('update', $this->dashboard));
        $this->assertTrue($admin->can('delete', $this->dashboard));
    }

    /**
     * verify users without permissions cannot access dashboards.
     *
     * viewer users without pivot permissions should not be able to view
     * or update dashboards.
     */
    public function test_standard_user_cannot_view_without_permission()
    {
        $viewer = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'workspace_role' => 'viewer',
        ]);

        $this->assertFalse($viewer->can('view', $this->dashboard));
        $this->assertFalse($viewer->can('update', $this->dashboard));
    }

    /**
     * verify view permission allows reading only.
     *
     * users with only 'view' permission can view dashboards but cannot edit.
     */
    public function test_standard_user_with_view_permission_cannot_edit()
    {
        $viewer = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'workspace_role' => 'viewer',
        ]);

        DB::table('dashboard_user')->insert([
            'dashboard_id' => $this->dashboard->id,
            'user_id' => $viewer->id,
            'permission' => 'view',
        ]);

        $this->assertTrue($viewer->can('view', $this->dashboard));
        $this->assertFalse($viewer->can('update', $this->dashboard));
    }

    /**
     * verify edit permission allows updates but not deletion.
     *
     * users with 'edit' permission can update dashboards but only
     * owners/admins can delete them.
     */
    public function test_standard_user_with_edit_permission_cannot_delete()
    {
        $member = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'workspace_role' => 'member',
        ]);

        DB::table('dashboard_user')->insert([
            'dashboard_id' => $this->dashboard->id,
            'user_id' => $member->id,
            'permission' => 'edit',
        ]);

        $this->assertTrue($member->can('view', $this->dashboard));
        $this->assertTrue($member->can('update', $this->dashboard));
        $this->assertFalse($member->can('delete', $this->dashboard));
    }
}
