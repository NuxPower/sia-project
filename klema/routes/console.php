<?php

use App\Services\AlertAutomationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('alerts:auto-generate', function (AlertAutomationService $automation) {
    $summary = $automation->run();

    $this->info(sprintf(
        'Scanned %d farms, created %d alerts.',
        $summary['farms_scanned'],
        $summary['alerts_created']
    ));

    if (!empty($summary['failures'])) {
        $this->warn('Some farms were skipped:');
        collect($summary['failures'])->each(function ($failure) {
            $this->line(sprintf('- Farm %s: %s', $failure['farm_id'], $failure['reason']));
        });
    }

    if ($summary['alerts_created'] === 0 && empty($summary['failures'])) {
        $this->comment('No alerts were created because current weather conditions do not meet alert thresholds:');
        $this->line('  - High heat: ≥34°C');
        $this->line('  - Cold snap: ≤8°C');
        $this->line('  - Dry spell: 3+ consecutive days with <1.5mm precipitation');
        $this->line('  - Harvest window: 2+ consecutive favorable days');
        $this->line('  - Maintenance: severe wind (≥45 km/h), heavy rain (≥30mm), or storms');
        $this->newLine();
        $this->info('This is normal - alerts will be created automatically when conditions are met.');
    }
})->purpose('Generate system alerts based on current forecast data');
