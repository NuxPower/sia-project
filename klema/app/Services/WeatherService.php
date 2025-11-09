<?php

namespace App\Services;

use App\Models\WeatherData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class WeatherService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.openweathermap.org/data/2.5';

    public function __construct()
    {
        $this->apiKey = config('services.openweather.api_key');
        
        if (empty($this->apiKey) || $this->apiKey === 'your-openweather-api-key-here') {
            throw new \RuntimeException('OpenWeather API key is not configured. Set services.openweather.api_key.');
        }
    }

    public function getCurrentWeather($location)
    {
        $cacheKey = "weather_current_{$location}";
        
        return Cache::remember($cacheKey, 600, function () use ($location) {
            $response = Http::weather()->get("{$this->baseUrl}/weather", [
                'q' => $location,
                'appid' => $this->apiKey,
                'units' => 'metric'
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException('Failed to fetch current weather: '.$response->status());
            }

            return $response->json();
        });
    }

    public function getCurrentWeatherByCoordinates($lat, $lon)
    {
        $cacheKey = "weather_current_coords_{$lat}_{$lon}";
        
        return Cache::remember($cacheKey, 600, function () use ($lat, $lon) {
            $response = Http::weather()->get("{$this->baseUrl}/weather", [
                'lat' => $lat,
                'lon' => $lon,
                'appid' => $this->apiKey,
                'units' => 'metric'
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException('Failed to fetch current weather by coordinates: '.$response->status());
            }

            return $response->json();
        });
    }

    public function getForecast($location, $days = 7)
    {
        $cacheKey = "weather_forecast_{$location}_{$days}";
        
        return Cache::remember($cacheKey, 3600, function () use ($location, $days) {
            $response = Http::weather()->get("{$this->baseUrl}/forecast", [
                'q' => $location,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'cnt' => $days * 8 // 8 forecasts per day (3-hour intervals)
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException('Failed to fetch forecast: '.$response->status());
            }

            $data = $response->json();
            return $this->processForecastData($data, $days);
        });
    }

    public function getForecastByCoordinates($lat, $lon, $days = 7)
    {
        $cacheKey = "weather_forecast_coords_{$lat}_{$lon}_{$days}";
        
        return Cache::remember($cacheKey, 3600, function () use ($lat, $lon, $days) {
            $response = Http::weather()->get("{$this->baseUrl}/forecast", [
                'lat' => $lat,
                'lon' => $lon,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'cnt' => $days * 8 // 8 forecasts per day (3-hour intervals)
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException('Failed to fetch forecast by coordinates: '.$response->status());
            }

            $data = $response->json();
            return $this->processForecastData($data, $days);
        });
    }

    public function getHistoricalWeather($location, $days)
    {
        $days = max(1, min((int) $days, 7));

        $locationName = Str::of($location ?? '')->trim()->lower();
        if ($locationName->isEmpty()) {
            return [];
        }

        $records = WeatherData::query()
            ->whereNotNull('location_name')
            ->where('location_name', $locationName)
            ->orderByDesc('recorded_at')
            ->limit($days)
            ->get();

        if ($records->isEmpty()) {
            return [];
        }

        return $this->formatHistoricalCollection($records);
    }

    public function getHistoricalWeatherByCoordinates($lat, $lon, $days)
    {
        $days = max(1, min((int) $days, 7));

        if ($lat === null || $lon === null) {
            return [];
        }

        $lat = round((float) $lat, 3);
        $lon = round((float) $lon, 3);

        $records = WeatherData::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$lat - 0.1, $lat + 0.1])
            ->whereBetween('longitude', [$lon - 0.1, $lon + 0.1])
            ->orderByDesc('recorded_at')
            ->limit($days)
            ->get();

        if ($records->isEmpty()) {
            return [];
        }

        return $this->formatHistoricalCollection($records);
    }

    public function storeWeatherSnapshot(array $weather, array $context = []): ?WeatherData
    {
        if (empty($weather['main'])) {
            return null;
        }

        $recordedAt = $this->resolveRecordedAt($weather, $context['recorded_at'] ?? null);
        $farmId = $context['farm_id'] ?? null;

        $location = $context['location'] ?? $context['location_name'] ?? ($weather['name'] ?? null);
        $locationName = $location ? Str::lower(trim($location)) : null;
        if ($locationName === '') {
            $locationName = null;
        }

        $lat = $context['lat'] ?? data_get($weather, 'coord.lat');
        $lon = $context['lon'] ?? data_get($weather, 'coord.lon');

        if (!$farmId && $locationName === null && ($lat === null || $lon === null)) {
            // Without a farm or identifiable location, skip persistence
            return null;
        }

        $lat = $lat !== null ? round((float) $lat, 6) : null;
        $lon = $lon !== null ? round((float) $lon, 6) : null;

        $query = WeatherData::query()->where('recorded_at', $recordedAt);

        if ($farmId) {
            $query->where('farm_id', $farmId);
        } else {
            $query->whereNull('farm_id');

            if ($locationName !== null) {
                $query->where('location_name', $locationName);
            } else {
                $query->whereNull('location_name');
            }

            if ($lat !== null && $lon !== null) {
                $query->where('latitude', $lat)->where('longitude', $lon);
            } else {
                $query->whereNull('latitude')->whereNull('longitude');
            }
        }

        $payload = [
            'farm_id' => $farmId,
            'location_name' => $locationName,
            'latitude' => $lat,
            'longitude' => $lon,
            'temperature' => data_get($weather, 'main.temp'),
            'humidity' => data_get($weather, 'main.humidity'),
            'rainfall' => data_get($weather, 'rain.1h', 0),
            'wind_speed' => data_get($weather, 'wind.speed'),
            'condition' => data_get($weather, 'weather.0.main'),
            'condition_icon' => data_get($weather, 'weather.0.icon'),
            'recorded_at' => $recordedAt,
        ];

        if ($existing = $query->first()) {
            $existing->fill($payload)->save();
            return $existing;
        }

        return WeatherData::create($payload);
    }

    private function processForecastData($data, int $days = 7)
    {
        $dailyForecasts = [];
        $currentDate = null;
        $dayData = [];
        $lat = data_get($data, 'city.coord.lat');
        $lon = data_get($data, 'city.coord.lon');
        $timezoneOffset = data_get($data, 'city.timezone', 0);
        $limit = max(1, min($days, 16));

        foreach ($data['list'] as $forecast) {
            $timestamp = data_get($forecast, 'dt');
            $date = Carbon::createFromTimestamp($timestamp, 'UTC')
                ->addSeconds($timezoneOffset)
                ->format('Y-m-d');
            
            if ($currentDate !== $date) {
                if (!empty($dayData)) {
                    $dailyForecasts[] = $this->aggregateDayData($dayData, $currentDate, $lat, $lon, (int) $timezoneOffset);
                }
                $currentDate = $date;
                $dayData = [];
            }
            
            $dayData[] = $forecast;
        }

        if (!empty($dayData)) {
            $dailyForecasts[] = $this->aggregateDayData($dayData, $currentDate, $lat, $lon, (int) $timezoneOffset);
        }

        return array_slice($dailyForecasts, 0, $limit);
    }

    private function aggregateDayData(array $dayData, ?string $date, ?float $lat = null, ?float $lon = null, int $timezoneOffset = 0): array
    {
        if (!$date) {
            $date = Carbon::now()->format('Y-m-d');
        }

        $temps = array_filter(array_map(function ($entry) {
            $value = data_get($entry, 'main.temp');
            return $value !== null ? (float) $value : null;
        }, $dayData), fn ($value) => $value !== null);
        $tempMax = !empty($temps) ? round(max($temps), 1) : null;
        $tempMin = !empty($temps) ? round(min($temps), 1) : null;

        $conditions = array_column(array_column($dayData, 'weather'), 0);
        $conditionEntry = $this->resolveDominantCondition($conditions);

        $popValues = array_filter(array_map(fn ($entry) => data_get($entry, 'pop'), $dayData), fn ($value) => $value !== null);
        $averagePop = !empty($popValues) ? array_sum($popValues) / count($popValues) : null;

        $sunrise = $this->computeSolarEvent($date, $lat, $lon, $timezoneOffset, true);
        $sunset = $this->computeSolarEvent($date, $lat, $lon, $timezoneOffset, false);

        $hourly = array_map(function ($entry) use ($timezoneOffset) {
            $entry['timezone_offset'] = $timezoneOffset;
            return $entry;
        }, array_values($dayData));

        return [
            'date' => $date,
            'day' => Carbon::parse($date)->format('l'),
            'temp_max' => $tempMax,
            'temp_min' => $tempMin,
            'condition' => $conditionEntry['main'] ?? null,
            'icon' => $conditionEntry['icon'] ?? null,
            'description' => $conditionEntry['description'] ?? null,
            'precip_probability' => $averagePop,
            'sunrise' => $sunrise,
            'sunset' => $sunset,
            'hourly' => $hourly,
            'timezone_offset' => $timezoneOffset,
        ];
    }

    private function resolveDominantCondition(array $conditions): array
    {
        if (empty($conditions)) {
            return ['main' => null, 'description' => null, 'icon' => null];
        }

        $priority = ['Thunderstorm', 'Rain', 'Drizzle', 'Snow', 'Clear', 'Clouds', 'Mist', 'Fog', 'Haze'];

        usort($conditions, function ($a, $b) use ($priority) {
            $indexA = array_search($a['main'] ?? '', $priority);
            $indexB = array_search($b['main'] ?? '', $priority);

            $indexA = $indexA === false ? PHP_INT_MAX : $indexA;
            $indexB = $indexB === false ? PHP_INT_MAX : $indexB;

            return $indexA <=> $indexB;
        });

        return $conditions[0] ?? ['main' => null, 'description' => null, 'icon' => null];
    }
 
    private function resolveRecordedAt(array $weather, $provided = null): Carbon
    {
        if ($provided) {
            return Carbon::parse($provided);
        }

        if (isset($weather['dt']) && is_numeric($weather['dt'])) {
            return Carbon::createFromTimestampUTC((int) $weather['dt']);
        }

        if (isset($weather['timestamp']) && is_numeric($weather['timestamp'])) {
            return Carbon::createFromTimestampUTC((int) $weather['timestamp']);
        }

        return Carbon::now('UTC');
    }

    private function computeSolarEvent(string $date, ?float $lat, ?float $lon, int $timezoneOffset, bool $sunrise): ?int
    {
        if ($lat === null || $lon === null) {
            return null;
        }

        $zenith = 90 + (50 / 60);
        $offsetHours = $timezoneOffset / 3600;
        $timestamp = strtotime($date . ' 12:00:00 UTC');

        if ($timestamp === false) {
            return null;
        }

        $result = $sunrise
            ? date_sunrise($timestamp, SUNFUNCS_RET_TIMESTAMP, $lat, $lon, $zenith, $offsetHours)
            : date_sunset($timestamp, SUNFUNCS_RET_TIMESTAMP, $lat, $lon, $zenith, $offsetHours);

        if ($result === false) {
            return null;
        }

        return $result;
    }

    private function formatHistoricalCollection(Collection $records): array
    {
        return $records
            ->sortBy('recorded_at')
            ->values()
            ->map(function (WeatherData $record) {
                $timestamp = Carbon::parse($record->recorded_at, 'UTC')->timestamp;

                return [
                    'date' => Carbon::createFromTimestampUTC($timestamp)->toDateString(),
                    'dt' => $timestamp,
                    'main' => [
                        'temp' => $record->temperature,
                        'temp_min' => $record->temperature,
                        'temp_max' => $record->temperature,
                        'humidity' => $record->humidity,
                        'pressure' => $record->pressure,
                    ],
                    'weather' => [[
                        'main' => $record->condition,
                        'description' => $record->condition,
                        'icon' => $record->condition_icon,
                    ]],
                    'wind' => [
                        'speed' => $record->wind_speed,
                    ],
                    'rain' => [
                        '1h' => $record->rainfall,
                    ],
                ];
            })
            ->all();
    }
}