<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create test users
        User::create([
            'name' => 'Test Farmer',
            'email' => 'farmer@example.com',
            'password' => Hash::make('password'),
            'role' => 'farmer',
        ]);

        User::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }
}