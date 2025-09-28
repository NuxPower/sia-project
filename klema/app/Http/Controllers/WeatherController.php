<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WeatherService;

class WeatherController extends Controller
{
    protected $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    public function getCurrentWeather(Request $request)
    {
        // Check if coordinates are provided
        $lat = $request->get('lat');
        $lon = $request->get('lon');
        $location = $request->get('location');
        
        try {
            if ($lat && $lon) {
                // Use coordinates
                $weather = $this->weatherService->getCurrentWeatherByCoordinates($lat, $lon);
            } else if ($location) {
                // Use location name
                $weather = $this->weatherService->getCurrentWeather($location);
            } else {
                return response()->json(['error' => 'Either location or lat/lon coordinates are required'], 400);
            }
            
            return response()->json($weather);
        } catch (\Exception $e) {
            \Log::error('Weather API error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch weather data: ' . $e->getMessage()], 500);
        }
    }

    public function getForecast(Request $request)
    {
        // Check if coordinates are provided
        $lat = $request->get('lat');
        $lon = $request->get('lon');
        $location = $request->get('location');
        $days = $request->get('days', 7);
        
        try {
            if ($lat && $lon) {
                // Use coordinates
                $forecast = $this->weatherService->getForecastByCoordinates($lat, $lon, $days);
            } else if ($location) {
                // Use location name
                $forecast = $this->weatherService->getForecast($location, $days);
            } else {
                return response()->json(['error' => 'Either location or lat/lon coordinates are required'], 400);
            }
            
            return response()->json($forecast);
        } catch (\Exception $e) {
            \Log::error('Forecast API error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch forecast data: ' . $e->getMessage()], 500);
        }
    }
}