<?php

namespace App\Providers;

use App\Services\WeatherService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WeatherService::class, fn () => new WeatherService());
    }

    public function boot(): void
    {
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        $this->configureHttpMacros();
        $this->configureRateLimiting();
    }

    private function configureHttpMacros(): void
    {
        if (Http::hasMacro('weather')) {
            return;
        }

        Http::macro('weather', function () {
            $options = [
                'timeout' => 10,
            ];

            if (app()->environment('local')) {
                $options['verify'] = false;
            }

            return Http::withOptions($options);
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $key = Str::lower((string) $request->input('email')) . '|' . $request->ip();

            return [
                Limit::perMinute(5)->by($key),
                Limit::perMinute(30)->by($request->ip())->response(function () {
                    return response()->json([
                        'message' => 'Too many login attempts. Please slow down.',
                    ], 429);
                }),
            ];
        });

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));
    }
}
