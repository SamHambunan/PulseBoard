<?php

namespace Tests\Feature\Auth;

use App\Models\ApiKey;
use App\Models\Dashboard;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * feature tests for user registration and workspace provisioning.
 *
 * these tests verify that the registration process successfully creates
 * all required resources for a new tenant workspace, including the tenant,
 * user account, default dashboard, API key, and appropriate role assignment.
 */

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * verify that a new user can register and that all workspace resources
     * are automatically provisioned.
     *
     * this test ensures that:
     * - the registration endpoint returns a successful response.
     * - authentication credentials are returned to the client.
     * - a tenant workspace is created.
     * - a user account is created and linked to the tenant.
     * - a default dashboard is provisioned.
     * - an API key is generated for workspace access.
     * - the registering user is assigned the owner role.
     *
     */

    public function test_user_can_register_and_provisions_workspace()
    {
        // submit a registration request for a new workspace owner.
        $response = $this->postJson('/api/v1/auth/register', [
            'company_name' => 'Acme Corp',
            'name'         => 'John Doe',
            'email'        => 'john@example.com',
            'password'     => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // verify the registration request succeeded and returned
        // the expected authentication and provisioning data.
        $response->assertStatus(201)
            ->assertJsonStructure(['user', 'token', 'api_key']);

        // verify that the tenant workspace was created.
        $this->assertEquals(1, Tenant::count());


        // verify that the user account was created.
        $this->assertEquals(1, User::count());


        // verify that a default dashboard was provisioned.
        $this->assertEquals(1, Dashboard::count());


        // verify that an API key was generated.
        $this->assertEquals(1, ApiKey::count());


        // verify that the registering user received owner privileges.
        $this->assertEquals('owner', User::first()->workspace_role);
    }
}
