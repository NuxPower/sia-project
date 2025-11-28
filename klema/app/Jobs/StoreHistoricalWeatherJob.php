<?php

namespace App\Jobs;

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

class StoreHistoricalWeatherJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $historicalSeries,
        public float $lat,
        public float $lon,
        public ?string $locationName = null,
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
            $lat = round($this->lat, 6);
            $lon = round($this->lon, 6);
            $locationName = $this->locationName ? Str::lower(trim($this->locationName)) : null;

            // Try to find associated farm if not provided
            $farmId = $this->farmId;
            if (!$farmId && $lat !== null && $lon !== null) {
                $farm = $this->findFarmByCoordinates($lat, $lon);
                if ($farm) {
                    $farmId = $farm->farm_id;
                    $locationName = Str::lower(trim($farm->farm_name));
                }
            }
            
            // If no location name provided, try to reverse geocode
            if (!$locationName && $lat !== null && $lon !== null) {
                $geocoded = $weatherService->reverseGeocodeCoordinates($lat, $lon);
                if ($geocoded) {
                    $locationName = Str::lower(trim($geocoded['name']));
                }
            }

            // Store each day in the historical series (30 days back)
            foreach ($this->historicalSeries as $dateKey => $entry) {
                try {
                    $dateObj = Carbon::parse($dateKey, 'UTC')->startOfDay();
                    
                    // Skip today and future dates - only store historical (past) data
                    $todayUtc = Carbon::today('UTC')->startOfDay();
                    if ($dateObj->gte($todayUtc)) {
                        continue;
                    }

                    // Prepare weather data payload in format expected by storeWeatherSnapshot
                    $weatherPayload = $this->convertHistoricalEntryToWeatherFormat($entry, $dateKey);

                    // Store using the existing method - stores to weather_data table
                    $weatherService->storeWeatherSnapshot($weatherPayload, [
                        'farm_id' => $farmId,
                        'location' => $locationName,
                        'lat' => $lat,
                        'lon' => $lon,
                        'recorded_at' => $dateObj,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to store historical weather entry', [
                        'date' => $dateKey,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue with next entry
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to store historical weather data in background', [
                'error' => $e->getMessage(),
                'lat' => $this->lat,
                'lon' => $this->lon,
                'location' => $this->locationName,
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Don't throw - let the job fail gracefully
        }
    }
    
    /**
     * Find a farm by coordinates (within 0.005 degree tolerance, ~500m).
     */
    private function findFarmByCoordinates(float $lat, float $lon): ?Farm
    {
        $tolerance = 0.005;
        
        return Farm::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$lat - $tolerance, $lat + $tolerance])
            ->whereBetween('longitude', [$lon - $tolerance, $lon + $tolerance])
            ->first();
    }

    /**
     * Convert historical entry format to weather snapshot format.
     */
    private function convertHistoricalEntryToWeatherFormat(array $entry, string $dateKey): array
    {
        $main = data_get($entry, 'main', []);
        $weather = data_get($entry, 'weather', []);
        $wind = data_get($entry, 'wind', []);

        return [
            'dt' => data_get($entry, 'timestamp', Carbon::parse($dateKey)->timestamp),
            'timestamp' => data_get($entry, 'timestamp', Carbon::parse($dateKey)->timestamp),
            'coord' => [
                'lat' => $this->lat,
                'lon' => $this->lon,
            ],
            'main' => [
                'temp' => data_get($main, 'temp'),
                'temp_min' => data_get($main, 'temp_min'),
                'temp_max' => data_get($main, 'temp_max'),
                'humidity' => data_get($main, 'humidity'),
                'pressure' => data_get($main, 'pressure'),
            ],
            'weather' => !empty($weather) ? $weather : [[
                'main' => data_get($entry, 'condition', 'Unknown'),
                'description' => data_get($entry, 'description', 'Unknown'),
                'icon' => data_get($entry, 'icon', '01d'),
            ]],
            'wind' => [
                'speed' => data_get($wind, 'speed'),
            ],
            'rain' => [
                '1h' => data_get($entry, 'precipitation_sum', 0),
            ],
            'name' => $this->locationName ?? data_get($entry, 'name'),
            'sunrise' => data_get($entry, 'sunrise'),
            'sunset' => data_get($entry, 'sunset'),
        ];
    }
}

