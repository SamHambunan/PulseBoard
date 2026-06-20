<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// The Presence Channel for the dashboard
Broadcast::channel('dashboard.{tenantId}', function (User $user, string $tenantId) {
    // 1. Verify the user belongs to this tenant
    if ($user->tenant_id != $tenantId) {
        return false; // Denies access (403 Forbidden)
    }

    // 2. Return presence data (This powers the "Who's Online" feature)
    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});
