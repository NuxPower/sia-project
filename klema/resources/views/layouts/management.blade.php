{{-- resources/views/farms/index.blade.php --}}
@extends('layouts.app')

@section('title', 'My Farms - KLEMA')

@section('content')
<div class="min-h-screen map-container relative overflow-hidden">
    <!-- Map Background -->
    <div class="absolute inset-0 opacity-20">
        <div id="map" class="w-full h-full"></div>
    </div>

    <div class="relative z-10 p-6">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">My Farms</h1>
                    <p class="text-white/70">Manage your farm locations and monitor conditions</p>
                </div>
                <a href="{{ route('farms.create') }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl transition-all duration-200 flex items-center space-x-2">
                    <i class="fas fa-plus"></i>
                    <span>Add Farm</span>
                </a>
            </div>

            <!-- Farms Grid -->
            @if($farms->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($farms as $farm)
                <div class="weather-card rounded-3xl p-6 text-white hover:scale-105 transition-all duration-200 cursor-pointer"
                     onclick="window.location.href='{{ route('farms.show', $farm->farm_id) }}'">
                    <!-- Farm Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-green-600/20 rounded-full flex items-center justify-center">
                                <i class="fas fa-seedling text-green-400 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg">{{ $farm->farm_name }}</h3>
                                <div class="text-sm text-white/60">
                                    {{ number_format($farm->latitude, 4) }}, {{ number_format($farm->longitude, 4) }}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Alert Badge -->
                        @if($farm->activeAlerts->count() > 0)
                        <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                            <span class="text-xs font-bold">{{ $farm->activeAlerts->count() }}</span>
                        </div>
                        @endif
                    </div>

                    <!-- Weather Info -->
                    @if($farm->latestWeather)
                    <div class="mb-4">
                        <div class="flex items-center justify-between">
                            <div class="text-2xl font-bold">{{ $farm->latestWeather->formatted_temperature }}</div>
                            <div class="text-right">
                                <i class="{{ $farm->latestWeather->weather_icon }}"></i>
                            </div>
                        </div>
                        <div class="text-sm text-white/70">{{ $farm->latestWeather->condition }}</div>
                        <div class="text-xs text-white/50">
                            Updated {{ $farm->latestWeather->recorded_at->diffForHumans() }}
                        </div>
                    </div>
                    @else
                    <div class="mb-4 text-center text-white/50 py-4">
                        <i class="fas fa-cloud-question text-2xl mb-2"></i>
                        <div class="text-sm">No weather data</div>
                    </div>
                    @endif

                    <!-- Farm Stats -->
                    <div class="flex justify-between text-sm text-white/70">
                        <div class="text-center">
                            <div class="font-semibold">{{ $farm->farmPoints->count() }}</div>
                            <div>Points</div>
                        </div>
                        <div class="text-center">
                            @php
                                $weeklyData = $farm->weatherData()->where('recorded_at', '>=', now()->subWeek())->count();
                            @endphp
                            <div class="font-semibold">{{ $weeklyData }}</div>
                            <div>Records</div>
                        </div>
                        <div class="text-center">
                            <div class="font-semibold {{ $farm->activeAlerts->count() > 0 ? 'text-red-400' : 'text-green-400' }}">
                                {{ $farm->activeAlerts->count() > 0 ? $farm->activeAlerts->count() : '✓' }}
                            </div>
                            <div>Alerts</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <!-- Empty State -->
            <div class="weather-card rounded-3xl p-12 text-center text-white">
                <div class="w-24 h-24 bg-green-600/20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-seedling text-green-400 text-3xl"></i>
                </div>
                <h3 class="text-2xl font-bold mb-4">No farms added yet</h3>
                <p class="text-white/70 mb-8 max-w-md mx-auto">
                    Start monitoring your agricultural activities by adding your first farm location.
                </p>
                <a href="{{ route('farms.create') }}" 
                   class="inline-flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-xl transition-all duration-200">
                    <i class="fas fa-plus"></i>
                    <span>Add Your First Farm</span>
                </a>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    let map;
    
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

        @if($farms->isNotEmpty())
        // Add farm markers
        const farms = @json($farms->map(function($farm) {
            return [
                'id' => $farm->farm_id,
                'name' => $farm->farm_name,
                'lat' => $farm->latitude,
                'lng' => $farm->longitude,
                'alerts' => $farm->activeAlerts->count(),
                'temperature' => $farm->latestWeather ? $farm->latestWeather->temperature : null
            ];
        }));

        farms.forEach(farm => {
            const markerColor = farm.alerts > 0 ? 'red' : 'green';
            const marker = L.marker([farm.lat, farm.lng], {
                icon: L.divIcon({
                    className: 'custom-marker',
                    html: `<div class="w-6 h-6 bg-${markerColor}-500 border-2 border-white rounded-full shadow-lg flex items-center justify-center text-white text-xs font-bold">${farm.alerts > 0 ? farm.alerts : '✓'}</div>`,
                    iconSize: [24, 24]
                })
            }).addTo(map);

            let popupContent = `<div class="p-2">
                <strong>${farm.name}</strong><br>
                <small>Farm ID: ${farm.id}</small><br>`;
            if (farm.temperature) {
                popupContent += `Temperature: ${farm.temperature}°C<br>`;
            }
            if (farm.alerts > 0) {
                popupContent += `<span style="color: red;">⚠️ ${farm.alerts} active alert(s)</span>`;
            } else {
                popupContent += `<span style="color: green;">✅ No alerts</span>`;
            }
            popupContent += `</div>`;

            marker.bindPopup(popupContent);
            
            // Click to navigate
            marker.on('click', function() {
                window.location.href = `/farms/${farm.id}`;
            });
        });
        
        // Fit map to show all farms
        if (farms.length > 0) {
            const group = new L.featureGroup(farms.map(farm => L.marker([farm.lat, farm.lng])));
            map.fitBounds(group.getBounds().pad(0.1));
        }
        @endif
    }

    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(initMap, 100);
    });
</script>
@endpush
@endsection

{{-- resources/views/farms/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Add Farm - KLEMA')

@section('content')
<div class="min-h-screen map-container relative overflow-hidden">
    <!-- Interactive Map Background -->
    <div class="absolute inset-0 opacity-80">
        <div id="map" class="w-full h-full"></div>
    </div>

    <div class="relative z-10 p-6">
        <div class="max-w-2xl mx-auto">
            <!-- Form Card -->
            <div class="weather-card rounded-3xl p-8 text-white">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-green-600/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-seedling text-green-400 text-2xl"></i>
                    </div>
                    <h2 class="text-3xl font-bold mb-2">Add New Farm</h2>
                    <p class="text-white/70">Click on the map to set your farm location</p>
                </div>

                <form action="{{ route('farms.store') }}" method="POST" class="space-y-6">
                    @csrf
                    
                    <!-- Farm Name -->
                    <div>
                        <label class="block text-white/80 text-sm font-medium mb-2">Farm Name</label>
                        <input type="text" 
                               name="farm_name"
                               value="{{ old('farm_name') }}"
                               placeholder="Enter your farm name"
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-blue-500/50 @error('farm_name') border-red-500 @enderror" 
                               required>
                        @error('farm_name')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Coordinates -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-white/80 text-sm font-medium mb-2">Latitude</label>
                            <input type="number" 
                                   name="latitude"
                                   id="latitude"
                                   value="{{ old('latitude') }}"
                                   step="any"
                                   placeholder="Click on map"
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-blue-500/50 @error('latitude') border-red-500 @enderror" 
                                   required>
                            @error('latitude')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="block text-white/80 text-sm font-medium mb-2">Longitude</label>
                            <input type="number" 
                                   name="longitude"
                                   id="longitude"
                                   value="{{ old('longitude') }}"
                                   step="any"
                                   placeholder="Click on map"
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-blue-500/50 @error('longitude') border-red-500 @enderror" 
                                   required>
                            @error('longitude')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- Selected Location Display -->
                    <div id="location-display" class="hidden p-4 bg-green-600/20 rounded-xl">
                        <div class="flex items-center space-x-2 text-green-400">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Selected Location:</span>
                            <span id="selected-coords" class="font-mono text-sm"></span>
                        </div>
                    </div>
                    
                    <!-- Buttons -->
                    <div class="flex space-x-4 pt-6">
                        <a href="{{ route('farms.index') }}" 
                           class="flex-1 px-6 py-3 bg-white/10 border border-white/20 text-white rounded-xl hover:bg-white/20 transition-all duration-200 text-center">
                            Cancel
                        </a>
                        <button type="submit"
                                class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-all duration-200">
                            Add Farm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let map;
    let marker;
    
    function initMap() {
        const defaultCoords = [8.2451, 124.2437]; // Northern Mindanao center
        
        map = L.map('map', {
            zoomControl: true,
            attributionControl: false,
        }).setView(defaultCoords, 10);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
        }).addTo(map);

        // Map click handler
        map.on('click', function(e) {
            const lat = e.latlng.lat.toFixed(6);
            const lng = e.latlng.lng.toFixed(6);
            
            // Update form fields
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            
            // Update display
            document.getElementById('selected-coords').textContent = `${lat}, ${lng}`;
            document.getElementById('location-display').classList.remove('hidden');
            
            // Add or update marker
            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng, {
                    icon: L.divIcon({
                        className: 'custom-marker',
                        html: `<div class="w-8 h-8 bg-green-500 border-4 border-white rounded-full shadow-lg flex items-center justify-center">
                                   <i class="fas fa-seedling text-white text-xs"></i>
                               </div>`,
                        iconSize: [32, 32]
                    })
                }).addTo(map);
            }
            
            marker.bindPopup(`<strong>New Farm Location</strong><br>Lat: ${lat}<br>Lng: ${lng}`).openPopup();
        });

        // Set existing location if editing
        @if(old('latitude') && old('longitude'))
        const existingLat = {{ old('latitude') }};
        const existingLng = {{ old('longitude') }};
        
        marker = L.marker([existingLat, existingLng], {
            icon: L.divIcon({
                className: 'custom-marker',
                html: `<div class="w-8 h-8 bg-green-500 border-4 border-white rounded-full shadow-lg flex items-center justify-center">
                           <i class="fas fa-seedling text-white text-xs"></i>
                       </div>`,
                iconSize: [32, 32]
            })
        }).addTo(map);
        
        map.setView([existingLat, existingLng], 15);
        document.getElementById('selected-coords').textContent = `${existingLat}, ${existingLng}`;
        document.getElementById('location-display').classList.remove('hidden');
        @endif
    }

    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(initMap, 100);
    });
</script>
@endpush
@endsection

{{-- resources/views/farms/show.blade.php --}}
@extends('layouts.app')

@section('title', $farm->farm_name . ' - KLEMA')

@section('content')
<div class="min-h-screen map-container relative overflow-hidden">
    <!-- Farm Map Background -->
    <div class="absolute inset-0 opacity-60">
        <div id="map" class="w-full h-full"></div>
    </div>

    <div class="relative z-10 p-6">
        <div class="max-w-7xl mx-auto">
            <!-- Farm Header -->
            <div class="weather-card rounded-3xl p-6 text-white mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-green-600/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-seedling text-green-400 text-2xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold mb-2">{{ $farm->farm_name }}</h1>
                            <div class="text-white/70">
                                <i class="fas fa-map-marker-alt mr-2"></i>
                                {{ number_format($farm->latitude, 6) }}, {{ number_format($farm->longitude, 6) }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        @if($farm->activeAlerts->count() > 0)
                        <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center">
                            <span class="font-bold">{{ $farm->activeAlerts->count() }}</span>
                        </div>
                        @endif
                        
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('farms.edit', $farm->farm_id) }}" 
                               class="p-3 bg-white/10 hover:bg-white/20 rounded-xl transition-all duration-200">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="addPointMode()" 
                                    class="p-3 bg-blue-600 hover:bg-blue-700 rounded-xl transition-all duration-200">
                                <i class="fas fa-map-pin"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Farm Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <!-- Current Weather -->
                @if($farm->latestWeather)
                <div class="weather-card rounded-2xl p-4 text-white text-center">
                    <i class="{{ $farm->latestWeather->weather_icon }} text-2xl mb-2"></i>
                    <div class="text-2xl font-bold">{{ $farm->latestWeather->formatted_temperature }}</div>
                    <div class="text-sm text-white/70">{{ $farm->latestWeather->condition }}</div>
                </div>
                @else
                <div class="weather-card rounded-2xl p-4 text-white text-center">
                    <i class="fas fa-cloud-question text-2xl text-white/50 mb-2"></i>
                    <div class="text-lg text-white/50">No Data</div>
                    <div class="text-sm text-white/40">Weather</div>
                </div>
                @endif

                <!-- Points Count -->
                <div class="weather-card rounded-2xl p-4 text-white text-center">
                    <i class="fas fa-map-marker-alt text-blue-400 text-2xl mb-2"></i>
                    <div class="text-2xl font-bold">{{ $farm->farmPoints->count() }}</div>
                    <div class="text-sm text-white/70">Points of Interest</div>
                </div>

                <!-- Weather Records -->
                <div class="weather-card rounded-2xl p-4 text-white text-center">
                    <i class="fas fa-chart-line text-green-400 text-2xl mb-2"></i>
                    <div class="text-2xl font-bold">{{ $farm->weatherData->count() }}</div>
                    <div class="text-sm text-white/70">Weather Records</div>
                </div>

                <!-- Active Alerts -->
                <div class="weather-card rounded-2xl p-4 text-white text-center">
                    <i class="fas fa-exclamation-triangle text-{{ $farm->activeAlerts->count() > 0 ? 'red' : 'green' }}-400 text-2xl mb-2"></i>
                    <div class="text-2xl font-bold">{{ $farm->activeAlerts->count() }}</div>
                    <div class="text-sm text-white/70">Active Alerts</div>
                </div>
            </div>

            <!-- Content Tabs -->
            <div class="weather-card rounded-3xl p-6 text-white">
                <div class="flex space-x-4 mb-6 border-b border-white/10 pb-4">
                    <button onclick="showTab('points')" id="points-tab" class="tab-btn px-6 py-2 rounded-lg bg-blue-600 text-white">
                        Points of Interest
                    </button>
                    <button onclick="showTab('weather')" id="weather-tab" class="tab-btn px-6 py-2 rounded-lg bg-white/10 text-white/70">
                        Weather History
                    </button>
                    <button onclick="showTab('alerts')" id="alerts-tab" class="tab-btn px-6 py-2 rounded-lg bg-white/10 text-white/70">
                        Alerts
                    </button>
                </div>

                <!-- Points Tab -->
                <div id="points-content" class="tab-content">
                    @if($farm->farmPoints->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($farm->farmPoints as $point)
                        <div class="p-4 bg-white/5 rounded-xl">
                            <div class="flex items-center space-x-3 mb-2">
                                <i class="{{ $point->point_type_icon }}"></i>
                                <div>
                                    <div class="font-medium">{{ $point->label }}</div>
                                    <div class="text-sm text-white/60 capitalize">{{ str_replace('_', ' ', $point->point_type) }}</div>
                                </div>
                            </div>
                            <div class="text-xs text-white/50 font-mono">
                                {{ number_format($point->latitude, 6) }}, {{ number_format($point->longitude, 6) }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-12">
                        <i class="fas fa-map-marker-alt text-4xl text-white/30 mb-4"></i>
                        <p class="text-white/60">No points of interest added yet</p>
                        <p class="text-sm text-white/40 mb-4">Click the pin button above to start adding points</p>
                    </div>
                    @endif
                </div>

                <!-- Weather Tab -->
                <div id="weather-content" class="tab-content hidden">
                    @if($farm->weatherData->isNotEmpty())
                    <div class="space-y-4">
                        @foreach($farm->weatherData->take(10) as $weather)
                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
                            <div class="flex items-center space-x-4">
                                <i class="{{ $weather->weather_icon }}"></i>
                                <div>
                                    <div class="font-medium">{{ $weather->formatted_temperature }}</div>
                                    <div class="text-sm text-white/60">{{ $weather->condition }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm">Humidity: {{ $weather->humidity }}%</div>
                                <div class="text-xs text-white/60">{{ $weather->recorded_at->format('M j, Y g:i A') }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-12">
                        <i class="fas fa-cloud text-4xl text-white/30 mb-4"></i>
                        <p class="text-white/60">No weather data recorded yet</p>
                    </div>
                    @endif
                </div>

                <!-- Alerts Tab -->
                <div id="alerts-content" class="tab-content hidden">
                    @if($farm->alerts->isNotEmpty())
                    <div class="space-y-4">
                        @foreach($farm->alerts->take(10) as $alert)
                        <div class="flex items-start space-x-4 p-4 bg-white/5 rounded-xl">
                            <div class="w-8 h-8 {{ $alert->alert_type_color }} rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="{{ $alert->alert_type_icon }} text-white text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="font-medium capitalize">{{ str_replace('_', ' ', $alert->alert_type) }}</div>
                                    @if(!$alert->resolved)
                                    <button onclick="resolveAlert({{ $alert->alert_id }})" 
                                            class="text-xs bg-green-600 hover:bg-green-700 px-3 py-1 rounded-full transition-all duration-200">
                                        Mark Resolved
                                    </button>
                                    @else
                                    <span class="text-xs bg-gray-600 px-3 py-1 rounded-full">Resolved</span>
                                    @endif
                                </div>
                                <div class="text-sm text-white/70 mb-1">{{ $alert->message }}</div>
                                <div class="text-xs text-white/50">{{ $alert->issued_at->diffForHumans() }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-12">
                        <i class="fas fa-check-circle text-4xl text-green-400 mb-4"></i>
                        <p class="text-white/60">No alerts for this farm</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Point Modal -->
<div id="add-point-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-3xl p-8 max-w-md w-full">
            <h2 class="text-2xl font-bold text-white mb-6">Add Point of Interest</h2>
            
            <form id="point-form" class="space-y-6">
                @csrf
                
                <div>
                    <label class="block text-white/80 text-sm font-medium mb-2">Label</label>
                    <input type="text" 
                           name="label"
                           placeholder="e.g., Water Source, Storage"
                           class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-blue-500/50" required>
                </div>
                
                <div>
                    <label class="block text-white/80 text-sm font-medium mb-2">Point Type</label>
                    <select name="point_type" 
                            class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                        <option value="water_source" class="bg-slate-800">Water Source</option>
                        <option value="storage" class="bg-slate-800">Storage</option>
                        <option value="equipment" class="bg-slate-800">Equipment</option>
                        <option value="crop_field" class="bg-slate-800">Crop Field</option>
                        <option value="entrance" class="bg-slate-800">Entrance</option>
                        <option value="shelter" class="bg-slate-800">Shelter</option>
                        <option value="general" class="bg-slate-800">General</option>
                    </select>
                </div>
                
                <input type="hidden" name="latitude" id="point-lat">
                <input type="hidden" name="longitude" id="point-lng">
                
                <div class="flex space-x-4 pt-4">
                    <button type="button" 
                            onclick="cancelAddPoint()"
                            class="flex-1 px-6 py-3 bg-white/10 border border-white/20 text-white rounded-xl hover:bg-white/20 transition-all duration-200">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-all duration-200">
                        Add Point
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let map;
    let farmMarker;
    let pointMarkers = [];
    let addingPoint = false;
    let tempMarker = null;

    function initMap() {
        const farmCoords = [{{ $farm->latitude }}, {{ $farm->longitude }}];
        
        map = L.map('map', {
            zoomControl: true,
            attributionControl: false,
        }).setView(farmCoords, 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
        }).addTo(map);

        // Add farm marker
        farmMarker = L.marker(farmCoords, {
            icon: L.divIcon({
                className: 'custom-marker',
                html: `<div class="w-12 h-12 bg-green-600 border-4 border-white rounded-full shadow-lg flex items-center justify-center">
                           <i class="fas fa-seedling text-white"></i>
                       </div>`,
                iconSize: [48, 48]
            })
        }).addTo(map);

        farmMarker.bindPopup(`<strong>{{ $farm->farm_name }}</strong><br>Main Farm Location`);

        // Add existing points
        @foreach($farm->farmPoints as $point)
        const point{{ $point->point_id }} = L.marker([{{ $point->latitude }}, {{ $point->longitude }}], {
            icon: L.divIcon({
                className: 'custom-marker',
                html: `<div class="w-8 h-8 bg-blue-500 border-2 border-white rounded-full shadow-lg flex items-center justify-center">
                           <i class="fas fa-map-pin text-white text-xs"></i>
                       </div>`,
                iconSize: [32, 32]
            })
        }).addTo(map);
        
        point{{ $point->point_id }}.bindPopup(`<strong>{{ $point->label }}</strong><br>{{ ucfirst(str_replace('_', ' ', $point->point_type)) }}`);
        pointMarkers.push(point{{ $point->point_id }});
        @endforeach

        // Map click handler for adding points
        map.on('click', function(e) {
            if (addingPoint) {
                const lat = e.latlng.lat.toFixed(6);
                const lng = e.latlng.lng.toFixed(6);
                
                // Remove previous temp marker
                if (tempMarker) {
                    map.removeLayer(tempMarker);
                }
                
                // Add temporary marker
                tempMarker = L.marker(e.latlng, {
                    icon: L.divIcon({
                        className: 'custom-marker',
                        html: `<div class="w-8 h-8 bg-yellow-500 border-2 border-white rounded-full shadow-lg flex items-center justify-center animate-pulse">
                                   <i class="fas fa-plus text-white text-xs"></i>
                               </div>`,
                        iconSize: [32, 32]
                    })
                }).addTo(map);
                
                // Update form
                document.getElementById('point-lat').value = lat;
                document.getElementById('point-lng').value = lng;
                
                // Show modal
                document.getElementById('add-point-modal').classList.remove('hidden');
            }
        });
    }

    function showTab(tabName) {
        // Hide all contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        // Reset all tabs
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('bg-blue-600');
            btn.classList.add('bg-white/10', 'text-white/70');
        });
        
        // Show selected content
        document.getElementById(tabName + '-content').classList.remove('hidden');
        
        // Highlight selected tab
        const activeTab = document.getElementById(tabName + '-tab');
        activeTab.classList.add('bg-blue-600');
        activeTab.classList.remove('bg-white/10', 'text-white/70');
    }

    function addPointMode() {
        addingPoint = true;
        showNotification('Click on the map to add a point of interest', 'info');
        document.body.style.cursor = 'crosshair';
    }

    function cancelAddPoint() {
        addingPoint = false;
        document.body.style.cursor = 'default';
        document.getElementById('add-point-modal').classList.add('hidden');
        
        if (tempMarker) {
            map.removeLayer(tempMarker);
            tempMarker = null;
        }
    }

    function resolveAlert(alertId) {
        fetch(`/alerts/${alertId}/resolve`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Alert resolved successfully', 'success');
                location.reload();
            } else {
                showNotification('Failed to resolve alert', 'error');
            }
        })
        .catch(error => {
            showNotification('An error occurred', 'error');
        });
    }

    // Handle point form submission
    document.getElementById('point-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch(`/farms/{{ $farm->farm_id }}/points`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Point added successfully!', 'success');
                location.reload(); // Reload to show new point
            } else {
                showNotification('Failed to add point', 'error');
            }
        })
        .catch(error => {
            showNotification('An error occurred', 'error');
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(initMap, 100);
    });
</script>
@endpush
@endsection