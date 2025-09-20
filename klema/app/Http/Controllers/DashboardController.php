<?php

// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WeatherService;
use App\Models\Farm;
use App\Models\WeatherData;
use App\Models\Alert;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $location = $request->get('location', 'Butuan, Caraga, PH');
        
        // Get user's farms
        $farms = $user->farms()->with(['weatherData' => function($query) {
            $query->latest('recorded_at')->limit(1);
        }, 'alerts' => function($query) {
            $query->where('resolved', false);
        }])->get();

        // Get current weather data from API
        $currentWeather = $this->weatherService->getCurrentWeather($location);
        $forecast = $this->weatherService->getForecast($location);
        
        // Store weather data for user's farms if they have any
        if ($farms->isNotEmpty()) {
            $this->storeWeatherDataForFarms($farms, $currentWeather);
        }
        
        // Get farming tips based on weather
        $farmingTips = $this->generateFarmingTips($currentWeather);
        
        // Get recent alerts
        $recentAlerts = Alert::whereHas('farm', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('resolved', false)
          ->orderBy('issued_at', 'desc')
          ->limit(5)
          ->get();

        // Get weather statistics
        $weatherStats = $this->getWeatherStatistics($farms);

        return view('dashboard', compact(
            'currentWeather', 
            'forecast', 
            'farmingTips', 
            'farms', 
            'recentAlerts', 
            'location',
            'weatherStats'
        ));
    }

    public function calendar()
    {
        $user = auth()->user();
        $farms = $user->farms;
        
        // Get alerts as calendar events
        $alerts = Alert::whereHas('farm', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get()->map(function ($alert) {
            return [
                'id' => $alert->alert_id,
                'title' => $alert->alert_type . ': ' . Str::limit($alert->message, 30),
                'start' => $alert->issued_at->format('Y-m-d'),
                'type' => $alert->alert_type,
                'resolved' => $alert->resolved,
                'farm' => $alert->farm->farm_name
            ];
        });

        return view('calendar', compact('alerts', 'farms'));
    }

    private function storeWeatherDataForFarms($farms, $weatherData)
    {
        foreach ($farms as $farm) {
            // Check if we already have recent data (within last hour)
            $recentData = $farm->weatherData()
                ->where('recorded_at', '>', Carbon::now()->subHour())
                ->first();

            if (!$recentData && isset($weatherData['main'])) {
                WeatherData::create([
                    'farm_id' => $farm->farm_id,
                    'temperature' => $weatherData['main']['temp'] ?? null,
                    'humidity' => $weatherData['main']['humidity'] ?? null,
                    'rainfall' => $weatherData['rain']['1h'] ?? 0,
                    'wind_speed' => $weatherData['wind']['speed'] ?? null,
                    'condition' => $weatherData['weather'][0]['main'] ?? null,
                    'recorded_at' => now(),
                ]);
            }
        }
    }

    private function generateFarmingTips($weather)
    {
        $tips = [];
        
        if (isset($weather['weather'][0]['main'])) {
            $condition = strtolower($weather['weather'][0]['main']);
            
            if (strpos($condition, 'rain') !== false) {
                $tips[] = "Keep an eye on weather forecasts and plan fieldwork during breaks in the rain.";
                $tips[] = "Have an emergency plan for flash floods (moving livestock, securing equipment).";
                $tips[] = "Plant trees or hedgerows around fields as windbreaks and to absorb excess water.";
            } elseif (strpos($condition, 'clear') !== false || strpos($condition, 'sun') !== false) {
                $tips[] = "Great weather for planting and harvesting activities.";
                $tips[] = "Consider early morning work to avoid peak heat hours.";
                $tips[] = "Ensure adequate irrigation for your crops.";
            } elseif (strpos($condition, 'cloud') !== false) {
                $tips[] = "Good conditions for transplanting seedlings.";
                $tips[] = "Monitor humidity levels for optimal plant growth.";
            }
        }
        
        if (empty($tips)) {
            $tips[] = "Check soil moisture before planting.";
            $tips[] = "Monitor local weather patterns for best farming decisions.";
            $tips[] = "Regular maintenance of farm equipment is essential.";
        }
        
        return $tips;
    }

    private function getWeatherStatistics($farms)
    {
        if ($farms->isEmpty()) {
            return null;
        }

        $allWeatherData = WeatherData::whereIn('farm_id', $farms->pluck('farm_id'))
            ->where('recorded_at', '>=', Carbon::now()->subDays(7))
            ->get();

        return [
            'avg_temperature' => $allWeatherData->avg('temperature'),
            'avg_humidity' => $allWeatherData->avg('humidity'),
            'total_rainfall' => $allWeatherData->sum('rainfall'),
            'avg_wind_speed' => $allWeatherData->avg('wind_speed'),
        ];
    }
}
