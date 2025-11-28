<?php

namespace App\Providers;

use App\Mail\Transports\SendGridTransport;
use App\Services\WeatherService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
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
        $this->configureSendGridTransport();
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

    private function configureSendGridTransport(): void
    {
        $apiKey = config('services.sendgrid.api_key');

        if (! $apiKey) {
            \Log::warning('SendGrid API key not configured. Emails may fail.');
            return;
        }

        Mail::extend('sendgrid', function (array $config) use ($apiKey) {
            return new SendGridTransport($apiKey);
        });

        \Log::info('SendGrid transport configured successfully');
    }
}
