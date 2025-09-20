<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;
use App\Services\WeatherService;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(WeatherService::class, function ($app) {
            return new WeatherService();
        });
    }

    public function boot()
    {
        // Configure HTTP client for development (XAMPP SSL issues)
        if (app()->environment('local')) {
            Http::macro('weather', function () {
                return Http::withOptions([
                    'verify' => false, // Disable SSL verification for XAMPP
                    'timeout' => 10,
                ]);
            });
        }
    }
}
