@extends('layouts.app')

@section('title', 'Calendar - KLEMA')

@section('content')
<div class="min-h-screen map-container relative overflow-hidden">
    <!-- Map Background -->
    <div class="absolute inset-0 opacity-20">
        <div id="map" class="w-full h-full"></div>
    </div>

    <!-- Calendar Content -->
    <div class="relative z-10 p-6">
        <div class="max-w-6xl mx-auto">
            <!-- Calendar Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">September, 2025</h1>
                    <p class="text-white/70">Plan your farming activities</p>
                </div>
                <button id="add-activity-btn" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl transition-all duration-200 flex items-center space-x-2">
                    <i class="fas fa-plus"></i>
                    <span>Add Activity</span>
                </button>
            </div>

            <!-- Calendar Grid -->
            <div class="weather-card rounded-3xl p-6 text-white">
                <!-- Calendar Header -->
                <div class="grid grid-cols-7 gap-4 mb-4">
                    <div class="text-center font-semibold text-white/70 py-3">SUNDAY</div>
                    <div class="text-center font-semibold text-white/70 py-3">MONDAY</div>
                    <div class="text-center font-semibold text-white/70 py-3">TUESDAY</div>
                    <div class="text-center font-semibold text-white/70 py-3">WEDNESDAY</div>
                    <div class="text-center font-semibold text-white/70 py-3">THURSDAY</div>
                    <div class="text-center font-semibold text-white/70 py-3">FRIDAY</div>
                    <div class="text-center font-semibold text-white/70 py-3">SATURDAY</div>
                </div>

                <!-- Calendar Body -->
                <div class="grid grid-cols-7 gap-4">
                    @for($day = 1; $day <= 31; $day++)
                        @php
                            $date = \Carbon\Carbon::create(2025, 9, $day);
                            $dayActivities = collect($activities)->where('start', $date->format('Y-m-d'));
                        @endphp
                        
                        <div class="calendar-day min-h-[120px] p-3 rounded-lg border border-white/10 hover:bg-white/5 transition-all duration-200 cursor-pointer"
                             data-date="{{ $date->format('Y-m-d') }}">
                            <div class="text-lg font-semibold mb-2 {{ $day === 20 ? 'text-blue-400' : '' }}">{{ $day }}</div>
                            
                            <!-- Activities for this day -->
                            <div class="space-y-1">
                                @foreach($dayActivities as $activity)
                                    <div class="text-xs bg-green-500/20 text-green-300 px-2 py-1 rounded truncate">
                                        {{ $activity['title'] }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endfor

                    <!-- Empty cells for calendar grid completion -->
                    @if($day <= 31)
                        @for($emptyDay = $day; $emptyDay <= 35; $emptyDay++)
                            <div class="calendar-day min-h-[120px] p-3 rounded-lg border border-white/10 opacity-50"></div>
                        @endfor
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Activity Modal -->
<div id="activity-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-3xl p-8 max-w-md w-full">
            <h2 class="text-2xl font-bold text-white mb-6">Add new activity</h2>
            
            <form id="activity-form" action="{{ route('activities.store') }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- Activity Type -->
                <div>
                    <label class="block text-white/80 text-sm font-medium mb-2">Activity Type</label>
                    <select name="activity_type" 
                            class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50" required>
                        <option value="" class="bg-slate-800">Select activity type</option>
                        <option value="Planting" class="bg-slate-800">Planting</option>
                        <option value="Harvesting" class="bg-slate-800">Harvesting</option>
                        <option value="Irrigation" class="bg-slate-800">Irrigation</option>
                        <option value="Fertilizing" class="bg-slate-800">Fertilizing</option>
                        <option value="Pest Control" class="bg-slate-800">Pest Control</option>
                        <option value="Land Preparation" class="bg-slate-800">Land Preparation</option>
                        <option value="Weeding" class="bg-slate-800">Weeding</option>
                    </select>
                </div>
                
                <!-- Field -->
                <div>
                    <label class="block text-white/80 text-sm font-medium mb-2">Field</label>
                    <input type="text" 
                           name="field"
                           placeholder="Enter field name or location"
                           class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-blue-500/50" required>
                </div>
                
                <!-- Start Date -->
                <div>
                    <label class="block text-white/80 text-sm font-medium mb-2">Start Date of Work</label>
                    <input type="date" 
                           name="start_date"
                           class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50" required>
                </div>
                
                <!-- Buttons -->
                <div class="flex space-x-4 pt-4">
                    <button type="button" 
                            id="reset-btn"
                            class="flex-1 px-6 py-3 bg-white/10 border border-white/20 text-white rounded-xl hover:bg-white/20 transition-all duration-200">
                        Reset
                    </button>
                    <button type="submit"
                            class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-all duration-200">
                        Add
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Weather Warning Modal -->
<div id="weather-warning-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-3xl p-8 max-w-md w-full text-center">
            <div class="mb-6">
                <i class="fas fa-exclamation-triangle text-red-400 text-4xl mb-4"></i>
                <h2 class="text-2xl font-bold text-red-400 mb-4">Warning!</h2>
                <p id="warning-message" class="text-white/90">
                    Based on last year's data for this month, the selected activity may not be suitable due to unfavorable weather and farm conditions.
                </p>
            </div>
            
            <div class="flex space-x-4">
                <button id="warning-cancel" 
                        class="flex-1 px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-all duration-200">
                    Cancel
                </button>
                <button id="warning-continue"
                        class="flex-1 px-6 py-3 bg-white/10 border border-white/20 text-white rounded-xl hover:bg-white/20 transition-all duration-200">
                    Continue
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let map;
    let selectedDate = null;
    
    // Initialize map
    function initMap() {
        const defaultCoords = [8.2451, 124.2437];
        
        map = L.map('map', {
            zoomControl: false,
            attributionControl: false,
            dragging: true,
            scrollWheelZoom: false
        }).setView(defaultCoords, 8);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
            attribution: ''
        }).addTo(map);
    }

    // Modal controls
    const activityModal = document.getElementById('activity-modal');
    const weatherWarningModal = document.getElementById('weather-warning-modal');
    const addActivityBtn = document.getElementById('add-activity-btn');
    const resetBtn = document.getElementById('reset-btn');
    const activityForm = document.getElementById('activity-form');

    // Show add activity modal
    addActivityBtn.addEventListener('click', () => {
        selectedDate = new Date().toISOString().split('T')[0];
        document.querySelector('[name="start_date"]').value = selectedDate;
        activityModal.classList.remove('hidden');
    });

    // Calendar day clicks
    document.querySelectorAll('.calendar-day').forEach(day => {
        day.addEventListener('click', function() {
            const date = this.dataset.date;
            if (date) {
                selectedDate = date;
                document.querySelector('[name="start_date"]').value = selectedDate;
                activityModal.classList.remove('hidden');
            }
        });
    });

    // Close modals when clicking outside
    [activityModal, weatherWarningModal].forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
    });

    // Reset form
    resetBtn.addEventListener('click', () => {
        activityForm.reset();
        if (selectedDate) {
            document.querySelector('[name="start_date"]').value = selectedDate;
        }
    });

    // Form submission with weather check
    activityForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const startDate = formData.get('start_date');
        
        try {
            // Check weather suitability
            const response = await fetch(`/activities/check-suitability/${startDate}`);
            const data = await response.json();
            
            if (!data.suitable && data.warning) {
                // Show warning modal
                document.getElementById('warning-message').textContent = data.message;
                weatherWarningModal.classList.remove('hidden');
                activityModal.classList.add('hidden');
                
                // Handle warning modal buttons
                document.getElementById('warning-cancel').onclick = () => {
                    weatherWarningModal.classList.add('hidden');
                    activityModal.classList.remove('hidden');
                };
                
                document.getElementById('warning-continue').onclick = () => {
                    weatherWarningModal.classList.add('hidden');
                    this.submit(); // Submit the form
                };
            } else {
                // Submit form directly
                this.submit();
            }
        } catch (error) {
            console.error('Weather check failed:', error);
            // Submit form anyway
            this.submit();
        }
    });

    // Initialize map when page loads
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(initMap, 100);
    });

    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            activityModal.classList.add('hidden');
            weatherWarningModal.classList.add('hidden');
        }
        
        if (e.key === 'Enter' && e.ctrlKey) {
            addActivityBtn.click();
        }
    });
</script>
@endpush
@endsection