<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\FarmApiController;
use App\Http\Controllers\API\WeatherApiController;
use App\Http\Controllers\API\AlertApiController;
use App\Http\Controllers\API\AuthApiController;
use App\Http\Controllers\API\ActivityApiController;

Route::prefix('auth')->group(function () {
    Route::middleware('throttle:login')->post('/login', [AuthApiController::class, 'login']);
    Route::middleware('throttle:register')->post('/register', [AuthApiController::class, 'register']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [AuthApiController::class, 'logout']);
        Route::post('/email/verification-notification', [AuthApiController::class, 'sendVerificationEmail'])
            ->middleware('throttle:6,1');
        Route::get('/me', function (Request $request) {
            return response()->json([
                'success' => true,
                'user' => $request->user()->load('farms'),
            ]);
        });
    });
});

// Allow session-authenticated dashboard users to mint SPA tokens
Route::middleware(['web', 'auth'])->post('/auth/token', [AuthApiController::class, 'issueToken']);

Route::middleware(['auth:sanctum', 'verified', 'role:admin,farmer'])->group(function () {
    // Weather endpoints
    Route::prefix('weather')->group(function () {
        Route::get('/current', [WeatherApiController::class, 'getCurrentWeather']);
        Route::get('/forecast', [WeatherApiController::class, 'getForecast']);
        Route::get('/history', [WeatherApiController::class, 'getWeatherHistory']);
        Route::get('/historical/{date}', [WeatherApiController::class, 'getHistoricalWeather']);
    });

    // Farm endpoints
    Route::apiResource('farms', FarmApiController::class);
    Route::post('/farms/{farm}/points', [FarmApiController::class, 'addPoint']);
    Route::get('/farms/{farm}/weather', [FarmApiController::class, 'getWeatherData']);

    // Alert endpoints
    Route::get('/alerts/active', [AlertApiController::class, 'active']);
    Route::get('/alerts/forecast-warnings', [AlertApiController::class, 'forecastWarnings']);
    Route::patch('/alerts/{alert}/resolve', [AlertApiController::class, 'resolve']);
    Route::apiResource('alerts', AlertApiController::class);

    // Activity endpoints
    Route::apiResource('activities', ActivityApiController::class)->only(['index', 'store']);
});