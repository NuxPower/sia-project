<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weather_data', function (Blueprint $table) {
            $table->string('location_name', 150)->nullable()->after('farm_id');
            $table->decimal('latitude', 9, 6)->nullable()->after('location_name');
            $table->decimal('longitude', 9, 6)->nullable()->after('latitude');
            $table->string('condition_icon', 12)->nullable()->after('condition');

            $table->index(['location_name', 'recorded_at'], 'weather_location_recorded_idx');
            $table->index(['latitude', 'longitude', 'recorded_at'], 'weather_coords_recorded_idx');
        });
    }

    public function down(): void
    {
        Schema::table('weather_data', function (Blueprint $table) {
            $table->dropIndex('weather_location_recorded_idx');
            $table->dropIndex('weather_coords_recorded_idx');

            $table->dropColumn(['location_name', 'latitude', 'longitude', 'condition_icon']);
        });
    }
};

