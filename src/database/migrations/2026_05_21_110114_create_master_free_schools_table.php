<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_free_schools', function (Blueprint $table) {
            $table->id();
            $table->string('school_name');
            $table->string('region');
            $table->timestamps();

            $table->index('region');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_free_schools');
    }
};