<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
        $location = $request->get('location', 'Butuan, Caraga, PH');
        
        try {
            if ($lat && $lon) {
                // Use coordinates
                $weather = $this->weatherService->getCurrentWeatherByCoordinates($lat, $lon);
            } else {
                // Use location name
                $weather = $this->weatherService->getCurrentWeather($location);
            }
            
            return response()->json([
                'success' => true,
                'weather' => $weather
            ]);
        } catch (\Exception $e) {
            \Log::error('Weather API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
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
        $location = $request->get('location', 'Butuan, Caraga, PH');
        $days = $request->get('days', 5);
        
        try {
            if ($lat && $lon) {
                // Use coordinates
                $forecast = $this->weatherService->getForecastByCoordinates($lat, $lon, $days);
            } else {
                // Use location name
                $forecast = $this->weatherService->getForecast($location, $days);
            }
            
            return response()->json([
                'success' => true,
                'forecast' => $forecast
            ]);
        } catch (\Exception $e) {
            \Log::error('Weather Forecast API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch forecast data: ' . $e->getMessage()
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
        $location = $request->get('location', 'Butuan, Caraga, PH');
        
        try {
            // Validate date format
            $dateObj = \Carbon\Carbon::createFromFormat('Y-m-d', $date);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date format. Use YYYY-MM-DD'
                ], 400);
            }
            
            if ($lat && $lon) {
                // Use coordinates
                $historical = $this->weatherService->getHistoricalWeatherByCoordinates($lat, $lon, $date);
            } else {
                // Use location name
                $historical = $this->weatherService->getHistoricalWeather($location, $date);
            }
            
            return response()->json([
                'success' => true,
                'historical' => $historical
            ]);
        } catch (\Exception $e) {
            \Log::error('Historical Weather API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch historical weather data: ' . $e->getMessage()
            ], 500);
        }
    }
}
