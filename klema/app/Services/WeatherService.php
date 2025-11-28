<?php

namespace App\Services;

use App\Models\WeatherData;
use App\Models\Forecast;
use App\Models\Farm;
use App\Jobs\StoreCurrentWeatherJob;
use App\Jobs\StoreForecastJob;
use App\Jobs\StoreHistoricalWeatherJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
        $locationName = Str::of($location ?? '')->trim()->lower();
        if ($locationName->isEmpty()) {
            throw new \RuntimeException('Location is required');
        }

        // Check database first (data from last 30 minutes is considered fresh)
        $storedWeather = $this->getStoredCurrentWeatherByLocation($locationName);
        if ($storedWeather !== null) {
            return $storedWeather;
        }

        // Fallback to API
        $cacheKey = "weather_current_{$location}";
        
        return Cache::remember($cacheKey, 600, function () use ($location, $locationName) {
            $response = Http::weather()->get("{$this->baseUrl}/weather", [
                'q' => $location,
                'appid' => $this->apiKey,
                'units' => 'metric'
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException('Failed to fetch current weather: '.$response->status());
            }

            $weather = $response->json();
            
            // Only store weather data for farm locations, not generic locations
            // For location-based queries, we skip storage to optimize performance

            return $weather;
        });
    }

    public function getCurrentWeatherByCoordinates($lat, $lon)
    {
        // Check database first (data from last 30 minutes is considered fresh)
        $storedWeather = $this->getStoredCurrentWeatherByCoordinates($lat, $lon);
        if ($storedWeather !== null) {
            return $storedWeather;
        }

        // Fallback to API
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

            $weather = $response->json();
            
            // Only store weather data for farm locations
            $farm = $this->findFarmByCoordinates($lat, $lon);
            if ($farm) {
                // Store to database in background (non-blocking) only if farm is found
                StoreCurrentWeatherJob::dispatch($weather, [
                    'lat' => $lat,
                    'lon' => $lon,
                    'location' => $weather['name'] ?? null,
                    'farm_id' => $farm->farm_id,
                ]);
            }

            return $weather;
        });
    }

    public function getForecast($location, $days = 7)
    {
        $days = max(1, min($days, 16));
        $locationName = Str::of($location ?? '')->trim()->lower();
        
        // Check database first
        $storedForecast = $this->getStoredForecastByLocation($locationName, $days);
        if ($storedForecast !== null) {
            return $storedForecast;
        }

        // Fallback to API
        $cacheKey = "weather_forecast_{$location}_{$days}";
        
        return Cache::remember($cacheKey, 3600, function () use ($location, $locationName, $days) {
            // First, get coordinates for the location (needed for One Call API)
            $geoData = $this->geocodeLocation($location);
            
            if ($geoData && isset($geoData['lat'], $geoData['lon'])) {
                $lat = $geoData['lat'];
                $lon = $geoData['lon'];
                
                // Try One Call API 3.0 for 7+ days (supports up to 8 days)
                if ($days > 5) {
                    try {
                        $oneCallData = $this->getForecastFromOneCall($lat, $lon, $days);
                        if (!empty($oneCallData)) {
                            // Store and return One Call data
                            $farm = $this->findFarmByCoordinates($lat, $lon);
                            if ($farm) {
                                StoreForecastJob::dispatch($oneCallData, $locationName, $lat, $lon, $farm->farm_id);
                            }
                            return $oneCallData;
                        }
                    } catch (\Exception $e) {
                        \Log::warning('One Call API failed, falling back to /forecast', [
                            'location' => $location,
                            'error' => $e->getMessage(),
                        ]);
                        // Fall through to regular /forecast endpoint
                    }
                }
            }
            
            // Fallback to regular /forecast endpoint (supports up to 5 days)
            $maxForecasts = min($days * 8, 40); // Cap at 40 (API limit)
            
            $response = Http::weather()->get("{$this->baseUrl}/forecast", [
                'q' => $location,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'cnt' => $maxForecasts // 8 forecasts per day (3-hour intervals)
            ]);

            if (!$response->successful()) {
                $errorData = $response->json();
                $errorMessage = data_get($errorData, 'message', 'HTTP ' . $response->status());
                \Log::error('Weather forecast API error', [
                    'status' => $response->status(),
                    'location' => $location,
                    'error' => $errorData,
                ]);
                throw new \RuntimeException('Failed to fetch forecast: ' . $errorMessage);
            }

            $data = $response->json();
            
            // Check for API error response (even if HTTP status is 200)
            if (isset($data['cod']) && $data['cod'] != 200) {
                $errorMessage = data_get($data, 'message', 'API returned error code: ' . ($data['cod'] ?? 'unknown'));
                \Log::error('Weather forecast API returned error code', [
                    'code' => $data['cod'],
                    'location' => $location,
                    'error' => $data,
                ]);
                throw new \RuntimeException('Failed to fetch forecast: ' . $errorMessage);
            }
            
            // Validate API response structure
            if (!isset($data['list']) || !is_array($data['list'])) {
                $errorMessage = data_get($data, 'message', 'Invalid forecast data structure');
                \Log::error('Invalid forecast API response structure', [
                    'location' => $location,
                    'response' => $data,
                ]);
                throw new \RuntimeException('Failed to fetch forecast: ' . $errorMessage);
            }
            
            $processedData = $this->processForecastData($data, $days);
            
            // Only store forecast data for farm locations
            $lat = data_get($data, 'city.coord.lat');
            $lon = data_get($data, 'city.coord.lon');
            if ($lat !== null && $lon !== null) {
                $farm = $this->findFarmByCoordinates($lat, $lon);
                if ($farm) {
                    // Store to database in background (non-blocking) only if farm is found
                    StoreForecastJob::dispatch($processedData, $locationName, $lat, $lon, $farm->farm_id);
                }
            }

            return $processedData;
        });
    }

    public function getForecastByCoordinates($lat, $lon, $days = 7)
    {
        $days = max(1, min($days, 16));
        
        // Check database first
        $storedForecast = $this->getStoredForecastByCoordinates($lat, $lon, $days);
        if ($storedForecast !== null) {
            return $storedForecast;
        }

        // Fallback to API
        $cacheKey = "weather_forecast_coords_{$lat}_{$lon}_{$days}";
        
        return Cache::remember($cacheKey, 3600, function () use ($lat, $lon, $days) {
            // Try One Call API 3.0 for 7+ days (supports up to 8 days)
            if ($days > 5) {
                try {
                    $oneCallData = $this->getForecastFromOneCall($lat, $lon, $days);
                    if (!empty($oneCallData)) {
                        // Store and return One Call data
                        $farm = $this->findFarmByCoordinates($lat, $lon);
                        if ($farm) {
                            StoreForecastJob::dispatch($oneCallData, null, $lat, $lon, $farm->farm_id);
                        }
                        return $oneCallData;
                    }
                } catch (\Exception $e) {
                    \Log::warning('One Call API failed, falling back to /forecast', [
                        'lat' => $lat,
                        'lon' => $lon,
                        'error' => $e->getMessage(),
                    ]);
                    // Fall through to regular /forecast endpoint
                }
            }
            
            // Fallback to regular /forecast endpoint (supports up to 5 days)
            $maxForecasts = min($days * 8, 40); // Cap at 40 (API limit)
            
            $response = Http::weather()->get("{$this->baseUrl}/forecast", [
                'lat' => $lat,
                'lon' => $lon,
                'appid' => $this->apiKey,
                'units' => 'metric',
                'cnt' => $maxForecasts // 8 forecasts per day (3-hour intervals)
            ]);

            if (!$response->successful()) {
                $errorData = $response->json();
                $errorMessage = data_get($errorData, 'message', 'HTTP ' . $response->status());
                \Log::error('Weather forecast API error (coordinates)', [
                    'status' => $response->status(),
                    'lat' => $lat,
                    'lon' => $lon,
                    'error' => $errorData,
                ]);
                throw new \RuntimeException('Failed to fetch forecast by coordinates: ' . $errorMessage);
            }

            $data = $response->json();
            
            // Check for API error response (even if HTTP status is 200)
            if (isset($data['cod']) && $data['cod'] != 200) {
                $errorMessage = data_get($data, 'message', 'API returned error code: ' . ($data['cod'] ?? 'unknown'));
                \Log::error('Weather forecast API returned error code (coordinates)', [
                    'code' => $data['cod'],
                    'lat' => $lat,
                    'lon' => $lon,
                    'error' => $data,
                ]);
                throw new \RuntimeException('Failed to fetch forecast by coordinates: ' . $errorMessage);
            }
            
            // Validate API response structure
            if (!isset($data['list']) || !is_array($data['list'])) {
                $errorMessage = data_get($data, 'message', 'Invalid forecast data structure');
                \Log::error('Invalid forecast API response structure (coordinates)', [
                    'lat' => $lat,
                    'lon' => $lon,
                    'response' => $data,
                ]);
                throw new \RuntimeException('Failed to fetch forecast by coordinates: ' . $errorMessage);
            }
            
            $processedData = $this->processForecastData($data, $days);
            
            // Only store forecast data for farm locations
            $farm = $this->findFarmByCoordinates($lat, $lon);
            if ($farm) {
                // Store to database in background (non-blocking) only if farm is found
                StoreForecastJob::dispatch($processedData, null, $lat, $lon, $farm->farm_id);
            }

            return $processedData;
        });
    }

    public function getHistoricalWeather($location, $days)
    {
        $days = max(1, min((int) $days, 7));

        $locationName = Str::of($location ?? '')->trim()->lower();
        if ($locationName->isEmpty()) {
            return [];
        }

        // Get records from the past up to today (exclude future dates)
        // Allow today's data to be included in historical queries
        $todayUtc = Carbon::today('UTC')->startOfDay();
        $tomorrowUtc = $todayUtc->copy()->addDay();

        $records = WeatherData::query()
            ->whereNotNull('location_name')
            ->where('location_name', $locationName)
            ->where('recorded_at', '>=', $todayUtc->copy()->subDays($days))
            ->where('recorded_at', '<', $tomorrowUtc) // Include today, exclude future dates
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

        // Get records from the past up to today (exclude future dates)
        // Allow today's data to be included in historical queries
        $todayUtc = Carbon::today('UTC')->startOfDay();
        $tomorrowUtc = $todayUtc->copy()->addDay();

        // Use stricter tolerance (0.01 degrees ≈ 1km) to avoid matching nearby locations
        $tolerance = 0.01;
        $records = WeatherData::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$lat - $tolerance, $lat + $tolerance])
            ->whereBetween('longitude', [$lon - $tolerance, $lon + $tolerance])
            ->where('recorded_at', '>=', $todayUtc->copy()->subDays($days))
            ->where('recorded_at', '<', $tomorrowUtc) // Include today, exclude future dates
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

        // Ensure date is in UTC and at start of day for consistent comparison
        $dateUtc = $date->copy()->setTimezone('UTC')->startOfDay();
        $dateEndUtc = $dateUtc->copy()->endOfDay();

        // Use stricter tolerance (0.01 degrees ≈ 1km) to avoid matching nearby locations
        $tolerance = 0.01;
        $record = WeatherData::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$lat - $tolerance, $lat + $tolerance])
            ->whereBetween('longitude', [$lon - $tolerance, $lon + $tolerance])
            ->whereBetween('recorded_at', [$dateUtc, $dateEndUtc])
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

        // Ensure date is in UTC and at start of day for consistent comparison
        $dateUtc = $date->copy()->setTimezone('UTC')->startOfDay();
        $dateEndUtc = $dateUtc->copy()->endOfDay();

        $record = WeatherData::query()
            ->whereNotNull('location_name')
            ->where('location_name', $locationName)
            ->whereBetween('recorded_at', [$dateUtc, $dateEndUtc])
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

        // Cache historical data for 24 hours (historical data doesn't change)
        // Use coordinates rounded to 3 decimals for cache key (about 100m precision)
        $latKey = round($lat, 3);
        $lonKey = round($lon, 3);
        $cacheKey = "historical_weather_{$latKey}_{$lonKey}_{$start}_{$end}";

        $series = Cache::remember($cacheKey, 86400, function () use ($lat, $lon, $start, $end) {
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
        });
        
        // Only store historical data for farm locations
        // Only dispatch if we got data from API (not empty series) and farm is found
        if (!empty($series)) {
            $farm = $this->findFarmByCoordinates($lat, $lon);
            if ($farm) {
                // Store historical data in background (non-blocking) only if farm is found
                StoreHistoricalWeatherJob::dispatch($series, $lat, $lon, null, $farm->farm_id);
            }
        }
        
        return $series;
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

    /**
     * Get multiple geocoding suggestions for autocomplete.
     * Returns up to 5 location suggestions.
     */
    public function geocodeSuggestions(string $query, int $limit = 5): array
    {
        $normalized = Str::of($query ?? '')->trim();
        if ($normalized->isEmpty() || $normalized->length() < 2) {
            return [];
        }

        $limit = max(1, min($limit, 10)); // Limit between 1 and 10
        $cacheKey = 'geocode_suggestions_' . Str::lower($normalized) . '_' . $limit;

        return Cache::remember($cacheKey, 3600, function () use ($normalized, $limit) {
            try {
                $response = Http::weather()->get('https://api.openweathermap.org/geo/1.0/direct', [
                    'q' => $normalized,
                    'limit' => $limit,
                    'appid' => $this->apiKey,
                ]);

                if (!$response->successful()) {
                    Log::warning('Geocoding suggestions request failed', [
                        'query' => $normalized,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    return [];
                }

                $results = $response->json();
                
                if (!is_array($results)) {
                    return [];
                }

                return array_map(function ($result) {
                    $name = $result['name'] ?? '';
                    $state = $result['state'] ?? null;
                    $country = $result['country'] ?? null;
                    
                    // Build full location name
                    $fullName = $name;
                    if ($state && $state !== $name) {
                        $fullName .= ', ' . $state;
                    }
                    if ($country) {
                        $fullName .= ', ' . $country;
                    }

                    return [
                        'name' => $name,
                        'fullName' => $fullName,
                        'lat' => isset($result['lat']) ? (float) $result['lat'] : null,
                        'lon' => isset($result['lon']) ? (float) $result['lon'] : null,
                        'country' => $country,
                        'state' => $state,
                    ];
                }, array_filter($results, function ($result) {
                    return isset($result['lat'], $result['lon']);
                }));
            } catch (\Throwable $e) {
                Log::error('Geocoding suggestions exception', [
                    'query' => $normalized,
                    'error' => $e->getMessage(),
                ]);
                return [];
            }
        });
    }

    /**
     * Reverse geocode coordinates to get location name.
     */
    public function reverseGeocodeCoordinates(float $lat, float $lon): ?array
    {
        $lat = round($lat, 3);
        $lon = round($lon, 3);
        $cacheKey = "reverse_geocode_{$lat}_{$lon}";

        return Cache::remember($cacheKey, 86400, function () use ($lat, $lon) {
            $response = Http::weather()->get('https://api.openweathermap.org/geo/1.0/reverse', [
                'lat' => $lat,
                'lon' => $lon,
                'limit' => 1,
                'appid' => $this->apiKey,
            ]);

            if (!$response->successful()) {
                Log::warning('Reverse geocoding request failed', [
                    'lat' => $lat,
                    'lon' => $lon,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $results = $response->json();
            $first = $results[0] ?? null;

            if (!$first) {
                return null;
            }

            // Build location name from available fields
            $name = $first['name'] ?? '';
            $state = $first['state'] ?? null;
            $country = $first['country'] ?? null;

            $locationName = $name;
            if ($state && $state !== $name) {
                $locationName .= ', ' . $state;
            }
            if ($country) {
                $locationName .= ', ' . $country;
            }

            return [
                'lat' => (float) $first['lat'],
                'lon' => (float) $first['lon'],
                'name' => $locationName ?: $name,
                'country' => $country,
            ];
        });
    }

    /**
     * Find a farm by coordinates (within 0.005 degree tolerance, ~500m).
     * Only matches farms that are very close to the weather data coordinates.
     */
    public function findFarmByCoordinates(float $lat, float $lon): ?Farm
    {
        // Don't round coordinates here - use them as-is for precise matching
        // Use a small tolerance (0.005 degrees ≈ 500m) to match nearby farms
        // This is stricter than weather data matching to avoid false matches
        $tolerance = 0.005;
        
        return Farm::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$lat - $tolerance, $lat + $tolerance])
            ->whereBetween('longitude', [$lon - $tolerance, $lon + $tolerance])
            ->first();
    }

    public function storeWeatherSnapshot(array $weather, array $context = []): ?WeatherData
    {
        if (empty($weather['main'])) {
            return null;
        }

        $recordedAt = $this->resolveRecordedAt($weather, $context['recorded_at'] ?? null);
        $farmId = $context['farm_id'] ?? null;

        // If farm_id is explicitly provided, fetch the farm to get its name and coordinates
        $farm = null;
        if ($farmId !== null) {
            $farm = Farm::find($farmId);
            if ($farm) {
                // Always use farm's name when farm_id is provided
                $locationName = Str::lower(trim($farm->farm_name));
                // Use farm's coordinates if not provided in context
                $lat = $context['lat'] ?? ($farm->latitude !== null ? (float) $farm->latitude : null);
                $lon = $context['lon'] ?? ($farm->longitude !== null ? (float) $farm->longitude : null);
            } else {
                // Farm not found, reset farm_id
                $farmId = null;
            }
        }

        // If no farm_id, get location from context
        if (!$farm) {
            $location = $context['location'] ?? $context['location_name'] ?? ($weather['name'] ?? null);
            $locationName = $location ? Str::lower(trim($location)) : null;
            if ($locationName === '') {
                $locationName = null;
            }

            $lat = $context['lat'] ?? data_get($weather, 'coord.lat');
            $lon = $context['lon'] ?? data_get($weather, 'coord.lon');

            // If no farm_id provided but we have coordinates, try to match to existing farms
            if (!$farmId && $lat !== null && $lon !== null) {
                $farm = $this->findFarmByCoordinates($lat, $lon);
                if ($farm) {
                    $farmId = $farm->farm_id;
                    // Always use farm name as location name when farm is matched
                    // This ensures data is categorized by the actual farm, not geocoded location
                    $locationName = Str::lower(trim($farm->farm_name));
                }
            }
        }

        // If still no location name but we have coordinates, try to reverse geocode
        // Only do this if we don't have a farm (to avoid overwriting farm names)
        if (!$locationName && !$farmId && $lat !== null && $lon !== null) {
            $geocoded = $this->reverseGeocodeCoordinates($lat, $lon);
            if ($geocoded) {
                $locationName = Str::lower(trim($geocoded['name']));
            }
        }

        // Only store weather data for farm locations (optimization)
        // Skip storage for generic locations that don't belong to farms
        if (!$farmId) {
            return null;
        }

        $lat = $lat !== null ? round((float) $lat, 6) : null;
        $lon = $lon !== null ? round((float) $lon, 6) : null;

        // Use start and end of day range for querying to handle any time on that day
        $startOfDay = $recordedAt->copy()->startOfDay();
        $endOfDay = $recordedAt->copy()->endOfDay();
        $query = WeatherData::query()->whereBetween('recorded_at', [$startOfDay, $endOfDay]);

        // Priority: farm_id > coordinates > location_name
        // Use coordinates for matching as they're more reliable and allow updating NULL location_name
        if ($farmId) {
            $query->where('farm_id', $farmId);
        } elseif ($lat !== null && $lon !== null) {
            // Match by coordinates first (most reliable)
            // Use small tolerance (0.001 degrees ≈ 100m) to handle slight rounding differences
            // This allows updating records that previously had NULL location_name
            $tolerance = 0.001;
            $query->whereNull('farm_id')
                  ->whereNotNull('latitude')
                  ->whereNotNull('longitude')
                  ->whereBetween('latitude', [$lat - $tolerance, $lat + $tolerance])
                  ->whereBetween('longitude', [$lon - $tolerance, $lon + $tolerance]);
        } elseif ($locationName !== null) {
            // Fall back to location_name matching if no coordinates
            $query->whereNull('farm_id')
                  ->where('location_name', $locationName);
        } else {
            // No reliable identifier
            return null;
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

        // Filter out today and future dates from historical data
        // Historical data should only include dates in the past
        $todayUtc = Carbon::today('UTC')->startOfDay();

        foreach ($dates as $index => $dateString) {
            // Parse date string and check if it's in the past
            $dateObj = Carbon::parse($dateString, 'UTC')->startOfDay();
            
            // Skip today and future dates - these should not be in historical data
            if ($dateObj->gte($todayUtc)) {
                continue;
            }

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

        // Calculate average humidity from hourly data if available
        $humidityValues = [];
        if (!empty($hourlyData)) {
            foreach ($hourlyData as $hour) {
                if (isset($hour['humidity']) && $hour['humidity'] !== null) {
                    $humidityValues[] = (float) $hour['humidity'];
                }
            }
        }
        $averageHumidity = !empty($humidityValues) ? round(array_sum($humidityValues) / count($humidityValues), 2) : null;

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
                'humidity' => $averageHumidity,
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

    /**
     * Fetch forecast from OpenWeather One Call API 3.0
     * Supports up to 8 days of daily forecasts
     */
    private function getForecastFromOneCall(float $lat, float $lon, int $days = 7): array
    {
        $oneCallBaseUrl = 'https://api.openweathermap.org/data/3.0/onecall';
        $requestDays = min($days, 8); // One Call API supports up to 8 days
        
        $response = Http::weather()->get($oneCallBaseUrl, [
            'lat' => $lat,
            'lon' => $lon,
            'appid' => $this->apiKey,
            'units' => 'metric',
            'exclude' => 'minutely,hourly,alerts', // Only get daily forecast
        ]);

        if (!$response->successful()) {
            $errorData = $response->json();
            $errorMessage = data_get($errorData, 'message', 'HTTP ' . $response->status());
            \Log::warning('One Call API request failed', [
                'status' => $response->status(),
                'lat' => $lat,
                'lon' => $lon,
                'error' => $errorData,
            ]);
            throw new \RuntimeException('One Call API failed: ' . $errorMessage);
        }

        $data = $response->json();
        
        // Validate response structure
        if (!isset($data['daily']) || !is_array($data['daily'])) {
            \Log::warning('One Call API invalid response structure', [
                'lat' => $lat,
                'lon' => $lon,
                'response' => $data,
            ]);
            throw new \RuntimeException('One Call API invalid response structure');
        }

        $timezoneOffset = data_get($data, 'timezone_offset', 0);
        $dailyData = $data['daily'];
        
        // Process daily forecast data
        $processedData = [];
        $limit = min(count($dailyData), $requestDays);
        
        for ($i = 0; $i < $limit; $i++) {
            $dayData = $dailyData[$i];
            if (!isset($dayData['dt'])) {
                continue;
            }
            
            $timestamp = $dayData['dt'];
            $date = Carbon::createFromTimestamp($timestamp, 'UTC')
                ->addSeconds($timezoneOffset)
                ->format('Y-m-d');
            
            $temp = $dayData['temp'] ?? [];
            $tempMax = isset($temp['max']) ? round((float) $temp['max'], 1) : null;
            $tempMin = isset($temp['min']) ? round((float) $temp['min'], 1) : null;
            
            $weather = $dayData['weather'][0] ?? null;
            $condition = $weather['main'] ?? null;
            $icon = $weather['icon'] ?? null;
            $description = $weather['description'] ?? null;
            
            $sunrise = isset($dayData['sunrise']) ? (int) $dayData['sunrise'] : null;
            $sunset = isset($dayData['sunset']) ? (int) $dayData['sunset'] : null;
            $precipProbability = isset($dayData['pop']) ? (float) $dayData['pop'] : null; // Already a decimal 0-1
            
            // Hourly data is excluded from One Call API request for performance
            // Set to empty array - can be populated later if needed
            $hourly = [];
            
            $processedData[] = [
                'date' => $date,
                'day' => Carbon::parse($date)->format('l'),
                'temp_max' => $tempMax,
                'temp_min' => $tempMin,
                'condition' => $condition,
                'icon' => $icon,
                'description' => $description,
                'precip_probability' => $precipProbability,
                'sunrise' => $sunrise,
                'sunset' => $sunset,
                'hourly' => $hourly,
                'timezone_offset' => $timezoneOffset,
            ];
        }
        
        return $processedData;
    }

    private function processForecastData($data, int $days = 7)
    {
        // Validate input data
        if (!is_array($data) || !isset($data['list']) || !is_array($data['list'])) {
            throw new \InvalidArgumentException('Invalid forecast data: missing or invalid list');
        }

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
            $date = Carbon::today('UTC')->format('Y-m-d');
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
            $parsed = Carbon::parse($provided);
            // Ensure we're in UTC timezone and extract the date part
            $parsed->setTimezone('UTC');
            $recordedDate = $parsed->copy()->startOfDay();
            
            // Never save records with future dates
            // If provided date is in the future, clamp it to today
            $todayUtc = Carbon::today('UTC');
            if ($recordedDate->gt($todayUtc)) {
                return $todayUtc;
            }
            
            return $recordedDate;
        }

        if (isset($weather['dt']) && is_numeric($weather['dt'])) {
            // API timestamp is in UTC (Unix timestamp)
            // Create from UTC timestamp, extract the date, and set to start of that day
            $dateTime = Carbon::createFromTimestampUTC((int) $weather['dt']);
            $recordedDate = $dateTime->copy()->startOfDay();
            
            // Never save records with future dates
            // If API timestamp is in the future, clamp it to today
            $todayUtc = Carbon::today('UTC');
            if ($recordedDate->gt($todayUtc)) {
                return $todayUtc;
            }
            
            return $recordedDate;
        }

        if (isset($weather['timestamp']) && is_numeric($weather['timestamp'])) {
            // Timestamp is in UTC (Unix timestamp)
            // Create from UTC timestamp, extract the date, and set to start of that day
            $dateTime = Carbon::createFromTimestampUTC((int) $weather['timestamp']);
            $recordedDate = $dateTime->copy()->startOfDay();
            
            // Never save records with future dates
            // If timestamp is in the future, clamp it to today
            $todayUtc = Carbon::today('UTC');
            if ($recordedDate->gt($todayUtc)) {
                return $todayUtc;
            }
            
            return $recordedDate;
        }

        // Default to today at start of day in UTC (for current weather)
        return Carbon::today('UTC');
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
                // Parse recorded_at as UTC and extract date
                $recordedAt = Carbon::parse($record->recorded_at)->setTimezone('UTC');
                $dateString = $recordedAt->toDateString();
                $timestamp = $recordedAt->timestamp;

                return [
                    'date' => $dateString,
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
        // Parse recorded_at as UTC and extract date
        $recordedAt = Carbon::parse($record->recorded_at)->setTimezone('UTC');
        $dateString = $recordedAt->toDateString();
        $timestamp = $recordedAt->timestamp;

        return [
            'date' => $dateString,
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

    /**
     * Get stored current weather from database by location name.
     * Returns data if it's less than 30 minutes old.
     */
    private function getStoredCurrentWeatherByLocation(string $locationName): ?array
    {
        $thirtyMinutesAgo = Carbon::now()->subMinutes(30);
        
        $record = WeatherData::query()
            ->whereNotNull('location_name')
            ->where('location_name', $locationName)
            ->where('recorded_at', '>=', $thirtyMinutesAgo)
            ->orderByDesc('recorded_at')
            ->first();

        if ($record) {
            return $this->createSnapshotFromRecord($record);
        }

        return null;
    }

    /**
     * Get stored current weather from database by coordinates.
     * Returns data if it's less than 30 minutes old.
     */
    private function getStoredCurrentWeatherByCoordinates(float $lat, float $lon): ?array
    {
        $lat = round($lat, 3);
        $lon = round($lon, 3);
        $thirtyMinutesAgo = Carbon::now()->subMinutes(30);
        $tolerance = 0.01;

        $record = WeatherData::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$lat - $tolerance, $lat + $tolerance])
            ->whereBetween('longitude', [$lon - $tolerance, $lon + $tolerance])
            ->where('recorded_at', '>=', $thirtyMinutesAgo)
            ->orderByDesc('recorded_at')
            ->first();

        if ($record) {
            return $this->createSnapshotFromRecord($record);
        }

        return null;
    }

    /**
     * Get stored forecast from database by location name.
     * Returns forecast if all requested days are available and not expired.
     */
    private function getStoredForecastByLocation(string $locationName, int $days): ?array
    {
        try {
            // Check if forecasts table exists
            if (!Schema::hasTable('forecasts')) {
                \Log::warning('Forecasts table does not exist - migrations may need to be run');
                return null;
            }

            $today = Carbon::today('UTC');
            $endDate = $today->copy()->addDays($days - 1);

            $forecasts = Forecast::query()
                ->notExpired()
                ->forLocation($locationName)
                ->forDateRange($today, $endDate)
                ->orderBy('forecast_date')
                ->get();

            // Check if we have all requested days
            if ($forecasts->count() >= $days) {
                return $this->formatForecastCollection($forecasts);
            }

            return null;
        } catch (\Exception $e) {
            // Gracefully handle if table doesn't exist
            \Log::warning('Error querying forecasts table', [
                'error' => $e->getMessage(),
                'location' => $locationName,
            ]);
            return null;
        }
    }

    /**
     * Get stored forecast from database by coordinates.
     * Returns forecast if all requested days are available and not expired.
     */
    private function getStoredForecastByCoordinates(float $lat, float $lon, int $days): ?array
    {
        try {
            // Check if forecasts table exists
            if (!Schema::hasTable('forecasts')) {
                \Log::warning('Forecasts table does not exist - migrations may need to be run');
                return null;
            }

            $lat = round($lat, 3);
            $lon = round($lon, 3);
            $today = Carbon::today('UTC');
            $endDate = $today->copy()->addDays($days - 1);

            $forecasts = Forecast::query()
                ->notExpired()
                ->forCoordinates($lat, $lon)
                ->forDateRange($today, $endDate)
                ->orderBy('forecast_date')
                ->get();

            // Check if we have all requested days
            if ($forecasts->count() >= $days) {
                return $this->formatForecastCollection($forecasts);
            }

            return null;
        } catch (\Exception $e) {
            // Gracefully handle if table doesn't exist
            \Log::warning('Error querying forecasts table', [
                'error' => $e->getMessage(),
                'lat' => $lat,
                'lon' => $lon,
            ]);
            return null;
        }
    }

    /**
     * Store forecast data to database.
     */
    public function storeForecastData(array $forecastData, ?string $locationName, ?float $lat, ?float $lon, ?int $farmId = null): void

    {
        if (empty($forecastData) || !is_array($forecastData)) {
            return;
        }
        
        // Check if forecasts table exists before trying to store
        if (!Schema::hasTable('forecasts')) {
            \Log::warning('Cannot store forecast data - forecasts table does not exist. Run migrations.');
            return;
        }

        $lat = $lat !== null ? round($lat, 6) : null;
        $lon = $lon !== null ? round($lon, 6) : null;
        $locationName = $locationName ? Str::lower(trim($locationName)) : null;

        // Use provided farm_id, or try to find associated farm if not provided
        if ($farmId === null && $lat !== null && $lon !== null) {
            $farm = $this->findFarmByCoordinates($lat, $lon);
            if ($farm) {
                $farmId = $farm->farm_id;
            }
        }

        // Only store forecast data for farm locations (optimization)
        if ($farmId === null) {
            return;
        }

        // Calculate expiration: forecasts expire at the end of the forecast date
        $now = Carbon::now('UTC');
        $maxForecastDate = null;

        foreach ($forecastData as $dayForecast) {
            $forecastDateStr = $dayForecast['date'] ?? null;
            if (!$forecastDateStr) {
                continue;
            }

            try {
                $forecastDate = Carbon::parse($forecastDateStr, 'UTC')->startOfDay();
                $expiresAt = $forecastDate->copy()->endOfDay()->addHours(2); // Expire 2 hours after the day ends
                
                if ($maxForecastDate === null || $expiresAt->gt($maxForecastDate)) {
                    $maxForecastDate = $expiresAt;
                }

                // Store or update forecast record
                Forecast::updateOrCreate(
                    [
                        'farm_id' => $farmId,
                        'location_name' => $locationName,
                        'latitude' => $lat,
                        'longitude' => $lon,
                        'forecast_date' => $forecastDate,
                    ],
                    [
                        'temp_max' => $dayForecast['temp_max'] ?? null,
                        'temp_min' => $dayForecast['temp_min'] ?? null,
                        'condition' => $dayForecast['condition'] ?? null,
                        'condition_icon' => $dayForecast['icon'] ?? null,
                        'description' => $dayForecast['description'] ?? null,
                        'precip_probability' => $dayForecast['precip_probability'] ?? null,
                        'sunrise' => $dayForecast['sunrise'] ?? null,
                        'sunset' => $dayForecast['sunset'] ?? null,
                        'timezone_offset' => $dayForecast['timezone_offset'] ?? 0,
                        'hourly_data' => $dayForecast['hourly'] ?? null,
                        'cached_at' => $now,
                        'expires_at' => $expiresAt,
                    ]
                );
            } catch (\Exception $e) {
                Log::warning('Failed to store forecast day', [
                    'date' => $forecastDateStr,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Format forecast collection to match API response format.
     */
    private function formatForecastCollection(Collection $forecasts): array
    {
        return $forecasts->map(function (Forecast $forecast) {
            return [
                'date' => $forecast->forecast_date->format('Y-m-d'),
                'day' => $forecast->forecast_date->format('l'),
                'temp_max' => $forecast->temp_max,
                'temp_min' => $forecast->temp_min,
                'condition' => $forecast->condition,
                'icon' => $forecast->condition_icon,
                'description' => $forecast->description,
                'precip_probability' => $forecast->precip_probability,
                'sunrise' => $forecast->sunrise,
                'sunset' => $forecast->sunset,
                'hourly' => $forecast->hourly_data ?? [],
                'timezone_offset' => $forecast->timezone_offset ?? 0,
            ];
        })->toArray();
    }
}