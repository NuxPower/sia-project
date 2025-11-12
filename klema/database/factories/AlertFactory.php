<?php

namespace Database\Factories;

use App\Models\Alert;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    protected $model = Alert::class;

    public function definition(): array
    {
        return [
            'farm_id' => Farm::factory(),
            'alert_type' => $this->faker->randomElement(['weather', 'pest', 'irrigation', 'maintenance']),
            'message' => $this->faker->sentence(),
            'issued_at' => now(),
            'resolved' => false,
        ];
    }
}

