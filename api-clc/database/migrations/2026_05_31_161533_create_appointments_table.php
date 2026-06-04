<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            Schema::create('appointments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
                $table->foreignId('doctor_schedule_id')->nullable()->constrained()->nullOnDelete();
                $table->date('appointment_date');
                $table->unsignedInteger('queue_number');
                $table->string('complaint');
                $table->string('status')->default('booked');
                $table->timestamp('checked_in_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('appointments', 'doctor_schedule_id')) {
                $table->foreignId('doctor_schedule_id')->nullable()->after('doctor_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('appointments', 'queue_number')) {
                $table->unsignedInteger('queue_number')->default(1)->after('appointment_date');
            }

            if (! Schema::hasColumn('appointments', 'checked_in_at')) {
                $table->timestamp('checked_in_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('appointments', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('checked_in_at');
            }

            if (! Schema::hasColumn('appointments', 'notes')) {
                $table->text('notes')->nullable()->after('completed_at');
            }
        });

        if (Schema::hasColumn('appointments', 'status')) {
            DB::statement("ALTER TABLE appointments MODIFY status varchar(255) NOT NULL DEFAULT 'booked'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
