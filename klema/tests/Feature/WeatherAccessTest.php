<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WeatherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WeatherAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(WeatherService::class, function ($mock) {
            $mock->shouldReceive('getCurrentWeather')
                ->andReturn(['name' => 'Butuan', 'weather' => []]);
            $mock->shouldReceive('storeWeatherSnapshot')
                ->andReturnNull();
            $mock->shouldReceive('getForecast')
                ->andReturn(['list' => []]);
        });
    }

    #[DataProvider('weatherRouteProvider')]
    public function test_authorized_users_can_access_weather_routes(string $method, string $uri): void
    {
        $user = User::factory()->create();

        PersonalAccessToken::query()->delete();
        Sanctum::actingAs($user, $user->defaultTokenAbilities());

        $this->json($method, $uri)->assertOk();
    }

    #[DataProvider('weatherRouteProvider')]
    public function test_guests_cannot_access_weather_routes(string $method, string $uri): void
    {
        PersonalAccessToken::query()->delete();
        
        // Ensure no user is authenticated
        $this->assertGuest();
        
        // Make request without any authentication headers
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->json($method, $uri);
        
        // Should return 401, not 200
        $response->assertUnauthorized();
    }

    public static function weatherRouteProvider(): array
    {
        return [
            ['GET', '/api/weather/current?location=Butuan'],
            ['GET', '/api/weather/forecast?location=Butuan'],
        ];
    }
}

