<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class WeatherService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.openweathermap.org/data/2.5';

    public function __construct()
    {
        $this->apiKey = config('services.openweather.api_key');
        
        // If no API key is configured, we'll use mock data
        if (empty($this->apiKey) || $this->apiKey === 'your-openweather-api-key-here') {
            $this->apiKey = null;
        }
    }

    public function getCurrentWeather($location)
    {
        $cacheKey = "weather_current_{$location}";
        
        return Cache::remember($cacheKey, 600, function () use ($location) {
            // If no API key, return mock data immediately
            if (!$this->apiKey) {
                return $this->getMockCurrentWeather($location);
            }

            try {
                // Use custom HTTP macro for weather API calls
                $response = Http::weather()->get("{$this->baseUrl}/weather", [
                    'q' => $location,
                    'appid' => $this->apiKey,
                    'units' => 'metric'
                ]);

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                \Log::warning('Weather API request failed: ' . $e->getMessage());
            }

            // Return mock data if API fails
            return $this->getMockCurrentWeather($location);
        });
    }

    public function getCurrentWeatherByCoordinates($lat, $lon)
    {
        $cacheKey = "weather_current_coords_{$lat}_{$lon}";
        
        return Cache::remember($cacheKey, 600, function () use ($lat, $lon) {
            // If no API key, return mock data with coordinates
            if (!$this->apiKey) {
                return $this->getMockCurrentWeatherByCoords($lat, $lon);
            }

            try {
                // Use coordinates for OpenWeatherMap API
                $response = Http::weather()->get("{$this->baseUrl}/weather", [
                    'lat' => $lat,
                    'lon' => $lon,
                    'appid' => $this->apiKey,
                    'units' => 'metric'
                ]);

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                \Log::warning('Weather API request by coordinates failed: ' . $e->getMessage());
            }

            // Return mock data if API fails
            return $this->getMockCurrentWeatherByCoords($lat, $lon);
        });
    }

    public function getForecast($location, $days = 7)
    {
        $cacheKey = "weather_forecast_{$location}_{$days}";
        
        return Cache::remember($cacheKey, 3600, function () use ($location, $days) {
            // If no API key, return mock data immediately
            if (!$this->apiKey) {
                return $this->getMockForecast($location);
            }

            try {
                // Use custom HTTP macro for weather API calls
                $response = Http::weather()->get("{$this->baseUrl}/forecast", [
                    'q' => $location,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'cnt' => $days * 8 // 8 forecasts per day (3-hour intervals)
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $this->processForecastData($data);
                }
            } catch (\Exception $e) {
                \Log::warning('Weather Forecast API request failed: ' . $e->getMessage());
            }

            // Return mock data if API fails
            return $this->getMockForecast($location);
        });
    }

    public function getForecastByCoordinates($lat, $lon, $days = 7)
    {
        $cacheKey = "weather_forecast_coords_{$lat}_{$lon}_{$days}";
        
        return Cache::remember($cacheKey, 3600, function () use ($lat, $lon, $days) {
            // If no API key, return mock data with coordinates
            if (!$this->apiKey) {
                return $this->getMockForecastByCoords($lat, $lon);
            }

            try {
                // Use coordinates for OpenWeatherMap API
                $response = Http::weather()->get("{$this->baseUrl}/forecast", [
                    'lat' => $lat,
                    'lon' => $lon,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'cnt' => $days * 8 // 8 forecasts per day (3-hour intervals)
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $this->processForecastData($data);
                }
            } catch (\Exception $e) {
                \Log::warning('Weather Forecast API request by coordinates failed: ' . $e->getMessage());
            }

            // Return mock data if API fails
            return $this->getMockForecastByCoords($lat, $lon);
        });
    }

    public function getHistoricalWeather($location, $date)
    {
        // OpenWeatherMap historical data requires paid plan
        // Return mock historical data for demonstration
        return $this->getMockHistoricalWeather($date);
    }

    private function processForecastData($data)
    {
        $dailyForecasts = [];
        $currentDate = null;
        $dayData = [];

        foreach ($data['list'] as $forecast) {
            $date = Carbon::parse($forecast['dt_txt'])->format('Y-m-d');
            
            if ($currentDate !== $date) {
                if (!empty($dayData)) {
                    $dailyForecasts[] = $this->aggregateDayData($dayData, $currentDate);
                }
                $currentDate = $date;
                $dayData = [];
            }
            
            $dayData[] = $forecast;
        }

        // Add the last day
        if (!empty($dayData)) {
            $dailyForecasts[] = $this->aggregateDayData($dayData, $currentDate);
        }

        return array_slice($dailyForecasts, 0, 7);
    }

    private function aggregateDayData($dayData, $date)
    {
        $temps = array_column(array_column($dayData, 'main'), 'temp');
        $conditions = array_column(array_column($dayData, 'weather'), 0);
        
        return [
            'date' => $date,
            'day' => Carbon::parse($date)->format('l'),
            'temp_max' => round(max($temps)),
            'temp_min' => round(min($temps)),
            'condition' => $conditions[0]['main'] ?? 'Clear',
            'icon' => $conditions[0]['icon'] ?? '01d',
            'description' => $conditions[0]['description'] ?? 'Clear sky'
        ];
    }

    private function getMockCurrentWeather($location = null)
    {
        // Generate different mock data based on location or use default
        $locationName = $this->extractLocationName($location) ?: 'Maramag';
        
        return [
            'weather' => [
                ['main' => 'Rain', 'description' => 'Heavy Rain', 'icon' => '10d']
            ],
            'main' => [
                'temp' => 28,
                'feels_like' => 31,
                'humidity' => 85,
                'temp_min' => 24,
                'temp_max' => 28,
                'pressure' => 1012
            ],
            'wind' => [
                'speed' => 7.9
            ],
            'visibility' => 5000,
            'dt' => time(),
            'sys' => [
                'sunrise' => strtotime('4:50 AM'),
                'sunset' => strtotime('6:45 PM'),
                'country' => 'PH'
            ],
            'coord' => [
                'lat' => 7.7708,
                'lon' => 125.0061
            ],
            'name' => $locationName
        ];
    }

    private function getMockCurrentWeatherByCoords($lat, $lon)
    {
        // Generate location-specific mock data based on coordinates
        $mockLocation = $this->getMockLocationFromCoords($lat, $lon);
        
        return [
            'weather' => [
                ['main' => $mockLocation['condition'], 'description' => $mockLocation['description'], 'icon' => $mockLocation['icon']]
            ],
            'main' => [
                'temp' => $mockLocation['temp'],
                'feels_like' => $mockLocation['temp'] + 3,
                'humidity' => $mockLocation['humidity'],
                'temp_min' => $mockLocation['temp'] - 4,
                'temp_max' => $mockLocation['temp'] + 2,
                'pressure' => $mockLocation['pressure']
            ],
            'wind' => [
                'speed' => $mockLocation['wind_speed']
            ],
            'visibility' => 10000,
            'dt' => time(),
            'sys' => [
                'sunrise' => strtotime('5:00 AM'),
                'sunset' => strtotime('6:30 PM'),
                'country' => $mockLocation['country']
            ],
            'coord' => [
                'lat' => (float) $lat,
                'lon' => (float) $lon
            ],
            'name' => $mockLocation['name']
        ];
    }

    private function getMockForecast($location = null)
    {
        $locationName = $this->extractLocationName($location) ?: 'Maramag';
        return $this->generateMockForecast($locationName);
    }

    private function getMockForecastByCoords($lat, $lon)
    {
        $mockLocation = $this->getMockLocationFromCoords($lat, $lon);
        return $this->generateMockForecast($mockLocation['name']);
    }

    private function generateMockForecast($locationName)
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $conditions = [
            ['condition' => 'Rain', 'icon' => '10d'],
            ['condition' => 'Rain', 'icon' => '10d'],
            ['condition' => 'Rain', 'icon' => '10d'],
            ['condition' => 'Clouds', 'icon' => '04d'],
            ['condition' => 'Clear', 'icon' => '01d'],
            ['condition' => 'Clear', 'icon' => '01d'],
            ['condition' => 'Clear', 'icon' => '01d']
        ];

        $forecast = [];
        for ($i = 0; $i < 7; $i++) {
            $forecast[] = [
                'date' => Carbon::now()->addDays($i)->format('Y-m-d'),
                'day' => $days[$i],
                'temp_max' => rand(32, 35),
                'temp_min' => rand(26, 28),
                'condition' => $conditions[$i]['condition'],
                'icon' => $conditions[$i]['icon']
            ];
        }

        return $forecast;
    }

    private function getMockLocationFromCoords($lat, $lon)
    {
        // Generate different mock data based on coordinates
        // This simulates different locations
        
        $lat = (float) $lat;
        $lon = (float) $lon;
        
        // Manila area
        if ($lat >= 14.0 && $lat <= 15.0 && $lon >= 120.0 && $lon <= 121.5) {
            return [
                'name' => 'Manila',
                'country' => 'PH',
                'temp' => 32,
                'humidity' => 75,
                'pressure' => 1010,
                'wind_speed' => 5.2,
                'condition' => 'Clouds',
                'description' => 'Partly cloudy',
                'icon' => '03d'
            ];
        }
        
        // Cebu area
        if ($lat >= 10.0 && $lat <= 11.0 && $lon >= 123.0 && $lon <= 124.5) {
            return [
                'name' => 'Cebu City',
                'country' => 'PH',
                'temp' => 30,
                'humidity' => 80,
                'pressure' => 1011,
                'wind_speed' => 4.8,
                'condition' => 'Clear',
                'description' => 'Clear sky',
                'icon' => '01d'
            ];
        }
        
        // Davao area
        if ($lat >= 6.5 && $lat <= 7.5 && $lon >= 125.0 && $lon <= 126.0) {
            return [
                'name' => 'Davao City',
                'country' => 'PH',
                'temp' => 29,
                'humidity' => 85,
                'pressure' => 1013,
                'wind_speed' => 3.5,
                'condition' => 'Rain',
                'description' => 'Light rain',
                'icon' => '10d'
            ];
        }
        
        // Default for other coordinates
        return [
            'name' => 'Unknown Location',
            'country' => 'PH',
            'temp' => 28,
            'humidity' => 82,
            'pressure' => 1012,
            'wind_speed' => 4.0,
            'condition' => 'Clouds',
            'description' => 'Scattered clouds',
            'icon' => '03d'
        ];
    }

    private function extractLocationName($location)
    {
        if (!$location) return null;
        
        // Extract just the city name from location strings like "Manila, PH" or "Cebu City, Cebu, PH"
        $parts = explode(',', $location);
        return trim($parts[0]);
    }

    private function getMockHistoricalWeather($date)
    {
        return [
            'weather' => [
                ['main' => 'Rain', 'description' => 'Heavy Rain']
            ],
            'main' => [
                'temp' => 26,
                'humidity' => 90
            ],
            'wind' => [
                'speed' => 8.5
            ],
            'rain' => [
                '1h' => 12.5
            ]
        ];
    }
}