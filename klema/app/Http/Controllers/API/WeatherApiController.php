<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Str;

class WeatherApiController extends Controller
{
    protected $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * Get current weather data.
     */
    public function getCurrentWeather(Request $request): JsonResponse
    {
        $lat = $request->get('lat');
        $lon = $request->get('lon');
        $location = $request->get('location');

        if ($location === null && ($lat === null || $lon === null)) {
            $location = 'Butuan, Caraga, PH';
        }
        
        try {
            if ($lat && $lon) {
                // Use coordinates
                $weather = $this->weatherService->getCurrentWeatherByCoordinates($lat, $lon);
            } else {
                // Use location name
                $weather = $this->weatherService->getCurrentWeather($location);
            }

            $this->weatherService->storeWeatherSnapshot($weather, [
                'location' => $weather['name'] ?? $location,
                'lat' => $lat,
                'lon' => $lon,
            ]);

            return response()->json($weather);
        } catch (\Throwable $e) {
            \Log::error('Weather API error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to fetch weather data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get weather forecast.
     */
    public function getForecast(Request $request): JsonResponse
    {
        $lat = $request->get('lat');
        $lon = $request->get('lon');
        $location = $request->get('location');

        if ($location === null && ($lat === null || $lon === null)) {
            $location = 'Butuan, Caraga, PH';
        }
        $days = $request->get('days', 5);
        
        try {
            if ($lat && $lon) {
                // Use coordinates
                $forecast = $this->weatherService->getForecastByCoordinates($lat, $lon, $days);
            } else {
                // Use location name
                $forecast = $this->weatherService->getForecast($location, $days);
            }

            return response()->json($forecast);
        } catch (\Throwable $e) {
            \Log::error('Weather Forecast API error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to fetch forecast data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getWeatherHistory(Request $request): JsonResponse
    {
        $lat = $request->get('lat');
        $lon = $request->get('lon');
        $location = $request->get('location');
        $farmId = $request->get('farm_id') ? (int) $request->get('farm_id') : null;

        // If farm_id is provided, fetch the farm's coordinates and name
        $farm = null;
        if ($farmId !== null) {
            $farm = \App\Models\Farm::find($farmId);
            if ($farm && $farm->latitude !== null && $farm->longitude !== null) {
                // Use farm's actual coordinates - this ensures we fetch data for the correct location
                $lat = (string) $farm->latitude;
                $lon = (string) $farm->longitude;
                // Use farm name as location if not provided
                if ($location === null) {
                    $location = $farm->farm_name;
                }
            }
        }

        if ($location === null && ($lat === null || $lon === null) && $farmId === null) {
            $location = 'Butuan, Caraga, PH';
        }
        $days = (int) $request->get('days', 30);
        $days = max(1, min($days, 90)); // Increased from 30 to 90 days

        try {
            // Resolve coordinates if needed
            $resolvedCoordinates = null;
            $targetLat = $lat !== null ? (float) $lat : null;
            $targetLon = $lon !== null ? (float) $lon : null;

            if ($targetLat === null || $targetLon === null) {
                if ($location && $resolvedCoordinates === null) {
                    $resolvedCoordinates = $this->weatherService->geocodeLocation($location);
                    if ($resolvedCoordinates) {
                        $targetLat = $resolvedCoordinates['lat'];
                        $targetLon = $resolvedCoordinates['lon'];
                    }
                }
            }

            if ($targetLat === null || $targetLon === null) {
                return response()->json([]);
            }

            // Use UTC for consistent date handling
            $today = Carbon::today('UTC');
            $tomorrow = $today->copy()->addDay();
            $startDate = $today->copy()->subDays($days - 1); // Include today
            $endDate = $today->copy();

            // Fetch directly from API with caching - no database storage needed for display
            // Open-Meteo is free and reliable, so we can use it directly
            $series = $this->weatherService->fetchHistoricalSeriesByCoordinates($targetLat, $targetLon, $startDate, $endDate);

            // Normalize the data for response
            $historyByDate = collect($series)->map(function ($entry, $dateKey) use ($resolvedCoordinates, $location, $farm) {
                // Add location name if not present
                if (!isset($entry['name'])) {
                    if ($farm && $farm->farm_name) {
                        $entry['name'] = $farm->farm_name;
                    } elseif ($location && $location !== 'Butuan, Caraga, PH' && $location !== 'Northern Mindanao') {
                        $entry['name'] = $location;
                    } elseif ($resolvedCoordinates && isset($resolvedCoordinates['name'])) {
                        $resolvedName = $resolvedCoordinates['name'];
                        if (!in_array(strtolower($resolvedName), ['butuan', 'cagayan de oro', 'northern mindanao'])) {
                            $entry['name'] = $resolvedName;
                        }
                    }
                }
                return $this->normalizeHistoricalEntry($dateKey, $entry);
            })->filter(function ($entry) {
                // Filter out entries without detail
                return $entry && $this->historyEntryHasDetail($entry);
            });

            $sortedHistory = $historyByDate
                ->sortKeys()
                ->values()
                ->all();

            return response()->json($sortedHistory);
        } catch (\Throwable $e) {
            \Log::error('Weather History API error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to fetch weather history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get historical weather data.
     * Now fetches directly from API with caching - no database storage.
     */
    public function getHistoricalWeather(Request $request, string $date): JsonResponse
    {
        $lat = $request->get('lat');
        $lon = $request->get('lon');
        $location = $request->get('location');

        if ($location === null && ($lat === null || $lon === null)) {
            $location = 'Butuan, Caraga, PH';
        }
        
        try {
            $dateObj = Carbon::createFromFormat('Y-m-d', $date);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
                return response()->json([
                    'message' => 'Invalid date format. Use YYYY-MM-DD'
                ], 400);
            }

            $targetLat = $lat !== null ? (float) $lat : null;
            $targetLon = $lon !== null ? (float) $lon : null;

            // Resolve coordinates if needed
            if ($targetLat === null || $targetLon === null) {
                if ($location) {
                    $geocoded = $this->weatherService->geocodeLocation($location);
                    if ($geocoded) {
                        $targetLat = $geocoded['lat'];
                        $targetLon = $geocoded['lon'];
                    }
                }
            }

            if ($targetLat === null || $targetLon === null) {
                return response()->json([
                    'message' => 'Unable to determine location coordinates.'
                ], 400);
            }

            // Fetch directly from API (with caching)
            $series = $this->weatherService->fetchHistoricalSeriesByCoordinates($targetLat, $targetLon, $dateObj->copy(), $dateObj->copy());
            $historical = $series[$date] ?? null;

            if ($historical === null) {
                return response()->json([
                    'message' => 'Historical weather data is unavailable for the requested date.',
                ], 404);
            }

            // Add location name if not present
            if (!isset($historical['name']) && $location) {
                $historical['name'] = $location;
            }

            return response()->json($this->normalizeHistoricalEntry($date, $historical));
        } catch (\Throwable $e) {
            \Log::error('Historical Weather API error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to fetch historical weather data: ' . $e->getMessage()
            ], 500);
        }
    }


    private function normalizeHistoricalEntry(string $date, $data): array
    {
        $temperature = data_get($data, 'main.temp');
        $tempMax = data_get($data, 'main.temp_max', $temperature !== null ? $temperature + 2 : null);
        $tempMin = data_get($data, 'main.temp_min', $temperature !== null ? $temperature - 3 : null);

        $conditionData = data_get($data, 'weather.0', []);

        $sunrise = data_get($data, 'sunrise');
        $sunset = data_get($data, 'sunset');
        $timezoneOffset = data_get($data, 'timezone_offset');
        $hourly = data_get($data, 'hourly', []);
        $precipProbability = data_get($data, 'precip_probability');
        $precipitationSum = data_get($data, 'precipitation_sum');

        return [
            'date' => $date,
            'temp_max' => $this->normalizeTemperature($tempMax),
            'temp_min' => $this->normalizeTemperature($tempMin),
            'condition' => $conditionData['main'] ?? ($conditionData['description'] ?? 'Unknown'),
            'description' => $conditionData['description'] ?? ($conditionData['main'] ?? 'Unknown'),
            'icon' => $conditionData['icon'] ?? '01d',
            'sunrise' => is_numeric($sunrise) ? (int) $sunrise : null,
            'sunset' => is_numeric($sunset) ? (int) $sunset : null,
            'timezone_offset' => is_numeric($timezoneOffset) ? (int) $timezoneOffset : null,
            'hourly' => array_values(is_array($hourly) ? $hourly : []),
            'precip_probability' => $precipProbability !== null ? (float) $precipProbability : null,
            'precipitation_sum' => $precipitationSum !== null ? (float) $precipitationSum : null,
        ];
    }

    private function normalizeTemperature($value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $value = (float) $value;
        if ($value > 150) {
            $value -= 273.15;
        }

        return (int) round($value);
    }

    private function historyEntryHasDetail($entry): bool
    {
        if (!is_array($entry) || data_get($entry, 'noData') === true) {
            return false;
        }

        $hourly = data_get($entry, 'hourly');
        if (is_array($hourly) && !empty($hourly)) {
            return true;
        }

        $sunrise = data_get($entry, 'sunrise');
        $sunset = data_get($entry, 'sunset');
        if (is_numeric($sunrise) && is_numeric($sunset)) {
            return true;
        }

        $tempMax = data_get($entry, 'temp_max');
        if ($tempMax === null) {
            $tempMax = data_get($entry, 'main.temp_max');
        }

        $tempMin = data_get($entry, 'temp_min');
        if ($tempMin === null) {
            $tempMin = data_get($entry, 'main.temp_min');
        }

        $condition = data_get($entry, 'condition');
        if ($condition === null) {
            $condition = data_get($entry, 'weather.0.main');
        }

        $description = data_get($entry, 'description');
        if ($description === null) {
            $description = data_get($entry, 'weather.0.description');
        }

        $hasTemperatures = $tempMax !== null || $tempMin !== null;
        $hasCondition = !empty($condition) || !empty($description);

        return $hasTemperatures || $hasCondition;
    }

    // Database query methods removed - now using API directly with caching

    /**
     * Manually trigger weather update from external API (Admin only).
     */
    public function manualUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location' => 'nullable|string',
            'lat' => 'nullable|numeric|between:-90,90',
            'lon' => 'nullable|numeric|between:-180,180',
            'farm_id' => 'nullable|exists:farms,farm_id',
        ]);

        $location = $validated['location'] ?? null;
        $lat = $validated['lat'] ?? null;
        $lon = $validated['lon'] ?? null;
        $farmId = $validated['farm_id'] ?? null;

        try {
            if ($farmId) {
                $farm = \App\Models\Farm::findOrFail($farmId);
                $lat = $farm->latitude;
                $lon = $farm->longitude;
                $location = $farm->farm_name;
            }

            if ($lat && $lon) {
                $weather = $this->weatherService->getCurrentWeatherByCoordinates($lat, $lon);
            } elseif ($location) {
                $weather = $this->weatherService->getCurrentWeather($location);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Either location, lat/lon coordinates, or farm_id is required'
                ], 400);
            }

            // Store the weather snapshot
            $this->weatherService->storeWeatherSnapshot($weather, [
                'location' => $weather['name'] ?? $location,
                'lat' => $lat,
                'lon' => $lon,
                'farm_id' => $farmId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Weather data updated successfully',
                'weather' => $weather
            ]);
        } catch (\Throwable $e) {
            \Log::error('Manual weather update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update weather data: ' . $e->getMessage()
            ], 500);
        }
    }

}
