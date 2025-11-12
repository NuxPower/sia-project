<?php

namespace App\Services;

use App\Models\WeatherData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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

    public function findStoredHistoricalByCoordinates(float $lat, float $lon, Carbon $date): ?array
    {
        $lat = round($lat, 3);
        $lon = round($lon, 3);

        $record = WeatherData::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$lat - 0.1, $lat + 0.1])
            ->whereBetween('longitude', [$lon - 0.1, $lon + 0.1])
            ->whereDate('recorded_at', $date->toDateString())
            ->orderByDesc('recorded_at')
            ->first();

        return $record ? $this->createSnapshotFromRecord($record) : null;
    }

    public function findStoredHistoricalByLocation(string $location, Carbon $date): ?array
    {
        $locationName = Str::of($location ?? '')->trim()->lower();
        if ($locationName->isEmpty()) {
            return null;
        }

        $record = WeatherData::query()
            ->whereNotNull('location_name')
            ->where('location_name', $locationName)
            ->whereDate('recorded_at', $date->toDateString())
            ->orderByDesc('recorded_at')
            ->first();

        return $record ? $this->createSnapshotFromRecord($record) : null;
    }

    public function fetchHistoricalSnapshotByCoordinates(float $lat, float $lon, Carbon $date): ?array
    {
        $series = $this->fetchHistoricalSeriesByCoordinates($lat, $lon, $date->copy(), $date->copy());

        return $series[$date->format('Y-m-d')] ?? null;
    }

    public function fetchHistoricalSeriesByCoordinates(float $lat, float $lon, Carbon $startDate, Carbon $endDate): array
    {
        $start = $startDate->copy()->format('Y-m-d');
        $end = $endDate->copy()->format('Y-m-d');

        try {
            $response = Http::withOptions([
                'timeout' => 10,
            ])->get('https://archive-api.open-meteo.com/v1/archive', [
                'latitude' => $lat,
                'longitude' => $lon,
                'start_date' => $start,
                'end_date' => $end,
                'daily' => implode(',', [
                    'temperature_2m_max',
                    'temperature_2m_min',
                    'weathercode',
                    'windspeed_10m_max',
                    'precipitation_sum',
                    'sunrise',
                    'sunset',
                    'precipitation_probability_mean',
                ]),
                'hourly' => implode(',', [
                    'temperature_2m',
                    'apparent_temperature',
                    'relativehumidity_2m',
                    'precipitation',
                    'rain',
                    'showers',
                    'snowfall',
                    'cloudcover',
                    'wind_speed_10m',
                    'wind_direction_10m',
                    'wind_gusts_10m',
                    'precipitation_probability',
                ]),
                'timezone' => 'auto',
            ]);

            if (!$response->successful()) {
                Log::warning('Historical weather range fetch failed', [
                    'lat' => $lat,
                    'lon' => $lon,
                    'start' => $start,
                    'end' => $end,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $payload = $response->json();
            return $this->transformHistoricalSeriesPayload($payload, $lat, $lon);
        } catch (\Throwable $e) {
            Log::error('Historical weather range fetch exception', [
                'message' => $e->getMessage(),
                'lat' => $lat,
                'lon' => $lon,
                'start' => $start,
                'end' => $end,
            ]);

            return [];
        }
    }

    public function geocodeLocation(string $location): ?array
    {
        $normalized = Str::of($location ?? '')->trim();
        if ($normalized->isEmpty()) {
            return null;
        }

        $cacheKey = 'geocode_' . Str::lower($normalized);

        return Cache::remember($cacheKey, 86400, function () use ($normalized) {
            $response = Http::weather()->get('https://api.openweathermap.org/geo/1.0/direct', [
                'q' => $normalized,
                'limit' => 1,
                'appid' => $this->apiKey,
            ]);

            if (!$response->successful()) {
                Log::warning('Geocoding request failed', [
                    'location' => $normalized,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $results = $response->json();
            $first = $results[0] ?? null;

            if (!$first || !isset($first['lat'], $first['lon'])) {
                return null;
            }

            return [
                'lat' => (float) $first['lat'],
                'lon' => (float) $first['lon'],
                'name' => $first['name'] ?? (string) $normalized,
                'country' => $first['country'] ?? null,
            ];
        });
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

    private function transformHistoricalSeriesPayload(array $payload, float $lat, float $lon): array
    {
        $timezoneName = data_get($payload, 'timezone', 'UTC');
        $utcOffsetSeconds = (int) data_get($payload, 'utc_offset_seconds', 0);

        $hourlyBuckets = $this->bucketHourlyEntries($payload, $timezoneName, $utcOffsetSeconds);

        $dates = data_get($payload, 'daily.time', []);
        $series = [];

        foreach ($dates as $index => $dateString) {
            $entry = $this->buildHistoricalEntryFromPayload(
                $payload,
                $index,
                $dateString,
                $lat,
                $lon,
                $timezoneName,
                $utcOffsetSeconds,
                $hourlyBuckets[$dateString] ?? []
            );

            if ($entry !== null) {
                $series[$dateString] = $entry;
            }
        }

        return $series;
    }

    private function bucketHourlyEntries(array $payload, string $timezoneName, int $utcOffsetSeconds): array
    {
        $times = data_get($payload, 'hourly.time', []);
        $buckets = [];

        foreach ($times as $index => $timeString) {
            $dateKey = substr((string) $timeString, 0, 10);
            if (!isset($buckets[$dateKey])) {
                $buckets[$dateKey] = [];
            }

            $hourTimestamp = Carbon::parse($timeString, $timezoneName)->setTimezone('UTC')->timestamp;
            $temperature = data_get($payload, "hourly.temperature_2m.{$index}");
            $apparentTemperature = data_get($payload, "hourly.apparent_temperature.{$index}");
            $humidity = data_get($payload, "hourly.relativehumidity_2m.{$index}");
            $cloudCover = data_get($payload, "hourly.cloudcover.{$index}");
            $windSpeedKmh = data_get($payload, "hourly.wind_speed_10m.{$index}");
            $windGustKmh = data_get($payload, "hourly.wind_gusts_10m.{$index}");
            $windDirection = data_get($payload, "hourly.wind_direction_10m.{$index}");
            $precipitation = data_get($payload, "hourly.precipitation.{$index}");
            $precipProbabilityHourly = data_get($payload, "hourly.precipitation_probability.{$index}");

            $windSpeedMs = $windSpeedKmh !== null ? ((float) $windSpeedKmh) / 3.6 : null;
            $windGustMs = $windGustKmh !== null ? ((float) $windGustKmh) / 3.6 : null;

            $buckets[$dateKey][] = [
                'dt' => $hourTimestamp,
                'timestamp' => $hourTimestamp,
                'time' => $hourTimestamp,
                'temp' => $temperature,
                'temperature' => $temperature,
                'feels_like' => $apparentTemperature,
                'humidity' => $humidity,
                'clouds' => $cloudCover,
                'cloud_cover' => $cloudCover,
                'wind_speed' => $windSpeedMs,
                'wind_gust' => $windGustMs,
                'wind_deg' => $windDirection,
                'precipitation' => $precipitation,
                'precipitationProbability' => $precipProbabilityHourly,
                'precip_probability' => $precipProbabilityHourly,
                'rain' => [
                    'value' => $precipitation,
                ],
                'timezone_offset' => $utcOffsetSeconds,
            ];
        }

        return $buckets;
    }

    private function buildHistoricalEntryFromPayload(
        array $payload,
        int $index,
        string $dateString,
        float $lat,
        float $lon,
        string $timezoneName,
        int $utcOffsetSeconds,
        array $hourlyData
    ): ?array {
        $tempMax = data_get($payload, "daily.temperature_2m_max.{$index}");
        $tempMin = data_get($payload, "daily.temperature_2m_min.{$index}");

        if ($tempMax === null && $tempMin === null) {
            return null;
        }

        $weatherCode = (int) data_get($payload, "daily.weathercode.{$index}", -1);
        $windSpeed = data_get($payload, "daily.windspeed_10m_max.{$index}");
        $precipitationSum = data_get($payload, "daily.precipitation_sum.{$index}");
        $precipProbability = data_get($payload, "daily.precipitation_probability_mean.{$index}");

        $averageTemp = null;
        if ($tempMax !== null && $tempMin !== null) {
            $averageTemp = ($tempMax + $tempMin) / 2;
        } elseif ($tempMax !== null) {
            $averageTemp = $tempMax;
        } elseif ($tempMin !== null) {
            $averageTemp = $tempMin;
        }

        $condition = $this->mapWeatherCodeToCondition($weatherCode);
        $timestamp = Carbon::parse($dateString, 'UTC')->setTime(12, 0)->timestamp;

        $sunriseIso = data_get($payload, "daily.sunrise.{$index}");
        $sunsetIso = data_get($payload, "daily.sunset.{$index}");
        $sunriseTimestamp = $sunriseIso
            ? Carbon::parse($sunriseIso, $timezoneName)->setTimezone('UTC')->timestamp
            : null;
        $sunsetTimestamp = $sunsetIso
            ? Carbon::parse($sunsetIso, $timezoneName)->setTimezone('UTC')->timestamp
            : null;

        return [
            'date' => $dateString,
            'dt' => $timestamp,
            'timestamp' => $timestamp,
            'coord' => [
                'lat' => $lat,
                'lon' => $lon,
            ],
            'main' => [
                'temp' => $averageTemp,
                'temp_min' => $tempMin,
                'temp_max' => $tempMax,
                'humidity' => null,
                'pressure' => null,
            ],
            'weather' => [[
                'main' => $condition['main'],
                'description' => $condition['description'],
                'icon' => $condition['icon'],
            ]],
            'wind' => [
                'speed' => $windSpeed,
            ],
            'sunrise' => $sunriseTimestamp,
            'sunset' => $sunsetTimestamp,
            'timezone_offset' => $utcOffsetSeconds,
            'precipitation_sum' => $precipitationSum,
            'precip_probability' => $precipProbability,
            'hourly' => $hourlyData,
        ];
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

    private function createSnapshotFromRecord(WeatherData $record): array
    {
        $timestamp = Carbon::parse($record->recorded_at, 'UTC')->timestamp;

        return [
            'date' => Carbon::createFromTimestampUTC($timestamp)->toDateString(),
            'dt' => $timestamp,
            'timestamp' => $timestamp,
            'coord' => [
                'lat' => $record->latitude,
                'lon' => $record->longitude,
            ],
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
            'name' => $record->location_name ? Str::title($record->location_name) : null,
        ];
    }

    private function mapWeatherCodeToCondition(int $code): array
    {
        $mapping = [
            0 => ['Clear', 'Clear sky', '01d'],
            1 => ['Clouds', 'Mainly clear', '02d'],
            2 => ['Clouds', 'Partly cloudy', '03d'],
            3 => ['Clouds', 'Overcast', '04d'],
            45 => ['Mist', 'Fog', '50d'],
            48 => ['Mist', 'Depositing rime fog', '50d'],
            51 => ['Drizzle', 'Light drizzle', '09d'],
            53 => ['Drizzle', 'Moderate drizzle', '09d'],
            55 => ['Drizzle', 'Dense drizzle', '09d'],
            56 => ['Rain', 'Light freezing drizzle', '13d'],
            57 => ['Rain', 'Dense freezing drizzle', '13d'],
            61 => ['Rain', 'Slight rain', '10d'],
            63 => ['Rain', 'Moderate rain', '10d'],
            65 => ['Rain', 'Heavy rain', '10d'],
            66 => ['Rain', 'Light freezing rain', '13d'],
            67 => ['Rain', 'Heavy freezing rain', '13d'],
            71 => ['Snow', 'Slight snow fall', '13d'],
            73 => ['Snow', 'Moderate snow fall', '13d'],
            75 => ['Snow', 'Heavy snow fall', '13d'],
            77 => ['Snow', 'Snow grains', '13d'],
            80 => ['Rain', 'Slight rain showers', '09d'],
            81 => ['Rain', 'Moderate rain showers', '09d'],
            82 => ['Rain', 'Violent rain showers', '09d'],
            85 => ['Snow', 'Slight snow showers', '13d'],
            86 => ['Snow', 'Heavy snow showers', '13d'],
            95 => ['Thunderstorm', 'Thunderstorm', '11d'],
            96 => ['Thunderstorm', 'Thunderstorm with slight hail', '11d'],
            99 => ['Thunderstorm', 'Thunderstorm with heavy hail', '11d'],
        ];

        $entry = $mapping[$code] ?? null;

        if ($entry === null) {
            return [
                'main' => 'Unknown',
                'description' => 'Unknown',
                'icon' => '01d',
            ];
        }

        return [
            'main' => $entry[0],
            'description' => $entry[1],
            'icon' => $entry[2],
        ];
    }
}