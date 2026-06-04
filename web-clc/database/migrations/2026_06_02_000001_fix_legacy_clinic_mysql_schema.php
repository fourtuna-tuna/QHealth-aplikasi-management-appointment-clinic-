<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('appointments') && Schema::hasColumn('appointments', 'schedule_id')) {
            DB::statement('ALTER TABLE appointments MODIFY schedule_id bigint unsigned NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kept intentionally non-destructive for legacy MySQL data.
    }
};
