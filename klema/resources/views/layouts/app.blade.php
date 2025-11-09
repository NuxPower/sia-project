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
            overflow: hidden;
            background: linear-gradient(135deg, #0f172a, #1e293b);
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

        /* Mobile Responsive */
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

        .calendar-view,
        .settings-view,
        .dashboard-view,
        .alerts-view,
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
        #app::-webkit-scrollbar {
            display: none;
        }

        /* Alternative: Show scrollbar only on hover (more user-friendly) */
        .calendar-view:hover::-webkit-scrollbar,
        .settings-view:hover::-webkit-scrollbar,
        .dashboard-view:hover::-webkit-scrollbar,
        .alerts-view:hover::-webkit-scrollbar {
            display: block;
            width: 8px;
        }

        .calendar-view:hover::-webkit-scrollbar-track,
        .settings-view:hover::-webkit-scrollbar-track,
        .dashboard-view:hover::-webkit-scrollbar-track,
        .alerts-view:hover::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
        }

        .calendar-view:hover::-webkit-scrollbar-thumb,
        .settings-view:hover::-webkit-scrollbar-thumb,
        .dashboard-view:hover::-webkit-scrollbar-thumb,
        .alerts-view:hover::-webkit-scrollbar-thumb {
            background: rgba(59, 130, 246, 0.5);
            border-radius: 10px;
            border: 2px solid rgba(0, 0, 0, 0.2);
        }

        .calendar-view:hover::-webkit-scrollbar-thumb:hover,
        .settings-view:hover::-webkit-scrollbar-thumb:hover,
        .dashboard-view:hover::-webkit-scrollbar-thumb:hover,
        .alerts-view:hover::-webkit-scrollbar-thumb:hover {
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

        /* Smooth scrolling */
        .calendar-view,
        .settings-view,
        .dashboard-view,
        .alerts-view {
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