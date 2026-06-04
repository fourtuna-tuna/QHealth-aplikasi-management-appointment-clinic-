<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('patient')->after('email');
            });
        }

        if (Schema::hasTable('admins')) {
            foreach (DB::table('admins')->get() as $admin) {
                $data = [
                    'name' => $admin->name,
                    'role' => 'admin',
                    'password' => $admin->password,
                    'updated_at' => now(),
                ];

                $exists = DB::table('users')->where('email', $admin->email)->exists();

                if ($exists) {
                    DB::table('users')->where('email', $admin->email)->update($data);
                } else {
                    DB::table('users')->insert($data + [
                        'email' => $admin->email,
                        'created_at' => $admin->created_at ?: now(),
                    ]);
                }
            }
        }

        if (Schema::hasTable('patients')) {
            foreach (DB::table('patients')->get() as $patient) {
                if (! $patient->email) {
                    continue;
                }

                $password = Schema::hasColumn('patients', 'password') && $patient->password
                    ? $patient->password
                    : '$2y$12$7aYIRhZ4Qmv8U0DeuikH8.zZdU5hrNNeFnXC1WbnWDHPk23gP8o5m';

                $data = [
                    'name' => $patient->name,
                    'role' => 'patient',
                    'password' => $password,
                    'phone' => $patient->phone,
                    'birth_date' => $patient->birth_date,
                    'gender' => $patient->gender,
                    'address' => $patient->address,
                    'blood_type' => $patient->blood_type,
                    'updated_at' => now(),
                ];

                $exists = DB::table('users')->where('email', $patient->email)->exists();

                if ($exists) {
                    DB::table('users')->where('email', $patient->email)->update($data);
                } else {
                    DB::table('users')->insert($data + [
                        'email' => $patient->email,
                        'created_at' => $patient->created_at ?: now(),
                    ]);
                }
            }
        }

        if (! Schema::hasColumn('appointments', 'user_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }

        if (Schema::hasTable('patients') && Schema::hasColumn('appointments', 'patient_id')) {
            DB::table('appointments')
                ->join('patients', 'appointments.patient_id', '=', 'patients.id')
                ->join('users', 'patients.email', '=', 'users.email')
                ->update(['appointments.user_id' => DB::raw('users.id')]);
        }

        if (! Schema::hasColumn('medical_records', 'user_id')) {
            Schema::table('medical_records', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }

        if (Schema::hasTable('patients') && Schema::hasColumn('medical_records', 'patient_id')) {
            DB::table('medical_records')
                ->join('patients', 'medical_records.patient_id', '=', 'patients.id')
                ->join('users', 'patients.email', '=', 'users.email')
                ->update(['medical_records.user_id' => DB::raw('users.id')]);
        }

        $this->dropForeignIfExists('appointments', 'patient_id');
        if (Schema::hasColumn('appointments', 'patient_id')) {
            Schema::table('appointments', fn (Blueprint $table) => $table->dropColumn('patient_id'));
        }

        $this->dropForeignIfExists('medical_records', 'patient_id');
        if (Schema::hasColumn('medical_records', 'patient_id')) {
            Schema::table('medical_records', fn (Blueprint $table) => $table->dropColumn('patient_id'));
        }

        Schema::dropIfExists('admins');
        Schema::dropIfExists('patients');
    }

    public function down(): void
    {
        //
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        $database = DB::getDatabaseName();
        $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');

        if ($constraint) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign($constraint));
        }
    }
};
