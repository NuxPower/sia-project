<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.default') === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        }

        Schema::table('farms', function (Blueprint $table) {
            $table->decimal('size_hectares', 10, 2)->nullable()->after('longitude');
            $table->string('soil_type', 100)->nullable()->after('size_hectares');
            $table->text('description')->nullable()->after('soil_type');
        });

        // Geometry column must be added via raw SQL to ensure SRID/type specificity
        if (Schema::hasTable('farms')) {
            DB::statement("ALTER TABLE farms ADD COLUMN IF NOT EXISTS boundary geometry(Polygon, 4326)");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('farms', 'boundary')) {
            DB::statement('ALTER TABLE farms DROP COLUMN boundary');
        }

        Schema::table('farms', function (Blueprint $table) {
            $table->dropColumn(['size_hectares', 'soil_type', 'description']);
        });
    }
};

