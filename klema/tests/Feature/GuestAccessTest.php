<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GuestAccessTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('protectedRouteProvider')]
    public function test_guests_receive_401_for_protected_routes(string $method, string $uri, array $payload = []): void
    {
        $response = $this->json($method, $uri, $payload);

        $response->assertStatus(401);
    }

    public static function protectedRouteProvider(): array
    {
        return [
            ['GET', '/api/farms'],
            ['POST', '/api/farms', ['farm_name' => 'Test Farm']],
            ['GET', '/api/alerts'],
            ['POST', '/api/alerts', ['farm_id' => 1]],
            ['GET', '/api/activities'],
            ['POST', '/api/activities', ['activity_type' => 'Planting']],
        ];
    }
}

