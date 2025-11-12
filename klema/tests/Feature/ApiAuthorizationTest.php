<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_protected_api_routes(): void
    {
        $this->getJson('/api/farms')->assertUnauthorized();
        $this->postJson('/api/alerts')->assertUnauthorized();
        $this->getJson('/api/activities')->assertUnauthorized();
    }

    public function test_farm_owner_can_view_and_update_their_farm(): void
    {
        $owner = User::factory()->create();
        $farm = Farm::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($owner, $owner->defaultTokenAbilities());

        $this->getJson("/api/farms/{$farm->farm_id}")->assertOk();

        $this->putJson("/api/farms/{$farm->farm_id}", ['farm_name' => 'Updated Farm'])
            ->assertOk()
            ->assertJsonPath('farm.farm_name', 'Updated Farm');
    }

    public function test_other_farmers_get_forbidden_when_accessing_foreign_farms(): void
    {
        $owner = User::factory()->create();
        $otherFarmer = User::factory()->create();
        $farm = Farm::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($otherFarmer, $otherFarmer->defaultTokenAbilities());

        $this->getJson("/api/farms/{$farm->farm_id}")->assertForbidden();
        $this->deleteJson("/api/farms/{$farm->farm_id}")->assertForbidden();
    }

    public function test_alert_updates_enforce_owner_or_admin_access(): void
    {
        $owner = User::factory()->create();
        $alert = Alert::factory()->for(
            Farm::factory()->create(['user_id' => $owner->id]),
            'farm'
        )->create();

        // Owner can resolve
        Sanctum::actingAs($owner, $owner->defaultTokenAbilities());
        $this->patchJson("/api/alerts/{$alert->alert_id}/resolve")->assertOk();

        // Other farmer is forbidden
        $otherFarmer = User::factory()->create();
        Sanctum::actingAs($otherFarmer, $otherFarmer->defaultTokenAbilities());
        $this->patchJson("/api/alerts/{$alert->alert_id}/resolve")->assertForbidden();

        // Admin can resolve
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, $admin->defaultTokenAbilities());
        $this->patchJson("/api/alerts/{$alert->alert_id}/resolve")->assertOk();
    }
}

