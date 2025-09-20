<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Activity;
use App\Models\User;
use Carbon\Carbon;

class ActivitySeeder extends Seeder
{
    public function run()
    {
        $user = User::first(); // Assumes you have at least one user
        
        if ($user) {
            $activities = [
                [
                    'user_id' => $user->id,
                    'activity_type' => 'Planting',
                    'field' => 'Field A - Rice Paddy',
                    'start_date' => Carbon::now()->addDays(2),
                    'status' => 'planned'
                ],
                [
                    'user_id' => $user->id,
                    'activity_type' => 'Irrigation',
                    'field' => 'Field B - Corn',
                    'start_date' => Carbon::now()->addDays(5),
                    'status' => 'planned'
                ],
                [
                    'user_id' => $user->id,
                    'activity_type' => 'Harvesting',
                    'field' => 'Field C - Vegetables',
                    'start_date' => Carbon::now()->addDays(10),
                    'status' => 'planned'
                ],
                [
                    'user_id' => $user->id,
                    'activity_type' => 'Fertilizing',
                    'field' => 'Field A - Rice Paddy',
                    'start_date' => Carbon::now()->subDays(2),
                    'status' => 'completed'
                ],
            ];

            foreach ($activities as $activity) {
                Activity::create($activity);
            }
        }
    }
}
