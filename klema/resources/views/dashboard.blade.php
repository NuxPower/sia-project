@extends('layouts.app')

@section('content')

<div id="app">
    <!-- Vue root component will be rendered here automatically -->
        </div>
        

<!-- Fallback content in case Vue doesn't load -->
<noscript>
    <div style="padding: 20px; text-align: center;">
        <h1>Weather Dashboard</h1>
        <p>Please enable JavaScript to view the weather dashboard.</p>
                        </div>

</noscript>

<style>
/* Weather overlay styles */
.clouds-overlay {
    filter: brightness(1.3) contrast(0.7) saturate(0.5);
    mix-blend-mode: screen;
}

/* Real OpenWeatherMap layers */
.clouds-real {
    mix-blend-mode: screen;
    opacity: 0.8;
}

.precipitation-real {
    mix-blend-mode: multiply;
    opacity: 0.8;
}

.temperature-real {
    mix-blend-mode: overlay;
    opacity: 0.8;
}

.precipitation-overlay {
    filter: hue-rotate(200deg) brightness(0.8) contrast(1.2);
    mix-blend-mode: multiply;
}

.temperature-overlay {
    filter: hue-rotate(30deg) brightness(1.1) contrast(1.1);
    mix-blend-mode: overlay;
}

.wind-overlay {
    filter: brightness(1.1) contrast(0.9);
    mix-blend-mode: difference;
    animation: windFlow 3s ease-in-out infinite alternate;
}

@keyframes windFlow {
    0% { transform: translateX(0px); }
    100% { transform: translateX(2px); }
}

/* Layer control styling */
.leaflet-control-layers {
    background: rgba(0, 0, 0, 0.8) !important;
    border-radius: 12px !important;
    color: white !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    backdrop-filter: blur(10px) !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    min-width: 200px !important;
    transition: all 0.3s ease !important;
    transform-origin: center right !important;
}

/* Enhanced pop-out effect when expanded */
.leaflet-control-layers:not(.leaflet-control-layers-collapsed) {
    animation: expandFromSquare 0.4s ease-out !important;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5) !important;
    border: 2px solid rgba(255, 255, 255, 0.4) !important;
}

/* Square toggle button styling */
.leaflet-control-layers-toggle {
    border-radius: 12px !important;
    transition: all 0.3s ease !important;
}

@keyframes expandFromSquare {
    0% {
        transform: translateY(-50%) scale(0.9);
        width: 50px;
        opacity: 0.8;
    }
    30% {
        transform: translateY(-50%) scale(1.1);
        width: 50px;
        opacity: 1;
    }
    70% {
        transform: translateY(-50%) scale(1);
        width: 200px;
        opacity: 1;
    }
    100% {
        transform: translateY(-50%) scale(1);
        width: auto;
        opacity: 1;
    }
}

.leaflet-control-layers label {
    color: white !important;
    font-weight: 500;
    font-size: 14px !important;
    padding: 8px 12px !important;
    margin: 0 !important;
    cursor: pointer !important;
    transition: background-color 0.2s ease !important;
}

.leaflet-control-layers label:hover {
    background-color: rgba(255, 255, 255, 0.1) !important;
}

.leaflet-control-layers-toggle {
    background-color: rgba(0, 0, 0, 0.8) !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
}

/* Zoom controls styling */
.leaflet-control-zoom {
    background: rgba(0, 0, 0, 0.8) !important;
    border-radius: 8px !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
}

.leaflet-control-zoom a {
    background-color: rgba(0, 0, 0, 0.8) !important;
    color: white !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2) !important;
}

.leaflet-control-zoom a:hover {
    background-color: rgba(0, 0, 0, 0.9) !important;
}
</style>

<script>
// Debug script
console.log('Dashboard page loaded');
console.log('Vue app element:', document.getElementById('app'));
</script>
@endsection

