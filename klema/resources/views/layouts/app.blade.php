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
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 15px;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .navbar.hidden {
            left: -80px;
            opacity: 0;
        }

        /* Navigation Icons */
        .navbar-icon {
            width: 60px;
            height: 60px;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            font-size: 20px;
            backdrop-filter: blur(15px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .navbar-icon:hover {
            background: rgba(59, 130, 246, 0.3);
            border-color: rgba(59, 130, 246, 0.5);
            transform: scale(1.1);
            box-shadow: 0 12px 40px rgba(59, 130, 246, 0.4);
        }

        .navbar-icon.active {
            background: rgba(59, 130, 246, 0.4);
            border-color: #3b82f6;
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.5);
        }

        /* Toggle Button */
        .sidebar-toggle {
            position: fixed;
            left: 20px;
            top: 20px;
            width: 50px;
            height: 50px;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid rgba(59, 130, 246, 0.3);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            font-size: 18px;
            backdrop-filter: blur(15px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            z-index: 1001;
        }

        .sidebar-toggle:hover {
            background: rgba(59, 130, 246, 0.3);
            border-color: rgba(59, 130, 246, 0.6);
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 8px 30px rgba(59, 130, 246, 0.4);
        }

        .sidebar-toggle.active {
            background: rgba(59, 130, 246, 0.4);
            border-color: #3b82f6;
        }

        .sidebar-toggle i {
            transition: transform 0.3s ease;
        }

        .sidebar-toggle.sidebar-hidden i {
            transform: rotate(180deg);
        }

        /* Logout Button */
        .logout-button {
            position: fixed;
            bottom: 30px;
            left: 20px;
            width: 60px;
            height: 60px;
            background: rgba(220, 38, 38, 0.8);
            border: 2px solid rgba(239, 68, 68, 0.3);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            color: white;
            font-size: 20px;
            backdrop-filter: blur(15px);
            box-shadow: 0 8px 32px rgba(220, 38, 38, 0.3);
            z-index: 1000;
            text-decoration: none;
        }

        .logout-button:hover {
            background: rgba(220, 38, 38, 1);
            border-color: rgba(239, 68, 68, 0.8);
            transform: scale(1.1);
            box-shadow: 0 12px 40px rgba(220, 38, 38, 0.5);
            color: white;
            text-decoration: none;
        }

        .logout-button.hidden {
            left: -80px;
            opacity: 0;
        }

        /* Tooltip for hidden state */
        .sidebar-hint {
            position: fixed;
            left: 80px;
            top: 20px;
            background: rgba(59, 130, 246, 0.9);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            z-index: 999;
            white-space: nowrap;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-hint::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-top: 6px solid transparent;
            border-bottom: 6px solid transparent;
            border-right: 6px solid rgba(59, 130, 246, 0.9);
        }

        .sidebar-toggle:hover+.sidebar-hint {
            opacity: 1;
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
    <!-- Sidebar Toggle Button -->
    <div class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>
    <div class="sidebar-hint">Toggle Menu</div>

    <!-- Sidebar Navigation -->
    <nav class="navbar" id="sidebar">
        <div class="navbar-icon active" onclick="setActiveView('dashboard')" title="Dashboard">
            <i class="fas fa-th-large"></i>
        </div>
        <div class="navbar-icon" onclick="setActiveView('map')" title="Weather Map">
            <i class="fas fa-map"></i>
        </div>
        <div class="navbar-icon" onclick="setActiveView('calendar')" title="Calendar">
            <i class="fas fa-calendar"></i>
        </div>
        <div class="navbar-icon" onclick="setActiveView('alerts')" title="Alerts">
            <i class="fas fa-bell"></i>
        </div>
        <div class="navbar-icon" onclick="setActiveView('settings')" title="Settings">
            <i class="fas fa-cog"></i>
        </div>
    </nav>

    <!-- Logout Button -->
    <a href="#" class="logout-button" id="logoutButton"
        onclick="event.preventDefault(); document.getElementById('logout-form').submit();" title="Logout">
        <i class="fas fa-sign-out-alt"></i>
    </a>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>

    <!-- Main Content -->
    <div id="app">
        @yield('content')
    </div>

    <script>
        // Sidebar state management
        let sidebarVisible = true;

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const logoutButton = document.getElementById('logoutButton');
            const toggleButton = document.getElementById('sidebarToggle');

            sidebarVisible = !sidebarVisible;

            if (sidebarVisible) {
                // Show sidebar
                sidebar.classList.remove('hidden');
                logoutButton.classList.remove('hidden');
                toggleButton.classList.remove('sidebar-hidden');
            } else {
                // Hide sidebar
                sidebar.classList.add('hidden');
                logoutButton.classList.add('hidden');
                toggleButton.classList.add('sidebar-hidden');
            }

            // Save state to localStorage
            localStorage.setItem('sidebarVisible', sidebarVisible);

            // Add a little haptic feedback effect
            toggleButton.style.transform = 'scale(0.9)';
            setTimeout(() => {
                toggleButton.style.transform = 'scale(1)';
            }, 100);
        }

        function setActiveView(view) {
            document.querySelectorAll('.navbar-icon').forEach(icon => {
                icon.classList.remove('active');
            });

            event.currentTarget.classList.add('active');

            if (window.vueApp && window.vueApp.setActiveView) {
                window.vueApp.setActiveView(view);
            }

            console.log('Active view:', view);
        }

        // Restore sidebar state on page load
        document.addEventListener('DOMContentLoaded', function () {
            const savedState = localStorage.getItem('sidebarVisible');

            if (savedState === 'false') {
                const sidebar = document.getElementById('sidebar');
                const logoutButton = document.getElementById('logoutButton');
                const toggleButton = document.getElementById('sidebarToggle');

                sidebar.classList.add('hidden');
                logoutButton.classList.add('hidden');
                toggleButton.classList.add('sidebar-hidden');
                sidebarVisible = false;
            }
        });

        // Keyboard shortcut: Press 'B' to toggle sidebar
        document.addEventListener('keydown', function (event) {
            if (event.key === 'b' || event.key === 'B') {
                // Only if not typing in an input field
                if (!['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
                    toggleSidebar();
                }
            }
        });
    </script>
</body>

</html>