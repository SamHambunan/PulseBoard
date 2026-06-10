<?php

namespace App\Http\Controllers;

use App\Models\Dashboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redis;

class PresenceController extends Controller
{
    public function index(Dashboard $dashboard): JsonResponse
    {
        // ensure the user is allowed to view this dashboard
        Gate::authorize('view', $dashboard);

        // fetch the connected users from reverb's redis tracking mechanism
        // reverb stores presence data in Redis under 'presence:dashboard.{tenantId}'
        $channel = "dashboard.{$dashboard->tenant_id}";

        // this grabs all the member data arrays from the Redis hash
        $rawMembers = Redis::hgetall("presence:{$channel}");

        $users = [];
        foreach ($rawMembers as $memberData) {
            $data = json_decode($memberData, true);
            if ($data) {
                // Return only non-sensitive data (id and name)
                $users[] = [
                    'id'   => $data['user_info']['id'] ?? null,
                    'name' => $data['user_info']['name'] ?? 'Unknown',
                ];
            }
        }

        // Return the list of online users
        return response()->json(['online_users' => $users]);
    }
}
