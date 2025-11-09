<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityApiController extends Controller
{
    public function __construct(private WeatherService $weatherService)
    {
    }

    /**
     * Return the authenticated user's activities for the given month.
     */
    public function index(Request $request): JsonResponse
    {
        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);

        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = (clone $startOfMonth)->endOfMonth();

        $activities = Activity::where('user_id', auth()->id())
            ->whereBetween('start_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->orderBy('start_date')
            ->get();

        return response()->json([
            'success' => true,
            'activities' => $activities,
        ]);
    }

    /**
     * Store a newly created activity for the authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'activity_type' => 'required|string|max:255',
            'field' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $weatherCheck = $this->checkWeatherSuitability($validated['start_date']);

        $activity = Activity::create([
            'user_id' => auth()->id(),
            'activity_type' => $validated['activity_type'],
            'field' => $validated['field'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'status' => $validated['status'] ?? 'pending',
            'notes' => $validated['notes'] ?? null,
            'weather_warning' => $weatherCheck['warning'],
        ]);

        return response()->json([
            'success' => true,
            'activity' => $activity,
            'message' => $weatherCheck['message'],
            'suitable' => $weatherCheck['suitable'],
        ], 201);
    }

    /**
     * Basic weather suitability check reused from the web controller.
     */
    private function checkWeatherSuitability(string $date): array
    {
        $targetDate = Carbon::parse($date);

        $lastYear = $targetDate->copy()->subYear();
        $historicalWeather = $this->weatherService->getHistoricalWeather('Butuan, Caraga, PH', $lastYear);

        $suitable = true;
        $message = '';
        $warning = null;

        if ($historicalWeather) {
            $condition = strtolower($historicalWeather['weather'][0]['main'] ?? '');
            $rainfall = $historicalWeather['rain']['1h'] ?? 0;
            $windSpeed = $historicalWeather['wind']['speed'] ?? 0;

            if (str_contains($condition, 'rain') || $rainfall > 5) {
                $suitable = false;
                $message = "Activity scheduled, but last year's data indicates possible heavy rainfall.";
                $warning = 'Heavy rainfall expected';
            } elseif ($windSpeed > 10) {
                $suitable = false;
                $message = 'Activity scheduled, but historical wind speeds were high for this date.';
                $warning = 'High wind conditions';
            }
        } else {
            $message = 'Activity scheduled. Weather data not available for suitability check.';
        }

        return [
            'suitable' => $suitable,
            'message' => $message,
            'warning' => $warning,
        ];
    }
}
