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
        Schema::create('disposisi_lapor', function (Blueprint $table) {
            $table->id();
            
            // Kunci asing ke laporan_forwardings
            $table->foreignId('laporan_forwarding_id')
                  ->constrained('laporan_forwardings')
                  ->onDelete('cascade');

            // ID Institusi Tujuan dari LAPOR! (misal: 12838)
            $table->string('institution_id');
            
            // Nama Institusi Tujuan (misal: Pemerintah Kota Padang)
            $table->string('institution_name');
            
            $table->timestamps();
            
            // Tambahkan index unik agar satu forwarding hanya punya satu disposisi
            $table->unique('laporan_forwarding_id'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disposisi_lapor');
    }
};
