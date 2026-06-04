<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetPatientDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (['medical_records', 'appointments', 'queue_resets', 'password_reset_tokens', 'personal_access_tokens'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        if (Schema::hasTable('patients')) {
            DB::table('patients')->truncate();
        }

        if (Schema::hasTable('users')) {
            DB::table('users')->whereIn('role', ['patient', 'pasien'])->delete();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
