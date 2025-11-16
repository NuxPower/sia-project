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
})->purpose('Generate system alerts based on current forecast data');
