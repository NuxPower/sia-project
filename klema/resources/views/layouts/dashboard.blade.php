@extends('layouts.app')

@section('title', 'Dashboard - KLEMA')

@section('content')
<div class="min-h-screen map-container relative overflow-hidden">
    <!-- Map Background -->
    <div class="absolute inset-0 opacity-30">
        <div id="map" class="w-full h-full"></div>
    </div>

    <!-- Search Bar -->
    <div class="relative z-20 p-6">
        <div class="max-w-md mx-auto">
            <div class="relative">
                <input type="text" 
                       id="location-search"
                       value="{{ $location }}"
                       class="w-full px-6 py-3 pl-12 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                       placeholder="Search location...">
                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-white/60"></i>
            </div>
        </div>
    </div>

    <!-- Weather Cards -->
    <div class="relative z-10 px-6 pb-6">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Current Weather Card -->
            <div class="lg:col-span-1">
                <div class="weather-card rounded-3xl p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="flex items-center text-sm text-white/70 mb-1">
                                <i class="fas fa-map-marker-alt mr-2"></i>
                                {{ $currentWeather['name'] ?? 'Maramag, Bukidnon' }}
                            </div>
                            <div class="text-sm text-white/70">
                                {{ now()->format('l') }}<br>
                                {{ now()->format('d M, Y') }}
                            </div>
                        </div>
                        <div class="text-right">
                            <button class="text-white/60 hover:text-white">
                                <i class="fas fa-thermometer-half"></i>°C
                            </button>
                        </div>
                    </div>

                    <!-- Temperature Display -->
                    <div class="text-center mb-6">
                        <div class="text-6xl font-light mb-2">
                            {{ round($currentWeather['main']['temp'] ?? 28) }}°C
                        </div>
                        <div class="text-lg text-white/80 mb-1">
                            /{{ round($currentWeather['main']['temp_min'] ?? 24) }}°
                        </div>
                        
                        <!-- Weather Icon -->
                        <div class="flex items-center justify-center mb-4">
                            <div class="relative">
                                @if(isset($currentWeather['weather'][0]['main']) && str_contains(strtolower($currentWeather['weather'][0]['main']), 'rain'))
                                    <i class="fas fa-cloud-rain text-6xl text-blue-300 drop-shadow-lg"></i>
                                    <div class="absolute -top-2 left-1/2 transform -translate-x-1/2">
                                        <div class="flex space-x-1">
                                            <div class="w-1 h-8 bg-blue-400 rounded-full opacity-60 animate-pulse"></div>
                                            <div class="w-1 h-6 bg-blue-300 rounded-full opacity-80 animate-pulse" style="animation-delay: 0.2s"></div>
                                            <div class="w-1 h-4 bg-blue-400 rounded-full opacity-60 animate-pulse" style="animation-delay: 0.4s"></div>
                                        </div>
                                    </div>
                                @else
                                    <i class="fas fa-sun text-6xl text-yellow-400 drop-shadow-lg"></i>
                                @endif
                            </div>
                        </div>
                        
                        <div class="text-xl font-medium">
                            {{ $currentWeather['weather'][0]['description'] ?? 'Heavy Rain' }}
                        </div>
                        <div class="text-sm text-white/70">
                            Feels like {{ round($currentWeather['main']['feels_like'] ?? 31) }}°
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Highlight -->
            <div class="lg:col-span-2">
                <div class="weather-card rounded-3xl p-6 text-white">
                    <h3 class="text-xl font-semibold mb-6">Today's Highlight</h3>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                        <!-- Wind Status -->
                        <div class="text-center">
                            <div class="flex items-center justify-center mb-2">
                                <i class="fas fa-wind text-2xl text-blue-300"></i>
                            </div>
                            <div class="text-sm text-white/70 mb-1">Wind Status</div>
                            <div class="text-2xl font-bold">{{ number_format($currentWeather['wind']['speed'] ?? 7.90, 2) }} <span class="text-sm font-normal">km/h</span></div>
                            <div class="text-xs text-white/60">{{ now()->format('g:i A') }}</div>
                        </div>

                        <!-- Humidity -->
                        <div class="text-center">
                            <div class="flex items-center justify-center mb-2">
                                <i class="fas fa-tint text-2xl text-blue-400"></i>
                            </div>
                            <div class="text-sm text-white/70 mb-1">Humidity</div>
                            <div class="text-2xl font-bold">{{ $currentWeather['main']['humidity'] ?? 85 }} <span class="text-sm font-normal">%</span></div>
                            <div class="text-xs text-green-400">Humidity is good</div>
                        </div>

                        <!-- UV Index -->
                        <div class="text-center">
                            <div class="flex items-center justify-center mb-2">
                                <i class="fas fa-sun text-2xl text-yellow-400"></i>
                            </div>
                            <div class="text-sm text-white/70 mb-1">UV Index</div>
                            <div class="text-2xl font-bold">4 <span class="text-sm font-normal">UV</span></div>
                            <div class="text-xs text-yellow-400">Moderate UV</div>
                        </div>

                        <!-- Visibility -->
                        <div class="text-center">
                            <div class="flex items-center justify-center mb-2">
                                <i class="fas fa-eye text-2xl text-slate-300"></i>
                            </div>
                            <div class="text-sm text-white/70 mb-1">Visibility</div>
                            <div class="text-2xl font-bold">{{ round(($currentWeather['visibility'] ?? 5000) / 1000) }} <span class="text-sm font-normal">km</span></div>
                            <div class="text-xs text-white/60">{{ now()->format('g:i A') }}</div>
                        </div>
                    </div>

                    <!-- Sunrise/Sunset -->
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <div class="w-12 h-12 bg-orange-500/20 rounded-full flex items-center justify-center">
                                    <i class="fas fa-sun text-orange-400"></i>
                                </div>
                                <div>
                                    <div class="text-sm text-white/70">Sunrise</div>
                                    <div class="font-semibold">{{ isset($currentWeather['sys']['sunrise']) ? date('g:i A', $currentWeather['sys']['sunrise']) : '4:50 AM' }}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <div class="w-12 h-12 bg-orange-500/20 rounded-full flex items-center justify-center">
                                    <i class="fas fa-moon text-orange-300"></i>
                                </div>
                                <div>
                                    <div class="text-sm text-white/70">Sunset</div>
                                    <div class="font-semibold">{{ isset($currentWeather['sys']['sunset']) ? date('g:i A', $currentWeather['sys']['sunset']) : '6:45 PM' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Weekly Forecast -->
        <div class="max-w-7xl mx-auto mt-6">
            <div class="weather-card rounded-3xl p-6 text-white">
                <h3 class="text-xl font-semibold mb-6">7-Day Forecast</h3>
                
                <div class="grid grid-cols-7 gap-4">
                    @foreach($forecast as $index => $day)
                    <div class="text-center p-4 rounded-2xl {{ $index === 0 ? 'bg-white/10' : 'hover:bg-white/5' }} transition-all duration-200">
                        <div class="text-sm font-medium mb-2">{{ $day['day'] }}</div>
                        <div class="mb-3">
                            @if(str_contains(strtolower($day['condition']), 'rain'))
                                <i class="fas fa-cloud-rain text-2xl text-blue-300"></i>
                            @elseif(str_contains(strtolower($day['condition']), 'cloud'))
                                <i class="fas fa-cloud text-2xl text-gray-400"></i>
                            @else
                                <i class="fas fa-sun text-2xl text-yellow-400"></i>
                            @endif
                        </div>
                        <div class="space-y-1">
                            <div class="font-semibold">{{ $day['temp_max'] }}°</div>
                            <div class="text-sm text-white/60">{{ $day['temp_min'] }}°</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Farm Overview and Alerts -->
        @if($farms->isNotEmpty())
        <div class="max-w-7xl mx-auto mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Farm Overview -->
            <div class="weather-card rounded-3xl p-6 text-white">
                <h3 class="text-xl font-semibold mb-6">Your Farms</h3>
                
                <div class="space-y-4">
                    @foreach($farms->take(3) as $farm)
                    <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-green-600/20 rounded-full flex items-center justify-center">
                                <i class="fas fa-seedling text-green-400"></i>
                            </div>
                            <div>
                                <div class="font-medium">{{ $farm->farm_name }}</div>
                                <div class="text-sm text-white/60">
                                    @if($farm->latestWeather)
                                        {{ $farm->latestWeather->formatted_temperature }} • {{ $farm->latestWeather->condition }}
                                    @else
                                        No weather data
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            @if($farm->activeAlerts->count() > 0)
                                <div class="w-6 h-6 bg-red-500 rounded-full flex items-center justify-center text-xs font-bold">
                                    {{ $farm->activeAlerts->count() }}
                                </div>
                            @else
                                <i class="fas fa-check-circle text-green-400"></i>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                
                @if($farms->count() > 3)
                <div class="mt-4 text-center">
                    <a href="{{ route('farms.index') }}" class="text-blue-400 hover:text-blue-300 text-sm">
                        View all {{ $farms->count() }} farms
                    </a>
                </div>
                @endif
            </div>

            <!-- Recent Alerts -->
            <div class="weather-card rounded-3xl p-6 text-white">
                <h3 class="text-xl font-semibold mb-6">Recent Alerts</h3>
                
                @if($recentAlerts->isNotEmpty())
                <div class="space-y-4">
                    @foreach($recentAlerts as $alert)
                    <div class="flex items-start space-x-3 p-4 bg-white/5 rounded-xl">
                        <div class="w-8 h-8 {{ $alert->alert_type_color }} rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="{{ $alert->alert_type_icon }} text-white text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium capitalize">{{ str_replace('_', ' ', $alert->alert_type) }}</div>
                            <div class="text-sm text-white/70 mb-1">{{ Str::limit($alert->message, 80) }}</div>
                            <div class="text-xs text-white/50">
                                {{ $alert->farm->farm_name }} • {{ $alert->issued_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center text-white/60 py-8">
                    <i class="fas fa-check-circle text-4xl mb-4 text-green-400"></i>
                    <p>No active alerts</p>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Farming Tips -->
        <div class="max-w-7xl mx-auto mt-6">
            <div class="weather-card rounded-3xl p-6 text-white">
                <h3 class="text-xl font-semibold mb-6">Tips for farming</h3>
                
                <div class="space-y-4">
                    @foreach($farmingTips as $tip)
                    <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 bg-green-400 rounded-full mt-2 flex-shrink-0"></div>
                        <p class="text-white/90">{{ $tip }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Weather Statistics (if available) -->
        @if($weatherStats)
        <div class="max-w-7xl mx-auto mt-6">
            <div class="weather-card rounded-3xl p-6 text-white">
                <h3 class="text-xl font-semibold mb-6">7-Day Farm Statistics</h3>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-400">{{ number_format($weatherStats['avg_temperature'], 1) }}°C</div>
                        <div class="text-sm text-white/70">Avg Temperature</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-400">{{ number_format($weatherStats['avg_humidity'], 1) }}%</div>
                        <div class="text-sm text-white/70">Avg Humidity</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-cyan-400">{{ number_format($weatherStats['total_rainfall'], 1) }}mm</div>
                        <div class="text-sm text-white/70">Total Rainfall</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-400">{{ number_format($weatherStats['avg_wind_speed'], 1) }}km/h</div>
                        <div class="text-sm text-white/70">Avg Wind Speed</div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Initialize map
    let map;
    
    function initMap() {
        // Default coordinates for Northern Mindanao (approximate center)
        const defaultCoords = [8.2451, 124.2437];
        
        map = L.map('map', {
            zoomControl: false,
            attributionControl: false,
            dragging: true,
            scrollWheelZoom: false
        }).setView(defaultCoords, 8);

        // Use OpenStreetMap tiles with dark theme
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
            attribution: ''
        }).addTo(map);

        // Add user's farms as markers
        @if($farms->isNotEmpty())
        const farms = @json($farms->map(function($farm) {
            return [
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
                    html: `<div class="w-4 h-4 bg-${markerColor}-500 border-2 border-white rounded-full shadow-lg"></div>`,
                    iconSize: [16, 16]
                })
            }).addTo(map);

            let popupContent = `<strong>${farm.name}</strong><br>`;
            if (farm.temperature) {
                popupContent += `Temperature: ${farm.temperature}°C<br>`;
            }
            if (farm.alerts > 0) {
                popupContent += `<span class="text-red-600">⚠️ ${farm.alerts} active alert(s)</span>`;
            } else {
                popupContent += `<span class="text-green-600">✅ No alerts</span>`;
            }

            marker.bindPopup(popupContent);
        });
        @endif

        // Add some sample markers for cities
        const cities = [
            {name: 'Cagayan de Oro', coords: [8.4542, 124.6319]},
            {name: 'Valencia', coords: [8.1478, 125.0878]},
            {name: 'Pagadian', coords: [7.8306, 123.4342]},
            {name: 'Cotabato City', coords: [7.2186, 124.2452]},
            {name: 'Davao City', coords: [7.0731, 125.6128]},
            {name: 'General Santos', coords: [6.1164, 125.1716]}
        ];

        cities.forEach(city => {
            L.marker(city.coords, {
                icon: L.divIcon({
                    className: 'custom-marker',
                    html: `<div class="w-3 h-3 bg-white rounded-full opacity-60"></div>`,
                    iconSize: [12, 12]
                })
            }).addTo(map).bindPopup(city.name);
        });
    }

    // Location search functionality
    document.getElementById('location-search').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const location = e.target.value;
            searchLocation(location);
        }
    });

    function searchLocation(location) {
        // Update URL and reload with new location
        const url = new URL(window.location.href);
        url.searchParams.set('location', location);
        window.location.href = url.toString();
    }

    // Initialize map when page loads
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(initMap, 100); // Small delay to ensure container is ready
    });

    // Auto-refresh weather data every 10 minutes
    setInterval(() => {
        window.location.reload();
    }, 600000);
</script>
@endpush
@endsection