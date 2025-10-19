<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\FarmApiController;
use App\Http\Controllers\API\WeatherApiController;
use App\Http\Controllers\API\AlertApiController;
use App\Http\Controllers\API\AuthApiController;

// Public authentication routes
Route::post('/login', [AuthApiController::class, 'login']);

Route::middleware(['web', 'auth'])->group(function () {
    // Logout route
    Route::post('/logout', [AuthApiController::class, 'logout']);
    // Farm endpoints
    Route::apiResource('farms', FarmApiController::class);
    Route::post('/farms/{farm}/points', [FarmApiController::class, 'addPoint']);
    Route::get('/farms/{farm}/weather', [FarmApiController::class, 'getWeatherData']);
    
    // Weather endpoints
    Route::get('/weather/current', [WeatherApiController::class, 'getCurrentWeather']);
    Route::get('/weather/forecast', [WeatherApiController::class, 'getForecast']);
    Route::get('/weather/historical/{date}', [WeatherApiController::class, 'getHistoricalWeather']);
    
    // Alert endpoints - specific routes must come before apiResource
    Route::get('/alerts/active', [AlertApiController::class, 'active']);
    Route::get('/alerts/forecast-warnings', [AlertApiController::class, 'forecastWarnings']);
    Route::patch('/alerts/{alert}/resolve', [AlertApiController::class, 'resolve']);
    Route::apiResource('alerts', AlertApiController::class);
    
    // User profile
    Route::get('/user', function (Request $request) {
        return $request->user()->load('farms');
    });
});