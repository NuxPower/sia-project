<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Farm;
use App\Models\FarmPoint;
use App\Models\Alert;
use App\Models\Activity;

class DropSeededData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:drop-seeded';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop only seeded test data from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Dropping seeded data...');

        // Track what was deleted
        $deletedCounts = [
            'alerts' => 0,
            'farm_points' => 0,
            'farms' => 0,
            'activities' => 0,
            'users' => 0,
        ];

        // Get seeded user emails
        $seededEmails = ['test@example.com', 'farmer@example.com'];
        
        // Find seeded users
        $seededUsers = User::whereIn('email', $seededEmails)->get();
        
        if ($seededUsers->isEmpty()) {
            $this->warn('No seeded users found.');
        } else {
            // Delete alerts for farms owned by seeded users
            $seededUserIds = $seededUsers->pluck('id');
            $seededFarms = Farm::whereIn('user_id', $seededUserIds)->get();
            
            if ($seededFarms->isNotEmpty()) {
                $seededFarmIds = $seededFarms->pluck('farm_id');
                
                // Delete alerts
                $deletedCounts['alerts'] = Alert::whereIn('farm_id', $seededFarmIds)->delete();
                
                // Delete farm points
                $deletedCounts['farm_points'] = FarmPoint::whereIn('farm_id', $seededFarmIds)->delete();
                
                // Delete farms
                $deletedCounts['farms'] = $seededFarms->count();
                Farm::whereIn('farm_id', $seededFarmIds)->delete();
            }
            
            // Delete activities created by seeded users
            $deletedCounts['activities'] = Activity::whereIn('user_id', $seededUserIds)->delete();
            
            // Delete seeded users
            $deletedCounts['users'] = $seededUsers->count();
            User::whereIn('id', $seededUserIds)->delete();
        }

        // Display results
        $this->info('Seeded data dropped successfully!');
        $this->table(
            ['Type', 'Count'],
            [
                ['Users', $deletedCounts['users']],
                ['Farms', $deletedCounts['farms']],
                ['Farm Points', $deletedCounts['farm_points']],
                ['Alerts', $deletedCounts['alerts']],
                ['Activities', $deletedCounts['activities']],
            ]
        );

        return Command::SUCCESS;
    }
}



