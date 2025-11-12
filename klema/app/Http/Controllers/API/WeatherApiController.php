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
            $resolvedCoordinates = null;
            $targetLat = $lat !== null ? (float) $lat : null;
            $targetLon = $lon !== null ? (float) $lon : null;
            $datesToFetch = [];

            for ($i = 1; $i <= $days; $i++) {
                $dateObj = $today->copy()->subDays($i);
                $date = $dateObj->format('Y-m-d');

                $existingNormalized = $historyByDate->get($date);
                if ($existingNormalized && $this->historyEntryHasDetail($existingNormalized)) {
                    continue;
                }

                $entry = null;
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
                    continue;
                }

                if ($location && !$existingNormalized) {
                    $entry = $this->weatherService->findStoredHistoricalByLocation($location, $dateObj);
                    if ($entry && !isset($entry['name'])) {
                        $entry['name'] = $resolvedCoordinates['name'] ?? $location;
                    }
                }

                if ($entry !== null && $this->historyEntryHasDetail($entry)) {
                    $normalized = $this->normalizeHistoricalEntry($date, $entry);
                    $historyByDate->put($date, $normalized);
                    continue;
                }

                $datesToFetch[$date] = $dateObj;
            }

            if (!empty($datesToFetch) && $targetLat !== null && $targetLon !== null) {
                $startDate = null;
                $endDate = null;
                foreach ($datesToFetch as $dateObj) {
                    if ($startDate === null || $dateObj->lt($startDate)) {
                        $startDate = $dateObj->copy();
                    }
                    if ($endDate === null || $dateObj->gt($endDate)) {
                        $endDate = $dateObj->copy();
                    }
                }

                if ($startDate !== null && $endDate !== null) {
                    $series = $this->weatherService->fetchHistoricalSeriesByCoordinates($targetLat, $targetLon, $startDate, $endDate);

                    foreach ($datesToFetch as $dateKey => $dateObj) {
                        $entry = $series[$dateKey] ?? null;
                        if ($entry === null) {
                            continue;
                        }

                        if ($location && !isset($entry['name'])) {
                            $entry['name'] = $resolvedCoordinates['name'] ?? $location;
                        }

                        $normalized = $this->normalizeHistoricalEntry($dateKey, $entry);
                        $historyByDate->put($dateKey, $normalized);

                        $this->weatherService->storeWeatherSnapshot($entry, [
                            'location' => data_get($entry, 'name') ?? $location,
                            'lat' => $targetLat,
                            'lon' => $targetLon,
                            'recorded_at' => $dateObj->copy()->setHour(12),
                        ]);
                    }
                }
            }

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
            if ($historical === null || !$this->historyEntryHasDetail($historical)) {
                $series = $this->weatherService->fetchHistoricalSeriesByCoordinates($targetLat, $targetLon, $dateObj->copy(), $dateObj->copy());
                $historical = $series[$date] ?? $historical;
                $fetchedExternally = isset($series[$date]);
            }
            } elseif ($location) {
                $historical = $this->weatherService->findStoredHistoricalByLocation($location, $dateObj);

            if ($historical === null || !$this->historyEntryHasDetail($historical)) {
                    $geocoded = $this->weatherService->geocodeLocation($location);
                    if ($geocoded) {
                        $targetLat = $geocoded['lat'];
                        $targetLon = $geocoded['lon'];
                    $series = $this->weatherService->fetchHistoricalSeriesByCoordinates($targetLat, $targetLon, $dateObj->copy(), $dateObj->copy());
                    $fetchedExternally = isset($series[$date]);
                    $historical = $series[$date] ?? $historical;
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
