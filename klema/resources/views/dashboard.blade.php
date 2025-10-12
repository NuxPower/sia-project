@extends('layouts.app')

@section('content')
<div id="app">
    <!-- Vue Weather Dashboard will be mounted here -->
</div>

<noscript>
    <div style="padding: 20px; text-align: center; color: white;">
        <h1>Weather Dashboard</h1>
        <p>Please enable JavaScript to view the weather dashboard.</p>
    </div>
</noscript>

<style>
/* Leaflet Layer Control Styling */
.leaflet-control-layers {
    background: rgba(0, 0, 0, 0.8) !important;
    border-radius: 12px !important;
    color: white !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    backdrop-filter: blur(10px) !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    min-width: 200px !important;
}

.leaflet-control-layers:not(.leaflet-control-layers-collapsed) {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5) !important;
    border: 2px solid rgba(255, 255, 255, 0.4) !important;
}

.leaflet-control-layers-toggle {
    border-radius: 12px !important;
}

.leaflet-control-layers label {
    color: white !important;
    font-weight: 500;
    font-size: 14px !important;
    padding: 8px 12px !important;
    margin: 0 !important;
    cursor: pointer !important;
}

.leaflet-control-layers label:hover {
    background-color: rgba(255, 255, 255, 0.1) !important;
}

.leaflet-control-layers-toggle {
    background-color: rgba(0, 0, 0, 0.8) !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
}

/* Zoom Controls */
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
@endsection