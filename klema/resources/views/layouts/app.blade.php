<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
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
        
        .navbar {
            position: fixed;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
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
        
        .search-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .search-box {
            display: flex;
            align-items: center;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 16px 20px;
            color: white;
            min-width: 320px;
            backdrop-filter: blur(15px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        .search-box:hover {
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 12px 40px rgba(59, 130, 246, 0.3);
        }
        
        .search-box i {
            margin-right: 12px;
            color: #9ca3af;
        }
        
        .search-box input {
            background: none;
            border: none;
            color: white;
            outline: none;
            flex: 1;
            font-size: 16px;
        }
        
        .search-box input::placeholder {
            color: #9ca3af;
        }
        
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
            transition: all 0.3s ease;
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
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <nav class="navbar">
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
    <a href="#" class="logout-button" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" title="Logout">
        <i class="fas fa-sign-out-alt"></i>
    </a>
    
    <!-- Hidden logout form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>

    <!-- Main Content -->
    <div id="app">
        @yield('content')
    </div>

    <script>
        // Navigation functionality
        function setActiveView(view) {
            // Remove active class from all icons
            document.querySelectorAll('.navbar-icon').forEach(icon => {
                icon.classList.remove('active');
            });
            
            // Add active class to clicked icon
            event.currentTarget.classList.add('active');
            
            // Trigger view change in Vue app if available
            if (window.vueApp && window.vueApp.setActiveView) {
                window.vueApp.setActiveView(view);
            }
            
            console.log('Active view set to:', view);
        }
    </script>