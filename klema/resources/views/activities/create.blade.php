@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Add New Activity</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('activities.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="activity_type" class="form-label">Activity Type *</label>
                                    <select class="form-select @error('activity_type') is-invalid @enderror" 
                                            id="activity_type" name="activity_type" required>
                                        <option value="">Select Activity Type</option>
                                        <option value="Planting" {{ old('activity_type') == 'Planting' ? 'selected' : '' }}>Planting</option>
                                        <option value="Harvesting" {{ old('activity_type') == 'Harvesting' ? 'selected' : '' }}>Harvesting</option>
                                        <option value="Irrigation" {{ old('activity_type') == 'Irrigation' ? 'selected' : '' }}>Irrigation</option>
                                        <option value="Fertilization" {{ old('activity_type') == 'Fertilization' ? 'selected' : '' }}>Fertilization</option>
                                        <option value="Pest Control" {{ old('activity_type') == 'Pest Control' ? 'selected' : '' }}>Pest Control</option>
                                        <option value="Soil Preparation" {{ old('activity_type') == 'Soil Preparation' ? 'selected' : '' }}>Soil Preparation</option>
                                        <option value="Pruning" {{ old('activity_type') == 'Pruning' ? 'selected' : '' }}>Pruning</option>
                                        <option value="Weeding" {{ old('activity_type') == 'Weeding' ? 'selected' : '' }}>Weeding</option>
                                        <option value="Other" {{ old('activity_type') == 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('activity_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="field" class="form-label">Field/Location *</label>
                                    <input type="text" class="form-control @error('field') is-invalid @enderror" 
                                           id="field" name="field" value="{{ old('field') }}" required>
                                    @error('field')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date *</label>
                                    <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                                           id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date (Optional)</label>
                                    <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                                           id="end_date" name="end_date" value="{{ old('end_date') }}">
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('activities.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Activities
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Activity
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('start_date').addEventListener('change', function() {
    const startDate = new Date(this.value);
    const endDateInput = document.getElementById('end_date');
    
    if (startDate) {
        endDateInput.min = this.value;
    }
});
</script>
@endsection
