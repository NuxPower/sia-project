@extends('layouts.app')

@section('title', 'Weather Statistics - KLEMA')

@section('content')
<div class="min-h-screen map-container relative overflow-hidden">
    <!-- Map Background with reduced opacity -->
    <div class="absolute inset-0 opacity-20">
        <div id="map" class="w-full h-full"></div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 p-6">
        <div class="max-w-7xl mx-auto">
            <!-- Header Section -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">Weather Statistics</h1>
                    <p class="text-white/70">Comprehensive weather data and analytics for your farms</p>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Time Range Selector -->
                    <select id="time-range" class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                        <option value="7d" class="bg-slate-800">Last 7 days</option>
                        <option value="30d" class="bg-slate-800">Last 30 days</option>
                        <option value="90d" class="bg-slate-800">Last 3 months</option>
                        <option value="365d" class="bg-slate-800">Last year</option>
                    </select>
                    
                    <!-- Export Button -->
                    <button id="export-stats" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl transition-all duration-200 flex items-center space-x-2">
                        <i class="fas fa-download"></i>
                        <span>Export</span>
                    </button>
                </div>
            </div>

            <!-- Statistics Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Average Temperature -->
                <div class="weather-card rounded-2xl p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-orange-500/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-thermometer-half text-orange-400 text-xl"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-white/60">AVG TEMP</div>
                        </div>
                    </div>
                    <div class="text-3xl font-bold mb-1">{{ $weatherStats['avg_temperature'] ?? '28.5' }}°C</div>
                    <div class="flex items-center text-sm">
                        <i class="fas fa-arrow-up text-green-400 mr-1"></i>
                        <span class="text-green-400">+2.3°</span>
                        <span class="text-white/60 ml-1">vs last week</span>
                    </div>
                </div>

                <!-- Average Humidity -->
                <div class="weather-card rounded-2xl p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-tint text-blue-400 text-xl"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-white/60">AVG HUMIDITY</div>
                        </div>
                    </div>
                    <div class="text-3xl font-bold mb-1">{{ $weatherStats['avg_humidity'] ?? '75.2' }}%</div>
                    <div class="flex items-center text-sm">
                        <i class="fas fa-arrow-down text-red-400 mr-1"></i>
                        <span class="text-red-400">-3.1%</span>
                        <span class="text-white/60 ml-1">vs last week</span>
                    </div>
                </div>

                <!-- Total Rainfall -->
                <div class="weather-card rounded-2xl p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-cyan-500/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-cloud-rain text-cyan-400 text-xl"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-white/60">RAINFALL</div>
                        </div>
                    </div>
                    <div class="text-3xl font-bold mb-1">{{ $weatherStats['total_rainfall'] ?? '156.8' }}mm</div>
                    <div class="flex items-center text-sm">
                        <i class="fas fa-arrow-up text-green-400 mr-1"></i>
                        <span class="text-green-400">+23mm</span>
                        <span class="text-white/60 ml-1">vs last week</span>
                    </div>
                </div>

                <!-- Average Wind Speed -->
                <div class="weather-card rounded-2xl p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-gray-500/20 rounded-xl flex items-center justify-center">
                            <i class="fas fa-wind text-gray-400 text-xl"></i>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-white/60">WIND SPEED</div>
                        </div>
                    </div>
                    <div class="text-3xl font-bold mb-1">{{ $weatherStats['avg_wind_speed'] ?? '12.4' }} km/h</div>
                    <div class="flex items-center text-sm">
                        <i class="fas fa-arrow-up text-green-400 mr-1"></i>
                        <span class="text-green-400">+1.2</span>
                        <span class="text-white/60 ml-1">vs last week</span>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Temperature Trend Chart -->
                <div class="weather-card rounded-2xl p-6 text-white">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-semibold">Temperature Trend</h3>
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-orange-400 rounded-full"></div>
                            <span class="text-sm text-white/70">Temperature (°C)</span>
                        </div>
                    </div>
                    <div class="h-64">
                        <canvas id="temperatureChart" class="w-full h-full"></canvas>
                    </div>
                </div>

                <!-- Humidity & Rainfall Chart -->
                <div class="weather-card rounded-2xl p-6 text-white">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-semibold">Humidity & Rainfall</h3>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 bg-blue-400 rounded-full"></div>
                                <span class="text-sm text-white/70">Humidity (%)</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 bg-cyan-400 rounded-full"></div>
                                <span class="text-sm text-white/70">Rainfall (mm)</span>
                            </div>
                        </div>
                    </div>
                    <div class="h-64">
                        <canvas id="humidityRainfallChart" class="w-full h-full"></canvas>
                    </div>
                </div>
            </div>

            <!-- Detailed Statistics Table -->
            <div class="weather-card rounded-2xl p-6 text-white mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold">Farm Weather Details</h3>
                    <div class="flex items-center space-x-2">
                        <input type="text" id="search-farms" placeholder="Search farms..." 
                               class="bg-white/10 border border-white/20 rounded-lg px-3 py-2 text-white text-sm placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                        <i class="fas fa-search text-white/50"></i>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-white/20">
                                <th class="text-left py-3 px-4 font-semibold text-white/90">Farm Name</th>
                                <th class="text-center py-3 px-4 font-semibold text-white/90">Temperature</th>
                                <th class="text-center py-3 px-4 font-semibold text-white/90">Humidity</th>
                                <th class="text-center py-3 px-4 font-semibold text-white/90">Rainfall</th>
                                <th class="text-center py-3 px-4 font-semibold text-white/90">Wind Speed</th>
                                <th class="text-center py-3 px-4 font-semibold text-white/90">Last Updated</th>
                                <th class="text-center py-3 px-4 font-semibold text-white/90">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($farms) && $farms->count() > 0)
                                @foreach($farms as $farm)
                                <tr class="border-b border-white/10 hover:bg-white/5 transition-all duration-200">
                                    <td class="py-4 px-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 bg-green-600/20 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-seedling text-green-400"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium">{{ $farm->farm_name }}</div>
                                                <div class="text-sm text-white/60">{{ $farm->location ?? 'N/A' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="text-orange-400 font-semibold">
                                            {{ $farm->latestWeather->temperature ?? 'N/A' }}
                                            @if($farm->latestWeather->temperature)°C@endif
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="text-blue-400 font-semibold">
                                            {{ $farm->latestWeather->humidity ?? 'N/A' }}
                                            @if($farm->latestWeather->humidity)%@endif
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="text-cyan-400 font-semibold">
                                            {{ $farm->latestWeather->rainfall ?? '0' }}mm
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="text-gray-400 font-semibold">
                                            {{ $farm->latestWeather->wind_speed ?? 'N/A' }}
                                            @if($farm->latestWeather->wind_speed) km/h@endif
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center text-white/60 text-sm">
                                        {{ $farm->latestWeather ? $farm->latestWeather->recorded_at->diffForHumans() : 'Never' }}
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        @if($farm->activeAlerts && $farm->activeAlerts->count() > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-500/20 text-red-400 border border-red-500/30">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                {{ $farm->activeAlerts->count() }} Alert(s)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-500/20 text-green-400 border border-green-500/30">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                Normal
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-white/60">
                                        <i class="fas fa-seedling text-4xl mb-4"></i>
                                        <p>No farm data available</p>
                                        <p class="text-sm mt-2">Add farms to view weather statistics</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Weather Alerts Summary -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Recent Weather Alerts -->
                <div class="weather-card rounded-2xl p-6 text-white">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-semibold">Recent Weather Alerts</h3>
                        <a href="{{ route('alerts.index') }}" class="text-blue-400 hover:text-blue-300 text-sm">View all</a>
                    </div>

                    @if(isset($recentAlerts) && $recentAlerts->count() > 0)
                        <div class="space-y-4">
                            @foreach($recentAlerts->take(5) as $alert)
                            <div class="flex items-start space-x-3 p-4 bg-white/5 rounded-lg">
                                <div class="w-8 h-8 
                                    @if($alert->alert_type == 'severe_weather') bg-red-500/20 text-red-400
                                    @elseif($alert->alert_type == 'temperature') bg-orange-500/20 text-orange-400
                                    @elseif($alert->alert_type == 'humidity') bg-blue-500/20 text-blue-400
                                    @else bg-yellow-500/20 text-yellow-400 @endif
                                    rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="
                                        @if($alert->alert_type == 'severe_weather') fas fa-exclamation-triangle
                                        @elseif($alert->alert_type == 'temperature') fas fa-thermometer-half
                                        @elseif($alert->alert_type == 'humidity') fas fa-tint
                                        @else fas fa-bell @endif
                                        text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium capitalize mb-1">
                                        {{ str_replace('_', ' ', $alert->alert_type) }}
                                    </div>
                                    <div class="text-sm text-white/70 mb-2">
                                        {{ Str::limit($alert->message, 80) }}
                                    </div>
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
                            <p>No recent weather alerts</p>
                            <p class="text-sm mt-2">Your farms are operating under normal conditions</p>
                        </div>
                    @endif
                </div>

                <!-- Weather Forecast Summary -->
                <div class="weather-card rounded-2xl p-6 text-white">
                    <h3 class="text-xl font-semibold mb-6">5-Day Forecast</h3>
                    
                    <div class="space-y-4">
                        @php
                            $forecast = $forecast ?? [
                                ['day' => 'Today', 'condition' => 'Rain', 'temp_max' => 28, 'temp_min' => 24, 'humidity' => 85, 'rainfall' => 15],
                                ['day' => 'Tomorrow', 'condition' => 'Partly Cloudy', 'temp_max' => 30, 'temp_min' => 25, 'humidity' => 70, 'rainfall' => 3],
                                ['day' => 'Tuesday', 'condition' => 'Sunny', 'temp_max' => 32, 'temp_min' => 26, 'humidity' => 60, 'rainfall' => 0],
                                ['day' => 'Wednesday', 'condition' => 'Cloudy', 'temp_max' => 29, 'temp_min' => 24, 'humidity' => 75, 'rainfall' => 8],
                                ['day' => 'Thursday', 'condition' => 'Rain', 'temp_max' => 27, 'temp_min' => 23, 'humidity' => 90, 'rainfall' => 20]
                            ];
                        @endphp
                        
                        @foreach($forecast as $day)
                        <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="text-2xl">
                                    @if(str_contains(strtolower($day['condition']), 'rain'))
                                        <i class="fas fa-cloud-rain text-blue-400"></i>
                                    @elseif(str_contains(strtolower($day['condition']), 'cloud'))
                                        <i class="fas fa-cloud text-gray-400"></i>
                                    @elseif(str_contains(strtolower($day['condition']), 'sun'))
                                        <i class="fas fa-sun text-yellow-400"></i>
                                    @else
                                        <i class="fas fa-cloud-sun text-yellow-300"></i>
                                    @endif
                                </div>
                                <div>
                                    <div class="font-medium">{{ $day['day'] }}</div>
                                    <div class="text-sm text-white/60">{{ $day['condition'] }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold">{{ $day['temp_max'] }}° / {{ $day['temp_min'] }}°</div>
                                <div class="text-sm text-white/60">{{ $day['rainfall'] }}mm • {{ $day['humidity'] }}%</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
    let map;
    let temperatureChart, humidityRainfallChart;

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

    // Initialize charts
    function initCharts() {
        // Temperature Chart
        const tempCtx = document.getElementById('temperatureChart').getContext('2d');
        temperatureChart = new Chart(tempCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Temperature (°C)',
                    data: [26, 28, 30, 29, 27, 25, 28],
                    borderColor: '#fb923c',
                    backgroundColor: 'rgba(251, 146, 60, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#fb923c',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        }
                    }
                }
            }
        });

        // Humidity & Rainfall Chart
        const humidCtx = document.getElementById('humidityRainfallChart').getContext('2d');
        humidityRainfallChart = new Chart(humidCtx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Humidity (%)',
                    data: [75, 68, 82, 79, 85, 72, 80],
                    backgroundColor: 'rgba(59, 130, 246, 0.6)',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    yAxisID: 'y'
                }, {
                    label: 'Rainfall (mm)',
                    data: [12, 5, 18, 8, 25, 3, 15],
                    backgroundColor: 'rgba(6, 182, 212, 0.6)',
                    borderColor: '#06b6d4',
                    borderWidth: 2,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.7)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }

    // Search functionality
    document.getElementById('search-farms').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        const tableRows = document.querySelectorAll('tbody tr');
        
        tableRows.forEach(row => {
            const farmName = row.querySelector('td:first-child').textContent.toLowerCase();
            if (farmName.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Export functionality
    document.getElementById('export-stats').addEventListener('click', function() {
        // Add export logic here
        showNotification('Export functionality coming soon!', 'info');
    });

    // Time range change
    document.getElementById('time-range').addEventListener('change', function() {
        // Add time range filter logic here
        showNotification('Filtering by ' + this.options[this.selectedIndex].text, 'info');
    });

    // Initialize everything when page loads
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            initMap();
            initCharts();
        }, 100);
    });

    // Notification function
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300`;
        
        const bgColor = {
            'success': 'bg-green-600',
            'error': 'bg-red-600',
            'warning': 'bg-yellow-600',
            'info': 'bg-blue-600'
        }[type] || 'bg-blue-600';
        
        notification.className += ` ${bgColor} text-white`;
        notification.innerHTML = `
            <div class="flex items-center space-x-2">
                <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : type === 'warning' ? 'exclamation' : 'info'}-circle"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => notification.remove(), 3000);
    }
</script>
@endpush
@endsection