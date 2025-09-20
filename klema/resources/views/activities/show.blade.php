@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Activity Details</h3>
                    <div class="btn-group">
                        <a href="{{ route('activities.edit', $activity) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('activities.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Activity Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Activity Type:</strong></td>
                                    <td>{{ $activity->activity_type }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Field/Location:</strong></td>
                                    <td>{{ $activity->field }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Start Date:</strong></td>
                                    <td>{{ $activity->start_date->format('F d, Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>End Date:</strong></td>
                                    <td>{{ $activity->end_date ? $activity->end_date->format('F d, Y') : 'Not specified' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $activity->status_color }}">
                                            {{ ucfirst($activity->status) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>Weather Information</h5>
                            <div class="alert alert-{{ $activity->hasWeatherWarning() ? 'warning' : 'success' }}">
                                @if($activity->hasWeatherWarning())
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Weather Warning:</strong><br>
                                    {{ $activity->weather_warning }}
                                @else
                                    <i class="fas fa-check-circle"></i>
                                    <strong>Weather Conditions:</strong><br>
                                    Suitable for this activity
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($activity->notes)
                        <div class="mt-4">
                            <h5>Notes</h5>
                            <div class="card">
                                <div class="card-body">
                                    {{ $activity->notes }}
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="mt-4">
                        <h5>Activity Timeline</h5>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6>Activity Created</h6>
                                    <p class="text-muted">{{ $activity->created_at->format('F d, Y \a\t g:i A') }}</p>
                                </div>
                            </div>
                            
                            <div class="timeline-item">
                                <div class="timeline-marker bg-{{ $activity->status_color }}"></div>
                                <div class="timeline-content">
                                    <h6>Start Date</h6>
                                    <p class="text-muted">{{ $activity->start_date->format('F d, Y') }}</p>
                                </div>
                            </div>
                            
                            @if($activity->end_date)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-info"></div>
                                    <div class="timeline-content">
                                        <h6>End Date</h6>
                                        <p class="text-muted">{{ $activity->end_date->format('F d, Y') }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    border-left: 3px solid #007bff;
}
</style>
@endsection
