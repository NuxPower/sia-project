<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Farm;
use App\Models\Alert;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AlertDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_can_delete_own_active_alert(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $farm = Farm::create([
            'user_id' => $user->id,
            'farm_name' => 'Sample Farm',
            'latitude' => 8.475123,
            'longitude' => 124.642789,
        ]);

        $alert = Alert::create([
            'farm_id' => $farm->farm_id,
            'alert_type' => 'weather',
            'message' => 'Test storm warning',
            'issued_at' => now()->subHour(),
            'resolved' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/alerts/{$alert->alert_id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Alert deleted successfully!',
            ]);

        $this->assertDatabaseMissing('alerts', [
            'alert_id' => $alert->alert_id,
        ]);
    }
}



