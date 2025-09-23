<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'KLEMA - Climate Smart Agriculture')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .weather-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.9), rgba(51, 65, 85, 0.9));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar-active {
            background: rgba(59, 130, 246, 0.1);
            border-right: 3px solid #3b82f6;
        }
        .map-container {
            background: linear-gradient(45deg, #0f172a, #1e293b);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Background overlay with farm imagery -->
    <div class="fixed inset-0 opacity-10 bg-cover bg-center z-0" 
         style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><defs><pattern id=%22farm%22 width=%2220%22 height=%2220%22 patternUnits=%22userSpaceOnUse%22><circle cx=%2210%22 cy=%2210%22 r=%223%22 fill=%22%23ffffff%22 opacity=%220.1%22/></pattern></defs><rect width=%22100%22 height=%22100%22 fill=%22url(%23farm)%22/></svg>')">
    </div>

    <div class="relative z-10 flex min-h-screen">
        <!-- Main Content -->
        <main class="flex-1 min-h-screen">
            @yield('content')
        </main>
    </div>

    <!-- Notification Container -->
    <div id="notifications" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <script>
        // CSRF token setup for AJAX requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `p-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 translate-x-full opacity-0`;
            
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
            
            document.getElementById('notifications').appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full', 'opacity-0');
            }, 100);
            
            // Auto remove
            setTimeout(() => {
                notification.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }

        // Weather icon mapping
        function getWeatherIcon(condition) {
            const iconMap = {
                'Clear': 'fas fa-sun text-yellow-400',
                'Clouds': 'fas fa-cloud text-gray-400',
                'Rain': 'fas fa-cloud-rain text-blue-400',
                'Snow': 'fas fa-snowflake text-white',
                'Thunderstorm': 'fas fa-bolt text-yellow-400',
                'Drizzle': 'fas fa-cloud-drizzle text-blue-300',
                'Mist': 'fas fa-smog text-gray-500',
                'Fog': 'fas fa-smog text-gray-500'
            };
            return iconMap[condition] || 'fas fa-question text-gray-400';
        }

        // Format date helper
        function formatDate(date) {
            return new Date(date).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        // Temperature conversion
        function formatTemperature(temp) {
            return Math.round(temp) + '°C';
        }

        @if(session('success'))
            showNotification('{{ session('success') }}', 'success');
        @endif

        @if(session('error'))
            showNotification('{{ session('error') }}', 'error');
        @endif

        @if(session('warning'))
            showNotification('{{ session('warning') }}', 'warning');
        @endif
    </script>

    @stack('scripts')
</body>
</html>