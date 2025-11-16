<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            $table->decimal('size_hectares', 10, 2)->nullable()->after('longitude');
            $table->string('soil_type', 100)->nullable()->after('size_hectares');
            $table->text('description')->nullable()->after('soil_type');
            $table->json('boundary_geojson')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            $table->dropColumn(['boundary_geojson', 'description', 'soil_type', 'size_hectares']);
        });
    }
};

