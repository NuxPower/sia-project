<?php

// routes/web.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FarmController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Auth\AuthController;

Route::get('/', function () {
    return redirect('/dashboard');
});

// Test route for weather service
Route::get('/test-weather', function () {
    $weather = app(App\Services\WeatherService::class);
    $current = $weather->getCurrentWeather('Butuan, Caraga, PH');
    return response()->json($current);
});

Route::middleware('auth')->group(function () {
    // Dashboard routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/calendar', [DashboardController::class, 'calendar'])->name('calendar');
    
    // Farm management
    Route::resource('farms', FarmController::class)->parameters([
        'farms' => 'farm:farm_id'
    ]);
    Route::post('/farms/{farm:farm_id}/points', [FarmController::class, 'addPoint'])->name('farms.add-point');
    
    // Alert management
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::post('/alerts', [AlertController::class, 'store'])->name('alerts.store');
    Route::patch('/alerts/{alert:alert_id}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');
    Route::delete('/alerts/{alert:alert_id}', [AlertController::class, 'destroy'])->name('alerts.destroy');
    
    // Weather API endpoints
    Route::get('/api/weather/current', [WeatherController::class, 'getCurrentWeather']);
    Route::get('/api/weather/forecast', [WeatherController::class, 'getForecast']);
    
    // Activity management
    Route::resource('activities', ActivityController::class);
    Route::get('/activities/{activity}/suitability', [ActivityController::class, 'checkSuitability'])->name('activities.suitability');
    
    // Export routes
    Route::get('/exports', [ExportController::class, 'index'])->name('exports.index');
    Route::post('/exports/weather', [ExportController::class, 'exportWeatherData'])->name('exports.weather');
    Route::post('/exports/farms', [ExportController::class, 'exportFarmData'])->name('exports.farms');
    Route::get('/exports/{export:export_id}/download', [ExportController::class, 'download'])->name('exports.download');
});

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);