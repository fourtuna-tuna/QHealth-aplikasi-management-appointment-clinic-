<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('queue_resets')) {
            Schema::create('queue_resets', function (Blueprint $table) {
                $table->id();
                $table->date('reset_date')->index();
                $table->timestamp('reset_at')->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_resets');
    }
};
