@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Weather Overview Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title">{{ $currentWeather['main']['temp'] ?? 'N/A' }}°C</h4>
                            <p class="card-text">Current Temperature</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-thermometer-half fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title">{{ $currentWeather['main']['humidity'] ?? 'N/A' }}%</h4>
                            <p class="card-text">Humidity</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-tint fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title">{{ $currentWeather['wind']['speed'] ?? 'N/A' }} m/s</h4>
                            <p class="card-text">Wind Speed</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-wind fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="card-title">{{ $currentWeather['weather'][0]['main'] ?? 'N/A' }}</h4>
                            <p class="card-text">Condition</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-cloud fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Farms Overview -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">My Farms</h5>
                    <a href="{{ route('farms.create') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Add Farm
                    </a>
                </div>
                <div class="card-body">
                    @if($farms->count() > 0)
                        <div class="list-group">
                            @foreach($farms as $farm)
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">{{ $farm->farm_name }}</h6>
                                        <small>{{ $farm->created_at->diffForHumans() }}</small>
                                    </div>
                                    <p class="mb-1">
                                        <i class="fas fa-map-marker-alt"></i> 
                                        {{ $farm->latitude }}, {{ $farm->longitude }}
                                    </p>
                                    @if($farm->latest_weather)
                                        <small class="text-muted">
                                            <i class="fas fa-thermometer-half"></i>
                                            {{ $farm->latest_weather->formatted_temperature }}
                                        </small>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-seedling fa-3x mb-3"></i>
                            <p>No farms registered yet.</p>
                            <a href="{{ route('farms.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Your First Farm
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Activities</h5>
                    <a href="{{ route('activities.index') }}" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    @if(auth()->user()->activities()->count() > 0)
                        <div class="list-group">
                            @foreach(auth()->user()->activities()->latest()->limit(5)->get() as $activity)
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">{{ $activity->activity_type }}</h6>
                                        <span class="badge bg-{{ $activity->status_color }}">
                                            {{ ucfirst($activity->status) }}
                                        </span>
                                    </div>
                                    <p class="mb-1">{{ $activity->field }}</p>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i>
                                        {{ $activity->start_date->format('M d, Y') }}
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-tasks fa-3x mb-3"></i>
                            <p>No activities scheduled yet.</p>
                            <a href="{{ route('activities.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Schedule Activity
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Weather Forecast -->
    @if(isset($forecast) && count($forecast) > 0)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">7-Day Weather Forecast</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($forecast as $day)
                                <div class="col-md-2 col-sm-4 col-6 mb-3">
                                    <div class="card text-center">
                                        <div class="card-body">
                                            <h6 class="card-title">{{ $day['day'] }}</h6>
                                            <p class="text-muted small">{{ $day['date'] }}</p>
                                            <div class="mb-2">
                                                <i class="fas fa-thermometer-half"></i>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-primary">{{ $day['temp_max'] }}°</span>
                                                <span class="text-muted">{{ $day['temp_min'] }}°</span>
                                            </div>
                                            <small class="text-muted">{{ $day['condition'] }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Recent Alerts -->
    @if(isset($recentAlerts) && $recentAlerts->count() > 0)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Alerts</h5>
                    </div>
                    <div class="card-body">
                        @foreach($recentAlerts as $alert)
                            <div class="alert alert-{{ $alert->alert_type === 'weather' ? 'warning' : 'info' }} alert-dismissible fade show">
                                <i class="{{ $alert->alert_type_icon }}"></i>
                                <strong>{{ ucfirst($alert->alert_type) }} Alert:</strong>
                                {{ $alert->message }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
