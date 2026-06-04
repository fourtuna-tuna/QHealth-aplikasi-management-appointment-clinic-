<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@clc.test'],
            ['name' => 'Admin CLC', 'role' => 'admin', 'password' => Hash::make('password123')]
        );

        $this->call(DoctorDummySeeder::class);
    }
}
