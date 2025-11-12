<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Weather Data Export</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h1>Weather Data Export</h1>
    <p><strong>Date Range:</strong> {{ $startDate }} to {{ $endDate }}</p>
    <p><strong>Generated:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>Farm Name</th>
                <th>Temperature (°C)</th>
                <th>Humidity (%)</th>
                <th>Rainfall (mm)</th>
                <th>Wind Speed (m/s)</th>
                <th>Condition</th>
                <th>Recorded At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($weatherData as $data)
            <tr>
                <td>{{ $data->farm ? $data->farm->farm_name : 'N/A' }}</td>
                <td>{{ number_format($data->temperature ?? 0, 2) }}</td>
                <td>{{ number_format($data->humidity ?? 0, 2) }}</td>
                <td>{{ number_format($data->rainfall ?? 0, 2) }}</td>
                <td>{{ number_format($data->wind_speed ?? 0, 2) }}</td>
                <td>{{ $data->condition ?? 'Unknown' }}</td>
                <td>{{ $data->recorded_at->format('Y-m-d H:i:s') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center;">No weather data found for the selected period.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

