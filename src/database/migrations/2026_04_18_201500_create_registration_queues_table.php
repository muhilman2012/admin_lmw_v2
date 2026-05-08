<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_queues', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('registration_number', 6)->unique();
            $table->string('nik', 16)->index();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('subject');
            $table->boolean('is_disabled')->default(false);
            $table->string('companion_name');
            $table->date('visit_date');
            $table->time('visit_time');
            $table->string('qr_path')->nullable();
            
            $table->enum('status', ['pending', 'checked_in', 'served', 'expired'])->default('pending');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_queues');
    }
};