<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Farm;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ActivityAdvisor
{
    private array $types;

    public function __construct(private WeatherService $weatherService)
    {
        $this->types = config('activities.types', []);
    }

    public function getActivityTypes(): array
    {
        return collect($this->types)
            ->map(function (array $definition, string $key) {
                return [
                    'key' => $key,
                    'label' => $definition['label'] ?? Str::headline($key),
                    'description' => $definition['description'] ?? null,
                    'default_actions' => array_values($definition['default_actions'] ?? []),
                ];
            })
            ->values()
            ->all();
    }

    public function buildRecommendation(User $user, string $typeKey, Carbon $date): array
    {
        $normalizedKey = Str::slug(strtolower($typeKey), '_');
        $typeDefinition = $this->types[$normalizedKey] ?? null;

        if (!$typeDefinition) {
            throw ValidationException::withMessages([
                'activity_type' => 'Unknown activity type selected.',
            ]);
        }

        $location = $this->resolveLocation($user);
        $forecast = $this->resolveForecastForDate($location, $date);

        $analysis = $this->analyzeConditions($typeDefinition, $forecast);
        $alerts = $this->collectRelevantAlerts($user, $typeDefinition['alert_tags'] ?? []);

        return [
            'type' => [
                'key' => $normalizedKey,
                'label' => $typeDefinition['label'] ?? Str::headline($normalizedKey),
                'description' => $typeDefinition['description'] ?? null,
            ],
            'date' => $date->toDateString(),
            'location' => $location['label'],
            'status' => $analysis['status'],
            'summary' => $analysis['summary'],
            'recommendations' => $analysis['recommendations'],
            'metrics' => $analysis['metrics'],
            'alerts' => $alerts,
        ];
    }

    private function resolveLocation(User $user): array
    {
        /** @var Farm|null $farm */
        $farm = $user->farms()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderByDesc('updated_at')
            ->first();

        if ($farm) {
            return [
                'label' => $farm->farm_name,
                'lat' => (float) $farm->latitude,
                'lon' => (float) $farm->longitude,
            ];
        }

        $fallback = config('activities.fallback_location');
        if (!$fallback || !isset($fallback['lat'], $fallback['lon'])) {
            throw new \RuntimeException('Activity fallback location is not configured.');
        }

        return [
            'label' => $fallback['label'] ?? 'Default Farm',
            'lat' => (float) $fallback['lat'],
            'lon' => (float) $fallback['lon'],
        ];
    }

    private function resolveForecastForDate(array $location, Carbon $date): ?array
    {
        try {
            $forecast = $this->weatherService->getForecastByCoordinates(
                $location['lat'],
                $location['lon'],
                7
            );
        } catch (\Throwable $exception) {
            Log::warning('Activity advisor failed to fetch forecast', [
                'message' => $exception->getMessage(),
                'location' => $location,
            ]);

            return null;
        }

        if (empty($forecast)) {
            return null;
        }

        $dateString = $date->toDateString();
        return collect($forecast)->firstWhere('date', $dateString)
            ?? $forecast[0];
    }

    private function analyzeConditions(array $definition, ?array $forecast): array
    {
        if (!$forecast) {
            return [
                'status' => 'unknown',
                'summary' => Arr::get($definition, 'tips.unknown')
                    ?? 'Weather data is unavailable. Use local observations before proceeding.',
                'metrics' => [],
                'recommendations' => array_values($definition['default_actions'] ?? []),
            ];
        }

        $metrics = $this->computeMetrics($forecast);
        $rules = $definition['rules'] ?? [];
        $severity = 'go';
        $messages = [];

        foreach ($rules as $rule) {
            $metricKey = $rule['metric'] ?? null;
            $operator = $rule['operator'] ?? '>=';
            $threshold = $rule['value'] ?? null;
            $ruleSeverity = $rule['severity'] ?? 'caution';

            if (!$metricKey || $threshold === null) {
                continue;
            }

            $value = $metrics[$metricKey] ?? null;
            if ($value === null) {
                continue;
            }

            if ($this->compare($value, $operator, $threshold)) {
                $severity = $this->maxSeverity($severity, $ruleSeverity);
                $messages[] = $this->interpolateMessage($rule['message'] ?? '', $metrics);
            }
        }

        $summary = Arr::get($definition, "tips.{$severity}")
            ?? Arr::get($definition, 'tips.go')
            ?? 'Activity window looks favorable.';

        $recommendations = array_values(array_unique(array_filter(array_merge(
            $definition['default_actions'] ?? [],
            $messages
        ))));

        return [
            'status' => $severity,
            'summary' => $summary,
            'metrics' => $metrics,
            'recommendations' => $recommendations,
        ];
    }

    private function computeMetrics(array $forecast): array
    {
        $hourly = $forecast['hourly'] ?? [];
        $precip = 0.0;
        $windSamples = [];
        $humidities = [];
        $windMax = null;

        foreach ($hourly as $entry) {
            $precip += $this->extractPrecipitation($entry);

            $wind = data_get($entry, 'wind.speed');
            if (is_numeric($wind)) {
                $kmh = (float) $wind * 3.6;
                $windSamples[] = $kmh;
                $windMax = $windMax === null ? $kmh : max($windMax, $kmh);
            }

            $humidity = data_get($entry, 'main.humidity') ?? data_get($entry, 'humidity');
            if (is_numeric($humidity)) {
                $humidities[] = (float) $humidity;
            }
        }

        $precipProbability = data_get($forecast, 'precip_probability');
        if (is_numeric($precipProbability) && $precipProbability <= 1) {
            $precipProbability *= 100;
        }

        return array_filter([
            'temp_max' => data_get($forecast, 'temp_max'),
            'temp_min' => data_get($forecast, 'temp_min'),
            'precip_mm' => round($precip, 1),
            'precip_probability' => $precipProbability !== null ? round($precipProbability) : null,
            'wind_max_kmh' => $windMax !== null ? round($windMax, 1) : null,
            'wind_avg_kmh' => !empty($windSamples)
                ? round(array_sum($windSamples) / count($windSamples), 1)
                : null,
            'humidity_avg' => !empty($humidities)
                ? round(array_sum($humidities) / count($humidities))
                : null,
            'description' => $forecast['description'] ?? $forecast['condition'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function extractPrecipitation(array $entry): float
    {
        $values = [
            data_get($entry, 'rain.3h'),
            data_get($entry, 'rain.1h'),
            data_get($entry, 'rain'),
            data_get($entry, 'snow.3h'),
            data_get($entry, 'snow.1h'),
            data_get($entry, 'snow'),
            data_get($entry, 'precipitation'),
        ];

        $total = 0.0;
        foreach ($values as $value) {
            if (is_numeric($value)) {
                $total += (float) $value;
            }
        }

        return $total;
    }

    private function collectRelevantAlerts(User $user, array $tags = []): array
    {
        $query = Alert::query()
            ->unresolved()
            ->whereHas('farm', function ($builder) use ($user) {
                $builder->where('user_id', $user->id);
            })
            ->orderByDesc('issued_at');

        if (!empty($tags)) {
            $query->whereIn('alert_type', $tags);
        }

        return $query
            ->limit(3)
            ->get()
            ->map(function (Alert $alert) {
                return [
                    'id' => $alert->alert_id,
                    'type' => $alert->alert_type,
                    'message' => $alert->message,
                    'issued_at' => optional($alert->issued_at)->toIso8601String(),
                ];
            })
            ->all();
    }

    private function compare(float $value, string $operator, float $threshold): bool
    {
        return match ($operator) {
            '>' => $value > $threshold,
            '>=' => $value >= $threshold,
            '<' => $value < $threshold,
            '<=' => $value <= $threshold,
            default => $value >= $threshold,
        };
    }

    private function maxSeverity(string $current, string $incoming): string
    {
        $order = ['go' => 0, 'caution' => 1, 'delay' => 2];
        $currentScore = $order[$current] ?? 0;
        $incomingScore = $order[$incoming] ?? 0;

        return $incomingScore > $currentScore ? $incoming : $current;
    }

    private function interpolateMessage(string $message, array $metrics): string
    {
        return preg_replace_callback('/\{([\w_]+)\}/', function ($matches) use ($metrics) {
            $key = $matches[1];
            $value = $metrics[$key] ?? null;

            if ($value === null) {
                return $matches[0];
            }

            if (in_array($key, ['precip_mm', 'wind_max_kmh', 'wind_avg_kmh'])) {
                return number_format((float) $value, 1);
            }

            return is_numeric($value) ? number_format((float) $value) : (string) $value;
        }, $message);
    }
}


