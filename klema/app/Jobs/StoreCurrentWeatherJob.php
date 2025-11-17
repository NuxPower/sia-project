<?php

namespace App\Jobs;

use App\Models\WeatherData;
use App\Models\Farm;
use App\Services\WeatherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StoreCurrentWeatherJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $weather,
        public array $context = []
    ) {
        // Set queue connection if needed
        $this->onQueue('weather');
    }

    /**
     * Execute the job.
     */
    public function handle(WeatherService $weatherService): void
    {
        try {
            // Use the existing storeWeatherSnapshot method
            $weatherService->storeWeatherSnapshot($this->weather, $this->context);
        } catch (\Throwable $e) {
            Log::error('Failed to store current weather data in background', [
                'error' => $e->getMessage(),
                'location' => $this->context['location'] ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Don't throw - let the job fail gracefully
            // Weather data will be re-fetched on next request
        }
    }
}

