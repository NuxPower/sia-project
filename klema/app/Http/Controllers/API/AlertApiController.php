<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Farm;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AlertApiController extends Controller
{
    /**
     * Display a listing of alerts for the authenticated user.
     */
    public function index(): JsonResponse
    {
        $alerts = Alert::whereHas('farm', function($query) {
            $query->where('user_id', auth()->id());
        })->with('farm')
          ->orderBy('issued_at', 'desc')
          ->get();
        
        return response()->json([
            'success' => true,
            'alerts' => $alerts
        ]);
    }

    /**
     * Store a newly created alert.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'required|exists:farms,farm_id',
            'alert_type' => 'required|string|max:50',
            'message' => 'required|string|max:500',
        ]);

        $farm = Farm::findOrFail($validated['farm_id']);
        
        // Check if user owns the farm
        if ($farm->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to farm'
            ], 403);
        }

        $alert = Alert::create([
            'farm_id' => $validated['farm_id'],
            'alert_type' => $validated['alert_type'],
            'message' => $validated['message'],
            'issued_at' => now(),
            'resolved' => false,
        ]);

        return response()->json([
            'success' => true,
            'alert' => $alert->load('farm'),
            'message' => 'Alert created successfully!'
        ], 201);
    }

    /**
     * Display the specified alert.
     */
    public function show(Alert $alert): JsonResponse
    {
        // Check if user owns the farm associated with this alert
        if ($alert->farm->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to alert'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'alert' => $alert->load('farm')
        ]);
    }

    /**
     * Update the specified alert.
     */
    public function update(Request $request, Alert $alert): JsonResponse
    {
        // Check if user owns the farm associated with this alert
        if ($alert->farm->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to alert'
            ], 403);
        }

        $validated = $request->validate([
            'alert_type' => 'sometimes|string|max:50',
            'message' => 'sometimes|string|max:500',
            'resolved' => 'sometimes|boolean',
        ]);

        $alert->update($validated);

        return response()->json([
            'success' => true,
            'alert' => $alert->load('farm'),
            'message' => 'Alert updated successfully!'
        ]);
    }

    /**
     * Remove the specified alert from storage.
     */
    public function destroy(Alert $alert): JsonResponse
    {
        // Check if user owns the farm associated with this alert
        if ($alert->farm->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to alert'
            ], 403);
        }

        $alert->delete();

        return response()->json([
            'success' => true,
            'message' => 'Alert deleted successfully!'
        ]);
    }

    /**
     * Resolve the specified alert.
     */
    public function resolve(Alert $alert): JsonResponse
    {
        // Check if user owns the farm associated with this alert
        if ($alert->farm->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to alert'
            ], 403);
        }

        $alert->update(['resolved' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Alert resolved successfully!'
        ]);
    }

    /**
     * Get active (unresolved) alerts for the authenticated user.
     */
    public function active(): JsonResponse
    {
        $alerts = Alert::whereHas('farm', function($query) {
            $query->where('user_id', auth()->id());
        })->where('resolved', false)
          ->with('farm')
          ->orderBy('issued_at', 'desc')
          ->get();
        
        return response()->json([
            'success' => true,
            'alerts' => $alerts
        ]);
    }

    /**
     * Get weather-based forecast warnings.
     */
    public function forecastWarnings(): JsonResponse
    {
        // This would typically integrate with weather data to generate warnings
        // For now, return mock data that could be replaced with real weather analysis
        $warnings = [
            [
                'id' => 1,
                'day' => 'Tomorrow',
                'message' => 'High wind speeds expected',
                'value' => '45 km/h',
                'icon' => 'fas fa-wind',
                'type' => 'wind'
            ],
            [
                'id' => 2,
                'day' => 'Wednesday',
                'message' => 'Heavy precipitation',
                'value' => '85mm',
                'icon' => 'fas fa-cloud-rain',
                'type' => 'rain'
            ],
            [
                'id' => 3,
                'day' => 'Friday',
                'message' => 'Temperature drop',
                'value' => '18°C',
                'icon' => 'fas fa-temperature-low',
                'type' => 'temperature'
            ]
        ];

        return response()->json([
            'success' => true,
            'warnings' => $warnings
        ]);
    }
}
