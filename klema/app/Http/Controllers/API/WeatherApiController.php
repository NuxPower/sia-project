<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

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
        $location = $request->get('location', 'Butuan, Caraga, PH');
        $days = (int) $request->get('days', 3);
        $days = max(1, min($days, 7));

        try {
            $history = [];
            $today = Carbon::today();

            for ($i = 1; $i <= $days; $i++) {
                $date = $today->copy()->subDays($i)->format('Y-m-d');

                if ($lat && $lon && method_exists($this->weatherService, 'getHistoricalWeatherByCoordinates')) {
                    $entry = $this->weatherService->getHistoricalWeatherByCoordinates($lat, $lon, $date);
                } else {
                    $entry = $this->weatherService->getHistoricalWeather($location, $date);
                }

                $history[] = $this->normalizeHistoricalEntry($date, $entry);
            }

            return response()->json(array_reverse($history));
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
        $location = $request->get('location', 'Butuan, Caraga, PH');
        
        try {
            $dateObj = Carbon::createFromFormat('Y-m-d', $date);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
                return response()->json([
                    'message' => 'Invalid date format. Use YYYY-MM-DD'
                ], 400);
            }

            if ($lat && $lon && method_exists($this->weatherService, 'getHistoricalWeatherByCoordinates')) {
                $historical = $this->weatherService->getHistoricalWeatherByCoordinates($lat, $lon, $date);
            } else {
                $historical = $this->weatherService->getHistoricalWeather($location, $date);
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
        $temperature = $data['main']['temp'] ?? null;
        $tempMax = $data['main']['temp_max'] ?? ($temperature !== null ? $temperature + 2 : null);
        $tempMin = $data['main']['temp_min'] ?? ($temperature !== null ? $temperature - 3 : null);

        $conditionData = $data['weather'][0] ?? [];

        return [
            'date' => $date,
            'temp_max' => $this->normalizeTemperature($tempMax),
            'temp_min' => $this->normalizeTemperature($tempMin),
            'condition' => $conditionData['main'] ?? ($conditionData['description'] ?? 'Unknown'),
            'icon' => $conditionData['icon'] ?? '01d'
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

}
