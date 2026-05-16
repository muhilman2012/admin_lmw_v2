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
        Schema::create('laporan_forwardings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('laporan_id')
                  ->constrained('reports')
                  ->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users');

            $table->string('institution_id');
            $table->string('complaint_id')->nullable();

            $table->string('lapor_status_code')->nullable();
            $table->string('lapor_status_name')->nullable();

            $table->text('content')->nullable();
            
            $table->string('status');
            $table->text('reason')->nullable();
            $table->text('error_message')->nullable();
            
            $table->boolean('is_anonymous')->default(false);
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('next_check_at')->nullable();
            
            $table->timestamps();

            $table->foreign('institution_id')
                  ->references('id')
                  ->on('institutions')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_forwardings');
    }
};
