<?php

namespace App\Http\Controllers;

use App\Models\Export;
use App\Models\WeatherData;
use App\Models\Farm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ExportController extends Controller
{
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

        $query = WeatherData::with('farm')
            ->whereBetween('recorded_at', [$validated['start_date'], $validated['end_date']]);

        if ($validated['farm_id']) {
            $query->where('farm_id', $validated['farm_id']);
        } else {
            $query->whereHas('farm', function($q) {
                $q->where('user_id', auth()->id());
            });
        }

        $weatherData = $query->get();

        // Generate CSV
        $csvContent = "Farm Name,Temperature,Humidity,Rainfall,Wind Speed,Condition,Recorded At\n";
        foreach ($weatherData as $data) {
            $csvContent .= sprintf(
                "%s,%.2f,%.2f,%.2f,%.2f,%s,%s\n",
                $data->farm->farm_name,
                $data->temperature ?? 0,
                $data->humidity ?? 0,
                $data->rainfall ?? 0,
                $data->wind_speed ?? 0,
                $data->condition ?? 'Unknown',
                $data->recorded_at->format('Y-m-d H:i:s')
            );
        }

        $fileName = 'weather_data_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';
        $filePath = 'exports/' . $fileName;
        
        Storage::put($filePath, $csvContent);

        $export = Export::create([
            'user_id' => auth()->id(),
            'file_name' => $fileName,
            'file_path' => $filePath,
        ]);

        return redirect()->route('exports.index')
            ->with('success', 'Weather data exported successfully!');
    }

    public function exportFarmData(Request $request)
    {
        $farms = auth()->user()->farms()->with(['farmPoints', 'weatherData', 'alerts'])->get();

        // Generate CSV
        $csvContent = "Farm Name,Latitude,Longitude,Points Count,Latest Temperature,Active Alerts\n";
        foreach ($farms as $farm) {
            $latestWeather = $farm->weatherData->first();
            $activeAlerts = $farm->alerts->where('resolved', false)->count();
            
            $csvContent .= sprintf(
                "%s,%.6f,%.6f,%d,%.2f,%d\n",
                $farm->farm_name,
                $farm->latitude,
                $farm->longitude,
                $farm->farmPoints->count(),
                $latestWeather ? $latestWeather->temperature : 0,
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
        ]);

        return redirect()->route('exports.index')
            ->with('success', 'Farm data exported successfully!');
    }

    public function download(Export $export)
    {
        $this->authorize('view', $export);
        
        if (!Storage::exists($export->file_path)) {
            abort(404, 'Export file not found');
        }

        return Storage::download($export->file_path, $export->file_name);
    }
}