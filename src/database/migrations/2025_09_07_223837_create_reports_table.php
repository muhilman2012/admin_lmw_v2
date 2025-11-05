<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 7)->unique();
            $table->uuid('uuid')->unique();
            $table->string('lapor_complaint_id')->nullable();
            $table->unsignedBigInteger('reporter_id');
            $table->string('subject');
            $table->text('details');
            $table->string('location')->nullable();
            $table->date('event_date')->nullable();
            $table->string('source');
            $table->string('status');
            $table->text('response')->nullable();
            $table->boolean('is_benefit_provided')->default(false); 
            $table->string('classification')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->foreignId('unit_kerja_id')->nullable()->constrained('unit_kerjas');
            $table->foreignId('deputy_id')->nullable()->constrained('deputies');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
