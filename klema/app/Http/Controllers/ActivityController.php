<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;
use App\Services\WeatherService;
use Carbon\Carbon;

class ActivityController extends Controller
{
    protected $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    public function index()
    {
        $activities = Activity::where('user_id', auth()->id())
            ->orderBy('start_date', 'desc')
            ->get();
        
        return view('activities.index', compact('activities'));
    }

    public function create()
    {
        return view('activities.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'activity_type' => 'required|string|max:255',
            'field' => 'required|string|max:255',
            'start_date' => 'required|date',
        ]);

        // Check weather suitability
        $weatherCheck = $this->checkWeatherSuitability($validated['start_date']);
        
        $activity = Activity::create([
            'user_id' => auth()->id(),
            'activity_type' => $validated['activity_type'],
            'field' => $validated['field'],
            'start_date' => $validated['start_date'],
            'weather_warning' => $weatherCheck['warning'] ?? null,
        ]);

        if ($weatherCheck['suitable']) {
            return redirect()->route('activities.index')
                ->with('success', 'Activity scheduled successfully!');
        } else {
            return redirect()->route('activities.index')
                ->with('warning', 'Activity scheduled, but weather conditions may not be favorable. ' . $weatherCheck['message']);
        }
    }

    public function checkSuitability($date)
    {
        $weatherCheck = $this->checkWeatherSuitability($date);
        
        return response()->json([
            'suitable' => $weatherCheck['suitable'],
            'message' => $weatherCheck['message'],
            'warning' => $weatherCheck['warning'] ?? null
        ]);
    }

    private function checkWeatherSuitability($date)
    {
        $targetDate = Carbon::parse($date);
        $now = Carbon::now();
        
        // Get historical weather data for the same date last year
        $lastYear = $targetDate->copy()->subYear();
        $historicalWeather = $this->weatherService->getHistoricalWeather('Butuan, Caraga, PH', $lastYear);
        
        $suitable = true;
        $message = '';
        $warning = null;
        
        if ($historicalWeather) {
            // Check for adverse conditions based on historical data
            $condition = strtolower($historicalWeather['weather'][0]['main'] ?? '');
            $rainfall = $historicalWeather['rain']['1h'] ?? 0;
            $windSpeed = $historicalWeather['wind']['speed'] ?? 0;
            
            if (strpos($condition, 'rain') !== false || $rainfall > 5) {
                $suitable = false;
                $message = 'Based on last year\'s data for this month, the selected activity may not be suitable due to unfavorable weather and farm conditions.';
                $warning = 'Heavy rainfall expected';
            } elseif ($windSpeed > 10) {
                $suitable = false;
                $message = 'High wind conditions may affect farming activities.';
                $warning = 'High wind conditions';
            }
        } else {
            $message = 'Weather data not available for suitability check.';
        }
        
        return [
            'suitable' => $suitable,
            'message' => $message,
            'warning' => $warning
        ];
    }

    public function show(Activity $activity)
    {
        $this->authorize('view', $activity);
        return view('activities.show', compact('activity'));
    }

    public function edit(Activity $activity)
    {
        $this->authorize('update', $activity);
        return view('activities.edit', compact('activity'));
    }

    public function update(Request $request, Activity $activity)
    {
        $this->authorize('update', $activity);
        
        $validated = $request->validate([
            'activity_type' => 'required|string|max:255',
            'field' => 'required|string|max:255',
            'start_date' => 'required|date',
        ]);

        $activity->update($validated);

        return redirect()->route('activities.index')
            ->with('success', 'Activity updated successfully!');
    }

    public function destroy(Activity $activity)
    {
        $this->authorize('delete', $activity);
        
        $activity->delete();

        return redirect()->route('activities.index')
            ->with('success', 'Activity deleted successfully!');
    }
}