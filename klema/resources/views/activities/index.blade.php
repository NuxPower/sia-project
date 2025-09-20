@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Farming Activities</h3>
                    <a href="{{ route('activities.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Activity
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Activity Type</th>
                                    <th>Field</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Weather Warning</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activities as $activity)
                                    <tr>
                                        <td>{{ $activity->activity_type }}</td>
                                        <td>{{ $activity->field }}</td>
                                        <td>{{ $activity->start_date->format('M d, Y') }}</td>
                                        <td>{{ $activity->end_date ? $activity->end_date->format('M d, Y') : 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $activity->status_color }}">
                                                {{ ucfirst($activity->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($activity->hasWeatherWarning())
                                                <span class="text-warning">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    {{ $activity->weather_warning }}
                                                </span>
                                            @else
                                                <span class="text-success">
                                                    <i class="fas fa-check-circle"></i>
                                                    Good
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('activities.show', $activity) }}" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('activities.edit', $activity) }}" class="btn btn-sm btn-outline-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('activities.destroy', $activity) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                            onclick="return confirm('Are you sure you want to delete this activity?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            No activities found. <a href="{{ route('activities.create') }}">Create your first activity</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
