<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecasts', function (Blueprint $table) {
            $table->id('forecast_id');
            $table->foreignId('farm_id')->nullable()->constrained('farms', 'farm_id')->onDelete('cascade');
            $table->string('location_name', 150)->nullable();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->date('forecast_date');
            $table->decimal('temp_max', 5, 2)->nullable();
            $table->decimal('temp_min', 5, 2)->nullable();
            $table->string('condition', 100)->nullable();
            $table->string('condition_icon', 12)->nullable();
            $table->string('description', 255)->nullable();
            $table->decimal('precip_probability', 5, 2)->nullable();
            $table->integer('sunrise')->nullable();
            $table->integer('sunset')->nullable();
            $table->integer('timezone_offset')->default(0);
            $table->json('hourly_data')->nullable();
            $table->timestamp('cached_at')->useCurrent();
            $table->timestamp('expires_at');

            // Indexes for optimal query performance
            $table->index(['location_name', 'forecast_date', 'expires_at'], 'forecast_location_date_idx');
            $table->index(['latitude', 'longitude', 'forecast_date', 'expires_at'], 'forecast_coords_date_idx');
            $table->index(['farm_id', 'forecast_date', 'expires_at'], 'forecast_farm_date_idx');
            $table->index('expires_at', 'forecast_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecasts');
    }
};

