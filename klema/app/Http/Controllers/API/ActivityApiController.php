<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\ActivityAdvisor;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActivityApiController extends Controller
{
    public function __construct(
        private WeatherService $weatherService,
        private ActivityAdvisor $activityAdvisor
    )
    {
    }

    /**
     * Return the authenticated user's activities for the given month.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Activity::where('user_id', auth()->id());

        $month = $request->query('month');
        $year = $request->query('year');

        if ($month !== null && $year !== null) {
            $month = (int) $month ?: now()->month;
            $year = (int) $year ?: now()->year;

            $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endOfMonth = (clone $startOfMonth)->endOfMonth();

            $query->whereBetween('start_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                ->orderBy('start_date');

            $activities = $query->get();
        } else {
            $statusFilter = $this->normalizeStatusFilter($request->query('status'));
            if (!empty($statusFilter)) {
                $query->whereIn('status', $statusFilter);
            }

            $fromDate = $this->parseDate($request->query('from_date'));
            $toDate = $this->parseDate($request->query('to_date'));

            if ($fromDate) {
                $query->where('start_date', '>=', $fromDate->startOfDay());
            }
            if ($toDate) {
                $query->where('start_date', '<=', $toDate->endOfDay());
            }

            $sortDirection = strtolower((string) $request->query('sort', 'desc')) === 'asc' ? 'asc' : 'desc';
            $query->orderBy('start_date', $sortDirection);

            $limit = (int) $request->query('limit', 50);
            $limit = max(1, min($limit, 200));
            $query->limit($limit);

            $activities = $query->get();
        }

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
            'weather_warning' => 'nullable|string|max:255',
        ]);

        if (array_key_exists('weather_warning', $validated)) {
            $validated['weather_warning'] = trim((string) $validated['weather_warning']) ?: null;
        }

        $weatherCheck = $this->checkWeatherSuitability($validated['start_date']);
        $weatherWarning = $validated['weather_warning'] ?? $weatherCheck['warning'];

        $activity = Activity::create([
            'user_id' => auth()->id(),
            'activity_type' => $validated['activity_type'],
            'field' => $validated['field'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'status' => $validated['status'] ?? 'pending',
            'notes' => $validated['notes'] ?? null,
            'weather_warning' => $weatherWarning,
        ]);

        return response()->json([
            'success' => true,
            'activity' => $activity,
            'message' => $weatherCheck['message'],
            'suitable' => $weatherCheck['suitable'],
        ], 201);
    }

    public function meta(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'types' => $this->activityAdvisor->getActivityTypes(),
        ]);
    }

    public function show(Activity $activity): JsonResponse
    {
        $this->ensureActivityOwner($activity);

        return response()->json([
            'success' => true,
            'activity' => $activity,
        ]);
    }

    public function update(Request $request, Activity $activity): JsonResponse
    {
        $this->ensureActivityOwner($activity);

        $validated = $request->validate([
            'activity_type' => 'sometimes|required|string|max:255',
            'field' => 'sometimes|required|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'sometimes|required|in:pending,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
            'weather_warning' => 'nullable|string|max:255',
        ]);

        if (array_key_exists('weather_warning', $validated)) {
            $validated['weather_warning'] = trim((string) $validated['weather_warning']) ?: null;
        }

        $weatherCheck = null;
        if (array_key_exists('start_date', $validated)) {
            $weatherCheck = $this->checkWeatherSuitability($validated['start_date']);
            $validated['weather_warning'] = $validated['weather_warning'] ?? $weatherCheck['warning'];
        }

        $activity->fill($validated);
        $activity->save();

        $response = [
            'success' => true,
            'activity' => $activity->fresh(),
            'message' => $weatherCheck['message'] ?? 'Activity updated successfully.',
        ];

        if ($weatherCheck !== null) {
            $response['suitable'] = $weatherCheck['suitable'];
        }

        return response()->json($response);
    }

    public function destroy(Activity $activity): JsonResponse
    {
        $this->ensureActivityOwner($activity);

        $activity->delete();

        return response()->json([
            'success' => true,
            'message' => 'Activity deleted successfully.',
        ]);
    }

    public function recommendation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'activity_type' => 'required|string',
            'date' => 'nullable|date',
        ]);

        $date = Carbon::parse($validated['date'] ?? now()->toDateString());
        $recommendation = $this->activityAdvisor->buildRecommendation(
            $request->user(),
            $validated['activity_type'],
            $date
        );

        return response()->json([
            'success' => true,
            'recommendation' => $recommendation,
        ]);
    }

    /**
     * Basic weather suitability check reused from the web controller.
     */
    private function checkWeatherSuitability(string $date): array
    {
        $suitable = true;
        $message = '';
        $warning = null;

        try {
            $targetDate = Carbon::parse($date);
            $comparisonDate = $targetDate->copy()->subYear();

            // Try to find historical data for the same day last year
            $historicalSnapshot = $this->weatherService
                ->findStoredHistoricalByLocation('Butuan, Caraga, PH', $comparisonDate);

            if ($historicalSnapshot) {
                $condition = strtolower($historicalSnapshot['weather'][0]['main'] ?? '');
                $rainfall = $historicalSnapshot['rain']['1h'] ?? 0;
                $windSpeed = $historicalSnapshot['wind']['speed'] ?? 0;

                if (str_contains($condition, 'rain') || $rainfall > 5) {
                    $suitable = false;
                    $message = "Activity scheduled, but last year's data indicates possible heavy rainfall.";
                    $warning = 'Heavy rainfall expected';
                } elseif ($windSpeed > 10) {
                    $suitable = false;
                    $message = 'Activity scheduled, but historical wind speeds were high for this date.';
                    $warning = 'High wind conditions';
                } else {
                    $message = 'Activity scheduled. Historical data suggests suitable conditions.';
                }
            } else {
                $message = 'Activity scheduled. Weather data not available for suitability check.';
            }
        } catch (\Throwable $e) {
            Log::warning('Weather suitability check failed', [
                'date' => $date,
                'message' => $e->getMessage(),
            ]);

            $message = 'Activity scheduled. Weather data not available for suitability check.';
        }

        return [
            'suitable' => $suitable,
            'message' => $message,
            'warning' => $warning,
        ];
    }

    private function ensureActivityOwner(Activity $activity): void
    {
        if ($activity->user_id !== auth()->id()) {
            abort(404);
        }
    }

    private function normalizeStatusFilter($status): array
    {
        if ($status === null) {
            return [];
        }

        $statuses = is_array($status) ? $status : explode(',', (string) $status);

        return array_values(array_filter(array_map(static function ($value) {
            $normalized = strtolower(trim($value));
            return in_array($normalized, ['pending', 'in_progress', 'completed', 'cancelled'], true)
                ? $normalized
                : null;
        }, $statuses)));
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $exception) {
            Log::warning('Invalid date filter supplied to activities endpoint', [
                'value' => $value,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}




