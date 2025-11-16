<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Farm;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AlertAutomationService
{
    private const FORECAST_DAYS = 7;
    private const DUPLICATE_WINDOW_HOURS = 18;

    public function __construct(private WeatherService $weatherService)
    {
    }

    public function run(): array
    {
        $summary = [
            'farms_scanned' => 0,
            'alerts_created' => 0,
            'failures' => [],
        ];

        Farm::chunkById(25, function (Collection $farms) use (&$summary) {
            foreach ($farms as $farm) {
                $summary['farms_scanned']++;
                $result = $this->runForFarm($farm);
                $summary['alerts_created'] += $result['created'];

                if ($result['status'] !== 'ok') {
                    $summary['failures'][] = [
                        'farm_id' => $farm->farm_id,
                        'reason' => $result['reason'],
                    ];
                }
            }
        });

        return $summary;
    }

    public function runForFarm(Farm $farm): array
    {
        if ($farm->latitude === null || $farm->longitude === null) {
            return [
                'status' => 'skipped',
                'reason' => 'missing_coordinates',
                'created' => 0,
                'scenarios' => [],
            ];
        }

        try {
            $forecast = $this->weatherService->getForecastByCoordinates(
                (float) $farm->latitude,
                (float) $farm->longitude,
                self::FORECAST_DAYS
            );
        } catch (\Throwable $exception) {
            Log::warning('Automatic alert generation failed to fetch forecast', [
                'farm_id' => $farm->farm_id,
                'message' => $exception->getMessage(),
            ]);

            return [
                'status' => 'error',
                'reason' => 'forecast_unavailable',
                'created' => 0,
                'scenarios' => [],
            ];
        }

        if (empty($forecast)) {
            return [
                'status' => 'skipped',
                'reason' => 'empty_forecast',
                'created' => 0,
                'scenarios' => [],
            ];
        }

        $days = $this->normalizeForecastDays($forecast);
        if (empty($days)) {
            return [
                'status' => 'skipped',
                'reason' => 'unusable_forecast',
                'created' => 0,
                'scenarios' => [],
            ];
        }

        $scenarios = array_values(array_filter([
            $this->detectWeatherScenario($farm, $days),
            $this->detectIrrigationScenario($farm, $days),
            $this->detectHarvestScenario($farm, $days),
            $this->detectMaintenanceScenario($farm, $days),
        ]));

        $created = 0;
        foreach ($scenarios as $scenario) {
            if ($this->createAlertIfMissing($farm, $scenario)) {
                $created++;
            }
        }

        return [
            'status' => 'ok',
            'reason' => null,
            'created' => $created,
            'scenarios' => $scenarios,
        ];
    }

    private function normalizeForecastDays(array $forecast): array
    {
        $days = array_slice($forecast, 0, self::FORECAST_DAYS);

        return array_map(function (array $day) {
            $date = Carbon::parse($day['date'] ?? $day['day'] ?? 'now', 'UTC')->startOfDay();
            $hourly = $day['hourly'] ?? [];

            $precipitation = $this->sumPrecipitation($hourly);
            $maxWind = $this->maxWindSpeed($hourly);
            $condition = strtolower($day['description'] ?? $day['condition'] ?? '');

            return [
                'date' => $date,
                'label' => $this->describeDay($date),
                'temp_max' => isset($day['temp_max']) ? (float) $day['temp_max'] : null,
                'temp_min' => isset($day['temp_min']) ? (float) $day['temp_min'] : null,
                'condition' => $condition,
                'precip_mm' => $precipitation,
                'wind_max_kmh' => $maxWind,
                'has_storm' => str_contains($condition, 'storm') || str_contains($condition, 'thunder'),
                'has_rain_keyword' => str_contains($condition, 'rain') || str_contains($condition, 'drizzle'),
            ];
        }, $days);
    }

    private function detectWeatherScenario(Farm $farm, array $days): ?array
    {
        foreach ($days as $day) {
            if (isset($day['temp_max']) && $day['temp_max'] >= 34) {
                $message = sprintf(
                    'High heat expected %s for %s — forecast highs near %s°C.',
                    $day['label'],
                    $farm->farm_name,
                    round($day['temp_max'])
                );

                return [
                    'type' => 'weather',
                    'signature' => $this->signature('weather-heat', $day['date']),
                    'message' => $message,
                ];
            }

            if (isset($day['temp_min']) && $day['temp_min'] <= 8) {
                $message = sprintf(
                    'Cold snap expected %s for %s — lows could reach %s°C.',
                    $day['label'],
                    $farm->farm_name,
                    round($day['temp_min'])
                );

                return [
                    'type' => 'weather',
                    'signature' => $this->signature('weather-cold', $day['date']),
                    'message' => $message,
                ];
            }
        }

        return null;
    }

    private function detectIrrigationScenario(Farm $farm, array $days): ?array
    {
        $streak = [];

        foreach ($days as $day) {
            $isDry = $day['precip_mm'] < 1.5 && !$day['has_rain_keyword'];

            if ($isDry) {
                $streak[] = $day;
                continue;
            }

            if (count($streak) >= 3) {
                break;
            }

            $streak = [];
        }

        if (count($streak) < 3) {
            return null;
        }

        $start = $streak[0]['date'];
        $end = Arr::last($streak)['date'];
        $message = sprintf(
            'Dry spell expected from %s to %s for %s — consider adjusting irrigation plans.',
            $start->format('M j'),
            $end->format('M j'),
            $farm->farm_name
        );

        return [
            'type' => 'irrigation',
            'signature' => sprintf('irrigation-dry-%s-%s', $start->toDateString(), $end->toDateString()),
            'message' => $message,
        ];
    }

    private function detectHarvestScenario(Farm $farm, array $days): ?array
    {
        $window = [];

        foreach ($days as $day) {
            $suitable = $day['precip_mm'] < 2
                && $day['wind_max_kmh'] <= 30
                && !$day['has_rain_keyword']
                && isset($day['temp_max'])
                && $day['temp_max'] >= 18
                && $day['temp_max'] <= 32;

            if ($suitable) {
                $window[] = $day;
            } else {
                if (count($window) >= 2) {
                    break;
                }
                $window = [];
            }
        }

        if (count($window) < 2) {
            return null;
        }

        $start = $window[0]['date'];
        $end = Arr::last($window)['date'];
        $message = sprintf(
            'Stable harvest window expected from %s to %s for %s — calm winds and dry skies forecast.',
            $start->format('M j'),
            $end->format('M j'),
            $farm->farm_name
        );

        return [
            'type' => 'harvest',
            'signature' => sprintf('harvest-window-%s-%s', $start->toDateString(), $end->toDateString()),
            'message' => $message,
        ];
    }

    private function detectMaintenanceScenario(Farm $farm, array $days): ?array
    {
        foreach ($days as $day) {
            $severeWind = $day['wind_max_kmh'] >= 45;
            $heavyRain = $day['precip_mm'] >= 30;
            $storm = $day['has_storm'];

            if (!($severeWind || $heavyRain || $storm)) {
                continue;
            }

            $details = array_filter([
                $heavyRain ? sprintf('~%s mm rain', round($day['precip_mm'])) : null,
                $severeWind ? sprintf('winds up to %s km/h', round($day['wind_max_kmh'])) : null,
                $storm ? 'thunderstorm activity' : null,
            ]);

            $message = sprintf(
                'Secure equipment before %s (%s) at %s.',
                $day['label'],
                implode(' • ', $details),
                $farm->farm_name
            );

            return [
                'type' => 'maintenance',
                'signature' => $this->signature('maintenance', $day['date']),
                'message' => $message,
            ];
        }

        return null;
    }

    private function createAlertIfMissing(Farm $farm, array $scenario): bool
    {
        $signature = $scenario['signature'] ?? null;
        if (!$signature) {
            return false;
        }

        $duplicateExists = Alert::query()
            ->where('farm_id', $farm->farm_id)
            ->where('automation_key', $signature)
            ->where('resolved', false)
            ->where('issued_at', '>=', now()->subHours(self::DUPLICATE_WINDOW_HOURS))
            ->exists();

        if ($duplicateExists) {
            return false;
        }

        Alert::create([
            'farm_id' => $farm->farm_id,
            'alert_type' => $scenario['type'],
            'message' => $scenario['message'],
            'issued_at' => now(),
            'resolved' => false,
            'is_system_generated' => true,
            'automation_key' => $signature,
        ]);

        return true;
    }

    private function signature(string $type, Carbon $date): string
    {
        return sprintf('%s-%s', $type, $date->toDateString());
    }

    private function describeDay(Carbon $date): string
    {
        if ($date->isToday()) {
            return 'today';
        }

        if ($date->isTomorrow()) {
            return 'tomorrow';
        }

        return 'on ' . $date->format('l');
    }

    private function sumPrecipitation(array $hourly): float
    {
        $total = 0.0;

        foreach ($hourly as $entry) {
            $rain = data_get($entry, 'rain.3h')
                ?? data_get($entry, 'rain.1h')
                ?? data_get($entry, 'rain');
            $snow = data_get($entry, 'snow.3h')
                ?? data_get($entry, 'snow.1h')
                ?? data_get($entry, 'snow');
            $precip = data_get($entry, 'precipitation', 0);

            foreach ([$rain, $snow, $precip] as $value) {
                if (is_numeric($value)) {
                    $total += (float) $value;
                }
            }
        }

        return round($total, 1);
    }

    private function maxWindSpeed(array $hourly): float
    {
        $max = 0.0;

        foreach ($hourly as $entry) {
            $speed = data_get($entry, 'wind.speed');
            if (!is_numeric($speed)) {
                $speed = data_get($entry, 'wind_speed');
            }

            if (!is_numeric($speed)) {
                continue;
            }

            $kmh = (float) $speed * 3.6;
            if ($kmh > $max) {
                $max = $kmh;
            }
        }

        return round($max, 1);
    }
}


