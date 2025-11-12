<?php

// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\WeatherService;
use App\Models\Farm;
use App\Models\Alert;
use App\Models\WeatherData;
use App\Models\User;
use App\Models\Activity;
use Illuminate\Support\Str;
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
        
        // Get all farms
        $farmsQuery = Farm::with(['weatherData' => function($query) {
            $query->latest('recorded_at')->limit(1);
        }, 'alerts' => function($query) {
            $query->where('resolved', false);
        }, 'user']);
        
        $farms = $farmsQuery->get();

        // Get current weather data from API
        $currentWeather = $this->weatherService->getCurrentWeather($location);
        $forecast = $this->weatherService->getForecast($location);
        
        // Store weather data for farms if they have any
        if ($farms->isNotEmpty()) {
            $this->storeWeatherDataForFarms($farms, $currentWeather);
        }
        
        // Get farming tips based on weather
        $farmingTips = $this->generateFarmingTips($currentWeather);
        
        // Get recent alerts - all alerts
        $recentAlerts = Alert::with('farm.user')
            ->where('resolved', false)
            ->orderBy('issued_at', 'desc')
            ->limit(5)
            ->get();

        // Get weather statistics - all farms
        $weatherStats = $this->getWeatherStatistics($farms);
        
        // Get system-wide statistics
        $systemStats = $this->getSystemStatistics();

        return view('dashboard', compact(
            'currentWeather', 
            'forecast', 
            'farmingTips', 
            'farms', 
            'recentAlerts', 
            'location',
            'weatherStats',
            'systemStats'
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
            $recentData = $farm->weatherData()
                ->where('recorded_at', '>', Carbon::now()->subHour())
                ->exists();

            if ($recentData || !isset($weatherData['main'])) {
                continue;
            }

            $this->weatherService->storeWeatherSnapshot($weatherData, [
                'farm_id' => $farm->farm_id,
                'location' => $farm->farm_name,
                'lat' => $farm->latitude,
                'lon' => $farm->longitude,
            ]);
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

        $query = WeatherData::whereIn('farm_id', $farms->pluck('farm_id'))
            ->where('recorded_at', '>=', Carbon::now()->subDays(7));

        $allWeatherData = $query->get();

        return [
            'avg_temperature' => $allWeatherData->avg('temperature'),
            'avg_humidity' => $allWeatherData->avg('humidity'),
            'total_rainfall' => $allWeatherData->sum('rainfall'),
            'avg_wind_speed' => $allWeatherData->avg('wind_speed'),
        ];
    }

    /**
     * Get system-wide statistics for admin dashboard.
     */
    private function getSystemStatistics()
    {
        $totalFarms = Farm::count();
        $totalUsers = User::count();
        $totalAlerts = Alert::where('resolved', false)->count();
        $totalActivities = Activity::where('start_date', '>=', Carbon::now()->subDays(30))->count();
        
        $recentWeatherData = WeatherData::where('recorded_at', '>=', Carbon::now()->subDays(7))->get();
        
        return [
            'total_farms' => $totalFarms,
            'total_users' => $totalUsers,
            'active_alerts' => $totalAlerts,
            'recent_activities' => $totalActivities,
            'weather_data_points' => $recentWeatherData->count(),
            'avg_temperature' => $recentWeatherData->avg('temperature'),
            'avg_humidity' => $recentWeatherData->avg('humidity'),
            'total_rainfall' => $recentWeatherData->sum('rainfall'),
        ];
    }

    /**
     * API endpoint to get system statistics.
     */
    public function getSystemStats(Request $request)
    {
        $stats = $this->getSystemStatistics();

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * API endpoint to get all farmers with their farms.
     * This helps see which farmer is registered to which farm.
     */
    public function getFarmersWithFarms(Request $request): JsonResponse
    {
        $farmers = User::where('role', 'farmer')
            ->with(['farms' => function($query) {
                $query->withCount(['alerts' => function($q) {
                    $q->where('resolved', false);
                }]);
            }])
            ->get()
            ->map(function($farmer) {
                return [
                    'user_id' => $farmer->id,
                    'name' => $farmer->name,
                    'email' => $farmer->email,
                    'email_verified_at' => $farmer->email_verified_at,
                    'farms_count' => $farmer->farms->count(),
                    'farms' => $farmer->farms->map(function($farm) {
                        return [
                            'farm_id' => $farm->farm_id,
                            'farm_name' => $farm->farm_name,
                            'latitude' => $farm->latitude,
                            'longitude' => $farm->longitude,
                            'size_hectares' => $farm->size_hectares,
                            'soil_type' => $farm->soil_type,
                            'active_alerts_count' => $farm->alerts_count ?? 0,
                            'created_at' => $farm->created_at,
                        ];
                    }),
                ];
            });

        return response()->json([
            'success' => true,
            'farmers' => $farmers,
            'total_farmers' => $farmers->count(),
        ]);
    }
}
