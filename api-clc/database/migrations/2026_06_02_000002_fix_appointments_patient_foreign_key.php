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
        if (! Schema::hasTable('appointments')) {
            return;
        }

        $constraint = DB::selectOne("
            SELECT CONSTRAINT_NAME constraint_name, REFERENCED_TABLE_NAME referenced_table
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'appointments'
              AND COLUMN_NAME = 'patient_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ");

        if ($constraint && $constraint->referenced_table !== 'patients') {
            DB::statement("ALTER TABLE appointments DROP FOREIGN KEY {$constraint->constraint_name}");
            DB::statement('ALTER TABLE appointments ADD CONSTRAINT appointments_patient_id_foreign FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE');
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
