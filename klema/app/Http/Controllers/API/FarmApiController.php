<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmPoint;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class FarmApiController extends Controller
{
    /**
     * Display a listing of farms for the authenticated user.
     */
    public function index(): JsonResponse
    {
        $farmsQuery = Farm::query()
            ->with([
                'weatherData' => function ($query) {
                    $query->latest('recorded_at')->limit(1);
                },
                'alerts' => function ($query) {
                    $query->where('resolved', false);
                },
            ]);

        $farms = $farmsQuery->get();
        
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
            'size_hectares' => 'nullable|numeric|min:0|max:100000',
            'soil_type' => ['nullable', 'string', Rule::in(config('farm.soil_types'))],
            'description' => 'nullable|string',
            'boundary' => 'nullable',
        ]);

        $farm = new Farm([
            'user_id' => auth()->id(),
            'farm_name' => $validated['farm_name'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'size_hectares' => $validated['size_hectares'] ?? null,
            'soil_type' => $validated['soil_type'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        if (array_key_exists('boundary', $validated)) {
            $farm->boundary = $validated['boundary'];
        }

        $farm->save();

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
        $validated = $request->validate([
            'farm_name' => 'sometimes|string|max:100',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'size_hectares' => 'sometimes|nullable|numeric|min:0|max:100000',
            'soil_type' => ['sometimes','nullable','string', Rule::in(config('farm.soil_types'))],
            'description' => 'sometimes|nullable|string',
            'boundary' => 'nullable',
        ]);

        $farm->fill($request->only([
            'farm_name',
            'latitude',
            'longitude',
            'size_hectares',
            'soil_type',
            'description',
        ]));

        if ($request->exists('boundary')) {
            $farm->boundary = $request->input('boundary');
        }

        $farm->save();

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
        $validated = $request->validate([
            'label' => 'required|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'point_type' => 'nullable|string|max:50',
        ]);

        $point = $farm->farmPoints()->create([
            'label' => $validated['label'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'point_type' => $validated['point_type'] ?? null,
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
        $weatherData = $farm->weatherData()
            ->orderBy('recorded_at', 'desc')
            ->limit(24) // Last 24 hours
            ->get();

        return response()->json([
            'success' => true,
            'weather_data' => $weatherData
        ]);
    }

    public function mapData(): JsonResponse
    {
        $farmsQuery = Farm::with('farmPoints');
        $farms = $farmsQuery->get();

        $farmFeatures = [];
        $pointFeatures = [];

        foreach ($farms as $farm) {
            $properties = [
                'farm_id' => $farm->farm_id,
                'farm_name' => $farm->farm_name,
                'size_hectares' => $farm->size_hectares,
                'soil_type' => $farm->soil_type,
                'description' => $farm->description,
            ];

            $boundary = $farm->boundary;

            if ($boundary) {
                $farmFeatures[] = [
                    'type' => 'Feature',
                    'geometry' => $boundary,
                    'properties' => array_merge($properties, [
                        'type' => 'boundary',
                    ]),
                ];
            } else {
                $farmFeatures[] = [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [(float) $farm->longitude, (float) $farm->latitude],
                    ],
                    'properties' => array_merge($properties, [
                        'type' => 'centroid',
                    ]),
                ];
            }

            foreach ($farm->farmPoints as $point) {
                $pointFeatures[] = [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [(float) $point->longitude, (float) $point->latitude],
                    ],
                    'properties' => [
                        'point_id' => $point->point_id,
                        'farm_id' => $farm->farm_id,
                        'label' => $point->label,
                        'point_type' => $point->point_type,
                    ],
                ];
            }
        }

        return response()->json([
            'success' => true,
            'farms' => $farms,
            'soil_types' => config('farm.soil_types'),
            'farm_features' => [
                'type' => 'FeatureCollection',
                'features' => $farmFeatures,
            ],
            'point_features' => [
                'type' => 'FeatureCollection',
                'features' => $pointFeatures,
            ],
        ]);
    }
}
