<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reporters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('nik', 16)->unique();
            $table->string('kk_number', 16)->nullable();
            $table->string('gender')->nullable();
            $table->text('address')->nullable();
            $table->string('checkin_status')->default('not_checked_in');
            $table->unsignedBigInteger('ktp_document_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reporters');
    }
};
