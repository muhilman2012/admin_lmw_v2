<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_slot_settings', function (Blueprint $table) {
            $table->id();
            $table->time('time_start'); // Contoh: 09:00:00
            $table->integer('quota');    // Contoh: 15
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_slot_settings');
    }
};