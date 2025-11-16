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
                [$latValue, $lonValue] = $this->sanitizeCoordinates($lat, $lon);
                if ($latValue === null || $lonValue === null) {
                    return response()->json(['error' => 'Latitude and longitude must be numeric values.'], 422);
                }

                // Use coordinates
                $weather = $this->weatherService->getCurrentWeatherByCoordinates($latValue, $lonValue);
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
                [$latValue, $lonValue] = $this->sanitizeCoordinates($lat, $lon);
                if ($latValue === null || $lonValue === null) {
                    return response()->json(['error' => 'Latitude and longitude must be numeric values.'], 422);
                }

                // Use coordinates
                $forecast = $this->weatherService->getForecastByCoordinates($latValue, $lonValue, $days);
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

    /**
     * Normalize latitude and longitude values.
     *
     * @param mixed $lat
     * @param mixed $lon
     * @return array{0: ?float, 1: ?float}
     */
    private function sanitizeCoordinates($lat, $lon): array
    {
        $latNumeric = filter_var($lat, FILTER_VALIDATE_FLOAT);
        $lonNumeric = filter_var($lon, FILTER_VALIDATE_FLOAT);

        if ($latNumeric === false || $lonNumeric === false) {
            return [null, null];
        }

        $latValue = max(-90.0, min(90.0, (float) $latNumeric));

        $lonValue = (float) $lonNumeric;
        if (!is_finite($lonValue)) {
            return [null, null];
        }

        $lonValue = fmod($lonValue + 180.0, 360.0);
        if ($lonValue < 0) {
            $lonValue += 360.0;
        }
        $lonValue -= 180.0;

        return [$latValue, $lonValue];
    }
}