<?php

namespace App\Jobs;

use App\Models\Forecast;
use App\Services\WeatherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class StoreForecastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $forecastData,
        public ?string $locationName = null,
        public ?float $lat = null,
        public ?float $lon = null,
        public ?int $farmId = null
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
            // Check if forecasts table exists
            if (!Schema::hasTable('forecasts')) {
                Log::warning('Cannot store forecast data - forecasts table does not exist');
                return;
            }

            // Use the existing storeForecastData method
            $weatherService->storeForecastData($this->forecastData, $this->locationName, $this->lat, $this->lon);
        } catch (\Throwable $e) {
            Log::error('Failed to store forecast data in background', [
                'error' => $e->getMessage(),
                'location' => $this->locationName,
                'lat' => $this->lat,
                'lon' => $this->lon,
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Don't throw - let the job fail gracefully
        }
    }
}

