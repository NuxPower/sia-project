<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Farm>
 */
class FarmFactory extends Factory
{
    protected $model = Farm::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'farm_name' => $this->faker->unique()->company().' Farm',
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'size_hectares' => $this->faker->randomFloat(2, 1, 500),
            'soil_type' => $this->faker->randomElement(config('farm.soil_types', ['loam'])),
            'description' => $this->faker->sentence(),
        ];
    }
}

