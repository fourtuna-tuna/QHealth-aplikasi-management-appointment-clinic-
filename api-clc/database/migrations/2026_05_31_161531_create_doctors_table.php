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
        if (! Schema::hasTable('doctors')) {
            Schema::create('doctors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('specialization');
                $table->string('phone')->nullable();
                $table->string('photo_url')->nullable();
                $table->text('bio')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            return;
        }

        Schema::table('doctors', function (Blueprint $table) {
            if (! Schema::hasColumn('doctors', 'service_id')) {
                $table->foreignId('service_id')->nullable()->after('id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('doctors', 'photo_url')) {
                $table->string('photo_url')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('doctors', 'bio')) {
                $table->text('bio')->nullable()->after('photo_url');
            }
        });

        if (Schema::hasColumn('doctors', 'photo') && Schema::hasColumn('doctors', 'photo_url')) {
            DB::table('doctors')
                ->whereNull('photo_url')
                ->whereNotNull('photo')
                ->update(['photo_url' => DB::raw('photo')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
