<!-- ============================================ -->
<!-- Updated layouts/app.blade.php with Sidebar Toggle -->
<!-- ============================================ -->
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'KLEMA') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            transition: background 0.3s ease;
        }

        body:not(.auth-mode) {
            overflow: hidden;
        }

        body.auth-mode {
            min-height: 100vh;
            overflow-x: hidden;
            overflow-y: auto;
            background: radial-gradient(circle at 15% 20%, rgba(59, 130, 246, 0.45), transparent 55%),
                        radial-gradient(circle at 85% 30%, rgba(14, 165, 233, 0.40), transparent 50%),
                        linear-gradient(135deg, #0f172a 0%, #1e293b 45%, #111827 100%);
        }

        body.auth-mode .navbar {
            display: none;
        }

        body.map-only-mode .navbar {
            display: none;
        }

        body.map-only-mode .sidebar-toggle {
            display: none;
        }

        body.map-only-mode .logout-button {
            display: none;
        }

        /* Sidebar Container */
        .navbar {
            position: fixed;
            left: 24px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 28px;
            width: 92px;
            padding: 32px 0;
            background: rgba(15, 16, 20, 0.95);
            border-radius: 40px;
            border: 1px solid rgba(255, 255, 255, 0.04);
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(18px);
        }

        /* Navigation Icons */
        .navbar-icon {
            width: 58px;
            height: 58px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .navbar-icon img {
            width: 30px;
            height: 30px;
            opacity: 0.55;
            filter: grayscale(100%) brightness(1.2);
            transition: opacity 0.3s ease, filter 0.3s ease, transform 0.3s ease;
        }

        .navbar-icon:hover {
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.35);
            transform: translateY(-2px);
        }

        .navbar-icon:hover img {
            opacity: 0.85;
            filter: grayscale(20%) brightness(1.15);
            transform: scale(1.05);
        }

        .navbar-icon.active {
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 14px 30px rgba(0, 0, 0, 0.45);
        }

        .navbar-icon.active img {
            opacity: 1;
            filter: none;
        }

        /* Animation for icons */
        @keyframes slideIn {
            from {
                transform: translateX(-100px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .navbar-icon {
            animation: slideIn 0.5s ease forwards;
        }

        .navbar-icon:nth-child(1) {
            animation-delay: 0.1s;
        }

        .navbar-icon:nth-child(2) {
            animation-delay: 0.15s;
        }

        .navbar-icon:nth-child(3) {
            animation-delay: 0.2s;
        }

        .navbar-icon:nth-child(4) {
            animation-delay: 0.25s;
        }

        .navbar-icon:nth-child(5) {
            animation-delay: 0.3s;
        }

        .navbar-icon:nth-child(6) {
            animation-delay: 0.35s;
        }

        .navbar-icon i {
            transition: opacity 0.3s ease, filter 0.3s ease, transform 0.3s ease, color 0.3s ease;
        }

        .navbar-icon:hover i {
            opacity: 0.85;
            filter: grayscale(20%) brightness(1.15);
            transform: scale(1.05);
            color: rgba(255, 255, 255, 0.85) !important;
        }

        .navbar-icon.active i {
            opacity: 1;
            filter: none;
            color: rgba(255, 255, 255, 1) !important;
        }

        /* Responsive Design - Mobile First Approach */
        
        /* Extra Small Devices (phones, 320px and up) */
        @media (max-width: 480px) {
            .navbar {
                left: 8px;
                gap: 8px;
                padding: 20px 0;
                width: 60px;
                border-radius: 30px;
            }

            .navbar-icon {
                width: 44px;
                height: 44px;
                font-size: 16px;
            }

            .navbar-icon img {
                width: 24px;
                height: 24px;
            }

            .navbar.hidden {
                left: -68px;
            }
        }

        /* Small Devices (landscape phones, 481px and up) */
        @media (min-width: 481px) and (max-width: 640px) {
            .navbar {
                left: 10px;
                gap: 10px;
                width: 70px;
            }

            .navbar-icon {
                width: 50px;
                height: 50px;
                font-size: 18px;
            }

            .navbar-icon img {
                width: 26px;
                height: 26px;
            }
        }

        /* Medium Devices (tablets, 641px to 768px) */
        @media (min-width: 641px) and (max-width: 768px) {
            .navbar {
                left: 12px;
                gap: 12px;
                width: 80px;
            }

            .navbar-icon {
                width: 54px;
                height: 54px;
                font-size: 20px;
            }

            .navbar-icon img {
                width: 28px;
                height: 28px;
            }
        }

        /* Standard Mobile (up to 768px) */
        @media (max-width: 768px) {
            .navbar {
                left: 10px;
                gap: 10px;
            }

            .navbar-icon {
                width: 50px;
                height: 50px;
                font-size: 18px;
            }

            .sidebar-toggle {
                left: 10px;
                top: 10px;
                width: 45px;
                height: 45px;
            }

            .logout-button {
                left: 10px;
                bottom: 20px;
                width: 50px;
                height: 50px;
            }

            .navbar.hidden {
                left: -70px;
            }

            .logout-button.hidden {
                left: -70px;
            }
        }

        /* Large Devices (desktops, 1024px and up) */
        @media (min-width: 1024px) {
            .navbar {
                left: 24px;
                gap: 28px;
                width: 92px;
            }
        }

        /* Extra Large Devices (large desktops, 1440px and up) */
        @media (min-width: 1440px) {
            .navbar {
                left: 32px;
                gap: 32px;
                width: 100px;
            }

            .navbar-icon {
                width: 62px;
                height: 62px;
            }

            .navbar-icon img {
                width: 32px;
                height: 32px;
            }
        }

        /* Zoom Support - Use relative units for better zoom compatibility */
        @media (min-resolution: 192dpi) {
            .navbar-icon img {
                image-rendering: -webkit-optimize-contrast;
                image-rendering: crisp-edges;
            }
        }

        .calendar-view,
        .settings-view,
        .dashboard-view,
        .alerts-view,
        .exports-view,
        #app {
            /* Firefox */
            scrollbar-width: none;

            /* IE and Edge */
            -ms-overflow-style: none;
        }

        /* Webkit browsers (Chrome, Safari, Opera) */
        .calendar-view::-webkit-scrollbar,
        .settings-view::-webkit-scrollbar,
        .dashboard-view::-webkit-scrollbar,
        .alerts-view::-webkit-scrollbar,
        .exports-view::-webkit-scrollbar,
        #app::-webkit-scrollbar {
            display: none;
        }

        /* Alternative: Show scrollbar only on hover (more user-friendly) */
        .calendar-view:hover::-webkit-scrollbar,
        .settings-view:hover::-webkit-scrollbar,
        .dashboard-view:hover::-webkit-scrollbar,
        .alerts-view:hover::-webkit-scrollbar,
        .exports-view:hover::-webkit-scrollbar {
            display: block;
            width: 8px;
        }

        .calendar-view:hover::-webkit-scrollbar-track,
        .settings-view:hover::-webkit-scrollbar-track,
        .dashboard-view:hover::-webkit-scrollbar-track,
        .alerts-view:hover::-webkit-scrollbar-track,
        .exports-view:hover::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
        }

        .calendar-view:hover::-webkit-scrollbar-thumb,
        .settings-view:hover::-webkit-scrollbar-thumb,
        .dashboard-view:hover::-webkit-scrollbar-thumb,
        .alerts-view:hover::-webkit-scrollbar-thumb,
        .exports-view:hover::-webkit-scrollbar-thumb {
            background: rgba(59, 130, 246, 0.5);
            border-radius: 10px;
            border: 2px solid rgba(0, 0, 0, 0.2);
        }

        .calendar-view:hover::-webkit-scrollbar-thumb:hover,
        .settings-view:hover::-webkit-scrollbar-thumb:hover,
        .dashboard-view:hover::-webkit-scrollbar-thumb:hover,
        .alerts-view:hover::-webkit-scrollbar-thumb:hover,
        .exports-view:hover::-webkit-scrollbar-thumb:hover {
            background: rgba(59, 130, 246, 0.8);
        }

        /* Custom styled scrollbar (Option 3 - Always visible but styled) */
        .custom-scrollbar {
            /* Firefox */
            scrollbar-width: thin;
            scrollbar-color: rgba(59, 130, 246, 0.5) transparent;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(59, 130, 246, 0.3);
            border-radius: 10px;
            transition: background 0.3s ease;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(59, 130, 246, 0.6);
        }

        body.drawing-boundary-mode .navbar {
            opacity: 0;
            pointer-events: none;
            transform: translateX(-24px);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        /* Smooth scrolling */
        .calendar-view,
        .settings-view,
        .dashboard-view,
        .alerts-view,
        .exports-view {
            scroll-behavior: smooth;
        }
    </style>
</head>

<body>
    <!-- Sidebar Navigation -->
    <nav class="navbar" id="sidebar">
        <div class="navbar-icon" onclick="setActiveView('dashboard', this)" title="Dashboard">
            <img src="{{ asset('assets/mage_dashboard-fill.png') }}" alt="Dashboard icon">
        </div>
        <div class="navbar-icon active" onclick="setActiveView('map', this)" title="Weather Map">
            <img src="{{ asset('assets/solar_map-linear.png') }}" alt="Weather map icon">
        </div>
        <div class="navbar-icon" onclick="setActiveView('calendar', this)" title="Calendar">
            <img src="{{ asset('assets/uil_calender.png') }}" alt="Calendar icon">
        </div>
        <div class="navbar-icon" onclick="setActiveView('alerts', this)" title="Alerts">
            <img src="{{ asset('assets/mingcute_notification-line.png') }}" alt="Alerts icon">
        </div>
        <div class="navbar-icon" onclick="setActiveView('exports', this)" title="Exports">
            <i class="fas fa-download" style="font-size: 24px; color: rgba(255, 255, 255, 0.55);"></i>
        </div>
        <div class="navbar-icon" onclick="setActiveView('settings', this)" title="Settings">
            <img src="{{ asset('assets/uil_setting.png') }}" alt="Settings icon">
        </div>
    </nav>

    <!-- Main Content -->
    <div id="app">
        @yield('content')
    </div>

    <script>
        function setActiveView(view, element) {
            document.querySelectorAll('.navbar-icon').forEach(icon => {
                icon.classList.remove('active');
            });

            if (element) {
                element.classList.add('active');
            }

            if (window.vueApp && window.vueApp.setActiveView) {
                window.vueApp.setActiveView(view);
            }

            console.log('Active view:', view);
        }
    </script>
</body>

</html>