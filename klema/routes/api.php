<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\FarmApiController;
use App\Http\Controllers\API\WeatherApiController;
use App\Http\Controllers\API\AlertApiController;
use App\Http\Controllers\API\AuthApiController;
use App\Http\Controllers\API\ActivityApiController;

Route::middleware('web')->group(function () {
    Route::post('/login', [AuthApiController::class, 'login']);
    Route::post('/register', [AuthApiController::class, 'register']);
});

// Public weather endpoints
Route::prefix('weather')->group(function () {
    Route::get('/current', [WeatherApiController::class, 'getCurrentWeather']);
    Route::get('/forecast', [WeatherApiController::class, 'getForecast']);
    Route::get('/history', [WeatherApiController::class, 'getWeatherHistory']);
    Route::get('/historical/{date}', [WeatherApiController::class, 'getHistoricalWeather']);
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/logout', [AuthApiController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user()->load('farms');
    });
    Route::post('/email/verification-notification', [AuthApiController::class, 'sendVerificationEmail'])->middleware('throttle:6,1');
});

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    // Farm endpoints
    Route::apiResource('farms', FarmApiController::class);
    Route::post('/farms/{farm}/points', [FarmApiController::class, 'addPoint']);
    Route::get('/farms/{farm}/weather', [FarmApiController::class, 'getWeatherData']);
    
    // Alert endpoints - specific routes must come before apiResource
    Route::get('/alerts/active', [AlertApiController::class, 'active']);
    Route::get('/alerts/forecast-warnings', [AlertApiController::class, 'forecastWarnings']);
    Route::patch('/alerts/{alert}/resolve', [AlertApiController::class, 'resolve']);
    Route::apiResource('alerts', AlertApiController::class);

    // Activity endpoints
    Route::apiResource('activities', ActivityApiController::class)->only(['index', 'store']);
    
});