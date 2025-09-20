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
        $location = $request->get('location', 'Maramag,PH');
        
        try {
            $weather = $this->weatherService->getCurrentWeather($location);
            return response()->json($weather);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch weather data'], 500);
        }
    }

    public function getForecast(Request $request)
    {
        $location = $request->get('location', 'Maramag,PH');
        $days = $request->get('days', 7);
        
        try {
            $forecast = $this->weatherService->getForecast($location, $days);
            return response()->json($forecast);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch forecast data'], 500);
        }
    }
}
