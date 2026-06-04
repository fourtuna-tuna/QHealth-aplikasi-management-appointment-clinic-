<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (! Schema::hasColumn('patients', 'password')) {
                $table->string('password')->nullable()->after('email');
            }

            if (! Schema::hasColumn('patients', 'remember_token')) {
                $table->rememberToken();
            }
        });

        Schema::table('appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('appointments', 'payment_status')) {
                $table->string('payment_status')->default('unpaid')->after('status');
            }

            if (! Schema::hasColumn('appointments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_status');
            }
        });

        if (! Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        DB::table('admins')->updateOrInsert(
            ['email' => 'admin@clc.test'],
            ['name' => 'Admin CLC', 'password' => Hash::make('password123'), 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
