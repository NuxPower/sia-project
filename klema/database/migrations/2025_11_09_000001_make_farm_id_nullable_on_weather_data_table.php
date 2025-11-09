<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE weather_data DROP CONSTRAINT IF EXISTS weather_data_farm_id_foreign');
        DB::statement('ALTER TABLE weather_data ALTER COLUMN farm_id DROP NOT NULL');
        DB::statement('ALTER TABLE weather_data ADD CONSTRAINT weather_data_farm_id_foreign FOREIGN KEY (farm_id) REFERENCES farms(farm_id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE weather_data DROP CONSTRAINT IF EXISTS weather_data_farm_id_foreign');
        DB::statement('UPDATE weather_data SET farm_id = (SELECT farm_id FROM farms ORDER BY farm_id ASC LIMIT 1) WHERE farm_id IS NULL');
        DB::statement('ALTER TABLE weather_data ALTER COLUMN farm_id SET NOT NULL');
        DB::statement('ALTER TABLE weather_data ADD CONSTRAINT weather_data_farm_id_foreign FOREIGN KEY (farm_id) REFERENCES farms(farm_id) ON DELETE CASCADE');
    }
};

