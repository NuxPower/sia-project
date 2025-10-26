<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmPoint;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FarmApiController extends Controller
{
    /**
     * Display a listing of farms for the authenticated user.
     */
    public function index(): JsonResponse
    {
        $farms = auth()->user()->farms()->with([
            'weatherData' => function($query) {
                $query->latest('recorded_at')->limit(1);
            },
            'alerts' => function($query) {
                $query->where('resolved', false);
            }
        ])->get();
        
        return response()->json([
            'success' => true,
            'farms' => $farms
        ]);
    }

    /**
     * Store a newly created farm.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_name' => 'required|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $farm = Farm::create([
            'user_id' => auth()->id(),
            'farm_name' => $validated['farm_name'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);

        return response()->json([
            'success' => true,
            'farm' => $farm,
            'message' => 'Farm created successfully!'
        ], 201);
    }

    /**
     * Display the specified farm.
     */
    public function show(Farm $farm): JsonResponse
    {
        // Check if user owns the farm
        if ($farm->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to farm'
            ], 403);
        }

        $farm->load([
            'weatherData' => function($query) {
                $query->latest('recorded_at')->limit(10);
            },
            'farmPoints',
            'alerts' => function($query) {
                $query->latest('issued_at');
            }
        ]);

        return response()->json([
            'success' => true,
            'farm' => $farm
        ]);
    }

    /**
     * Update the specified farm.
     */
    public function update(Request $request, Farm $farm): JsonResponse
    {
        // Check if user owns the farm
        if ($farm->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to farm'
            ], 403);
        }

        $validated = $request->validate([
            'farm_name' => 'sometimes|string|max:100',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
        ]);

        $farm->update($validated);

        return response()->json([
            'success' => true,
            'farm' => $farm,
            'message' => 'Farm updated successfully!'
        ]);
    }

    /**
     * Remove the specified farm from storage.
     */
    public function destroy(Farm $farm): JsonResponse
    {
        // Check if user owns the farm
        if ($farm->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to farm'
            ], 403);
        }

        $farm->delete();

        return response()->json([
            'success' => true,
            'message' => 'Farm deleted successfully!'
        ]);
    }

    /**
     * Add a point to the farm.
     */
    public function addPoint(Request $request, Farm $farm): JsonResponse
    {
        // Check if user owns the farm
        if ($farm->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to farm'
            ], 403);
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'point_name' => 'sometimes|string|max:100',
        ]);

        $point = FarmPoint::create([
            'farm_id' => $farm->farm_id,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'point_name' => $validated['point_name'] ?? 'Point ' . ($farm->farmPoints()->count() + 1),
        ]);

        return response()->json([
            'success' => true,
            'point' => $point,
            'message' => 'Point added successfully!'
        ], 201);
    }

    /**
     * Get weather data for a specific farm.
     */
    public function getWeatherData(Farm $farm): JsonResponse
    {
        // Check if user owns the farm
        if ($farm->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to farm'
            ], 403);
        }

        $weatherData = $farm->weatherData()
            ->orderBy('recorded_at', 'desc')
            ->limit(24) // Last 24 hours
            ->get();

        return response()->json([
            'success' => true,
            'weather_data' => $weatherData
        ]);
    }
}
