<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;

class BroadcastAuthController extends Controller
{
    public function authenticate(Request $request)
    {
        $user = $request->user();
        $channel = $request->input('channel_name');

        // Cache the authorization result for 30 seconds to prevent DB hammering
        $cacheKey = "broadcast_auth:{$user->id}:".md5((string) $channel);
        $auth = Cache::remember($cacheKey, 30, function () use ($request) {
            // Let Laravel evaluate the rules in routes/channels.php
            return Broadcast::auth($request);

        });

        // The exact JSON format that Echo/Reverb expects
        return response()->json($auth);
    }
}
