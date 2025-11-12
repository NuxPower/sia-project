<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiTokenLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_refresh_token_and_receive_new_credentials(): void
    {
        config([
            'sanctum.expiration' => 10,
            'sanctum.remember_expiration' => 120,
        ]);

        $password = 'StrongPass!234';

        $user = User::factory()->create([
            'password' => Hash::make($password),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => $password,
            'device_name' => 'PHPUnit Device',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'token',
                'token_type',
                'expires_in',
                'abilities',
                'user',
            ]);

        $originalToken = $loginResponse->json('token');
        $this->assertNotEmpty($originalToken);
        $this->assertSame(config('sanctum.expiration') * 60, $loginResponse->json('expires_in'));

        $refreshResponse = $this->withHeader('Authorization', 'Bearer '.$originalToken)
            ->postJson('/api/token/refresh');

        $refreshResponse
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'token',
                'token_type',
                'expires_in',
                'abilities',
            ]);

        $rotatedToken = $refreshResponse->json('token');

        $this->assertNotEmpty($rotatedToken);
        $this->assertNotSame($originalToken, $rotatedToken);

        $this->withHeader('Authorization', 'Bearer '.$rotatedToken)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_remember_me_tokens_use_extended_expiration_and_flag(): void
    {
        config([
            'sanctum.expiration' => 60,
            'sanctum.remember_expiration' => 120,
        ]);

        $password = 'StrongPass!234';

        $user = User::factory()->create([
            'password' => Hash::make($password),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => $password,
            'device_name' => 'Remembered Device',
            'remember' => true,
        ]);

        $response->assertOk();

        $abilities = $response->json('abilities');

        $this->assertIsArray($abilities);
        $this->assertContains('token:remember', $abilities);
        $this->assertSame(config('sanctum.remember_expiration') * 60, $response->json('expires_in'));
    }

    public function test_user_can_list_and_revoke_tokens(): void
    {
        config([
            'sanctum.expiration' => 60,
            'sanctum.remember_expiration' => 120,
        ]);

        $user = User::factory()->create();
        $abilities = $user->defaultTokenAbilities();

        $currentTokenResult = $user->createToken('Current Browser', $abilities);
        $currentPlainToken = $currentTokenResult->plainTextToken;
        $currentAccessToken = $currentTokenResult->accessToken;

        $secondaryAccessToken = $user->createToken('Tablet', $abilities)->accessToken;

        $listResponse = $this->withHeader('Authorization', 'Bearer '.$currentPlainToken)
            ->getJson('/api/tokens');

        $listResponse->assertOk();

        $tokens = $listResponse->json('tokens');
        $this->assertIsArray($tokens);
        $this->assertCount(2, $tokens);
        $this->assertTrue(collect($tokens)->contains(fn ($token) => $token['current'] === true));

        $this->withHeader('Authorization', 'Bearer '.$currentPlainToken)
            ->deleteJson('/api/tokens/'.$secondaryAccessToken->id)
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $secondaryAccessToken->id,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$currentPlainToken)
            ->deleteJson('/api/tokens/'.$currentAccessToken->id)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Token revoked. Please re-authenticate.',
            ]);
    }
}

