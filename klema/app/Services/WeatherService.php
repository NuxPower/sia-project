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
                return $this->getMockCurrentWeather();
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
            return $this->getMockCurrentWeather();
        });
    }

    public function getForecast($location, $days = 7)
    {
        $cacheKey = "weather_forecast_{$location}_{$days}";
        
        return Cache::remember($cacheKey, 3600, function () use ($location, $days) {
            // If no API key, return mock data immediately
            if (!$this->apiKey) {
                return $this->getMockForecast();
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
            return $this->getMockForecast();
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

    private function getMockCurrentWeather()
    {
        return [
            'weather' => [
                ['main' => 'Rain', 'description' => 'Heavy Rain', 'icon' => '10d']
            ],
            'main' => [
                'temp' => 28,
                'feels_like' => 31,
                'humidity' => 85,
                'temp_min' => 24,
                'temp_max' => 28
            ],
            'wind' => [
                'speed' => 7.9
            ],
            'visibility' => 5000,
            'dt' => time(),
            'sys' => [
                'sunrise' => strtotime('4:50 AM'),
                'sunset' => strtotime('6:45 PM')
            ],
            'name' => 'Maramag'
        ];
    }

    private function getMockForecast()
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