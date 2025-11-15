<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Farm;
use App\Models\Alert;
use App\Models\FarmPoint;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get the test farmer
        $farmer = User::where('email', 'farmer@example.com')->first();
        
        if (!$farmer) {
            $this->command->error('Test farmer not found. Please run TestUserSeeder first.');
            return;
        }

        // Create test farms
        $farm1 = Farm::create([
            'user_id' => $farmer->id,
            'farm_name' => 'North Field',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $farm2 = Farm::create([
            'user_id' => $farmer->id,
            'farm_name' => 'South Field',
            'latitude' => 40.7589,
            'longitude' => -73.9851,
        ]);

        // Create farm points
        FarmPoint::create([
            'farm_id' => $farm1->farm_id,
            'label' => 'Center Point',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'point_type' => 'center',
        ]);

        FarmPoint::create([
            'farm_id' => $farm2->farm_id,
            'label' => 'Center Point',
            'latitude' => 40.7589,
            'longitude' => -73.9851,
            'point_type' => 'center',
        ]);

        // Create test alerts
        Alert::create([
            'farm_id' => $farm1->farm_id,
            'alert_type' => 'weather',
            'message' => 'Heavy rain expected in the next 24 hours. Consider delaying irrigation.',
            'issued_at' => now()->subHours(2),
            'resolved' => false,
        ]);

        Alert::create([
            'farm_id' => $farm1->farm_id,
            'alert_type' => 'maintenance',
            'message' => 'High winds predicted tomorrow. Secure equipment and postpone servicing.',
            'issued_at' => now()->subDays(1),
            'resolved' => false,
        ]);

        Alert::create([
            'farm_id' => $farm2->farm_id,
            'alert_type' => 'irrigation',
            'message' => 'Soil moisture levels below optimal. Irrigation system activation recommended.',
            'issued_at' => now()->subHours(6),
            'resolved' => true,
        ]);

        Alert::create([
            'farm_id' => $farm2->farm_id,
            'alert_type' => 'harvest',
            'message' => 'Crop maturity reached 85%. Harvest window opening in 3-5 days.',
            'issued_at' => now()->subDays(2),
            'resolved' => false,
        ]);

        $this->command->info('Test data created successfully!');
        $this->command->info('Created 2 farms and 4 alerts for testing.');
    }
}
