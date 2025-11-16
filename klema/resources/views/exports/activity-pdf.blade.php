<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Activity Data Export</title>
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
    <h1>Activity Data Export</h1>
    @if($startDate || $endDate)
    <p><strong>Date Range:</strong> 
        @if($startDate) {{ $startDate }} @endif
        @if($startDate && $endDate) to @endif
        @if($endDate) {{ $endDate }} @endif
    </p>
    @endif
    <p><strong>Generated:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Activity Type</th>
                <th>Field</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th>Weather Warning</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activities as $activity)
            <tr>
                <td>{{ $activity->user->name ?? 'N/A' }}</td>
                <td>{{ $activity->activity_type }}</td>
                <td>{{ $activity->field }}</td>
                <td>{{ $activity->start_date->format('Y-m-d') }}</td>
                <td>{{ $activity->end_date ? $activity->end_date->format('Y-m-d') : 'N/A' }}</td>
                <td>{{ ucfirst($activity->status ?? 'pending') }}</td>
                <td>{{ $activity->weather_warning ?? 'None' }}</td>
                <td>{{ $activity->notes ?? '' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center;">No activities found for the selected period.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

