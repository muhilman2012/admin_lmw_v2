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
            $table->foreignId('laporan_id')->constrained('reports')->onDelete('cascade');
            $table->string('institution_id'); // ID Institusi LAPOR! (String)
            $table->text('reason')->nullable();
            $table->string('status'); // contoh: terkirim, gagal_forward, dijadwalkan
            $table->string('complaint_id')->nullable(); // Complaint ID dari LAPOR!
            $table->boolean('is_anonymous')->default(false);
            $table->text('error_message')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->timestamps();

            // Tambahkan foreign key manual untuk institution_id karena bertipe string
            $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('cascade');
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
