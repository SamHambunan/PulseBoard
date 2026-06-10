<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardRequest;
use App\Models\Dashboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    /**
     * display a list of dashboards visible to the authenticated user
     *
     * - admins/owners can see all dashboards
     * - viewers and members only see dashboards they are assigned to
     */
    public function index(): JsonResponse
    {
        $user = request()->user();

        // base query for dashboards
        $query = Dashboard::query();

        /**
         * restrict visibility for lower-privileged roles
         *
         * only return dashboards where the user exists in the pivot table
         */
        if (in_array($user->workspace_role, ['viewer', 'member'])) {
            $query->whereHas('users', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return response()->json($query->get());
    }

    /**
     * create a new dashboard
     *
     * the authenticated user is stored as the creator
     * validation is handled by dashboardrequest
     */
    public function store(DashboardRequest $request): JsonResponse
    {
        $dashboard = Dashboard::create([
            'name'       => $request->name,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($dashboard, 201);
    }

    /**
     * display a single dashboard
     *
     * authorization:
     * - user must pass the view policy
     *
     * data:
     * - widgets are eager loaded to avoid n+1 queries
     */
    public function show(Dashboard $dashboard): JsonResponse
    {
        Gate::authorize('view', $dashboard);

        return response()->json(
            $dashboard->load('widgets')
        );
    }

    /**
     * update an existing dashboard
     *
     * authorization:
     * - user must pass update policy
     *
     * only validated fields are used
     */
    public function update(DashboardRequest $request, Dashboard $dashboard): JsonResponse
    {
        Gate::authorize('update', $dashboard);

        $dashboard->update($request->validated());

        return response()->json($dashboard);
    }

    /**
     * delete a dashboard
     *
     * authorization:
     * - user must pass delete policy
     *
     * permanently removes the record
     */
    public function destroy(Dashboard $dashboard): JsonResponse
    {
        Gate::authorize('delete', $dashboard);

        $dashboard->delete();

        return response()->json(null, 204);
    }
}
