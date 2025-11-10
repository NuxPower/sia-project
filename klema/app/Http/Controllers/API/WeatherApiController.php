<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\WeatherData;
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

        if ($location === null && ($lat === null || $lon === null)) {
            $location = 'Butuan, Caraga, PH';
        }
        $days = (int) $request->get('days', 30);
        $days = max(1, min($days, 30));

        try {
            $history = $this->getStoredWeatherHistory($location, $lat, $lon, $days);
            $historyByDate = collect($history)->keyBy('date');

            $today = Carbon::today();

            for ($i = 1; $i <= $days; $i++) {
                $dateObj = $today->copy()->subDays($i);
                $date = $dateObj->format('Y-m-d');

                if ($historyByDate->has($date)) {
                    continue;
                }

                $entry = null;
                $fetchedExternally = false;
                $targetLat = $lat !== null ? (float) $lat : null;
                $targetLon = $lon !== null ? (float) $lon : null;

                if ($targetLat !== null && $targetLon !== null) {
                    $entry = $this->weatherService->findStoredHistoricalByCoordinates($targetLat, $targetLon, $dateObj);

                    if ($entry === null) {
                        $entry = $this->weatherService->fetchHistoricalSnapshotByCoordinates($targetLat, $targetLon, $dateObj);
                        $fetchedExternally = $entry !== null;
                    }
                } elseif ($location) {
                    $entry = $this->weatherService->findStoredHistoricalByLocation($location, $dateObj);

                    if ($entry === null) {
                        $geocoded = $this->weatherService->geocodeLocation($location);
                        if ($geocoded) {
                            $targetLat = $geocoded['lat'];
                            $targetLon = $geocoded['lon'];
                            $entry = $this->weatherService->fetchHistoricalSnapshotByCoordinates($targetLat, $targetLon, $dateObj);
                            $fetchedExternally = $entry !== null;
                            if ($entry && !isset($entry['name'])) {
                                $entry['name'] = $geocoded['name'] ?? $location;
                            }
                        }
                    }
                }

                if ($entry === null) {
                    continue;
                }

                $normalized = $this->normalizeHistoricalEntry($date, $entry);
                $historyByDate->put($date, $normalized);

                if ($fetchedExternally) {
                    $this->weatherService->storeWeatherSnapshot($entry, [
                        'location' => data_get($entry, 'name') ?? $location,
                        'lat' => $targetLat,
                        'lon' => $targetLon,
                        'recorded_at' => $dateObj->copy()->setHour(12),
                    ]);
                }
            }

            $sortedHistory = $historyByDate->sortKeys()->values()->all();

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

            $historical = null;
            $fetchedExternally = false;
            $targetLat = $lat !== null ? (float) $lat : null;
            $targetLon = $lon !== null ? (float) $lon : null;

            if ($targetLat !== null && $targetLon !== null) {
                $historical = $this->weatherService->findStoredHistoricalByCoordinates($targetLat, $targetLon, $dateObj);
                if ($historical === null) {
                    $historical = $this->weatherService->fetchHistoricalSnapshotByCoordinates($targetLat, $targetLon, $dateObj);
                    $fetchedExternally = $historical !== null;
                }
            } elseif ($location) {
                $historical = $this->weatherService->findStoredHistoricalByLocation($location, $dateObj);

                if ($historical === null) {
                    $geocoded = $this->weatherService->geocodeLocation($location);
                    if ($geocoded) {
                        $targetLat = $geocoded['lat'];
                        $targetLon = $geocoded['lon'];
                        $historical = $this->weatherService->fetchHistoricalSnapshotByCoordinates($targetLat, $targetLon, $dateObj);
                        $fetchedExternally = $historical !== null;
                        if ($historical && !isset($historical['name'])) {
                            $historical['name'] = $geocoded['name'] ?? $location;
                        }
                    }
                }
            }

            if ($historical === null) {
                return response()->json([
                    'message' => 'Historical weather data is unavailable for the requested location.',
                ], 404);
            }

            if ($fetchedExternally) {
                $this->weatherService->storeWeatherSnapshot($historical, [
                    'location' => data_get($historical, 'name') ?? $location,
                    'lat' => $targetLat,
                    'lon' => $targetLon,
                    'recorded_at' => $dateObj->copy()->setHour(12),
                ]);
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
        $temperature = $data['main']['temp'] ?? null;
        $tempMax = $data['main']['temp_max'] ?? ($temperature !== null ? $temperature + 2 : null);
        $tempMin = $data['main']['temp_min'] ?? ($temperature !== null ? $temperature - 3 : null);

        $conditionData = $data['weather'][0] ?? [];

        return [
            'date' => $date,
            'temp_max' => $this->normalizeTemperature($tempMax),
            'temp_min' => $this->normalizeTemperature($tempMin),
            'condition' => $conditionData['main'] ?? ($conditionData['description'] ?? 'Unknown'),
            'icon' => $conditionData['icon'] ?? '01d'
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

    private function getStoredWeatherHistory(?string $location, ?string $lat, ?string $lon, int $days): array
    {
        $startDate = Carbon::today()->subDays($days)->startOfDay();

        $query = WeatherData::query()
            ->where('recorded_at', '>=', $startDate)
            ->orderBy('recorded_at');

        $locationKey = $this->normalizeLocation($location);
        $lat = $lat !== null ? round((float) $lat, 6) : null;
        $lon = $lon !== null ? round((float) $lon, 6) : null;

        if ($locationKey !== null) {
            $query->where('location_name', $locationKey);
        } elseif ($lat !== null && $lon !== null) {
            $query->where('latitude', $lat)->where('longitude', $lon);
        } else {
            return [];
        }

        $records = $query->get();
        $history = [];

        foreach ($records as $record) {
            if (!$record->recorded_at) {
                continue;
            }

            $dateKey = $record->recorded_at->toDateString();

            if (!isset($history[$dateKey])) {
                $history[$dateKey] = [
                    'date' => $dateKey,
                    'temperatures' => [],
                    'condition' => $record->condition,
                    'icon' => $record->condition_icon,
                    'latest_recorded_at' => $record->recorded_at,
                ];
            }

            if ($record->temperature !== null) {
                $history[$dateKey]['temperatures'][] = (float) $record->temperature;
            }

            if ($record->recorded_at->gt($history[$dateKey]['latest_recorded_at'])) {
                $history[$dateKey]['condition'] = $record->condition;
                $history[$dateKey]['icon'] = $record->condition_icon;
                $history[$dateKey]['latest_recorded_at'] = $record->recorded_at;
            }
        }

        ksort($history);

        $history = array_map(function ($entry) {
            $temps = $entry['temperatures'];

            return [
                'date' => $entry['date'],
                'temp_max' => !empty($temps) ? (int) round(max($temps)) : null,
                'temp_min' => !empty($temps) ? (int) round(min($temps)) : null,
                'condition' => $entry['condition'] ?? 'Unknown',
                'icon' => $entry['icon'] ?? '01d',
            ];
        }, $history);

        $history = array_values($history);

        if (count($history) > $days) {
            return array_slice($history, -$days);
        }

        return $history;
    }

    private function normalizeLocation(?string $location): ?string
    {
        if ($location === null) {
            return null;
        }

        $trimmed = trim($location);

        if ($trimmed === '') {
            return null;
        }

        return Str::lower($trimmed);
    }

}
