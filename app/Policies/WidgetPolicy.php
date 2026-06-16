<?php

namespace App\Policies;

use App\Models\Dashboard;
use App\Models\User;
use App\Models\Widget;

class WidgetPolicy
{
    // A user can manage a widget if they have permission to update its parent dashboard
    public function create(User $user): bool
    {
        $dashboard = Dashboard::findOrFail(request()->route('dashboard'));

        return $user->can('update', $dashboard);
    }

    public function update(User $user, Widget $widget): bool
    {
        return $user->can('update', $widget->dashboard);
    }

    public function delete(User $user, Widget $widget): bool
    {
        return $user->can('update', $widget->dashboard);
    }
}
