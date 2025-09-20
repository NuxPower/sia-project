<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'KLEMA - Climate Smart Agriculture')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .auth-bg {
            background: linear-gradient(135deg, 
                rgba(15, 23, 42, 0.9), 
                rgba(30, 41, 59, 0.9), 
                rgba(51, 65, 85, 0.9)
            );
        }
        
        .floating-elements::before {
            content: '';
            position: absolute;
            top: 10%;
            left: 10%;
            width: 100px;
            height: 100px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-elements::after {
            content: '';
            position: absolute;
            top: 60%;
            right: 15%;
            width: 80px;
            height: 80px;
            background: rgba(34, 197, 94, 0.1);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
    </style>
</head>
<body class="min-h-screen auth-bg relative overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
        <svg width="100%" height="100%" viewBox="0 0 100 100" preserveAspectRatio="none">
            <defs>
                <pattern id="grain" width="10" height="10" patternUnits="userSpaceOnUse">
                    <circle cx="5" cy="5" r="2" fill="white" opacity="0.3"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grain)"/>
        </svg>
    </div>
    
    <!-- Floating Elements -->
    <div class="floating-elements absolute inset-0"></div>
    
    <!-- Main Content -->
    <div class="relative z-10">
        @yield('content')
    </div>

    @if(session('success'))
        <div class="fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="fixed top-4 right-4 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            {{ session('error') }}
        </div>
    @endif

    <script>
        // Auto-hide notifications
        setTimeout(() => {
            const notifications = document.querySelectorAll('.fixed.top-4.right-4');
            notifications.forEach(notification => {
                notification.style.transform = 'translateX(100%)';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
