<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Farm Data Export</title>
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
    <h1>Farm Data Export</h1>
    <p><strong>Generated:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>Farm Name</th>
                <th>Owner</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Size (Hectares)</th>
                <th>Soil Type</th>
                <th>Points Count</th>
                <th>Latest Temp (°C)</th>
                <th>Active Alerts</th>
            </tr>
        </thead>
        <tbody>
            @forelse($farms as $farm)
            <tr>
                <td>{{ $farm->farm_name }}</td>
                <td>{{ $farm->user->name ?? 'N/A' }}</td>
                <td>{{ number_format($farm->latitude, 6) }}</td>
                <td>{{ number_format($farm->longitude, 6) }}</td>
                <td>{{ $farm->size_hectares ?? 'N/A' }}</td>
                <td>{{ $farm->soil_type ?? 'N/A' }}</td>
                <td>{{ $farm->farmPoints->count() }}</td>
                <td>{{ $farm->weatherData->first() ? number_format($farm->weatherData->first()->temperature, 2) : 'N/A' }}</td>
                <td>{{ $farm->alerts->where('resolved', false)->count() }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align: center;">No farms found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

