<?php

namespace App\Http\Controllers;

use App\Models\Export;
use App\Models\Farm;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ExportController extends Controller
{
    protected $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    public function index()
    {
        $exports = auth()->user()->exports()
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return view('exports.index', compact('exports'));
    }

    public function exportWeatherData(Request $request)
    {
        $validated = $request->validate([
            'farm_id' => 'nullable|exists:farms,farm_id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Get farms to export
        $farmsQuery = Farm::where('user_id', auth()->id());
        if ($validated['farm_id']) {
            $farmsQuery->where('farm_id', $validated['farm_id']);
        }
        $farms = $farmsQuery->get();

        if ($farms->isEmpty()) {
            return redirect()->route('exports.index')
                ->with('error', 'No farms found to export weather data.');
        }

        // Generate CSV header
        $csvContent = "Farm Name,Date,Temperature,Humidity,Rainfall,Wind Speed,Condition\n";

        // Fetch weather data from API for each farm
        foreach ($farms as $farm) {
            if (!$farm->latitude || !$farm->longitude) {
                continue;
            }

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $days = $startDate->diffInDays($endDate) + 1;

            // Fetch historical weather data from API
            $series = $this->weatherService->fetchHistoricalSeriesByCoordinates(
                (float) $farm->latitude,
                (float) $farm->longitude,
                $startDate,
                $endDate
            );

            // Add data to CSV
            foreach ($series as $date => $entry) {
                $temp = data_get($entry, 'main.temp', 0);
                $humidity = data_get($entry, 'main.humidity', 0);
                $rainfall = data_get($entry, 'precipitation_sum', 0);
                $windSpeed = data_get($entry, 'wind.speed', 0);
                $condition = data_get($entry, 'weather.0.main', 'Unknown');

                $csvContent .= sprintf(
                    "%s,%s,%.2f,%.2f,%.2f,%.2f,%s\n",
                    $farm->farm_name,
                    $date,
                    $temp,
                    $humidity,
                    $rainfall,
                    $windSpeed,
                    $condition
                );
            }
        }

        $fileName = 'weather_data_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';
        $filePath = 'exports/' . $fileName;
        
        Storage::put($filePath, $csvContent);

        $export = Export::create([
            'user_id' => auth()->id(),
            'file_name' => $fileName,
            'file_path' => $filePath,
            'disk' => 'local',
        ]);

        return redirect()->route('exports.index')
            ->with('success', 'Weather data exported successfully!');
    }

    public function exportFarmData(Request $request)
    {
        $farms = auth()->user()->farms()->with(['farmPoints', 'alerts'])->get();

        // Generate CSV
        $csvContent = "Farm Name,Latitude,Longitude,Points Count,Current Temperature,Active Alerts\n";
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
            
            $csvContent .= sprintf(
                "%s,%.6f,%.6f,%d,%.2f,%d\n",
                $farm->farm_name,
                $farm->latitude,
                $farm->longitude,
                $farm->farmPoints->count(),
                $currentTemp,
                $activeAlerts
            );
        }

        $fileName = 'farm_data_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';
        $filePath = 'exports/' . $fileName;
        
        Storage::put($filePath, $csvContent);

        $export = Export::create([
            'user_id' => auth()->id(),
            'file_name' => $fileName,
            'file_path' => $filePath,
            'disk' => 'local',
        ]);

        return redirect()->route('exports.index')
            ->with('success', 'Farm data exported successfully!');
    }

    public function download(Export $export)
    {
        $this->authorize('view', $export);
        
        $disk = $export->disk ?? 'local';
        
        if (!Storage::disk($disk)->exists($export->file_path)) {
            abort(404, 'Export file not found');
        }

        return Storage::disk($disk)->download($export->file_path, $export->file_name);
    }
}