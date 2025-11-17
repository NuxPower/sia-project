<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\FarmApiController;
use App\Http\Controllers\API\WeatherApiController;
use App\Http\Controllers\API\AlertApiController;
use App\Http\Controllers\API\AuthApiController;
use App\Http\Controllers\API\ActivityApiController;
use App\Http\Controllers\API\ExportApiController;
use App\Http\Controllers\API\SettingsApiController;

Route::prefix('auth')->group(function () {
    Route::middleware('throttle:login')->post('/login', [AuthApiController::class, 'login']);
    Route::middleware('throttle:register')->post('/register', [AuthApiController::class, 'register']);
    Route::post('/password/forgot', [AuthApiController::class, 'sendPasswordResetLink'])->middleware('throttle:6,1');
    Route::post('/password/reset', [AuthApiController::class, 'resetPassword'])->middleware('throttle:6,1');
    Route::post('/email/resend', [AuthApiController::class, 'resendVerificationForEmail'])->middleware('throttle:6,1');

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

Route::middleware(['auth:sanctum', 'verified'])
    ->name('api.')
    ->group(function () {
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
    Route::get('/map/farms', [FarmApiController::class, 'mapData']);

    // Alert endpoints
    Route::get('/alerts/active', [AlertApiController::class, 'active']);
    Route::get('/alerts/forecast-warnings', [AlertApiController::class, 'forecastWarnings']);
    Route::patch('/alerts/{alert}/resolve', [AlertApiController::class, 'resolve'])->whereNumber('alert');
    Route::apiResource('alerts', AlertApiController::class)->whereNumber('alert');

    // Activity endpoints
    Route::get('/activities/meta', [ActivityApiController::class, 'meta']);
    Route::get('/activities/recommendation', [ActivityApiController::class, 'recommendation']);
    Route::apiResource('activities', ActivityApiController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

    // Export endpoints
    Route::get('/exports', [ExportApiController::class, 'index']);
    Route::post('/exports/weather', [ExportApiController::class, 'exportWeatherData']);
    Route::post('/exports/farms', [ExportApiController::class, 'exportFarmData']);
    Route::post('/exports/activities', [ExportApiController::class, 'exportActivityData']);
    Route::get('/exports/{export:export_id}/download', [ExportApiController::class, 'download'])->name('exports.download');

    // Settings endpoints
    Route::get('/settings', [SettingsApiController::class, 'index']);
    Route::put('/settings', [SettingsApiController::class, 'update']);
    Route::post('/settings/reset', [SettingsApiController::class, 'reset']);

    // Admin endpoints (now accessible to all farmers)
    Route::prefix('admin')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\DashboardController::class, 'getSystemStats']);
        Route::get('/farmers', [\App\Http\Controllers\DashboardController::class, 'getFarmersWithFarms']);
        Route::post('/weather/update', [WeatherApiController::class, 'manualUpdate']);
        Route::apiResource('users', \App\Http\Controllers\UserController::class);
        Route::post('/users/{user}/reset-password', [\App\Http\Controllers\UserController::class, 'resetPassword']);
        Route::get('/users/{user}/sessions', [\App\Http\Controllers\UserController::class, 'getSessions']);
        Route::delete('/users/{user}/sessions', [\App\Http\Controllers\UserController::class, 'revokeSessions']);
    });
});