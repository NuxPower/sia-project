<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Export;
use App\Models\Farm;
use App\Models\Activity;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportApiController extends Controller
{
    protected $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * Get list of exports for the authenticated user
     */
    public function index(): JsonResponse
    {
        $exports = auth()->user()->exports()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($export) {
                return [
                    'export_id' => $export->export_id,
                    'file_name' => $export->file_name,
                    'file_size' => $export->file_size,
                    'created_at' => $export->created_at->toISOString(),
                    'download_url' => route('api.exports.download', $export->export_id),
                ];
            });

        return response()->json([
            'success' => true,
            'exports' => $exports,
        ]);
    }

    /**
     * Export weather data
     */
    public function exportWeatherData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => 'nullable|exists:farms,farm_id',
            'start_date' => 'required|date|before_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date|before_or_equal:today',
            'format' => 'nullable|in:csv,pdf',
        ]);

        $format = $validated['format'] ?? 'csv';

        // Get farms to export
        $farmsQuery = Farm::where('user_id', auth()->id());
        if ($validated['farm_id']) {
            $farmsQuery->where('farm_id', $validated['farm_id']);
        }
        $farms = $farmsQuery->get();

        if ($farms->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No farms found to export weather data.',
            ], 404);
        }

        // Collect weather data
        $weatherData = [];
        foreach ($farms as $farm) {
            if (!$farm->latitude || !$farm->longitude) {
                continue;
            }

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            // Fetch historical weather data from API
            try {
                $series = $this->weatherService->fetchHistoricalSeriesByCoordinates(
                    (float) $farm->latitude,
                    (float) $farm->longitude,
                    $startDate,
                    $endDate
                );

                foreach ($series as $date => $entry) {
                    $weatherData[] = [
                        'farm_name' => $farm->farm_name,
                        'date' => $date,
                        'temperature' => data_get($entry, 'main.temp', 0),
                        'humidity' => data_get($entry, 'main.humidity', 0),
                        'rainfall' => data_get($entry, 'precipitation_sum', 0),
                        'wind_speed' => data_get($entry, 'wind.speed', 0),
                        'condition' => data_get($entry, 'weather.0.main', 'Unknown'),
                    ];
                }
            } catch (\Exception $e) {
                // Log error but continue with other farms
                \Log::error('Failed to fetch weather data for farm ' . $farm->farm_id . ': ' . $e->getMessage());
            }
        }

        if (empty($weatherData)) {
            return response()->json([
                'success' => false,
                'message' => 'No weather data found for the specified date range.',
            ], 404);
        }

        // Generate file
        $fileName = 'weather_data_' . Carbon::now()->format('Y-m-d_H-i-s') . '.' . $format;
        $filePath = 'exports/' . $fileName;

        if ($format === 'pdf') {
            $this->generateWeatherPdf($weatherData, $filePath, $validated);
        } else {
            $this->generateWeatherCsv($weatherData, $filePath);
        }

        $export = Export::create([
            'user_id' => auth()->id(),
            'file_name' => $fileName,
            'file_path' => $filePath,
            'disk' => 'local',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Weather data exported successfully.',
            'export' => [
                'export_id' => $export->export_id,
                'file_name' => $export->file_name,
                'file_size' => $export->file_size,
                'created_at' => $export->created_at->toISOString(),
                'download_url' => route('api.exports.download', $export->export_id),
            ],
        ]);
    }

    /**
     * Export farm data
     */
    public function exportFarmData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'format' => 'nullable|in:csv,pdf',
        ]);

        $format = $validated['format'] ?? 'csv';
        $farms = auth()->user()->farms()->with(['farmPoints', 'alerts'])->get();

        if ($farms->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No farms found to export.',
            ], 404);
        }

        // Collect farm data
        $farmData = [];
        foreach ($farms as $farm) {
            // Fetch current weather from API
            $currentTemp = 0;
            if ($farm->latitude && $farm->longitude) {
                try {
                    $currentWeather = $this->weatherService->getCurrentWeatherByCoordinates(
                        (float) $farm->latitude,
                        (float) $farm->longitude
                    );
                    $currentTemp = data_get($currentWeather, 'main.temp', 0);
                } catch (\Exception $e) {
                    // If API fails, just use 0
                }
            }

            $activeAlerts = $farm->alerts->where('resolved', false)->count();
            
            $farmData[] = [
                'farm_name' => $farm->farm_name,
                'latitude' => $farm->latitude,
                'longitude' => $farm->longitude,
                'points_count' => $farm->farmPoints->count(),
                'current_temperature' => $currentTemp,
                'active_alerts' => $activeAlerts,
            ];
        }

        // Generate file
        $fileName = 'farm_data_' . Carbon::now()->format('Y-m-d_H-i-s') . '.' . $format;
        $filePath = 'exports/' . $fileName;

        if ($format === 'pdf') {
            $this->generateFarmPdf($farmData, $filePath);
        } else {
            $this->generateFarmCsv($farmData, $filePath);
        }

        $export = Export::create([
            'user_id' => auth()->id(),
            'file_name' => $fileName,
            'file_path' => $filePath,
            'disk' => 'local',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Farm data exported successfully.',
            'export' => [
                'export_id' => $export->export_id,
                'file_name' => $export->file_name,
                'file_size' => $export->file_size,
                'created_at' => $export->created_at->toISOString(),
                'download_url' => route('api.exports.download', $export->export_id),
            ],
        ]);
    }

    /**
     * Export activity data
     */
    public function exportActivityData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format' => 'nullable|in:csv,pdf',
        ]);

        $format = $validated['format'] ?? 'csv';

        // Get activities
        $activitiesQuery = Activity::where('user_id', auth()->id());
        
        if (isset($validated['start_date'])) {
            $activitiesQuery->where('start_date', '>=', $validated['start_date']);
        }
        
        if (isset($validated['end_date'])) {
            $activitiesQuery->where('start_date', '<=', $validated['end_date']);
        }
        
        $activities = $activitiesQuery->orderBy('start_date', 'desc')->get();

        if ($activities->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No activities found to export.',
            ], 404);
        }

        // Collect activity data
        $activityData = [];
        foreach ($activities as $activity) {
            $activityData[] = [
                'activity_type' => $activity->activity_type,
                'field' => $activity->field,
                'start_date' => $activity->start_date->format('Y-m-d'),
                'end_date' => $activity->end_date ? $activity->end_date->format('Y-m-d') : 'N/A',
                'status' => $activity->status ?? 'scheduled',
                'weather_warning' => $activity->weather_warning ?? 'No weather warning logged',
                'notes' => $activity->notes ?? 'N/A',
            ];
        }

        // Generate file
        $fileName = 'activity_data_' . Carbon::now()->format('Y-m-d_H-i-s') . '.' . $format;
        $filePath = 'exports/' . $fileName;

        if ($format === 'pdf') {
            $this->generateActivityPdf($activityData, $filePath, $validated);
        } else {
            $this->generateActivityCsv($activityData, $filePath);
        }

        $export = Export::create([
            'user_id' => auth()->id(),
            'file_name' => $fileName,
            'file_path' => $filePath,
            'disk' => 'local',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Activity data exported successfully.',
            'export' => [
                'export_id' => $export->export_id,
                'file_name' => $export->file_name,
                'file_size' => $export->file_size,
                'created_at' => $export->created_at->toISOString(),
                'download_url' => route('api.exports.download', $export->export_id),
            ],
        ]);
    }

    /**
     * Generate CSV file for weather data
     */
    protected function generateWeatherCsv(array $data, string $filePath): void
    {
        $csvContent = "Farm Name,Date,Temperature,Humidity,Rainfall,Wind Speed,Condition\n";
        
        foreach ($data as $row) {
            $csvContent .= sprintf(
                "%s,%s,%.2f,%.2f,%.2f,%.2f,%s\n",
                $row['farm_name'],
                $row['date'],
                $row['temperature'],
                $row['humidity'],
                $row['rainfall'],
                $row['wind_speed'],
                $row['condition']
            );
        }

        Storage::put($filePath, $csvContent);
    }

    /**
     * Generate PDF file for weather data
     */
    protected function generateWeatherPdf(array $data, string $filePath, array $filters): void
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Weather Data Export</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .footer { margin-top: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Weather Data Export</h1>';
        
        if (isset($filters['farm_id']) && $filters['farm_id']) {
            $farm = Farm::find($filters['farm_id']);
            if ($farm) {
                $html .= '<p><strong>Farm:</strong> ' . htmlspecialchars($farm->farm_name) . '</p>';
            }
        } else {
            $html .= '<p><strong>Farm:</strong> All Farms</p>';
        }
        
        $html .= '<p><strong>Date Range:</strong> ' . htmlspecialchars($filters['start_date']) . ' to ' . htmlspecialchars($filters['end_date']) . '</p>
    <table>
        <thead>
            <tr>
                <th>Farm Name</th>
                <th>Date</th>
                <th>Temperature</th>
                <th>Humidity</th>
                <th>Rainfall</th>
                <th>Wind Speed</th>
                <th>Condition</th>
            </tr>
        </thead>
        <tbody>';
        
        foreach ($data as $row) {
            $html .= '<tr>
                <td>' . htmlspecialchars($row['farm_name']) . '</td>
                <td>' . htmlspecialchars($row['date']) . '</td>
                <td>' . number_format($row['temperature'], 2) . '</td>
                <td>' . number_format($row['humidity'], 2) . '</td>
                <td>' . number_format($row['rainfall'], 2) . '</td>
                <td>' . number_format($row['wind_speed'], 2) . '</td>
                <td>' . htmlspecialchars($row['condition']) . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
    </table>
    <div class="footer">Generated at: ' . Carbon::now()->format('Y-m-d H:i:s') . '</div>
</body>
</html>';

        $pdf = Pdf::loadHTML($html);
        Storage::put($filePath, $pdf->output());
    }

    /**
     * Generate CSV file for farm data
     */
    protected function generateFarmCsv(array $data, string $filePath): void
    {
        $csvContent = "Farm Name,Latitude,Longitude,Points Count,Current Temperature,Active Alerts\n";
        
        foreach ($data as $row) {
            $csvContent .= sprintf(
                "%s,%.6f,%.6f,%d,%.2f,%d\n",
                $row['farm_name'],
                $row['latitude'],
                $row['longitude'],
                $row['points_count'],
                $row['current_temperature'],
                $row['active_alerts']
            );
        }

        Storage::put($filePath, $csvContent);
    }

    /**
     * Generate PDF file for farm data
     */
    protected function generateFarmPdf(array $data, string $filePath): void
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Farm Data Export</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .footer { margin-top: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Farm Data Export</h1>
    <table>
        <thead>
            <tr>
                <th>Farm Name</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Points Count</th>
                <th>Current Temperature</th>
                <th>Active Alerts</th>
            </tr>
        </thead>
        <tbody>';
        
        foreach ($data as $row) {
            $html .= '<tr>
                <td>' . htmlspecialchars($row['farm_name']) . '</td>
                <td>' . number_format($row['latitude'], 6) . '</td>
                <td>' . number_format($row['longitude'], 6) . '</td>
                <td>' . $row['points_count'] . '</td>
                <td>' . number_format($row['current_temperature'], 2) . '</td>
                <td>' . $row['active_alerts'] . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
    </table>
    <div class="footer">Generated at: ' . Carbon::now()->format('Y-m-d H:i:s') . '</div>
</body>
</html>';

        $pdf = Pdf::loadHTML($html);
        Storage::put($filePath, $pdf->output());
    }

    /**
     * Generate CSV file for activity data
     */
    protected function generateActivityCsv(array $data, string $filePath): void
    {
        $csvContent = "Activity Type,Field,Start Date,End Date,Status,Weather Warning,Notes\n";
        
        foreach ($data as $row) {
            $csvContent .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s\n",
                $row['activity_type'],
                $row['field'],
                $row['start_date'],
                $row['end_date'],
                $row['status'],
                $row['weather_warning'],
                $row['notes']
            );
        }

        Storage::put($filePath, $csvContent);
    }

    /**
     * Generate PDF file for activity data
     */
    protected function generateActivityPdf(array $data, string $filePath, array $filters): void
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Activity Data Export</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .footer { margin-top: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Activity Data Export</h1>';
        
        if (isset($filters['start_date']) || isset($filters['end_date'])) {
            $html .= '<p><strong>Date Range:</strong> ';
            if (isset($filters['start_date'])) {
                $html .= htmlspecialchars($filters['start_date']);
            } else {
                $html .= 'All';
            }
            $html .= ' to ';
            if (isset($filters['end_date'])) {
                $html .= htmlspecialchars($filters['end_date']);
            } else {
                $html .= 'All';
            }
            $html .= '</p>';
        }
        
        $html .= '<table>
        <thead>
            <tr>
                <th>Activity Type</th>
                <th>Field</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th>Weather Warning</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>';
        
        foreach ($data as $row) {
            $html .= '<tr>
                <td>' . htmlspecialchars($row['activity_type']) . '</td>
                <td>' . htmlspecialchars($row['field']) . '</td>
                <td>' . htmlspecialchars($row['start_date']) . '</td>
                <td>' . htmlspecialchars($row['end_date']) . '</td>
                <td>' . htmlspecialchars($row['status']) . '</td>
                <td>' . htmlspecialchars($row['weather_warning']) . '</td>
                <td>' . htmlspecialchars($row['notes']) . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
    </table>
    <div class="footer">Generated at: ' . Carbon::now()->format('Y-m-d H:i:s') . '</div>
</body>
</html>';

        $pdf = Pdf::loadHTML($html);
        Storage::put($filePath, $pdf->output());
    }

    /**
     * Download an export file via the API.
     */
    public function download(Export $export)
    {
        $this->authorize('view', $export);

        $disk = $export->disk ?? 'local';

        if (!Storage::disk($disk)->exists($export->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Export file is no longer available.',
            ], 404);
        }
        
        return Storage::disk($disk)->download($export->file_path, $export->file_name);
    }
}

