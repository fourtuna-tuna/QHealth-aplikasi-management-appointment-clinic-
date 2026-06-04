<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('patients') || ! Schema::hasTable('users')) {
            return;
        }

        $hasPatientPassword = Schema::hasColumn('patients', 'password');
        $patients = DB::table('patients')->get();

        foreach ($patients as $patient) {
            if (! $patient->email) {
                continue;
            }

            $user = DB::table('users')->where('email', $patient->email)->first();

            if (! $user) {
                $userId = DB::table('users')->insertGetId([
                    'name' => $patient->name,
                    'email' => $patient->email,
                    'password' => ($hasPatientPassword && $patient->password) ? $patient->password : '$2y$12$7aYIRhZ4Qmv8U0DeuikH8.zZdU5hrNNeFnXC1WbnWDHPk23gP8o5m',
                    'phone' => $patient->phone,
                    'birth_date' => $patient->birth_date,
                    'gender' => $patient->gender,
                    'address' => $patient->address,
                    'blood_type' => $patient->blood_type,
                    'created_at' => $patient->created_at ?: now(),
                    'updated_at' => now(),
                ]);
            } else {
                $userId = $user->id;
            }

            if (Schema::hasColumn('patients', 'user_id') && ! $patient->user_id) {
                DB::table('patients')->where('id', $patient->id)->update(['user_id' => $userId]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
