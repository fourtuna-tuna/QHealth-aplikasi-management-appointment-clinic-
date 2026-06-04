<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        DB::table('users')->where('role', 'pasien')->update(['role' => 'patient']);
        DB::statement("ALTER TABLE users MODIFY role varchar(20) NOT NULL DEFAULT 'patient'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role')) {
            return;
        }

        DB::table('users')->where('role', 'patient')->update(['role' => 'pasien']);
        DB::statement("ALTER TABLE users MODIFY role varchar(20) NOT NULL DEFAULT 'pasien'");
    }
};
