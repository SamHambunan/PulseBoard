<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\ApiKey;
use App\Models\Dashboard;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * handles tenant onboarding and user authentication.
 *
 * responsibilities:
 * - register new tenants and their initial owner account
 * - issue authentication tokens
 * - authenticate existing users
 * - revoke active access tokens
 */
class AuthController extends Controller
{
    /**
     * register a new tenant and owner account.
     *
     * registration workflow:
     * 1. create the tenant/workspace
     * 2. create the initial owner user
     * 3. create a default dashboard
     * 4. generate a default ingestion API key
     * 5. issue an authentication token
     *
     * all operations are wrapped in a database transaction to
     * ensure the tenant is never partially provisioned.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $response = DB::transaction(function () use ($request) {

            // create the tenant that owns all workspace resources.
            $tenant = Tenant::create([
                'name' => $request->company_name,
            ]);

            // establish tenant context during provisioning so
            // tenant-scoped models can be created successfully.
            app()->instance('current.tenant.id', $tenant->id);

            // create the first user and assign ownership of
            // the newly created workspace.
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'workspace_role' => 'owner', // First user is the owner
            ]);

            // provision a starter dashboard for new tenants.
            Dashboard::create([
                'tenant_id' => $tenant->id,
                'created_by' => $user->id,
                'name' => 'Default Dashboard',
            ]);

            // generated the default ingestion key.
            // only the hashed version is stored.
            $rawApiKey = 'pb_'.Str::random(40);
            ApiKey::create([
                'tenant_id' => $tenant->id,
                'name' => 'Default Ingestion Key',
                'key_hash' => hash('sha256', $rawApiKey),
            ]);

            // create a Sanctum access token for immediate login.
            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,

                // the raw API key is only available during creation.
                // it cannot be recovered after this response.
                'api_key' => $rawApiKey,
            ];
        });

        return response()->json($response, 201);
    }

    /**
     * Authenticate a user and issue an access token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token]);
    }

    /**
     * revoke the current access token.
     *
     * this logs the user out of the current device/session
     * without affecting other active tokens.
     */
    public function logout(): JsonResponse
    {
        /** @var User $user */
        $user = request()->user();
        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
