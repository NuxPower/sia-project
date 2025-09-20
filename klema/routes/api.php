<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\FarmApiController;
use App\Http\Controllers\API\WeatherApiController;
use App\Http\Controllers\API\AlertApiController;

Route::middleware('auth:sanctum')->group(function () {
    // Farm endpoints
    Route::apiResource('farms', FarmApiController::class);
    Route::post('/farms/{farm}/points', [FarmApiController::class, 'addPoint']);
    Route::get('/farms/{farm}/weather', [FarmApiController::class, 'getWeatherData']);
    
    // Weather endpoints
    Route::get('/weather/current', [WeatherApiController::class, 'getCurrentWeather']);
    Route::get('/weather/forecast', [WeatherApiController::class, 'getForecast']);
    Route::get('/weather/historical/{date}', [WeatherApiController::class, 'getHistoricalWeather']);
    
    // Alert endpoints
    Route::apiResource('alerts', AlertApiController::class);
    Route::patch('/alerts/{alert}/resolve', [AlertApiController::class, 'resolve']);
    
    // User profile
    Route::get('/user', function (Request $request) {
        return $request->user()->load('farms');
    });
});