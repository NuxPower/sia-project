<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->boolean('is_system_generated')->default(false)->after('resolved');
            $table->string('automation_key')->nullable()->after('is_system_generated');
            $table->index('automation_key', 'alerts_automation_key_index');
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropIndex('alerts_automation_key_index');
            $table->dropColumn(['is_system_generated', 'automation_key']);
        });
    }
};

