<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExportWeatherDateValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_weather_export_rejects_future_dates(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/exports/weather', [
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->addDay()->toDateString(),
            'format' => 'csv',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_date', 'end_date']);
    }
}

