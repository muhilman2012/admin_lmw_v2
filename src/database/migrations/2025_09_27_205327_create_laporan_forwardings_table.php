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

            // Kunci utama ke tabel reports
            $table->foreignId('laporan_id')
                  ->constrained('reports')
                  ->onDelete('cascade'); // Jika laporan dihapus, entri forwarding juga dihapus
            $table->foreignId('user_id')->nullable()->constrained('users');

            // Informasi Institusi Tujuan & ID Lapor
            $table->string('institution_id'); // ID Institusi LAPOR!
            $table->string('complaint_id')->nullable(); // Complaint ID dari LAPOR!

            // Informasi Status dari LAPOR! (tambahan dari Schema::table)
            $table->string('lapor_status_code')->nullable();
            $table->string('lapor_status_name')->nullable();

            $table->text('content')->nullable();
            
            // Status pengiriman internal
            $table->string('status'); // contoh: terkirim, gagal_forward, dijadwalkan
            $table->text('reason')->nullable();
            $table->text('error_message')->nullable();
            
            // Metadata
            $table->boolean('is_anonymous')->default(false);
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('next_check_at')->nullable(); // Kapan harus cek status lagi
            
            $table->timestamps();

            // Tambahkan foreign key manual untuk institution_id (karena bertipe string)
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
