<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // USERS - Role column already exists, no modification needed

        // FARMS
        Schema::create('farms', function (Blueprint $table) {
            $table->id('farm_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('farm_name', 100);
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 9, 6);
            $table->timestamps();
        });

        // Add geometry column for PostgreSQL
        DB::statement('ALTER TABLE farms ADD COLUMN boundary geometry(Polygon, 4326);');

        // WEATHER DATA
        Schema::create('weather_data', function (Blueprint $table) {
            $table->id('weather_id');
            $table->foreignId('farm_id')->constrained('farms', 'farm_id')->onDelete('cascade');
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('humidity', 5, 2)->nullable();
            $table->decimal('rainfall', 5, 2)->nullable();
            $table->decimal('wind_speed', 5, 2)->nullable();
            $table->string('condition', 100)->nullable();
            $table->timestamp('recorded_at')->useCurrent();
        });

        // FARM POINTS
        Schema::create('farm_points', function (Blueprint $table) {
            $table->id('point_id');
            $table->foreignId('farm_id')->constrained('farms', 'farm_id')->onDelete('cascade');
            $table->string('label', 100);
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 9, 6);
            $table->string('point_type', 50)->nullable();
            $table->timestamps();
        });

        // ALERTS
        Schema::create('alerts', function (Blueprint $table) {
            $table->id('alert_id');
            $table->foreignId('farm_id')->constrained('farms', 'farm_id')->onDelete('cascade');
            $table->string('alert_type', 50)->nullable();
            $table->text('message');
            $table->timestamp('issued_at')->useCurrent();
            $table->boolean('resolved')->default(false);
        });

        // EXPORTS
        Schema::create('exports', function (Blueprint $table) {
            $table->id('export_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('file_name', 255);
            $table->text('file_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exports');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('farm_points');
        Schema::dropIfExists('weather_data');
        Schema::dropIfExists('farms');
        // Note: Role column is part of the default users table, not dropped here
    }
};
