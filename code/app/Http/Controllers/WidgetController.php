<?php

namespace App\Http\Controllers;

use App\Http\Requests\WidgetRequest;
use App\Models\Dashboard;
use App\Models\Widget;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class WidgetController extends Controller
{
    // note: The $dashboard parameter is automatically injected by Laravel because
    // the route URL will be: POST /api/v1/dashboards/{dashboard}/widgets
    public function store(WidgetRequest $request, Dashboard $dashboard): JsonResponse
    {
        // check if the user is allowed to edit this specific dashboard
        Gate::authorize('update', $dashboard);

        // we use the dashboard's relationship to automatically stamp the dashboard_id
        $widget = $dashboard->widgets()->create($request->validated());

        return response()->json($widget, 201);
    }

    public function update(WidgetRequest $request, Widget $widget): JsonResponse
    {
        Gate::authorize('update', $widget);

        $widget->update($request->validated());

        return response()->json($widget);
    }

    public function destroy(Widget $widget): JsonResponse
    {
        Gate::authorize('delete', $widget);

        $widget->delete();

        return response()->json(null, 204);
    }
}
