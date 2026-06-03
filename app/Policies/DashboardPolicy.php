<?php

namespace App\Policies;

use App\Models\Dashboard;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * authorization rules for dashboard access.
 *
 * role hierarchy:
 * - owner  : full access within the tenant
 * - admin  : full access within the tenant
 * - member : access determined by dashboard permissions
 * - viewer : access determined by dashboard permissions
 *
 * dashboard permissions for members/viewers are stored
 * in the dashboard_user pivot table.
 */

class DashboardPolicy
{

    /**
     * determine whether the user can view a dashboard.
     *
     * Rules:
     * - owners and admins can view any dashboard in their tenant.
     * - members and viewers must be explicitly assigned to
     *   the dashboard.
     */
    public function view(User $user, Dashboard $dashboard): bool
    {
        // Owners and Admins see all dashboards in their tenant
        if (in_array($user->workspace_role, ['owner', 'admin'])) {
            return $user->tenant_id === $dashboard->tenant_id;
        }

        // non-administrative users require an explicit
        // dashboard assignment.
        return DB::table('dashboard_user')
            ->where('dashboard_id', $dashboard->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * determine whether the user can modify a dashboard.
     *
     * Rules:
     * - owners and admins can edit any dashboard in their tenant.
     * - members/viewers require an explicit "edit" permission.
     */
    public function update(User $user, Dashboard $dashboard): bool
    {
        // tenant administrators may edit any dashboard
        // within their workspace.
        if (in_array($user->workspace_role, ['owner', 'admin'])) {
            return $user->tenant_id === $dashboard->tenant_id;
        }

        // dashboard-level edit permission is required
        // for non-administrative users.
        return DB::table('dashboard_user')
            ->where('dashboard_id', $dashboard->id)
            ->where('user_id', $user->id)
            ->where('permission', 'edit')
            ->exists();
    }

    /**
     * determine whether the user can delete a dashboard.
     *
     * rules:
     * - owners and admins may delete any dashboard.
     * - dashboard creators may delete dashboards they created.
     */
    public function delete(User $user, Dashboard $dashboard): bool
    {
        return in_array($user->workspace_role, ['owner', 'admin'])
            || $dashboard->created_by === $user->id;
    }
}
